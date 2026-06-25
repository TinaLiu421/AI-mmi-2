<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Handles the education agent account claim flow.
 * Route: GET/POST /claim_edu_account/{token}
 */
class Claim_Edu_Account extends WebController {

    /**
     * Show the claim form.
     * GET /claim_edu_account/{token}
     */
    public function index($token = '') {
        $token = preg_replace('/[^a-f0-9]/', '', $token);
        if (empty($token)) {
            return $this->pageData(['error' => 'Invalid claim link.'])->pageView('claim_edu_account');
        }

        $grant = DB::table('edu_agent_grants')->where('token', $token)->first();
        if (!$grant) {
            return $this->pageData(['error' => 'This claim link is invalid or does not exist.'])->pageView('claim_edu_account');
        }
        if ((int)$grant->status === 1) {
            return $this->pageData(['error' => 'This account has already been claimed. Please log in.'])->pageView('claim_edu_account');
        }

        return $this->pageData([
            'token'   => $token,
            'error'   => null,
            'success' => false,
        ])->pageView('claim_edu_account');
    }

    /**
     * Process the claim form submission.
     * POST /claim_edu_account/{token}
     * Body: email, password, password_confirm
     */
    public function submit($token = '') {
        $token = preg_replace('/[^a-f0-9]/', '', $token);
        if (empty($token)) {
            return response()->json(['status' => 400, 'message' => 'Invalid token'], 400);
        }

        $grant = DB::table('edu_agent_grants')->where('token', $token)->first();
        if (!$grant || (int)$grant->status !== 0) {
            return response()->json(['status' => 400, 'message' => 'This claim link is invalid or has already been used.'], 400);
        }

        $email   = trim((string)(request()->input('email', $this->_page_post_data['email'] ?? '')));
        $pass    = (string)request()->input('password', $this->_page_post_data['password'] ?? '');
        $confirm = (string)request()->input('password_confirm', $this->_page_post_data['password_confirm'] ?? '');

        // Validate email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['status' => 400, 'message' => 'A valid email address is required.'], 400);
        }

        // Check email not already in use
        $existing = DB::table('member')->where('email', $email)->first();
        if ($existing) {
            return response()->json(['status' => 400, 'message' => 'This email address is already registered. Please use a different email.'], 400);
        }

        // Validate password
        if (strlen($pass) < 8) {
            return response()->json(['status' => 400, 'message' => 'Password must be at least 8 characters.'], 400);
        }
        if ($pass !== $confirm) {
            return response()->json(['status' => 400, 'message' => 'Passwords do not match.'], 400);
        }

        DB::beginTransaction();
        try {
            // Activate the member account
            DB::table('member')
                ->where('id', $grant->member_id)
                ->update([
                    'email'      => $email,
                    'password'   => Hash::make($pass),
                    'status'     => 1,   // active
                    'verified'   => 1,
                    'updated_at' => now(),
                ]);

            // Mark grant as claimed
            DB::table('edu_agent_grants')
                ->where('id', $grant->id)
                ->update([
                    'status'     => 1,
                    'claimed_at' => now(),
                    'updated_at' => now(),
                ]);

            // Mark the institution profile as claimed
            DB::table('institution_profiles')
                ->where('member_id', $grant->member_id)
                ->whereNull('claimed_at')
                ->update([
                    'claimed_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'status'  => 200,
                'message' => 'Account activated successfully! You can now log in.',
                'redirect' => url('/en/account_login'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => 500, 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }
}
