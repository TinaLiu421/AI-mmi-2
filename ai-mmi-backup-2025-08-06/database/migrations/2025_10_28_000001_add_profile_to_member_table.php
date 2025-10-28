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
        Schema::table('member', function (Blueprint $table) {
            if (!Schema::hasColumn('member', 'profile_data')) {
                $table->longText('profile_data')->nullable()->comment('JSON object with extracted user profile info (age, education, occupation, experience, english_level, ielts_score, etc)');
            }

            if (!Schema::hasColumn('member', 'profile_updated_at')) {
                $table->timestamp('profile_updated_at')->nullable()->comment('Timestamp for cache validation - profile is re-extracted if older than 1 hour');
            }
        });
    }
    
    public function down(): void
    {
        Schema::table('member', function (Blueprint $table) {
            if (Schema::hasColumn('member', 'profile_data')) {
                $table->dropColumn('profile_data');
            }
            if (Schema::hasColumn('member', 'profile_updated_at')) {
                $table->dropColumn('profile_updated_at');
            }
        });
    }
};
