<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Support\CountriesPhoneCodes;
use App\Support\DestinationsServing;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeCheckoutSession;

class Account extends WebController {
    
    protected $_show_current_member = null;

    public function __construct($data) {
        parent::__construct($data);
        if (isset($this->_current_member['countries_serving'])) {
            $this->_current_member['countries_serving'] = DestinationsServing::fromStorage($this->_current_member['countries_serving']);
        }

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

        if (isset($this->_show_current_member['countries_serving'])) {
            $this->_show_current_member['countries_serving'] = DestinationsServing::fromStorage($this->_show_current_member['countries_serving']);
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

        $isSelf = isset($this->_current_member['id']) 
                && (int)$this->_show_current_member['id'] === (int)$this->_current_member['id'];

        // Load institution profile for education institutions
        $institution_profile = null;
        if ((int)$this->_show_current_member['type'] === 3) {
            $det = DB::table('member_details')->where('member_id', $this->_show_current_member['id'])->first();
            if ($det && (int)$det->institution_type === 2) {
                $institution_profile = DB::table('institution_profiles')
                    ->where('member_id', $this->_show_current_member['id'])
                    ->first();
            }
        }

        return $this->pageData([
            'is_readonly' => !$isSelf,
            'show_current_member'       =>  $this->_show_current_member,
            'current_member_details'    =>  $this->_member_model->getDetailsByID($this->_show_current_member['id']),
            'current_member_agent'      =>  $this->_member_model->getAgentByID($this->_show_current_member['id']),
            'current_member_lawfirm'    =>  $this->_member_model->getLawFirmByID($this->_show_current_member['id']),
            'institution_profile'       =>  $institution_profile ? (array)$institution_profile : null,
        ])->pageView();
    }
    
    public function posts_publish($id = 0) {
        // post event
        $this->pageAction(function() {
            // do checking
            $validator = Validator::make($this->_page_post_data, 
            [
                'title'     =>  'required',
                'content'   =>  'required',
                'sector'    =>  'required|in:study,migration'
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
            // Detect education institution for type=3 members
            $_is_edu_institution = false;
            if ((int)$this->_current_member['type'] === 3) {
                $det = DB::table('member_details')->where('member_id', $this->_current_member['id'])->first();
                $_is_edu_institution = $det && (int)$det->institution_type === 2;
            }
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
                if($_is_edu_institution) {
                    // Education institution: no company_type or company_address required
                    $validator = Validator::make($this->_page_post_data, ((!empty($this->_page_post_data['password']))?
                    [
                        'company_name'      =>  'required',
                        'company_website'   =>  'required',
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
                        'first_name'        =>  'required',
                        'last_name'         =>  'required',
                        'email'             =>  'required|email',
                        'telephone_code'    =>  'required',
                        'telephone_num'     =>  'required',
                    ]));
                } else {
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

                $destinationsServing = DestinationsServing::toStorage($this->postParamValue('countries_serving', []));

                $revise_member =
                [
                    'full_name'             =>  implode(' ', array_filter([$this->_page_post_data['first_name'], $this->_page_post_data['last_name']])),
                    'first_name'            =>  $this->_page_post_data['first_name'],
                    'last_name'             =>  $this->_page_post_data['last_name'],
                    'email'                 =>  $this->_page_post_data['email'],
                    'remark'                =>  $this->_page_post_data['remark'],
                    'countries_serving'     =>  $destinationsServing,
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
                        'company_address'   =>  $this->_page_post_data['company_address'] ?? '',
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
            'organization_type' => $this->optionsToArray($list_organization_type),
            'destinations_serving' => DestinationsServing::options()
        ]);
        
    // ✅ search subscriptions table
        $memberId = $this->_show_current_member['id'];
        try {
            $currentSub = DB::table('subscriptions as s')
                ->join('plans as p','p.id','=','s.plan_id')
                ->where('s.member_id', $memberId)
                ->where('s.status','active')
                ->where(function($q){
                    $q->whereNull('s.ends_at')->orWhere('s.ends_at','>', now());
                })
                ->orderByDesc('s.started_at')
                ->first(['p.name as plan_name','p.code as plan_code','s.ends_at','s.stripe_subscription_id','s.cancel_at_period_end']);

            // write the finals into _show_current_member
            $this->_show_current_member['subscription_name']   = ($currentSub !== null) ? $currentSub->plan_name : 'Free Plan';
            $this->_show_current_member['subscription_expiry'] = ($currentSub !== null) ? $currentSub->ends_at : null;
            $this->_show_current_member['subscription_plan_code']      = ($currentSub !== null) ? $currentSub->plan_code : null;
            $this->_show_current_member['subscription_stripe_sub_id']  = ($currentSub !== null) ? $currentSub->stripe_subscription_id : null;
            $this->_show_current_member['subscription_cancel_at_period_end'] = ($currentSub !== null) ? (bool)$currentSub->cancel_at_period_end : false;
        } catch (\Throwable $e) {
            Log::warning('account.profile: subscription query failed', ['member_id' => $memberId, 'error' => $e->getMessage()]);
            $this->_show_current_member['subscription_name']                 = 'Free Plan';
            $this->_show_current_member['subscription_expiry']               = null;
            $this->_show_current_member['subscription_plan_code']            = null;
            $this->_show_current_member['subscription_stripe_sub_id']        = null;
            $this->_show_current_member['subscription_cancel_at_period_end'] = false;
        }

        $isSelf = isset($this->_current_member['id']) 
            && (int)$this->_show_current_member['id'] === (int)$this->_current_member['id'];

        // Load institution profile for education institutions (type=3, institution_type=2)
        $institution_profile = null;
        if ((int)$this->_show_current_member['type'] === 3) {
            $det = DB::table('member_details')->where('member_id', $this->_show_current_member['id'])->first();
            if ($det && (int)$det->institution_type === 2) {
                $institution_profile = DB::table('institution_profiles')
                    ->where('member_id', $this->_show_current_member['id'])
                    ->first();
            }
        }

        return $this->pageData([
            'is_readonly' => !$isSelf,
            'show_current_member'       => $this->_show_current_member,
            'current_member_details'    => $this->_member_model->getDetailsByID($this->_show_current_member['id']),
            'current_member_agent'      => $this->_member_model->getAgentByID($this->_show_current_member['id']),
            'current_member_lawfirm'    => $this->_member_model->getLawFirmByID($this->_show_current_member['id']),
            'current_member_business_license' => $this->_member_model->getBusinessLicenseByID($this->_show_current_member['id']),
            'institution_profile'       => $institution_profile ? (array)$institution_profile : null,
        ])->pageView();
    }

    // ── Education institution student list pages ─────────────────────────────

    private function _eduStudentListPage(string $listType) {
        if (!in_array($this->_show_current_member['type'], [2, 3])) {
            $this->doRedirect($this->toURL([$this->_mapping_data['class'], 'profile']));
        }
        $institution_profile = null;
        $det = DB::table('member_details')->where('member_id', $this->_show_current_member['id'])->first();
        if (!$det || (int)$det->institution_type !== 2) {
            $this->doRedirect($this->toURL([$this->_mapping_data['class'], 'posts']));
        }
        $institution_profile = DB::table('institution_profiles')
            ->where('member_id', $this->_show_current_member['id'])
            ->first();

        $isSelf = isset($this->_current_member['id'])
            && (int)$this->_show_current_member['id'] === (int)$this->_current_member['id'];

        return $this->pageData([
            'is_readonly'           => !$isSelf,
            'list_type'             => $listType,
            'show_current_member'   => $this->_show_current_member,
            'current_member_details'=> $this->_member_model->getDetailsByID($this->_show_current_member['id']),
            'current_member_agent'  => $this->_member_model->getAgentByID($this->_show_current_member['id']),
            'current_member_lawfirm'=> $this->_member_model->getLawFirmByID($this->_show_current_member['id']),
            'institution_profile'   => $institution_profile ? (array)$institution_profile : null,
            'students'              => [], // placeholder — to be populated when student-side feature is built
        ])->pageView('account_students');
    }

    public function students_matched() {
        return $this->_eduStudentListPage('matched');
    }

    public function students_applied() {
        return $this->_eduStudentListPage('applied');
    }

    public function students_accepted() {
        return $this->_eduStudentListPage('accepted');
    }

    // ────────────────────────────────────────────────────────────────────────

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

    // ── Spotlight subscription ────────────────────────────────────────────

    public function spotlight()
    {
        if (!in_array((int)$this->_show_current_member['type'], [2, 3])) {
            $this->doRedirect($this->toURL([$this->_mapping_data['class'], 'posts']));
        }

        $sq = $this->loadModel('spotlight_queue');

        // Lazy-expire finished slots and activate next queued ones
        $sq->expireActive();
        $sq->activateNext();

        $member_id = (int)$this->_show_current_member['id'];
        $isSelf    = isset($this->_current_member['id'])
                  && (int)$this->_current_member['id'] === $member_id;

        // My published posts (all, unfiltered — we show status in the UI)
        $my_posts_result = $this->loadModel('posts')->getAll([
            'member_id'      => $member_id,
            'show_page_size' => 50,
        ]);
        $my_posts = $my_posts_result['data'] ?? [];

        // Extract text titles
        foreach ($my_posts as &$p) {
            if (empty($p['title'])) {
                $p['title'] = mb_substr($this->toPlainText($p['content']), 0, 60);
            }
        }
        unset($p);

        // My queue entries (active + queued + pending)
        $my_queue = $sq->getQueuedForMember($member_id);

        // IDs already in active/queued/pending (so we don't offer them in basket)
        $occupied_post_ids = array_column($my_queue, 'posts_id');

        // Posts available for purchase (not already in spotlight)
        $available_posts = array_values(array_filter($my_posts, function ($p) use ($occupied_post_ids) {
            return !in_array((int)$p['id'], array_map('intval', $occupied_post_ids));
        }));

        // How many slots are globally free right now
        $active_count  = $sq->getActiveCount();
        $free_slots    = max(0, \App\Models\Spotlight_Queue::SLOT_LIMIT - $active_count);

        // Schedule preview for up to 3 new purchases
        $schedule_preview = $sq->getSchedulePreview(min(3, max(1, count($available_posts))));

        // Flash messages from redirect
        $payment_status = request()->query('payment', '');

        $institution_profile = null;
        if ((int)$this->_show_current_member['type'] === 3) {
            $det = DB::table('member_details')->where('member_id', $member_id)->first();
            if ($det && (int)$det->institution_type === 2) {
                $institution_profile = DB::table('institution_profiles')
                    ->where('member_id', $member_id)
                    ->first();
            }
        }

        return $this->pageData([
            'is_readonly'           => !$isSelf,
            'show_current_member'   => $this->_show_current_member,
            'current_member_details'=> $this->_member_model->getDetailsByID($member_id),
            'current_member_agent'  => $this->_member_model->getAgentByID($member_id),
            'current_member_lawfirm'=> $this->_member_model->getLawFirmByID($member_id),
            'institution_profile'   => $institution_profile ? (array)$institution_profile : null,
            'my_queue'              => $my_queue,
            'available_posts'       => $available_posts,
            'total_my_posts'        => count($my_posts),
            'active_count'          => $active_count,
            'free_slots'            => $free_slots,
            'schedule_preview'      => $schedule_preview,
            'slot_price_cents'      => 10000,  // $100 in cents
            'payment_status'        => $payment_status,
        ])->pageView('account_spotlight', false, false);
    }

    public function spotlight_cancel()
    {
        $member = $this->_current_member;
        if (empty($member)) {
            return redirect()->to($this->toURL('account_login'));
        }

        $sq_id = (int)request()->input('sq_id', 0);
        if ($sq_id < 1) {
            return redirect()->to($this->toURL('account/spotlight'));
        }

        $sq = $this->loadModel('spotlight_queue');
        $sq->cancelPending((int)$member['id'], $sq_id);

        return redirect()->to($this->toURL('account/spotlight'))
            ->with('info', 'Spotlight entry cancelled. The post is now available to select again.');
    }

    public function spotlight_retry()
    {
        $member = $this->_current_member;
        if (empty($member)) {
            return redirect()->to($this->toURL('account_login'));
        }

        if (!in_array((int)$member['type'], [2, 3])) {
            return redirect()->to($this->toURL('account/spotlight'));
        }

        $sq_id = (int)request()->input('sq_id', 0);
        if ($sq_id < 1) {
            return redirect()->to($this->toURL('account/spotlight'));
        }

        $sq    = $this->loadModel('spotlight_queue');
        $entry = $sq->getPendingEntry((int)$member['id'], $sq_id);

        if (!$entry) {
            return redirect()->to($this->toURL('account/spotlight'))
                ->with('error', 'Entry not found or already paid.');
        }

        // Cancel the old pending row first
        $sq->cancelPending((int)$member['id'], $sq_id);

        $post_id = (int)$entry['posts_id'];

        // Verify the post still belongs to the member and is published
        $post = DB::table('member_posts')
            ->where('id', $post_id)
            ->where('member_id', $member['id'])
            ->where('status', '>', 0)
            ->first();

        if (!$post) {
            return redirect()->to($this->toURL('account/spotlight'))
                ->with('error', 'Post not found or unpublished.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $success_url = $this->toURL('account/spotlight') . '?payment=success';
            $cancel_url  = $this->toURL('account/spotlight') . '?payment=cancel';

            $session = StripeCheckoutSession::create([
                'mode'       => 'payment',
                'line_items' => [[
                    'price'    => \App\Models\Spotlight_Queue::PRICE_ID,
                    'quantity' => 1,
                ]],
                'success_url'         => $success_url,
                'cancel_url'          => $cancel_url,
                'client_reference_id' => (string)$member['id'],
                'customer_email'      => (string)$member['email'],
                'metadata'            => [
                    'member_id' => (string)$member['id'],
                    'post_ids'  => (string)$post_id,
                    'source'    => 'spotlight',
                ],
            ]);

            $sq->createPending((int)$member['id'], $post_id, $session->id);

            return redirect()->away($session->url);

        } catch (\Throwable $e) {
            Log::error('spotlight_retry failed', [
                'member_id' => $member['id'],
                'sq_id'     => $sq_id,
                'post_id'   => $post_id,
                'error'     => $e->getMessage(),
            ]);
            return redirect()->to($this->toURL('account/spotlight'))
                ->with('error', 'Unable to start payment right now. Please try again.');
        }
    }

    public function spotlight_checkout()
    {
        $member = $this->_current_member;
        if (empty($member)) {
            return redirect()->to($this->toURL('account_login'));
        }

        if (!in_array((int)$member['type'], [2, 3])) {
            return redirect()->to($this->toURL('account/spotlight'));
        }

        // Validate post_ids (comma-separated or array)
        $raw_ids = request()->input('post_ids', '');
        if (is_array($raw_ids)) {
            $post_ids = array_map('intval', $raw_ids);
        } else {
            $post_ids = array_map('intval', array_filter(explode(',', (string)$raw_ids)));
        }

        // Deduplicate and constrain to max 3
        $post_ids = array_values(array_unique($post_ids));
        $post_ids = array_slice($post_ids, 0, 3);

        if (empty($post_ids)) {
            return redirect()->to($this->toURL('account/spotlight'))->with('error', 'No posts selected.');
        }

        $sq = $this->loadModel('spotlight_queue');

        // Validate: each post must belong to the member and not already spotlighted
        $valid_ids = [];
        foreach ($post_ids as $pid) {
            $post = DB::table('member_posts')
                ->where('id', $pid)
                ->where('member_id', $member['id'])
                ->where('status', '>', 0)
                ->first();
            if (!$post) continue;
            if ($sq->isAlreadySpotlighted($pid)) continue;
            $valid_ids[] = $pid;
        }

        if (empty($valid_ids)) {
            return redirect()->to($this->toURL('account/spotlight'))->with('error', 'Selected posts are not eligible for spotlight.');
        }

        $qty = count($valid_ids);

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $success_url = $this->toURL('account/spotlight') . '?payment=success';
            $cancel_url  = $this->toURL('account/spotlight') . '?payment=cancel';

            $session = StripeCheckoutSession::create([
                'mode'       => 'payment',
                'line_items' => [[
                    'price'    => \App\Models\Spotlight_Queue::PRICE_ID,
                    'quantity' => $qty,
                ]],
                'success_url'          => $success_url,
                'cancel_url'           => $cancel_url,
                'client_reference_id'  => (string)$member['id'],
                'customer_email'       => (string)$member['email'],
                'metadata'             => [
                    'member_id' => (string)$member['id'],
                    'post_ids'  => implode(',', $valid_ids),
                    'source'    => 'spotlight',
                ],
            ]);

            // Create pending_payment records (one per post)
            foreach ($valid_ids as $pid) {
                $sq->createPending((int)$member['id'], $pid, $session->id);
            }

            return redirect()->away($session->url);

        } catch (\Throwable $e) {
            Log::error('spotlight_checkout failed', [
                'member_id' => $member['id'],
                'post_ids'  => $valid_ids,
                'error'     => $e->getMessage(),
            ]);
            return redirect()->to($this->toURL('account/spotlight'))->with('error', 'Unable to start payment right now. Please try again.');
        }
    }

}
