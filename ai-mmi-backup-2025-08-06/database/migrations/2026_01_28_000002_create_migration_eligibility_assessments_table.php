<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMigrationEligibilityAssessmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('migration_eligibility_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->json('countries')->nullable(); // Preferred countries
            $table->json('visa_types')->nullable(); // Interested visa types
            $table->string('nationality')->nullable();
            $table->string('residency')->nullable();
            $table->integer('age')->nullable();
            $table->string('education_level')->nullable();
            $table->enum('english_test_completed', ['Yes', 'No'])->nullable();
            $table->json('test_results')->nullable(); // English test scores
            $table->string('occupation')->nullable();
            $table->integer('total_work_experience')->nullable();
            $table->integer('occupation_work_experience')->nullable();
            $table->enum('destination_work_experience', ['Yes', 'No'])->nullable();
            $table->integer('destination_work_years')->nullable();
            $table->enum('job_offer', ['Yes', 'No'])->nullable();
            $table->enum('outstanding_achievements', ['Yes', 'No'])->nullable();
            $table->text('achievements_details')->nullable();
            $table->string('cv_file_path')->nullable();
            $table->text('ai_assessment')->nullable(); // AI analysis result
            $table->timestamps();
            
            $table->index('member_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('migration_eligibility_assessments');
    }
}
