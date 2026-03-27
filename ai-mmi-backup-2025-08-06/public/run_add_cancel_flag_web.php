<?php
/**
 * One-time web script: adds cancel_at_period_end column to subscriptions table.
 * Run once via: https://ai-mmi.com/run_add_cancel_flag_web.php?key=wsk2026
 * DELETE this file after running.
 */

$key = $_GET['key'] ?? '';
if ($key !== 'wsk2026') {
    http_response_code(403);
    exit('Forbidden');
}

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

try {
    if (Schema::hasColumn('subscriptions', 'cancel_at_period_end')) {
        echo "Column 'cancel_at_period_end' already exists in subscriptions table. Nothing to do.\n";
    } else {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('cancel_at_period_end')->default(false)->after('stripe_subscription_id');
        });
        echo "SUCCESS: Column 'cancel_at_period_end' added to subscriptions table.\n";
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo "ERROR: " . htmlspecialchars($e->getMessage()) . "\n";
}
