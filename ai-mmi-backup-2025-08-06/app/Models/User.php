<?php
namespace App\Models;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class User extends BaseModel {
    protected $_user_table = 'user';
    
    public function __construct($data) {
        parent::__construct($data);
    }
    
    public function getAll($options = [], $pageration = true) {
        $where = [];
        
        if($this->_user_table == 'user') {
            $where[] = 
            [
                'name'      =>  'id', 
                'operate'   =>  '>', 
                'value'     =>  1
            ];
            
            if(!empty($options['keywords'])) {
                $where[] = 
                [
                    [
                        'name'      =>  'name', 
                        'operate'   =>  'like', 
                        'value'     =>  $options['keywords']
                    ],
                    [
                        'name'      =>  'email', 
                        'operate'   =>  'like', 
                        'value'     =>  $options['keywords']
                    ]
                ];
            }
        }
        else {
            $where[] = 
            [
                'name'      =>  'id', 
                'operate'   =>  '>', 
                'value'     =>  0
            ];
            
            if(!empty($options['keywords'])) {
                if(!empty($options['less'])) {
                    $where[] = 
                    [
                        [
                            'name'      =>  'code', 
                            'operate'   =>  'like', 
                            'value'     =>  $options['keywords']
                        ],
                        [
                            'name'      =>  'display_name', 
                            'operate'   =>  'like', 
                            'value'     =>  $options['keywords']
                        ],
                        [
                            'name'      =>  'telephone', 
                            'operate'   =>  'like', 
                            'value'     =>  $options['keywords']
                        ]
                    ];
                }
                else {
                    $where[] = 
                    [
                        [
                            'name'      =>  'code', 
                            'operate'   =>  'like', 
                            'value'     =>  $options['keywords']
                        ],
                        [
                            'name'      =>  'display_name', 
                            'operate'   =>  'like', 
                            'value'     =>  $options['keywords']
                        ],
                        [
                            'name'      =>  'name', 
                            'operate'   =>  'like', 
                            'value'     =>  $options['keywords']
                        ],
                        [
                            'name'      =>  'email', 
                            'operate'   =>  'like', 
                            'value'     =>  $options['keywords']
                        ],
                        [
                            'name'      =>  'telephone', 
                            'operate'   =>  'like', 
                            'value'     =>  $options['keywords']
                        ]
                    ];
                }
            }
        }

        if(!empty($options['sorting'])) {
            if(!is_array($options['sorting'])) {
                $options['sorting'] = explode(',', $options['sorting']);
            }
            $order = array_filter(array_merge($options['sorting'], ['name_asc']));
        }
        else {
            $order = array_filter(['name_asc']);
        }

        return $this->setWhere($where)->setOrder($order)->queryListData($this->_user_table, $pageration);
    }
    
    public function getByID($user_id = 0) {
        return ((!empty((int)$user_id))?$this->setWhere(
        [
            'name'      =>  'id', 
            'operate'   =>  '=', 
            'value'     =>  (int)$user_id
        ])->queryOneData($this->_user_table):false);
    }
    
    public function getByUserName($user_name = '') {
        return ((!empty((string)$user_name))?$this->setWhere(
        [
            'name'      =>  'name', 
            'operate'   =>  '=', 
            'value'     =>  (string)$user_name
        ])->queryOneData($this->_user_table):false);
    }
    
    public function getByEmail($user_email = '') {
        return ((!empty((string)$user_email))?$this->setWhere(
        [
            'name'      =>  'email', 
            'operate'   =>  '=', 
            'value'     =>  (string)$user_email
        ])->queryOneData($this->_user_table):false);
    }
    
    public function getByToken($user_token = '') {
        // first, fetch token
        $user_token_data = ((!empty((string)$user_token))?$this->setWhere(
        [
            [
                'name'      =>  'type', 
                'operate'   =>  '=', 
                'value'     =>  1
            ],
            [
                'name'      =>  'value', 
                'operate'   =>  '=', 
                'value'     =>  (string)$user_token
            ]
        ])->queryOneData($this->_user_table.'_token'):false);
        
        // second, fetch user
        return ((!empty($user_token_data))?$this->setWhere(
        [
            'name'      =>  'id', 
            'operate'   =>  '=', 
            'value'     =>  (int)$user_token_data['user_id']
        ])->queryOneData($this->_user_table):false);
    }
    
