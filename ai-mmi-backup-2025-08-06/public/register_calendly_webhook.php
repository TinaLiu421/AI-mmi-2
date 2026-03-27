<?php
/**
 * Register the Calendly webhook subscription (one-time setup).
 * Upload to /public and open:
 * https://ai-mmi.com/register_calendly_webhook.php?key=wsk2026
 * DELETE immediately after success.
 */

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_GET['key']) || $_GET['key'] !== 'wsk2026') {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

// ── Hardcoded credentials (no .env needed) ────────────────────────────────────
$token      = 'eyJraWQiOiIxY2UxZTEzNjE3ZGNmNzY2YjNjZWJjY2Y4ZGM1YmFmYThhNjVlNjg0MDIzZjdjMzJiZTgzNDliMjM4MDEzNWI0IiwidHlwIjoiUEFUIiwiYWxnIjoiRVMyNTYifQ.eyJpc3MiOiJodHRwczovL2F1dGguY2FsZW5kbHkuY29tIiwiaWF0IjoxNzc0NTcwNjkyLCJqdGkiOiI4NDU5NzM4Yi1lMzAwLTQ0ZTUtOTczZi0zYWI0ZjU1Zjc2NDkiLCJ1c2VyX3V1aWQiOiIyZWYzMDQyMy0wMmFlLTQyNWItOWI3NS0wMDJjMWQ4NjczNGMiLCJzY29wZSI6InNjaGVkdWxlZF9ldmVudHM6cmVhZCBhdmFpbGFiaWxpdHk6cmVhZCB3ZWJob29rczpyZWFkIHdlYmhvb2tzOndyaXRlIHVzZXJzOnJlYWQifQ.n36aXox1bGJ68ZJWzLPTzsyFxdsmwGqGQK8s1pe4-hp7aMJL9_uLK9Qz_CGevyM27i4jqa0b-zT6u857aktYKg';
$signingKey = '4940a185ff10cb9243e1be0d320fbd741efad2c4c95bce0f36ad3882220812a1';
$webhookUrl = 'https://ai-mmi.com/calendly/webhook';
// ─────────────────────────────────────────────────────────────────────────────

echo "=== Calendly Webhook Registration ===\n\n";
echo "Webhook URL : {$webhookUrl}\n\n";

// ── Helper functions ──────────────────────────────────────────────────────────
function calendly_get(string $url, string $token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($body, true)];
}

function calendly_post(string $url, string $token, array $data): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($body, true)];
}
// ─────────────────────────────────────────────────────────────────────────────

// Step 1: Get current user
echo "Step 1: Fetching Calendly user info...\n";
$me = calendly_get('https://api.calendly.com/users/me', $token);

if ($me['code'] !== 200 || empty($me['body']['resource'])) {
    echo "[ERROR] Failed to fetch user (HTTP {$me['code']})\n";
    echo json_encode($me['body'], JSON_PRETTY_PRINT) . "\n";
    exit;
}

$userUri = $me['body']['resource']['uri'];
$orgUri  = $me['body']['resource']['current_organization'];
echo "  User : {$userUri}\n";
echo "  Org  : {$orgUri}\n\n";

// Step 2: Check if webhook already exists
echo "Step 2: Checking for existing webhook...\n";
$listUrl  = 'https://api.calendly.com/webhook_subscriptions'
          . '?organization=' . urlencode($orgUri)
          . '&user='         . urlencode($userUri)
          . '&scope=user';
$existing = calendly_get($listUrl, $token);

if ($existing['code'] === 200) {
    foreach ($existing['body']['collection'] ?? [] as $sub) {
        if (rtrim($sub['callback_url'], '/') === rtrim($webhookUrl, '/')) {
            echo "[ALREADY REGISTERED] Webhook already exists: {$sub['uri']}\n";
            echo "  State : {$sub['state']}\n";
            echo "\nNothing to do. Delete this file from the server.\n";
            exit;
        }
    }
    echo "  No existing webhook found for this URL.\n\n";
} else {
    echo "[WARN] Could not list existing webhooks (HTTP {$existing['code']}), proceeding anyway...\n\n";
}

// Step 3: Register the webhook
echo "Step 3: Registering webhook subscription...\n";
$result = calendly_post('https://api.calendly.com/webhook_subscriptions', $token, [
    'url'          => $webhookUrl,
    'events'       => ['invitee.created'],
    'organization' => $orgUri,
    'user'         => $userUri,
    'scope'        => 'user',
    'signing_key'  => $signingKey,
]);

if ($result['code'] === 201 && !empty($result['body']['resource']['uri'])) {
    $res = $result['body']['resource'];
    echo "[SUCCESS] Webhook registered!\n";
    echo "  URI    : {$res['uri']}\n";
    echo "  State  : {$res['state']}\n";
    echo "\nDELETE this file from the server now.\n";
} else {
    echo "[ERROR] Registration failed (HTTP {$result['code']})\n";
    echo json_encode($result['body'], JSON_PRETTY_PRINT) . "\n";
}
