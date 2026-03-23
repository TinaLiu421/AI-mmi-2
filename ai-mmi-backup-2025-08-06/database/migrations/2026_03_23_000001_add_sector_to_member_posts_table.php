<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('member_posts')) {
            return;
        }

        if (!Schema::hasColumn('member_posts', 'sector')) {
            Schema::table('member_posts', function (Blueprint $table) {
                $table->string('sector', 20)->default('study')->after('category_country');
                $table->index('sector', 'member_posts_sector_index');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('member_posts') || !Schema::hasColumn('member_posts', 'sector')) {
            return;
        }

        Schema::table('member_posts', function (Blueprint $table) {
            $table->dropIndex('member_posts_sector_index');
            $table->dropColumn('sector');
        });
    }
};
