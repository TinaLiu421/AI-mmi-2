<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['posts_comments', 'member_posts_comment'] as $table) {
            if (!Schema::hasTable($table) || Schema::hasColumn($table, 'parent_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->unsignedInteger('parent_id')->nullable()->after('id')->index()->comment('Link to question for AI replies');
            });
        }
    }

    public function down(): void
    {
        foreach (['posts_comments', 'member_posts_comment'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'parent_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('parent_id');
            });
        }
    }
};
