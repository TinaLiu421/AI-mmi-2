<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

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
}