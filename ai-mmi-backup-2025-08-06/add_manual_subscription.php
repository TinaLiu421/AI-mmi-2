<?php
/**
 * Manual Subscription Assignment Script
 *
 * This script allows you to manually assign subscriptions to members from the database
 *
 * Usage:
 * 1. Via Command Line:
 *    php add_manual_subscription.php <member_id> <plan_slug> [duration_months]
 *    Example: php add_manual_subscription.php 5 hybrid 6
 *
 * 2. Via Browser:
 *    http://yourdomain.com/add_manual_subscription.php?member_id=5&plan=hybrid&duration=6
 *
 * 3. Via Artisan Tinker:
 *    See commands below
 */

// Artisan Tinker Commands (Copy and paste into: php artisan tinker)

/*

// ===== METHOD 1: Quick Add =====
// Give member_id 5 the "ai" plan for 6 months
DB::table('member_subscriptions')->insert([
    'member_id' => 5,
    'subscription_plan_id' => DB::table('subscription_plans')->where('slug', 'ai')->value('id'),
    'status' => 'active',
    'started_at' => now(),
    'expires_at' => now()->addMonths(6),
    'migration_questions_used' => 0,
    'education_questions_used' => 0,
    'human_agent_hours_used' => 0,
    'program_applications_used' => 0,
    'created_at' => now(),
    'updated_at' => now()
]);
echo "Subscription added!\n";


// ===== METHOD 2: Add with Details =====
$memberId = 5;
$planSlug = 'hybrid'; // free, ai, hybrid, premium, vip, education_app
$durationMonths = 6;

$plan = DB::table('subscription_plans')->where('slug', $planSlug)->first();

if (!$plan) {
    echo "Plan not found!\n";
} else {
    $expiresAt = $durationMonths > 0 ? now()->addMonths($durationMonths) : null;

    DB::table('member_subscriptions')->insert([
        'member_id' => $memberId,
        'subscription_plan_id' => $plan->id,
        'stripe_subscription_id' => null, // Manual, no Stripe
        'stripe_customer_id' => null,
        'status' => 'active',
        'started_at' => now(),
        'expires_at' => $expiresAt,
        'migration_questions_used' => 0,
        'education_questions_used' => 0,
        'human_agent_hours_used' => 0,
        'program_applications_used' => 0,
        'metadata' => json_encode(['source' => 'manual', 'added_by' => 'admin']),
        'created_at' => now(),
        'updated_at' => now()
    ]);

    echo "✅ {$plan->name} subscription added to member {$memberId}\n";
    echo "   Expires: " . ($expiresAt ? $expiresAt->format('Y-m-d') : 'Never') . "\n";
}


// ===== METHOD 3: Update Existing Subscription =====
$memberId = 5;
$newPlanSlug = 'premium';

$plan = DB::table('subscription_plans')->where('slug', $newPlanSlug)->first();
$subscription = DB::table('member_subscriptions')
    ->where('member_id', $memberId)
    ->whereIn('status', ['active', 'trialing'])
    ->first();

if ($subscription) {
    DB::table('member_subscriptions')->where('id', $subscription->id)->update([
        'subscription_plan_id' => $plan->id,
        'expires_at' => now()->addMonths(6),
        'updated_at' => now()
    ]);
    echo "✅ Subscription upgraded to {$plan->name}\n";
} else {
    echo "❌ No active subscription found for member {$memberId}\n";
}


// ===== METHOD 4: Cancel Subscription =====
$memberId = 5;

DB::table('member_subscriptions')
    ->where('member_id', $memberId)
    ->whereIn('status', ['active', 'trialing'])
    ->update([
        'status' => 'canceled',
        'canceled_at' => now(),
        'updated_at' => now()
    ]);

echo "✅ Subscription canceled for member {$memberId}\n";


// ===== METHOD 5: Extend Subscription =====
$memberId = 5;
$additionalMonths = 3;

DB::table('member_subscriptions')
    ->where('member_id', $memberId)
    ->where('status', 'active')
    ->update([
        'expires_at' => DB::raw("DATE_ADD(expires_at, INTERVAL {$additionalMonths} MONTH)"),
        'updated_at' => now()
    ]);

echo "✅ Subscription extended by {$additionalMonths} months\n";


// ===== VIEW MEMBER SUBSCRIPTIONS =====
$memberId = 5;

$subscriptions = DB::table('member_subscriptions as ms')
    ->join('subscription_plans as sp', 'ms.subscription_plan_id', '=', 'sp.id')
    ->where('ms.member_id', $memberId)
    ->select('ms.*', 'sp.name as plan_name', 'sp.slug as plan_slug', 'sp.price')
    ->get();

foreach ($subscriptions as $sub) {
    echo "Plan: {$sub->plan_name} ({$sub->plan_slug})\n";
    echo "Status: {$sub->status}\n";
    echo "Started: {$sub->started_at}\n";
    echo "Expires: " . ($sub->expires_at ?? 'Never') . "\n";
    echo "Questions Used: Migration={$sub->migration_questions_used}, Education={$sub->education_questions_used}\n";
    echo "---\n";
}


// ===== VIEW ALL PLANS =====
DB::table('subscription_plans')
    ->select('id', 'name', 'slug', 'price', 'duration_months')
    ->orderBy('display_order')
    ->get()
    ->each(function($plan) {
        echo "{$plan->id}. {$plan->name} ({$plan->slug}) - \${$plan->price} / {$plan->duration_months} months\n";
    });


// ===== BULK ASSIGN TO MULTIPLE MEMBERS =====
$memberIds = [5, 10, 15, 20];
$planSlug = 'ai';
$durationMonths = 6;

$plan = DB::table('subscription_plans')->where('slug', $planSlug)->first();

foreach ($memberIds as $memberId) {
    DB::table('member_subscriptions')->insert([
        'member_id' => $memberId,
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
        'started_at' => now(),
        'expires_at' => now()->addMonths($durationMonths),
        'migration_questions_used' => 0,
        'education_questions_used' => 0,
        'human_agent_hours_used' => 0,
        'program_applications_used' => 0,
        'created_at' => now(),
        'updated_at' => now()
    ]);
}

echo "✅ {$plan->name} assigned to " . count($memberIds) . " members\n";

*/

// End of Tinker Commands
?>