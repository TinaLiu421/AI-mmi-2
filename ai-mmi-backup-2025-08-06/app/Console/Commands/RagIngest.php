<?php

// RAG 功能已停用。原始实现保留如下供参考。
// <?php
// 
// namespace App\Console\Commands;
// 
// use Illuminate\Console\Command;
// use Illuminate\Support\Str;
// use App\Models\Chunk;
// use App\Services\Rag\Chunker;
// use App\Services\Rag\Embeddings;
// use App\Services\Rag\Pinecone;
// use App\Support\Utf8;
// 
// /**
//  * 导入语料：切片 -> 嵌入 -> 写入 Pinecone -> 在 MySQL 记录 chunk
//  *
//  * 用法：
//  *  php artisan rag:ingest storage/app/policies --tag=policy
//  *  php artisan rag:ingest storage/docs --tag=visa --ext=txt --batch=100
//  */
// class RagIngest extends Command
// {
//     protected $signature = 'rag:ingest 
//         {path : 文件路径或目录路径} 
//         {--type=file : 源类型(file|url|db等，自定义)} 
//         {--tag= : 语料标签（可选）} 
//         {--ext=txt : 当 path 是目录时匹配的扩展名} 
//         {--batch=100 : 向 Pinecone upsert 的批大小}';
// 
//     protected $description = 'Ingest text chunks into Pinecone (RAG) with UTF-8 sanitization';
// 
//     public function handle(Chunker $chunker, Embeddings $emb, Pinecone $pc)
//     {
//         $path  = $this->argument('path');
//         $type  = (string) $this->option('type');
//         $tag   = (string) ($this->option('tag') ?? '');
//         $ext   = (string) $this->option('ext');
//         $batch = (int) $this->option('batch') ?: 100;
// 
//         // 1) 收集文本源
//         $sources = $this->collectSources($path, $ext);
//         if (empty($sources)) {
//             $this->error("No source files found at: {$path}");
//             return self::FAILURE;
//         }
// 
//         $totalChunks = 0;
//         foreach ($sources as $src) {
//             $file = $src['id'];
//             $raw  = @file_get_contents($file);
// 
//             if ($raw === false) {
//                 $this->warn("Skip unreadable file: {$file}");
//                 continue;
//             }
// 
//             // 2) 统一 UTF-8 清洗
//             $text = Utf8::normalizeString($raw);
//             if ($text === '') {
//                 $this->warn("Skip empty (after normalize): {$file}");
//                 continue;
//             }
// 
//             // 3) 切片
//             $chunks = $chunker->split($text, 600, 80);
//             if (empty($chunks)) {
//                 $this->warn("No chunks generated: {$file}");
//                 continue;
//             }
// 
//             $points = [];
//             foreach ($chunks as $c) {
//                 // 4) 写 DB（记录 chunk 元数据）
//                 $model = Chunk::create([
//                     'source_type' => $type,
//                     'source_id'   => $file,
//                     'chunk_index' => $c['index'],
//                     'content'     => $c['content'],  // 原文存库（如需也可存清洗后）
//                     'meta'        => ['filename' => $file, 'tag' => $tag],
//                 ]);
// 
//                 // 5) 嵌入（Gemini）
//                 $contentForEmbed = Utf8::normalizeString($c['content']);
//                 $vec = $emb->embed($contentForEmbed);
//                 if (!is_array($vec) || count($vec) === 0) {
//                     $this->warn("Embedding failed; skip chunk {$model->id} ({$file}#{$c['index']})");
//                     continue;
//                 }
// 
//                 // 6) 组 Pinecone point（metadata 扁平 + 限长 + UTF-8 正常化）
//                 $metaContent = mb_strimwidth($contentForEmbed, 0, 900, '…');
//                 $points[] = [
//                     'id'       => (string) $model->id,
//                     'values'   => array_values($vec), // 确保是纯数值数组
//                     'metadata' => [
//                         'chunk_id'    => $model->id,
//                         'source_id'   => Utf8::normalizeString($file),
//                         'chunk_index' => $c['index'],
//                         'tag'         => Utf8::normalizeString($tag),
//                         'content'     => Utf8::normalizeString($metaContent),
//                     ],
//                 ];
// 
//                 // 批量 upsert
//                 if (count($points) >= $batch) {
//                     $this->safeUpsert($pc, $points, $file);
//                     $totalChunks += count($points);
//                     $points = [];
//                 }
//             }
// 
//             // 清空尾批
//             if (!empty($points)) {
//                 $this->safeUpsert($pc, $points, $file);
//                 $totalChunks += count($points);
//                 $points = [];
//             }
// 
//             $this->info("Ingested file: {$file} (chunks: ".count($chunks).")");
//         }
// 
//         $this->info("All done. Total chunks upserted: {$totalChunks}");
//         return self::SUCCESS;
//     }
// 
//     /**
//      * 收集文件列表
//      */
//     private function collectSources(string $path, string $ext): array
//     {
//         if (is_dir($path)) {
//             // Windows 下也能用 glob
//             $pattern = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.' . ltrim($ext, '.');
//             $files = glob($pattern) ?: [];
//             return array_map(fn($f) => ['id' => $f], $files);
//         }
// 
//         if (is_file($path)) {
//             return [['id' => $path]];
//         }
// 
//         return [];
//     }
// 
//     /**
//      * 安全 upsert：一致性清洗 + 出错时定位是哪条 point 崩溃
//      */
//     private function safeUpsert(Pinecone $pc, array $points, string $file): void
//     {
//         // 先快速自检，定位问题 point
//         foreach ($points as $i => $p) {
//             $ok = json_encode(
//                 \App\Support\Utf8::normalizeArray($p),
//                 JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
//             );
//             if ($ok === false) {
//                 $meta = $p['metadata'] ?? [];
//                 $this->error("JSON encode failed before upsert. file={$file}, idx={$i}, chunk_id=" . ($meta['chunk_id'] ?? 'n/a'));
//                 // 你也可以在这里 dd($p) 直接查看
//                 // dd($p);
//                 // 尝试缩短并再清洗
//                 if (isset($p['metadata']['content'])) {
//                     $p['metadata']['content'] = Utf8::normalizeString(mb_strimwidth((string)$p['metadata']['content'], 0, 600, '…'));
//                 }
//             }
//         }
// 
//         // 真正 upsert（Pinecone 内部已做 JSON 容错）
//         try {
//             $pc->upsert($points);
//         } catch (\Throwable $e) {
//             // 兜底：逐条尝试，定位出问题的 point
//             $this->warn("Batch upsert failed, fallback to single upserts. file={$file}; reason={$e->getMessage()}");
//             foreach ($points as $p) {
//                 try {
//                     $pc->upsert([$p]);
//                 } catch (\Throwable $e2) {
//                     $meta = $p['metadata'] ?? [];
//                     $this->error("Single upsert failed: chunk_id=" . ($meta['chunk_id'] ?? 'n/a') . "; file={$file}; reason={$e2->getMessage()}");
//                 }
//             }
//         }
//     }
// }
