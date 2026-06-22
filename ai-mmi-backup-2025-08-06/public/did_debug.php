<?php
// Temporary D-ID debug script – DELETE after use
// Access: https://ai-mmi.com/did_debug.php?key=aimmi_did_debug_2026
if (($_GET['key'] ?? '') !== 'aimmi_did_debug_2026') {
    http_response_code(403);
    die('Forbidden');
}

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apiKey   = env('DID_API_KEY', '');
$imgUrl   = env('DID_PRESENTER_IMG', '');
$voiceId  = env('DID_VOICE_ID', 'en-US-JennyNeural');

echo "<pre>\n";
echo "DID_API_KEY set: " . (!empty($apiKey) ? 'YES ('.strlen($apiKey).' chars)' : 'NO') . "\n";
echo "DID_PRESENTER_IMG: $imgUrl\n";
echo "DID_VOICE_ID: $voiceId\n\n";

// Encode auth
$authValue = (strpos($apiKey, ':') !== false) ? base64_encode($apiKey) : $apiKey;

// Test stream creation
$ch = curl_init('https://api.d-id.com/talks/streams');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Basic ' . $authValue,
    ],
    CURLOPT_POSTFIELDS     => json_encode([
        'source_url'         => $imgUrl,
        'compatibility_mode' => 'on',
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_PROXY          => '',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($curlErr) echo "cURL Error: $curlErr\n";
echo "Response:\n";
$decoded = json_decode($response, true);
echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
echo "</pre>";
