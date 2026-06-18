#!/usr/bin/env php
<?php
/**
 * Startup & company logo fetch regression tests.
 * Usage: php tmp_logo_fetch_test.php
 */
$basePath = __DIR__;
require $basePath . '/vendor/autoload.php';
$app = require_once $basePath . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CompanyLogoFetcher;

$cases = [
  // Job board / platform startups
  ['Soqqle Hong Kong Limited', null, true],
  ['Soqqle', 'https://soqqle.com', true],
  ['AI-mmi', null, true],
  ['Wealthskey', null, true],
  // Well-known tech (sanity)
  ['Google', 'https://google.com', true],
  ['Stripe', null, true],
  ['Notion', null, true],
  ['Canva', null, true],
  // Fintech startups
  ['Airwallex', null, true],
  ['Deel', null, true],
  ['Revolut', null, true],
  // Should stay blank
  ['XYZ Fake Company 99999', null, false],
  ['Random Startup That Does Not Exist Ltd', null, false],
];

$fetcher = new CompanyLogoFetcher('upload/job_logos');
$pass = 0;
$fail = 0;

echo "=== Startup Logo Fetch Tests ===\n\n";

foreach ($cases as [$name, $website, $expectFound]) {
    echo "• {$name}";
    if ($website) echo " ({$website})";
    echo "\n";

    $result = $fetcher->fetch($name, $website, 'testlogo');
    $found = $result !== null && !empty($result['relative_path']);

    if ($found) {
        $path = public_path($result['relative_path']);
        $size = is_file($path) ? filesize($path) : 0;
        $ext  = pathinfo($path, PATHINFO_EXTENSION);
        echo "  FOUND: {$result['relative_path']} ({$size} bytes, .{$ext})\n";
    } else {
        echo "  BLANK (no logo)\n";
    }

    if ($expectFound) {
        if ($found) { echo "  [PASS]\n"; $pass++; }
        else { echo "  [FAIL] expected logo\n"; $fail++; }
    } else {
        if (!$found) { echo "  [PASS] correctly blank\n"; $pass++; }
        else { echo "  [FAIL] should be blank\n"; $fail++; }
    }
    echo "\n";
}

echo "Result: {$pass} passed, {$fail} failed\n";
exit($fail > 0 ? 1 : 0);
