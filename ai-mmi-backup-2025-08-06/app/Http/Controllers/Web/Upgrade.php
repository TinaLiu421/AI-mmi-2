<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Price as StripePrice;

class Upgrade extends WebController
{
    public function index()
    {
        $member = $this->_current_member ?? null;

        if (empty($member)) {
            // Retrieve the current full URL, ensuring proper redirection after login
            $currentUrl = url()->current();
            // Generate a login page link with the redirect parameter included
            $loginUrl = $this->toURL('account_login') . '?redirect=' . urlencode($currentUrl);
            return redirect()->to($loginUrl);
        }

        // Logged in: Upgrade page rendering normally
        $this->pageMeta([
            'title'       => $this->_page_lang['upgrade'] ?? 'Upgrade',
            'description' => '',
            'image'       => ''
        ]);

        // Detect the member's current active migration plan (if any)
        $activeMigrationSub = DB::table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.member_id', $member['id'])
            ->where('subscriptions.status', 'active')
            ->where(function ($q) {
                $q->whereNull('subscriptions.ends_at')
                  ->orWhere('subscriptions.ends_at', '>', now());
            })
            ->where('plans.business_domain', 'migration')
            ->orderByDesc('subscriptions.id')
            ->select('plans.code as plan_code', 'plans.name as plan_name', 'subscriptions.ends_at', 'subscriptions.stripe_subscription_id')
            ->first();

        $currentPlanCode = $activeMigrationSub->plan_code ?? null;

        $data = [
            'pricing_table_id'   => env('STRIPE_PRICING_TABLE_ID_1'),
            'stripe_pk'          => env('STRIPE_KEY'),
            'current_plan_code'  => $currentPlanCode,
            'current_plan_name'  => $activeMigrationSub->plan_name ?? null,
            'current_plan_expiry'=> $activeMigrationSub->ends_at ?? null,
            'plans_gate' => [
                [
                    'code' => 'all_ai',
                    'name' => 'AI Smart Plan',
                    'period_label' => '(For 90 days)',
                    'renew_note' => 'Auto renews unless cancelled',
                    'subtitle' => 'Your 24/7 AI migration guide. Perfect for self-starters who want smart support anytime.',
                    'price' => '$9',
                    'billing' => '',
                    'cta' => 'Subscribe',
                    'is_popular' => false,
                    'features' => [
                        'Unlimited AI migration and visa guidance',
                        'DIY tools for eligibility, document prep, and planning',
                        'Regular policy updates and step-by-step guidance',
                    ],
                    'checkout_url' => $this->toURL('upgrade/checkout/all_ai'),
                ],
                [
                    'code' => 'hybrid',
                    'name' => 'AI + Agent Plan',
                    'period_label' => '(For 90 days)',
                    'renew_note' => 'Auto renews unless cancelled',
                    'subtitle' => 'AI Smart Plan + 2-hour voice or video call with a qualified migration/education agent',
                    'price' => '$29',
                    'billing' => '',
                    'cta' => 'Subscribe',
                    'is_popular' => true,
                    'features' => [
                        'Everything in the AI Smart Plan',
                        '2-hour consultation with a registered migration agent/lawyer',
                        'Personalized feedback and recommendations',
                    ],
                    'checkout_url' => $this->toURL('upgrade/checkout/hybrid'),
                ],
                [
                    'code' => 'premium',
                    'name' => 'DIY Plan',
                    'period_label' => 'One-time payment',
                    'renew_note' => '',
                    'subtitle' => 'DIY for visa submission with final validation and review by a qualified migration agent',
                    'price' => '$699',
                    'billing' => '',
                    'cta' => 'Pay',
                    'is_popular' => false,
                    'features' => [
                        'Everything in the Hybrid Plan',
                        'Final review of your DIY application by a licensed expert',
                        'Detailed recommendations before submission',
                    ],
                    'checkout_url' => $this->toURL('upgrade/checkout/premium'),
                ],
                [
                    'code' => 'vip',
                    'name' => 'VIP Agent Plan',
                    'period_label' => 'One-time payment',
                    'renew_note' => '',
                    'subtitle' => 'AI and qualified migration agent support for student, graduate work, working holiday, tourist, and certain family visas',
                    'price' => '$999',
                    'billing' => '',
                    'cta' => 'Pay',
                    'is_popular' => false,
                    'features' => [
                        'Everything in the Premium Plan',
                        'Full guidance and support from a licensed migration agent or lawyer',
                        'Continuous follow-up and personalized support',
                        '*student, graduate work, working holiday, tourist, and certain family visas only',
                    ],
                    'checkout_url' => $this->toURL('upgrade/checkout/vip'),
                ],
            ],
        ];

        return $this->pageData($data)->pageView();
    }

