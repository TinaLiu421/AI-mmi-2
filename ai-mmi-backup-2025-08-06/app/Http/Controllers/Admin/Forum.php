<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

class Forum extends AdminController {
    
    public function __construct($data) {
        parent::__construct($data);
        
        $this->pageIndex('member_area');
        
        // load model
        $this->_forum_model = $this->loadModel('forum');
    }

    public function index($action = '', $id = 0) {
        // post
        $this->pageAction(function() {
            switch(strtolower($this->postParamValue('page_action'))) {
                case 'delete':
                    $result = $this->_forum_model->doDelete($this->postParamValue('id', 0));
                    $this->pageResult(
                    [
                        'status'    =>  $this->_forum_model->getResultCode(),
                        'message'   =>  $this->_forum_model->getResultMessage()
                    ], ((!empty($result))?true:false));
                    break;
                case 'delete_sub':
                    $result = $this->_forum_model->doDeleteSub($this->postParamValue('id', 0));
                    $this->pageResult(
                    [
                        'status'    =>  $this->_forum_model->getResultCode(),
                        'message'   =>  $this->_forum_model->getResultMessage()
                    ], ((!empty($result))?true:false));
                    break;
            }
        });

        // page data
        $action = strtolower($action);
        if(!empty($action)) {
            if(in_array($action, ['add', 'edit', 'details'])) {
                // check role if need 
                $target_forum = $this->_forum_model->getByID($id);
                if(($action == 'edit' || $action == 'details') && empty($target_forum)) {
                    $this->doRedirect($this->toURL('forum'));
                }
                $list_forum_comment = $this->_forum_model->getAllComment($id);

                // set nav
                $this->pageNavigator($this->_page_lang['member_area']);
                $this->pageNavigator($this->_page_lang['forum'], $this->toURL('forum'));
                
                if(!empty($action) && in_array($action, ['add', 'edit', 'details'])) {
                    $this->pageNavigator($this->_page_lang[strtolower($action)]);
                }
  
                // load view
                $country_options = [];
                $list_countries = $this->loadModel('pages', ['table' => 'country'])->getAll($this->_current_lang_index, null, false);
                if(!empty($list_countries)) {
                    foreach ($list_countries as $country) {
                        $country_options[$country['id']] = $country['title'];
                    }
                }
                $country_options[-1] = $this->_page_lang['forum_others'];

                return $this->pageOptions(
                [
                    'country'               =>  $country_options
                ])->pageData(
                [
                    'target_forum'          =>  $target_forum,
                    'list_forum_comment'    =>  $list_forum_comment
                ])->pageView('member_area.forum_details');
            }
            else {
                $this->doRedirect($this->toURL('forum'));
            }
        }
        else {
            // set nav
            $this->pageNavigator($this->_page_lang['member_area']);
            $this->pageNavigator($this->_page_lang['forum'], $this->toURL('forum'));

            // load view
            $country_options = [];
            $list_countries = $this->loadModel('pages', ['table' => 'country'])->getAll($this->_current_lang_index, null, false);
            if(!empty($list_countries)) {
                foreach ($list_countries as $country) {
                    $country_options[$country['id']] = $country['title'];
                }
            }
            $country_options[-1] = $this->_page_lang['forum_others'];

            return $this->templateListView(
            [
                'can_add'               =>  false,
                'can_edit'              =>  false,
                'can_details'           =>  true,
                'can_disabled'          =>  false,
                'can_seq'               =>  false,
                'columns'               => 
                [
                    [
                        'name'          =>  'last_comment_at',            
                        'alias'         =>  $this->_page_lang['forum_last_dt'],
                        'style'         =>  'width:150px;'
                    ],
                    [
                        'name'          =>  'forum_country',            
                        'alias'         =>  $this->_page_lang['country'],
                        'options'       =>  $country_options,
                        'style'         =>  'width:180px;'
                    ],
                    [
                        'name'          =>  'forum_topic',            
                        'alias'         =>  $this->_page_lang['page_title']
                    ],
                    [
                        'name'          =>  'author_name',            
                        'alias'         =>  $this->_page_lang['forum_author']
                    ],
                    [
                        'name'          =>  'total_view',            
                        'alias'         =>  $this->_page_lang['forum_total_1'],
                        'type'          =>  'number',
                        'style'         =>  'width:100px;'
                    ],
                    [
                        'name'          =>  'total_comment',            
                        'alias'         =>  $this->_page_lang['forum_total_2'],
                        'type'          =>  'number',
                        'style'         =>  'width:100px;'
                    ]
                ]
            ], $this->_forum_model->getAll(['keywords' => $this->getParamValue('keywords')]));
        }
    }
      
    public function role($action = '', $id = 0) {
        // post
        $this->pageAction(function() {
            switch(strtolower($this->postParamValue('page_action'))) {
                case 'save':
                    $result = $this->_forum_model->doRoleSave(
                    [
                        'name'      =>  $this->postParamValue('role_name'),
                        'allowed'   =>  $this->postParamValue('role_allowed')
                    ], $this->postParamValue('role_id', 0));
                    $this->pageResult(
                    [
                        'status'    =>  $this->_forum_model->getResultCode(),
                        'message'   =>  $this->_forum_model->getResultMessage(),
                        'url'       =>  (!empty($this->getSession('page_return_link')))?$this->getSession('page_return_link'):$this->toURL('privilege/role')
                    ], ((!empty($result))?true:false));
                    break;
                case 'delete':
                    $result = $this->_forum_model->doRoleDelete($this->postParamValue('id', 0));
                    $this->pageResult(
                    [
                        'status'    =>  $this->_forum_model->getResultCode(),
                        'message'   =>  $this->_forum_model->getResultMessage()
                    ], ((!empty($result))?true:false));
                    break;
            }
        });

        // page data
        if(!empty($action)) {
            if(in_array($action, ['add', 'edit', 'details'])) {
                // check role if need 
                $privilege_role = ((int)$id > 0)?$this->_forum_model->getRoleByID($id):false;
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
            ], $this->_forum_model->getRoleAll(['keywords' => $this->getParamValue('keywords'),'sorting' => 'name_asc']));
        }
    }
}
