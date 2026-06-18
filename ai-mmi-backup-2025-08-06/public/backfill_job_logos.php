<?php
if (($_GET['key'] ?? '') !== 'aimmi2026setup') { http_response_code(403); die('Forbidden'); }
header('Content-Type: text/plain');

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';
$app = require_once $basePath . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CompanyLogoFetcher;

$db = app('db');
$fetcher = new CompanyLogoFetcher('upload/job_logos');

$jobs = $db->table('job_postings')->whereNull('deleted_at')->where('status', 1)->get();
echo "Backfilling logos for " . count($jobs) . " jobs...\n\n";

foreach ($jobs as $job) {
    if (!empty($job->company_logo)) {
        echo "Skip #{$job->id} {$job->company_name} (already has logo)\n";
        continue;
    }
    $website = $job->application_url ?: null;
    $result = $fetcher->fetch($job->company_name ?? '', $website);
    if ($result) {
        $db->table('job_postings')->where('id', $job->id)->update([
            'company_logo' => $result['relative_path'],
            'updated_at' => now()->toDateTimeString(),
        ]);
        echo "OK #{$job->id} {$job->company_name} -> {$result['relative_path']}\n";
    } else {
        echo "— #{$job->id} {$job->company_name} (no logo found, left blank)\n";
    }
}
echo "\nDone.\n";
