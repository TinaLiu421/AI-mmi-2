<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Support\CountriesPhoneCodes;

class Account extends WebController {
    
    protected $_show_current_member = null;

    public function __construct($data) {
        parent::__construct($data);
        $show_member_id = $this->getParamValue('uid', 0);
        if(!empty($show_member_id)) {
            $this->_show_current_member = $this->_member_model->getByID($show_member_id);
        }
        else if(!empty($this->_current_member)){
            $this->_show_current_member = $this->_current_member;
        }
        if(empty($this->_show_current_member)) {
            $this->doRedirect($this->toURL('home'));
        }
    }
    
    public function posts() {
        if(!in_array($this->_show_current_member['type'], [2, 3])) {
            $this->doRedirect($this->toURL([$this->_mapping_data['class'],'profile']));
        }
        
        // load view
        $list_countries = $this->loadModel('pages', ['table' => 'country'])->getAll($this->_current_lang_index, null, false);
        $this->pageOptions(
        [
            'countries' => $this->optionsToArray($list_countries)
        ]);
        
        return $this->pageData([
            'is_readonly'               =>  (md5(json_encode($this->_show_current_member))!=md5(json_encode($this->_current_member))),
            'show_current_member'       =>  $this->_show_current_member,
            'current_member_details'    =>  $this->_member_model->getDetailsByID($this->_show_current_member['id']),
            'current_member_agent'      =>  $this->_member_model->getAgentByID($this->_show_current_member['id']),
            'current_member_lawfirm'    =>  $this->_member_model->getLawFirmByID($this->_show_current_member['id'])
        ])->pageView();
    }
    
    public function posts_publish($id = 0) {
        // post event
        $this->pageAction(function() {
            // do checking
            $validator = Validator::make($this->_page_post_data, 
            [
                'title'     =>  'required',
                'content'   =>  'required'
            ]);
            
            if(!$validator->fails()) {
                if(strtotime($this->_current_member['expiration_date_account']) >= strtotime($this->_today_date)) {
                    $this->_page_post_data['member_id'] = $this->_current_member['id'];
                    if($this->loadModel('posts')->doSave($this->_page_post_data, $this->postParamValue('posts_id', 0))) {
                        $this->pageResult(
                        [
                            'status'    =>  200,
                            'message'   =>  'OK'
                        ]);
                    }
                    else {
                        $this->pageResult(
                        [
                            'status'    =>  $this->_user_model->getResultCode(),
                            'message'   =>  $this->_user_model->getResultMessage()
                        ]);
                    }
                }
                else {
                    $this->pageResult(
                    [
                        'status'    =>  403,
                        'message'   =>  $this->_page_lang['please_renew_ac'],
                        'url'       =>  $this->toURL('account_renew/payment/'.$this->_current_member['type'])
                    ]);
                }
            }
            else {
                $this->pageResult(
                [
                    'status'    =>  400,
                    'message'   =>  $this->_page_lang['bad_request']
                ]);
            }
        });
        
        // load view
        $list_countries = $this->loadModel('pages', ['table' => 'country'])->getAll($this->_current_lang_index, null, false);
        $this->pageOptions(
        [
            'countries' => $this->optionsToArray($list_countries)
        ]);

        return $this->pageData(
        [
            'show_current_member'       =>  $this->_current_member,
            'posts'                     =>  $this->loadModel('posts')->getByID($id)
        ])->pageView('', false, false);
    }
    
    public function delete_post($id = 0) {
        if($this->loadModel('posts')->deleteSelfPost($id, $this->_current_member['id'])) {
            $this->pageResult(
            [
                'status'    =>  200,
                'message'   =>  'OK'
            ]);
        }
        else {
            $this->pageResult(
            [
                'status'    =>  $this->_user_model->getResultCode(),
                'message'   =>  $this->_user_model->getResultMessage()
            ]);
        }
    }

