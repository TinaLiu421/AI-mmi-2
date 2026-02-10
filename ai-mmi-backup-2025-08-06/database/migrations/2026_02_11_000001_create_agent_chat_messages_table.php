<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('agent_chat_messages')) {
            return;
        }
        Schema::create('agent_chat_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sender_member_id')->nullable();
            $table->string('sender_guest_id', 64)->nullable();
            $table->unsignedBigInteger('receiver_member_id')->nullable();
            $table->string('receiver_guest_id', 64)->nullable();
            $table->text('message');
            $table->timestamps();

            $table->index(['sender_member_id', 'receiver_member_id'], 'acm_s_m_r_m');
            $table->index(['sender_guest_id', 'receiver_member_id'], 'acm_s_g_r_m');
            $table->index(['sender_member_id', 'receiver_guest_id'], 'acm_s_m_r_g');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_chat_messages');
    }
};
