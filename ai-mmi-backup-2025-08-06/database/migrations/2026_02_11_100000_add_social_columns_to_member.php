<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('member')) {
            return;
        }

        Schema::table('member', function (Blueprint $table) {
            if (!Schema::hasColumn('member', 'social_provider')) {
                $table->string('social_provider', 50)->nullable()->after('avatar');
            }
            if (!Schema::hasColumn('member', 'social_id')) {
                $table->string('social_id', 120)->nullable()->after('social_provider');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('member')) {
            return;
        }

        Schema::table('member', function (Blueprint $table) {
            if (Schema::hasColumn('member', 'social_id')) {
                $table->dropColumn('social_id');
            }
            if (Schema::hasColumn('member', 'social_provider')) {
                $table->dropColumn('social_provider');
            }
        });
    }
};
