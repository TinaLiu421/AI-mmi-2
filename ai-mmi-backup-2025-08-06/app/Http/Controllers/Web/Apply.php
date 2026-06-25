<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Http;

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

        // Base list of approved institutions (always shown)
        $baseList = [
            'Australian College of Tourism and Information Technology',
            'MBI-AU',
            'Rosehill College',
        ];

        // Auto-add any newly registered institutions that have a proper institute_name
        $dbRows = \DB::table('member as m')
            ->join('institution_profiles as ip', 'ip.member_id', '=', 'm.id')
            ->where('m.type', 3)
            ->where('m.status', 1)
            ->whereNotNull('ip.institute_name')
            ->where('ip.institute_name', '!=', '')
            ->orderByRaw("app_ip.institute_name ASC")
            ->pluck('ip.institute_name')
            ->map(fn($n) => trim($n))
            ->filter()
            ->all();

        $institutions = array_values(array_unique(array_merge($baseList, $dbRows)));
        sort($institutions);

        $data = [
            'institutions'        => $institutions,
            'prefill_institution' => request()->query('institution', ''),
            'prefill_course'      => request()->query('prefill_course', request()->query('course', '')),
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
