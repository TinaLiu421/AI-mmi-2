# Subscription System Verification Checklist

## ✅ What's Already Implemented

### 1. **Database Tables** ✓
- `subscription_plans` - 6 plans seeded
- `member_subscriptions` - User subscription tracking
- `payments` - Stripe payment records

### 2. **Models** ✓
- `SubscriptionPlan.php` - Plan configuration and features
- `MemberSubscription.php` - Subscription management
- `Member.php` - Helper methods added

### 3. **Controllers** ✓
- `StripeWebhookController.php` - Links payments to users
- `Web/Home.php` - Uses subscription checking
- `Web/Upgrade.php` - Shows current plan
- `Admin/SubscriptionPlans.php` - Admin management

### 4. **Logic Flow** ✓
```
User asks question
    ↓
Home.php → Member.canAskQuestion()
    ↓
Check active subscription
    ↓
    ├─→ Has subscription? → Check plan limits
    │                       ↓
    │                   Check usage vs limit
    │                       ↓
    │                   Allow/Deny
    ↓
    └─→ No subscription? → Free plan (5 immigration, ∞ education)
```

---

## 🔍 How to Verify Subscription Checking Works

### **Method 1: Quick Test via Tinker**

```bash
php artisan tinker
```

```php
// Test 1: Check a member's plan
$memberModel = new App\Models\Member([]);
$memberId = 1; // Change to your test member ID

// Check if they can ask questions
$canAskImmigration = $memberModel->canAskQuestion($memberId, 'immigration');
$canAskEducation = $memberModel->canAskQuestion($memberId, 'education');

echo "Can ask immigration: " . ($canAskImmigration ? "YES" : "NO") . "\n";
echo "Can ask education: " . ($canAskEducation ? "YES" : "NO") . "\n";

// Get their current plan
$planInfo = $memberModel->getCurrentPlanInfo($memberId);
print_r($planInfo);
```

### **Method 2: Run Comprehensive Test**

```bash
php artisan tinker < test_subscription_check.php
```

This will:
1. ✅ Test free user limits
2. ✅ Add subscription to member
3. ✅ Verify unlimited access
4. ✅ Test usage increment
5. ✅ Test feature access
6. ✅ Show all plan features
7. ✅ List active subscriptions

### **Method 3: Manual Database Check**

```bash
php artisan tinker
```

```php
// Check if member has subscription
DB::table('member_subscriptions as ms')
    ->join('subscription_plans as sp', 'ms.subscription_plan_id', '=', 'sp.id')
    ->where('ms.member_id', 1)
    ->where('ms.status', 'active')
    ->select('ms.*', 'sp.name', 'sp.slug', 'sp.features')
    ->get();
```

### **Method 4: SQL Query**

See `manual_subscription_queries.sql` - Query #4:

```sql
SELECT
    ms.*,
    sp.name as plan_name,
    sp.slug,
    m.email
FROM app_member_subscriptions ms
JOIN app_subscription_plans sp ON ms.subscription_plan_id = sp.id
JOIN app_member m ON ms.member_id = m.id
WHERE ms.member_id = 1;
```

---

## 🎯 Test Scenarios

### **Scenario 1: Free User - Immigration Mode**

**Setup:**
- Member has NO subscription
- Has asked 0 immigration questions

**Expected:**
- ✅ Questions 1-5: Allowed
- ❌ Question 6: Blocked, redirect to `/upgrade`

**Verify:**
```php
$memberModel->canAskQuestion($memberId, 'immigration'); // Should return false after 5
```

### **Scenario 2: Free User - Education Mode**

**Setup:**
- Member has NO subscription

**Expected:**
- ✅ Unlimited questions allowed

**Verify:**
```php
$memberModel->canAskQuestion($memberId, 'education'); // Always true
```

### **Scenario 3: AI Plan Subscriber**

**Setup:**
- Member has AI Plan subscription
- Subscription is active and not expired

**Expected:**
- ✅ Immigration: Unlimited
- ✅ Education: Unlimited
- ✅ AI Consultation: YES
- ❌ Human Agent: NO
- ❌ Validation: NO

**Add Subscription:**
```sql
-- See manual_subscription_queries.sql Query #3
INSERT INTO app_member_subscriptions (
    member_id, subscription_plan_id, status,
    started_at, expires_at,
    migration_questions_used, education_questions_used,
    human_agent_hours_used, program_applications_used,
    created_at, updated_at
) VALUES (
    1, 2, 'active',
    NOW(), DATE_ADD(NOW(), INTERVAL 6 MONTH),
    0, 0, 0, 0,
    NOW(), NOW()
);
```

