-- ============================================
-- Manual Subscription Management SQL Queries
-- ============================================

-- 1. VIEW ALL AVAILABLE PLANS
-- ============================================
SELECT
    id,
    name,
    slug,
    price,
    duration_months,
    is_active
FROM app_subscription_plans
ORDER BY display_order;

-- Expected output:
-- 1  | Free Plan             | free         | 0.00  | 0  | 1
-- 2  | All AI Plan           | ai           | 99.00 | 6  | 1
-- 3  | Hybrid Plan           | hybrid       | 299.00| 6  | 1
-- 4  | Premium Plan          | premium      | 699.00| 6  | 1
-- 5  | VIP Plan              | vip          | 999.00| 6  | 1
-- 6  | Education Application | education_app| 100.00| 0  | 1


-- 2. VIEW ALL MEMBERS (to get member_id)
-- ============================================
SELECT
    id,
    email,
    alias_name,
    full_name
FROM app_member
WHERE status > 0
ORDER BY id DESC
LIMIT 20;


-- 3. ADD SUBSCRIPTION TO A MEMBER
-- ============================================
-- Replace:
--   5 = your member_id
--   2 = subscription_plan_id (2 = AI Plan, see query #1)

INSERT INTO app_member_subscriptions (
    member_id,
    subscription_plan_id,
    stripe_subscription_id,
    stripe_customer_id,
    status,
    started_at,
    expires_at,
    migration_questions_used,
    education_questions_used,
    human_agent_hours_used,
    program_applications_used,
    metadata,
    created_at,
    updated_at
) VALUES (
    5,                                    -- member_id (CHANGE THIS)
    2,                                    -- plan_id: 2=AI, 3=Hybrid, 4=Premium, 5=VIP
    NULL,                                 -- No Stripe ID (manual)
    NULL,                                 -- No Stripe customer
    'active',                             -- Status
    NOW(),                                -- Started now
    DATE_ADD(NOW(), INTERVAL 6 MONTH),   -- Expires in 6 months
    0,                                    -- Questions used
    0,
    0,
    0,
    '{"source":"manual","added_by":"admin"}',
    NOW(),
    NOW()
);


-- 4. VIEW MEMBER'S SUBSCRIPTIONS
-- ============================================
-- Replace 5 with your member_id

SELECT
    ms.id,
    ms.member_id,
    m.email,
    sp.name as plan_name,
    sp.slug as plan_slug,
    sp.price,
    ms.status,
    ms.started_at,
    ms.expires_at,
    ms.migration_questions_used,
    ms.education_questions_used,
    ms.human_agent_hours_used,
    CASE
        WHEN ms.expires_at IS NULL THEN 'No expiration'
        WHEN ms.expires_at > NOW() THEN CONCAT(DATEDIFF(ms.expires_at, NOW()), ' days left')
        ELSE 'EXPIRED'
    END as expiration_status
FROM app_member_subscriptions ms
JOIN app_member m ON ms.member_id = m.id
JOIN app_subscription_plans sp ON ms.subscription_plan_id = sp.id
WHERE ms.member_id = 5;


-- 5. UPDATE SUBSCRIPTION (UPGRADE/DOWNGRADE)
-- ============================================
-- Change member 5's subscription to Premium (plan_id = 4)

UPDATE app_member_subscriptions
SET
    subscription_plan_id = 4,          -- 4 = Premium Plan
    expires_at = DATE_ADD(NOW(), INTERVAL 6 MONTH),
    updated_at = NOW()
WHERE
    member_id = 5
    AND status IN ('active', 'trialing');


-- 6. EXTEND SUBSCRIPTION
-- ============================================
-- Add 3 more months to member 5's subscription

UPDATE app_member_subscriptions
SET
    expires_at = DATE_ADD(expires_at, INTERVAL 3 MONTH),
    updated_at = NOW()
WHERE
    member_id = 5
    AND status = 'active';


-- 7. CANCEL SUBSCRIPTION
-- ============================================
UPDATE app_member_subscriptions
SET
    status = 'canceled',
    canceled_at = NOW(),
    updated_at = NOW()
WHERE
    member_id = 5
    AND status IN ('active', 'trialing');


-- 8. REACTIVATE CANCELED SUBSCRIPTION
-- ============================================
UPDATE app_member_subscriptions
SET
    status = 'active',
    canceled_at = NULL,
    expires_at = DATE_ADD(NOW(), INTERVAL 6 MONTH),
    updated_at = NOW()
WHERE
    member_id = 5
    AND status = 'canceled';


-- 9. RESET USAGE COUNTERS
-- ============================================
UPDATE app_member_subscriptions
SET
    migration_questions_used = 0,
    education_questions_used = 0,
    human_agent_hours_used = 0,
    program_applications_used = 0,
    updated_at = NOW()
WHERE member_id = 5;


-- 10. BULK ASSIGN SUBSCRIPTION TO MULTIPLE MEMBERS
-- ============================================
-- Give AI Plan (id=2) to members 5, 10, 15, 20

INSERT INTO app_member_subscriptions
    (member_id, subscription_plan_id, status, started_at, expires_at,
     migration_questions_used, education_questions_used, human_agent_hours_used,
     program_applications_used, created_at, updated_at)
VALUES
    (5,  2, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 6 MONTH), 0, 0, 0, 0, NOW(), NOW()),
    (10, 2, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 6 MONTH), 0, 0, 0, 0, NOW(), NOW()),
    (15, 2, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 6 MONTH), 0, 0, 0, 0, NOW(), NOW()),
    (20, 2, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 6 MONTH), 0, 0, 0, 0, NOW(), NOW());


