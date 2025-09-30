<?php
/**
 * Test Subscription Plan Checking
 *
 * This script tests if the subscription system is working correctly
 *
 * Run via Artisan Tinker:
 * php artisan tinker < test_subscription_check.php
 *
 * Or copy/paste commands into: php artisan tinker
 */

// ============================================
// TEST 1: Check if subscription checking works
// ============================================

echo "\n========================================\n";
echo "TEST 1: Free User (No Subscription)\n";
echo "========================================\n";

// Assume member_id = 1 has no subscription
$testMemberId = 1;
$memberModel = new App\Models\Member([]);

// Check immigration questions (should be limited to 5)
$canAskImmigration = $memberModel->canAskQuestion($testMemberId, 'immigration');
echo "Can ask immigration question: " . ($canAskImmigration ? "✅ YES" : "❌ NO") . "\n";

// Check education questions (should be unlimited)
$canAskEducation = $memberModel->canAskQuestion($testMemberId, 'education');
echo "Can ask education question: " . ($canAskEducation ? "✅ YES" : "❌ NO") . "\n";

// Get current plan info
$planInfo = $memberModel->getCurrentPlanInfo($testMemberId);
echo "Current Plan: " . $planInfo['plan_slug'] . "\n";
echo "Immigration questions used: {$planInfo['migration_questions_used']} / 5\n";
echo "Education questions used: {$planInfo['education_questions_used']} / Unlimited\n";


// ============================================
// TEST 2: Add subscription and test
// ============================================

echo "\n========================================\n";
echo "TEST 2: Adding AI Plan to Member\n";
echo "========================================\n";

// Get AI plan
$aiPlan = DB::table('subscription_plans')->where('slug', 'ai')->first();
echo "Found plan: {$aiPlan->name} (ID: {$aiPlan->id})\n";

// Check if member already has subscription
$existing = DB::table('member_subscriptions')
    ->where('member_id', $testMemberId)
    ->whereIn('status', ['active', 'trialing'])
    ->first();

if ($existing) {
    echo "⚠️  Member already has an active subscription (ID: {$existing->id})\n";
    echo "Updating existing subscription...\n";

    DB::table('member_subscriptions')
        ->where('id', $existing->id)
        ->update([
            'subscription_plan_id' => $aiPlan->id,
            'expires_at' => now()->addMonths(6),
            'updated_at' => now()
        ]);

    echo "✅ Subscription updated to AI Plan\n";
} else {
    echo "Creating new subscription...\n";

    DB::table('member_subscriptions')->insert([
        'member_id' => $testMemberId,
        'subscription_plan_id' => $aiPlan->id,
        'status' => 'active',
        'started_at' => now(),
        'expires_at' => now()->addMonths(6),
        'migration_questions_used' => 0,
        'education_questions_used' => 0,
        'human_agent_hours_used' => 0,
        'program_applications_used' => 0,
        'metadata' => json_encode(['source' => 'test', 'test_date' => now()]),
        'created_at' => now(),
        'updated_at' => now()
    ]);

    echo "✅ AI Plan subscription added\n";
}


// ============================================
// TEST 3: Verify subscription works
// ============================================

echo "\n========================================\n";
echo "TEST 3: Testing AI Plan Subscription\n";
echo "========================================\n";

// Test again with subscription
$canAskImmigration = $memberModel->canAskQuestion($testMemberId, 'immigration');
echo "Can ask immigration question: " . ($canAskImmigration ? "✅ YES (UNLIMITED)" : "❌ NO") . "\n";

$canAskEducation = $memberModel->canAskQuestion($testMemberId, 'education');
echo "Can ask education question: " . ($canAskEducation ? "✅ YES (UNLIMITED)" : "❌ NO") . "\n";

// Get updated plan info
$planInfo = $memberModel->getCurrentPlanInfo($testMemberId);
echo "Current Plan: " . $planInfo['plan_slug'] . "\n";
echo "Plan Name: " . ($planInfo['plan_name'] ?? 'N/A') . "\n";
echo "Migration limit: " . ($planInfo['migration_questions_limit'] == -1 ? 'Unlimited' : $planInfo['migration_questions_limit']) . "\n";
echo "Education limit: " . ($planInfo['education_questions_limit'] == -1 ? 'Unlimited' : $planInfo['education_questions_limit']) . "\n";
echo "Expires: " . ($planInfo['expires_at'] ?? 'Never') . "\n";


// ============================================
// TEST 4: Test usage increment
// ============================================

echo "\n========================================\n";
echo "TEST 4: Testing Usage Increment\n";
echo "========================================\n";

$beforeUsage = DB::table('member_subscriptions')
    ->where('member_id', $testMemberId)
    ->where('status', 'active')
    ->first();

