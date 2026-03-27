<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixAllAiPlanStripePriceId extends Migration
{
    public function up()
    {
        DB::table('plans')
            ->where('code', 'all_ai')
            ->update([
                'stripe_price_id' => 'price_1TFFF0KcbpMSEKkQs9bnP4bs',
                'updated_at'      => now(),
            ]);
    }

    public function down()
    {
        DB::table('plans')
            ->where('code', 'all_ai')
            ->update([
                'stripe_price_id' => 'price_1TBjgzKcbpMSEKkQccSj3vA0',
                'updated_at'      => now(),
            ]);
    }
}
