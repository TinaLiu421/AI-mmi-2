<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

class Plans extends AdminController {
    
    public function __construct($data) {
        parent::__construct($data);
        
        // set nav
        $this->pageNavigator($this->_page_lang['plans'], $this->toURL($this->_mapping_data['class']));
    }
    
    public function account($action = '', $page_id = 0) {
        // load model
        $this->_page_model = $this->loadModel('pages', ['table' => 'plan_account']);
        
        // set nav
        $this->pageNavigator($this->_page_lang['plan_account'], $this->toURL($this->_mapping_data['class'].'/account'));
        
        $form_setting = 
        [
            'rows'              =>  
            [
                [
                    'name'      =>  'title',
                    'alias'     =>  $this->_page_lang['page_title'],
                    'validation'=>  'required'
                ],
                [
                    'name'      =>  'content',
                    'alias'     =>  $this->_page_lang['page_content'],
                    'type'      =>  'editor'
                ],
                [
                    'name'      =>  'price',
                    'alias'     =>  $this->_page_lang['price'],
                    'validation'=>  'required|number',
                    'share'     =>  true
                ],
                [
                    'name'      =>  'valid_days_trial',
                    'alias'     =>  $this->_page_lang['valid_days_trial'],
                    'validation'=>  'required|number',
                    'share'     =>  true
                ],
                [
                    'name'      =>  'valid_days',
                    'alias'     =>  $this->_page_lang['valid_days'],
                    'validation'=>  'required|number',
                    'share'     =>  true
                ]
            ]
        ];
        
        $list_setting = 
        [
            'can_add'               =>  false,
            'can_edit'              =>  true,
            'can_delete'            =>  false,
            'can_disabled'          =>  false,
            'can_seq'               =>  false,
            'columns'               => 
            [
                [
                    'name'          =>  'title',            
                    'alias'         =>  $this->_page_lang['page_title']
                ]
            ]
        ];

        return $this->loadTemplate($this->_page_model, $action, $page_id, $form_setting, $list_setting);
    }
    
    public function visa_submission($action = '', $page_id = 0) {
        // load model
        $this->_page_model = $this->loadModel('pages', ['table' => 'plan_visa_submission']);
        
        // set nav
        $this->pageNavigator($this->_page_lang['plan_visa_submission'], $this->toURL($this->_mapping_data['class'].'/visa_submission'));
        
        $form_setting = 
        [
            'rows'              =>  
            [
                [
                    'name'      =>  'title',
                    'alias'     =>  $this->_page_lang['page_title'],
                    'validation'=>  'required'
                ],
                [
                    'name'      =>  'brief',
                    'alias'     =>  $this->_page_lang['page_brief'],
                    'type'      =>  'textarea'
                ],
                [
                    'name'      =>  'content',
                    'alias'     =>  $this->_page_lang['page_content'],
                    'type'      =>  'editor'
                ],
                [
                    'name'      =>  'price',
                    'alias'     =>  $this->_page_lang['price'],
                    'validation'=>  'required|number',
                    'share'     =>  true
                ],
                [
                    'name'      =>  'valid_days',
                    'alias'     =>  $this->_page_lang['valid_days'],
                    'validation'=>  'required|number',
                    'share'     =>  true
                ]
            ]
        ];
        
        $list_setting = 
        [
            'can_add'               =>  false,
            'can_edit'              =>  true,
            'can_delete'            =>  false,
            'can_disabled'          =>  false,
            'can_seq'               =>  false,
            'columns'               => 
            [
                [
                    'name'          =>  'title',            
                    'alias'         =>  $this->_page_lang['page_title']
                ]
            ]
        ];
        
        return $this->loadTemplate($this->_page_model, $action, $page_id, $form_setting, $list_setting);
    }
}