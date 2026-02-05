<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PlansAndAdminSubscriptionSeeder extends Seeder
{
    public function run()
    {
        // Create plans if they don't exist
        $plans = [
            [
                'code' => 'free',
                'name' => 'Free Plan',
                'duration_months' => null,
                'price_usd' => 0.00,
                'business_domain' => 'combined',
                'is_active' => true,
                'description' => 'Basic free access',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'all_ai',
                'name' => 'All AI Plan',
                'duration_months' => 6,
                'price_usd' => 99.00,
                'business_domain' => 'combined',
                'is_active' => true,
                'description' => 'Full AI access for 6 months',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'hybrid',
                'name' => 'Hybrid Plan',
                'duration_months' => 6,
                'price_usd' => 199.00,
                'business_domain' => 'combined',
                'is_active' => true,
                'description' => 'AI + Human consultation for 6 months',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'premium',
                'name' => 'Premium Plan',
                'duration_months' => 12,
                'price_usd' => 399.00,
                'business_domain' => 'combined',
                'is_active' => true,
                'description' => 'Premium features for 12 months',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'vip',
                'name' => 'VIP Plan',
                'duration_months' => null,
                'price_usd' => 999.00,
                'business_domain' => 'combined',
                'is_active' => true,
                'description' => 'Lifetime VIP access with all features',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('plans')->updateOrInsert(
                ['code' => $plan['code']],
                $plan
            );
        }

        // Get the VIP plan ID
        $vipPlan = DB::table('plans')->where('code', 'vip')->first();

        // Get admin member (ID = 1)
        $admin = DB::table('member')->where('id', 1)->first();

        if ($admin && $vipPlan) {
            // Delete any existing subscriptions for admin
            DB::table('subscriptions')->where('member_id', 1)->delete();

            // Create VIP subscription for admin
            DB::table('subscriptions')->insert([
                'member_id' => 1,
                'plan_id' => $vipPlan->id,
                'status' => 'active',
                'started_at' => Carbon::now(),
                'ends_at' => null, // Lifetime
                'currency' => 'USD',
                'amount_usd' => 0.00, // Free for admin
                'meta' => json_encode(['note' => 'Admin VIP access']),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            echo "✓ Admin account (ID: 1) upgraded to VIP Plan (Lifetime)\n";
        } else {
            echo "✗ Admin account or VIP plan not found\n";
        }
    }
}
