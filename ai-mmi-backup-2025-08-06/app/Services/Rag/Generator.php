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
        // 可选：系统提示，没配就使用一个稳健的默认
        $this->system = env('RAG_SYSTEM_PROMPT') ?: 'You are AI-mmi. Only answer using the provided CONTEXT. '
            .'If the context is insufficient, say you don’t know.';
    }

    /**
     * 纯文本 Prompt 生成（你已有的 ask 控制器把 CONTEXT 拼到 prompt 里）
     */
    public function answer(string $prompt): string
    {
        $prompt = Utf8::normalizeString($prompt);

        $payload = [
            // 可选系统指令（Gemini 支持 systemInstruction）
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
                'temperature'     => 0.0,                  // RAG 更稳
                'topK'            => 1,
                'topP'            => 1.0,
                'maxOutputTokens' => (int) env('GEN_MAX_TOKENS', 1024),
                'thinkingConfig'  => ['thinkingBudget' => 0], // 关闭“thinking”
            ],
            // （如需调整安全等级，可在这里增加 safetySettings）
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
        // 常见返回结构：candidates[0].content.parts[0].text
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    /**
     * （可选）更方便的 RAG 入口：把 question/context 分开给
     */
    public function answerWithContext(string $question, string $context): string
    {
        $q = Utf8::normalizeString($question);
        $c = Utf8::normalizeString($context);

        $prompt = <<<PROMPT
Answer the QUESTION using only the CONTEXT. If the answer is not in the context, say you don't know.

QUESTION:
{$q}

CONTEXT:
{$c}
PROMPT;

        return $this->answer($prompt);
    }
}
