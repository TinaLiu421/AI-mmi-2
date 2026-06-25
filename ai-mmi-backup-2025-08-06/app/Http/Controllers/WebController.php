<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class WebController extends CoreController {
    protected $_member_model = null;
    protected $_setting_model = null;
    protected $_visa_countries = [];

    public function __construct(array $data = []) {
        parent::__construct($data);
        // Only initialize when mapping data is present. During container
        // instantiation (artisan commands, route:list, etc.) the parent
        // constructor may have been called with empty mapping data.
        if (!empty($this->_mapping_data) && is_array($this->_mapping_data)) {
            $this->initialize();
        }
      
    }

    /**
     * Verify Google reCAPTCHA v2 token server-side
     * @param string $token
     * @return bool
     */
    protected function verifyRecaptcha($token)
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
            if (isset($data['success'])) {
                return (bool) $data['success'];
            }
            return false;
        } catch (\Exception $e) {
            \Log::error('reCAPTCHA verification error: ' . $e->getMessage());
            return false;
        }
    }
    
    protected function setMyCookie($name, $value)
    {
        
        return setcookie($name, $value, time() + (86400 * 30), '/'); // 86400 = 1 day
    }
    
    protected function getMyCookie($name)
    {
        return (isset($_COOKIE[$name]))?$_COOKIE[$name]:'';
    }
    
    protected function delMyCookie($name) {
        return setcookie($name, '', time() - 3600, '/');
    }

    // send email
    protected function sendEmail($email_address = '', $subject = '', $content = '') {
        if(is_string($email_address)) {
            $email_address = [$email_address];
        }
        foreach ($email_address as $key => $to_email) {
            $email_address[$key] = $this->toPlainText($to_email);
        }
        $email_address = array_unique(array_filter($email_address));

        if(!empty($email_address)) {
            $html = '<div style="font-family:Arial,微軟正黑體,PMingLiU,新細明體;padding:8px;">';
                $html.= '<div style="padding:10px;border:8px solid #002065;border-radius:4px;box-shadow:0px 0px 8px #222;">';
                $html.= $content;
                $html.= '</div>';
            $html.= '</div>';

            $allSent = true;
            foreach ($email_address as $to_email) {
                $to_email = $this->toPlainText($to_email);
                if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                try {
                    \Illuminate\Support\Facades\Mail::html($html, function ($message) use ($to_email, $subject) {
                        $message->to($to_email)->subject($subject);
                    });
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('sendEmail failed', ['to' => $to_email, 'subject' => $subject, 'error' => $e->getMessage()]);
                    $allSent = false;
                }
            }
            return $allSent;
        }
        return false;
    }
    
    // init
    private function initialize() {
        $this->_setting_model = $this->loadModel('setting');
        $this->_member_model = $this->loadModel('member');

        $adminEmails = ['admin@wealthskey.com', 'info@ai-mmi.com'];

        
        if(!empty($this->getSession('member_access_token'))) {
            $this->_current_member = $this->_member_model->getByToken($this->getSession('member_access_token'));
        }
        
        if(empty($this->_current_member) && !empty($this->getMyCookie('member_access_token'))){
            $this->_current_member = $this->_member_model->getByToken($this->getMyCookie('member_access_token'));
        }

        // Full-account admin proxy mode: admin can act as selected member across all account pages.
        $this->pageData([
            'admin_proxy_mode'   => false,
            'admin_proxy_target' => null,
            'admin_real_member'  => null,
        ]);

        if (!empty($this->_current_member)) {
            $currentEmail = mb_strtolower(trim((string)($this->_current_member['email'] ?? '')), 'UTF-8');
            $isAdmin = in_array($currentEmail, $adminEmails, true);
            $proxyMemberId = (int)$this->getSession('admin_proxy_member_id_full');

            if ($isAdmin && $proxyMemberId > 0) {
                // Use raw table query so unclaimed (inactive/no-email) accounts can still be proxied by admin.
                $proxyMemberObj = DB::table('member')->where('id', $proxyMemberId)->first();
                $proxyMember = $proxyMemberObj ? (array)$proxyMemberObj : [];
                if (!empty($proxyMember)) {
                    $realAdmin = $this->_current_member;
                    $this->_current_member = $proxyMember;
                    $this->_current_member['__admin_proxy_mode'] = 1;
                    $this->_current_member['__admin_real_member_id'] = (int)($realAdmin['id'] ?? 0);
                    $this->_current_member['__admin_real_email'] = (string)($realAdmin['email'] ?? '');
                    $this->_current_member['status'] = 1;
                    $this->_current_member['verified'] = 1;
                    $this->_current_member['spotlight_manager'] = 1;
                    $this->_current_member['expiration_date_account'] = '9999-12-31';
                    $this->_current_member['expiration_date_visa_submission_ai'] = '9999-12-31';
                    $this->_current_member['expiration_date_visa_submission_human'] = '9999-12-31';

                    $this->pageData([
                        'admin_proxy_mode'   => true,
                        'admin_proxy_target' => $proxyMember,
                        'admin_real_member'  => $realAdmin,
                    ]);
                }
            }
        }

        // Accounts that never expire as service providers
        $never_expire_emails = ['info@mbi-au.com'];
        if (!empty($this->_current_member['email']) && in_array($this->_current_member['email'], $never_expire_emails)) {
            $this->_current_member['expiration_date_account']              = '9999-12-31';
            $this->_current_member['expiration_date_visa_submission_ai']   = '9999-12-31';
            $this->_current_member['expiration_date_visa_submission_human'] = '9999-12-31';
        }

        $list_countries = $this->loadModel('pages', ['table' => 'country'])->getAll($this->_current_lang_index, 
        [
            'media_files'   => 
            [
                ['type' => 'page', 'category' => 'flag']
            ]
        ], false);
        if(!empty($list_countries)) {
            foreach ($list_countries as $country_key => $country) {
                $this->_visa_countries[$country_key]['id'] = $country['id'];
                $this->_visa_countries[$country_key]['title'] = $country['title'];
                $this->_visa_countries[$country_key]['url'] = $this->toURL('visa_options/?country='.$country['id']);
                if(!empty($country['media_files']['flag'])) { 
                    $this->_visa_countries[$country_key]['photo_flag'] = $this->generateImage(reset($country['media_files']['flag']), 300, 150);
                }
                else {
                    $this->_visa_countries[$country_key]['photo_flag'] = $this->generateImage(null, 300, 150, true);
                }
            }
        }
        $this->pageData(['visa_countries' => $this->_visa_countries]);

        // —— 访客ID（持久）——
        if (empty($this->getMyCookie('guest_id'))) {
            $gid = (string) \Str::uuid();
            $this->setMyCookie('guest_id', $gid);
        } else {
            $gid = $this->getMyCookie('guest_id');
        }

        // —— 访客聊天计数（会话级）——
        if (empty($this->getSession('guest_chat_count'))) {
            $this->setSession(['guest_chat_count' => 0]);
        }

    }
    
    function getYoutubeEmbedUrl($url) {
        $shortUrlRegex = '/youtu.be\/([a-zA-Z0-9_-]+)\??/i';
        $longUrlRegex = '/youtube.com\/((?:embed)|(?:watch)|(?:shorts))((?:\?v\=)|(?:\/))([a-zA-Z0-9_-]+)/i';

        $youtube_id = '';
        if (preg_match($longUrlRegex, $url, $matches)) {
            $youtube_id = $matches[count($matches) - 1];
        }
        else if (preg_match($shortUrlRegex, $url, $matches)) {
            $youtube_id = $matches[count($matches) - 1];
        }
        return (!empty($youtube_id))?('https://www.youtube.com/embed/'.$youtube_id):'';
    }
}
