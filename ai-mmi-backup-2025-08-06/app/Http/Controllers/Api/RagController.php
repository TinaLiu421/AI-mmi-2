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
            // 假定签名为 query($vector, $topK = 15, $filter = null)
            $topK   = 15;
            $filter = $tag ? array('tag' => $tag) : null;
            $matches = $pc->query($qvec, $topK, $filter);
            $out['match_count'] = is_array($matches) ? count($matches) : 0;

            // === 新增：同源邻接扩展（把被切散的表/段落黏回来） ===
            if (is_array($matches) && count($matches) > 0) {
                $matches = $this->expandNeighbors($matches, $qvec, $pc, $tag, /*window*/ 2, /*seeds*/ 2, /*maxNeighbors*/ 12);
                $out['match_count_after_neighbor_expansion'] = count($matches);
            }
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

        // =======================================================
        // ✨ 英语细节提取（regex） + 上下文增强
        // =======================================================
        $ctxText = $context;

        function extractEnglishDetails(string $t): array {
            $found = [];

            // IELTS overall & band
            if (preg_match('/IELTS[^0-9]*?([0-9](?:\.[0-9])?)\s*(?:overall|score)?[^0-9\n]*?(?:no\s*(?:band|component)\s*(?:below|less\s*than)\s*)?([0-9](?:\.[0-9])?)?/i', $t, $m)) {
                $found['IELTS'] = [
                    'overall' => $m[1] ?? null,
                    'band'    => $m[2] ?? null,
                ];
            }

            // PTE Academic
            if (preg_match('/PTE[^0-9]*?([0-9]{2,3})(?:[^0-9\n]+?(?:no\s*(?:skill|band)\s*(?:below|less\s*than)\s*)?([0-9]{2,3}))?/i', $t, $m)) {
                $found['PTE'] = [
                    'overall' => $m[1] ?? null,
                    'band'    => $m[2] ?? null,
                ];
            }

            // TOEFL iBT
            if (preg_match('/TOEFL[^0-9]*?([0-9]{2,3})/i', $t, $m)) {
                $found['TOEFL'] = ['total' => $m[1] ?? null];
            }

            // OET
            if (preg_match('/OET[^A-Za-z0-9]*?(?:grade\s*)?([ABC][+]?)/i', $t, $m)) {
                $found['OET'] = ['grade' => strtoupper($m[1] ?? '')];
            }

            // Cambridge C1/C2
            if (preg_match('/Cambridge[^.\n]*?(C1|C2|CAE|CPE)/i', $t, $m)) {
                $found['Cambridge'] = ['level' => $m[1] ?? null];
            }

            // 有效期（within 3 years / valid for 2 years）
            if (preg_match('/(within|valid\s*for)\s*(\d)\s*year/i', $t, $m)) {
                $found['valid_years'] = (int)($m[2] ?? 0);
            }

            // 免测国家
            if (preg_match('/(UK|United\s*Kingdom|USA|United\s*States|Canada|New\s*Zealand|Republic\s*of\s*Ireland)[^.\n]*(exempt|do\s*not\s*need|no\s*test)/i', $t)) {
                $found['exempt_countries'] = true;
            }

            return $found;
        }

        $englishFacts = extractEnglishDetails($ctxText);

        if (!empty($englishFacts)) {
            $context .= "\n\n---\nSTRUCTURED_FACTS:\n" . json_encode(['english' => $englishFacts], JSON_UNESCAPED_UNICODE);
        }

        // 6) 生成答案
        try {
            $lang   = (string) $req->input('lang', 'en');
            $answer = $gen->answerWithContext($q, $context);
        } catch (\Throwable $e) {
            Log::error('RAG generate failed', array('err' => $e->getMessage(), 'trace' => $e->getTraceAsString()));
            return response()->json(array('error' => 'generate_failed', 'detail' => $e->getMessage()), 500);
        }

        // ✅ 随机友好结尾语句
        $followups = [
            "Would you like me to provide more help or detailed guidance related to this topic?",
            "Is there anything else you’d like to know about this visa or process?",
            "Would you like me to explain the next steps or related options in more detail?",
            "Would you like a quick summary of related requirements or how to apply?",
            "Is there anything else I can help you clarify before you move forward?",
            "Would you like me to expand on this with more practical examples or advice?",
        ];

        $randomFollowup = $followups[array_rand($followups)];
        $answer = trim($answer) . "\n\n---\n" . $randomFollowup;

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

    private function idKey(array $m): string {
    // 去重用：优先 m['id']，否则 fallback 到 source_id+chunk_index
        $sid = isset($m['metadata']['source_id']) ? (string)$m['metadata']['source_id'] : 'na';
        $idx = isset($m['metadata']['chunk_index']) ? (string)$m['metadata']['chunk_index'] : 'na';
        return (isset($m['id']) ? (string)$m['id'] : $sid.'#'.$idx);
    }
    private function toInt($x, $default = 0): int {
        return is_numeric($x) ? (int)$x : $default;
    }

    private function expandNeighbors(array $matches, array $qvec, Pinecone $pc, ?string $tag,
                                    int $windowK = 2, int $seedsPerSource = 2, int $maxNeighborsPerSource = 15): array
    {
        // ① 先按 source_id 分组，挑选每个源的若干“种子块”（靠前的命中）
        $bySource = array();
        foreach ($matches as $m) {
            $sid = isset($m['metadata']['source_id']) ? (string)$m['metadata']['source_id'] : null;
            if (!$sid) continue;
            if (!isset($bySource[$sid])) $bySource[$sid] = array();
            $bySource[$sid][] = $m;
        }

        // ② 合并容器 + 把第一跳结果先放入（去重）
        $merged = array();
        foreach ($matches as $m) {
            $merged[$this->idKey($m)] = $m;
        }

        // ③ 对每个 source，取前 seedsPerSource 个种子块做“范围过滤检索”
        foreach ($bySource as $sid => $list) {
            // 按相似度分数降序（Pinecone 已排序，这里保险再排一次）
            usort($list, function($a,$b){
                $sa = isset($a['score']) ? (float)$a['score'] : 0.0;
                $sb = isset($b['score']) ? (float)$b['score'] : 0.0;
                if ($sa === $sb) return 0;
                return ($sa > $sb) ? -1 : 1;
            });
            $seeds = array_slice($list, 0, $seedsPerSource);

            foreach ($seeds as $seed) {
                $idx = $this->toInt($seed['metadata']['chunk_index'] ?? null, 0);
                $lo  = $idx - $windowK;
                $hi  = $idx + $windowK;

                // Pinecone metadata 过滤：source_id 等值 + chunk_index 范围
                $filter2 = array(
                    'source_id'   => array('$eq' => $sid),
                    'chunk_index' => array('$gte' => $lo, '$lte' => $hi),
                );
                if ($tag) {
                    // 叠加已有 tag 过滤（与你现有用法一致）
                    $filter2 = array('tag' => array('$eq' => $tag)) + $filter2;
                }

                // 第二跳查询：同向量 qvec，但 topK 提高一点以覆盖范围（不会很多，因为被过滤约束住了）
                try {
                    $neighbors = $pc->query($qvec, $maxNeighborsPerSource, $filter2);
                } catch (\Throwable $e) {
                    // 出错就跳过该 seed，保证健壮性
                    continue;
                }

                // 加入并去重
                if (is_array($neighbors)) {
                    foreach ($neighbors as $nm) {
                        $merged[$this->idKey($nm)] = $nm;
                    }
                }
            }
        }

        // ④ 输出数组化的合并结果
        return array_values($merged);
    }

}
