<?php

// RAG 功能已停用。原始实现保留如下供参考。
// <?php
// 
// namespace App\Services\Rag;
// 
// use GuzzleHttp\Client;
// use App\Support\Utf8;
// 
// class Embeddings
// {
//     private Client $http;
//     private string $model;
// 
//     public function __construct()
//     {
//         $this->http  = new Client(['base_uri' => 'https://generativelanguage.googleapis.com']);
//         // 建议：models/gemini-embedding-001
//         $this->model = env('GEMINI_EMBED_MODEL', 'models/gemini-embedding-001');
//     }
// 
//     /** 单条文本 -> 向量 */
//     public function embed(string $text): array
//     {
//         $payload = [
//             'model'   => $this->model,
//             'content' => [
//                 'parts' => [
//                     ['text' => Utf8::normalizeString($text)],
//                 ],
//             ],
//         ];
// 
//         $res = $this->http->post('/v1beta/models/gemini-embedding-001:embedContent', [
//             'headers' => [
//                 'x-goog-api-key' => env('GEMINI_API_KEY'),
//                 'Content-Type'   => 'application/json',
//             ],
//             // 不用 'json'，改用 body + 容错 json_encode
//             'body'    => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
//             'timeout' => 30,
//         ]);
// 
//         $data = json_decode((string)$res->getBody(), true) ?? [];
//         // 兼容不同返回形态
//         return $data['embedding']['values'] ?? ($data['embeddings'][0]['values'] ?? []);
//     }
// 
//     /** 批量文本 -> 向量数组（与上面相同维度的多条） */
//     public function embedBatch(array $texts): array
//     {
//         $requests = [];
//         foreach ($texts as $t) {
//             $requests[] = [
//                 'model'   => $this->model,
//                 'content' => ['parts' => [['text' => Utf8::normalizeString((string)$t)]]],
//             ];
//         }
// 
//         $res = $this->http->post('/v1beta/models/gemini-embedding-001:batchEmbedContents', [
//             'headers' => [
//                 'x-goog-api-key' => env('GEMINI_API_KEY'),
//                 'Content-Type'   => 'application/json',
//             ],
//             'body'    => json_encode(['requests' => $requests], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
//             'timeout' => 60,
//         ]);
// 
//         $data = json_decode((string)$res->getBody(), true) ?? [];
//         $rows = $data['responses'] ?? [];
//         return array_map(fn($r) => $r['embedding']['values'] ?? [], $rows);
//     }
// }