echo "Before - Immigration questions used: {$beforeUsage->migration_questions_used}\n";

// Increment usage
$memberModel->incrementQuestionUsage($testMemberId, 'immigration');

$afterUsage = DB::table('member_subscriptions')
    ->where('member_id', $testMemberId)
    ->where('status', 'active')
    ->first();

echo "After  - Immigration questions used: {$afterUsage->migration_questions_used}\n";
echo ($afterUsage->migration_questions_used > $beforeUsage->migration_questions_used ? "✅ Usage incremented correctly\n" : "❌ Usage not incremented\n");


// ============================================
// TEST 5: Test feature access
// ============================================

echo "\n========================================\n";
echo "TEST 5: Testing Feature Access\n";
echo "========================================\n";

$hasAiConsultation = $memberModel->hasFeatureAccess($testMemberId, 'ai_consultation');
echo "Has AI consultation: " . ($hasAiConsultation ? "✅ YES" : "❌ NO") . "\n";

$hasValidation = $memberModel->hasFeatureAccess($testMemberId, 'validation_check');
echo "Has validation check: " . ($hasValidation ? "✅ YES" : "❌ NO (needs Premium+)") . "\n";

$hasFullService = $memberModel->hasFeatureAccess($testMemberId, 'full_service');
echo "Has full service: " . ($hasFullService ? "✅ YES" : "❌ NO (needs VIP)") . "\n";

$humanHoursLeft = $memberModel->getHumanAgentHoursRemaining($testMemberId);
echo "Human agent hours remaining: " . ($humanHoursLeft == -1 ? 'Unlimited' : $humanHoursLeft) . "\n";


// ============================================
// TEST 6: Test all plans
// ============================================

echo "\n========================================\n";
echo "TEST 6: Testing All Plan Features\n";
echo "========================================\n";

$plans = DB::table('subscription_plans')->orderBy('display_order')->get();

foreach ($plans as $plan) {
    $features = json_decode($plan->features, true);

    echo "\n--- {$plan->name} ({$plan->slug}) - \${$plan->price} ---\n";
    echo "Duration: " . ($plan->duration_months > 0 ? "{$plan->duration_months} months" : "One-time/Forever") . "\n";
    echo "Immigration questions: " . ($features['migration_questions_limit'] == -1 ? 'Unlimited' : $features['migration_questions_limit']) . "\n";
    echo "Education questions: " . ($features['education_questions_limit'] == -1 ? 'Unlimited' : $features['education_questions_limit']) . "\n";
    echo "AI consultation: " . ($features['ai_consultation'] ? 'Yes' : 'No') . "\n";
    echo "Human agent hours: " . ($features['human_agent_hours'] == -1 ? 'Unlimited' : $features['human_agent_hours']) . "\n";
    echo "Validation check: " . ($features['validation_check'] ? 'Yes' : 'No') . "\n";
    echo "Full service: " . ($features['full_service'] ? 'Yes' : 'No') . "\n";
}


// ============================================
// TEST 7: View all member subscriptions
// ============================================

echo "\n========================================\n";
echo "TEST 7: All Active Subscriptions\n";
echo "========================================\n";

$subscriptions = DB::table('member_subscriptions as ms')
    ->join('subscription_plans as sp', 'ms.subscription_plan_id', '=', 'sp.id')
    ->join('member as m', 'ms.member_id', '=', 'm.id')
    ->whereIn('ms.status', ['active', 'trialing'])
    ->select(
        'ms.id',
        'ms.member_id',
        'm.email',
        'sp.name as plan_name',
        'ms.status',
        'ms.started_at',
        'ms.expires_at',
        'ms.migration_questions_used',
        'ms.education_questions_used'
    )
    ->orderBy('ms.created_at', 'desc')
    ->limit(10)
    ->get();

if ($subscriptions->isEmpty()) {
    echo "⚠️  No active subscriptions found\n";
} else {
    foreach ($subscriptions as $sub) {
        $daysLeft = $sub->expires_at ? \Carbon\Carbon::parse($sub->expires_at)->diffInDays(now(), false) : null;
        $expiryStatus = $daysLeft === null ? 'Never' : ($daysLeft < 0 ? abs($daysLeft) . ' days left' : 'EXPIRED');

        echo "\n• Member #{$sub->member_id} ({$sub->email})\n";
        echo "  Plan: {$sub->plan_name}\n";
        echo "  Status: {$sub->status}\n";
        echo "  Questions: Immigration={$sub->migration_questions_used}, Education={$sub->education_questions_used}\n";
        echo "  Expires: {$expiryStatus}\n";
    }
}

echo "\n========================================\n";
echo "✅ ALL TESTS COMPLETED\n";
echo "========================================\n\n";

?>