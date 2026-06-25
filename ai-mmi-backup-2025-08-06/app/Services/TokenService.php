<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TokenService — central ledger for AI-mmi tokens.
 *
 * All balance mutations go through this service so the
 * token_transactions table stays the single source of truth.
 * token_balance on the member row is a denormalised cache for
 * fast reads (navbar display, balance checks).
 */
class TokenService
{
    // -------------------------------------------------------
    // Constants
    // -------------------------------------------------------

    /** Earn types */
    const EARN_SIGNUP            = 'earn_signup';
    const EARN_DAILY_LOGIN       = 'earn_daily_login';
    const EARN_PROFILE_COMPLETE  = 'earn_profile_complete';
    const EARN_SHARE_RESULTS     = 'earn_share_results';
    const EARN_REFERRAL_ACCEPTED = 'earn_referral_accepted';
    const EARN_ADMIN_GRANT       = 'earn_admin_grant';

    /** Spend types */
    const SPEND_CHAT             = 'spend_chat';
    const SPEND_MATCH            = 'spend_match';
    const SPEND_AGENT_CALL       = 'spend_agent_call';
    const SPEND_DIY_VISA         = 'spend_diy_visa';
    const SPEND_FULL_ASSISTANCE  = 'spend_full_assistance';
    const SPEND_SCHOOL_PAYMENT   = 'spend_school_payment';
    const SPEND_ADMIN_DEDUCT     = 'spend_admin_deduct';

    /** Transfer types */
    const TRANSFER_OUT = 'transfer_out';
    const TRANSFER_IN  = 'transfer_in';

    /** Purchase type */
    const PURCHASE = 'purchase';

    /** Earn amounts (tokens) */
    const AMOUNT_SIGNUP            = 20;
    const AMOUNT_DAILY_LOGIN       = 1;
    const AMOUNT_PROFILE_COMPLETE  = 3;
    const AMOUNT_SHARE_RESULTS     = 2;
    const AMOUNT_REFERRAL_ACCEPTED = 5;

    /** Spend amounts (credits) */
    const CHATS_PER_TOKEN  = 5;    // 5 chats = 1 credit deducted
    const MATCHES_PER_TOKEN = 5;   // 5 matches = 1 credit deducted

    // -------------------------------------------------------
    // Balance
    // -------------------------------------------------------

    /**
     * Get current token balance for a member (reads from denormalised column).
     */
    public function getBalance(int $memberId): int
    {
        $row = DB::table('member')->where('id', $memberId)->value('token_balance');
        return (int) $row;
    }

    // -------------------------------------------------------
    // Earn
    // -------------------------------------------------------

    /**
     * Award tokens to a member.
     *
     * @param  int    $memberId
     * @param  int    $amount       Positive integer
     * @param  string $type         One of the EARN_* constants
     * @param  string|null $referenceType  e.g. 'member', 'chat_log'
     * @param  int|null    $referenceId
     * @param  string|null $notes
     * @return bool
     */
    public function earn(
        int $memberId,
        int $amount,
        string $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): bool {
        if ($amount <= 0) {
            return false;
        }

        return $this->applyTransaction(
            $memberId, $amount, $type, $referenceType, $referenceId, $notes
        );
    }

    // -------------------------------------------------------
    // Spend
    // -------------------------------------------------------

    /**
     * Deduct tokens from a member's balance.
     * Returns false if the member has insufficient balance.
     */
    public function spend(
        int $memberId,
        int $amount,
        string $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): bool {
        if ($amount <= 0) {
            return false;
        }

        $balance = $this->getBalance($memberId);
        if ($balance < $amount) {
            return false; // insufficient balance
        }

        return $this->applyTransaction(
            $memberId, -$amount, $type, $referenceType, $referenceId, $notes
        );
    }

    // -------------------------------------------------------
    // Transfer
    // -------------------------------------------------------

    /**
     * Transfer tokens from one member to another.
     * No per-transfer limits per spec; only insufficient balance is rejected.
     */
    public function transfer(int $fromMemberId, int $toMemberId, int $amount): bool
    {
        if ($amount <= 0 || $fromMemberId === $toMemberId) {
            return false;
        }

        $balance = $this->getBalance($fromMemberId);
        if ($balance < $amount) {
            return false;
        }

        return DB::transaction(function () use ($fromMemberId, $toMemberId, $amount) {
            $this->applyTransaction(
                $fromMemberId, -$amount, self::TRANSFER_OUT,
                'member', $toMemberId,
                "Transferred to member #{$toMemberId}"
            );
            $this->applyTransaction(
                $toMemberId, $amount, self::TRANSFER_IN,
                'member', $fromMemberId,
                "Received from member #{$fromMemberId}"
            );
            return true;
        });
    }

