<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chat_log', function (Blueprint $table) {
            // 推荐长度：guest_id 64（uuid/自定义token都可），session_id 128（Laravel session id 足够）
            if (!Schema::hasColumn('chat_log', 'guest_id')) {
                $table->string('guest_id', 64)->nullable()->after('member_id');
                $table->index('guest_id', 'idx_chat_log_guest_id');
            }
            if (!Schema::hasColumn('chat_log', 'session_id')) {
                $table->string('session_id', 128)->nullable()->after('guest_id');
                $table->index('session_id', 'idx_chat_log_session_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_log', function (Blueprint $table) {
            // 先删索引再删列（不同数据库驱动会要求先删索引）
            if (Schema::hasColumn('chat_log', 'guest_id')) {
                $table->dropIndex('idx_chat_log_guest_id');
                $table->dropColumn('guest_id');
            }
            if (Schema::hasColumn('chat_log', 'session_id')) {
                $table->dropIndex('idx_chat_log_session_id');
                $table->dropColumn('session_id');
            }
        });
    }
};
