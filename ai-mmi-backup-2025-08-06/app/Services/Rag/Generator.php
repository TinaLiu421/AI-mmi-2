<?php

// RAG 功能已停用。原始实现保留如下供参考。
// <?php
// 
// namespace App\Services\Rag;
// 
// use GuzzleHttp\Client;
// use App\Support\Utf8;
// 
// class Generator
// {
//     private Client $http;
//     private string $model;
//     private ?string $system;
// 
//     public function __construct()
//     {
//         $this->http  = new Client(['base_uri' => 'https://generativelanguage.googleapis.com']);
//         $this->model = env('GEMINI_CHAT_MODEL', 'gemini-2.5-flash');
// 
//         $this->system = env('RAG_SYSTEM_PROMPT') ?: <<<SYS
// You are **AI-mmi**, a professional assistant for migration, education, relocation, and accommodation.
// You answer **only** from the given CONTEXT. Never invent or mix external knowledge.
// 
// ### Style (STRICT)
// - Output **pure Markdown** (no HTML). Use `###` headings + concise bullet/numbered lists.
// - Bold key terms (e.g., **English requirement**, **Processing time**, **AUD**).
// - Keep one short intro sentence (<= 20 words). Keep sentences short.
// - When summarizing requirements or conditions, include brief explanations, examples, and any quantitative details found in CONTEXT.
// - When multiple criteria exist, expand each into its own bullet instead of grouping them together.
// 
// ### Sections (ONLY IF present in CONTEXT)
// - `### Overview` (1–2 bullets max)
// - `### Eligibility`
// - `### English Requirement`
// - `### Health & Character`
// - `### Fees`
// - `### Processing Time`
// - `### Documents / Evidence`
// - `### Application Steps`
// - `### Conditions / Notes`
// 
// ### Structured Facts (if provided)
// - If CONTEXT includes test scores (IELTS/PTE/TOEFL/OET/Cambridge), add a short parenthetical after the relevant bullet.
// - Only use existing data; never invent.
// 
// ### Details Rule
// - If CONTEXT contains concrete details (numbers/dates/durations/fees/forms/condition codes), include at least one under the right bullet.
// - If no detail is in CONTEXT, keep the line concise without placeholders.
// 
// If not answerable from CONTEXT: **I don’t know based on the provided context.**
// SYS;
//     }
// 
//     private function detectLang(string $text): string
//     {
//         $t = trim((string)$text);
// 
//         // ✅ 强制指令优先
//         if (preg_match('/(用中文(回答|回复)|in\s+Chinese|answer\s+in\s+Chinese)/i', $t)) return 'zh-CN';
//         if (preg_match('/(用英文(回答|回复)|in\s+English|answer\s+in\s+English)/i', $t)) return 'en';
//         if (preg_match('/(用繁体|繁體|traditional\s+Chinese)/i', $t)) return 'zh-TW';
// 
//         // 语言探测
//         if (preg_match('/\p{Han}/u', $t)) {
//             // 简/繁粗判：常见繁体字
//             if (preg_match('/[為麼嗎體臺國裏]/u', $t)) return 'zh-TW';
//             return 'zh-CN';
//         }
//         return 'en';
//     }
// 
//     private function langInstruction(string $lang): string
//     {
//         if ($lang === 'zh-CN') {
//             return '请用简体中文回复。';
//         } elseif ($lang === 'zh-TW') {
//             return '請使用繁體中文回答。';
//         }
//         return 'Please answer in English.';
//     }
// 
//     public function answer(string $prompt): string
//     {
//         $prompt = Utf8::normalizeString($prompt);
// 
//         $payload = [
//             'systemInstruction' => ['parts' => [[ 'text' => Utf8::normalizeString($this->system ?? '') ]]],
//             'contents'          => [[ 'parts' => [[ 'text' => $prompt ]]]],
//             'generationConfig'  => [
//                 'temperature'      => 0.0,
//                 'topK'             => 1,
//                 'topP'             => 1.0,
//                 'maxOutputTokens'  => (int) env('GEN_MAX_TOKENS', 4096),
//                 // 建议：若曾遇到 thinking 不支持报错，可直接移除下一行
//                 'thinkingConfig'   => ['thinkingBudget' => 0],
//                 'responseMimeType' => 'text/plain',
//             ],
//         ];
// 
//         $res  = $this->http->post("/v1beta/models/{$this->model}:generateContent", [
//             'headers' => [
//                 'x-goog-api-key' => env('GEMINI_API_KEY'),
//                 'Content-Type'   => 'application/json',
//             ],
//             'body'    => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION),
//             'timeout' => 60,
//         ]);
//         $data = json_decode((string) $res->getBody(), true) ?? [];
//         return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
//     }
// 
//     /**
//      * 更常用的 RAG 入口
//      * @param string|null $preferredLang 传 'en' / 'zh-CN' / 'zh-TW'，不传则自动判定
//      */
//     public function answerWithContext(string $question, string $context, ?string $preferredLang = null): string
//     {
//         $q = Utf8::normalizeString($question);
//         $c = Utf8::normalizeString($context);
// 
//         $lang = $preferredLang ?: $this->detectLang($question);
//         $langInstruction = $this->langInstruction($lang);
// 
//         $prompt = <<<PROMPT
// {$langInstruction}
// 
// Answer the QUESTION using **only** the CONTEXT.
// Follow the system style strictly (Markdown headings + bullet/numbered lists, bold key terms).
// Apply the **Details Rule**.
// 
// QUESTION:
// {$q}
// 
// CONTEXT:
// {$c}
// PROMPT;
// 
//         return $this->answer($prompt);
//     }
// }
