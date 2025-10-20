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
    public function ask(Request $req, Embeddings $emb, Pinecone $pc, Generator $gen)
    {
        // —— 1) 收集诊断信息（debug=1 时会返回）——
        $diag = [
            'ctype'   => (string) $req->header('Content-Type'),
            'accept'  => (string) $req->header('Accept'),
            'method'  => $req->method(),
            'qs'      => $req->query(),      // 查询串
            'all'     => $req->all(),        // Laravel 解析后的体
            'jsonAll' => $req->json()->all(),// Laravel 的 JSON 解析
            'rawLen'  => strlen($req->getContent()),
        ];

        // —— 2) 尝试多通道解析 —— 
        $payload = $req->all();                           // 表单或已解析 JSON
        if (empty($payload)) {
            $payload = $req->json()->all();              // 再试 JSON
        }
        if (empty($payload)) {
            // 原始体（可能编码混乱）
            $raw = $req->getContent();

            // 如果不是 UTF-8，尽力转码成 UTF-8
            if ($raw !== '' && !mb_check_encoding($raw, 'UTF-8')) {
                $raw = @mb_convert_encoding($raw, 'UTF-8', 'UTF-8,GBK,GB2312,CP936,ISO-8859-1');
            }

            // 如果是 JSON 就解一次
            if ($raw !== '' && (str_starts_with(strtolower((string)$req->header('Content-Type')), 'application/json') || (str_starts_with(trim($raw), '{') || str_starts_with(trim($raw), '[')))) {
                $try = json_decode($raw, true);
                if (is_array($try)) {
                    $payload = $try;
                }
            }

            // 如果还不行，尝试按 x-www-form-urlencoded 解析
            if (empty($payload) && str_contains(strtolower((string)$req->header('Content-Type')), 'application/x-www-form-urlencoded')) {
                $tmp = [];
                parse_str($raw, $tmp);
                if (is_array($tmp)) $payload = $tmp;
            }
        }

        // 查询串也兜底
        if (!isset($payload['q']) && $req->query('q')) {
            $payload['q'] = $req->query('q');
        }
        if (!isset($payload['tag']) && $req->query('tag')) {
            $payload['tag'] = $req->query('tag');
        }
        $debug = filter_var($payload['debug'] ?? $req->query('debug') ?? false, FILTER_VALIDATE_BOOL);

        $q   = trim((string)($payload['q'] ?? ''));
        $tag = $payload['tag'] ?? null;

        if ($q === '') {
            // 把诊断信息返回给你（不会抛 JSON 编码错）
            return response()->json([
                'error' => 'missing q',
                'diag'  => $diag + [
                    'payload' => $payload,
                    // 只回传原始体前 120 字节，避免非 UTF-8 造成 json 编码错误
                    'rawHead' => mb_substr($req->getContent(), 0, 120),
                ],
            ], 422, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }

        $out = ['question' => $q, 'tag' => $tag];

        // 1) 生成问题向量
        try {
            $qvec = $emb->embed($q);
            $out['embedding_dim'] = is_array($qvec) ? count($qvec) : 0;
        } catch (\Throwable $e) {
            Log::error('RAG embed failed', ['err' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'embed_failed', 'detail' => $e->getMessage()], 500);
        }

        // 2) Pinecone 检索
        try {
            $filter  = $tag ? ['tag' => $tag] : null;
            $matches = $pc->query($qvec, topK: 6, filter: $filter);
            $out['match_count'] = count($matches);
        } catch (\Throwable $e) {
            Log::error('RAG pinecone query failed', ['err' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'pinecone_query_failed', 'detail' => $e->getMessage()], 500);
        }

        // 3) 拼上下文
        $contexts = array_map(fn($m) => $m['metadata']['content'] ?? '', $matches);
        $context  = implode("\n---\n", array_filter($contexts));
        $out['context_preview'] = mb_strimwidth($context, 0, 300, '…');

        // 4) 调用 Gemini 生成
        try {
            $answer = $gen->answerWithContext($q, $context);
        } catch (\Throwable $e) {
            Log::error('RAG generate failed', ['err' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'generate_failed', 'detail' => $e->getMessage()], 500);
        }

        $out['answer'] = $answer;
        $out['snippets'] = array_map(fn($m) => [
            'id'      => $m['id'] ?? null,
            'score'   => $m['score'] ?? null,
            'content' => mb_strimwidth($m['metadata']['content'] ?? '', 0, 400, '…'),
        ], $matches);

        // debug=1 时返回更多原始数据，方便排查
        if ($debug) {
            $out['raw_matches'] = $matches;
        }

        return response()->json($out);
    }
}
