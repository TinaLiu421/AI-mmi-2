<?php
/**
 * Run the member_posts sector migration from public/ folder.
 * Upload to /public and open:
 * https://your-domain.com/run_post_sector_migration_web.php?key=wsk2026
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
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

$migrationName = '2026_03_23_000001_add_sector_to_member_posts_table';

try {
    if (!Schema::hasTable('member_posts')) {
        http_response_code(500);
        echo "[ERROR] member_posts table not found.\n";
        exit;
    }

    DB::beginTransaction();

    if (!Schema::hasColumn('member_posts', 'sector')) {
        Schema::table('member_posts', function (Blueprint $table) {
            $table->string('sector', 20)->default('study')->after('category_country');
            $table->index('sector', 'member_posts_sector_index');
        });
        echo "[OK] Added member_posts.sector column with default 'study'.\n";
    } else {
        echo "[SKIP] member_posts.sector already exists.\n";
    }

    $updatedRows = DB::table('member_posts')
        ->whereNull('sector')
        ->update(['sector' => 'study']);
    echo "[OK] Backfilled sector='study' for {$updatedRows} existing rows.\n";

    if (Schema::hasTable('migrations')) {
        $exists = DB::table('migrations')->where('migration', $migrationName)->exists();
        if (!$exists) {
            $currentBatch = (int) DB::table('migrations')->max('batch');
            DB::table('migrations')->insert([
                'migration' => $migrationName,
                'batch' => $currentBatch > 0 ? $currentBatch + 1 : 1,
            ]);
            echo "[OK] Marked migration as executed: {$migrationName}\n";
        } else {
            echo "[SKIP] Migration already marked: {$migrationName}\n";
        }
    } else {
        echo "[WARN] migrations table not found, skipped migration mark.\n";
    }

    DB::commit();

    $sectorCounts = DB::table('member_posts')
        ->select('sector', DB::raw('COUNT(*) AS total'))
        ->groupBy('sector')
        ->orderBy('sector')
        ->get();

    echo "\nCurrent sector counts:\n";
    foreach ($sectorCounts as $row) {
        echo '- ' . (string) $row->sector . ': ' . (int) $row->total . "\n";
    }

    echo "\n[DONE] Post sector migration completed successfully. Delete this file now.\n";
} catch (Throwable $e) {
    try {
        DB::rollBack();
    } catch (Throwable $rollbackError) {
    }

    http_response_code(500);
    echo "[ERROR] " . $e->getMessage() . "\n";
}