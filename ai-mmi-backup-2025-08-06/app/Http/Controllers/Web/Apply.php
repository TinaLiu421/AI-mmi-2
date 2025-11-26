<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Apply extends WebController
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
            'title'       => $this->_page_lang['apply'] ?? 'apply',
            'description' => '',
            'image'       => ''
        ]);

        $data = [
            'pricing_table_id' => env('STRIPE_PRICING_TABLE_ID_2'),
            'stripe_pk'        => env('STRIPE_KEY'),
        ];

        return $this->pageData($data)->pageView();
    }
    
    /**
     * Verify reCAPTCHA token server-side
     * @param string $token The reCAPTCHA response token
     * @return bool True if verification passes, false otherwise
     */
    public function verifyRecaptcha($token)
    {
        $secret = env('RECAPTCHA_SECRET');
        
        if (empty($secret) || empty($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => request()->ip(),
            ]);

            $data = $response->json();
            
            // For v2 checkbox, just check success
            if (isset($data['success'])) {
                return (bool) $data['success'];
            }

            return false;
        } catch (\Exception $e) {
            \Log::error('reCAPTCHA verification error: ' . $e->getMessage());
            return false;
        }
    } 
}
