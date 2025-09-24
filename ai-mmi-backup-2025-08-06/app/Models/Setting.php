<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Setting extends BaseModel {
    protected $_setting_table = 'setting';
    
    public function __construct($data) {
        parent::__construct($data);
    }
    
    public function getByName($name = '', $lang = 0) {
        if(empty($lang)) {
            $find_value = [];
            $data = ((!empty((string)$name))?$this->setWhere(['name', '=', (string)$name])->queryListData($this->_setting_table, false):false);
            if(!empty($data)) {
                foreach ($data as $key => $value) {
                    if(!empty($value['lang'])) {
                        $find_value[$value['lang']] = $value['value'];
                    }
                    else {
                        $find_value = $value['value'];
                    }
                }
            }
            
            return $find_value;
        }
        else {
            $data = ((!empty((string)$name))?$this->setWhere(
            [
                [
                    'name'      =>  'name', 
                    'operate'   =>  '=', 
                    'value'     =>  (string)$name
                ],
                [
                    'name'      =>  'lang', 
                    'operate'   =>  '=', 
                    'value'     =>  (int)$lang
                ]
            ])->queryOneData($this->_setting_table):false);
            
            return (!empty($data))?$data['value']:'';
        }
    }

    public function doSave($data = []) {
        if(!empty($data)) {
            DB::beginTransaction();
            try {
                foreach ($data as $name => $value) {
                    if(is_array($value)) {
                        foreach ($value as $lang => $lang_value) {
                            $setting_data = $this->setWhere(
                            [
                                [
                                    'name'      =>  'name', 
                                    'operate'   =>  '=', 
                                    'value'     =>  (string)$name
                                ],
                                [
                                    'name'      =>  'lang', 
                                    'operate'   =>  $lang, 
                                    'value'     =>  (int)$lang
                                ]
                            ])->queryOneData($this->_setting_table);
                            if(empty($setting_data)) {
                                $this->queryInsertData($this->_setting_table, [
                                    'name'  =>  $name,
                                    'value' =>  $lang_value,
                                    'lang'  =>  $lang
                                ], false);
                            }
                            else {
                                $this->setWhere(
                                [
                                    [
                                        'name'      =>  'name', 
                                        'operate'   =>  '=', 
                                        'value'     =>  (string)$name
                                    ],
                                    [
                                        'name'      =>  'lang', 
                                        'operate'   =>  $lang, 
                                        'value'     =>  (int)$lang
                                    ]
                                ])->queryUpdateData($this->_setting_table, [
                                    'value' =>  $lang_value,
                                ], false);
                            }
                        }
                    }
                    else {
                        $lang = 0;
                        $setting_data = $this->setWhere(
                        [
                            [
                                'name'      =>  'name', 
                                'operate'   =>  '=', 
                                'value'     =>  (string)$name
                            ],
                            [
                                'name'      =>  'lang', 
                                'operate'   =>  $lang, 
                                'value'     =>  (int)$lang
                            ]
                        ])->queryOneData($this->_setting_table);
                        if(empty($setting_data)) {
                            $this->queryInsertData($this->_setting_table, [
                                'name'  =>  $name,
                                'value' =>  $value,
                                'lang'  =>  $lang
                            ], false);
                        }
                        else {
                            $this->setWhere(
                            [
                                [
                                    'name'      =>  'name', 
                                    'operate'   =>  '=', 
                                    'value'     =>  (string)$name
                                ],
                                [
                                    'name'      =>  'lang', 
                                    'operate'   =>  $lang, 
                                    'value'     =>  (int)$lang
                                ]
                            ])->queryUpdateData($this->_setting_table, [
                                'value' =>  $value,
                            ], false);
                        }
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
}
