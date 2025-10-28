<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Support\CountriesPhoneCodes;

class Account_Registration extends WebController {
    
    public function __construct($data) {
        parent::__construct($data);
        if(!empty($this->_current_member)) {
            $this->doRedirect($this->toURL('home'));
        }
    }
    
    public function index() {
        $page_data = $this->loadModel('pages')->getByID(5, $this->_current_lang_index);
        
        // set meta
        $this->pageMeta(
        [
            'title'         =>  (!empty($page_data['meta_title']))?$page_data['meta_title']:$page_data['title'],
            'description'   =>  $page_data['meta_description'],
            'image'         =>  $page_data['meta_image']
        ]);
        
        // get list
        $list_account_plans = $this->loadModel('pages', ['table' => 'plan_account'])->getAll($this->_current_lang_index, null, false);
        
        // load view
        return $this->pageData(
        [
            'list_account_plans'    =>  $list_account_plans,
            'details'               =>  $page_data
        ])->pageView();
    }
    
    public function individual($parameter = '') {
        $parameter = strtolower($parameter);
        
        // post event
        $this->pageAction(function($parameter) {
            if(empty($parameter)) {
                // do checking
                $validator = Validator::make($this->_page_post_data, 
                [
                    'first_name'        =>  'required',
                    'last_name'         =>  'required',
                    'email'             =>  'required|email',
                    'password'          =>  'required|regex:/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}$/',
                    'repeat_password'   =>  'required|same:repeat_password'
                ]);
                
                // save data into session
                if(!$validator->fails()) {
                    if(empty($this->_member_model->getByEmail($this->_page_post_data['email']))) {
                        // Create account directly without preference step
                        $new_member =
                        [
                            'method'                =>  ((!empty($this->_page_post_data['method']))?max(1, $this->_page_post_data['method']):0),
                            'type'                  =>  1,
                            'full_name'             =>  implode(' ', array_filter([$this->_page_post_data['first_name'], $this->_page_post_data['last_name']])),
                            'first_name'            =>  $this->_page_post_data['first_name'],
                            'last_name'             =>  $this->_page_post_data['last_name'],
                            'email'                 =>  $this->_page_post_data['email'],
                            'password'              =>  $this->_page_post_data['password'],
                            'repeat_password'       =>  $this->_page_post_data['repeat_password'],
                            'migration_destination' =>  0,
                            'interested_visa'       =>  0,
                            'interested_topic'      =>  '',
                            'verified_token'        =>  md5($this->_page_post_data['email'].'@'.md5(uniqid(rand()))),
                            'verified'              =>  0,
                            'third_party_token'     =>  ((!empty($this->_page_post_data['third_party_token']))?$this->_page_post_data['third_party_token']:'')
                        ];
                        $new_member['alias_name'] = $new_member['full_name'];
                        if($new_member['method'] > 1) {
                            $new_member['verified'] =  1;
                        }

                        if($result = $this->_member_model->doSave($new_member, 0, true)) {
                            // email verification if need
                            if((int)$new_member['method'] == 1) {
                                $link = $this->toURL('account_registration/verification').'?token='.$new_member['verified_token'];
                                $subject = 'Email Verification';
                                $body = '<p>Hello '.$new_member['full_name'].',</p>';
                                $body.= '<p>You registered an account on AI-mmi, before being able to use your account you need to verify that this is your email address by clicking below link:';
                                $body.= '<p><a href="'.$link.'" target="_blank">'.$link.'</a></p>';
                                $body.= '<p>Kind Regards';
                                $this->sendEmail($new_member['email'], $subject, $body);
                                $this->pageResult(
                                [
                                    'status'    =>  200,
                                    'message'   =>  '<strong>'.$this->_page_lang['registration_success'].'</strong><br/>'.str_replace('{email}', $new_member['email'], $this->_page_lang['email_verification_link']),
                                    'url'       =>  $this->toURL('account_login')
                                ]);
                            }
                            else {
                                $this->pageResult(
                                [
                                    'status'    =>  200,
                                    'message'   =>  $this->_page_lang['registration_success'],
                                    'url'       =>  $this->toURL('account_login')
                                ]);
                            }
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
                            'message'   =>  $this->_page_lang['duplicate_email']
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
            }
            // UNUSED: Preference step has been removed - account is created directly on first submission
            // else {
            //     // do checking
            //     $validator = Validator::make($this->_page_post_data,
            //     [
            //         'interested_visa'   =>  'required',
            //         'interested_topic'  =>  'required'
            //     ]);

            //     if(!$validator->fails()) {
            //         $this->setSession(['temp_individual_account_preference' => $this->_page_post_data]);
            //         $temp_individual_account = $this->getSession('temp_individual_account');
            //         $temp_individual_account_preference = $this->getSession('temp_individual_account_preference');

            //         // try to save into db
            //         if(!empty($temp_individual_account) && !empty($temp_individual_account_preference)) {
            //             $new_member =
            //             [
            //                 'method'                =>  ((!empty($temp_individual_account['method']))?max(1, $temp_individual_account['method']):0),
            //                 'type'                  =>  1,
            //                 'full_name'             =>  implode(' ', array_filter([$temp_individual_account['first_name'], $temp_individual_account['last_name']])),
            //                 'first_name'            =>  $temp_individual_account['first_name'],
            //                 'last_name'             =>  $temp_individual_account['last_name'],
            //                 'email'                 =>  $temp_individual_account['email'],
            //                 'password'              =>  $temp_individual_account['password'],
            //                 'repeat_password'       =>  $temp_individual_account['repeat_password'],
            //                 'migration_destination' =>  ((!empty($temp_individual_account_preference['migration_destination']))?(int)$temp_individual_account_preference['migration_destination']:0),
            //                 'interested_visa'       =>  ((!empty($temp_individual_account_preference['interested_visa']))?(int)$temp_individual_account_preference['interested_visa']:0),
            //                 'interested_topic'      =>  $temp_individual_account_preference['interested_topic'],
            //                 'verified_token'        =>  md5($temp_individual_account['email'].'@'.md5(uniqid(rand()))),
            //                 'verified'              =>  0,
            //                 'third_party_token'     =>  ((!empty($temp_individual_account['third_party_token']))?$temp_individual_account['third_party_token']:'')
            //             ];
            //             $new_member['alias_name'] = $new_member['full_name'];
            //             if($new_member['method'] > 1) {
            //                 $new_member['verified'] =  1;
            //             }

            //             if($result = $this->_member_model->doSave($new_member, 0, true)) {
            //                 $this->delSession('temp_individual_account');
            //                 $this->delSession('temp_individual_account_preference');

            //                 // email verification if need
            //                 if((int)$new_member['method'] == 1) {
            //                     $link = $this->toURL('account_registration/verification').'?token='.$new_member['verified_token'];
            //                     $subject = 'Email Verification';
            //                     $body = '<p>Hello '.$new_member['full_name'].',</p>';
            //                     $body.= '<p>You registered an account on AI-mmi, before being able to use your account you need to verify that this is your email address by clicking below link:';
            //                     $body.= '<p><a href="'.$link.'" target="_blank">'.$link.'</a></p>';
            //                     $body.= '<p>Kind Regards';
            //                     $this->sendEmail($new_member['email'], $subject, $body);
            //                     $this->pageResult(
            //                     [
            //                         'status'    =>  200,
            //                         'message'   =>  '<strong>'.$this->_page_lang['registration_success'].'</strong><br/>'.str_replace('{email}', $new_member['email'], $this->_page_lang['email_verification_link']),
            //                         'url'       =>  $this->toURL('account_login')
            //                     ]);
            //                 }
            //                 else {
            //                     $this->pageResult(
            //                     [
            //                         'status'    =>  200,
            //                         'message'   =>  $this->_page_lang['registration_success'],
            //                         'url'       =>  $this->toURL('account_login')
            //                     ]);
            //                 }
            //             }
            //             else {
            //                 $this->pageResult(
            //                 [
            //                     'status'    =>  $this->_user_model->getResultCode(),
            //                     'message'   =>  $this->_user_model->getResultMessage()
            //                 ]);
            //             }
            //         }
            //         else {
            //             $this->pageResult(
            //             [
            //                 'status'    =>  400,
            //                 'message'   =>  $this->_page_lang['bad_request']
            //             ]);
            //         }
            //     }
            //     else {
            //         $this->pageResult(
            //         [
            //             'status'    =>  400,
            //             'message'   =>  $this->_page_lang['bad_request']
            //         ]);
            //     }
            // }
        }, $parameter);
        
        // UNUSED: Preference step view logic - no longer needed
        // // next step
        // if(!empty($parameter)) {
        //     if($parameter!='preference') {
        //         $this->doRedirect($this->toURL([$this->_mapping_data['class']]));
        //     }
        //     else {
        //         if(empty($this->getSession('temp_individual_account'))) {
        //             $this->doRedirect($this->toURL([$this->_mapping_data['class'], $this->_mapping_data['function']]));
        //         }

        //         // get options
        //         $list_countries = $this->loadModel('pages', ['table' => 'country'])->getAll($this->_current_lang_index, null, false);
        //         $list_interest_visas = $this->loadModel('pages', ['table' => 'interest_visa'])->getAll($this->_current_lang_index, null, false);
        //         $list_interest_topics = $this->loadModel('pages', ['table' => 'interest_topic'])->getAll($this->_current_lang_index, null, false);
        //         $this->pageOptions(
        //         [
        //             'countries' => $this->optionsToArray($list_countries),
        //             'interest_visas' => $this->optionsToArray($list_interest_visas),
        //             'interest_topics' => $this->optionsToArray($list_interest_topics)
        //         ]);
        //     }
        // }
        
        // load view
        // UNUSED: Session variables related to removed preference step
        return $this->pageData(
        [
            'parameter'     =>  $parameter,
            // 'account'       =>  $this->getSession('temp_individual_account'),
            // 'preference'    =>  $this->getSession('temp_individual_account_preference')
        ])->pageView();
    }
    
    public function migration_agent($parameter = '') {
        $parameter = strtolower($parameter);
        
        // post event
        $this->pageAction(function($parameter) {
            // get target plan
            $account_plan = $this->loadModel('pages', ['table' => 'plan_account'])->getByID(2, $this->_current_lang_index);
            if(!empty($account_plan)) {
                if(empty($parameter)) {
                    // do checking
                    $validator = Validator::make($this->_page_post_data, 
                    [
                        'company_name'      =>  'required',
                        'company_website'   =>  'required',
                        'company_address'   =>  'required',
                        'first_name'        =>  'required',
                        'last_name'         =>  'required',
                        'email'             =>  'required|email',
                        'telephone_code'    =>  'required',
                        'telephone_num'     =>  'required',
                        'password'          =>  'required|regex:/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}$/',
                        'repeat_password'   =>  'required|same:repeat_password'
                    ]);

                    if(!$validator->fails()) {
                        if(empty($this->_member_model->getByEmail($this->_page_post_data['email']))) {

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
                                    $this->setSession(['temp_agent_account_logo' => $file_name]);
                                }
                            }
                            else {
                                $this->_page_post_data['logo'] = $this->getSession('temp_agent_account_logo');
                            }

                            // if trial, create account directly
                            $this->_page_post_data['expiration_date_account'] = '1970-01-01';
                            if(!empty($this->_page_post_data['trial'])) {
                                $this->_page_post_data['expiration_date_account'] = date('Y-m-d', strtotime('+'.max(0, (int)$account_plan['valid_days_trial']).' days', strtotime($this->_today_date)));

                                $new_member = 
                                [
                                    'method'                =>  ((!empty($this->_page_post_data['method']))?max(1, $this->_page_post_data['method']):0),
                                    'type'                  =>  2,
                                    'avatar'                =>  $this->_page_post_data['logo'],
                                    'full_name'             =>  implode(' ', array_filter([$this->_page_post_data['first_name'], $this->_page_post_data['last_name']])),
                                    'first_name'            =>  $this->_page_post_data['first_name'],
                                    'last_name'             =>  $this->_page_post_data['last_name'],
                                    'email'                 =>  $this->_page_post_data['email'],
                                    'telephone_code'        =>  $this->_page_post_data['telephone_code'],
                                    'telephone_num'         =>  $this->_page_post_data['telephone_num'],
                                    'password'              =>  $this->_page_post_data['password'],
                                    'repeat_password'       =>  $this->_page_post_data['repeat_password'],
                                    'expiration_date_account'   =>  $this->_page_post_data['expiration_date_account'],
                                    'verified_token'        =>  md5($this->_page_post_data['email'].'@'.md5(uniqid(rand()))),
                                    'verified'              =>  0,
                                    'third_party_token'     =>  ((!empty($this->_page_post_data['third_party_token']))?$this->_page_post_data['third_party_token']:''),
                                    'details'               =>  
                                    [
                                        'logo'              =>  $this->_page_post_data['logo'],
                                        'company_name'      =>  $this->_page_post_data['company_name'],
                                        'company_website'   =>  $this->_page_post_data['company_website'],
                                        'company_address'   =>  $this->_page_post_data['company_address']
                                    ],
                                    'countries_serving'     =>  ((!empty($this->_page_post_data['countries_serving']))?$this->_page_post_data['countries_serving']:''),
                                ];
                                $new_member['alias_name'] = $this->_page_post_data['company_name'];
                                if($new_member['method'] > 1) {
                                    $new_member['verified'] =  1;
                                }

                                // try to save into db
                                if($new_member_id = $this->_member_model->doSave($new_member, 0, true)) {
                                    // reset
                                    $this->delSession('temp_agent_account');
                                    $this->delSession('temp_agent_account_logo');

                                    // email verification if need
                                    if((int)$new_member['method'] == 1) {
                                        // send email
                                        $link = $this->toURL('account_registration/verification').'?token='.$new_member['verified_token'];
                                        $subject = 'Email Verification';
                                        $body = '<p>Hello '.$new_member['full_name'].',</p>';
                                        $body.= '<p>You registered an account on AI-mmi, before being able to use your account you need to verify that this is your email address by clicking below link:';
                                        $body.= '<p><a href="'.$link.'" target="_blank">'.$link.'</a></p>';
                                        $body.= '<p>Kind Regards';
                                        $this->sendEmail($new_member['email'], $subject, $body);
                                        $this->pageResult(
                                        [
                                            'status'    =>  200,
                                            'message'   =>  '<strong>'.$this->_page_lang['registration_success'].'</strong><br/>'.str_replace('{email}', $new_member['email'], $this->_page_lang['email_verification_link']),
                                            'url'       =>  $this->toURL('account_login')
                                        ]);
                                    }
                                    else {
                                        $this->pageResult(
                                        [
                                            'status'    =>  200,
                                            'message'   =>  $this->_page_lang['registration_success'],
                                            'url'       =>  $this->toURL('account_login')
                                        ]);
                                    }
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
                                $this->setSession(['temp_agent_account' => $this->_page_post_data]);
                                $this->pageResult(
                                [
                                    'status'    =>  200,
                                    'message'   =>  '',
                                    'url'       =>  $this->toURL([$this->_mapping_data['class'], $this->_mapping_data['function'], 'payment'])
                                ]);
                            }
                        }
                        else {
                            $this->pageResult(
                            [
                                'status'    =>  400,
                                'message'   =>  $this->_page_lang['duplicate_email']
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
                }
                else {
                    $temp_agent_account = $this->getSession('temp_agent_account');     
                    if(!empty($temp_agent_account)) {
                        $temp_agent_account['expiration_date_account'] = date('Y-m-d', strtotime('+'.max(0, (int)$account_plan['valid_days_trial']).' days', strtotime($this->_today_date)));

                        $new_member = 
                        [
                            'method'                =>  ((!empty($temp_agent_account['method']))?max(1, $temp_agent_account['method']):0),
                            'type'                  =>  2,
                            'avatar'                =>  $temp_agent_account['logo'],
                            'full_name'             =>  implode(' ', array_filter([$temp_agent_account['first_name'], $temp_agent_account['last_name']])),
                            'first_name'            =>  $temp_agent_account['first_name'],
                            'last_name'             =>  $temp_agent_account['last_name'],
                            'email'                 =>  $temp_agent_account['email'],
                            'telephone_code'        =>  preg_replace('/^(\+)(.*)/i', '$2', $temp_agent_account['telephone_code']),
                            'telephone_num'         =>  $temp_agent_account['telephone_num'],
                            'password'              =>  $temp_agent_account['password'],
                            'repeat_password'       =>  $temp_agent_account['repeat_password'],
                            'expiration_date_account'   =>  $temp_agent_account['expiration_date_account'],
                            'verified_token'        =>  md5($temp_agent_account['email'].'@'.md5(uniqid(rand()))),
                            'verified'              =>  0,
                            'third_party_token'     =>  ((!empty($temp_agent_account['third_party_token']))?$temp_agent_account['third_party_token']:''),
                            'details'               =>  
                            [
                                'logo'              =>  $temp_agent_account['logo'],
                                'company_name'      =>  $temp_agent_account['company_name'],
                                'company_website'   =>  $temp_agent_account['company_website'],
                                'company_address'   =>  $temp_agent_account['company_address']
                            ],
                            'countries_serving'     =>  ((!empty($temp_agent_account['countries_serving']))?$temp_agent_account['countries_serving']:''),
                        ];
                        $new_member['alias_name'] = $temp_agent_account['company_name'];
                        if($new_member['method'] > 1) {
                            $new_member['verified'] =  1;
                        }

                        // try to save into db
                        if($new_member_id = $this->_member_model->doSave($new_member, 0, true)) {
                            $item = 
                            [
                                [
                                    'name'      =>  $account_plan['title'],
                                    'price'     =>  $account_plan['price'],
                                    'quantity'  =>  1
                                ]
                            ];

                            $shipTo = 
                            [
                                'name'          =>  '',
                                'email'         =>  $temp_agent_account['email'],
                                'street'        =>  $temp_agent_account['company_address'],
                                'city'          =>  'HK',
                                'state'         =>  'HK',
                                'country_code'  =>  'HK',
                                'zip'           =>  '000000',
                                'street2'       =>  '',
                                'phone_num'     =>  '',
                            ];

                            // call paypal
                            $paypal_api = new \App\Libraries\PaypalApi();
                            $paypal_api->setCurrency('USD');
                            $paypal_api->returnURL($this->toURL([$this->_mapping_data['class'],'paypal_feedback_account']));
                            $paypal_api->cancelURL($this->toURL([$this->_mapping_data['class'],'migration_agent']));
                            $paypal_url = $paypal_api->checkout($item, $shipTo);

                            if($paypal_url) {
                                if($this->_member_model->doSavePayment('account', [
                                    'member_id'             =>  $new_member_id,
                                    'payment_item_id'       =>  $account_plan['id'],
                                    'payment_method'        =>  $this->_page_post_data['payment_method'],
                                    'payment_valid_days'    =>  max(0, (int)$account_plan['valid_days']),
                                    'payment_amt'           =>  $account_plan['price'],
                                    'payment_token'         =>  $paypal_api->getToken()
                                ])) {
                                    $this->pageResult(
                                    [
                                        'status'    =>  200,
                                        'url'   =>  $paypal_url
                                    ]);
                                }
                                else {
                                    $this->pageResult(
                                    [
                                        'status'    =>  200,
                                        'url'   =>  $this->toURL([$this->_mapping_data['class'],'migration_agent'])
                                    ]);
                                }
                            }
                            else {
                                $this->pageResult(
                                [
                                    'status'    =>  200,
                                    'url'   =>  $this->toURL([$this->_mapping_data['class'],'migration_agent'])
                                ]);
                            }
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
                }
            }
            else {
                $this->pageResult(
                [
                    'status'    =>  400,
                    'message'   =>  $this->_page_lang['bad_request']
                ]);
            }
        }, $parameter);
        
        // next step
        if(!empty($parameter)) {
            if($parameter!='payment') {
                $this->doRedirect($this->toURL([$this->_mapping_data['class']]));
            }
            else {
                if(empty($this->getSession('temp_agent_account'))) {
                    $this->doRedirect($this->toURL([$this->_mapping_data['class'], $this->_mapping_data['function']]));
                }
            }
        }
        
        $list_countries = $this->loadModel('pages', ['table' => 'country'])->getAll($this->_current_lang_index, null, false);
        $this->pageOptions(
        [
            'countries' => $this->optionsToArray($list_countries)
        ]);
        // load view
        return $this->pageData(
        [
            'parameter'     =>  $parameter,
            'account'       =>  $this->getSession('temp_agent_account'),
            'plan'          =>  $this->loadModel('pages', ['table' => 'plan_account'])->getByID(2, $this->_current_lang_index)
        ])->pageView();
    }
    
    public function service_provider($parameter = '') {
        $parameter = strtolower($parameter);
        
        // post event
        $this->pageAction(function($parameter) {
            // get target plan
            $account_plan = $this->loadModel('pages', ['table' => 'plan_account'])->getByID(3, $this->_current_lang_index);
            if(!empty($account_plan)) {
                if(empty($parameter)) {
                    // do checking
                    $validator = Validator::make($this->_page_post_data,
                    [
                        'company_type'      =>  'required',
                        'company_name'      =>  'required',
                        'country'           =>  'required',
                        'first_name'        =>  'required',
                        'last_name'         =>  'required',
                        'email'             =>  'required|email',
                        'telephone_code'    =>  'required',
                        'telephone_num'     =>  'required',
                        'password'          =>  'required|regex:/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}$/',
                        'repeat_password'   =>  'required|same:repeat_password'
                    ]);

                    if(!$validator->fails()) {
                        if(empty($this->_member_model->getByEmail($this->_page_post_data['email']))) {

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
                                    $this->setSession(['temp_service_provider_account_logo' => $file_name]);
                                }
                            }
                            else {
                                $this->_page_post_data['logo'] = $this->getSession('temp_service_provider_account_logo');
                            }

                            // Create account directly without payment
                            // Service providers are free with 1 year expiry
                            $this->_page_post_data['expiration_date_account'] = date('Y-m-d', strtotime('+1 year', strtotime($this->_today_date)));

                            $new_member =
                            [
                                'method'                =>  ((!empty($this->_page_post_data['method']))?max(1, $this->_page_post_data['method']):0),
                                'type'                  =>  3,
                                'avatar'                =>  $this->_page_post_data['logo'],
                                'full_name'             =>  implode(' ', array_filter([$this->_page_post_data['first_name'], $this->_page_post_data['last_name']])),
                                'first_name'            =>  $this->_page_post_data['first_name'],
                                'last_name'             =>  $this->_page_post_data['last_name'],
                                'email'                 =>  $this->_page_post_data['email'],
                                'telephone_code'        =>  $this->_page_post_data['telephone_code'],
                                'telephone_num'         =>  $this->_page_post_data['telephone_num'],
                                'password'              =>  $this->_page_post_data['password'],
                                'repeat_password'       =>  $this->_page_post_data['repeat_password'],
                                'expiration_date_account'   =>  $this->_page_post_data['expiration_date_account'],
                                'verified_token'        =>  md5($this->_page_post_data['email'].'@'.md5(uniqid(rand()))),
                                'verified'              =>  0,
                                'third_party_token'     =>  ((!empty($this->_page_post_data['third_party_token']))?$this->_page_post_data['third_party_token']:''),
                                'details'               =>
                                [
                                    'logo'              =>  $this->_page_post_data['logo'],
                                    'company_type'      =>  $this->_page_post_data['company_type'],
                                    'company_name'      =>  $this->_page_post_data['company_name'],
                                    'company_website'   =>  (!empty($this->_page_post_data['company_website']) ? $this->_page_post_data['company_website'] : ''),
                                    'company_address'   =>  (!empty($this->_page_post_data['company_address']) ? $this->_page_post_data['company_address'] : ''),
                                    'country'           =>  $this->_page_post_data['country'],
                                    'services_country'  =>  (!empty($this->_page_post_data['services_country']) ? json_encode(in_array('all', (array)$this->_page_post_data['services_country']) ? ['all'] : $this->_page_post_data['services_country']) : ''),
                                    'services'          =>  (!empty($this->_page_post_data['services']) ? $this->_page_post_data['services'] : ''),
                                    'registered_business_country' => (!empty($this->_page_post_data['registered_business_country']) ? $this->_page_post_data['registered_business_country'] : ''),
                                    'registered_business_name' => (!empty($this->_page_post_data['registered_business_name']) ? $this->_page_post_data['registered_business_name'] : ''),
                                    'registered_business_number' => (!empty($this->_page_post_data['registered_business_number']) ? $this->_page_post_data['registered_business_number'] : '')
                                ],
                                'business_licenses'     =>  []
                            ];
                            $new_member['alias_name'] = $this->_page_post_data['company_name'];
                            if($new_member['method'] > 1) {
                                $new_member['verified'] =  1;
                            }

                            // Process business licenses if submitted
                            if(!empty($this->_page_post_data['license_id'])) {
                                foreach ($this->_page_post_data['license_id'] as $license_key => $license_id) {
                                    $new_member['business_licenses'][] = [
                                        'id'                    =>  (int)$license_id,
                                        'license_country'       =>  $this->_page_post_data['license_country'][$license_key],
                                        'issuing_authority'     =>  $this->_page_post_data['issuing_authority'][$license_key],
                                        'type_of_registration'  =>  $this->_page_post_data['type_of_registration'][$license_key],
                                        'registration_number'   =>  $this->_page_post_data['registration_number'][$license_key],
                                    ];
                                }
                            }

                            // try to save into db
                            if($new_member_id = $this->_member_model->doSave($new_member, 0, true)) {
                                // reset
                                $this->delSession('temp_service_provider_account');
                                $this->delSession('temp_service_provider_account_logo');

                                // Create free subscription for service provider (plan_id = 1 - Free Plan)
                                DB::table('subscriptions')->insert([
                                    'member_id' => $new_member_id,
                                    'plan_id' => 1,
                                    'status' => 'active',
                                    'started_at' => now(),
                                    'ends_at' => null,
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]);

                                // email verification if need
                                if((int)$new_member['method'] == 1) {
                                    // send email
                                    $link = $this->toURL('account_registration/verification').'?token='.$new_member['verified_token'];
                                    $subject = 'Email Verification';
                                    $body = '<p>Hello '.$new_member['full_name'].',</p>';
                                    $body.= '<p>You registered an account on AI-mmi, before being able to use your account you need to verify that this is your email address by clicking below link:';
                                    $body.= '<p><a href="'.$link.'" target="_blank">'.$link.'</a></p>';
                                    $body.= '<p>Kind Regards';
                                    $this->sendEmail($new_member['email'], $subject, $body);
                                    $this->pageResult(
                                    [
                                        'status'    =>  200,
                                        'message'   =>  '<strong>'.$this->_page_lang['registration_success'].'</strong><br/>'.str_replace('{email}', $new_member['email'], $this->_page_lang['email_verification_link']),
                                        'url'       =>  $this->toURL('account_login')
                                    ]);
                                }
                                else {
                                    $this->pageResult(
                                    [
                                        'status'    =>  200,
                                        'message'   =>  $this->_page_lang['registration_success'],
                                        'url'       =>  $this->toURL('account_login')
                                    ]);
                                }
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
                                'message'   =>  $this->_page_lang['duplicate_email']
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
                }
                // Payment parameter handling removed - service providers now sign up freely without payment
            }
            else {
                $this->pageResult(
                [
                    'status'    =>  400,
                    'message'   =>  $this->_page_lang['bad_request']
                ]);
            }
        }, $parameter);
        
        // load view
        $list_organization_type = $this->loadModel('pages', ['table' => 'organization_type'])->getAll($this->_current_lang_index, null, false);

        $this->pageOptions(
        [
            'organization_type' => $this->optionsToArray($list_organization_type),
            'countries' => CountriesPhoneCodes::getCountries(),
            'phone_codes' => CountriesPhoneCodes::getPhoneCodes(),
            'country_phone_map' => CountriesPhoneCodes::getCountryPhoneMap()
        ]);

        return $this->pageData(
        [
            'parameter'     =>  $parameter,
            'account'       =>  $this->getSession('temp_service_provider_account'),
            'plan'          =>  $this->loadModel('pages', ['table' => 'plan_account'])->getByID(3, $this->_current_lang_index)
        ])->pageView();
    }
    
    public function verification() {
        return $this->pageData(
        [
            'verification_result'  => $this->_member_model->doVerification($this->getParamValue('token'))
        ])->pageView();
    }
    
    public function paypal_feedback_account() {
        $member_plan_account = $this->_member_model->getPaymentByToken('account', $this->_page_get_data['token']);
        if(!empty($member_plan_account)) {
            // confirm payment
            $paypal_api = new \App\Libraries\PaypalApi();
            $paypal_api->setCurrency('USD');
            if($paypal_api->confirm((double)$member_plan_account['payment_amt'])) {
                
                // renew
                $new_member = $this->_member_model->getByID($member_plan_account['member_id']);
                $new_expiration_date = date('Y-m-d', strtotime('+'.max(0, (int)$member_plan_account['payment_valid_days']).' days', max(strtotime($new_member['expiration_date_account']), strtotime($this->_today_date))));
                if($this->_member_model->renewExpirationDate('account', $member_plan_account['member_id'], [
                    'new_expiration_date'   =>  $new_expiration_date, 
                    'token'                 =>  $this->_page_get_data['token'],
                    'transaction_id'        =>  $paypal_api->getTransactionID()
                ])) {
                    // reset
                    $this->delSession('temp_agent_account');
                    $this->delSession('temp_agent_account_logo');


                    // send email
                    $link = $this->toURL('account_registration/verification').'?token='.$new_member['verified_token'];
                    $subject = 'Email Verification';
                    $body = '<p>Hello '.$new_member['full_name'].',</p>';
                    $body.= '<p>You registered an account on AI-mmi, before being able to use your account you need to verify that this is your email address by clicking below link:';
                    $body.= '<p><a href="'.$link.'" target="_blank">'.$link.'</a></p>';
                    $body.= '<p>Kind Regards';
                    $this->sendEmail($new_member['email'], $subject, $body);
                    
                    // do redirect
                    $this->doRedirect($this->toURL([$this->_mapping_data['class'], 'payment_done']));
                }
            } 
        }
        
        // do redirect
        $this->doRedirect($this->toURL([$this->_mapping_data['class']]));
    }
    
    public function payment_done() {
        return $this->pageView();
    }
}