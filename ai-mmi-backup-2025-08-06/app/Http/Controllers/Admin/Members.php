<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

class Members extends AdminController {
    
    protected $_member_type = [
        1   =>  'Individual',
        2   =>  'Migration Agent',
        3   =>  'Service Provider'
    ];

    public function __construct($data) {
        parent::__construct($data);
        
        $this->pageIndex('member_area');
        
        // load model
        $this->_member_model = $this->loadModel('member');
    }

    public function index($action = '', $id = 0) {
        // post
        $this->pageAction(function() {
            switch(strtolower($this->postParamValue('page_action'))) {
                case 'save':
                    $revise_member = 
                    [
                        'full_name'             =>  implode(' ', array_filter([$this->_page_post_data['first_name'], $this->_page_post_data['last_name']])),
                        'first_name'            =>  $this->_page_post_data['first_name'],
                        'last_name'             =>  $this->_page_post_data['last_name'],
                        'email'                 =>  $this->_page_post_data['email'],
                        'remark'                =>  $this->_page_post_data['remark'],
                        'details'               =>  [],
                        'agents'                =>  [],
                        'lawfirms'              =>  [],
                        'verified'              =>  $this->_page_post_data['verified'],
                        'status'                =>  $this->_page_post_data['status'],
                        'expiration_date_account'   => $this->postParamValue('expiration_date_account', '1970-01-01'),
                        'expiration_date_visa_submission_ai'   => $this->postParamValue('expiration_date_visa_submission_ai', '1970-01-01'),
                        'expiration_date_visa_submission_human'   => $this->postParamValue('expiration_date_visa_submission_human', '1970-01-01'),
                    ];
                    
                    if(!empty($this->_page_post_data['countries_serving'])) {
                        $revise_member['countries_serving']  = $this->_page_post_data['countries_serving'];
                    }
                    
                    if(!empty($this->_page_post_data['interested_visa'])) {
                        $revise_member['interested_visa']  = $this->_page_post_data['interested_visa'];
                    }

                    if(!empty($this->_page_post_data['interested_topic'])) {
                        $revise_member['interested_topic']  = $this->_page_post_data['interested_topic'];
                    }

                    if(!empty($this->_page_post_data['password'])) {
                        $revise_member['password']  = $this->_page_post_data['password'];
                        $revise_member['repeat_password']  = $this->_page_post_data['repeat_password'];
                    }

                    if(!empty($this->_page_post_data['telephone_code'])) {
                        $revise_member['telephone_code']  = $this->_page_post_data['telephone_code'];
                    }

                    if(!empty($this->_page_post_data['telephone_num'])) {
                        $revise_member['telephone_num']  = $this->_page_post_data['telephone_num'];
                    }

                    if(in_array((int)$this->_page_post_data['member_type'], [2,3])) {
                        $revise_member['details'] = 
                        [
                            'company_name'      =>  $this->_page_post_data['company_name'],
                            'company_website'   =>  $this->_page_post_data['company_website'],
                            'company_address'   =>  $this->_page_post_data['company_address'],
                            'registered_agent'  =>  0,
                            'registered_lawfirm'=>  0,
                        ];

                        if(!empty($this->_page_post_data['logo'])) {
                            $revise_member['details']['logo'] = $this->_page_post_data['logo'];
                        }

                        if(!empty($this->_page_post_data['company_type'])) {
                            $revise_member['details']['company_type'] = $this->_page_post_data['company_type'];
                        }

                        if(!empty($this->_page_post_data['services'])) {
                            $revise_member['details']['services'] = $this->_page_post_data['services'];
                        }

                        if(!empty($this->_page_post_data['services_country'])) {
                            $revise_member['details']['services_country'] = $this->_page_post_data['services_country'];
                        }

                        if(!empty($this->_page_post_data['registered_agent'])) {
                            if(!empty($this->_page_post_data['agent_id'])) {
                                $revise_member['details']['registered_agent'] = 1;
                                foreach ($this->_page_post_data['agent_id'] as $agent_key => $agent_id) {
                                    $revise_member['agents'][] = [
                                        'id'                    =>  (int)$agent_id,
                                        'full_name'             =>  implode(' ', array_filter([$this->_page_post_data['agent_first_name'][$agent_key], $this->_page_post_data['agent_last_name'][$agent_key]])),
                                        'first_name'            =>  $this->_page_post_data['agent_first_name'][$agent_key],
                                        'last_name'             =>  $this->_page_post_data['agent_last_name'][$agent_key],
                                        'registration_country'  =>  $this->_page_post_data['agent_registration_country'][$agent_key],
                                        'registration_num'      =>  $this->_page_post_data['agent_registration_num'][$agent_key],
                                    ];
                                }
                            }
                            else {
                                $revise_member['details']['registered_agent'] = 0;
                            }
                        }

                        if(!empty($this->_page_post_data['registered_lawfirm'])) {
                            if(!empty($this->_page_post_data['lawfirm_id'])) {
                                $revise_member['details']['registered_lawfirm'] = 1;
                                foreach ($this->_page_post_data['lawfirm_id'] as $lawfirm_key => $lawfirm_id) {
                                    $revise_member['lawfirms'][] = [
                                        'id'                    =>  (int)$lawfirm_id,
                                        'full_name'             =>  $this->_page_post_data['lawfirm_name'][$lawfirm_key],
                                        'registration_country'  =>  $this->_page_post_data['lawfirm_registration_country'][$lawfirm_key],
                                        'registration_num'      =>  $this->_page_post_data['lawfirm_registration_num'][$lawfirm_key],
                                    ];
                                }
                            }
                            else {
                                $revise_member['details']['registered_lawfirm'] = 0;
                            }
                        }
                    }

                    $result = $this->_member_model->doSave($revise_member, $this->postParamValue('member_id', 0));
                    $this->pageResult(
                    [
                        'status'    =>  $this->_member_model->getResultCode(),
                        'message'   =>  $this->_member_model->getResultMessage(),
                        'url'       =>  ((!empty($result))?$this->toURL('members'):'')
                    ], ((!empty($result))?true:false));
                    break;
                case 'delete':
                    $result = $this->_member_model->doDelete($this->postParamValue('id', 0));
                    $this->pageResult(
                    [
                        'status'    =>  $this->_member_model->getResultCode(),
                        'message'   =>  $this->_member_model->getResultMessage()
                    ], ((!empty($result))?true:false));
                    break;
            }
        });

        // page data
        if(!empty($action)) {
            if(in_array($action, ['add', 'edit', 'details'])) {
                // check role if need 
                $target_member = $this->_member_model->getByID($id);
                if($action == 'edit' && empty($target_member)) {
                    $this->doRedirect($this->toURL('members'));
                }

                // set nav
                $this->pageNavigator($this->_page_lang['member_area']);
                $this->pageNavigator($this->_page_lang['member'], $this->toURL('members'));
                if(!empty($action) && in_array($action, ['add', 'edit', 'details'])) {
                    $this->pageNavigator($this->_page_lang[strtolower($action)]);
                }
                
                $list_countries = $this->loadModel('pages', ['table' => 'country'])->getAll($this->_current_lang_index, null, false);
                $list_interest_visas = $this->loadModel('pages', ['table' => 'interest_visa'])->getAll($this->_current_lang_index, null, false);
                $list_interest_topics = $this->loadModel('pages', ['table' => 'interest_topic'])->getAll($this->_current_lang_index, null, false);
                $list_organization_type = $this->loadModel('pages', ['table' => 'organization_type'])->getAll($this->_current_lang_index, null, false);

                // load view
                return $this->pageOptions(
                [
                    'type'  => $this->_member_type,
                    'countries' => $this->optionsToArray($list_countries),
                    'interest_visas' => $this->optionsToArray($list_interest_visas),
                    'interest_topics' => $this->optionsToArray($list_interest_topics),
                    'organization_type' => $this->optionsToArray($list_organization_type)
                ])->pageData(
                [
                    'target_member'             =>  $target_member,
                    'target_member_details'     =>  $this->_member_model->getDetailsByID($target_member['id']),
                    'target_member_agent'       =>  $this->_member_model->getAgentByID($target_member['id']),
                    'target_member_lawfirm'     =>  $this->_member_model->getLawFirmByID($target_member['id'])
                ])->pageView('member_area.member_form');
            }
            else {
                $this->doRedirect($this->toURL('members'));
            }
        }
        else {
            // set nav
            $this->pageNavigator($this->_page_lang['member_area']);
            $this->pageNavigator($this->_page_lang['member'], $this->toURL('members'));

            $list_member = $this->_member_model->getAll(['keywords' => $this->getParamValue('keywords')]);
            if(!empty($list_member['data'])) {
                foreach ($list_member['data'] as $data_key => $data) {
                    if($data['type'] == 1) {
                        $list_member['data'][$data_key]['expiration_date_account'] = '-';
                    }
                } 
            }
            
            // load view
            return $this->templateListView(
            [
                'can_add'               =>  false,
                'can_disabled'          =>  false,
                'can_seq'               =>  false,
                'columns'               => 
                [
                    [
                        'name'          =>  'created_at',            
                        'alias'         =>  'Created At'
                    ],
                    [
                        'name'          =>  'type',            
                        'alias'         =>  $this->_page_lang['member_account.type'],
                        'options'       =>  $this->_member_type
                    ],
                    [
                        'name'          =>  'full_name',            
                        'alias'         =>  $this->_page_lang['member_account.name']
                    ],
                    [
                        'name'          =>  'alias_name',            
                        'alias'         =>  $this->_page_lang['member_account.company_name']
                    ],
                    [
                        'name'          =>  'email',            
                        'alias'         =>  $this->_page_lang['member_account.email']
                    ],
                    [
                        'name'          =>  'expiration_date_account',            
                        'alias'         =>  $this->_page_lang['member_account.expiration_date']
                    ],
                    [
                        'name'          =>  'verified',            
                        'alias'         =>  'Verified',
                        'style'         =>  'width:70px;text-align:center;',
                        'options'       =>  
                        [
                            1           =>  '<i class="fa fa-check-circle" style="font-size:18px;color:mediumaquamarine"></i>',
                            0           =>  '<i class="fa fa-ban" style="font-size:18px;color:lightpink"></i>',
                        ]
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
            ], $list_member);
        }
    }
}
