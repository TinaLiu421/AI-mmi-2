<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

class Pages extends AdminController {
    
    public function __construct($data) {
        parent::__construct($data);
        
        // load model
        $this->_page_model = $this->loadModel('pages');

        // set nav
        $this->pageNavigator($this->_page_lang['pages'], $this->toURL($this->_mapping_data['class']));
    }
    
    public function index($action = '', $page_id = 0) {
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
                    'name'      =>  'content',
                    'alias'     =>  $this->_page_lang['page_content'],
                    'type'      =>  'editor'
                ]
            ]
        ];
        if($page_id == 1) {
            $form_setting['seo'] = false;
            unset($form_setting['rows'][1]);
            $form_setting['files'] = 
            [
                [
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
                    'h'         =>  800
                ],
                [
                    'name'      =>  'mobile_banner_2',
                    'alias'     =>  'Mobile Banner (繁)',
                    'allowed'   =>  'jpg|jpeg|png|gif',
                    'w'         =>  800,
                    'h'         =>  800
                ],
                [
                    'name'      =>  'mobile_banner_3',
                    'alias'     =>  'Mobile Banner (简)',
                    'allowed'   =>  'jpg|jpeg|png|gif',
                    'w'         =>  800,
                    'h'         =>  800
                ]
            ];
        }
        else if($page_id == 3) {
            $form_setting['files'] = 
            [
                [
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
                ]
            ];
        }
        
        
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
                    'alias'         =>  $this->_page_lang['page_title'],
                    'next'          =>  true,
                    'next_mapping'  =>  
                    [
                        3           =>  $this->toURL('visa'),
                        4           =>  $this->toURL('faqs')
                    ],
                    'max_level'     =>  1
                ]
            ]
        ];

        return $this->loadTemplate($this->_page_model, $action, $page_id, $form_setting, $list_setting);
    }
}