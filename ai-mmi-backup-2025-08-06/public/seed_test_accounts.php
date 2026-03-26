<?php
/**
 * Test Account Seeder — run once on production to create 5 plan test accounts.
 * Access via: https://ai-mmi.com/seed_test_accounts.php?secret=aimmi_seed_2026
 * DELETE this file after use.
 */

// Simple secret gate — change or remove after use
if (($_GET['secret'] ?? '') !== 'aimmi_seed_2026') {
    http_response_code(403);
    exit('Forbidden');
}

// ── Load .env from parent directory ──────────────────────────────────────────
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val, " \t\n\r\0\x0B\"'");
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $val;
            putenv("$key=$val");
        }
    }
}
loadEnv(__DIR__ . '/../.env');

$host   = $_ENV['DB_HOST']     ?? '127.0.0.1';
$port   = $_ENV['DB_PORT']     ?? '3306';
$dbname = $_ENV['DB_DATABASE'] ?? '';
$user   = $_ENV['DB_USERNAME'] ?? '';
$pass   = $_ENV['DB_PASSWORD'] ?? '';
$prefix = $_ENV['DB_TABLEPREFIX'] ?? '';

// ── Connect ───────────────────────────────────────────────────────────────────
$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    exit('DB connection failed: ' . htmlspecialchars($e->getMessage()));
}

$now = date('Y-m-d H:i:s');

// ── Plan map: code → plan_id (read from DB so it stays in sync) ───────────────
$planRows = $pdo->query("SELECT id, code FROM {$prefix}plans")->fetchAll(PDO::FETCH_KEY_PAIR);
// $planRows = ['free' => 1, 'all_ai' => 2, ...]

// ── Test accounts ─────────────────────────────────────────────────────────────
$testPassword  = password_hash('Test1234!', PASSWORD_BCRYPT);
$accounts = [
    ['email' => 'test.free@ai-mmi.com',    'alias' => 'Test Free',         'plan' => 'free'],
    ['email' => 'test.allai@ai-mmi.com',   'alias' => 'Test AI Smart',     'plan' => 'all_ai'],
    ['email' => 'test.hybrid@ai-mmi.com',  'alias' => 'Test AI+Agent',     'plan' => 'hybrid'],
    ['email' => 'test.diy@ai-mmi.com',     'alias' => 'Test DIY',          'plan' => 'premium'],
    ['email' => 'test.vip@ai-mmi.com',     'alias' => 'Test VIP',          'plan' => 'vip'],
];

header('Content-Type: text/plain; charset=utf-8');

foreach ($accounts as $acc) {
    $email    = $acc['email'];
    $alias    = $acc['alias'];
    $planCode = $acc['plan'];
    $planId   = $planRows[$planCode] ?? null;

    if (!$planId) {
        echo "SKIP  $email — plan '$planCode' not found in DB\n";
        continue;
    }

    // Check if member already exists
    $stmt = $pdo->prepare("SELECT id FROM {$prefix}member WHERE email = ?");
    $stmt->execute([$email]);
    $existingId = $stmt->fetchColumn();

    if ($existingId) {
        // Update password and ensure verified + active
        $pdo->prepare("UPDATE {$prefix}member SET password = ?, alias_name = ?, verified = 1, status = 1, updated_at = ? WHERE id = ?")
            ->execute([$testPassword, $alias, $now, $existingId]);
        $memberId = $existingId;
        echo "UPDATE $email (id=$memberId)\n";
    } else {
        // Insert new member
        $pdo->prepare("INSERT INTO {$prefix}member
            (type, alias_name, email, password, verified, status, created_at, updated_at)
            VALUES (1, ?, ?, ?, 1, 1, ?, ?)")
            ->execute([$alias, $email, $testPassword, $now, $now]);
        $memberId = (int)$pdo->lastInsertId();
        echo "INSERT $email (id=$memberId)\n";
    }

    // Cancel any existing active subscriptions for this member
    $pdo->prepare("UPDATE {$prefix}subscriptions SET status = 'cancelled', updated_at = ? WHERE member_id = ? AND status = 'active'")
        ->execute([$now, $memberId]);

    // Create fresh active subscription (no ends_at = never expires)
    $pdo->prepare("INSERT INTO {$prefix}subscriptions
        (member_id, plan_id, status, started_at, ends_at, currency, amount_usd, created_at, updated_at)
        VALUES (?, ?, 'active', ?, NULL, 'USD', 0.00, ?, ?)")
        ->execute([$memberId, $planId, $now, $now, $now]);

    echo "  → subscription: $planCode (plan_id=$planId)\n";
}

echo "\nDone. Password for all accounts: Test1234!\n";
echo "Accounts: test.free / test.allai / test.hybrid / test.diy / test.vip @ai-mmi.com\n";
echo "\n*** DELETE this file after testing: public/seed_test_accounts.php ***\n";
