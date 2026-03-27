<?php
/**
 * Fix Stripe price IDs for AI Smart Plan and AI + Agent Plan.
 * Upload to /public and open:
 * https://ai-mmi.com/run_stripe_price_fix_web.php?key=wsk2026
 * DELETE immediately after success.
 */

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_GET['key']) || $_GET['key'] !== 'wsk2026') {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$basePath = dirname(__DIR__);

if (!file_exists($basePath . '/vendor/autoload.php') || !file_exists($basePath . '/bootstrap/app.php')) {
    http_response_code(500);
    echo "Cannot locate Laravel bootstrap files.\n";
    exit;
}

require $basePath . '/vendor/autoload.php';
$app = require_once $basePath . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    DB::beginTransaction();

    $updates = [
        'all_ai' => 'price_1TFFF0KcbpMSEKkQs9bnP4bs',
        'hybrid' => 'price_1TFFFdKcbpMSEKkQzo54WAWl',
    ];

    foreach ($updates as $code => $priceId) {
        $affected = DB::table('plans')
            ->where('code', $code)
            ->update([
                'stripe_price_id' => $priceId,
                'updated_at'      => now(),
            ]);
        echo "[OK] plans.{$code} => stripe_price_id='{$priceId}' ({$affected} row updated)\n";
    }

    DB::commit();
    echo "\nDone. DELETE this file from the server immediately.\n";
} catch (\Throwable $e) {
    DB::rollBack();
    http_response_code(500);
    echo "[ERROR] " . $e->getMessage() . "\n";
}
