<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('app_nextgen_submission_interests')) {
            Schema::create('app_nextgen_submission_interests', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('submission_id');
                $table->unsignedBigInteger('member_id');
                $table->string('institution_name', 255)->nullable();
                $table->string('contact_email', 255)->nullable();
                $table->text('message')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->unsignedBigInteger('created_by')->default(0);
                $table->dateTime('created_at')->nullable();
                $table->unsignedBigInteger('updated_by')->default(0);
                $table->dateTime('updated_at')->nullable();
                $table->unsignedBigInteger('deleted_by')->default(0);
                $table->dateTime('deleted_at')->nullable();

                $table->index(['submission_id', 'status'], 'ng_interest_submission_status_idx');
                $table->index(['member_id', 'status'], 'ng_interest_member_status_idx');
                $table->unique(['submission_id', 'member_id'], 'ng_interest_submission_member_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_nextgen_submission_interests');
    }
};