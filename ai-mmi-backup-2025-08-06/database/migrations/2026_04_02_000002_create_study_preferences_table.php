<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudyPreferencesTable extends Migration
{
    public function up()
    {
        Schema::create('app_study_preferences', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('member_id')->default(0)->index();
            // 1st choice
            $table->string('choice_1_country', 255)->nullable();
            $table->string('choice_1_city', 255)->nullable();
            $table->string('choice_1_university', 500)->nullable();
            $table->string('choice_1_level', 255)->nullable();
            $table->string('choice_1_fields', 500)->nullable();
            $table->string('choice_1_budget', 255)->nullable();
            $table->string('choice_1_year', 50)->nullable();
            // 2nd choice
            $table->string('choice_2_country', 255)->nullable();
            $table->string('choice_2_city', 255)->nullable();
            $table->string('choice_2_university', 500)->nullable();
            $table->string('choice_2_level', 255)->nullable();
            $table->string('choice_2_fields', 500)->nullable();
            $table->string('choice_2_budget', 255)->nullable();
            $table->string('choice_2_year', 50)->nullable();
            // 3rd choice
            $table->string('choice_3_country', 255)->nullable();
            $table->string('choice_3_city', 255)->nullable();
            $table->string('choice_3_university', 500)->nullable();
            $table->string('choice_3_level', 255)->nullable();
            $table->string('choice_3_fields', 500)->nullable();
            $table->string('choice_3_budget', 255)->nullable();
            $table->string('choice_3_year', 50)->nullable();

            $table->tinyInteger('status')->default(1);
            $table->integer('created_by')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->integer('updated_by')->default(0);
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('app_study_preferences');
    }
}