    public function checkout($planCode = '')
    {
        $member = $this->_current_member ?? null;
        if (empty($member)) {
            $currentUrl = url()->current();
            $loginUrl = $this->toURL('account_login') . '?redirect=' . urlencode($currentUrl);
            return redirect()->to($loginUrl);
        }

        $planCode = trim((string)$planCode);
        if (!in_array($planCode, ['all_ai', 'hybrid', 'premium', 'vip'], true)) {
            return redirect()->to($this->toURL('upgrade'));
        }

        $plan = DB::table('plans')->where('code', $planCode)->first();
        if (!$plan || empty($plan->stripe_price_id)) {
            Log::warning('upgrade.checkout missing stripe price mapping', [
                'plan_code' => $planCode,
                'member_id' => (int)($member['id'] ?? 0),
            ]);
            return redirect()->to($this->toURL('upgrade'));
        }

        // Guard: prevent re-subscribing to the exact same plan while still active
        $alreadyActive = DB::table('subscriptions')
            ->where('member_id', $member['id'])
            ->where('status', 'active')
            ->where('plan_id', $plan->id)
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->exists();

        if ($alreadyActive) {
            $this->setSession(['error_message' => 'You already have this plan active. To renew early, please wait until closer to your expiry date.']);
            return redirect()->to($this->toURL('upgrade'));
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        // Upgrade path: cancel the old Stripe subscription so it stops billing
        $oldMigrationSub = DB::table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.member_id', $member['id'])
            ->where('subscriptions.status', 'active')
            ->whereNotNull('subscriptions.stripe_subscription_id')
            ->where('plans.business_domain', 'migration')
            ->where(function ($q) {
                $q->whereNull('subscriptions.ends_at')->orWhere('subscriptions.ends_at', '>', now());
            })
            ->orderByDesc('subscriptions.id')
            ->select('subscriptions.id', 'subscriptions.stripe_subscription_id')
            ->first();

        if ($oldMigrationSub) {
            try {
                \Stripe\Subscription::cancel($oldMigrationSub->stripe_subscription_id);
                DB::table('subscriptions')
                    ->where('id', $oldMigrationSub->id)
                    ->update(['status' => 'expired', 'ends_at' => now(), 'updated_at' => now()]);
                Log::info('upgrade.checkout: cancelled old subscription', [
                    'old_sub_id'        => $oldMigrationSub->id,
                    'stripe_sub_id'     => $oldMigrationSub->stripe_subscription_id,
                    'member_id'         => (int)($member['id'] ?? 0),
                    'upgrading_to'      => $planCode,
                ]);
            } catch (\Throwable $e) {
                // Log but don't block the upgrade checkout
                Log::warning('upgrade.checkout: could not cancel old subscription', [
                    'stripe_sub_id' => $oldMigrationSub->stripe_subscription_id,
                    'error'         => $e->getMessage(),
                ]);
            }
        }

        try {
            $priceObj = StripePrice::retrieve($plan->stripe_price_id);
            $mode = !empty($priceObj->recurring) ? 'subscription' : 'payment';

            $successUrl = $this->toURL('upgrade') . '?payment=success';
            $cancelUrl  = $this->toURL('upgrade') . '?payment=cancel';

            $session = StripeCheckoutSession::create([
                'mode' => $mode,
                'line_items' => [[
                    'price' => $plan->stripe_price_id,
                    'quantity' => 1,
                ]],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'client_reference_id' => (string)($member['id'] ?? ''),
                'customer_email' => (string)($member['email'] ?? ''),
                'allow_promotion_codes' => true,
                'metadata' => [
                    'member_id' => (string)($member['id'] ?? ''),
                    'plan_code' => (string)$planCode,
                    'source' => 'custom-upgrade-page',
                ],
            ]);

            return redirect()->away($session->url);
        } catch (\Throwable $e) {
            Log::error('upgrade.checkout failed', [
                'plan_code' => $planCode,
                'member_id' => (int)($member['id'] ?? 0),
                'error' => $e->getMessage(),
            ]);

            $this->setSession(['error_message' => 'Unable to start Stripe checkout right now. Please try again.']);
            return redirect()->to($this->toURL('upgrade'));
        }
    }
}
