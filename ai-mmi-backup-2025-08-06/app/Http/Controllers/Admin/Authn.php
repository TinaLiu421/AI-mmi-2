<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

class Authn extends AdminController {
    
    public function __construct($data) {
        parent::__construct($data);
        
        // load model
        $this->_user_model = $this->loadModel('user');
    }
    
    public function index() {
        // post
        $this->pageAction(function() {
            if($token = $this->_user_model->doLogin($this->postParamValue('user_id'), $this->postParamValue('user_password'))) {
                $this->setSession(['admin_access_token' => $token]);
                $this->pageResult([
                    'status'    =>  $this->_user_model->getResultCode(),
                    'url'       =>  $this->toURL('home')
                ]);
            }
            else {
                $this->pageResult([
                    'status'    =>  $this->_user_model->getResultCode(),
                    'message'   =>  $this->_user_model->getResultMessage()
                ]);
            }
        });
        
        // load view
        return $this->pageData(['myip' => ((!empty($this->getParamValue('show_ip_address', '')))?$this->getCurrentIP():'')])->pageView('authn.login', false);
    }
    
    public function forgot() {
        // post
        $this->pageAction(function() {
           if($token = $this->_user_model->forgotPassword($this->postParamValue('user_email'))) {
                // send email
                $link = $this->toURL('authn/reset').'?token='.$token;
                $body = '<p>It seems like you forgot your password. If this is true, click the link below to reset your password.</p>';
                $body.= '<p>Reset my password: <a href="'.$link.'" target="_blank" style="color:blue;text-decoration:underline;">'.$link.'</a></p>';
                $body.= '<p>If you did not forget your password, please disregard this email.</p>';

                $body.= '<p>&nbsp;</p>';

                $body.= '<p>您似乎忘記了密碼。 如果確實如此，請點擊下面的鏈接重置您的密碼。</p>';
                $body.= '<p>重置我的密碼：<a href="'.$link.'" target="_blank" style="color:blue;text-decoration:underline;">'.$link.' </a></p>';
                $body.= '<p>如果您沒有忘記密碼，請忽略此電子郵件。</p>';

                $this->sendEmail($this->postParamValue('user_email'), $this->_page_lang['forgot_password'], $body);

                $this->pageResult([
                    'status'    =>  $this->_user_model->getResultCode(),
                    'message'   =>  $this->_page_lang['success_send_reset']
                ]);
            }
            else {
                $this->pageResult(
                [
                    'status'    =>  $this->_user_model->getResultCode(),
                    'message'   =>  $this->_user_model->getResultMessage()
                ]);
            }
        });

        // load view
        return $this->pageView('authn.forgot', false);
    }
    
    public function reset() {
        // post event
        $this->pageAction(function() {
            if($this->_user_model->resetPassword($this->getParamValue('token'), $this->postParamValue('user_password'), $this->postParamValue('user_repeat_password'))) {
                $this->pageResult([
                    'status'    =>  $this->_user_model->getResultCode(),
                    'message'   =>  $this->_page_lang['success_reset_password']
                ]);
            }
            else {
                $this->pageResult(
                [
                    'status'    =>  $this->_user_model->getResultCode(),
                    'message'   =>  $this->_user_model->getResultMessage()
                ]);
            }
        });

        // load view
        return $this->pageView('authn.reset', false);
    }

    public function logout() {
        $this->_user_model->doLogout($this->getSession('admin_access_token'));
        $this->delSession('admin_access_token');
        $this->delSessionPrefix($this->_mapping_data['module']);
        $this->doRedirect($this->toURL('authn'));
    }
}
