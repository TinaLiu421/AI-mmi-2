<?php
namespace App\Http\Controllers;

class AdminController extends CoreController {
    protected $_user_model = null;
    protected $_media_files_model = null;
    protected $_setting_model = null;
    protected $_page_model = null;
    
    public function __construct($data) {
        parent::__construct($data);
        $this->initialize();
    }

    // template  
    protected function templateListView($list_setting = [], $list_data = []) {
        /*
        $list_setting = 
        [
            'table_index'           =>  '',
            'list_url'              =>  '',
            'list_index'            =>  'id',
            'can_details'           =>  false,
            'can_add'               =>  true,
            'can_edit'              =>  true,
            'can_delete'            =>  true,
            'can_void'              =>  false,
            'can_disabled'          =>  true,
            'can_seq'               =>  true,
            'advanced_search'       =>  
            [
                [
                    'name'          =>  'title',
                    'alias'         =>  'Title'
                ],
                [
                    'name'          =>  'start_date',
                    'alias'         =>  'Start Date',
                    'type'          =>  'date',
                    'format'        =>  'Y-m-d'
                ],
                [
                    'name'          =>  'status',
                    'alias'         =>  'Status',
                    'type'          =>  'select',
                    'options'       => 
                    [
                        '1'         =>  'Enable',
                        '4'         =>  'Disable'
                    ]
                ]
            ],
            'columns'               => 
            [
                [
                    'name'          =>  'title',            // required
                    'alias'         =>  'Title',            // optional, default = blank
                    'style'         =>  'width:80px;',      // optional, default = blank
                    'type'          =>  'text',             // optional, default = text (date, number)
                    'options'       =>  [],                 // optional, default = false
                    'dateformat'    =>  'Y-m-d'             // optional, default = blank
                    'decimal'       =>  2                   // optional, default = 2
                    'prefix'        =>  'HK$'               // optional, default = blank
                    'sortable'      =>  false,              // optional, default = false
                    'next'          =>  'false'              // optional, default value is false,
                    'next_mapping'  =>  
                    [
                        1           =>  'your_url'
                    ],
                    'max_level'     =>  0
                ],
                ...
                [
                    'name'          =>  'status',
                    'options'       => 
                    [
                        1           =>  'Enable',
                        4           =>  'Disable'
                    ]
                ]
            ],
            'extra_action_link'     => 
            [
                '<a class="btn" href="#">Export<a>'
            ]
            'extra_param'            =>  
            [
                'parent_id'         =>  1
            ],
            'customize'             =>  
            [
                'you_html_code_here'
            ]
        ];
        
        $list_data = 
        [
            'data'              => 
            [
                ['title' => 'xxxxx', 'content' => ''],
                ['title' => 'yyyyy', 'content' => '']
                ....
            ],
            'pageration'        => 
            [
                'page_size'     => 20,
                'total'         => 100,
                'current_page'  => 2,
                'total_page'    => 5
            ]
        ];
        */
        
        // default setting
        $list_setting = array_merge(
        [
            'table_index'       =>  $this->_mapping_data['class'].'_'.$this->_mapping_data['function'],
            'list_url'          =>  $this->toURL(array_filter([$this->_mapping_data['class'],(($this->_mapping_data['function']!='index')?$this->_mapping_data['function']:'')])),
            'list_index'        =>  'id',
            'can_details'       =>  false,
            'can_add'           =>  true,
            'can_edit'          =>  true,
            'can_delete'        =>  true,
            'can_void'          =>  false,
            'can_disabled'      =>  true,
            'can_seq'           =>  true,
            'advanced_search'   =>  [],
            'columns'           =>  [],
            'extra_action_link' =>  [],
            'extra_param'       =>  
            [
                'parent_id'     =>  $this->getParamValue('parent_id', 0)
            ],
            'customize'         =>  []
        ],
        $list_setting);
        
        // revise columns, assign default
        if(!empty($list_setting['columns'])) {
            foreach ($list_setting['columns'] as $col_key => $col) {
                $col = array_merge(
                [
                    'name'          =>  'title',
                    'alias'         =>  '', 
                    'style'         =>  '',
                    'type'          =>  'text',
                    'options'       =>  [],                
                    'dateformat'    =>  '',
                    'decimal'       =>  0,
                    'prefix'        =>  '',  
                    'sortable'      =>  false, 
                    'next'          =>  false,
                    'next_mapping'  =>  [],
                    'max_level'     =>  0
                ], $col);
                if(!empty($list_setting['can_seq'])) {
                    $col['sortable'] = false;
                }
                $list_setting['columns'][$col_key] = $col;
             }
        }
   
        // current page link
        $this->setSession(['page_return_link' => $this->_mapping_data['current_url']]);
       
        // load view
        return $this->pageSetting($list_setting)->pageData(['list' => $list_data])->pageView('template.list');
    }
    
