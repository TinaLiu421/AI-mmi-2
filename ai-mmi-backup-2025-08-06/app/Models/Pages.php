<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Pages extends BaseModel {
    protected $_page_table = 'page';

    public function __construct($data) {
        parent::__construct($data);
        if(!empty($data['table'])) {
            $this->_page_table = $data['table'];
        }
    }

    public function getAll($lang = 0, $options = [], $pagination = true) {
        $where = [];
        if(!empty($lang)) {
            $where = array_merge($where,
            [
                [
                    'name'      =>  'lang', 
                    'operate'   =>  '=', 
                    'value'     =>  $lang
                ]
            ]);
        }
        
        $parent_id = (!empty($options) && !empty($options['parent_id']))?$options['parent_id']:$this->getParamValue('parent_id',0);
        $where = array_merge($where,
        [
            [
                'name'      =>  'parent_id', 
                'operate'   =>  '=', 
                'value'     =>  $parent_id
            ]
        ]);
        
        $keywords = (!empty($options) && !empty($options['keywords']))?$options['keywords']:'';
        if(!empty($keywords)) {
            $where = array_merge($where,
            [
                [
                    'name'      =>  'title', 
                    'operate'   =>  'like', 
                    'value'     =>  $keywords
                ]
            ]);
        }
        
        if(!$this->_is_backend) {
            $where = array_merge($where,
            [
                [
                    'name'      =>  'disabled', 
                    'operate'   =>  '=', 
                    'value'     =>  0
                ]
            ]);
        }

        $extra_where = (!empty($options) && !empty($options['where']))?$options['where']:'';
        if(!empty($extra_where)) {
            $where = array_merge($where, $extra_where);
        }

        if(!empty($options['sorting'])) {
            if(!is_array($options['sorting'])) {
                $options['sorting'] = explode(',', $options['sorting']);
            }
            $order = array_filter(array_merge($options['sorting'], ['seq_desc']));
        }
        else {
            $order = array_filter(['seq_desc']);
        }

        $result = $this->setWhere($where)
                ->setOrder($order)
                ->setLimitSize((!empty($options['limit_size']))?$options['limit_size']:0)
                ->setCurrentPage((!empty($options['page_num']))?$options['page_num']:0)
                ->setPageSize((!empty($options['page_size']))?$options['page_size']:20)
                ->setSelectCols((!empty($options['select_cols']))?$options['select_cols']:'')
                ->queryListData($this->_page_table, $pagination);
        
        if(empty($pagination)) {
            $result['data'] = $result;
        }

        if(!empty($result['data']) && !empty($options['media_files'])) {
            /*
            $options['media_files'] = 
            [
                ['type' => 'page', 'category' => 'gallery']
            ];
            */
            $media_files_model = (new Media_Files(null));
            $included_utoken = [];
            foreach ($result['data'] as $data_key => $data_value) {
                $included_utoken[] = $data_value['utoken'];
            }
            foreach ($options['media_files'] as $mfile) {
                $media_files_data = $media_files_model->getAll($included_utoken, $mfile['type'], $mfile['category']);
                if(!empty($media_files_data)) {
                    foreach ($result['data'] as $data_key => $data_value) {
                        if(empty($result['data'][$data_key]['media_files'])) {
                            $result['data'][$data_key]['media_files'] = [];
                        }
                        foreach ($media_files_data as $media_files_key => $media_files) {
                            if(empty($result['data'][$data_key]['media_files'][$mfile['category']])) {
                                $result['data'][$data_key]['media_files'][$mfile['category']] = [];
                            }
                            if($data_value['utoken'] == $media_files['related_token']) {
                                $result['data'][$data_key]['media_files'][$mfile['category']][] = $media_files;
                            }
                        }
                    }
                }
            }
        }
        
        if(empty($pagination)) {
            $result = $result['data'];
        }

        return $result;
    }
    
    public function getByID($page_id = 0, $lang = 0, $options = []) {
        if(!empty($options['parent_id'])) {
            $result = ((!empty((int)$page_id))?$this->setWhere(
            [
                [
                    'name'      =>  'parent_id', 
                    'operate'   =>  '=', 
                    'value'     =>  (int)$options['parent_id']
                ],
                [
                    'name'      =>  'id', 
                    'operate'   =>  '=', 
                    'value'     =>  (int)$page_id
                ],
                [
                    'name'      =>  'lang', 
                    'operate'   =>  ((int)$lang > 0)?'=':'>=', 
                    'value'     =>  (int)$lang
                ],
                [
                    'name'      =>  'disabled',
                    'operate'   =>  (($this->_is_backend)?'>=':'='), 
                    'value'     =>  0
                ]
            ])->setSelectCols((!empty($options['select_cols']))?$options['select_cols']:'')->queryListData($this->_page_table, false):false);
        }
        else {
            $result = ((!empty((int)$page_id))?$this->setWhere(
            [
                [
                    'name'      =>  'id', 
                    'operate'   =>  '=', 
                    'value'     =>  (int)$page_id
                ],
                [
                    'name'      =>  'lang', 
                    'operate'   =>  ((int)$lang > 0)?'=':'>=', 
                    'value'     =>  (int)$lang
                ],
                [
                    'name'      =>  'disabled',
                    'operate'   =>  (($this->_is_backend)?'>=':'='), 
                    'value'     =>  0
                ]
            ])->setSelectCols((!empty($options['select_cols']))?$options['select_cols']:'')->queryListData($this->_page_table, false):false);
        }

        if(!empty($result)) {
            if((int)$lang == 0) {
                $revised_result = [];
                foreach ($result as $key => $value) {
                    $revised_result[$value['lang']] = $value;
                }
                $result = $revised_result;
            }
            else {
                $result = reset($result);
                if(!empty($result) && !empty($options) && !empty($options['media_files'])) {
                    /*
                    $options['media_files'] = 
                    [
                        ['type' => 'page', 'category' => 'gallery']
                    ];
                    */
                    $result['media_files'] = [];
                    foreach ($options['media_files'] as $mfile) {
                        $result['media_files'][$mfile['category']] = (new Media_Files(null))->getAll($result['utoken'], $mfile['type'], $mfile['category']);
                    }
                }
            }
        }
        
        return $result;
    }
    
    public function getByPath($path = '', $lang = 0, $options = []) {
        if(!empty($options['parent_id'])) {
            $result = ((!empty((string)$path))?$this->setWhere(
            [
                [
                    'name'      =>  'parent_id', 
                    'operate'   =>  '=', 
                    'value'     =>  (int)$options['parent_id']
                ],
                [
                    'name'      =>  'path', 
                    'operate'   =>  '=', 
                    'value'     =>  (string)$path
                ],
                [
                    'name'      =>  'lang', 
                    'operate'   =>  ((int)$lang > 0)?'=':'>=', 
                    'value'     =>  (int)$lang
                ],
                [
                    'name'      =>  'disabled',
                    'operate'   =>  (($this->_is_backend)?'>=':'='), 
                    'value'     =>  0
                ]
            ])->setSelectCols((!empty($options['select_cols']))?$options['select_cols']:'')->queryListData($this->_page_table, false):false);
        }
        else {
            $result = ((!empty((string)$path))?$this->setWhere(
            [
                [
                    'name'      =>  'path', 
                    'operate'   =>  '=', 
                    'value'     =>  (string)$path
                ],
                [
                    'name'      =>  'lang', 
                    'operate'   =>  ((int)$lang > 0)?'=':'>=', 
                    'value'     =>  (int)$lang
                ],
                [
                    'name'      =>  'disabled',
                    'operate'   =>  (($this->_is_backend)?'>=':'='), 
                    'value'     =>  0
                ]
            ])->setSelectCols((!empty($options['select_cols']))?$options['select_cols']:'')->queryListData($this->_page_table, false):false);
        }

        if(!empty($result)) {
            if((int)$lang == 0) {
                $revised_result = [];
                foreach ($result as $key => $value) {
                    $revised_result[$value['lang']] = $value;
                }
                $result = $revised_result;
            }
            else {
                $result = reset($result);
                if(!empty($result) && !empty($options) && !empty($options['media_files'])) {
                    /*
                    $options['media_files'] = 
                    [
                        ['type' => 'page', 'category' => 'gallery']
                    ];
                    */
                    $result['media_files'] = [];
                    foreach ($options['media_files'] as $mfile) {
                        $result['media_files'][$mfile['category']] = (new Media_Files(null))->getAll($result['utoken'], $mfile['type'], $mfile['category']);
                    }
                }
            }
        }
        
        return $result;
    }
    
    public function getParentsNode($page_id = 0, $lang = 0, $options = []) {
        return $this->setWhere(
        [
            [
                'name'      =>  'lang',
                'operate'   =>  '=', 
                'value'     =>  (int)$lang
            ],
            [
                'name'      =>  'disabled',
                'operate'   =>  (($this->_is_backend)?'>=':'='), 
                'value'     =>  0
            ]
        ])->setSelectCols((!empty($options['select_cols']))?$options['select_cols']:'')->recursiveParents($this->_page_table, $page_id);
    }
    
    public function getChildsNode($parent_id = 0, $lang = 0, $options = []) {
        return $this->setWhere(
        [
            [
                'name'      =>  'lang',
                'operate'   =>  '=', 
                'value'     =>  (int)$lang
            ],
            [
                'name'      =>  'disabled',
                'operate'   =>  (($this->_is_backend)?'>=':'='), 
                'value'     =>  0
            ]
        ])->setOrder([
            'seq_desc'
        ])->setSelectCols((!empty($options['select_cols']))?$options['select_cols']:'')->recursiveChilds($this->_page_table, $parent_id);
    }
    
    public function doSave($data = [], $page_id = 0) {
        // table structure
        $table_structure = DB::select('describe '.$this->_db_prefix.$this->_page_table);
        $table_mapping = [];
        if(!empty($table_structure)) {
            foreach ($table_structure as $structure) {
                $structure = (array)$structure;
                $table_mapping[$structure['Field']] = strtolower(preg_replace('/(.*)((\()(\d+)(\)))/', '$1', $structure['Type']));
            }
        }
        
        // get id
        if(empty($page_id) && !empty($data['id'])) {
            $page_id = $data['id'];
        }
        unset($data['id']);
        
        if(isset($data['path'])) {
            $data['path'] = preg_replace('/[\s]/i', '_', $this->toPlainText(preg_replace('/[^a-z0-9\-\_\s]/i', '', $data['path'])));
            if(empty($data['path'])) {
                $data['path'] = md5(uniqid(rand()));
            }
            $data['path'] = strtolower($data['path']);
        }
        
        // level
        $paeg_level = 1;
        if(!empty($data['parent_id'])) {
            $parent_data = $this->setWhere(
            [
                'name'      =>  'id', 
                'operate'   =>  '=', 
                'value'     =>  $data['parent_id']
            ])->queryOneData($this->_page_table);
            if(!empty($parent_data) && !empty($parent_data['level'])) {
                $paeg_level = $parent_data['level'] + 1;
            }
        }
        
        // revise input data, also remove unnecessary fields
        if(!empty($data)) {
            $revised_data = [];
            foreach ($this->_support_lang as $lang_key => $lang) {
                foreach ($data as $key => $value) {
                    if(!empty($table_mapping[$key])) {
                        if(is_array($value)) {
                            $revised_data[$lang_key][$key] = (!empty($value[$lang_key]))?$value[$lang_key]:((!empty($value[0]))?$value[0]:'');
                        }
                        else {
                            $revised_data[$lang_key][$key] = $value;
                        }
                        // convert input format
                        if(in_array($table_mapping[$key], ['int', 'tinyint'])) {
                            $revised_data[$lang_key][$key] = (int)$revised_data[$lang_key][$key];
                        }
                        else if(in_array($table_mapping[$key], ['float', 'double'])) {
                            $revised_data[$lang_key][$key] = round((double)$revised_data[$lang_key][$key], 2);
                        }
                    }
                }
            }

            // update or insert
            DB::beginTransaction();
            try {
                $new_page_id = 0;
                $new_page_seq = 1;
                if($page_id == 0) {
                    // find max id & seq
                    $find_max = DB::table($this->_page_table)->max('id');
                    if(!empty($find_max)) {
                        $new_page_id = $find_max + 1;
                    }
                    else {
                        $new_page_id = 1;
                    }
                    $find_max = DB::table($this->_page_table)->where('parent_id', ((!empty($data['parent_id']))?$data['parent_id']:0))->max('seq');
                    if(!empty($find_max)) {
                        $new_page_seq = $find_max + 1;
                    }
                }
                
                foreach ($revised_data as $key => $value) {
                    $value['level'] = $paeg_level;
                    if($page_id == 0) {
                        $value['id'] = $new_page_id;
                        $value['seq'] = $new_page_seq;
                        $value['lang'] = $key;
                        $this->queryInsertData($this->_page_table, $value, false);
                    }
                    else {
                        $temp_page_data = $this->setWhere(
                        [
                            [
                                'name'      =>  'id', 
                                'operate'   =>  '=', 
                                'value'     =>  (int)$page_id
                            ],
                            [
                                'name'      =>  'lang', 
                                'operate'   =>  '=', 
                                'value'     =>  (int)$key
                            ]
                        ])->queryOneData($this->_page_table);
                        
                        if(!empty($temp_page_data)) {
                            $this->setWhere(
                            [
                                [
                                    'name'      =>  'id', 
                                    'operate'   =>  '=', 
                                    'value'     =>  (int)$page_id
                                ],
                                [
                                    'name'      =>  'lang', 
                                    'operate'   =>  '=',
                                    'value'     =>  (int)$key
                                ]
                            ])->queryUpdateData($this->_page_table, $value, false);
                        }
                        else {
                            $temp_page_data = $this->setWhere(
                            [
                                'name'      =>  'id', 
                                'operate'   =>  '=', 
                                'value'     =>  (int)$page_id
                            ])->queryOneData($this->_page_table);
                            
                            $value['id'] = $temp_page_data['id'];
                            $value['utoken'] = $temp_page_data['utoken'];
                            $value['seq'] = $temp_page_data['seq'];
                            $value['lang'] = $key;
                            $this->queryInsertData($this->_page_table, $value, false);
                        }
                    }
                }
                
                DB::commit();
                return (!empty($new_page_id))?$new_page_id:$page_id;
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
    
    public function doDelete($page_id = 0) {
        if(!empty($page_id)) {
            if(is_numeric($page_id)) {
                $page_id = [$page_id];
            }
            else if(is_string($page_id)){
                $page_id = explode(',', $page_id);
            }
            else {
                $page_id = (array)$page_id;
            }
        }
        
        return $this->setWhere(
        [[
            'name'      =>  'id', 
            'operate'   =>  '>', 
            'value'     =>  0
        ],
        [
            'name'      =>  'id', 
            'operate'   =>  'in', 
            'value'     =>  $page_id
        ]])->queryDeleteData($this->_page_table);
    }
    
    public function setDisabled($page_id = 0) {
        $page_data = $this->setWhere(
        [
            'name'      =>  'id', 
            'operate'   =>  '=', 
            'value'     =>  (int)$page_id
        ])->queryOneData($this->_page_table);
        
        if(!empty($page_data)) {
            $new_disabled = (!empty($page_data['disabled']))?0:1;
            return $this->setWhere(
            [
                'name'      =>  'id', 
                'operate'   =>  '=', 
                'value'     =>  (int)$page_data['id']
            ])->queryUpdateData($this->_page_table, ['disabled'  =>  $new_disabled]);
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return false;
    }

    public function switchSeq($page_id = 0, $from_seq = 0, $to_seq = 0, $options = []) {
        $page_data = $this->getByID($page_id);
        if(!empty($page_data) && !empty($from_seq) && !empty($to_seq)) {
            $direction = ((int)$from_seq > (int)$to_seq)?'up':'down';
            $from_page_data = reset($page_data);

            // extra filter
            $extra_where = [];
            $keywords = $this->getParamValue('keywords');
            if(!empty($keywords)) {
                $extra_where[] = '`title` LIKE \'%'.$this->specialChars($keywords).'%\'';
            } 
            if(!empty($options['where'])) {
                foreach ($options['where'] as $w) {
                    $w = array_values($w);
                    if(isset($w[0]) && isset($w[1]) && isset($w[2])) {
                        $w = ['name' => $w[0], 'operate' => $w[1], 'value' => $w[2]];
                    }
                    switch (strtolower($w['operate'])) {
                        case 'in':
                            $extra_where[] = '`'.$w['name'].'` IN ('.(array)$w['value'].')';
                            break;
                        case 'like':
                            $extra_where[] = '`'.$w['name'].'` LIKE \'%'.$this->specialChars($w['value']).'%\'';
                            break;
                        default:
                            $extra_where[] = '`'.$w['name'].'` '.strtoupper($w['operate']).' \''.$this->specialChars($w['value']).'\'';
                    }
                }
            }

            // find match page by seq number
            if($direction == 'down') {
                $sql = 'SELECT * FROM (';
                $sql.= 'SELECT `id`,`title`, `seq`, ROW_NUMBER() OVER(ORDER BY `seq` DESC) AS `new_seq` ';
                $sql.= 'FROM `'.($this->_db_prefix.$this->_page_table).'` ';
                $sql.=' WHERE `status` >= 0 AND `parent_id` = '.($from_page_data['parent_id']).((!empty($extra_where))?(' AND '. implode(' AND ', $extra_where)):'').' GROUP BY `id`';
                $sql.= ') AS `t` WHERE `new_seq` > '.((int)$from_seq).' AND `new_seq` <= '.((int)$to_seq).' ORDER BY `new_seq` ASC';
                $match_page_data = $this->queryRowSQL($sql);
            }
            else {
                $sql = 'SELECT * FROM (';
                $sql.= 'SELECT `id`,`title`, `seq`, ROW_NUMBER() OVER(ORDER BY `seq` DESC) AS `new_seq` ';
                $sql.= 'FROM `'.($this->_db_prefix.$this->_page_table).'` ';
                $sql.=' WHERE `status` >= 0 AND `parent_id` = '.($from_page_data['parent_id']).((!empty($extra_where))?(' AND '. implode(' AND ', $extra_where)):'').' GROUP BY `id`';
                $sql.= ') AS `t` WHERE `new_seq` >= '.((int)$to_seq).' AND `new_seq` < '.((int)$from_seq).' ORDER BY `new_seq` DESC';
                $match_page_data = $this->queryRowSQL($sql);
            }
            
            // loop
            if(!empty($from_page_data) && !empty($match_page_data)) {
                DB::beginTransaction();
                try {
                    foreach ($match_page_data as $match_page) {
                        if((int)$from_page_data['id'] != (int)$match_page['id']) {
                            $to_page_data = $this->getByID($match_page['id']);
                            $to_page_data = reset($to_page_data);
                            if(!empty($to_page_data) && ((int)$from_page_data['seq'] != (int)$to_page_data['seq'])) {
                                $this->setWhere(
                                [
                                    'name'      =>  'id', 
                                    'operate'   =>  '=', 
                                    'value'     =>  (int)$from_page_data['id']
                                ])->queryUpdateData($this->_page_table, ['seq'  =>  $to_page_data['seq']], false);

                                $this->setWhere(
                                [
                                    'name'      =>  'id', 
                                    'operate'   =>  '=', 
                                    'value'     =>  (int)$to_page_data['id']
                                ])->queryUpdateData($this->_page_table, ['seq'  =>  $from_page_data['seq']], false);
                                
                                $from_page_data['seq'] = $to_page_data['seq'];
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
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return false;
    }
}