<?php

// RAG 功能已停用。原始实现保留如下供参考。
// <?php
// namespace App\Services\Rag;
// 
// use GuzzleHttp\Client;
// use App\Support\Utf8;
// 
// class Pinecone
// {
//     private Client $http;
//     private string $host;
//     private string $ns;
// 
//     public function __construct()
//     {
//         $this->host = rtrim(env('PINECONE_INDEX_HOST'), '/');
//         $this->ns   = env('PINECONE_NAMESPACE', 'aimmi-default');
//         $this->http = new Client([
//             'base_uri' => $this->host,
//             'headers'  => [
//                 'Api-Key'      => env('PINECONE_API_KEY'),
//                 'Content-Type' => 'application/json',
//             ],
//             'timeout' => 30,
//         ]);
//     }
// 
//     /** 统一 JSON 编码（递归清洗 + 容错） */
//     private function encode(array $payload): string
//     {
//         $clean = Utf8::normalizeArray($payload);
//         $json  = json_encode(
//             $clean,
//             JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION
//         );
//         if ($json === false) {
//             // 兜底：把 metadata.content 再次缩短/清洗
//             if (isset($clean['vectors'])) {
//                 foreach ($clean['vectors'] as &$v) {
//                     if (isset($v['metadata']['content'])) {
//                         $v['metadata']['content'] = mb_strimwidth(
//                             Utf8::normalizeString((string)$v['metadata']['content']), 0, 600, '…'
//                         );
//                     }
//                 }
//                 unset($v);
//             }
//             $json = json_encode(
//                 $clean,
//                 JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION | JSON_PARTIAL_OUTPUT_ON_ERROR
//             );
//         }
//         return $json ?: '{}';
//     }
// 
//     public function upsert(array $points): void
//     {
//         // Pinecone 要求 vectors 的 metadata 为扁平 JSON，长度也别太大
//         $payload = [
//             'namespace' => $this->ns,
//             'vectors'   => $points,
//         ];
//         $this->http->post('/vectors/upsert', [
//             'body'    => $this->encode($payload),
//             'timeout' => 30,
//         ]);
//     }
// 
//     public function query(array $vector, int $topK = 15, array $filter = null): array
//     {
//         $payload = [
//             'namespace'       => $this->ns,
//             'vector'          => $vector,
//             'topK'            => $topK,
//             'includeMetadata' => true,
//         ];
//         if ($filter) $payload['filter'] = $filter;
// 
//         $res = $this->http->post('/query', [
//             'body'    => $this->encode($payload),
//             'timeout' => 30,
//         ]);
//         return json_decode((string)$res->getBody(), true)['matches'] ?? [];
//     }
// }
