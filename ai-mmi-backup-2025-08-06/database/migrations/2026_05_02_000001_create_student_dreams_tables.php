<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentDreamsTables extends Migration
{
    public function up()
    {
        // One dream profile per student
        Schema::create('app_student_dreams', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('member_id')->default(0)->index();
            $table->string('title', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('photo', 500)->nullable();       // main photo filename
            $table->text('gallery_json')->nullable();        // JSON array of extra photo filenames
            $table->tinyInteger('status')->default(1);
            $table->integer('created_by')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->integer('updated_by')->default(0);
            $table->dateTime('updated_at')->nullable();
            $table->integer('deleted_by')->default(0);
            $table->dateTime('deleted_at')->nullable();
        });

        // Likes for student dreams
        Schema::create('app_student_dreams_like', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('member_id')->default(0)->index();
            $table->integer('dream_id')->default(0)->index();
            $table->tinyInteger('status')->default(1);
            $table->integer('created_by')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->integer('updated_by')->default(0);
            $table->dateTime('updated_at')->nullable();
            $table->integer('deleted_by')->default(0);
            $table->dateTime('deleted_at')->nullable();
        });

        // Comments for student dreams
        Schema::create('app_student_dreams_comment', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('member_id')->default(0)->index();
            $table->integer('dream_id')->default(0)->index();
            $table->integer('parent_id')->default(0);
            $table->text('content')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->integer('created_by')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->integer('updated_by')->default(0);
            $table->dateTime('updated_at')->nullable();
            $table->integer('deleted_by')->default(0);
            $table->dateTime('deleted_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('app_student_dreams_comment');
        Schema::dropIfExists('app_student_dreams_like');
        Schema::dropIfExists('app_student_dreams');
    }
}
