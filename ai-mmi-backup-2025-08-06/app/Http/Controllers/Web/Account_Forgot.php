<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Account_Forgot extends WebController {
    
    public function __construct($data) {
        parent::__construct($data);
        if(!empty($this->_current_member)) {
            $this->doRedirect($this->toURL('home'));
        }
    }
    
    public function index() {
        // post
        $this->pageAction(function() {
           if($token = $this->_member_model->forgotPassword($this->postParamValue('email'))) {
                // send email
                $link = $this->toURL('account_reset').'?token='.$token;
                $body = '<p>It seems like you forgot your password. If this is true, click the link below to reset your password.</p>';
                $body.= '<p>Reset my password: <a href="'.$link.'" target="_blank" style="color:blue;text-decoration:underline;">'.$link.'</a></p>';
                $body.= '<p>If you did not forget your password, please disregard this email.</p>';

                $body.= '<p>&nbsp;</p>';

                $body.= '<p>您似乎忘記了密碼。 如果確實如此，請點擊下面的鏈接重置您的密碼。</p>';
                $body.= '<p>重置我的密碼：<a href="'.$link.'" target="_blank" style="color:blue;text-decoration:underline;">'.$link.' </a></p>';
                $body.= '<p>如果您沒有忘記密碼，請忽略此電子郵件。</p>';

                $this->sendEmail($this->postParamValue('email'), $this->_page_lang['forgot_password'], $body);
                //$this->sendEmail('ken@at-creative.com', $this->_page_lang['forgot_password'], $body);

                $this->pageResult([
                    'status'    =>  $this->_member_model->getResultCode(),
                    'message'   =>  $this->_page_lang['success_send_reset']
                ]);
            }
            else {
                $this->pageResult(
                [
                    'status'    =>  $this->_member_model->getResultCode(),
                    'message'   =>  $this->_member_model->getResultMessage()
                ]);
            }
        });
        
        
        return $this->pageView();
    }
}