<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourseApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->string('family_name')->nullable();
            $table->string('given_name')->nullable();
            $table->string('email_address')->nullable();
            $table->string('mobile_number')->nullable();
            $table->text('residential_address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality')->nullable();
            $table->string('highest_education')->nullable();
            $table->boolean('has_english_test')->default(false);
            $table->json('english_tests')->nullable();
            $table->boolean('has_financial_support')->default(false);
            $table->text('financial_notes')->nullable();
            $table->string('target_institution')->nullable();
            $table->string('target_program')->nullable();
            $table->string('start_year', 10)->nullable();
            $table->boolean('wants_scholarship')->default(false);
            $table->json('scholarship_colleges')->nullable();
            $table->json('document_paths')->nullable();
            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->string('payment_reference')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index('member_id');
            $table->index('status');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_applications');
    }
}
