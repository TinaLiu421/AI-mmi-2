<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Support\DestinationsServing;

class Member extends BaseModel {
    
    protected $_member_table = 'member';
    protected $_member_token_table = 'member_token';
  
    public function __construct($data) {
        parent::__construct($data);
    }
    
    public function getAll($options = [], $pagination = true) {
        $keywords = (!empty($options['keywords']))?$options['keywords']:'';
        $where = [];
        if(!empty($keywords)) {
            $where[] = 
            [
                [
                    'name'      =>  'alias_name', 
                    'operate'   =>  'like', 
                    'value'     =>  $keywords
                ],
                [
                    'name'      =>  'full_name', 
                    'operate'   =>  'like', 
                    'value'     =>  $keywords
                ],
                [
                    'name'      =>  'email', 
                    'operate'   =>  'like', 
                    'value'     =>  $keywords
                ]
            ];
        }
        
        $order = array_filter([
            (!empty($options['sorting']))?$options['sorting']:$this->getParamValue('sorting'),
            'id_desc'
        ]);

        return $this->setWhere($where)->setOrder($order)->queryListData($this->_member_table, $pagination);
    }
    
    public function getByID($member_id = 0) {
        $target_member = ((!empty((int)$member_id))?$this->setWhere(
        [
            'name'      =>  'id', 
            'operate'   =>  '=', 
            'value'     =>  (int)$member_id
        ])->queryOneData($this->_member_table):false);
        
        if(!empty($target_member)) {
            $query = DB::table($this->_member_table.'_plan_visa_submission');
            $query->where($this->_member_table.'_plan_visa_submission.member_id', '=', $target_member['id']);
            $query->where($this->_member_table.'_plan_visa_submission.status', '>', 1);
            $total_ai_service = $query->count();

            $query = DB::table('chat_log');
            $query->where('chat_log.member_id', '=', $target_member['id']);
            $query->where('chat_log.type', '=', 'ask');
            $query->where('chat_log.status', '>', 0);
            $total_ask_question = $query->count();

            $target_member['total_ask_question'] = $total_ask_question;
            $target_member['total_ai_service'] = $total_ai_service;
            $target_member['expiration_ai_level'] = 0;
            $target_member['expiration_human_level'] = 0;
            if(strtotime($target_member['expiration_date_visa_submission_ai']) < strtotime($this->_today_date)) {
                $target_member['expiration_ai_level'] = 1;
                if(empty($total_ai_service)) {
                    $target_member['expiration_ai_level'] = 2;
                }
            }
            if(strtotime($target_member['expiration_date_visa_submission_human']) < strtotime($this->_today_date)) {
                $target_member['expiration_huma_level'] = 1;
            }

            // Fetch active subscriptions with plan details
            $active_subscriptions = DB::table('subscriptions')
                ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
                ->where('subscriptions.member_id', '=', $target_member['id'])
                ->where('subscriptions.status', '=', 'active')
                ->where(function($q) {
                    $q->whereNull('subscriptions.ends_at')
                      ->orWhere('subscriptions.ends_at', '>', now());
                })
                ->select(
                    'plans.id as plan_id',
                    'plans.code as plan_code',
                    'plans.name as plan_name',
                    'plans.business_domain',
                    'subscriptions.started_at',
                    'subscriptions.ends_at'
                )
                ->get();

            // Organize subscriptions by type
            $has_migration = false;
            $has_education = false;
            $primary_subscription = null;
            $subscription_names = [];

            foreach ($active_subscriptions as $sub) {
                $subscription_names[] = $sub->plan_name;

                if ($sub->business_domain === 'migration') {
                    $has_migration = true;
                    if (!$primary_subscription) {
                        $primary_subscription = $sub;
                    }
                }
                if ($sub->business_domain === 'education') {
                    $has_education = true;
                    if (!$primary_subscription && !$has_migration) {
                        $primary_subscription = $sub;
                    }
                }
                if ($sub->business_domain === 'combined') {
                    $has_migration = true;
                    $has_education = true;
                    if (!$primary_subscription) {
                        $primary_subscription = $sub;
                    }
                }
            }

            // Set subscription data
            $target_member['active_subscriptions'] = $active_subscriptions->toArray();
            $target_member['has_migration_subscription'] = $has_migration;
            $target_member['has_education_subscription'] = $has_education;

            if ($primary_subscription) {
                $target_member['subscription_name'] = implode(', ', $subscription_names);
                $target_member['subscription_plan_type'] = $primary_subscription->business_domain;
                $target_member['subscription_expiry'] = $primary_subscription->ends_at;
                $target_member['primary_plan_code'] = $primary_subscription->plan_code;
            } else {
                $target_member['subscription_name'] = null;
                $target_member['subscription_plan_type'] = null;
                $target_member['subscription_expiry'] = null;
                $target_member['primary_plan_code'] = null;
            }
        }

        return $target_member;
    }
    
