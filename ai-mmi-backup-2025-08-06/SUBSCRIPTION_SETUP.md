# Subscription System Setup Guide

## Overview
This document explains the new scalable subscription system that links Stripe payments to user accounts with admin-configurable features.

## Architecture

### Database Tables

1. **`subscription_plans`** - Admin-configurable subscription tiers
   - Stores plan details (name, price, duration)
   - JSON `features` column for flexible feature flags
   - Links to Stripe products via `stripe_price_id` and `stripe_product_id`

2. **`member_subscriptions`** - Active user subscriptions
   - Links members to their current plan
   - Tracks usage (questions, human agent hours, etc.)
   - Stores expiration dates and status

3. **`payments`** (existing, updated)
   - Now properly links to `user_id`
   - Connected to `member_subscriptions` via `payment_id`

### Models

- **`SubscriptionPlan`** - Plan configuration and feature checking
- **`MemberSubscription`** - User subscription management and usage tracking
- **`Member`** (updated) - Added subscription helper methods

## Setup Instructions

### 1. Run Migrations

```bash
php artisan migrate
```

This will create:
- `subscription_plans` table with 6 default plans (Free, AI, Hybrid, Premium, VIP, Education App)
- `member_subscriptions` table

### 2. Configure Environment Variables

Add to your `.env` file:

```env
# Stripe Configuration
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRICING_TABLE_ID_1=prctbl_...

# Map Stripe Price/Product IDs to Plans
STRIPE_PRICE_AI=price_...
STRIPE_PRODUCT_AI=prod_...

STRIPE_PRICE_HYBRID=price_...
STRIPE_PRODUCT_HYBRID=prod_...

STRIPE_PRICE_PREMIUM=price_...
STRIPE_PRODUCT_PREMIUM=prod_...

STRIPE_PRICE_VIP=price_...
STRIPE_PRODUCT_VIP=prod_...

STRIPE_PRICE_EDUCATION_APP=price_...
STRIPE_PRODUCT_EDUCATION_APP=prod_...
```

### 3. Update Subscription Plans

After migration, update the `subscription_plans` table with your actual Stripe IDs:

```sql
UPDATE subscription_plans
SET stripe_price_id = 'price_XXX', stripe_product_id = 'prod_XXX'
WHERE slug = 'ai';

-- Repeat for hybrid, premium, vip, education_app
```

### 4. Configure Stripe Webhook

1. Go to Stripe Dashboard → Developers → Webhooks
2. Add endpoint: `https://yourdomain.com/stripe/webhook`
3. Select these events:
   - `checkout.session.completed`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
   - `customer.subscription.deleted`
4. Copy the webhook signing secret to `STRIPE_WEBHOOK_SECRET`

### 5. Configure Stripe Pricing Table

In your Stripe Pricing Table settings:
- Enable "Collect customer email"
- Enable "client-reference-id" to pass member_id
- The member_id will be automatically passed via JavaScript in the upgrade page

## Features

### Admin Configuration

Subscription plans are fully configurable via the `features` JSON column:

```json
{
  "migration_questions_limit": 5,    // -1 = unlimited, 0 = none
  "education_questions_limit": -1,   // -1 = unlimited
  "ai_consultation": true,           // boolean
  "human_agent_hours": 2,            // -1 = unlimited, 0 = none
  "validation_check": false,         // boolean
  "full_service": false,             // boolean
  "allowed_visa_types": ["student", "tourist", "skilled_independent", "partner", "child", "graduate_work"],
  "program_applications": 1          // for education plans
}
```

### Usage Tracking

The system automatically tracks:
- Migration questions asked
- Education questions asked
- Human agent hours used
- Program applications submitted

### Question Limits by Mode

- **Immigration mode**: Respects `migration_questions_limit`
- **Education mode**: Respects `education_questions_limit` (Free = unlimited)

## API Reference

### Member Model Methods

```php
// Check if member can ask a question
$canAsk = $memberModel->canAskQuestion($memberId, 'immigration');

// Get active subscription
$subscription = $memberModel->getActiveSubscription($memberId);

// Get current plan info
$planInfo = $memberModel->getCurrentPlanInfo($memberId);

// Increment usage (called automatically after question)
$memberModel->incrementQuestionUsage($memberId, 'immigration');

// Check feature access
$hasAccess = $memberModel->hasFeatureAccess($memberId, 'validation_check');

// Get remaining human agent hours
$hoursLeft = $memberModel->getHumanAgentHoursRemaining($memberId);
```

