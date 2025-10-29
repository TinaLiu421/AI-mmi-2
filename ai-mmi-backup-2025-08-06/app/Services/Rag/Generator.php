<?php

namespace App\Services\Rag;

use GuzzleHttp\Client;
use App\Support\Utf8;

class Generator
{
    private Client $http;
    private string $model;
    private ?string $system;

    public function __construct()
    {
        // Gemini API
        $this->http   = new Client(['base_uri' => 'https://generativelanguage.googleapis.com']);
        $this->model  = env('GEMINI_CHAT_MODEL', 'gemini-2.5-flash');

        /**
         * —— 统一系统提示（RAG 专用）——
         * 目标：样式统一 + 有细节必写，无细节不填充。
         */
        $this->system = env('RAG_SYSTEM_PROMPT') ?: <<<SYS
You are **AI-mmi**, a professional assistant for migration, education, relocation, and accommodation.
You answer **only** from the given CONTEXT. Never invent or mix external knowledge.

### Style (STRICT)
- Output **pure Markdown** (no HTML). Use `###` headings + concise bullet/numbered lists.
- Bold key terms (e.g., **English requirement**, **Processing time**, **AUD**).
- Keep one short intro sentence (<= 20 words). Keep sentences short.

### Sections (ONLY IF present in CONTEXT)
Use any of these sections when relevant; **skip** a section if the CONTEXT has nothing for it:
- `### Overview` (1–2 bullets max)
- `### Eligibility`
- `### English Requirement`
- `### Health & Character`
- `### Fees`
- `### Processing Time`
- `### Documents / Evidence`
- `### Application Steps`
- `### Conditions / Notes`

### Details Rule (VERY IMPORTANT)
- If CONTEXT contains **any** concrete detail (numbers, dates, durations, fees, form names like **Form 1000**, visa condition codes, streams), include **at least one** such detail under the relevant bullet.
- If the CONTEXT **does not** include concrete details for an item, keep the line natural and concise (do **not** add placeholders or guesses).

If the answer truly cannot be derived from CONTEXT, say: **I don’t know based on the provided context.**
SYS;
    }

    /**
     * 纯文本 Prompt 生成（控制器已把 CONTEXT 拼进 prompt）
     */
    public function answer(string $prompt): string
    {
        $prompt = Utf8::normalizeString($prompt);

        $payload = [
            'systemInstruction' => [
                'parts' => [
                    ['text' => Utf8::normalizeString($this->system ?? '')],
                ],
            ],
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                ],
            ]],
            'generationConfig' => [
            'temperature'        => 0.0,       // 保持事实性
            'topK'               => 1,
            'topP'               => 1.0,
            // ✅ 提升上限：支持更完整的长文
            'maxOutputTokens'    => (int) env('GEN_MAX_TOKENS', 4096),
            'thinkingConfig'     => ['thinkingBudget' => 0],
            'responseMimeType'   => 'text/plain',
            ],                        
        ];

        $res = $this->http->post("/v1beta/models/{$this->model}:generateContent", [
            'headers' => [
                'x-goog-api-key' => env('GEMINI_API_KEY'),
                'Content-Type'   => 'application/json',
            ],
            'body'    => json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION
            ),
            'timeout' => 60,
        ]);

        $data = json_decode((string) $res->getBody(), true) ?? [];
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    /**
     * 问题与上下文分开传入（更常用的 RAG 入口）
     */
    public function answerWithContext(string $question, string $context): string
    {
        $q = Utf8::normalizeString($question);
        $c = Utf8::normalizeString($context);

        // 这里不再重复风格规则，直接复用 system prompt；仅约束“只用上下文”与“Markdown”
        $prompt = <<<PROMPT
Answer the QUESTION using **only** the CONTEXT.
Follow the system style strictly (Markdown headings + bullet/numbered lists, bold key terms).
Apply the **Details Rule**: if any concrete details appear in CONTEXT, include at least one under the right section; if not, keep lines concise without placeholders.

QUESTION:
{$q}

CONTEXT:
{$c}
PROMPT;

        return $this->answer($prompt);
    }
}
