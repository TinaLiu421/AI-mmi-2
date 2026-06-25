<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('member') && !Schema::hasColumn('member', 'spotlight_manager')) {
            Schema::table('member', function (Blueprint $table) {
                $table->tinyInteger('spotlight_manager')->default(0)->after('type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('member') && Schema::hasColumn('member', 'spotlight_manager')) {
            Schema::table('member', function (Blueprint $table) {
                $table->dropColumn('spotlight_manager');
            });
        }
    }
};
