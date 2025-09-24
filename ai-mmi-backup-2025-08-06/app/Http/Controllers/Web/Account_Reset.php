<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Account_Reset extends WebController {
    
    public function __construct($data) {
        parent::__construct($data);
        if(!empty($this->_current_member)) {
            $this->doRedirect($this->toURL('home'));
        }
    }
    
    public function index() {
        // post event
        $this->pageAction(function() {
            if($this->_member_model->resetPassword($this->getParamValue('reset_token'), $this->postParamValue('password'), $this->postParamValue('repeat_password'))) {
                $this->pageResult([
                    'status'    =>  $this->_member_model->getResultCode(),
                    'message'   =>  $this->_page_lang['success_reset_password'],
                    'url'       =>  $this->toURL('account_login')
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