    public function doLogin($user_id = '', $password = '') {
        if(!empty((string)$user_id) && !empty((string)$password)) {
            // fetch user by name or email
            if(!filter_var($user_id, FILTER_VALIDATE_EMAIL)) {
                $user_data = $this->getByUserName($user_id);
            }
            else {
                $user_data = $this->getByEmail($user_id);
            }

            if((!empty($user_data) && $user_data['status'] == 1) && \Illuminate\Support\Facades\Hash::check((string)$password, $user_data['password'])) {
                DB::beginTransaction();
                try {
                    // disable old access token if need
                    if(!empty($user_data['signle_mode'])) {
                        $this->setWhere(
                        [
                            'name'      =>  'user_id', 
                            'operate'   =>  '=', 
                            'value'     =>  (int)$user_data['id']
                        ])->queryDeleteData($this->_user_table.'_token', false); 
                    }

                    // new access token
                    $new_access_token = md5(uniqid(rand()));
                    $this->queryInsertData($this->_user_table.'_token', [
                        'type'          =>  1,
                        'user_id'       =>  (int)$user_data['id'],
                        'value'         =>  $new_access_token,
                        'created_by'    =>  (int)$user_data['id']
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
    
    public function doLogout($user_token = '') {
        return ((!empty((string)$user_token))?$this->setWhere(
        [
            'name'      =>  'value', 
            'operate'   =>  '=', 
            'value'     =>  (string)$user_token
        ])->queryDeleteData($this->_user_table.'_token'):false);
    }
    
    public function forgotPassword($email = '') {
        if(!empty((string)$email)) {
            // fetch user by email
            $user_data = $this->getByEmail($email);

            if(!empty($user_data)) {
                // fetch latest token
                $user_token_data = $this->setWhere(
                [
                    [
                        'name'      =>  'type', 
                        'operate'   =>  '=', 
                        'value'     =>  2
                    ],
                    [
                        'name'      =>  'user_id', 
                        'operate'   =>  '=', 
                        'value'     =>  (int)$user_data['id']
                    ]
                ])->setOrder(['id_desc'])->queryOneData($this->_user_table.'_token');

                if((empty($user_token_data)) || (!empty($user_token_data) && strtotime($user_token_data['created_at'])+3600 < strtotime($this->_today_datetime))) {
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
                                'name'      =>  'user_id', 
                                'operate'   =>  '=', 
                                'value'     =>  (int)$user_data['id']
                            ]
                        ])->setOrder(['id_desc'])->queryDeleteData($this->_user_table.'_token', false);

                        // new reset token
                        $new_reset_password_token = md5(uniqid(rand()));
                        $this->queryInsertData($this->_user_table.'_token', [
                            'type'          =>  2,
                            'user_id'       =>  (int)$user_data['id'],
                            'value'         =>  $new_reset_password_token,
                            'expiry_at'     =>  date('Y-m-d H:i:s', strtotime($this->_today_datetime)+2*3600),
                            'created_by'    =>  (int)$user_data['id']
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
            $user_token_data = $this->setWhere(
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
            ])->setOrder(['id_desc'])->queryOneData($this->_user_table.'_token');

            // prevent frequent operation
            if(!empty($user_token_data) && strtotime($user_token_data['expiry_at']) >= strtotime($this->_today_datetime)) {
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
                            'name'      =>  'user_id', 
                            'operate'   =>  '=', 
                            'value'     =>  (int)$user_token_data['user_id']
                        ]
                    ])->setOrder(['id_desc'])->queryDeleteData($this->_user_table.'_token', false);

                    // renew password
                    $this->setWhere(
                    [
                        'name'          =>  'id', 
                        'operate'       =>  '=', 
                        'value'         =>  $user_token_data['user_id']
                    ])->queryUpdateData($this->_user_table, [
                        'password'      =>  \Illuminate\Support\Facades\Hash::make($password),
                        'updated_by'    =>  $user_token_data['user_id']
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

    public function doSave($data = [], $user_id = 0) {
        if(!empty($data)) {
            if($user_id > 0 && empty($data['password'])) {
                $validator = Validator::make($data, 
                [
                    'name'     =>  'required',
                    'email'    =>  'required|email'
                ]);
            }
            else {
                $validator = Validator::make($data, 
                [
                    'name'             =>  'required',
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
                    if ($user_id > 0){
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

                // unique user & email
                $user_data = $this->setWhere(
                [
                    [
                        'name'      =>  'id', 
                        'operate'   =>  '!=', 
                        'value'     =>  (int)$user_id
                    ],
                    [
                        'name'      =>  'name', 
                        'operate'   =>  '=', 
                        'value'     =>  (string)$data['name']
                    ]
                ])->queryOneData($this->_user_table);

                if(empty($user_data)) {
                    $user_data = $this->setWhere(
                    [
                        [
                            'name'      =>  'id', 
                            'operate'   =>  '!=', 
                            'value'     =>  (int)$user_id
                        ],
                        [
                            'name'      =>  'email', 
                            'operate'   =>  '=', 
                            'value'     =>  (string)$data['email']
                        ]
                    ])->queryOneData($this->_user_table);

                    if(empty($user_data)) {
                        if((int)$user_id == 0) {
                            return $this->queryInsertData($this->_user_table, $data);
                        }
                        else {
                            return (($this->setWhere(
                            [
                                'name'      =>  'id', 
                                'operate'   =>  '=', 
                                'value'     =>  (int)$user_id
                            ])->queryUpdateData($this->_user_table, $data))?(int)$user_id:0);
                        }
                    }
                    else {
                        $this->setResultMessage($this->pLang('duplicate_email'), 403);
                    }
                }
                else {
                    $this->setResultMessage($this->pLang('duplicate_user'), 403);
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
    
    public function doDelete($user_ids = 0) {
        if(!empty($user_ids)) {
            if(is_numeric($user_ids)) {
                $user_ids = [$user_ids];
            }
            else if(is_string($user_ids)){
                $user_ids = explode(',', $user_ids);
            }
            else {
                $user_ids = (array)$user_ids; 
            }
            
            // loop
            DB::beginTransaction();
            try {
                foreach ($user_ids as $user_key => $user_id) {
                    // fetch user by id
                    $user_data = $this->getByID($user_id);
                    
                    if(!empty($user_data)) {
                        $del_prefix = 'del_'.md5(uniqid(rand()));
                        $this->setWhere(
                        [
                            'name'      =>  'id', 
                            'operate'   =>  '=', 
                            'value'     =>  (int)$user_data['id']
                        ])->queryUpdateData($this->_user_table, [
                            'name'          =>  $del_prefix.'#'.$user_data['name'],
                            'email'         =>  $del_prefix.'#'.$user_data['email'],
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
    
    /* role */
    public function getRoleAll($options = [], $pageration = true) {
        $where = [];
        if(!empty($options['keywords'])) {
            $where[] = 
            [
                [
                    'name'      =>  'name', 
                    'operate'   =>  'like', 
                    'value'     =>  $options['keywords']
                ]
            ];
        }
        
        if(!empty($options['sorting'])) {
            if(!is_array($options['sorting'])) {
                $options['sorting'] = explode(',', $options['sorting']);
            }
            $order = array_filter(array_merge($options['sorting'], ['name_asc']));
        }
        else {
            $order = array_filter(['name_asc']);
        }

        return $this->setWhere($where)->setOrder($order)->queryListData($this->_user_table.'_role', $pageration);
    }

    public function getRoleByID($role_id = 0) {
        return ((!empty((int)$role_id))?$this->setWhere(
        [
            'name'      =>  'id', 
            'operate'   =>  '=', 
            'value'     =>  (int)$role_id
        ])->queryOneData($this->_user_table.'_role'):false);
    }
    
    public function doRoleSave($data = [], $role_id = 0) {
        $validator = Validator::make($data, 
        [
            'name'     =>  'required'
        ]);
        
        if(!$validator->fails()) {
            if(!empty($data['allowed'])) {
                foreach ($data['allowed'] as $key => $value) {
                    $data['allowed'][$key] = array_merge([101],$value);
                }
            }

            if((int)$role_id == 0) {
                return $this->queryInsertData($this->_user_table.'_role', $data);
            }
            else {
                return (($this->setWhere(
                [
                    'name'      =>  'id', 
                    'operate'   =>  '=', 
                    'value'     =>  (int)$role_id
                ])->queryUpdateData($this->_user_table.'_role', $data))?(int)$role_id:0);
            }
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return false;
    }
    
    public function doRoleDelete($role_ids = 0) {
        if(!empty($role_ids)) {
            if(is_numeric($role_ids)) {
                $role_ids = [$role_ids];
            }
            else if(is_string($role_ids)){
                $role_ids = explode(',', $role_ids);
            }
            else {
                $role_ids = (array)$role_ids;
            }
        }
        
        return $this->setWhere(
        [
            'name'      =>  'id', 
            'operate'   =>  'in', 
            'value'     =>  $role_ids
        ])->queryDeleteData($this->_user_table.'_role');
    }
}