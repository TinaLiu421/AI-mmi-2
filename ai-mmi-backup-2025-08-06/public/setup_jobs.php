<?php
/**
 * One-time setup: Job Applications feature tables.
 * DELETE THIS FILE after running on production.
 * Access: https://ai-mmi.com/setup_jobs.php?key=aimmi2026setup
 */
if (($_GET['key'] ?? '') !== 'aimmi2026setup') { http_response_code(403); die('Forbidden'); }

header('Content-Type: text/plain');

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';
$app = require_once $basePath . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$db = app('db');

echo "=== Setting up Job Applications Feature ===\n\n";

$tables = [
    'app_job_seeker_profiles' => "CREATE TABLE IF NOT EXISTS `app_job_seeker_profiles` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `member_id` INT NOT NULL DEFAULT 0,
        `headline` VARCHAR(300) NULL,
        `bio` TEXT NULL,
        `nationality` VARCHAR(100) NULL,
        `current_country` VARCHAR(100) NULL,
        `current_city` VARCHAR(100) NULL,
        `open_to_work` VARCHAR(50) NULL,
        `target_roles` TEXT NULL,
        `target_locations` TEXT NULL,
        `employment_preference` VARCHAR(50) NULL,
        `education_history` MEDIUMTEXT NULL,
        `work_experience` TEXT NULL,
        `skills` TEXT NULL,
        `language_scores` TEXT NULL,
        `resume_path` VARCHAR(500) NULL,
        `profile_views` INT NOT NULL DEFAULT 0,
        `status` TINYINT NOT NULL DEFAULT 1,
        `created_by` INT NOT NULL DEFAULT 0,
        `created_at` DATETIME NULL,
        `updated_by` INT NOT NULL DEFAULT 0,
        `updated_at` DATETIME NULL,
        INDEX `idx_member_id` (`member_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'app_job_postings' => "CREATE TABLE IF NOT EXISTS `app_job_postings` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `posted_by` INT NOT NULL DEFAULT 0,
        `title` VARCHAR(300) NOT NULL,
        `company_name` VARCHAR(200) NULL,
        `company_logo` VARCHAR(300) NULL,
        `country` VARCHAR(100) NULL,
        `city` VARCHAR(100) NULL,
        `location_type` VARCHAR(50) NULL,
        `employment_type` VARCHAR(50) NULL,
        `description` MEDIUMTEXT NULL,
        `requirements` TEXT NULL,
        `salary_min` INT NULL,
        `salary_max` INT NULL,
        `salary_currency` VARCHAR(10) DEFAULT 'USD',
        `visa_sponsorship` TINYINT NOT NULL DEFAULT 0,
        `application_url` VARCHAR(500) NULL,
        `closes_at` DATETIME NULL,
        `views` INT NOT NULL DEFAULT 0,
        `status` TINYINT NOT NULL DEFAULT 1,
        `created_by` INT NOT NULL DEFAULT 0,
        `created_at` DATETIME NULL,
        `updated_by` INT NOT NULL DEFAULT 0,
        `updated_at` DATETIME NULL,
        `deleted_by` INT NOT NULL DEFAULT 0,
        `deleted_at` DATETIME NULL,
        INDEX `idx_posted_by` (`posted_by`),
        INDEX `idx_status` (`status`),
        INDEX `idx_country` (`country`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'app_job_applications' => "CREATE TABLE IF NOT EXISTS `app_job_applications` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `job_posting_id` INT NOT NULL DEFAULT 0,
        `member_id` INT NOT NULL DEFAULT 0,
        `cover_letter` TEXT NULL,
        `resume_path` VARCHAR(500) NULL,
        `profile_snapshot` TEXT NULL,
        `status` VARCHAR(20) DEFAULT 'submitted',
        `submitted_at` DATETIME NULL,
        `created_by` INT NOT NULL DEFAULT 0,
        `created_at` DATETIME NULL,
        `updated_by` INT NOT NULL DEFAULT 0,
        `updated_at` DATETIME NULL,
        UNIQUE KEY `uq_job_application` (`job_posting_id`, `member_id`),
        INDEX `idx_member_id` (`member_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($tables as $name => $sql) {
    echo "Creating {$name}...\n";
    try {
        $db->statement($sql);
        echo "   ✓ {$name} OK\n";
    } catch (\Exception $e) {
        echo "   ✗ " . $e->getMessage() . "\n";
    }
}

echo "\nDone.\n";