**Verify:**
```php
$memberModel->canAskQuestion(1, 'immigration'); // true
$memberModel->hasFeatureAccess(1, 'ai_consultation'); // true
$memberModel->hasFeatureAccess(1, 'human_agent_hours'); // false
```

### **Scenario 4: Expired Subscription**

**Setup:**
- Member has subscription with `expires_at` in the past

**Expected:**
- ❌ Treated as free user
- ✅ Back to 5 immigration questions limit

**Test:**
```sql
-- Set expiration to past
UPDATE app_member_subscriptions
SET expires_at = DATE_SUB(NOW(), INTERVAL 1 DAY)
WHERE member_id = 1;
```

**Verify:**
```php
$subscription = $memberModel->getActiveSubscription(1);
var_dump($subscription); // Should be null or empty
```

### **Scenario 5: Usage Tracking**

**Setup:**
- Member asks questions

**Expected:**
- ✅ `migration_questions_used` increments
- ✅ `education_questions_used` increments separately

**Verify:**
```sql
-- Before
SELECT migration_questions_used FROM app_member_subscriptions WHERE member_id = 1;

-- Ask question (happens in Home.php automatically)

-- After
SELECT migration_questions_used FROM app_member_subscriptions WHERE member_id = 1;
```

---

## 🔧 Quick Commands Reference

### **View Member's Subscription Status**
```php
php artisan tinker --execute="
\$m = new App\Models\Member([]);
\$info = \$m->getCurrentPlanInfo(1);
print_r(\$info);
"
```

### **Add Subscription Manually**
```bash
# See: manual_subscription_queries.sql
# Or use the script: add_manual_subscription.php
```

### **Check if Logic is Working**
```bash
php artisan tinker < test_subscription_check.php
```

### **View All Active Subscriptions**
```sql
SELECT
    m.id,
    m.email,
    sp.name as plan,
    ms.status,
    ms.expires_at
FROM app_member_subscriptions ms
JOIN app_member m ON ms.member_id = m.id
JOIN app_subscription_plans sp ON ms.subscription_plan_id = sp.id
WHERE ms.status = 'active';
```

---

## 📊 Debugging Tips

### **Problem: Questions not being limited**

**Check:**
1. Verify `chat_mode` is being passed correctly
2. Check `chat_log` table has correct `chat_mode` values
3. Verify free plan limits in database:
   ```sql
   SELECT features FROM app_subscription_plans WHERE slug = 'free';
   ```

### **Problem: Subscription not detected**

**Check:**
1. Member has active subscription:
   ```sql
   SELECT * FROM app_member_subscriptions WHERE member_id = X AND status = 'active';
   ```
2. Subscription not expired:
   ```sql
   SELECT expires_at, NOW() FROM app_member_subscriptions WHERE member_id = X;
   ```
3. Check logs: `storage/logs/laravel.log`

### **Problem: Usage not incrementing**

**Check:**
1. `incrementQuestionUsage()` is called after `doSave()` in Home.php
2. Subscription exists in database
3. Check subscription ID:
   ```php
   $sub = $memberModel->getActiveSubscription($memberId);
   dd($sub);
   ```

---

## 📝 Files to Review

1. **Logic:**
   - `app/Models/Member.php` (lines 935-1094)
   - `app/Http/Controllers/Web/Home.php` (lines 133-157)

2. **Test:**
   - `test_subscription_check.php`
   - `manual_subscription_queries.sql`

3. **Documentation:**
   - `SUBSCRIPTION_SETUP.md`
   - This file: `SUBSCRIPTION_CHECKLIST.md`

---

## ✅ Verification Steps

Run these in order:

1. **Verify tables exist:**
   ```bash
   php artisan tinker --execute="echo 'Plans: ' . DB::table('subscription_plans')->count() . '\nSubscriptions: ' . DB::table('member_subscriptions')->count();"
   ```

2. **Verify plans are seeded:**
   ```bash
   php artisan tinker --execute="DB::table('subscription_plans')->select('name', 'slug')->get();"
   ```

3. **Test subscription checking:**
   ```bash
   php artisan tinker < test_subscription_check.php
   ```

4. **Add manual subscription:**
   ```sql
   -- Use manual_subscription_queries.sql Query #3
   ```

5. **Test in browser:**
   - Login as test user
   - Go to `/upgrade` - should show current plan
   - Ask questions - should enforce limits
   - Purchase plan - should give unlimited access

---

## 🚀 System is Ready When:

- ✅ Free users limited to 5 immigration questions
- ✅ Education questions unlimited for all
- ✅ Paid subscribers get unlimited based on plan
- ✅ Usage tracked in `member_subscriptions` table
- ✅ Expired subscriptions revert to free tier
- ✅ Stripe webhooks create subscriptions automatically
- ✅ Upgrade page shows current plan and usage

**All checks passed? System is production-ready! 🎉**