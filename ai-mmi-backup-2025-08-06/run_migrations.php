<?php
/**
 * Emergency Migration Runner
 * Upload this file to your server root via Cyberduck
 * Then visit: https://ai-mmi.com/run_migrations.php
 * 
 * IMPORTANT: This file will delete itself after running
 */

// Security check - only allow from specific IP or remove this block
// Uncomment and set your IP if needed:
// $allowed_ip = 'YOUR_IP_HERE';
// if ($_SERVER['REMOTE_ADDR'] !== $allowed_ip) {
//     die('Access denied');
// }

echo "<h1>Running Migrations...</h1>";
echo "<pre>";

try {
    // Load Laravel (go up one directory from public/)
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    // Boot the kernel
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "✓ Laravel loaded successfully\n\n";
    
    // Run specific migration
    echo "Running: database/migrations/2026_02_11_000001_create_agent_chat_messages_table.php\n";
    $exitCode = $kernel->call('migrate', [
        '--path' => 'database/migrations/2026_02_11_000001_create_agent_chat_messages_table.php',
        '--force' => true
    ]);
    
    if ($exitCode === 0) {
        echo "\n✓ Migration completed successfully!\n";
        
        // Verify table was created
        $pdo = DB::connection()->getPdo();
        $result = $pdo->query("SHOW TABLES LIKE 'app_agent_chat_messages'")->fetch();
        
        if ($result) {
            echo "✓ Table 'app_agent_chat_messages' confirmed in database\n";
        }
        
        echo "\n<strong style='color: green;'>SUCCESS! Agent chat is now ready to use.</strong>\n";
    } else {
        echo "\n✗ Migration failed with exit code: $exitCode\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";

// Self-destruct after 5 seconds
echo "<script>
setTimeout(function() {
    window.location.href = 'run_migrations.php?delete=1';
}, 5000);
</script>";

// Delete this file if requested
if (isset($_GET['delete'])) {
    if (unlink(__FILE__)) {
        echo "<h2 style='color: green;'>✓ Migration script deleted successfully</h2>";
        echo "<p>You can now close this window.</p>";
    } else {
        echo "<h2 style='color: red;'>✗ Please manually delete run_migrations.php from your server</h2>";
    }
    exit;
}

echo "<p><em>This script will delete itself in 5 seconds...</em></p>";
