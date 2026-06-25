<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNextgenSubmissionsTables extends Migration
{
    public function up()
    {
        // NextGen AI & Talent Challenge submissions
        Schema::create('app_nextgen_submissions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('member_id')->default(0)->index();
            $table->string('stream', 50)->nullable();            // 'AI' or 'Talent'
            $table->string('title', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('tags', 500)->nullable();
            $table->tinyInteger('youtube_consent')->default(0);
            $table->tinyInteger('copyright_consent')->default(0);
            $table->string('full_name', 255)->nullable();
            $table->string('country', 255)->nullable();
            $table->integer('age')->nullable();
            $table->string('video_path', 500)->nullable();       // uploaded video filename
            $table->string('email', 255)->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('youtube_link', 500)->nullable();     // YouTube link after posting
            $table->dateTime('youtube_sent_at')->nullable();     // when YouTube link was sent
            $table->tinyInteger('admin_status')->default(0);     // 0=pending, 1=approved, 2=rejected
            $table->tinyInteger('published')->default(0);        // 1=visible on public feed
            $table->text('admin_notes')->nullable();             // rejection note to participant
            $table->tinyInteger('status')->default(1);
            $table->integer('created_by')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->integer('updated_by')->default(0);
            $table->dateTime('updated_at')->nullable();
            $table->integer('deleted_by')->default(0);
            $table->dateTime('deleted_at')->nullable();
        });

        // Likes for NextGen submissions
        Schema::create('app_nextgen_likes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('member_id')->default(0)->index();
            $table->integer('submission_id')->default(0)->index();
            $table->tinyInteger('status')->default(1);
            $table->integer('created_by')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->integer('updated_by')->default(0);
            $table->dateTime('updated_at')->nullable();
            $table->integer('deleted_by')->default(0);
            $table->dateTime('deleted_at')->nullable();
        });

        // Comments for NextGen submissions
        Schema::create('app_nextgen_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('member_id')->default(0)->index();
            $table->integer('submission_id')->default(0)->index();
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
        Schema::dropIfExists('app_nextgen_comments');
        Schema::dropIfExists('app_nextgen_likes');
        Schema::dropIfExists('app_nextgen_submissions');
    }
}
