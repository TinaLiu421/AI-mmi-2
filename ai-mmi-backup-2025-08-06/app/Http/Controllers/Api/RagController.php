<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Rag\Embeddings;
use App\Services\Rag\Pinecone;
use App\Services\Rag\Generator;

class RagController extends Controller
{
    // --- helpers for PHP < 8 ---
    private function strStartsWith($haystack, $needle) {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
    private function strContains($haystack, $needle) {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }

    public function ask(Request $req, Embeddings $emb, Pinecone $pc, Generator $gen)
    {
        // 1) 诊断信息
        $diag = array(
            'ctype'   => (string) $req->header('Content-Type'),
            'accept'  => (string) $req->header('Accept'),
            'method'  => $req->method(),
            'qs'      => $req->query(),
            'all'     => $req->all(),
            'rawLen'  => strlen($req->getContent()),
        );

        // 2) 解析 payload（多通道）
        $payload = $req->all(); // 已解析的表单或 JSON
        if (empty($payload)) {
            // 尝试从原始体解析
            $raw = $req->getContent();

            if ($raw !== '' && !mb_check_encoding($raw, 'UTF-8')) {
                $raw = @mb_convert_encoding($raw, 'UTF-8', 'UTF-8,GBK,GB2312,CP936,ISO-8859-1');
            }

            $ctype = strtolower((string)$req->header('Content-Type'));
            $rawTrim = ltrim($raw);

            $isJsonCtype = $this->strStartsWith($ctype, 'application/json');
            $looksJson   = $this->strStartsWith($rawTrim, '{') || $this->strStartsWith($rawTrim, '[');

            if ($raw !== '' && ($isJsonCtype || $looksJson)) {
                $tmp = json_decode($raw, true);
                if (is_array($tmp)) $payload = $tmp;
            }

            if (empty($payload) && $this->strContains($ctype, 'application/x-www-form-urlencoded')) {
                $tmp = array();
                parse_str($raw, $tmp);
                if (is_array($tmp)) $payload = $tmp;
            }
        }

        // query string 兜底
        if (!isset($payload['q']) && $req->query('q')) {
            $payload['q'] = $req->query('q');
        }
        if (!isset($payload['tag']) && $req->query('tag')) {
            $payload['tag'] = $req->query('tag');
        }
        $debug = filter_var(isset($payload['debug']) ? $payload['debug'] : $req->query('debug'), FILTER_VALIDATE_BOOL);

        $q   = trim((string)(isset($payload['q']) ? $payload['q'] : ''));
        $tag = isset($payload['tag']) ? $payload['tag'] : null;

        if ($q === '') {
            $flags = defined('JSON_INVALID_UTF8_SUBSTITUTE')
                ? JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
                : JSON_UNESCAPED_UNICODE;

            return response()->json(array(
                'error' => 'missing q',
                'diag'  => $diag + array(
                    'payload' => $payload,
                    'rawHead' => substr($req->getContent(), 0, 120),
                ),
            ), 422, array(), $flags);
        }

        $out = array('question' => $q, 'tag' => $tag);

        // 3) 生成向量
        try {
            $qvec = $emb->embed($q);
            $out['embedding_dim'] = is_array($qvec) ? count($qvec) : 0;
        } catch (\Throwable $e) {
            Log::error('RAG embed failed', array('err' => $e->getMessage(), 'trace' => $e->getTraceAsString()));
            return response()->json(array('error' => 'embed_failed', 'detail' => $e->getMessage()), 500);
        }

        // 4) Pinecone 检索（去掉命名参数，按位置传参）
        try {
            // 假定签名为 query($vector, $topK = 6, $filter = null)
            $topK   = 6;
            $filter = $tag ? array('tag' => $tag) : null;
            $matches = $pc->query($qvec, $topK, $filter);
            $out['match_count'] = is_array($matches) ? count($matches) : 0;
        } catch (\Throwable $e) {
            Log::error('RAG pinecone query failed', array('err' => $e->getMessage(), 'trace' => $e->getTraceAsString()));
            return response()->json(array('error' => 'pinecone_query_failed', 'detail' => $e->getMessage()), 500);
        }

        // 5) 拼上下文（不用箭头函数）
        $contexts = array();
        if (is_array($matches)) {
            foreach ($matches as $m) {
                $contexts[] = isset($m['metadata']['content']) ? $m['metadata']['content'] : '';
            }
        }
        $contexts = array_filter($contexts, function ($x) { return (string)$x !== ''; });
        $context  = implode("\n---\n", $contexts);
        $out['context_preview'] = mb_strimwidth($context, 0, 300, '…');

        // 6) 生成答案
        try {
            $answer = $gen->answerWithContext($q, $context);
        } catch (\Throwable $e) {
            Log::error('RAG generate failed', array('err' => $e->getMessage(), 'trace' => $e->getTraceAsString()));
            return response()->json(array('error' => 'generate_failed', 'detail' => $e->getMessage()), 500);
        }

        $out['answer'] = $answer;

        // snippets（不用箭头函数）
        $snippets = array();
        if (is_array($matches)) {
            foreach ($matches as $m) {
                $snippets[] = array(
                    'id'      => isset($m['id']) ? $m['id'] : null,
                    'score'   => isset($m['score']) ? $m['score'] : null,
                    'content' => mb_strimwidth(isset($m['metadata']['content']) ? $m['metadata']['content'] : '', 0, 400, '…'),
                );
            }
        }
        $out['snippets'] = $snippets;

        if ($debug) {
            $out['raw_matches'] = $matches;
        }

        return response()->json($out);
    }
}
