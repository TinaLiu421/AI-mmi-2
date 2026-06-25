-- ============================================================
-- AI-mmi Production DB Update: Token Plans & Packages
-- Run this on the production MySQL database
-- Verified against production snapshot: 2026-06-04
-- ============================================================

-- ── 1. Update plan token_costs ──────────────────────────────
UPDATE plans SET token_cost = 400  WHERE code = 'agent_call';  -- was 390
UPDATE plans SET token_cost = 2000 WHERE code = 'premium';     -- was 1900
UPDATE plans SET token_cost = 4600 WHERE code = 'vip';         -- was 4900

-- ── 2. Fix price_usd mismatch on existing packages ──────────
-- id=2 (500 Tokens): price_usd was 90.00, should be 100.00
UPDATE token_packages SET price_usd = 100.00 WHERE id = 2;

-- id=3 (1000 Tokens): price_usd was 160.00, should be 200.00
UPDATE token_packages SET price_usd = 200.00 WHERE id = 3;

-- ── 3. Insert 2 new packages (2000T and 5000T) ──────────────
-- Only inserts if they don't already exist (safe to re-run)
INSERT INTO token_packages (name, tokens, price_usd_cents, price_usd, is_active, sort_order)
SELECT '2000 Tokens', 2000, 40000, 400.00, 1, 4
WHERE NOT EXISTS (SELECT 1 FROM token_packages WHERE tokens = 2000);

INSERT INTO token_packages (name, tokens, price_usd_cents, price_usd, is_active, sort_order)
SELECT '5000 Tokens', 5000, 100000, 1000.00, 1, 5
WHERE NOT EXISTS (SELECT 1 FROM token_packages WHERE tokens = 5000);

-- ── 4. Verify ───────────────────────────────────────────────
SELECT id, name, tokens, price_usd_cents, price_usd, is_active, sort_order
FROM token_packages
ORDER BY sort_order;

SELECT code, token_cost
FROM plans
WHERE code IN ('agent_call', 'premium', 'vip');
