<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use App\Services\TokenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Wallet controller — handles token purchase, transfer, history, and share earn.
 * Routes:
 *   GET  /{lang}/wallet             → index (wallet page)
 *   POST /{lang}/wallet/buy         → initiate Stripe token purchase
 *   POST /{lang}/wallet/transfer    → peer transfer
 *   POST /{lang}/wallet/share       → earn share_results tokens
 */
class Wallet extends WebController
{
    protected TokenService $tokenService;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->tokenService = new TokenService();

        // Must be logged in for all wallet actions
        if (empty($this->_current_member)) {
            header('Location: /en/account_login');
            exit();
        }
    }

    // -------------------------------------------------------
    // GET /{lang}/wallet
    // -------------------------------------------------------
    public function index($lang = 'en')
    {
        $memberId = (int) $this->_current_member['id'];
        $balance  = $this->tokenService->getBalance($memberId);

        // Transaction history (latest 20)
        $transactions = DB::table('token_transactions')
            ->where('member_id', $memberId)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        // Token packages for purchase UI
        $packages = DB::table('token_packages')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Referral info
        $referralCode = !empty($this->_current_member['referral_code'])
            ? $this->_current_member['referral_code']
            : $this->tokenService->generateReferralCode($memberId);
        $referralUrl  = url('/en/account_registration/individual?ref=' . $referralCode);

        // Payment status flash (from Stripe return URL)
        $paymentStatus = request()->query('payment');

        return $this->pageData([
            'balance'        => $balance,
            'transactions'   => $transactions,
            'packages'       => $packages,
            'referral_code'  => $referralCode,
            'referral_url'   => $referralUrl,
            'payment_status' => $paymentStatus,
        ])->pageView('wallet');
    }

    // -------------------------------------------------------
    // POST /{lang}/wallet/buy
    // -------------------------------------------------------
    public function buy()
    {
        $packageId = (int) request()->input('package_id', 0);
        if (!$packageId) {
            return response()->json(['status' => 400, 'message' => 'Invalid package.']);
        }

        $memberId = (int) $this->_current_member['id'];
        $url = $this->tokenService->createPurchaseSession($memberId, $packageId);

        if ($url) {
            return response()->json(['status' => 200, 'url' => $url]);
        }
        return response()->json(['status' => 500, 'message' => 'Could not create payment session. Please try again.']);
    }

    // -------------------------------------------------------
    // POST /{lang}/wallet/transfer
    // -------------------------------------------------------
    public function transfer()
    {
        $memberId = (int) $this->_current_member['id'];
        $amount   = (int) request()->input('amount', 0);
        $toEmail  = trim((string) request()->input('to_email', ''));

        $validator = Validator::make(
            ['amount' => $amount, 'to_email' => $toEmail],
            [
                'amount'   => 'required|integer|min:1',
                'to_email' => 'required|email',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
        }

        // Find recipient
        $recipient = DB::table('member')
            ->where('email', $toEmail)
            ->where('status', '>', 0)
            ->first();

            if (!$recipient) {
            return response()->json(['status' => 404, 'message' => 'Recipient email not found.']);
        }

        if ((int)$recipient->id === $memberId) {
            return response()->json(['status' => 400, 'message' => 'You cannot transfer credits to yourself.']);
        }

        $balance = $this->tokenService->getBalance($memberId);
        if ($balance < $amount) {
            return response()->json(['status' => 400, 'message' => "Insufficient balance. You have {$balance} credits."]);
        }

        $ok = $this->tokenService->transfer($memberId, (int)$recipient->id, $amount);

        if ($ok) {
            return response()->json([
                'status'  => 200,
                'message' => "Successfully sent {$amount} credit" . ($amount !== 1 ? 's' : '') . " to {$toEmail}.",
            ]);
        }
        return response()->json(['status' => 500, 'message' => 'Transfer failed. Please try again.']);
    }

    // -------------------------------------------------------
    // POST /{lang}/wallet/share
    // Earn 2 tokens when user shares results (one-time per day)
    // -------------------------------------------------------
    public function share()
    {
        $memberId = (int) $this->_current_member['id'];

        // Limit share earn to once per 24 hours via check on recent transactions
        $lastShare = DB::table('token_transactions')
            ->where('member_id', $memberId)
            ->where('type', TokenService::EARN_SHARE_RESULTS)
            ->whereDate('created_at', date('Y-m-d'))
            ->exists();

        if ($lastShare) {
            return response()->json(['status' => 200, 'message' => 'Already earned share tokens today.']);
        }

        $ok = $this->tokenService->earn(
            $memberId,
            TokenService::AMOUNT_SHARE_RESULTS,
            TokenService::EARN_SHARE_RESULTS,
            'member',
            $memberId,
            'Shared results'
        );

        return response()->json([
            'status'  => $ok ? 200 : 500,
            'message' => $ok ? 'You earned ' . TokenService::AMOUNT_SHARE_RESULTS . ' credits for sharing!' : 'Error recording share.',
            'balance' => $this->tokenService->getBalance($memberId),
        ]);
    }

    // -------------------------------------------------------
    // GET balance (AJAX helper)
    // -------------------------------------------------------
    public function balance()
    {
        $memberId = (int) $this->_current_member['id'];
        return response()->json(['balance' => $this->tokenService->getBalance($memberId)]);
    }
}
