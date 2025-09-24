<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

class News extends AdminController {
    
    public function __construct($data) {
        parent::__construct($data);
        
        // load model
        $this->_page_model = $this->loadModel('pages', ['table' => 'news']);

        // set nav
        $this->pageNavigator($this->_page_lang['news'], $this->toURL($this->_mapping_data['class']));
    }
    
    public function index($action = '', $page_id = 0) {
        $country_options = [];
        $list_countries = $this->loadModel('pages', ['table' => 'country'])->getAll($this->_current_lang_index, null, false);
        if(!empty($list_countries)) {
            foreach ($list_countries as $country) {
                $country_options[$country['id']] = $country['title'];
            }
        }
        
        
        $form_setting = 
        [
            'seo'               =>  true,
            'rows'              =>  
            [
                [
                    'name'      =>  'target_date',
                    'alias'     =>  $this->_page_lang['target_date'],
                    'type'      =>  'date',
                    'validation'=>  'required|date',
                    'share'     =>  true
                ],
                [
                    'name'      =>  'target_country',
                    'alias'     =>  $this->_page_lang['country'],
                    'type'      =>  'select',
                    'options'   =>  $country_options,
                    'share'     =>  true
                ],
                [
                    'name'      =>  'title',
                    'alias'     =>  $this->_page_lang['page_title'],
                    'validation'=>  'required'
                ],
                [
                    'name'      =>  'content',
                    'alias'     =>  $this->_page_lang['page_content'],
                    'type'      =>  'editor'
                ]
            ],
            'files'             => 
            [
                [
                    'name'      =>  'photo',
                    'alias'     =>  'Featured Photo',
                    'allowed'   =>  'jpg|jpeg|png|gif',
                    'w'         =>  630,
                    'h'         =>  420
                ]
            ]
        ];
        
        $list_setting = 
        [
            'columns'               => 
            [
                [
                    'name'          =>  'target_date',            
                    'alias'         =>  $this->_page_lang['target_date'],
                    'style'         =>  'width:120px;'        
                ],
                [
                    'name'          =>  'target_country',            
                    'alias'         =>  $this->_page_lang['country'],
                    'style'         =>  'width:150px;',
                    'options'       =>  $country_options
                ],
                [
                    'name'          =>  'title',            
                    'alias'         =>  $this->_page_lang['page_title']
                ]
            ]
        ];

        return $this->loadTemplate($this->_page_model, $action, $page_id, $form_setting, $list_setting);
    }
}