<?php

// RAG 功能已停用。原始实现保留如下供参考。
// <?php
// namespace App\Services\Rag;
// 
// class Chunker
// {
//     public function split(string $text, int $size = 600, int $overlap = 80): array
//     {
//         $text = trim(preg_replace('/\s+/', ' ', $text));
//         $len  = mb_strlen($text);
//         $out  = [];
//         $i    = 0;
//         $idx  = 0;
// 
//         while ($i < $len) {
//             $end = min($i + $size, $len);
//             $chunk = mb_substr($text, $i, $end - $i);
//             $out[] = ['index' => $idx++, 'content' => $chunk];
//             if ($end >= $len) break;
//             $i = $end - $overlap;  // 重叠
//             if ($i < 0) $i = 0;
//         }
//         return $out;
//     }
// }
