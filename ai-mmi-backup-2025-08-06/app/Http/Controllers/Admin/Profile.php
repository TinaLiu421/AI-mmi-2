<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

class Profile extends AdminController {
    
    public function __construct($data) {
        parent::__construct($data);
        
        // load model
        $this->_user_model = $this->loadModel('user');
    }
    
    public function index() {
        // post
        $this->pageAction(function() {
            if($this->_user_model->doSave(
            [
                'name'              =>  $this->postParamValue('user_name'),
                'email'             =>  $this->postParamValue('user_email'),
                'password'          =>  $this->postParamValue('user_password'),
                'repeat_password'   =>  $this->postParamValue('user_repeat_password')
            ], $this->_current_user['id'])) {
                $this->pageResult(
                [
                    'status'    =>  $this->_user_model->getResultCode(),
                    'message'   =>  $this->_user_model->getResultMessage(),
                    'url'       =>  $this->toURL('profile')
                ], true);
            }
            else {
                $this->pageResult([
                    'status'    =>  $this->_user_model->getResultCode(),
                    'message'   =>  $this->_user_model->getResultMessage()
                ]);
            }
        });
        
        // set nav
        $this->pageNavigator($this->_page_lang['profile'], $this->toURL('profile'));
        
        // load view
        return $this->pageData(
        [
            'privilege_user'  =>  $this->_user_model->getByID($this->_current_user['id'])
        ])->pageView('privilege.user_form');
    }
}