    protected function templateFormView($form_setting = [], $page_data = [], $form_id = 0, $parent_page_data = []){
        /*
        $form_setting = [
            'readonly'              =>  false,
            'custom_link'           =>  false,
            'multi_language'        =>  true,
            'ts_translation'        =>  true,
            'seo'                   =>  false, 
            'type'                  =>  'page',
            'rows'                  =>  
            [
                [
                    'readonly'      =>  false,       // optional, default = false
                    'name'          =>  'title',
                    'alias'         =>  'Title',
                    'type'          =>  'text',     // text, color, textarea, editor, select, select_multi
                    'options'       =>  [],
                    'share'         =>  false,      // optional, default = false
                    'validation'    =>  'required'  // required, email, number, date, password
                ]
            ],
            'file'                  =>  
            [
                [
                    'name'          =>  'photo',
                    'alias'         =>  'Gallery',
                    'allowed'       =>  'jpg|jpeg|png|gif',
                    'data'          =>  [],
                ]
            ],
            'customize'             =>  
            [
                'you_html_code_here'
            ]
        ]
        */
        
        if((int)$form_id > 0 && empty($page_data)) {
            return abort(404);
        }
        
        // default setting & data
        $form_setting = array_merge(
        [
            'custom_link'       =>  false,
            'multi_language'    =>  true,
            'ts_translation'    =>  true,
            'seo'               =>  false, 
            'type'              =>  'page',
            'rows'              =>  [],
            'files'             =>  [],
        ],
        $form_setting);
        
        // revise columns, assign default
        if(!empty($form_setting['rows'])) {
            foreach ($form_setting['rows'] as $row_key => $row) {
                $row = array_merge(
                [
                    'readonly'      =>  false,
                    'name'          =>  '',
                    'alias'         =>  '',
                    'type'          =>  'text',
                    'options'       =>  [],
                    'share'         =>  false,
                    'validation'    =>  ''
                ], $row);
                if(empty($form_setting['multi_language'])) {
                    $row['share'] = true;
                }
                $form_setting['rows'][$row_key] = $row;
             }
        }
        
        // load view
        $this->pageScript('editor.min.js', 'asset/lib/tinymce4', false);
        return $this->pageSetting($form_setting)->pageData(['data' => $page_data, 'parent_data' => $parent_page_data])->pageView('template.form');
    }
    
    protected function templateAction($model_object = null, $permisson = [], $options = []) {
        $permisson = array_merge([
            'can_add'           =>  true,
            'can_edit'          =>  true,
            'can_delete'        =>  true,
            'can_void'          =>  false,
            'can_disabled'      =>  true,
            'can_seq'           =>  true
        ], $permisson);

        if(!empty($model_object)) {
            $this->pageAction(function($permisson, $model_object, $options) {
                $permission_denied = false;
                $result = false;
                $url = '';
                switch ($this->postParamValue('page_action')) {
                    case 'save':
                        if(($this->postParamValue('id',0) == 0 && !empty($permisson['can_add'])) || ($this->postParamValue('id',0) > 0 && !empty($permisson['can_edit']))) {
                            $result = $model_object->doSave($this->_page_post_data);
                            $url = $this->toPrevURL();
                        }
                        else {
                            $permission_denied = true;
                        }
                        break;
                    case 'delete':
                        if(!empty($permisson['can_delete'])) {
                            $result = $model_object->doDelete($this->postParamValue('id',0));
                        }
                        else {
                            $permission_denied = true;
                        }
                        break;
                    case 'disabled':
                        if(!empty($permisson['can_disabled'])) {
                            $model_object->setDisabled($this->postParamValue('id', 0));
                        }
                        else {
                            $permission_denied = true;
                        }
                        break;
                    case 'seq':
                        if(!empty($permisson['can_seq'])) {
                            $model_object->switchSeq($this->postParamValue('id', 0), $this->postParamValue('from_seq', 0), $this->postParamValue('to_seq', 0), $options);
                        }
                        else {
                            $permission_denied = true;
                        }
                        break;
                    default:
                        $result = false;
                }
                if(!empty($permission_denied)) {
                    $this->pageResult(
                    [
                        'status'    =>  403,
                        'message'   =>  $this->_page_lang['permission_denied'],
                        'url'       =>  $url,
                    ], false);
                }
                else {
                    $this->pageResult(
                    [
                        'status'    =>  $model_object->getResultCode(),
                        'message'   =>  $model_object->getResultMessage(),
                        'url'       =>  $url,
                    ], ((!empty($result))?true:false));
                }
            }, $permisson, $model_object, $options);
        }
    }
    