-- 11. VIEW ALL ACTIVE SUBSCRIPTIONS
-- ============================================
SELECT
    ms.id,
    m.id as member_id,
    m.email,
    m.alias_name,
    sp.name as plan_name,
    ms.status,
    ms.started_at,
    ms.expires_at,
    CASE
        WHEN ms.expires_at IS NULL THEN '∞'
        WHEN ms.expires_at > NOW() THEN CONCAT(DATEDIFF(ms.expires_at, NOW()), 'd')
        ELSE 'EXPIRED'
    END as days_left
FROM app_member_subscriptions ms
JOIN app_member m ON ms.member_id = m.id
JOIN app_subscription_plans sp ON ms.subscription_plan_id = sp.id
WHERE ms.status IN ('active', 'trialing')
ORDER BY ms.created_at DESC
LIMIT 50;


-- 12. VIEW EXPIRING SUBSCRIPTIONS (within 7 days)
-- ============================================
SELECT
    m.id as member_id,
    m.email,
    sp.name as plan_name,
    ms.expires_at,
    DATEDIFF(ms.expires_at, NOW()) as days_left
FROM app_member_subscriptions ms
JOIN app_member m ON ms.member_id = m.id
JOIN app_subscription_plans sp ON ms.subscription_plan_id = sp.id
WHERE
    ms.status = 'active'
    AND ms.expires_at IS NOT NULL
    AND ms.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
ORDER BY ms.expires_at ASC;


-- 13. VIEW SUBSCRIPTION STATISTICS
-- ============================================
SELECT
    sp.name as plan_name,
    COUNT(ms.id) as total_subscriptions,
    SUM(CASE WHEN ms.status = 'active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN ms.status = 'canceled' THEN 1 ELSE 0 END) as canceled,
    SUM(CASE WHEN ms.status = 'past_due' THEN 1 ELSE 0 END) as past_due,
    SUM(sp.price) as total_revenue
FROM app_subscription_plans sp
LEFT JOIN app_member_subscriptions ms ON sp.id = ms.subscription_plan_id
GROUP BY sp.id, sp.name
ORDER BY total_subscriptions DESC;


-- 14. DELETE A SUBSCRIPTION (USE WITH CAUTION!)
-- ============================================
-- Permanently remove subscription record
-- WARNING: This cannot be undone!

DELETE FROM app_member_subscriptions
WHERE
    member_id = 5
    AND id = 123;  -- Specific subscription ID


-- 15. GIVE LIFETIME ACCESS (NO EXPIRATION)
-- ============================================
-- Set expires_at to NULL for unlimited access

UPDATE app_member_subscriptions
SET
    expires_at = NULL,
    updated_at = NOW()
WHERE member_id = 5;


-- ============================================
-- QUICK REFERENCE
-- ============================================
/*
Plan IDs (from app_subscription_plans):
1 = Free Plan (default)
2 = All AI Plan ($99/6mo)
3 = Hybrid Plan ($299/6mo)
4 = Premium Plan ($699/6mo)
5 = VIP Plan ($999/6mo)
6 = Education Application ($100)

Subscription Status Values:
- 'active'      : Currently active
- 'trialing'    : In trial period
- 'past_due'    : Payment failed
- 'canceled'    : Canceled by user/admin
- 'incomplete'  : Payment not completed
- 'unpaid'      : Payment overdue
- 'paused'      : Temporarily paused

Common Use Cases:
- New subscription: Use Query #3
- Upgrade plan: Use Query #5
- Extend time: Use Query #6
- Cancel: Use Query #7
- View status: Use Query #4
- Bulk assign: Use Query #10
*/