<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

class Privilege extends AdminController {
    
    public function __construct($data) {
        parent::__construct($data);
        
        // load model
        $this->_user_model = $this->loadModel('user');
    }

    public function index() {
        $this->doRedirect($this->toURL('privilege/user'));
    }

    public function user($action = '', $id = 0) {
        // post
        $this->pageAction(function() {
            switch(strtolower($this->postParamValue('page_action'))) {
                case 'save':
                    $result = $this->_user_model->doSave(
                    [
                        'role_id'           =>  $this->postParamValue('user_role', 0),
                        'name'              =>  $this->postParamValue('user_name'),
                        'email'             =>  $this->postParamValue('user_email'),
                        'password'          =>  $this->postParamValue('user_password'),
                        'repeat_password'   =>  $this->postParamValue('user_repeat_password'),
                        'status'            =>  $this->postParamValue('user_status', 1)
                    ], $this->postParamValue('user_id', 0));
                    $this->pageResult(
                    [
                        'status'    =>  $this->_user_model->getResultCode(),
                        'message'   =>  $this->_user_model->getResultMessage(),
                        'url'       =>  ((!empty($result))?$this->toURL('privilege/user'):'')
                    ], ((!empty($result))?true:false));
                    break;
                case 'delete':
                    $result = $this->_user_model->doDelete($this->postParamValue('id', 0));
                    $this->pageResult(
                    [
                        'status'    =>  $this->_user_model->getResultCode(),
                        'message'   =>  $this->_user_model->getResultMessage()
                    ], ((!empty($result))?true:false));
                    break;
            }
        });

        // page data
        if(!empty($action)) {
            if(in_array($action, ['add', 'edit', 'details'])) {
                // check role if need 
                $privilege_user = ((int)$id > 1)?$this->_user_model->getByID($id):false;
                if($action == 'edit' && empty($privilege_user)) {
                    $this->doRedirect($this->toURL('privilege'));
                }

                // set nav
                $this->pageNavigator($this->_page_lang['privilege'], $this->toURL('privilege'));
                $this->pageNavigator($this->_page_lang['privilege_user'], $this->toURL('privilege/user'));
                if(!empty($action) && in_array($action, ['add', 'edit', 'details'])) {
                    $this->pageNavigator($this->_page_lang[strtolower($action)]);
                }
  
                // load view
                return $this->pageOptions(
                [
                    'role'  => $this->optionsToArray($this->_user_model->getRoleAll(['sorting' => 'name_asc'], false), 'id', 'name')
                ])->pageData(
                [
                    'privilege_user'    =>  $privilege_user
                ])->pageView('privilege.user_form');
            }
            else {
                $this->doRedirect($this->toURL('privilege/user'));
            }
        }
        else {
            // set nav
            $this->pageNavigator($this->_page_lang['privilege'], $this->toURL('privilege'));
            $this->pageNavigator($this->_page_lang['privilege_user'], $this->toURL('privilege/user'));

            // load view
            return $this->templateListView(
            [
                'can_disabled'          =>  false,
                'can_seq'               =>  false,
                'columns'               => 
                [
                    [
                        'name'          =>  'role_id',            
                        'alias'         =>  $this->_page_lang['privilege_role'],
                        'options'       =>  $this->optionsToArray($this->_user_model->getRoleAll(['sorting' => 'name_asc'], false), 'id', 'name'),
                        'style'         =>  'width:120px;'
                    ],
                    [
                        'name'          =>  'name',            
                        'alias'         =>  $this->_page_lang['user_name']
                    ],
                    [
                        'name'          =>  'email',            
                        'alias'         =>  $this->_page_lang['user_email']
                    ],
                    [
                        'name'          =>  'status',            
                        'alias'         =>  $this->_page_lang['status'],
                        'style'         =>  'width:70px;text-align:center;',
                        'options'       =>  
                        [
                            1           =>  '<i class="fa fa-check-circle" style="font-size:18px;color:mediumaquamarine"></i>',
                            4           =>  '<i class="fa fa-ban" style="font-size:18px;color:lightpink"></i>',
                        ]
                    ]
                ]
            ], $this->_user_model->getAll(['keywords' => $this->getParamValue('keywords'),'sorting' => 'name_asc']));
        }
    }
      
    public function role($action = '', $id = 0) {
        // post
        $this->pageAction(function() {
            switch(strtolower($this->postParamValue('page_action'))) {
                case 'save':
                    $result = $this->_user_model->doRoleSave(
                    [
                        'name'      =>  $this->postParamValue('role_name'),
                        'allowed'   =>  $this->postParamValue('role_allowed')
                    ], $this->postParamValue('role_id', 0));
                    $this->pageResult(
                    [
                        'status'    =>  $this->_user_model->getResultCode(),
                        'message'   =>  $this->_user_model->getResultMessage(),
                        'url'       =>  (!empty($this->getSession('page_return_link')))?$this->getSession('page_return_link'):$this->toURL('privilege/role')
                    ], ((!empty($result))?true:false));
                    break;
                case 'delete':
                    $result = $this->_user_model->doRoleDelete($this->postParamValue('id', 0));
                    $this->pageResult(
                    [
                        'status'    =>  $this->_user_model->getResultCode(),
                        'message'   =>  $this->_user_model->getResultMessage()
                    ], ((!empty($result))?true:false));
                    break;
            }
        });

        // page data
        if(!empty($action)) {
            if(in_array($action, ['add', 'edit', 'details'])) {
                // check role if need 
                $privilege_role = ((int)$id > 0)?$this->_user_model->getRoleByID($id):false;
                if($action == 'edit' && empty($privilege_role)) {
                    $this->doRedirect($this->toURL('privilege'));
                }
                
                // set nav
                $this->pageNavigator($this->_page_lang['privilege'], $this->toURL('privilege'));
                $this->pageNavigator($this->_page_lang['privilege_role'], $this->toURL('privilege/role'));
                if(!empty($action) && in_array($action, ['add', 'edit', 'details'])) {
                    $this->pageNavigator($this->_page_lang[strtolower($action)]);
                }
  
                // load view
                return $this->pageData(
                [
                    'privilege_role'    =>  $privilege_role
                ])->pageView('privilege.role_form');
            }
            else {
                $this->doRedirect($this->toURL('privilege/role'));
            }
        }
        else {
            // set nav
            $this->pageNavigator($this->_page_lang['privilege'], $this->toURL('privilege'));
            $this->pageNavigator($this->_page_lang['privilege_role'], $this->toURL('privilege/role'));
            
            // load view
            return $this->templateListView(
            [
                'can_disabled'          =>  false,
                'can_seq'               =>  false,
                'columns'               => 
                [
                    [
                        'name'          =>  'name',            
                        'alias'         =>  $this->_page_lang['name']
                    ]
                ]
            ], $this->_user_model->getRoleAll(['keywords' => $this->getParamValue('keywords'),'sorting' => 'name_asc']));
        }
    }
}
