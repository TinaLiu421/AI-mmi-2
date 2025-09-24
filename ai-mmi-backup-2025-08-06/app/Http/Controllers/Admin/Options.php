<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

class Options extends AdminController {
    
    public function __construct($data) {
        parent::__construct($data);
        
        // set nav
        $this->pageNavigator($this->_page_lang['options'], $this->toURL($this->_mapping_data['class']));
    }
    
    public function countries($action = '', $page_id = 0) {
        // load model
        $this->_page_model = $this->loadModel('pages', ['table' => 'country']);
        
        // set nav
        $this->pageNavigator($this->_page_lang['countries'], $this->toURL($this->_mapping_data['class'].'/countries'));
        
        $form_setting = 
        [
            'rows'              =>  
            [
                [
                    'name'      =>  'title',
                    'alias'     =>  $this->_page_lang['page_title'],
                    'validation'=>  'required'
                ]
            ]
        ];
        
        $form_setting['files'] = 
        [
            [
                'name'      =>  'flag',
                'alias'     =>  'Flag',
                'allowed'   =>  'jpg|jpeg|png|gif',
                'w'         =>  300,
                'h'         =>  150
            ],
            /*[
                'name'      =>  'banner_1',
                'alias'     =>  'Banner (Eng)',
                'allowed'   =>  'jpg|jpeg|png|gif',
                'w'         =>  1300,
                'h'         =>  245
            ],
            [
                'name'      =>  'banner_2',
                'alias'     =>  'Banner (繁)',
                'allowed'   =>  'jpg|jpeg|png|gif',
                'w'         =>  1300,
                'h'         =>  245
            ],
            [
                'name'      =>  'banner_3',
                'alias'     =>  'Banner (简)',
                'allowed'   =>  'jpg|jpeg|png|gif',
                'w'         =>  1300,
                'h'         =>  245
            ],
            [
                'name'      =>  'mobile_banner_1',
                'alias'     =>  'Mobile Banner (Eng)',
                'allowed'   =>  'jpg|jpeg|png|gif',
                'w'         =>  800,
                'h'         =>  400
            ],
            [
                'name'      =>  'mobile_banner_2',
                'alias'     =>  'Mobile Banner (繁)',
                'allowed'   =>  'jpg|jpeg|png|gif',
                'w'         =>  800,
                'h'         =>  400
            ],
            [
                'name'      =>  'mobile_banner_3',
                'alias'     =>  'Mobile Banner (简)',
                'allowed'   =>  'jpg|jpeg|png|gif',
                'w'         =>  800,
                'h'         =>  400
            ]*/
        ];
        
        $list_setting = 
        [
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
    
    public function organization_type($action = '', $page_id = 0) {
        // load model
        $this->_page_model = $this->loadModel('pages', ['table' => 'organization_type']);
        
        // set nav
        $this->pageNavigator($this->_page_lang['organization_type'], $this->toURL($this->_mapping_data['class'].'/organization_type'));
        
        $form_setting = 
        [
            'rows'              =>  
            [
                [
                    'name'      =>  'title',
                    'alias'     =>  $this->_page_lang['page_title'],
                    'validation'=>  'required'
                ]
            ]
        ];
        
        $list_setting = 
        [
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
   
    public function interest_visas($action = '', $page_id = 0) {
        // load model
        $this->_page_model = $this->loadModel('pages', ['table' => 'interest_visa']);
        
        // set nav
        $this->pageNavigator($this->_page_lang['interest_visas'], $this->toURL($this->_mapping_data['class'].'/interest_visas'));
        
        $form_setting = 
        [
            'rows'              =>  
            [
                [
                    'name'      =>  'title',
                    'alias'     =>  $this->_page_lang['page_title'],
                    'validation'=>  'required'
                ]
            ]
        ];
        
        $list_setting = 
        [
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
    
    public function interest_topics($action = '', $page_id = 0) {
        // load model
        $this->_page_model = $this->loadModel('pages', ['table' => 'interest_topic']);
        
        // set nav
        $this->pageNavigator($this->_page_lang['interest_topics'], $this->toURL($this->_mapping_data['class'].'/interest_topics'));
        
        $form_setting = 
        [
            'rows'              =>  
            [
                [
                    'name'      =>  'title',
                    'alias'     =>  $this->_page_lang['page_title'],
                    'validation'=>  'required'
                ]
            ]
        ];
        
        $list_setting = 
        [
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