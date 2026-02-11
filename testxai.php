<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== xAI API TEST ===\n\n";

// 1. Get API key
$apiKey = env('XAI_API_KEY');
echo "1. API Key: " . ($apiKey ? substr($apiKey, 0, 10) . '...' : 'NOT FOUND') . "\n";

if (!$apiKey) {
    die("❌ ERROR: Add XAI_API_KEY to .env file\n");
}

// 2. Test endpoint
$url = "https://api.x.ai/v1/chat/completions";

// 3. Make simple request
$data = [
    "model" => "grok-4-1-fast-reasoning",
    "messages" => [["role" => "user", "content" => "Hello"]],
    "max_tokens" => 5
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . $apiKey,
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);

echo "2. Calling API: $url\n";
$start = microtime(true);
$response = curl_exec($ch);
$time = round((microtime(true) - $start) * 1000, 2);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
curl_close($ch);

echo "3. Response time: {$time}ms\n";
echo "4. HTTP Status: $httpCode\n";

// 4. Analyze response
if ($curlErrno) {
    echo "❌ CURL ERROR ($curlErrno): $curlError\n";
    exit;
}

switch($httpCode) {
    case 200:
        $data = json_decode($response, true);
        $reply = $data['choices'][0]['message']['content'] ?? 'No content';
        echo "✅ SUCCESS! Response: '$reply'\n";
        break;
    case 401:
        echo "❌ ERROR 401: Invalid API key\n";
        break;
    case 402:
        echo "❌ ERROR 402: Payment required - check billing on platform.x.ai\n";
        break;
    case 403:
        echo "❌ ERROR 403: Forbidden - key revoked or no permissions\n";
        break;
    case 429:
        echo "❌ ERROR 429: Rate limit exceeded\n";
        break;
    default:
        echo "Response: " . substr($response, 0, 200) . "\n";
}

echo "\n=== TEST COMPLETE ===\n";