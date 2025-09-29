<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chat_log', function (Blueprint $table) {
            $table->string('chat_mode', 20)->default('immigration')->after('content');
            $table->index(['member_id', 'chat_mode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_log', function (Blueprint $table) {
            $table->dropIndex(['member_id', 'chat_mode']);
            $table->dropColumn('chat_mode');
        });
    }
};