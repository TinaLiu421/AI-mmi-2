<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_agent_grants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->comment('The pre-built education agent account');
            $table->string('token', 64)->unique()->comment('Unique claim token sent to school');
            $table->tinyInteger('status')->default(0)->comment('0=pending, 1=claimed');
            $table->unsignedBigInteger('created_by')->comment('Admin member_id who created this grant');
            $table->string('notes', 500)->nullable()->comment('Optional notes from admin');
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->index('member_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_agent_grants');
    }
};