    public function getDetailsByID($member_id = 0) {
        return ((!empty((int)$member_id))?$this->setWhere(
        [
            'name'      =>  'member_id', 
            'operate'   =>  '=', 
            'value'     =>  (int)$member_id
        ])->queryOneData($this->_member_table.'_details'):false);
    }
    
    public function getAgentByID($member_id = 0) {
        return ((!empty((int)$member_id))?$this->setWhere(
        [
            'name'      =>  'member_id', 
            'operate'   =>  '=', 
            'value'     =>  (int)$member_id
        ])->queryListData($this->_member_table.'_agent', false):false);
    }
    
    public function getAgentByCountryID($country_id = 0) {
        if(!is_array($country_id)) {
            $country_id = [$country_id];
        }

        $legacyCountryIds = array_values(array_filter(array_map('intval', $country_id)));
        $destinations = DestinationsServing::all();
        $legacyToDestination = [];
        foreach ($destinations as $destination) {
            if (!empty($destination['visa_country_id'])) {
                $legacyToDestination[(int) $destination['visa_country_id']] = (int) $destination['id'];
            }
            if (!empty($destination['legacy_ids'])) {
                foreach ($destination['legacy_ids'] as $legacy) {
                    $legacyToDestination[(int) $legacy] = (int) $destination['id'];
                }
            }
        }

        $destinationIds = [];
        foreach ($legacyCountryIds as $legacy) {
            if (isset($legacyToDestination[$legacy])) {
                $destinationIds[] = (int) $legacyToDestination[$legacy];
            }
        }
        $destinationIds = array_values(array_unique($destinationIds));

        $searchTokens = [];
        $allDestinationIds = [];
        foreach ($destinations as $destination) {
            $allDestinationIds[] = (int) $destination['id'];
        }

        foreach (array_merge($destinationIds, $allDestinationIds) as $destId) {
            $searchTokens[] = '"'.(string) $destId.'"';
        }
        foreach ($legacyCountryIds as $legacy) {
            $searchTokens[] = '"'.(string) $legacy.'"';
        }
        $searchTokens = array_values(array_unique($searchTokens));

        $query = DB::table($this->_member_table)
            ->leftJoin($this->_member_table.'_details', function($join) {
                $join->on($this->_member_table.'.id', '=', $this->_member_table.'_details.member_id');
            })
            ->leftJoin($this->_member_table.'_agent', function($join) {
                $join->on($this->_member_table.'.id', '=', $this->_member_table.'_agent.member_id')
                     ->where($this->_member_table.'_agent.status', '>', 0);
            })
            ->select(
                $this->_member_table.'.*',
                $this->_member_table.'_details.company_website',
                $this->_member_table.'_agent.registration_country as agent_registration_country',
                $this->_member_table.'_agent.full_name as agent_full_name',
                $this->_member_table.'_agent.registration_num as agent_registration_num'
            )
            ->where($this->_member_table.'.type', [2, 3])
            ->where($this->_member_table.'.status', '>', 0)
            ->where(function($query) use ($searchTokens, $legacyCountryIds) {
                if(!empty($searchTokens)) {
                    $query->where(function($query) use ($searchTokens) {
                        foreach($searchTokens as $token) {
                            $query->orWhere($this->_member_table.'.countries_serving', 'LIKE', '%'.$token.'%');
                        }
                    });
                }
                if(!empty($legacyCountryIds)) {
                    $query->orWhereIn($this->_member_table.'_agent.registration_country', $legacyCountryIds);
                }
            })
            ->orderBy($this->_member_table.'.alias_name', 'asc')
            ->orderBy($this->_member_table.'_agent.full_name', 'asc');

        return $this->revisedData($query->get()->map(function($items) {
            $data = [];
            foreach ($items as $item_key => $item_value) {
                $data[$item_key] = $item_value;
            }
            return $data;
        })->toArray(), true);
    }
    
    
    public function getLawFirmByID($member_id = 0) {
        return ((!empty((int)$member_id))?$this->setWhere(
        [
            'name'      =>  'member_id',
            'operate'   =>  '=',
            'value'     =>  (int)$member_id
        ])->queryListData($this->_member_table.'_lawfirm', false):false);
    }

