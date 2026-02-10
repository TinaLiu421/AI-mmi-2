<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('agent_chat_messages', function (Blueprint $table) {
            $table->index(['sender_member_id', 'receiver_member_id'], 'acm_s_m_r_m');
            $table->index(['sender_guest_id', 'receiver_member_id'], 'acm_s_g_r_m');
            $table->index(['sender_member_id', 'receiver_guest_id'], 'acm_s_m_r_g');
        });
    }

    public function down(): void
    {
        Schema::table('agent_chat_messages', function (Blueprint $table) {
            $table->dropIndex('acm_s_m_r_m');
            $table->dropIndex('acm_s_g_r_m');
            $table->dropIndex('acm_s_m_r_g');
        });
    }
};
