<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Account_Logout extends WebController {
    
    
    public function index() {
        $this->_member_model->doLogout($this->getSession('member_access_token'));
        $this->delSession('member_access_token');
        $this->delSessionPrefix('web');
        $this->delMyCookie('member_access_token');
        $this->doRedirect($this->toURL('home'));
    }
}