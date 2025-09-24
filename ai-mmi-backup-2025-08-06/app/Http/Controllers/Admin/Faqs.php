<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

class Faqs extends AdminController {
    
    public function __construct($data) {
        parent::__construct($data);
        
        // load model
        $this->_page_model = $this->loadModel('pages', ['table' => 'faq']);

        // set nav
        $this->pageNavigator($this->_page_lang['faqs'], $this->toURL($this->_mapping_data['class']));
    }
    
    public function index($action = '', $page_id = 0) {
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