    public function getBusinessLicenseByID($member_id = 0) {
        return ((!empty((int)$member_id))?$this->setWhere(
        [
            'name'      =>  'member_id',
            'operate'   =>  '=',
            'value'     =>  (int)$member_id
        ])->queryListData($this->_member_table.'_business_license', false):false);
    }

    public function getByEmail($member_email = '', $verified = 1) {
        return ((!empty((string)$member_email))?$this->setWhere(
        [
            [
                'name'      =>  'email', 
                'operate'   =>  '=', 
                'value'     =>  (string)$member_email
            ],
            [
                'name'      =>  'verified', 
                'operate'   =>  '=', 
                'value'     =>  (int)$verified
            ]
        ])->queryOneData($this->_member_table):false);
    }
    
    public function getByToken($member_token = '') {
        // first, fetch token
        $member_token_data = ((!empty((string)$member_token))?$this->setWhere(
        [
            [
                'name'      =>  'type', 
                'operate'   =>  '=', 
                'value'     =>  1
            ],
            [
                'name'      =>  'value', 
                'operate'   =>  '=', 
                'value'     =>  (string)$member_token
            ]
        ])->queryOneData($this->_member_token_table):false);
        
        // second, fetch user
        $target_member = false;
        if(!empty($member_token_data)) {
            $target_member = ((!empty((int)$member_token_data['member_id']))?$this->setWhere(
            [
                'name'      =>  'id', 
                'operate'   =>  '=', 
                'value'     =>  (int)$member_token_data['member_id']
            ])->queryOneData($this->_member_table):false);

            if(!empty($target_member)) {
                $query = DB::table($this->_member_table.'_plan_visa_submission');
                $query->where($this->_member_table.'_plan_visa_submission.member_id', '=', $target_member['id']);
                $query->where($this->_member_table.'_plan_visa_submission.status', '>', 1);
                $total_ai_service = $query->count();
                
                $query = DB::table('chat_log');
                $query->where('chat_log.member_id', '=', $target_member['id']);
                $query->where('chat_log.type', '=', 'ask');
                $query->where('chat_log.status', '>', 0);
                $total_ask_question = $query->count();

                $target_member['total_ask_question'] = $total_ask_question;
                $target_member['total_ai_service'] = $total_ai_service;
                $target_member['expiration_ai_level'] = 0;
                $target_member['expiration_human_level'] = 0;
                if(strtotime($target_member['expiration_date_visa_submission_ai']) < strtotime($this->_today_date)) {
                    $target_member['expiration_ai_level'] = 1;
                    if(empty($total_ai_service)) {
                        $target_member['expiration_ai_level'] = 2;
                    }
                }
                if(strtotime($target_member['expiration_date_visa_submission_human']) < strtotime($this->_today_date)) {
                    $target_member['expiration_huma_level'] = 1;
                }

                // Fetch active subscriptions with plan details
                $active_subscriptions = DB::table('subscriptions')
                    ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
                    ->where('subscriptions.member_id', '=', $target_member['id'])
                    ->where('subscriptions.status', '=', 'active')
                    ->where(function($q) {
                        $q->whereNull('subscriptions.ends_at')
                          ->orWhere('subscriptions.ends_at', '>', now());
                    })
                    ->select(
                        'plans.id as plan_id',
                        'plans.code as plan_code',
                        'plans.name as plan_name',
                        'plans.business_domain',
                        'subscriptions.started_at',
                        'subscriptions.ends_at'
                    )
                    ->get();

                // Organize subscriptions by type
                $has_migration = false;
                $has_education = false;
                $primary_subscription = null;
                $subscription_names = [];

                foreach ($active_subscriptions as $sub) {
                    $subscription_names[] = $sub->plan_name;

                    if ($sub->business_domain === 'migration') {
                        $has_migration = true;
                        if (!$primary_subscription) {
                            $primary_subscription = $sub;
                        }
                    }
                    if ($sub->business_domain === 'education') {
                        $has_education = true;
                        if (!$primary_subscription && !$has_migration) {
                            $primary_subscription = $sub;
                        }
                    }
                    if ($sub->business_domain === 'combined') {
                        $has_migration = true;
                        $has_education = true;
                        if (!$primary_subscription) {
                            $primary_subscription = $sub;
                        }
                    }
                }

                // Set subscription data
                $target_member['active_subscriptions'] = $active_subscriptions->toArray();
                $target_member['has_migration_subscription'] = $has_migration;
                $target_member['has_education_subscription'] = $has_education;

                if ($primary_subscription) {
                    $target_member['subscription_name'] = implode(', ', $subscription_names);
                    $target_member['subscription_plan_type'] = $primary_subscription->business_domain;
                    $target_member['subscription_expiry'] = $primary_subscription->ends_at;
                    $target_member['primary_plan_code'] = $primary_subscription->plan_code;
                } else {
                    $target_member['subscription_name'] = null;
                    $target_member['subscription_plan_type'] = null;
                    $target_member['subscription_expiry'] = null;
                    $target_member['primary_plan_code'] = null;
                }
            }
        }

        return $target_member;
    }

