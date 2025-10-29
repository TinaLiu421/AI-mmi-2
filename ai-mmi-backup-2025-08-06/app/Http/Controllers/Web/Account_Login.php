<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Laravel\Socialite\Facades\Socialite;

class Account_Login extends WebController {

    public function __construct($data) {
        parent::__construct($data);

        if(!empty($this->_current_member)) {
            $this->doRedirect($this->toURL('home'));
        }
    }

    public function index() {
        // post
        $this->pageAction(function() {
            if($token = $this->_member_model->doLogin($this->postParamValue('email'), $this->postParamValue('password'))) {
                $this->setSession(['member_access_token' => $token]);
                $this->setMyCookie('member_access_token', $token);
                $this->pageResult([
                    'status'    =>  $this->_member_model->getResultCode(),
                    'url'       =>  $this->toURL('home')
                ]);
            }
            else {
                $this->pageResult([
                    'status'    =>  $this->_member_model->getResultCode(),
                    'message'   =>  $this->_member_model->getResultMessage()
                ]);
            }
        });


        return $this->pageView();
    }

    // Google OAuth
    public function google() {
        return Socialite::driver('google')->redirect();
    }

    public function google_callback() {
        try {
            $googleUser = Socialite::driver('google')->user();
            $this->handleSocialLogin($googleUser, 'google');
        } catch (\Exception $e) {
            \Log::error('Google OAuth failed: ' . $e->getMessage());
            $this->doRedirect($this->toURL('account_login'));
        }
    }

    // Facebook OAuth
    public function facebook() {
        return Socialite::driver('facebook')->redirect();
    }

    public function facebook_callback() {
        try {
            $facebookUser = Socialite::driver('facebook')->user();
            $this->handleSocialLogin($facebookUser, 'facebook');
        } catch (\Exception $e) {
            \Log::error('Facebook login error: ' . $e->getMessage());
            $this->doRedirect($this->toURL('account_login'));
        }
    }

    // Handle social login
    private function handleSocialLogin($socialUser, $provider) {
        // Download avatar if available
        $avatarFilename = null;
        if ($socialUser->getAvatar()) {
            $avatarFilename = $this->downloadAvatar($socialUser->getAvatar(), $provider);
        }

        // Find existing member by email
        $member = \DB::table('member')
            ->where('email', $socialUser->getEmail())
            ->first();

        if (!$member) {
            // Create new member as individual (type 1)
            // They can upgrade to service provider later if needed
            $memberId = \DB::table('member')->insertGetId([
                'email' => $socialUser->getEmail(),
                'alias_name' => $socialUser->getName(),
                'full_name' => $socialUser->getName(),
                'password' => bcrypt(uniqid()),
                'type' => 1,
                'status' => 1,
                'verified' => 1,
                'avatar' => $avatarFilename,
                'social_provider' => $provider,
                'social_id' => $socialUser->getId(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            // Existing member - log them in
            $memberId = $member->id;

            // Update social info and avatar if not set
            $updateData = [];

            if (empty($member->social_provider)) {
                $updateData['social_provider'] = $provider;
                $updateData['social_id'] = $socialUser->getId();
            }

            // Update avatar if they don't have one and we downloaded one
            if (empty($member->avatar) && $avatarFilename) {
                $updateData['avatar'] = $avatarFilename;
            }

            if (!empty($updateData)) {
                \DB::table('member')->where('id', $memberId)->update($updateData);
            }
        }

        // Create login token
        $token = md5(uniqid(rand()));
        \DB::table('member_token')->insert([
            'type' => 1,
            'member_id' => $memberId,
            'value' => $token,
            'created_by' => $memberId
        ]);

        // Set session and redirect
        $this->setSession(['member_access_token' => $token]);
        $this->setMyCookie('member_access_token', $token);
        $this->doRedirect($this->toURL('home'));
    }

    // Download and save avatar from social provider
    private function downloadAvatar($avatarUrl, $provider) {
        try {
            $uploadPath = public_path('upload/member_avatar');

            // Create directory if it doesn't exist
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Generate unique filename
            $filename = md5($provider . '_' . time() . '_' . uniqid()) . '.jpg';
            $filePath = $uploadPath . '/' . $filename;

            // Download and save the image
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
}