    public function profile() {
        // post event
        $this->pageAction(function() {
            // do checking
            if((int)$this->_current_member['type'] == 1) {
                $validator = Validator::make($this->_page_post_data, ((!empty($this->_page_post_data['password']))?
                [
                    'first_name'        =>  'required',
                    'last_name'         =>  'required',
                    'email'             =>  'required|email',
                    'password'          =>  'regex:/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}$/',
                    'repeat_password'   =>  'same:repeat_password',
                    'interested_visa'   =>  'required',
                    'interested_topic'  =>  'required'
                ]:
                [
                    'first_name'        =>  'required',
                    'last_name'         =>  'required',
                    'email'             =>  'required|email',
                    'interested_visa'   =>  'required',
                    'interested_topic'  =>  'required'
                ]));
            }
            else if((int)$this->_current_member['type'] == 2) {
                $validator = Validator::make($this->_page_post_data, ((!empty($this->_page_post_data['password']))?
                [
                    'company_name'      =>  'required',
                    'company_website'   =>  'required',
                    'company_address'   =>  'required',
                    'first_name'        =>  'required',
                    'last_name'         =>  'required',
                    'email'             =>  'required|email',
                    'telephone_code'    =>  'required',
                    'telephone_num'     =>  'required',
                    'password'          =>  'regex:/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}$/',
                    'repeat_password'   =>  'same:repeat_password'
                ]:
                [
                    'company_name'      =>  'required',
                    'company_website'   =>  'required',
                    'company_address'   =>  'required',
                    'first_name'        =>  'required',
                    'last_name'         =>  'required',
                    'email'             =>  'required|email',
                    'telephone_code'    =>  'required',
                    'telephone_num'     =>  'required'
                ]));
            }
            else if((int)$this->_current_member['type'] == 3) {
                $validator = Validator::make($this->_page_post_data, ((!empty($this->_page_post_data['password']))?
                [
                    'company_type'      =>  'required',
                    'company_name'      =>  'required',
                    'company_website'   =>  'required',
                    'company_address'   =>  'required',
                    'first_name'        =>  'required',
                    'last_name'         =>  'required',
                    'email'             =>  'required|email',
                    'telephone_code'    =>  'required',
                    'telephone_num'     =>  'required',
                    'password'          =>  'regex:/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}$/',
                    'repeat_password'   =>  'same:repeat_password'
                ]:
                [
                    'company_type'      =>  'required',
                    'company_name'      =>  'required',
                    'company_website'   =>  'required',
                    'company_address'   =>  'required',
                    'first_name'        =>  'required',
                    'last_name'         =>  'required',
                    'email'             =>  'required|email',
                    'telephone_code'    =>  'required',
                    'telephone_num'     =>  'required',
                ]));
            }
            
            // save data into session
            if(!$validator->fails()) {
                // tyr to upload logo
                if(!empty($file = \Illuminate\Support\Facades\Request::file('mylogo'))) {
                    // get file info
                    $file_ori_name = $file->getClientOriginalName();
                    $file_extension = $file->getClientOriginalExtension();
                    $file_size = $file->getSize();
                    $file_name = md5(uniqid(rand())).'.'. strtolower($file_extension);

                    // upload folder
                    $location = ('upload/member_logo');
                    if(!file_exists(public_path($location))){
                        @mkdir(public_path($location), 0755, true);
                    }

                    // move & resize
                    if($file->move(public_path($location), $file_name)) {
                        if(file_exists(public_path($location.'/'.$file_name))) {
                            \Intervention\Image\Facades\Image::make(public_path($location.'/'.$file_name))->resize(400, 400, function ($constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            })->save(public_path($location.'/'.$file_name));
                        }
                        $this->_page_post_data['logo'] = $file_name;
                    }
                }

                $revise_member =
                [
                    'full_name'             =>  implode(' ', array_filter([$this->_page_post_data['first_name'], $this->_page_post_data['last_name']])),
                    'first_name'            =>  $this->_page_post_data['first_name'],
                    'last_name'             =>  $this->_page_post_data['last_name'],
                    'email'                 =>  $this->_page_post_data['email'],
                    'remark'                =>  $this->_page_post_data['remark'],
                    'countries_serving'     =>  ((!empty($this->_page_post_data['countries_serving']))?$this->_page_post_data['countries_serving']:''),
                    'details'               =>  [],
                    'agents'                =>  [],
                    'lawfirms'              =>  [],
                    'business_licenses'     =>  []
                ];

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

                if(in_array((int)$this->_current_member['type'], [2,3])) {
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
                        $revise_member['details']['services_country'] = json_encode($this->_page_post_data['services_country']);
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

                    // business licenses
                    if(!empty($this->_page_post_data['license_id'])) {
                        foreach ($this->_page_post_data['license_id'] as $license_key => $license_id) {
                            $revise_member['business_licenses'][] = [
                                'id'                    =>  (int)$license_id,
                                'license_country'       =>  $this->_page_post_data['license_country'][$license_key],
                                'issuing_authority'     =>  $this->_page_post_data['issuing_authority'][$license_key],
                                'type_of_registration'  =>  $this->_page_post_data['type_of_registration'][$license_key],
                                'registration_number'   =>  $this->_page_post_data['registration_number'][$license_key],
                            ];
                        }
                    }
                }

                if($this->_member_model->doSave($revise_member, $this->_current_member['id'])) {
                    $this->pageResult(
                    [
                        'status'    =>  200,
                        'url'       =>  $this->toURL([$this->_mapping_data['class'],'profile'])
                    ]);
                }
                else {
                    $this->pageResult(
                    [
                        'status'    =>  $this->_user_model->getResultCode(),
                        'message'   =>  $this->_user_model->getResultMessage()
                    ]);
                }
            }
            else {
                $this->pageResult(
                [
                    'status'    =>  400,
                    'message'   =>  $this->_page_lang['bad_request']
                ]);
            }
        });
        
        // load view
        $list_interest_visas = $this->loadModel('pages', ['table' => 'interest_visa'])->getAll($this->_current_lang_index, null, false);
        $list_interest_topics = $this->loadModel('pages', ['table' => 'interest_topic'])->getAll($this->_current_lang_index, null, false);
        $list_organization_type = $this->loadModel('pages', ['table' => 'organization_type'])->getAll($this->_current_lang_index, null, false);

        $this->pageOptions(
        [
            'countries' => CountriesPhoneCodes::getCountries(),
            'phone_codes' => CountriesPhoneCodes::getPhoneCodes(),
            'country_phone_map' => CountriesPhoneCodes::getCountryPhoneMap(),
            'interest_visas' => $this->optionsToArray($list_interest_visas),
            'interest_topics' => $this->optionsToArray($list_interest_topics),
            'organization_type' => $this->optionsToArray($list_organization_type)
        ]);
        
    // ✅ search subscriptions table
        $memberId = $this->_show_current_member['id'];
        $currentSub = DB::table('subscriptions as s')
            ->join('plans as p','p.id','=','s.plan_id')
            ->where('s.member_id', $memberId)
            ->where('s.status','active')
            ->where(function($q){
                $q->whereNull('s.ends_at')->orWhere('s.ends_at','>', now());
            })
            ->orderByDesc('s.started_at')
            ->first(['p.name as plan_name','s.ends_at']);

        // write the finals into _show_current_member
        $this->_show_current_member['subscription_name']   = $currentSub->plan_name ?? 'Free Plan';
        $this->_show_current_member['subscription_expiry'] = $currentSub?->ends_at;

        return $this->pageData([
            'is_readonly'               => (md5(json_encode($this->_show_current_member))!=md5(json_encode($this->_current_member))),
            'show_current_member'       => $this->_show_current_member,
            'current_member_details'    => $this->_member_model->getDetailsByID($this->_show_current_member['id']),
            'current_member_agent'      => $this->_member_model->getAgentByID($this->_show_current_member['id']),
            'current_member_lawfirm'    => $this->_member_model->getLawFirmByID($this->_show_current_member['id']),
            'current_member_business_license' => $this->_member_model->getBusinessLicenseByID($this->_show_current_member['id'])
        ])->pageView();
    }
    
