<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('job_seeker_profiles')) {
            Schema::create('job_seeker_profiles', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('member_id')->default(0)->index();
                $table->string('headline', 300)->nullable();
                $table->text('bio')->nullable();
                $table->string('nationality', 100)->nullable();
                $table->string('current_country', 100)->nullable();
                $table->string('current_city', 100)->nullable();
                $table->string('open_to_work', 50)->nullable();
                $table->text('target_roles')->nullable();
                $table->text('target_locations')->nullable();
                $table->string('employment_preference', 50)->nullable();
                $table->mediumText('education_history')->nullable();
                $table->text('work_experience')->nullable();
                $table->text('skills')->nullable();
                $table->text('language_scores')->nullable();
                $table->string('resume_path', 500)->nullable();
                $table->integer('profile_views')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->integer('created_by')->default(0);
                $table->dateTime('created_at')->nullable();
                $table->integer('updated_by')->default(0);
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('job_postings')) {
            Schema::create('job_postings', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('posted_by')->default(0)->index();
                $table->string('title', 300);
                $table->string('company_name', 200)->nullable();
                $table->string('company_logo', 300)->nullable();
                $table->string('country', 100)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('location_type', 50)->nullable();
                $table->string('employment_type', 50)->nullable();
                $table->mediumText('description')->nullable();
                $table->text('requirements')->nullable();
                $table->integer('salary_min')->nullable();
                $table->integer('salary_max')->nullable();
                $table->string('salary_currency', 10)->default('USD');
                $table->tinyInteger('visa_sponsorship')->default(0);
                $table->string('application_url', 500)->nullable();
                $table->dateTime('closes_at')->nullable();
                $table->integer('views')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->integer('created_by')->default(0);
                $table->dateTime('created_at')->nullable();
                $table->integer('updated_by')->default(0);
                $table->dateTime('updated_at')->nullable();
                $table->integer('deleted_by')->default(0);
                $table->dateTime('deleted_at')->nullable();
            });
        }

        if (!Schema::hasTable('job_applications')) {
            Schema::create('job_applications', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('job_posting_id')->default(0)->index();
                $table->integer('member_id')->default(0)->index();
                $table->text('cover_letter')->nullable();
                $table->string('resume_path', 500)->nullable();
                $table->text('profile_snapshot')->nullable();
                $table->string('status', 20)->default('submitted');
                $table->dateTime('submitted_at')->nullable();
                $table->integer('created_by')->default(0);
                $table->dateTime('created_at')->nullable();
                $table->integer('updated_by')->default(0);
                $table->dateTime('updated_at')->nullable();
                $table->unique(['job_posting_id', 'member_id'], 'uq_job_application');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_postings');
        Schema::dropIfExists('job_seeker_profiles');
    }
}