    public function doLogin($member_id = '', $password = '') {
        if(!empty((string)$member_id) && !empty((string)$password)) {
            // fetch user by name or email
            if(!filter_var($member_id, FILTER_VALIDATE_EMAIL)) {
                $member_data = $this->getByUserName($member_id);
            }
            else {
                $member_data = $this->getByEmail($member_id);
            }

            if((!empty($member_data) && $member_data['status'] == 1 && $member_data['verified'] == 1) && \Illuminate\Support\Facades\Hash::check((string)$password, $member_data['password'])) {
                DB::beginTransaction();
                try {
                    // disable old access token if need
                    if(!empty($member_data['signle_mode'])) {
                        $this->setWhere(
                        [
                            'name'      =>  'member_id', 
                            'operate'   =>  '=', 
                            'value'     =>  (int)$member_data['id']
                        ])->queryDeleteData($this->_member_token_table, false); 
                    }

                    // new access token
                    $new_access_token = md5(uniqid(rand()));
                    $this->queryInsertData($this->_member_token_table, [
                        'type'          =>  1,
                        'member_id'       =>  (int)$member_data['id'],
                        'value'         =>  $new_access_token,
                        'created_by'    =>  (int)$member_data['id']
                    ], false);

                    DB::commit();
                    return $new_access_token;
                }
                catch (Exception $e) {
                    $this->setResultMessage($this->pLang('query_error'), 500);
                    DB::rollBack();
                    throw $e;
                }
            }
            else {
                $this->setResultMessage($this->pLang('authn_not_match'), 404);
            }
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return false;
    }
    
    public function doLogout($member_token = '') {
        return ((!empty((string)$member_token))?$this->setWhere(
        [
            'name'      =>  'value', 
            'operate'   =>  '=', 
            'value'     =>  (string)$member_token
        ])->queryDeleteData($this->_member_token_table):false);
    }
    
    public function forgotPassword($email = '') {
        if(!empty((string)$email)) {
            // fetch user by email
            $member_data = $this->getByEmail($email);

            if(!empty($member_data)) {
                // fetch latest token
                $member_token_data = $this->setWhere(
                [
                    [
                        'name'      =>  'type', 
                        'operate'   =>  '=', 
                        'value'     =>  2
                    ],
                    [
                        'name'      =>  'member_id', 
                        'operate'   =>  '=', 
                        'value'     =>  (int)$member_data['id']
                    ]
                ])->setOrder(['id_desc'])->queryOneData($this->_member_token_table);

                if((empty($member_token_data)) || (!empty($member_token_data) && strtotime($member_token_data['created_at'])+3600 < strtotime($this->_today_datetime))) {
                    DB::beginTransaction();
                    try {
                        // disable previous
                        $this->setWhere(
                        [
                            [
                                'name'      =>  'type', 
                                'operate'   =>  '=', 
                                'value'     =>  2
                            ],
                            [
                                'name'      =>  'member_id', 
                                'operate'   =>  '=', 
                                'value'     =>  (int)$member_data['id']
                            ]
                        ])->setOrder(['id_desc'])->queryDeleteData($this->_member_token_table, false);

                        // new reset token
                        $new_reset_password_token = md5(uniqid(rand()));
                        $this->queryInsertData($this->_member_token_table, [
                            'type'          =>  2,
                            'member_id'       =>  (int)$member_data['id'],
                            'value'         =>  $new_reset_password_token,
                            'expiry_at'     =>  date('Y-m-d H:i:s', strtotime($this->_today_datetime)+2*3600),
                            'created_by'    =>  (int)$member_data['id']
                        ], false);

                        DB::commit();
                        return $new_reset_password_token;
                    }
                    catch (Exception $e) {
                        $this->setResultMessage($this->pLang('query_error'), 500);
                        DB::rollBack();
                        throw $e;
                    }
                }
                else {
                    $this->setResultMessage($this->pLang('operation_denied'), 403);
                }
            }
            else {
                $this->setResultMessage($this->pLang('email_not_found'), 404);
            }
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return false;
    }
    
    public function resetPassword($token = '', $password = '', $repeat_password = '') {
        $validator = Validator::make([
            'token'             =>  $token,
            'password'          =>  $password,
            'repeat_password'   =>  $repeat_password
        ], 
        [
            'token'             =>  'required',
            'password'          =>  'required|regex:/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}$/',
            'repeat_password'   =>  'required|same:repeat_password'
        ]);
         
        if(!$validator->fails()) {
            // fetch latest token
            $member_token_data = $this->setWhere(
            [
                [
                    'name'      =>  'type', 
                    'operate'   =>  '=', 
                    'value'     =>  2
                ],
                [
                    'name'      =>  'value', 
                    'operate'   =>  '=', 
                    'value'     =>  (string)$token
                ]
            ])->setOrder(['id_desc'])->queryOneData($this->_member_token_table);

            // prevent frequent operation
            if(!empty($member_token_data) && strtotime($member_token_data['expiry_at']) >= strtotime($this->_today_datetime)) {
                DB::beginTransaction();
                try {
                    // disable token
                    $this->setWhere(
                    [
                        [
                            'name'      =>  'type', 
                            'operate'   =>  '=', 
                            'value'     =>  2
                        ],
                        [
                            'name'      =>  'member_id', 
                            'operate'   =>  '=', 
                            'value'     =>  (int)$member_token_data['member_id']
                        ]
                    ])->setOrder(['id_desc'])->queryDeleteData($this->_member_token_table, false);

                    // renew password
                    $this->setWhere(
                    [
                        'name'          =>  'id', 
                        'operate'       =>  '=', 
                        'value'         =>  $member_token_data['member_id']
                    ])->queryUpdateData($this->_member_table, [
                        'password'      =>  \Illuminate\Support\Facades\Hash::make($password),
                        'updated_by'    =>  $member_token_data['member_id']
                    ], false);

                    DB::commit();
                    return true;
                }
                catch (Exception $e) {
                    $this->setResultMessage($this->pLang('query_error'), 500);
                    DB::rollBack();
                    throw $e;
                }
            }
            else {
                $this->setResultMessage($this->pLang('token_expiry'), 408);
            }
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return false;
    }

    public function doSave($data = [], $member_id = 0, $overwrite = false) {
        if(!empty($data)) {
            if($member_id > 0 && empty($data['password'])) {
                $validator = Validator::make($data, 
                [
                    'email'    =>  'required|email'
                ]);
            }
            else {
                $validator = Validator::make($data, 
                [
                    'email'            =>  'required|email',
                    'password'         =>  'required|regex:/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}$/',
                    'repeat_password'  =>  'required|same:repeat_password'
                ]);
            }

            if(!$validator->fails()) {
                // encrypt password if need
                if(!empty($data['password'])) {
                    $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
                }
                else {
                    if ($member_id > 0){
                        if(isset($data['password'])) {
                            unset($data['password']);
                        }
                    }
                    else {
                        $data['password'] = \Illuminate\Support\Facades\Hash::make(strtolower(substr(md5(uniqid(rand())), 0, 8)));
                    }
                }
                if(isset($data['repeat_password'])) {
                    unset($data['repeat_password']);
                }

                // unique email
                $member_data = $this->setWhere(
                [
                    [
                        'name'      =>  'id', 
                        'operate'   =>  '!=', 
                        'value'     =>  (int)$member_id
                    ],
                    [
                        'name'      =>  'email', 
                        'operate'   =>  '=', 
                        'value'     =>  (string)$data['email']
                    ]
                ])->queryOneData($this->_member_table);

                if(empty($member_data)) {
                    return $this->queryTransaction(function($data, $member_id) {
                        if((int)$member_id == 0) {
                            $result = $this->queryInsertData($this->_member_table, $data);
                            $member_id = $result;
                        }
                        else {
                            if(empty($data['password'])) {
                                unset($data['password']);
                            }
                            $result = (($this->setWhere(
                            [
                                'name'      =>  'id', 
                                'operate'   =>  '=', 
                                'value'     =>  (int)$member_id
                            ])->queryUpdateData($this->_member_table, $data))?(int)$member_id:0);
                        }
                        if(!empty($result)) {
                            // disable all first
                            $this->setWhere(
                            [
                                'name'      =>  'member_id', 
                                'operate'   =>  '=', 
                                'value'     =>  (int)$member_id
                            ])->queryDeleteData($this->_member_table.'_preference');
                            
                            // re-insert relation if need
                            if(!empty($data['interested_topic']) && is_array($data['interested_topic'])) {
                                foreach ($data['interested_topic'] as $interested_topic) {
                                    $this->setWhere(
                                    [
                                        [
                                            'name'      =>  'member_id', 
                                            'operate'   =>  '=', 
                                            'value'     =>  (int)$member_id
                                        ],
                                        [
                                            'name'      =>  'related_id', 
                                            'operate'   =>  '=', 
                                            'value'     =>  (int)$interested_topic
                                        ],
                                    ])->queryInsertData($this->_member_table.'_preference', [
                                        'type'          =>  3,
                                        'member_id'     =>  (int)$member_id,
                                        'related_id'    =>  (int)$interested_topic,
                                        'status'        =>  1
                                    ], true);
                                }
                            }
                            
                            // details
                            if(!empty($data['details'])) {
                                $data['details']['member_id'] = $member_id;
                                $this->setWhere(
                                [
                                    [
                                        'name'      =>  'member_id', 
                                        'operate'   =>  '=', 
                                        'value'     =>  (int)$member_id
                                    ]
                                ])->queryInsertData($this->_member_table.'_details', $data['details'], true);
                            }
                            
                            // agents
                            // disable all first
                            $this->setWhere(
                            [
                                'name'      =>  'member_id', 
                                'operate'   =>  '=', 
                                'value'     =>  (int)$member_id
                            ])->queryDeleteData($this->_member_table.'_agent');
                            
                      
                            // re-insert if need
                            if(!empty($data['agents'])) {
                                foreach ($data['agents'] as $agent) {
                                    $agent_id = (int)$agent['id'];
                                    $agent['member_id'] = (int)$member_id;
                                    $agent['status'] = 1;
                                    unset($agent['id']);
                                    $this->setWhere(
                                    [
                                        [
                                            'name'      =>  'member_id', 
                                            'operate'   =>  '=', 
                                            'value'     =>  (int)$member_id
                                        ],
                                        [
                                            'name'      =>  'id', 
                                            'operate'   =>  '=', 
                                            'value'     =>  (int)$agent_id
                                        ]
                                    ])->queryInsertData($this->_member_table.'_agent', $agent, true);
                                }
                            }

                            // lawfirms
                            // disable all first
                            $this->setWhere(
                            [
                                'name'      =>  'member_id', 
                                'operate'   =>  '=', 
                                'value'     =>  (int)$member_id
                            ])->queryDeleteData($this->_member_table.'_lawfirm');
                            
                            // re-insert if need
                            if(!empty($data['lawfirms'])) {
                                foreach ($data['lawfirms'] as $lawfirm) {
                                    $lawfirm_id = (int)$lawfirm['id'];
                                    $lawfirm['member_id'] = (int)$member_id;
                                    $lawfirm['status'] = 1;
                                    unset($lawfirm['id']);
                                    $this->setWhere(
                                    [
                                        [
                                            'name'      =>  'member_id', 
                                            'operate'   =>  '=', 
                                            'value'     =>  (int)$member_id
                                        ],
                                        [
                                            'name'      =>  'id', 
                                            'operate'   =>  '=', 
                                            'value'     =>  (int)$lawfirm_id
                                        ]
                                    ])->queryInsertData($this->_member_table.'_lawfirm', $lawfirm, true);
                                }
                            }
                        }

                        // business licenses
                        // delete all first
                        $this->setWhere(
                        [
                            'name'      =>  'member_id',
                            'operate'   =>  '=',
                            'value'     =>  (int)$member_id
                        ])->queryDeleteData($this->_member_table.'_business_license');

                        // re-insert if need
                        if(!empty($data['business_licenses'])) {
                            foreach ($data['business_licenses'] as $license) {
                                $license_id = (int)$license['id'];
                                $license['member_id'] = (int)$member_id;
                                $license['status'] = 1;
                                unset($license['id']);
                                $this->setWhere(
                                [
                                    [
                                        'name'      =>  'member_id',
                                        'operate'   =>  '=',
                                        'value'     =>  (int)$member_id
                                    ],
                                    [
                                        'name'      =>  'id',
                                        'operate'   =>  '=',
                                        'value'     =>  (int)$license_id
                                    ]
                                ])->queryInsertData($this->_member_table.'_business_license', $license, true);
                            }
                        }

                        return $result;
                        
                    }, $data, $member_id);
                }
                else {
                    if(empty($member_data['verified']) && !empty($overwrite)) {
                        $member_id = $member_data['id'];
                        
                        return $this->queryTransaction(function($data, $member_id) {
                            $result = (($this->setWhere(
                            [
                                'name'      =>  'id', 
                                'operate'   =>  '=', 
                                'value'     =>  (int)$member_id
                            ])->queryUpdateData($this->_member_table, $data))?(int)$member_id:0);
                            
                            if(!empty($result)) {
                                // disable all first
                                $this->setWhere(
                                [
                                    'name'      =>  'member_id', 
                                    'operate'   =>  '=', 
                                    'value'     =>  (int)$member_id
                                ])->queryDeleteData($this->_member_table.'_preference');
                                
                                // re-insert relation if need
                                if(!empty($data['interested_topic']) && is_array($data['interested_topic'])) {
                                    foreach ($data['interested_topic'] as $interested_topic) {
                                        $this->setWhere(
                                        [
                                            [
                                                'name'      =>  'member_id', 
                                                'operate'   =>  '=', 
                                                'value'     =>  (int)$member_id
                                            ],
                                            [
                                                'name'      =>  'related_id', 
                                                'operate'   =>  '=', 
                                                'value'     =>  (int)$interested_topic
                                            ],
                                        ])->queryInsertData($this->_member_table.'_preference', [
                                            'type'          =>  3,
                                            'member_id'     =>  (int)$member_id,
                                            'related_id'    =>  (int)$interested_topic,
                                            'status'        =>  1
                                        ], true);
                                    }
                                }
                                
                                // details
                                if(!empty($data['details'])) {
                                    $data['details']['member_id'] = $member_id;
                                    $this->setWhere(
                                    [
                                        [
                                            'name'      =>  'member_id', 
                                            'operate'   =>  '=', 
                                            'value'     =>  (int)$member_id
                                        ]
                                    ])->queryInsertData($this->_member_table.'_details', $data['details'], true);
                                }
                            }

                            return $result;
                        }, $data, $member_id); 
                    }
                    else {
                        $this->setResultMessage($this->pLang('duplicate_email'), 403);
                    }
                }
            }
            else {
                $this->setResultMessage($this->pLang('bad_request'), 400);
            }
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return false;
    }
    
    public function doDelete($member_ids = 0) {
        if(!empty($member_ids)) {
            if(is_numeric($member_ids)) {
                $member_ids = [$member_ids];
            }
            else if(is_string($member_ids)){
                $member_ids = explode(',', $member_ids);
            }
            else {
                $member_ids = (array)$member_ids; 
            }
            
            // loop
            DB::beginTransaction();
            try {
                foreach ($member_ids as $member_key => $member_id) {
                    // fetch user by id
                    $member_data = $this->getByID($member_id);
                    
                    if(!empty($member_data)) {
                        $del_prefix = 'del_'.md5(uniqid(rand()));
                        $this->setWhere(
                        [
                            'name'      =>  'id', 
                            'operate'   =>  '=', 
                            'value'     =>  (int)$member_data['id']
                        ])->queryUpdateData($this->_member_table, [
                            'email'         =>  $del_prefix.'#'.$member_data['email'],
                            'status'        =>  0,
                            'deleted_by'    =>  ((!empty($this->_current_user))?$this->_current_user['id']:0),
                            'deleted_at'    =>  $this->_today_datetime
                        ], false);
                    }
                }
                DB::commit();
                return true;
            }
            catch (Exception $e) {
                $this->setResultMessage($this->pLang('query_error'), 500);
                DB::rollBack();
                throw $e;
            }
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return false;
    }
    
    public function doVerification($token = '') {
        if(!empty($token)) {
            $member_data = $this->setWhere(
            [
                [
                    'name'      =>  'verified_token', 
                    'operate'   =>  '=', 
                    'value'     =>  (string)$token
                ],
                [
                    'name'      =>  'verified', 
                    'operate'   =>  '>=', 
                    'value'     =>  0
                ],
            ])->queryOneData($this->_member_table);
            
            if(!empty($member_data)) {
                return ($this->setWhere(
                [
                    'name'      =>  'id', 
                    'operate'   =>  '=', 
                    'value'     =>  (int)$member_data['id']
                ])->queryUpdateData($this->_member_table, [
                    'verified'  =>  1
                ]));
            }
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return false;
    }
    
    public function doSavePayment($type = 'account', $data = []) {
        if(!empty($data)) {
            return $this->queryInsertData($this->_member_table.'_plan_'.$type, $data);
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return false;
    }
    
    public function getPaymentByToken($type = 'account', $token = '') {
        return ((!empty($token))?($this->setWhere(
            [
                'name'      =>  'payment_token', 
                'operate'   =>  '=', 
                'value'     =>  (string)$token
            ])->queryOneData($this->_member_table.'_plan_'.$type)):'');
    }
    
    public function renewExpirationDate($type= 'account', $member_id = 0, $data = []) {
        if(!empty($member_id) && !empty($data['token']) && !empty($data['transaction_id'])) {
            DB::beginTransaction();
            try {
                if($type == 'account') {
                    if(!empty($data['new_expiration_date'])) {
                        $this->setWhere([
                            'name'      =>  'id', 
                            'operate'   =>  '=', 
                            'value'     =>  (int)$member_id
                        ])->queryUpdateData($this->_member_table, [
                            'expiration_date_account'  => $data['new_expiration_date'],
                            'verified'  =>  1
                        ]);
                    }
                }
                else {
                    if(!empty($data['new_expiration_date_ai'])) {
                        $this->setWhere([
                            'name'      =>  'id', 
                            'operate'   =>  '=', 
                            'value'     =>  (int)$member_id
                        ])->queryUpdateData($this->_member_table, [
                            'expiration_date_visa_submission_ai'  => $data['new_expiration_date_ai']
                        ]);
                    }
                    
                    if(!empty($data['new_expiration_date_human'])) {
                        $this->setWhere([
                            'name'      =>  'id', 
                            'operate'   =>  '=', 
                            'value'     =>  (int)$member_id
                        ])->queryUpdateData($this->_member_table, [
                            'expiration_date_visa_submission_human'  => $data['new_expiration_date_human']
                        ]);
                    }
                }
                
                $this->setWhere([
                    'name'      =>  'payment_token', 
                    'operate'   =>  '=', 
                    'value'     =>  (string)$data['token']
                ])->queryUpdateData($this->_member_table.'_plan_'.$type, [
                    'status'                  => 2,
                    'payment_transaction_id'  => $data['transaction_id']
                ]);

                DB::commit();
                return true;
            }
            catch (Exception $e) {
                $this->setResultMessage($this->pLang('query_error'), 500);
                DB::rollBack();
                throw $e;
            }
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return false;
    }
    
    public function renewAvatar($member_id = 0, $path = '') {
        return ((!empty($path))?$this->setWhere([
            'name'      =>  'id', 
            'operate'   =>  '=', 
            'value'     =>  (int)$member_id
        ])->queryUpdateData($this->_member_table, [
            'avatar'  => $path
        ]):false);
    }
    
    public function renewAlias($member_id = 0, $data = []) {
        return ((!empty($data))?$this->setWhere([
            'name'      =>  'id', 
            'operate'   =>  '=', 
            'value'     =>  (int)$member_id
        ])->queryUpdateData($this->_member_table, $data):false);
    }

    public function getAllOnlyAgent($country_id = 0) {
        $query = DB::table($this->_member_table);
        $query->join($this->_member_table.'_agent', $this->_member_table.'.id', '=', $this->_member_table.'_agent.member_id');
        $query->where($this->_member_table.'.status', '>', 0);
        $query->where($this->_member_table.'_agent.status', '>', 0);
        if(!empty($country_id)) { 
            $query->where($this->_member_table.'_agent.registration_country', '=', (int)$country_id);
        }
        $query->orderBy($this->_member_table.'.alias_name', 'ASC');
        $query->select([
            $this->_member_table.'.*'
        ]);

        return $this->revisedData($query->distinct()->get()->map(function($items) {
            $data = [];
            foreach ($items as $item_key => $item_value) {
                $data[$item_key] = $item_value;
            }
            return $data;
        })->toArray(), true);
    }
    
    public function getAllOnlySP($company_type = 0, $country_id = 0) {
        $query = DB::table($this->_member_table);
        $query->join($this->_member_table.'_details', $this->_member_table.'.id', '=', $this->_member_table.'_details.member_id');
        $query->where($this->_member_table.'.status', '>', 0);
        $query->where($this->_member_table.'_details.status', '>', 0);
        if(!empty($company_type)) { 
            $query->where($this->_member_table.'_details.company_type', '=', (int)$company_type);
        }
        if(!empty($country_id)) { 
            $query->where($this->_member_table.'_details.services_country', '=', (int)$country_id);
        }
        $query->orderBy($this->_member_table.'.alias_name', 'ASC');
        $query->select([
            $this->_member_table.'.*'
        ]);
        
        return $this->revisedData($query->distinct()->get()->map(function($items) {
            $data = [];
            foreach ($items as $item_key => $item_value) {
                $data[$item_key] = $item_value;
            }
            return $data;
        })->toArray(), true);
    }
}