    public function myavatar() {
        // post event
        $this->pageAction(function() {
            // tyr to upload logo
            if(!empty($file = \Illuminate\Support\Facades\Request::file('myavatar'))) {
                // get file info
                $file_ori_name = $file->getClientOriginalName();
                $file_extension = $file->getClientOriginalExtension();
                $file_size = $file->getSize();
                $file_name = md5(uniqid(rand())).'.'. strtolower($file_extension);

                // upload folder
                $location = ('upload/member_avatar');
                if(!file_exists(public_path($location))){
                    @mkdir(public_path($location), 0755, true);
                }

                // move & resize
                if($file->move(public_path($location), $file_name)) {
                    if(file_exists(public_path($location.'/'.$file_name))) {
                        \Intervention\Image\Facades\Image::make(public_path($location.'/'.$file_name))->resize(400, 400, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        })->save(public_path($location.'/'.$file_name));
                    }
                    
                    // update
                    $this->_member_model->renewAvatar($this->_current_member['id'], $file_name);
                }
            }
        });
    }
    
    public function myalias() {
        // post event
        $this->pageAction(function() {
            // tyr to upload logo
            if(!empty($file = \Illuminate\Support\Facades\Request::file('mycoverphoto'))) {
                // get file info
                $file_ori_name = $file->getClientOriginalName();
                $file_extension = $file->getClientOriginalExtension();
                $file_size = $file->getSize();
                $file_name = md5(uniqid(rand())).'.'. strtolower($file_extension);

                // upload folder
                $location = ('upload/member_coverphoto');
                if(!file_exists(public_path($location))){
                    @mkdir(public_path($location), 0755, true);
                }

                // move & resize
                if($file->move(public_path($location), $file_name)) {
                    if(file_exists(public_path($location.'/'.$file_name))) {
                        \Intervention\Image\Facades\Image::make(public_path($location.'/'.$file_name))->resize(1200, 300, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        })->save(public_path($location.'/'.$file_name));
                    }
                    
                    // update
                    $this->_member_model->renewAlias($this->_current_member['id'], [
                        'coverphoto' => $file_name,
                        'alias_name'    => $this->_page_post_data['alias_name']
                    ]);
                }
            }
            else {
                // update
                $this->_member_model->renewAlias($this->_current_member['id'], [
                    'alias_name'    => $this->_page_post_data['alias_name']
                ]);
            }
            
            $this->pageResult(
            [
                'status'    =>  200
            ]);
        });
    }
    
}