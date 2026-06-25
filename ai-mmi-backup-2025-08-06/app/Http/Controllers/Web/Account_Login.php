<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Laravel\Socialite\Facades\Socialite;
use App\Rules\RecaptchaRule;
use App\Services\TokenService;

class Account_Login extends WebController {

    public function __construct(array $data = []) {
        parent::__construct($data);

        if (!empty($this->_current_member)) {
            $this->doRedirect($this->toURL('home'));
        }
    }

    public function index() {
        $this->pageAction(function() {
            if ($token = $this->_member_model->doLogin($this->postParamValue('email'), $this->postParamValue('password'))) {
                $this->setSession(['member_access_token' => $token]);
                $this->setMyCookie('member_access_token', $token);
                $this->pageResult([
                    'status' => $this->_member_model->getResultCode(),
                    'url'    => $this->toURL('home')
                ]);
            } else {
                $this->pageResult([
                    'status'  => $this->_member_model->getResultCode(),
                    'message' => $this->_member_model->getResultMessage()
                ]);
            }
        });

        return $this->pageView();
    }

    public function localWealthskeyAgentLogin() {
        if (!app()->environment('local')) {
            abort(404);
        }

        $member = \DB::table('member')
            ->where(function ($q) {
                $q->where('email', 'admin@wealthskey.com')
                  ->orWhere('alias_name', 'Wealthskey Migration');
            })
            ->where('status', '>', 0)
            ->first();

        if (!$member || empty($member->id)) {
            abort(404, 'Wealthskey agent account not found.');
        }

        $token = md5(uniqid(rand(), true));
        \DB::table('member_token')->insert([
            'type'       => 1,
            'member_id'  => (int)$member->id,
            'value'      => $token,
            'created_by' => (int)$member->id,
        ]);

        $this->setSession(['member_access_token' => $token]);
        $this->setMyCookie('member_access_token', $token);

        return redirect('/en/agent_chat/chat');
    }

    /* =======================
     * Google OAuth（单入口 + 查询参数分流）
     * ======================= */

    // 入口：/account_login/google?role=individual|provider
    public function google() {
        // role -> intendedType（与你member表保持一致：1=Individual, 3=Service Provider）
        $role = request()->query('role', 'individual');
        $type = ($role === 'provider') ? 3 : 1;

        // 写入 Session，回调时读取
        session(['oauth_type' => $type]);

        // 避免自动选择旧账号
        $driver = Socialite::driver('google')->with(['prompt' => 'select_account']);
        if (app()->environment('local')) {
            $driver->redirectUrl(url('/account_login/google_callback'));
        }
        return $driver->redirect();
    }

    public function google_callback() {
        try {
            // 生产常见反代环境下更稳
            $driver = Socialite::driver('google')->stateless();
            if (app()->environment('local')) {
                $driver->redirectUrl(url('/account_login/google_callback'));
            }
            $googleUser = $driver->user();

            $intendedType = (int) session('oauth_type', 1);  // 1 or 3
            $this->clearOauthSession();

            $this->handleSocialLogin($googleUser, 'google', $intendedType);
        } catch (\Exception $e) {
            \Log::error('Google OAuth failed: ' . $e->getMessage());
            $this->clearOauthSession();
            $this->doRedirect($this->toURL('account_login'));
        }
    }

    /* =======================
     * Facebook OAuth（原逻辑保留）
     * ======================= */

    public function facebook() {
        // individual | provider
        $role = request()->query('role', 'individual');
        $type = ($role === 'provider') ? 3 : 1;   // 1=Individual, 3=Service Provider
        session(['oauth_type' => $type]);

        return \Laravel\Socialite\Facades\Socialite::driver('facebook')->redirect();
    }

    public function facebook_callback() {
        try {
            $facebookUser = \Laravel\Socialite\Facades\Socialite::driver('facebook')->stateless()->user();
            $intendedType = (int) session('oauth_type', 1);
            $this->clearOauthSession();

            $this->handleSocialLogin($facebookUser, 'facebook', $intendedType);
        } catch (\Exception $e) {
            \Log::error('Facebook login error: ' . $e->getMessage());
            $this->clearOauthSession();
            $this->doRedirect($this->toURL('account_login'));
        }
    }

