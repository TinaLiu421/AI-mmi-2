<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('free_assessment', function (Blueprint $table) {
            // 如已有 user_id/uid，请先判断再迁移
            if (!Schema::hasColumn('free_assessment', 'member_id')) {
                $table->unsignedBigInteger('member_id')->after('id')->index();
            }
            // 可选：外键约束
            // $table->foreign('member_id')->references('id')->on('app_member')->cascadeOnDelete();
        });
    }
    public function down(): void {
        Schema::table('free_assessment', function (Blueprint $table) {
            if (Schema::hasColumn('free_assessment', 'member_id')) {
                $table->dropColumn('member_id');
            }
        });
    }
};