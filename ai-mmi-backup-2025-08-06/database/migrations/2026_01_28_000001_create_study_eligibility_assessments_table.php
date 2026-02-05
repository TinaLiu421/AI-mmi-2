<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudyEligibilityAssessmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('study_eligibility_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->json('countries')->nullable(); // Selected countries
            $table->string('nationality')->nullable();
            $table->string('residency')->nullable();
            $table->integer('age')->nullable();
            $table->string('education_level')->nullable();
            $table->enum('english_test_completed', ['Yes', 'No'])->nullable();
            $table->json('test_results')->nullable(); // English test scores
            $table->string('study_level')->nullable();
            $table->string('field_of_study')->nullable();
            $table->string('budget')->nullable();
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
        Schema::dropIfExists('study_eligibility_assessments');
    }
}
