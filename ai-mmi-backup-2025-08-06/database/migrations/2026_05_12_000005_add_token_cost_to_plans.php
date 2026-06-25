<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('token_cost')->default(0)->after('price_usd')
                  ->comment('Token cost to activate this plan (0 = not a token plan)');
            $table->unsignedInteger('access_months')->default(0)->after('token_cost')
                  ->comment('Months of access granted when plan is activated via tokens');
        });

        // Seed token costs per the spec document:
        //   all_ai  → removed as a paid plan; AI chat is pay-per-use (10 chats = 1 token)
        //   hybrid  → removed as a paid plan; agent calls booked separately (390 tokens/session)
        //   premium → 1,900 tokens / 6 months
        //   vip     → 4,900 tokens / 6 months
        DB::table('plans')->where('code', 'premium')->update([
            'token_cost'    => 1900,
            'access_months' => 6,
        ]);
        DB::table('plans')->where('code', 'vip')->update([
            'token_cost'    => 4900,
            'access_months' => 6,
        ]);
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['token_cost', 'access_months']);
        });
    }
};
