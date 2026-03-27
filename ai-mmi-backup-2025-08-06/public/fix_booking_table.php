<?php
/**
 * Diagnose & fix the agent_chat_meeting_bookings table on production.
 * Upload to /public and open:
 * https://ai-mmi.com/fix_booking_table.php?key=wsk2026
 * DELETE immediately after success.
 *
 * Optionally pass ?email=user@example.com to check a specific member's booking state.
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

// Capture any bootstrap errors
try {
    $app = require_once $basePath . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
} catch (\Throwable $e) {
    echo "[BOOT ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit;
}

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Booking Table Diagnostics ===\n\n";

$prefix = DB::getTablePrefix();
$table  = $prefix . 'agent_chat_meeting_bookings';
echo "Table name (with prefix): {$table}\n";
echo "Laravel DB prefix: '{$prefix}'\n\n";

// ── Step 1: Check if table exists ────────────────────────────────────────────
echo "--- Step 1: Table existence ---\n";
try {
    $exists = Schema::hasTable('agent_chat_meeting_bookings');
    echo $exists ? "[OK] Table exists.\n\n" : "[MISSING] Table does NOT exist.\n\n";
} catch (\Throwable $e) {
    echo "[ERROR] " . $e->getMessage() . "\n\n";
    exit;
}

// ── Step 2: Create if missing ────────────────────────────────────────────────
if (!$exists) {
    echo "--- Step 2: Creating table ---\n";
    try {
        DB::statement("CREATE TABLE IF NOT EXISTS `{$table}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `member_id` BIGINT UNSIGNED NOT NULL,
            `status` VARCHAR(30) NOT NULL DEFAULT 'booked',
            `plan_code` VARCHAR(30) NULL DEFAULT NULL,
            `agent_attended` TINYINT(1) NOT NULL DEFAULT 0,
            `attended_at` TIMESTAMP NULL DEFAULT NULL,
            `calendly_event_uri` VARCHAR(500) NULL,
            `calendly_invitee_uri` VARCHAR(500) NULL,
            `booked_at` TIMESTAMP NULL DEFAULT NULL,
            `created_at` TIMESTAMP NULL DEFAULT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `agent_chat_meeting_bookings_member_id_unique` (`member_id`),
            KEY `agent_chat_meeting_bookings_status_index` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "[OK] Table created.\n\n";
    } catch (\Throwable $e) {
        echo "[ERROR creating table] " . $e->getMessage() . "\n\n";
        exit;
    }
} else {
    // ── Step 3: Check and add missing columns ────────────────────────────────
    echo "--- Step 3: Checking columns ---\n";
    try {
        $existingColumns = DB::select("SHOW COLUMNS FROM `{$table}`");
        $colNames = array_map(function ($col) {
            // Handle both object and array results
            return is_object($col) ? ($col->Field ?? $col->field ?? '') : ($col['Field'] ?? $col['field'] ?? '');
        }, $existingColumns);

        echo "Existing columns: " . implode(', ', $colNames) . "\n\n";

        $requiredColumns = [
            'plan_code'            => "ALTER TABLE `{$table}` ADD COLUMN `plan_code` VARCHAR(30) NULL DEFAULT NULL AFTER `status`",
            'agent_attended'       => "ALTER TABLE `{$table}` ADD COLUMN `agent_attended` TINYINT(1) NOT NULL DEFAULT 0 AFTER `plan_code`",
            'attended_at'          => "ALTER TABLE `{$table}` ADD COLUMN `attended_at` TIMESTAMP NULL DEFAULT NULL AFTER `agent_attended`",
            'calendly_event_uri'   => "ALTER TABLE `{$table}` ADD COLUMN `calendly_event_uri` VARCHAR(500) NULL DEFAULT NULL AFTER `attended_at`",
            'calendly_invitee_uri' => "ALTER TABLE `{$table}` ADD COLUMN `calendly_invitee_uri` VARCHAR(500) NULL DEFAULT NULL AFTER `calendly_event_uri`",
            'booked_at'            => "ALTER TABLE `{$table}` ADD COLUMN `booked_at` TIMESTAMP NULL DEFAULT NULL AFTER `calendly_invitee_uri`",
        ];

        foreach ($requiredColumns as $col => $sql) {
            if (!in_array($col, $colNames, true)) {
                try {
                    DB::statement($sql);
                    echo "[ADDED] Column '{$col}' added.\n";
                } catch (\Throwable $e) {
                    echo "[ERROR adding {$col}] " . $e->getMessage() . "\n";
                }
            } else {
                echo "[OK] Column '{$col}' exists.\n";
            }
        }
        echo "\n";
    } catch (\Throwable $e) {
        echo "[ERROR checking columns] " . $e->getMessage() . "\n\n";
    }
}

// ── Step 4: Show current bookings (optional filter by email) ─────────────────
echo "--- Step 4: Current bookings ---\n";
try {
    $email = trim($_GET['email'] ?? '');
    if ($email !== '') {
        $member = DB::table('members')->where('email', $email)->first();
        if (!$member) {
            echo "Member not found for email: {$email}\n";
        } else {
            echo "Member: #{$member->id} {$member->email}\n";
            $booking = DB::table('agent_chat_meeting_bookings')->where('member_id', $member->id)->first();
            if ($booking) {
                echo "Booking found:\n";
                echo "  id             : {$booking->id}\n";
                echo "  status         : {$booking->status}\n";
                echo "  plan_code      : " . ($booking->plan_code ?? 'NULL') . "\n";
                echo "  agent_attended : {$booking->agent_attended}\n";
                echo "  attended_at    : " . ($booking->attended_at ?? 'NULL') . "\n";
                echo "  booked_at      : " . ($booking->booked_at ?? 'NULL') . "\n";
            } else {
                echo "No booking record found for this member.\n";
            }
        }
    } else {
        $count = DB::table('agent_chat_meeting_bookings')->count();
        echo "Total booking records: {$count}\n";
        echo "(Add ?email=user@example.com to check a specific member)\n";
    }
    echo "\n";
} catch (\Throwable $e) {
    echo "[ERROR querying bookings] " . $e->getMessage() . "\n\n";
}

// ── Step 5: Test INSERT permission ───────────────────────────────────────────
echo "--- Step 5: Test write permission ---\n";
try {
    $testId = DB::table('agent_chat_meeting_bookings')->insertGetId([
        'member_id'      => 999999999,
        'status'         => 'test',
        'plan_code'      => 'test',
        'agent_attended' => 0,
        'booked_at'      => now(),
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);
    DB::table('agent_chat_meeting_bookings')->where('id', $testId)->delete();
    echo "[OK] INSERT + DELETE work correctly.\n\n";
} catch (\Throwable $e) {
    echo "[ERROR] Write test failed: " . $e->getMessage() . "\n\n";
}

echo "=== Done. Delete this file from the server. ===\n";