    protected function loadTemplate($model_object = null, $action = '', $page_id = 0, $form_setting = [], $list_setting = [], $options = []) {
        if(!empty($model_object)) {
            $this->templateAction($model_object, 
            [
                'can_add'           =>  (isset($list_setting['can_add']))?$list_setting['can_add']:true,
                'can_edit'          =>  (isset($list_setting['can_edit']))?$list_setting['can_edit']:true,
                'can_delete'        =>  (isset($list_setting['can_delete']))?$list_setting['can_delete']:true,
                'can_void'          =>  (isset($list_setting['can_void']))?$list_setting['can_void']:false,
                'can_disabled'      =>  (isset($list_setting['can_disabled']))?$list_setting['can_disabled']:true,
                'can_seq'           =>  (isset($list_setting['can_seq']))?$list_setting['can_seq']:true,
            ], $options);

            // page data
            $parent_id = $this->getParamValue('parent_id', 0);
            $parents_data = $model_object->getParentsNode((($page_id > 0)?$page_id:$parent_id), $this->_current_lang_index, ['select_cols' => ['id', 'title']]);
            if(!empty($parents_data)) {
                foreach ($parents_data as $parent_key => $parent) {
                    $this->pageNavigator($parent['title'], $this->toURL($this->_mapping_data['class'].(($this->_mapping_data['function']!='index')?('/'.$this->_mapping_data['function']):'')).'?parent_id='.$parent['id']);
                }
            }

            if(!empty($action)) {
                if(in_array($action, ['add', 'edit', 'details'])) {
                    
                    $page_data = $model_object->getByID($page_id);

                    // set nav
                    if(!empty($action) && in_array($action, ['add', 'edit', 'details'])) {
                        $this->pageNavigator($this->_page_lang[strtolower($action)]);
                    }

                    // load view
                    $this->_page_readonly = ($action == 'details')?true:false;
                    return $this->templateFormView($form_setting, $page_data, $page_id, $parents_data);
                }
                else {
                    $this->doRedirect($this->toURL($this->_mapping_data['class']));
                }
            }
            else {
                // load view
                $get_options = [];
                if(!empty($this->_page_get_data)) {
                    foreach ($this->_page_get_data as $get_key => $get_value) {
                        $get_options[$get_key] = $get_value;
                    }
                }
                if(!empty($options)) {
                    $get_options = array_merge($get_options, $options);
                }
                return $this->templateListView($list_setting, $model_object->getAll($this->_current_lang_index, $get_options));
            }
        }
        else {
            return abort(500);
        }
    }

    // current user privilege
    protected function hasUserPrivilege() {
        if(!empty($this->getSession('admin_access_token'))) {
            $user_model = $this->loadModel('user');
            $this->_current_user = $user_model->getByToken($this->getSession('admin_access_token'));
            if(!empty($this->_current_user)) {
                $role_data = $user_model->getRoleByID($this->_current_user['role_id']);
                if(!empty($role_data)) {
                    $this->_current_user['allowed'] = $role_data['allowed'];
                }
            }
        }
        
        return $this->_current_user;
    }
    
