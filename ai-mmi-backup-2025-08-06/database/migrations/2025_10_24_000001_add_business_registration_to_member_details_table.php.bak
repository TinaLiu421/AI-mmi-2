<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `app_member_details` ADD COLUMN `registered_business_country` INT DEFAULT 0 NULL COMMENT 'Country ID of business registration' AFTER `services_country`");
        DB::statement("ALTER TABLE `app_member_details` ADD COLUMN `registered_business_name` TEXT NULL COMMENT 'Registered business name' AFTER `registered_business_country`");
        DB::statement("ALTER TABLE `app_member_details` ADD COLUMN `registered_business_number` VARCHAR(255) NULL COMMENT 'Business registration number or license' AFTER `registered_business_name`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `app_member_details` DROP COLUMN `registered_business_country`");
        DB::statement("ALTER TABLE `app_member_details` DROP COLUMN `registered_business_name`");
        DB::statement("ALTER TABLE `app_member_details` DROP COLUMN `registered_business_number`");
    }
};