### SubscriptionPlan Model Methods

```php
// Get feature value
$limit = $plan->getFeature('migration_questions_limit');

// Check if feature exists
$hasFeature = $plan->hasFeature('ai_consultation');

// Check if unlimited
$isUnlimited = $plan->isUnlimited('migration_questions_limit');

// Get question limit for mode
$limit = $plan->getQuestionLimit('immigration');

// Check visa type allowed
$allowed = $plan->isVisaTypeAllowed('student');

// Static: Get free plan
$freePlan = SubscriptionPlan::getFreePlan();

// Static: Find by Stripe price ID
$plan = SubscriptionPlan::findByStripePriceId($priceId);
```

### MemberSubscription Model Methods

```php
// Check if active
$isActive = $subscription->isActive();

// Check if can ask question
$canAsk = $subscription->canAskQuestion('immigration');

// Get remaining questions
$remaining = $subscription->getRemainingQuestions('immigration');

// Increment usage
$subscription->incrementQuestionUsage('immigration');

// Check human agent access
$canUse = $subscription->canUseHumanAgent(2); // 2 hours needed

// Get days until expiration
$daysLeft = $subscription->getDaysUntilExpiration();

// Check if expiring soon
$expiringSoon = $subscription->isExpiringSoon(7); // within 7 days
```

## Middleware

Use the `CheckSubscriptionFeature` middleware to protect routes:

```php
Route::get('/human-agent', [HumanAgentController::class, 'index'])
    ->middleware('check.subscription:human_agent_hours');

Route::get('/validation', [ValidationController::class, 'index'])
    ->middleware('check.subscription:validation_check');
```

Register in `app/Http/Kernel.php`:
```php
protected $routeMiddleware = [
    // ...
    'check.subscription' => \App\Http\Middleware\CheckSubscriptionFeature::class,
];
```

## Webhook Flow

1. User completes checkout → `checkout.session.completed` event
2. Webhook extracts:
   - `member_id` from `session.metadata` or customer email
   - `price_id` from subscription items
   - Subscription details from Stripe
3. Creates/updates records in:
   - `payments` table (with `user_id` linked)
   - `member_subscriptions` table (with plan and usage tracking)
4. Future renewals update via `invoice.payment_succeeded`
5. Cancellations update via `customer.subscription.deleted`

## Testing

### Test Free Tier Limits

1. Create account (no subscription = Free plan)
2. Ask 5 immigration questions
3. 6th question should redirect to `/upgrade`
4. Education questions should work unlimited

### Test Paid Subscription

1. Purchase a plan via Stripe (test mode)
2. Verify `member_subscriptions` record created
3. Ask unlimited immigration questions
4. Check usage tracking in database

### Test Subscription Expiration

1. Manually set `expires_at` to past date in `member_subscriptions`
2. Verify member gets redirected to upgrade page
3. Status should change to expired

## Admin Management

Admin can manage plans at `/admin/subscription-plans` (controller created):

- View all plans
- Edit plan features (JSON configuration)
- Change prices, durations
- Enable/disable plans
- Update Stripe price/product IDs

## Troubleshooting

### Member ID not passed to Stripe

- Check `upgrade.blade.php` has JavaScript metadata code
- Verify `$member_id` is available in controller
- Check browser console for JavaScript errors

### Webhook not creating subscription

- Check logs: `storage/logs/laravel.log`
- Verify Stripe webhook secret is correct
- Ensure migrations ran successfully
- Check if `price_id` in webhook matches `stripe_price_id` in database

### Question limit not working

- Verify `chat_mode` is being passed correctly ('immigration' or 'education')
- Check `member_subscriptions` table for active subscription
- Verify `features` JSON in `subscription_plans` is correct format

### Usage not incrementing

- Check if `incrementQuestionUsage()` is called after `doSave()` in Home controller
- Verify member has active subscription in `member_subscriptions` table

## Future Enhancements

Recommended additions:
1. Cron job to check expired subscriptions daily
2. Email notifications for expiring subscriptions
3. Grace period after expiration
4. Subscription upgrade/downgrade functionality
5. Admin dashboard with subscription analytics
6. Usage charts and reporting
7. Proration support for mid-cycle upgrades

## Support

For issues or questions:
1. Check logs in `storage/logs/laravel.log`
2. Review Stripe webhook logs in Dashboard
3. Verify database records in all 3 tables
4. Test in Stripe test mode first