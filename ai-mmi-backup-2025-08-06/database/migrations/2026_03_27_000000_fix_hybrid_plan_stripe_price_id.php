<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixHybridPlanStripePriceId extends Migration
{
    public function up()
    {
        DB::table('plans')
            ->where('code', 'hybrid')
            ->update([
                'stripe_price_id' => 'price_1TFFFdKcbpMSEKkQzo54WAWl',
                'updated_at'      => now(),
            ]);
    }

    public function down()
    {
        DB::table('plans')
            ->where('code', 'hybrid')
            ->update([
                'stripe_price_id' => 'price_1TBjlrKcbpMSEKkQIFSBDXCk',
                'updated_at'      => now(),
            ]);
    }
}