    /* =======================
     * 核心：第三方登录落库
     * ======================= */
    private function handleSocialLogin($socialUser, $provider, int $intendedType = 1)
    {
        // 头像下载逻辑不变
        $avatarFilename = null;
        if ($socialUser->getAvatar()) {
            $avatarFilename = $this->downloadAvatar($socialUser->getAvatar(), $provider);
        }

        // 【核心修复】
        // 优先用 social_id + provider 找
        $member = \DB::table('member')
            ->where('social_provider', $provider)
            ->where('social_id', $socialUser->getId())
            ->first();

        // 如果找不到，再尝试用 email 匹配（兼容老账号）
        if (!$member && $socialUser->getEmail()) {
            $member = \DB::table('member')
                ->where('email', $socialUser->getEmail())
                ->first();
        }

        // 若依然没有，则创建新用户
        $isNewMember = false;
        if (!$member) {
            $isNewMember = true;
            $memberId = \DB::table('member')->insertGetId([
                'email'           => $socialUser->getEmail() ?? 'N/A',
                'alias_name'      => $socialUser->getName() ?? 'N/A',
                'full_name'       => $socialUser->getName() ?? 'N/A',
                'password'        => bcrypt(uniqid()),
                'method'          => 2, // social
                'type'            => in_array($intendedType, [1, 3]) ? $intendedType : 1,
                'status'          => 1,
                'verified'        => 1,
                'avatar'          => $avatarFilename,
                'social_provider' => $provider,
                'social_id'       => $socialUser->getId(),
                'created_at'      => now(),
            ]);
        } else {
            // 老用户更新
            $memberId = $member->id;
            $updateData = [];

            // 如果首次没有存 provider/social_id，现在补上
            if (empty($member->social_provider)) {
                $updateData['social_provider'] = $provider;
            }
            if (empty($member->social_id)) {
                $updateData['social_id'] = $socialUser->getId();
            }

            if (empty($member->avatar) && $avatarFilename) {
                $updateData['avatar'] = $avatarFilename;
            }

            if (!empty($updateData)) {
                \DB::table('member')->where('id', $memberId)->update($updateData);
            }
        }

        // 填默认字段（你已有）
        $this->fillDefaultProfileIfEmpty($memberId, $intendedType ?? 1);

        // Token rewards
        $tokenService = new TokenService();
        if ($isNewMember) {
            $tokenService->earn((int)$memberId, TokenService::AMOUNT_SIGNUP, TokenService::EARN_SIGNUP, 'member', (int)$memberId, 'Sign up via ' . $provider);
            $tokenService->generateReferralCode((int)$memberId);
        }

        // 登录 token 不变
        $token = md5(uniqid(rand()));
        \DB::table('member_token')->insert([
            'type'       => 1,
            'member_id'  => $memberId,
            'value'      => $token,
            'created_by' => $memberId,
        ]);

        $this->setSession(['member_access_token' => $token]);
        $this->setMyCookie('member_access_token', $token);

        $this->doRedirect($this->toURL('account/profile'));
    }


    private function clearOauthSession(): void {
        try { session()->forget('oauth_type'); } catch (\Throwable $e) {}
    }

    private function downloadAvatar($avatarUrl, $provider) {
        try {
            $uploadPath = public_path('upload/member_avatar');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $filename = md5($provider . '_' . time() . '_' . uniqid()) . '.jpg';
            $filePath = $uploadPath . '/' . $filename;
            $imageContent = file_get_contents($avatarUrl);
            if ($imageContent !== false) {
                file_put_contents($filePath, $imageContent);
                return $filename;
            }
        } catch (\Exception $e) {
            \Log::error('Avatar download failed: ' . $e->getMessage());
        }
        return null;
    }

    private function fillDefaultProfileIfEmpty(int $memberId, int $type): void
    {
        // —— 统一给 member 表做「缺省值补齐」（两类用户都需要，避免 About 报错）——
        $memberDefaults = [
            'alias_name'            => 'N/A',
            'full_name'             => 'N/A',
            'first_name'            => 'N/A',
            'last_name'             => 'N/A',
            'email'                 => 'N/A',
            'telephone_code'        => 'N/A',
            'telephone_num'         => 'N/A',
            'migration_destination' => 0,
            'interested_visa'       => 0,
            // 你的系统里 interested_topic 有时是数组，有时是字符串；
            // 这里用 json 空数组，前端读取时注意兼容。
            'interested_topic'      => json_encode([]),
            'remark'                => 'N/A',
            'verified'              => 0,
            'status'                => 1,
        ];

        $member = \DB::table('member')->where('id', $memberId)->first();
        if ($member) {
            $update = [];
            foreach ($memberDefaults as $key => $value) {
                if (!property_exists($member, $key) || $member->$key === null || $member->$key === '') {
                    $update[$key] = $value;
                }
            }
            if (!empty($update)) {
                \DB::table('member')->where('id', $memberId)->update($update);
            }
        }

        // —— 只有 Service Provider(type=3) 才需要 member_details —— 
        if ((int)$type !== 3) {
            // 如果你希望“清理误写”的 details，可以在这里做可选清理（默认不动）：
            // \DB::table('member_details')->where('member_id', $memberId)->delete();
            return;
        }

        // SP 详情的缺省值
        $detailsDefaults = [
            'company_type'       => 0,
            'company_name'       => 'N/A',
            'company_website'    => 'N/A',
            'company_address'    => 'N/A',
            'services'           => 'N/A',
            'services_country'   => json_encode([]),
            'registered_agent'   => 0,
            'registered_lawfirm' => 0,
            // 视图里有 logo 字段时，避免 undefined index
            'logo'               => '',
        ];

        $details = \DB::table('member_details')->where('member_id', $memberId)->first();
        if ($details) {
            $update = [];
            foreach ($detailsDefaults as $key => $value) {
                if (!property_exists($details, $key) || $details->$key === null || $details->$key === '') {
                    $update[$key] = $value;
                }
            }
            if (!empty($update)) {
                \DB::table('member_details')->where('member_id', $memberId)->update($update);
            }
        } else {
            $detailsDefaults['member_id'] = $memberId;
                \DB::table('member_details')->insert($detailsDefaults);
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'g-recaptcha-response' => ['required', new RecaptchaRule()],
        ]);
    }
}