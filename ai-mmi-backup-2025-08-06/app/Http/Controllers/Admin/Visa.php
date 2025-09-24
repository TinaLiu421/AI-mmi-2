<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

class Visa extends AdminController {
    
    public function __construct($data) {
        parent::__construct($data);
        
        // load model
        $this->_page_model = $this->loadModel('pages', ['table' => 'visa']);

        // set nav
        $this->pageNavigator($this->_page_lang['visa_options'], $this->toURL($this->_mapping_data['class']));
    }
    
    public function index($action = '', $page_id = 0) {
        $parent_id = $this->getParamValue('parent_id', 0);
        if(!empty($parent_id)) {
            $form_setting = 
            [
                'seo'               =>  true,
                'rows'              =>  
                [
                    [
                        'name'      =>  'title',
                        'alias'     =>  $this->_page_lang['page_title'],
                        'validation'=>  'required'
                    ],
                    [
                        'name'      =>  'sub_title',
                        'alias'     =>  $this->_page_lang['page_sub_title']
                    ],
                    [
                        'name'      =>  'content',
                        'alias'     =>  $this->_page_lang['page_content'],
                        'type'      =>  'editor'
                    ]
                ]
            ];
            
            $list_setting = 
            [
                'columns'               => 
                [
                    [
                        'name'          =>  'title',            
                        'alias'         =>  $this->_page_lang['page_title'],
                        'next'          =>  true,
                        'max_level'     =>  2
                    ]
                ]
            ];
        }
        else {
            $list_countries = $this->loadModel('pages', ['table' => 'country'])->getAll($this->_current_lang_index, null, false);
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
                        'name'      =>  'ref_country',
                        'alias'     =>  $this->_page_lang['country'],
                        'type'      =>  'select',
                        'options'   =>  $this->optionsToArray($list_countries),
                        'share'     =>  true
                    ]
                ],
                'files'             =>  
                [
                    [
                        'type'      =>  'visa_option',
                        'name'      =>  'photo',
                        'alias'     =>  'Photo',
                        'allowed'   =>  'jpg|jpeg|png|gif',
                        'w'         =>  750,
                        'h'         =>  520
                    ]
                ]
            ];
            
            $list_setting = 
            [
                'columns'               => 
                [
                    [
                        'name'          =>  'ref_country',            
                        'alias'         =>  $this->_page_lang['country'],
                        'options'       =>  $this->optionsToArray($list_countries),
                        'style'         =>  'width:150px'
                    ],
                    [
                        'name'          =>  'title',            
                        'alias'         =>  $this->_page_lang['page_title'],
                        'next'          =>  true,
                        'max_level'     =>  2
                    ]
                ]
            ];
        }
        
        return $this->loadTemplate($this->_page_model, $action, $page_id, $form_setting, $list_setting);
    }
}