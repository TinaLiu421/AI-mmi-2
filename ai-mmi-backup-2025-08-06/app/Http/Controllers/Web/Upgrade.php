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

        $base = rtrim($this->_page_base_url ?? '', '/');

        $data = [
            'plans_gate' => [
                [
                    'code' => 'all_ai',
                    'name' => 'AI Smart Plan',
                    'subtitle' => 'Your 24/7 AI migration guide. Perfect for self-starters who want smart support anytime (For 3 months).',
                    'price' => '$12',
                    'billing' => 'every 3 months',
                    'cta' => 'Subscribe',
                    'is_popular' => false,
                    'features' => [
                        'Unlimited AI migration and visa guidance',
                        'DIY tools for eligibility, document prep, and planning',
                        'Regular policy updates and step-by-step guidance',
                    ],
                    'checkout_url' => $base . '/upgrade/checkout/all_ai',
                ],
                [
                    'code' => 'hybrid',
                    'name' => 'AI + Agent Plan',
                    'subtitle' => 'AI Smart Plan + 2-hour voice or video call with a qualified migration/education agent',
                    'price' => '$99',
                    'billing' => 'every 3 months',
                    'cta' => 'Subscribe',
                    'is_popular' => true,
                    'features' => [
                        'Everything in the AI Smart Plan',
                        '2-hour consultation with a registered migration agent/lawyer',
                        'Personalized feedback and recommendations',
                    ],
                    'checkout_url' => $base . '/upgrade/checkout/hybrid',
                ],
                [
                    'code' => 'premium',
                    'name' => 'DIY Plan',
                    'subtitle' => 'DIY for visa submission with final validation and review by a qualified migration agent',
                    'price' => '$699',
                    'billing' => 'one-time payment',
                    'cta' => 'Pay',
                    'is_popular' => false,
                    'features' => [
                        'Everything in the Hybrid Plan',
                        'Final review of your DIY application by a licensed expert',
                        'Detailed recommendations before submission',
                    ],
                    'checkout_url' => $base . '/upgrade/checkout/premium',
                ],
                [
                    'code' => 'vip',
                    'name' => 'VIP Agent Plan',
                    'subtitle' => 'AI and qualified migration agent support for student, graduate work, working holiday, tourist, and certain family visas',
                    'price' => '$999',
                    'billing' => 'one-time payment',
                    'cta' => 'Pay',
                    'is_popular' => false,
                    'features' => [
                        'Everything in the Premium Plan',
                        'Full guidance and support from a licensed migration agent or lawyer',
                        'Continuous follow-up and personalized support',
                        '*student, graduate work, working holiday, tourist, and certain family visas only',
                    ],
                    'checkout_url' => $base . '/upgrade/checkout/vip',
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

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $priceObj = StripePrice::retrieve($plan->stripe_price_id);
            $mode = !empty($priceObj->recurring) ? 'subscription' : 'payment';

            $successUrl = rtrim($this->_page_base_url, '/') . '/upgrade?payment=success';
            $cancelUrl  = rtrim($this->_page_base_url, '/') . '/upgrade?payment=cancel';

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

            return redirect()->to($this->toURL('upgrade'));
        }
    }
}
