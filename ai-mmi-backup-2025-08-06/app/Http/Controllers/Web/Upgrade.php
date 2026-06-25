<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use App\Services\TokenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Upgrade extends WebController
{
    /** Plans available for token-based purchase (ordered for display). */
    private const TOKEN_PLAN_CODES = ['agent_call', 'premium', 'vip'];

    /** Plan display metadata */
    private const PLAN_META = [
        'agent_call' => [
            'name'        => 'AI + Agent Plan',
            'subtitle'    => 'Smart AI + Human Expert Support',
            'description' => 'Get instant guidance with access to qualified agents when you need personalized help.',
            'features'    => [
                'AI-powered guidance',
                'Full smart matching features',
                'Consultation call with expert agents for up to 1 hour (may arrange more consultations using tokens)',
                'Faster and more affordable than traditional agencies',
            ],
            'best_for'    => 'Best for users who want expert support at lower cost',
            'is_popular'  => false,
        ],
        'premium' => [
            'name'        => 'DIY + Expert Review Plan',
            'subtitle'    => "Do-It-Yourself for Visa + Expert's Review before Submission",
            'description' => 'Perfect for individual applicants who want to prepare and submit visa applications without agents.',
            'features'    => [
                'AI visa eligibility assessment',
                'Step by step guidance for 6 months',
                'Document checklists',
                'One feedback by an expert agent after screening your application before submission',
            ],
            'best_for'    => 'Save money while staying guided and organized',
            'is_popular'  => false,
        ],
        'vip' => [
            'name'        => 'Full Agent Service Plan',
            'subtitle'    => 'End-to-End Professional Assistance',
            'description' => 'A complete AI+Agent services with dedicated agent support throughout your application journey.',
            'features'    => [
                'Full visa consultation',
                'Full visa application services by both AI & expert agents (limit to Student Visa, Guardian Visa, Domestic Helper Visa, Working Holiday Visa, Visitor Visa and Graduate Work Visa only)',
            ],
            'best_for'    => 'Maximum support for the best chance of success at competitive rates',
            'is_popular'  => true,
        ],
    ];

    public function index()
    {
        $member = $this->_current_member ?? null;

        if (empty($member)) {
            $loginUrl = $this->toURL('account_login') . '?redirect=' . urlencode(url()->current());
            return redirect()->to($loginUrl);
        }

        $this->pageMeta([
            'title'       => $this->_page_lang['upgrade'] ?? 'Upgrade',
            'description' => '',
            'image'       => '',
        ]);

        $memberId = (int) $member['id'];
        $svc      = new TokenService();
        $balance  = $svc->getBalance($memberId);

        // All active token-based subscriptions (may be multiple)
        $activeSubs = DB::table('subscriptions as s')
            ->join('plans as p', 'p.id', '=', 's.plan_id')
            ->where('s.member_id', $memberId)
            ->where('s.status', 'active')
            ->where(function ($q) {
                $q->whereNull('s.ends_at')->orWhere('s.ends_at', '>', now());
            })
            ->whereIn('p.code', self::TOKEN_PLAN_CODES)
            ->select('p.code', 'p.name', 's.ends_at', 'p.token_cost')
            ->get();

        $activeSubCodes = $activeSubs->pluck('code')->toArray();

        // Show highest-value active plan in the header banner
        $activeSub = $activeSubs->sortByDesc('token_cost')->first();

        // Build plan cards from DB + metadata
        $plansGate = [];
        $dbPlans   = DB::table('plans')
            ->whereIn('code', self::TOKEN_PLAN_CODES)
            ->where('is_active', 1)
            ->get()
            ->keyBy('code');

        foreach (self::TOKEN_PLAN_CODES as $code) {
            $plan = $dbPlans[$code] ?? null;
            if (!$plan) {
                continue;
            }
            $meta       = self::PLAN_META[$code] ?? [];
            $tokenCost  = (int) $plan->token_cost;
            $deficit    = max(0, $tokenCost - $balance);
            $isCurrent  = in_array($code, $activeSubCodes, true);

            $plansGate[] = [
                'code'           => $code,
                'name'           => $meta['name'] ?? $plan->name,
                'subtitle'       => $meta['subtitle'] ?? '',
                'description'    => $meta['description'] ?? '',
                'features'       => $meta['features'] ?? [],
                'best_for'       => $meta['best_for'] ?? '',
                'is_popular'     => $meta['is_popular'] ?? false,
                'token_cost'     => $tokenCost,
                'usd_equiv'      => '$' . number_format($tokenCost * 0.10, 0),
                'access_months'  => (int) $plan->access_months,
                'deficit'        => $deficit,
                'deficit_usd'    => '$' . number_format($deficit * 0.10, 2),
                'can_pay_direct' => ($balance >= $tokenCost),
                'is_current'     => $isCurrent,
                'checkout_url'   => $this->toURL('upgrade/checkout/' . $code),
            ];
        }

        $data = [
            'token_balance'       => $balance,
            'wallet_url'          => $this->toURL('wallet'),
            'plans_gate'          => $plansGate,
            'current_plan_code'   => $activeSub->code   ?? null,
            'current_plan_name'   => $activeSub->name   ?? null,
            'current_plan_expiry' => $activeSub->ends_at ?? null,
        ];

        return $this->pageData($data)->pageView();
    }

    public function checkout($planCode = '')
    {
        $member = $this->_current_member ?? null;
        if (empty($member)) {
            $loginUrl = $this->toURL('account_login') . '?redirect=' . urlencode(url()->current());
            return redirect()->to($loginUrl);
        }

        $planCode = trim((string) $planCode);
        if (!in_array($planCode, self::TOKEN_PLAN_CODES, true)) {
            return redirect()->to($this->toURL('upgrade'));
        }

        $plan = DB::table('plans')->where('code', $planCode)->first();
        if (!$plan || !(int) $plan->token_cost) {
            return redirect()->to($this->toURL('upgrade'));
        }

        $memberId  = (int) $member['id'];
        $tokenCost = (int) $plan->token_cost;
        $svc       = new TokenService();
        $balance   = $svc->getBalance($memberId);

        // Prevent re-purchase while already active
        if ($svc->hasPlanAccess($memberId, $planCode)) {
            return redirect()->to($this->toURL('upgrade') . '?notice=already_active');
        }

        if ($balance >= $tokenCost) {
            // Sufficient balance — activate directly without Stripe
            $ok = $svc->activatePlan($memberId, $planCode, $tokenCost);
            if ($ok) {
                return redirect()->to($this->toURL('upgrade') . '?payment=success&plan=' . $planCode);
            }
            Log::error('upgrade.checkout: activatePlan failed', [
                'member_id' => $memberId,
                'plan_code' => $planCode,
            ]);
            return redirect()->to($this->toURL('upgrade') . '?error=activation_failed');
        }

        // Balance insufficient — charge the deficit via Stripe
        $deficit   = $tokenCost - $balance;
        $stripeUrl = $svc->createPlanDeficitSession($memberId, $planCode, $deficit, $tokenCost);

        if (!$stripeUrl) {
            Log::error('upgrade.checkout: createPlanDeficitSession failed', [
                'member_id' => $memberId,
                'plan_code' => $planCode,
                'deficit'   => $deficit,
            ]);
            return redirect()->to($this->toURL('upgrade') . '?error=stripe_failed');
        }

        return redirect()->away($stripeUrl);
    }
}