    protected function hasUserRole($section = '', $action_index = 0) {
        if(!empty($this->_current_user)) {
            if($this->_current_user['id'] == 1) {
                return true;
            }

            if(!empty($section) && !empty($action_index)) {
                if(!empty($this->_current_user['allowed'][$section])) {
                    if(in_array($action_index, $this->_current_user['allowed'][$section])) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    // send email
    protected function sendEmail($email_address = '', $subject = '', $content = '') {
        if(is_string($email_address)) {
            $email_address = [$email_address];
        }
        foreach ($email_address as $key => $to_email) {
            $email_address[$key] = $this->toPlainText($to_email);
        }
        $email_address = array_unique(array_filter($email_address));
        
        if(!empty($email_address)) {
            $html = '<div style="font-family:Arial,微軟正黑體,PMingLiU,新細明體;padding:8px;">';
                $html.= '<div style="padding:10px;border:8px solid #002065;border-radius:4px;box-shadow:0px 0px 8px #222;">';
                $html.= $content;
                $html.= '</div>';
            $html.= '</div>';

            require app_path('Libraries/sendgrid/sendgrid-php.php');
            $email = new \SendGrid\Mail\Mail();

            $email->setFrom('no-reply@artech-appmakers.com', 'AI-mmi');

            $email->setSubject($subject);
            foreach ($email_address as $key => $to_email) {
                $to_email = $this->toPlainText($to_email);
                if (filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
                    $email->addTo($to_email);
                }
            }
            $email->addContent(
                "text/html",
                $html
            );

            $sendgrid = new \SendGrid('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');

            try {
                if($sendgrid->send($email)) {
                    return true;
                }
                return false;
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }
    
    // excel
    protected function outputPHPExcel($row_data = [], $header_title = [], $fixed_row = 'A2', $excel_file_name = '') {
        require app_path('Libraries/excel/PHPExcel.php');
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        
        $colCount = 0;
        $rowCount = 1;
        if(!empty($header_title)) {
            foreach ($header_title as $title_key => $title) {
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($colCount, $rowCount, (string)$title);
                $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($colCount, $rowCount)->getFont()->setBold(true);
                $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($colCount, $rowCount)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($colCount, $rowCount)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_TOP);
                $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($colCount)->setAutoSize(true);
                $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($colCount, $rowCount)->applyFromArray(
                    array('borders' => array(
                        'allborders'    => array('style' => \PHPExcel_Style_Border::BORDER_THIN)
                    ))
                );
                $colCount++;
            }
        }
        
        $colCount = 0;
        $rowCount = 2;
        if(!empty($row_data)) {
            foreach ($row_data as $row_key => $data) {
                $colCount = 0;
                foreach ($data as $data_key => $value) {
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($colCount, $rowCount, (string)$value);
                    $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($colCount, $rowCount)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                    $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($colCount, $rowCount)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_TOP);
                    $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($colCount, $rowCount)->getAlignment()->setWrapText(true);
                    $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($colCount)->setAutoSize(true);
                    $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($colCount, $rowCount)->applyFromArray(
                        array('borders' => array(
                            'allborders'    => array('style' => \PHPExcel_Style_Border::BORDER_THIN)
                        ))
                    );
                    $colCount++;
                }
                $rowCount++;
            }
        }
        
        if(!empty($header_title) && !empty($fixed_row)) {
            $objPHPExcel->getActiveSheet()->freezePane($fixed_row);
        }
        
        $objPHPExcel->setActiveSheetIndex(0);
        
        header('Content-Type: application/vnd.ms-excel'); //mime type
        header('Content-Disposition: attachment;filename="'.$excel_file_name.'.xlsx"'); //tell browser what's the file name
        header('Cache-Control: max-age=0'); //no cache
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  
        $objWriter->save('php://output');

        exit();
    }
    
    // init
    private function initialize() {
        // left menu
        if(!empty($this->hasUserPrivilege())) {
            $this->pageNavigator($this->_page_lang['home']);
            
            $left_menu = [];

            /*$left_menu['home'] = [
                'index' =>  'home',
                'title' =>  $this->_page_lang['home'],
                'url'   =>  $this->toURL('home'),
                'icon'  =>  'home'
            ];*/
            
            $left_menu['pages'] = [
                'index' =>  'pages',
                'title' =>  $this->_page_lang['pages'],
                'url'   =>  $this->toURL('pages'),
                'icon'  =>  'book'
            ];
            
            /*$left_menu['news'] = [
                'index' =>  'news',
                'title' =>  $this->_page_lang['news'],
                'url'   =>  $this->toURL('news'),
                'icon'  =>  'newspaper-o'
            ];
            
            $left_menu['events'] = [
                'index' =>  'events',
                'title' =>  $this->_page_lang['events'],
                'url'   =>  $this->toURL('events'),
                'icon'  =>  'calendar-o'
            ];*/
            
            $left_menu['visa'] = [
                'index' =>  'visa',
                'title' =>  $this->_page_lang['visa_options'],
                'url'   =>  $this->toURL('visa'),
                'icon'  =>  'ticket'
            ];

            $left_menu['faqs'] = [
                'index' =>  'faqs',
                'title' =>  $this->_page_lang['faqs'],
                'url'   =>  $this->toURL('faqs'),
                'icon'  =>  'commenting'
            ];
            
            $left_menu['member_area'] = [
                'index' =>  'member_area',
                'title' =>  $this->_page_lang['member_area'],
                'url'   =>  $this->toURL('member_area'),
                'icon'  =>  'database',
                'child'   =>  [
                    [
                        'index' =>  'member',
                        'title' =>  $this->_page_lang['member'],
                        'url'   =>  $this->toURL('members'),
                        'icon'  =>  'user'
                    ],
                    [
                        'index' =>  'posts',
                        'title' =>  $this->_page_lang['posts'],
                        'url'   =>  $this->toURL('posts'),
                        'icon'  =>  'rss'
                    ],
                    [
                        'index' =>  'forum',
                        'title' =>  $this->_page_lang['forum'],
                        'url'   =>  $this->toURL('forum'),
                        'icon'  =>  'podcast'
                    ]
                ]
            ];
            
            $left_menu['plans'] = [
                'index' =>  'plans',
                'title' =>  $this->_page_lang['plans'],
                'url'   =>  $this->toURL('plans'),
                'icon'  =>  'leaf', 
                'child'   =>  [
                    [
                        'index' =>  'account',
                        'title' =>  $this->_page_lang['plan_account'],
                        'url'   =>  $this->toURL('plans/account'),
                        'icon'  =>  'user'
                    ],
                    [
                        'index' =>  'visa_submission',
                        'title' =>  $this->_page_lang['plan_visa_submission'],
                        'url'   =>  $this->toURL('plans/visa_submission'),
                        'icon'  =>  'gavel'
                    ]
                ]
            ];
            
            $left_menu['options'] = [
                'index' =>  'options',
                'title' =>  $this->_page_lang['options'],
                'url'   =>  $this->toURL('options'),
                'icon'  =>  'filter', 
                'child'   =>  [
                    [
                        'index' =>  'countries',
                        'title' =>  $this->_page_lang['countries'],
                        'url'   =>  $this->toURL('options/countries'),
                        'icon'  =>  'globe'
                    ],
                    [
                        'index' =>  'organization_type',
                        'title' =>  $this->_page_lang['organization_type'],
                        'url'   =>  $this->toURL('options/organization_type'),
                        'icon'  =>  'tag'
                    ],
                    [
                        'index' =>  'interest_visas',
                        'title' =>  $this->_page_lang['interest_visas'],
                        'url'   =>  $this->toURL('options/interest_visas'),
                        'icon'  =>  'address-card-o'
                    ],
                    [
                        'index' =>  'interest_topics',
                        'title' =>  $this->_page_lang['interest_topics'],
                        'url'   =>  $this->toURL('options/interest_topics'),
                        'icon'  =>  'coffee'
                    ]
                ]
            ];
            
            $left_menu['media_files'] = [
                'index' =>  'media_files',
                'title' =>  $this->_page_lang['media_files'],
                'url'   =>  $this->toURL('media_files'),
                'icon'  =>  'cloud-upload'
            ];

            if($this->_current_user['id'] == 1) {
                $left_menu['setting'] = [
                    'index' =>  'setting',
                    'title' =>  $this->_page_lang['setting'],
                    'url'   =>  $this->toURL('setting'),
                    'icon'  =>  'gears', 
                    'child'   =>  [
                        [
                            'index' =>  'general',
                            'title' =>  $this->_page_lang['setting_general'],
                            'url'   =>  $this->toURL('setting/general'),
                            'icon'  =>  'at'
                        ],
                        [
                            'index' =>  'email',
                            'title' =>  $this->_page_lang['setting_email'],
                            'url'   =>  $this->toURL('setting/email'),
                            'icon'  =>  'envelope-o'
                        ],
                        [
                            'index' =>  'whitelist',
                            'title' =>  $this->_page_lang['setting_whitelist'],
                            'url'   =>  $this->toURL('setting/whitelist'),
                            'icon'  =>  'warning'
                        ]
                    ]
                ];
            }
            
            $left_menu['profile'] = [
                'index' =>  'profile',
                'title' =>  $this->_page_lang['profile'],
                'url'   =>  $this->toURL('profile'),
                'icon'  =>  'info-circle'
            ];
            
            $left_menu['website'] = [
                'index' =>  'website',
                'title' =>  $this->_page_lang['website'],
                'url'   =>  $this->_mapping_data['app_url'],
                'icon'  =>  'desktop',
                'target'=>  '_blank'  
            ];
            
            $left_menu['logout'] = [
                'index' =>  'logout',
                'title' =>  $this->_page_lang['logout'],
                'url'   =>  $this->toURL('authn/logout'),
                'icon'  =>  'sign-out'
            ];
            
            $this->pageData(['left_menu' => $left_menu]);
        }
    }
}