    // -------------------------------------------------------
    // Purchase (Stripe)
    // -------------------------------------------------------

    /**
     * Create a Stripe Checkout Session for a token package.
     * Returns the Stripe session URL on success, null on failure.
     */
    public function createPurchaseSession(int $memberId, int $packageId): ?string
    {
        $package = DB::table('token_packages')
            ->where('id', $packageId)
            ->where('is_active', true)
            ->first();

        if (!$package) {
            return null;
        }

        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $member = DB::table('member')->where('id', $memberId)->first();
            $email  = $member->email ?? null;

            // Build a Stripe Price on-the-fly (no pre-created Price ID needed)
            // If admin has set a stripe_price_id, use it; otherwise create ad-hoc
            $lineItem = [];
            if (!empty($package->stripe_price_id)) {
                $lineItem = ['price' => $package->stripe_price_id, 'quantity' => 1];
            } else {
                $lineItem = [
                    'price_data' => [
                        'currency'     => 'usd',
                        'unit_amount'  => (int) round((float)$package->price_usd * 100),
                        'product_data' => [
                            'name' => "AI-mmi {$package->tokens} Tokens",
                        ],
                    ],
                    'quantity' => 1,
                ];
            }

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'mode'                 => 'payment',
                'customer_email'       => $email,
                'client_reference_id'  => "token|{$memberId}|{$packageId}|{$package->tokens}",
                'line_items'           => [$lineItem],
                'success_url'          => url('/en/wallet?payment=success'),
                'cancel_url'           => url('/en/wallet?payment=cancelled'),
                'metadata'             => [
                    'type'       => 'token_purchase',
                    'member_id'  => $memberId,
                    'package_id' => $packageId,
                    'tokens'     => $package->tokens,
                ],
            ]);

            return $session->url;
        } catch (\Exception $e) {
            Log::error("TokenService::createPurchaseSession failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Credit tokens after a confirmed Stripe payment.
     * Called by StripeWebhookController after checkout.session.completed.
     */
    public function creditPurchase(int $memberId, int $tokens, int $paymentId): bool
    {
        return $this->applyTransaction(
            $memberId, $tokens, self::PURCHASE,
            'payment', $paymentId,
            "Purchased {$tokens} tokens"
        );
    }

    // -------------------------------------------------------
    // Referral
    // -------------------------------------------------------

    /**
     * Generate and persist a unique referral code for a member.
     */
    public function generateReferralCode(int $memberId): string
    {
        // Check if already has one
        $existing = DB::table('member')->where('id', $memberId)->value('referral_code');
        if (!empty($existing)) {
            return $existing;
        }

        do {
            $code = strtoupper(substr(md5($memberId . uniqid('', true)), 0, 8));
            $exists = DB::table('member')->where('referral_code', $code)->exists();
        } while ($exists);

        DB::table('member')->where('id', $memberId)->update(['referral_code' => $code]);

        return $code;
    }

    /**
     * Process a referral when a new user signs up with a referral code.
     * Awards 5 tokens to the referrer and records the link.
     */
    public function processReferral(int $newMemberId, string $referralCode): bool
    {
        $referrer = DB::table('member')
            ->where('referral_code', $referralCode)
            ->where('id', '!=', $newMemberId)
            ->first();

        if (!$referrer) {
            return false;
        }

        DB::table('member')
            ->where('id', $newMemberId)
            ->update(['referred_by_member_id' => $referrer->id]);

        return $this->earn(
            (int) $referrer->id,
            self::AMOUNT_REFERRAL_ACCEPTED,
            self::EARN_REFERRAL_ACCEPTED,
            'member',
            $newMemberId,
            "Friend #{$newMemberId} signed up via referral"
        );
    }

    // -------------------------------------------------------
    // Convenience: chat & match spend triggers
    // -------------------------------------------------------

    /**
     * Called after every chat message sent by a member.
     * Deducts 1 token for every CHATS_PER_TOKEN messages.
     * Returns true if a token was deducted, false otherwise.
     */
    public function onChatSent(int $memberId): bool
    {
        // Count total chat messages for this member
        $total = (int) DB::table('chat_log')
            ->where('member_id', $memberId)
            ->where('type', 'ask')
            ->where('status', '>', 0)
            ->count();

        // Only deduct when crossing a multiple of CHATS_PER_TOKEN
        if ($total > 0 && $total % self::CHATS_PER_TOKEN === 0) {
            return $this->spend(
                $memberId, 1, self::SPEND_CHAT,
                'chat_log', null,
                "Chat #{$total}"
            );
        }

        return false;
    }

    /**
     * Called after every school/program match search.
     * Atomically increments match_search_count and deducts 1 token every MATCHES_PER_TOKEN searches.
     */
    public function onMatchMade(int $memberId): bool
    {
        // Atomically increment the counter and get the new value
        DB::table('member')->where('id', $memberId)->increment('match_search_count');
        $total = (int) DB::table('member')->where('id', $memberId)->value('match_search_count');

        if ($total > 0 && $total % self::MATCHES_PER_TOKEN === 0) {
            return $this->spend(
                $memberId, 1, self::SPEND_MATCH,
                'member', $memberId,
                "Every {$total} match searches milestone"
            );
        }

        return false;
    }

    // -------------------------------------------------------
    // Daily login helper
    // -------------------------------------------------------

    /**
     * Award 1 token for daily login if not already awarded today.
     * Safe to call on every login — idempotent within the same calendar day.
     */
    public function awardDailyLogin(int $memberId): bool
    {
        $today = date('Y-m-d');
        $lastDate = DB::table('member')->where('id', $memberId)->value('last_daily_token_date');

        if ($lastDate === $today) {
            return false; // already awarded today
        }

        DB::table('member')->where('id', $memberId)->update([
            'last_daily_token_date' => $today,
        ]);

        return $this->earn(
            $memberId,
            self::AMOUNT_DAILY_LOGIN,
            self::EARN_DAILY_LOGIN,
            'member',
            $memberId,
            'Daily login'
        );
    }

    // -------------------------------------------------------
    // Transaction history
    // -------------------------------------------------------

    /**
     * Get paginated transaction history for a member.
     */
    public function getHistory(int $memberId, int $perPage = 20): object
    {
        return DB::table('token_transactions')
            ->where('member_id', $memberId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    // -------------------------------------------------------
    // Internal
    // -------------------------------------------------------

    /**
     * Apply a single transaction atomically:
     *   1. Lock the member row
     *   2. Update the cached balance
     *   3. Insert the ledger row
     */
    private function applyTransaction(
        int $memberId,
        int $amount,       // positive = credit, negative = debit
        string $type,
        ?string $referenceType,
        ?int $referenceId,
        ?string $notes
    ): bool {
        try {
            DB::transaction(function () use (
                $memberId, $amount, $type, $referenceType, $referenceId, $notes
            ) {
                // Lock member row to prevent race conditions
                $member = DB::table('member')
                    ->where('id', $memberId)
                    ->lockForUpdate()
                    ->first(['id', 'token_balance']);

                if (!$member) {
                    throw new \RuntimeException("Member {$memberId} not found");
                }

                $newBalance = max(0, (int) $member->token_balance + $amount);

                DB::table('member')
                    ->where('id', $memberId)
                    ->update(['token_balance' => $newBalance]);

                DB::table('token_transactions')->insert([
                    'member_id'      => $memberId,
                    'type'           => $type,
                    'amount'         => $amount,
                    'balance_after'  => $newBalance,
                    'reference_type' => $referenceType,
                    'reference_id'   => $referenceId,
                    'notes'          => $notes,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::error("TokenService::applyTransaction failed [{$type}] member={$memberId}: " . $e->getMessage());
            return false;
        }
    }

    // -------------------------------------------------------
    // Plan purchase (token-based plan system)
    // -------------------------------------------------------

    /**
     * Check whether a member holds an active subscription for any of the given plan codes.
     * Reads from the subscriptions + plans tables.
     *
     * @param  int    $memberId
     * @param  string ...$planCodes  e.g. 'premium', 'vip'
     */
    public function hasPlanAccess(int $memberId, string ...$planCodes): bool
    {
        if (empty($planCodes)) {
            return false;
        }

        return DB::table('subscriptions as s')
            ->join('plans as p', 'p.id', '=', 's.plan_id')
            ->where('s.member_id', $memberId)
            ->where('s.status', 'active')
            ->whereIn('p.code', $planCodes)
            ->where(function ($q) {
                $q->whereNull('s.ends_at')->orWhere('s.ends_at', '>', now());
            })
            ->exists();
    }

    /**
     * Create a Stripe Checkout Session for the token deficit of a plan purchase.
     * The Stripe session amount covers ONLY the deficit (tokens beyond current balance).
     * On webhook success, the caller should then call activatePlan().
     *
     * @param  int    $memberId
     * @param  string $planCode       'premium' or 'vip'
     * @param  int    $deficitTokens  Number of tokens to buy via Stripe
     * @param  int    $totalCost      Full token cost of the plan
     * @return string|null  Stripe Checkout Session URL, or null on failure
     */
    public function createPlanDeficitSession(int $memberId, string $planCode, int $deficitTokens, int $totalCost): ?string
    {
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $member = DB::table('member')->where('id', $memberId)->first();
            if (!$member) {
                return null;
            }

            $planNames = [
                'premium' => 'Premium (DIY) Plan',
                'vip'     => 'VIP Agent Plan',
            ];
            $planName = $planNames[$planCode] ?? (ucfirst($planCode) . ' Plan');

            $amountCents = $deficitTokens * 10; // $0.10 per credit

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'mode'                 => 'payment',
                'customer_email'       => $member->email ?? null,
                'line_items'           => [[
                    'price_data' => [
                        'currency'     => 'usd',
                        'unit_amount'  => $amountCents,
                        'product_data' => [
                            'name'        => "AI-mmi {$planName} — Top-up ({$deficitTokens} credits)",
                            'description' => "Deficit payment to unlock your {$planName} (6-month access)",
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => url('/' . app()->getLocale() . '/upgrade?payment=success&plan=' . $planCode),
                'cancel_url'  => url('/' . app()->getLocale() . '/upgrade?payment=cancelled'),
                'metadata'    => [
                    'type'            => 'plan_purchase',
                    'member_id'       => $memberId,
                    'plan_code'       => $planCode,
                    'deficit_tokens'  => $deficitTokens,
                    'total_tokens'    => $totalCost,
                ],
            ]);

            return $session->url ?? null;

        } catch (\Exception $e) {
            Log::error("TokenService::createPlanDeficitSession failed member={$memberId} plan={$planCode}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Spend tokens and create/renew a subscription record for the given plan.
     * Expires any existing active migration subscription for the plan first.
     * Should be called either directly (when balance covers full cost)
     * or from the Stripe webhook (when a deficit was paid).
     *
     * @param  int         $memberId
     * @param  string      $planCode       'premium' or 'vip'
     * @param  int         $tokenCost      Full token cost of the plan
     * @param  string|null $stripeSessionId  If paid via Stripe, pass the session ID for audit
     * @return bool
     */
    public function activatePlan(int $memberId, string $planCode, int $tokenCost, ?string $stripeSessionId = null): bool
    {
        try {
            DB::transaction(function () use ($memberId, $planCode, $tokenCost, $stripeSessionId) {

                // 1. Resolve the plan record
                $plan = DB::table('plans')->where('code', $planCode)->lockForUpdate()->first();
                if (!$plan) {
                    throw new \RuntimeException("Plan '{$planCode}' not found");
                }

                $accessMonths = (int) $plan->access_months ?: 6;

                // 2. Deduct tokens (SPEND_DIY_VISA for premium, SPEND_FULL_ASSISTANCE for vip)
                $spendType = ($planCode === 'vip') ? self::SPEND_FULL_ASSISTANCE : self::SPEND_DIY_VISA;
                $notes     = "Plan activation: {$planCode} ({$accessMonths} months)";
                if ($stripeSessionId) {
                    $notes .= " — Stripe: {$stripeSessionId}";
                }

                // applyTransaction expects negative amounts for spend
                $this->applyTransaction(
                    $memberId,
                    -$tokenCost,
                    $spendType,
                    'plans',
                    (int) $plan->id,
                    $notes
                );

                // 3. Expire any currently-active subscriptions for this plan
                DB::table('subscriptions')
                    ->where('member_id', $memberId)
                    ->where('plan_id', $plan->id)
                    ->where('status', 'active')
                    ->update([
                        'status'     => 'cancelled',
                        'updated_at' => now(),
                    ]);

                // 4. Insert new subscription record
                $startsAt = now();
                $endsAt   = now()->addMonths($accessMonths);

                $meta = ['source' => 'token_payment'];
                if ($stripeSessionId) {
                    $meta['stripe_session_id'] = $stripeSessionId;
                }

                DB::table('subscriptions')->insert([
                    'member_id'  => $memberId,
                    'plan_id'    => $plan->id,
                    'status'     => 'active',
                    'started_at' => $startsAt,
                    'ends_at'    => $endsAt,
                    'currency'   => 'usd',
                    'amount_usd' => $tokenCost * 0.10,
                    'meta'       => json_encode($meta),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            return true;

        } catch (\Exception $e) {
            Log::error("TokenService::activatePlan failed member={$memberId} plan={$planCode}: " . $e->getMessage());
            return false;
        }
    }
}
