<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdatePlansStripePriceIdsAndNames extends Migration
{
    public function up()
    {
        $updates = [
            'all_ai'  => ['name' => 'AI Smart Plan',    'stripe_price_id' => 'price_1TBjgzKcbpMSEKkQccSj3vA0'],
            'hybrid'  => ['name' => 'AI + Agent Plan',  'stripe_price_id' => 'price_1TBjlrKcbpMSEKkQIFSBDXCk'],
            'premium' => ['name' => 'DIY Plan',          'stripe_price_id' => 'price_1TBjocKcbpMSEKkQxZIliIvV'],
            'vip'     => ['name' => 'VIP Agent Plan',    'stripe_price_id' => 'price_1SBmHjKcbpMSEKkQ1T5PjSzz'],
        ];

        foreach ($updates as $code => $data) {
            DB::table('plans')
                ->where('code', $code)
                ->update([
                    'name'            => $data['name'],
                    'stripe_price_id' => $data['stripe_price_id'],
                    'updated_at'      => now(),
                ]);
        }
    }

    public function down()
    {
        $rollback = [
            'all_ai'  => ['name' => 'All AI Plan',   'stripe_price_id' => null],
            'hybrid'  => ['name' => 'Hybrid Plan',   'stripe_price_id' => null],
            'premium' => ['name' => 'Premium Plan',  'stripe_price_id' => null],
            'vip'     => ['name' => 'VIP Plan',      'stripe_price_id' => null],
        ];

        foreach ($rollback as $code => $data) {
            DB::table('plans')
                ->where('code', $code)
                ->update([
                    'name'            => $data['name'],
                    'stripe_price_id' => $data['stripe_price_id'],
                    'updated_at'      => now(),
                ]);
        }
    }
}
