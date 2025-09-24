<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;

date_default_timezone_set('Asia/Hong_Kong');

class BaseModel extends Model {
    use HasFactory;
    
    protected $_today_datetime = null;
    protected $_today_date = null;
    
    protected $_current_user = null;
    protected $_support_lang = [];

    protected $_where = [];
    protected $_order = [];

    protected $_select_cols = [];
    
    protected $_limit_size = 0;
    
    protected $_page_number = 0;
    protected $_page_size = 20;
    protected $_pagination = [];
    
    protected $_result_message = '';
    protected $_result_code = 200;  // 200: OK, 400: Bad Request, 403: Forbidden, 404: Not Found, 408: Time Out, 500: Server Error
    
    protected $_is_backend = false;
    protected $_db_prefix = '';

    public function __construct($data = []){
        // set current date & time
        $this->_today_datetime = date('Y-m-d H:i:s');
        $this->_today_date = date('Y-m-d', strtotime($this->_today_datetime));

        $this->_current_user = (!empty($data['current_user']))?$data['current_user']:null;
        $this->_support_lang = \Illuminate\Support\Facades\Config::get('app_portal.support_lang');
        
        $this->_is_backend = (!empty($data['is_backend']))?true:false;
        $this->_db_prefix = env('DB_TABLEPREFIX');
    }

    protected function setWhere($options = []) {
        /*
        case 1: WHERE WHERE `id` > 1
        $options = ['name' => 'id', 'operate' => '>', 'value' => 1]
   
        case 2: WHERE `name` LIKE '%abc%' AND `email` LIKE '%abc%'
        $options = 
        [
            ['name' => 'name', 'operate' => 'like', 'value' => 'abc'],
            ['name' => 'email', 'operate' => 'like', 'value' => 'abc']
        ];
         
        case 3: WHERE (`name` LIKE '%abc%' OR `email` LIKE '%abc%')      
        $options = 
        [
            [
                ['name' => 'name', 'operate' => 'like', 'value' => 'abc'],
                ['name' => 'email', 'operate' => 'like', 'value' => 'abc']
            ]
        ];
        
        case 4: WHERE `id` > 1 AND (`name` LIKE '%abc%' OR `email` LIKE '%abc%')      
        $options = 
        [
            ['name' => 'id', 'operate' => '>', 'value' => 1],
            [
                ['name' => 'name', 'operate' => 'like', 'value' => 'abc'],
                ['name' => 'email', 'operate' => 'like', 'value' => 'abc']
            ]
        ];
        
        short format: ['name', 'like', 'abc']; 
        */
        
        /*$options = ['name' => 'id', 'operate' => '>', 'value' => 1];
        $options = 
        [
            ['name' => 'name', 'operate' => 'like', 'value' => 'abc'],
            ['name' => 'email', 'operate' => 'like', 'value' => 'abc']
        ];
        $options = 
        [
            [
                ['name' => 'name', 'operate' => 'like', 'value' => 'abc'],
                ['name' => 'email', 'operate' => 'like', 'value' => 'abc']
            ]
        ];
        $options = 
        [
            ['name' => 'id', 'operate' => '>', 'value' => 1],
            [
                ['name' => 'name', 'operate' => 'like', 'value' => 'abc'],
                ['name' => 'email', 'operate' => 'like', 'value' => 'abc']
            ]
        ];*/

        $loop_index = 0;
        $loop_sub_index = 0;
        $extra_where = [];
        if(!empty($options)) {
            foreach ($options as $option_key => $option) {
                if(!is_array($option)) {
                    $option = array_values($options);
                    if(isset($option[0]) && isset($option[1]) && isset($option[2])) {
                        $extra_where[$loop_index] = ['name' => $option[0], 'operate' => $option[1], 'value' => $option[2]];
                        $loop_index++;
                    }
                    break;
                }
                else {
                    foreach($option as $sub_option_key => $sub_option) {
                        if(!is_array($sub_option)) {
                            $sub_option = array_values($option);
                            if(isset($sub_option[0]) && isset($sub_option[1]) && isset($sub_option[2])) {
                                $extra_where[$loop_index] = ['name' => $sub_option[0], 'operate' => $sub_option[1], 'value' => $sub_option[2]];
                                $loop_index++;
                            }
                            break;
                        }
                        else {
                            $sub_option = array_values($sub_option);
                            if(isset($sub_option[0]) && isset($sub_option[1]) && isset($sub_option[2])) {
                                $extra_where[$loop_index][$loop_sub_index] = ['name' => $sub_option[0], 'operate' => $sub_option[1], 'value' => $sub_option[2]];
                                $loop_sub_index++;
                            }
                        }
                    }
                }
            }
            $this->_where = array_merge($this->_where, $extra_where);
        }
        return $this;
    }
    
    protected function setOrder($options = []) {
        $this->_order = $options;
        return $this;
    }
    
    protected function setLimitSize($size = 0) {
        $this->_limit_size = $size;
        return $this;
    }
    
    protected function setCurrentPage($page_number = 0) {
        $this->_page_number = max(0, (int)$page_number);
        return $this;
    }

    protected function setPageSize($size = 0) {
        $this->_page_size = max(1, (int)$size);
        return $this;
    }
    
    protected function setSelectCols($cols = []) {
        $this->_select_cols = $cols;
        return $this;
    }

    protected function queryListTotal($table_name, $including_deleted = false) {
        if(!empty($table_name)) {
            try {
                // init
                $query = DB::table($table_name)->where('status', ((!empty($including_deleted))?'>=':'>'), 0);
                
                // set where
                $query = $this->queryBuildWhere($query);

                // fetch
                return $query->count();
                
            } catch (Exception $e) {
                throw $e;
            } finally {
                $this->_where = [];
                $this->_order = [];
                $this->_select_cols = [];
                $this->_limit_size = 0;
                $this->_page_number = 0;
                $this->_page_size = 20;
            }
        }

        return 0;
    }
    
    protected function queryListData($table_name, $pagination = true, $including_deleted = false) {
        $list_data = [];

        if(!empty($table_name)) {
            try {
                // init
                $query = DB::table($table_name)->where('status', ((!empty($including_deleted))?'>=':'>'), 0);

                // set where
                $query = $this->queryBuildWhere($query);

                // set order
                $query = $this->queryBuildOrder($query);
                
                // cols
                if(!empty($this->_select_cols)) {
                    $query->select($this->_select_cols);
                }
                
                // fetch
                if(!empty($pagination)) {
                    Paginator::currentPageResolver(fn() => (int)max(1, ((!empty($this->_page_number))?$this->_page_number:$this->getParamValue('page', 1))));
                    $result = $query->paginate($this->_page_size);
                    $this->setPagination($result->total());
  
                    // if the total number of pages is exceeded, load the last page of data
                    if((int)$this->_pagination['total_page'] > 0 && ((int)max(1, $this->getParamValue('page', 1)) > (int)$this->_pagination['total_page'])) {
                        // re-execute
                        $re_query = DB::table($table_name)->where('status', ((!empty($including_deleted))?'>=':'>'), 0);

                        // set where
                        $re_query = $this->queryBuildWhere($re_query);

                        // set order
                        $re_query = $this->queryBuildOrder($re_query);

                        // cols
                        if(!empty($this->_select_cols)) {
                            $re_query->select($this->_select_cols);
                        }
                        
                        Paginator::currentPageResolver(fn() => (int)$this->_pagination['total_page']);
                        $re_result = $re_query->paginate($this->_page_size);
                        $this->setPagination($re_result->total());
                        
                        $list_data = [
                            'data' => $this->revisedData($re_result->getCollection()->map(function($items) {
                                    $data = [];
                                    foreach ($items as $item_key => $item_value) {
                                        $data[$item_key] = $item_value;
                                    }
                                    return $data;
                                })->toArray(), true),
                            'pagination' => $this->_pagination
                        ];
                    }
                    else {
                        $list_data = [
                            'data' => $this->revisedData($result->getCollection()->map(function($items) {
                                    $data = [];
                                    foreach ($items as $item_key => $item_value) {
                                        $data[$item_key] = $item_value;
                                    }
                                    return $data;
                                })->toArray(), true),
                            'pagination' => $this->_pagination
                        ];
                    }
                }
                else {
                    if(!empty($this->_limit_size)) {
                        $query->limit(max(0, $this->_limit_size));
                    }
                    $list_data = $this->revisedData($query->get()->map(function($items) {
                        $data = [];
                        foreach ($items as $item_key => $item_value) {
                            $data[$item_key] = $item_value;
                        }
                        return $data;
                    })->toArray(), true);
                }
            } catch (Exception $e) {
                throw $e;
            } finally {
                $this->_where = [];
                $this->_order = [];
                $this->_select_cols = [];
                $this->_limit_size = 0;
                $this->_page_number = 0;
                $this->_page_size = 20;
            }
        }
        
        return $list_data;
    }
    
    protected function queryOneData($table_name, $including_deleted = false) {
        if(!empty($table_name)) {
            try {
                // init
                $query = DB::table($table_name)->where('status', ((!empty($including_deleted))?'>=':'>'), 0);

                // set where
                $query = $this->queryBuildWhere($query);

                // set order
                $query = $this->queryBuildOrder($query);
                
                // cols
                if(!empty($this->_select_cols)) {
                    $query->select($this->_select_cols);
                }

                // fetch
                $data = $this->revisedData($query->get()->map(function($items) {
                    $data = [];
                    foreach ($items as $item_key => $item_value) {
                        $data[$item_key] = $item_value;
                    }
                    return $data;
                })->toArray(), true);
                
                return reset($data);
            } catch (Exception $e) {
                throw $e;
            } finally {
                $this->_where = [];
                $this->_order = [];
                $this->_select_cols = [];
                $this->_limit_size = 0;
                $this->_page_number = 0;
                $this->_page_size = 20;
            }
        }
        
        return false;
    }

    protected function queryInsertData($table_name, $data = [], $including_deleted = false) {
        $new_insert_id = 0;
        
        if(!empty($table_name) && !empty($data)) {
            try {
                // optimize input value
                $data = $this->queryBuildData($table_name, $data);

                // check if exists
                $existing_data = null;
                if(!empty($this->_where)) {
                    $existing_data = $this->setWhere($this->_where)->queryOneData($table_name, $including_deleted);
                }

                if(!empty($existing_data)) {
                    // do update
                    if($this->setWhere([
                        'name'      =>  'id',
                        'operate'   =>  '=', 
                        'value'     =>  (int)$existing_data['id']
                    ])->queryUpdateData($table_name, $data, $including_deleted)) {
                        $new_insert_id = $existing_data['id'];
                        $this->setResultMessage($this->pLang('query_sucess'), 200);
                    }
                    else {
                        $this->setResultMessage($this->pLang('query_error'), 500);
                    }
                }
                else {
                    // do insert
                    if($new_insert_id = DB::table($table_name)->insertGetId(array_merge([
                        'status'        =>  1,
                        'created_by'    =>  ((!empty($this->_current_user))?$this->_current_user['id']:0),
                        'created_at'    =>  $this->_today_datetime,
                        'updated_by'    =>  ((!empty($this->_current_user))?$this->_current_user['id']:0),
                        'updated_at'    =>  $this->_today_datetime
                    ], $this->revisedData($data)))) {
                        $this->setResultMessage($this->pLang('query_sucess'), 200);
                    }
                    else {
                        $this->setResultMessage($this->pLang('query_error'), 500);
                    }
                }
            }
            catch (Exception $e) {
                $this->setResultMessage($this->pLang('query_error'), 500);
                throw $e;
            }
            finally {
                $this->_where = [];
                $this->_order = [];
                $this->_limit_size = 0;
                $this->_page_size = 20;
            }
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return $new_insert_id;
    }
    
    protected function queryUpdateData($table_name, $data = [], $including_deleted = false) {
        $result = false;

        if(!empty($table_name) && !empty($data)) {
            try {
                // optimize input value
                $data = $this->queryBuildData($table_name, $data);

                // init
                $query = DB::table($table_name)->where('status', ((!empty($including_deleted))?'>=':'>'), 0);

                // set where
                $query = $this->queryBuildWhere($query);
                
                // do update
                if($result = $query->update(array_merge([
                    'updated_by'    =>  ((!empty($this->_current_user))?$this->_current_user['id']:0),
                    'updated_at'    =>  $this->_today_datetime
                ], $this->revisedData($data)))) {
                    $this->setResultMessage($this->pLang('query_sucess'), 200);
                }
                else {
                    $this->setResultMessage($this->pLang('query_error'), 500);
                }
            } 
            catch (Exception $e) {
                $this->setResultMessage($this->pLang('query_error'), 500);
                throw $e;
            } finally {
                $this->_where = [];
                $this->_order = [];
                $this->_select_cols = [];
                $this->_limit_size = 0;
                $this->_page_number = 0;
                $this->_page_size = 20;
            }
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return $result;
    }
    
    protected function queryDeleteData($table_name, $including_deleted = false) {
        $result = false;

        if(!empty($table_name)) {
            try {
                // init
                $query = DB::table($table_name)->where('status', ((!empty($including_deleted))?'>=':'>'), 0);

                // set where
                $query = $this->queryBuildWhere($query);
                
                // do update
                if($result = $query->update([
                    'status'        =>  0,
                    'deleted_by'    =>  ((!empty($this->_current_user))?$this->_current_user['id']:0),
                    'deleted_at'    =>  $this->_today_datetime
                ])){
                    $this->setResultMessage($this->pLang('delete_sucess'), 200);
                }
                else {
                    $this->setResultMessage($this->pLang('query_error'), 500);
                }
            } 
            catch (Exception $e) {
                $this->setResultMessage($this->pLang('query_error'), 500);
                throw $e;
            } finally {
                $this->_where = [];
                $this->_order = [];
                $this->_select_cols = [];
                $this->_limit_size = 0;
                $this->_page_number = 0;
                $this->_page_size = 20;
            }
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return $result;
    }
    
    protected function queryTransaction(callable $callback, ...$args) {
        DB::beginTransaction();
        try {
            $result = $callback(...$args);
            DB::commit();
            $this->setResultMessage($this->pLang('query_sucess'), 200);
            return $result;
        } catch (Exception $ex) {
            DB::rollBack();
            $this->setResultMessage($this->pLang('query_error'), 500);
            return false;
        }
    }
    
    protected function queryRowSQL($sql) {
        return $this->revisedData(array_map(function ($value) {
            return (array)$value;
        }, DB::select(DB::raw(trim($sql)))), true);
    }

    protected function recursiveParents($table_name, $find_id = 0, $parent_index = 'parent_id') {
        $clone_where = $this->_where;
        $clone_order = $this->_order;
        $clone_select_cols = $this->_select_cols;
        $parent = [];
        
        $find_data = $this->setWhere(array_merge($clone_where, [[
            'name'      =>  'id',
            'operate'   =>  '=', 
            'value'     =>  (int)$find_id
        ]]))->queryOneData($table_name);
        
        if(!empty($find_data)) {
            if(!empty($find_data[$parent_index])) {
                $parent = $this->setWhere($clone_where)->setOrder($clone_order)->setSelectCols($clone_select_cols)->recursiveParents($table_name, $find_data[$parent_index], $parent_index);
            }
            $parent[] = $find_data;
        }
        
        return $parent;
    }
    
    protected function recursiveChilds($table_name, $find_parent_id = 0, $parent_index = 'parent_id') {
        $clone_where = $this->_where;
        $clone_order = $this->_order;
        $clone_select_cols = $this->_select_cols;
        $childs = [];
        
        $level = $this->setWhere(array_merge($clone_where, [[
            'name'      =>  $parent_index,
            'operate'   =>  '=', 
            'value'     =>  (int)$find_parent_id
        ]]))->setOrder($clone_order)->setSelectCols($clone_select_cols)->queryListData($table_name, false);

        if(!empty($level)) {
            foreach ($level as $level_data) {
                $level_data['child'] = $this->setWhere($clone_where)->setOrder($clone_order)->setSelectCols($clone_select_cols)->recursiveChilds($table_name, $level_data['id'], $parent_index);
                $childs[] = $level_data;
            }
        }
        
        return $childs;
    }
    
    protected function queryBuildData($table_name, $data) {
        if(!empty($table_name) && !empty($data)) {
            // table structure
            $table_structure = DB::select('describe '.$this->_db_prefix.$table_name);
            $table_mapping = [];
            if(!empty($table_structure)) {
                foreach ($table_structure as $structure) {
                    $structure = (array)$structure;
                    $table_mapping[$structure['Field']] = strtolower(preg_replace('/(.*)((\()(\d+)(\)))/', '$1', $structure['Type']));
                }
            }

            // remove unnecessary fields
            $revised_data = [];
            foreach ($data as $key => $value) {
                if(!empty($table_mapping[$key])) {
                    $value = (!is_array($value))?trim($value):$value;
                    if(in_array($table_mapping[$key], ['int', 'tinyint'])) {
                        $revised_data[$key] = (int)$value;
                    }
                    else if(in_array($table_mapping[$key], ['float', 'double'])) {
                        $revised_data[$key] = round((double)$value, 2);
                    }
                    else if(in_array($table_mapping[$key], ['date'])) {
                        preg_match('/^(\d{1,2})(\/)(\d{1,2})(\/)(\d{4})$/', $value, $date_match);
                        if($date_match) {
                            $value = (int)$date_match[5].'-'.(str_pad((int)$date_match[3], 2, '0', STR_PAD_LEFT)).'-'.(str_pad((int)$date_match[1], 2, '0', STR_PAD_LEFT));
                        }
                        $revised_data[$key] = date('Y-m-d', strtotime($value));
                    }
                    else if(in_array($table_mapping[$key], ['datetime'])) {
                        preg_match('/^(\d{1,2})(\/)(\d{1,2})(\/)(\d{4})(\s)(\d{1,2})(:)(\d{1,2})((:)(\d{1,2}))?$/', $value, $datetime_match);
                        if($datetime_match) {
                            $value = (int)$datetime_match[5].'-'.(str_pad((int)$datetime_match[3], 2, '0', STR_PAD_LEFT)).'-'.(str_pad((int)$datetime_match[1], 2, '0', STR_PAD_LEFT));
                            $value.= str_pad((int)$datetime_match[7], 2, '0', STR_PAD_LEFT).':'.str_pad((int)$datetime_match[9], 2, '0', STR_PAD_LEFT);
                            if(!empty($datetime_match[12])) {
                                $value.= ':'.str_pad((int)$datetime_match[12], 2, '0', STR_PAD_LEFT);
                            }
                            else {
                                $value.= ':00';
                            }
                        }
                        $revised_data[$key] = date('Y-m-d H:i:s', strtotime($value));
                    }
                    else {
                        $revised_data[$key] = $value;
                    }
                }
            }
            return $revised_data;
        }
        
        return [];
    }

    protected function queryBuildWhere($query = null, $new_where = []) {
        if(!empty($new_where)) {
            $this->_where = $new_where;
        }
        if(!empty($query) && !empty($this->_where)) {
            foreach ($this->_where as $where_key => $where) {
                foreach ($where as $where_sub_key => $where_sub) {
                    if(!is_array($where_sub)) {
                        $filter = $where;
                        switch (strtolower($filter['operate'])) {
                            case 'in':
                                $query->whereIn($filter['name'], (array)$filter['value']);
                                break;
                            case 'like':
                                $query->where($filter['name'], strtoupper($filter['operate']), '%'.$this->specialChars($filter['value']).'%');
                                break;
                            default:
                                $query->where($filter['name'], strtoupper($filter['operate']), $this->specialChars($filter['value']));
                        }
                    }
                    else {
                        $query->where(function($query) use ($where) {
                            foreach ($where as $filter_key => $filter) {
                                if($filter_key > 0) {
                                    switch (strtolower($filter['operate'])) {
                                        case 'in':
                                            $query->orWhereIn($filter['name'], (array)$filter['value']);
                                            break;
                                        case 'like':
                                            $query->orWhere($filter['name'], strtoupper($filter['operate']), '%'.$this->specialChars($filter['value']).'%');
                                            break;
                                        default:
                                            $query->orWhere($filter['name'], strtoupper($filter['operate']), $this->specialChars($filter['value']));
                                    }
                                }
                                else {
                                    switch (strtolower($filter['operate'])) {
                                        case 'in':
                                            $query->whereIn($filter['name'], (array)$filter['value']);
                                            break;
                                        case 'like':
                                            $query->where($filter['name'], strtoupper($filter['operate']), '%'.$this->specialChars($filter['value']).'%');
                                            break;
                                        default:
                                            $query->where($filter['name'], strtoupper($filter['operate']), $this->specialChars($filter['value']));
                                    }
                                }
                            }
                        });
                    }
                    break;
                }
            }
        }
        
        return $query;
    }
    
    protected function queryBuildOrder($query = null, $new_order = []) {
        if(!empty($new_order)) {
            $this->_order = $new_order;
        }
        if(!empty($query) && !empty($this->_order)) {
            foreach ($this->_order as $order_key => $order) {
                if(is_array($order)) {
                    foreach ($order as $order_sub_key => $sub_order) {
                        preg_match('/(.*)(_)(asc|desc)$/i', $sub_order, $order_match);
                        if(!empty($order_match[1]) && !empty($order_match[3])) {
                            $query->orderBy($order_match[1], strtoupper($order_match[3]));
                        }
                        else if(strtolower($sub_order) == 'rand()') {
                            $query->orderBy(DB::raw('RAND()'));
                        }
                    }
                }
                else {
                    preg_match('/(.*)(_)(asc|desc)$/i', $order, $order_match);
                    if(!empty($order_match[1]) && !empty($order_match[3])) {
                        $query->orderBy($order_match[1], strtoupper($order_match[3]));
                    }
                    else if(strtolower($order) == 'rand()') {
                        $query->orderBy(DB::raw('RAND()'));
                    }
                }
            }
        }
        return $query;
    }
    
    protected function showSQL($query = null) {
        if(!empty($query)) {
            dump(vsprintf(str_replace('?', '%s', $query->toSql()), collect($query->getBindings())->map(function ($binding) {
                $binding = addslashes($binding);
                return is_numeric($binding) ? $binding : "'{$binding}'";
            })->toArray()));
        }
    }
    
    protected function revisedData($data = [], $decode = false) {
        if(!empty($data)) {
            if($decode) {
                foreach ($data as $key => $value) {
                    if(!empty($value) && is_array($value)) {
                        foreach ($value as $sub_key => $sub_value) {
                            if(is_array(json_decode($sub_value, true))) {
                                $data[$key][$sub_key] = json_decode($sub_value, true);
                            }
                        }
                    }
                }
                $data = $this->specialChars($data, false);
            }
            else {
                $data = $this->specialChars($data);
                foreach ($data as $key => $value) {
                    // array to json format
                    if(!empty($value) && is_array($value)) {
                        $data[$key] = json_encode($value);
                    }
                }
            }
        }
        
        return $data;
    }

    protected function specialChars($value = [], $encode = true) {
        if(!empty($value)) {
            if(is_array($value)) {
                foreach ($value as $sub_key => $sub_value) {
                    if(is_array($sub_value)) {
                        $value[trim($sub_key)] = $this->specialChars($sub_value, $encode);
                    }
                    else if(!is_numeric($sub_value)) {
                        $value[trim($sub_key)] = ($encode)?htmlspecialchars(trim($sub_value), ENT_QUOTES):htmlspecialchars_decode(trim($sub_value), ENT_QUOTES);
                    }
                }
            }
            else if(!is_numeric($value)) {
                $value = ($encode)?htmlspecialchars(trim($value), ENT_QUOTES):htmlspecialchars_decode(trim($value), ENT_QUOTES);
            }
        }
        
        return $value;
    }
    
    protected function toPlainText($value = '', $no_space = false) {
        // First remove the leading/trialing whitespace
        $value = strip_tags(trim(str_replace('&nbsp;', ' ', $value)));
        // Now remove any doubled-up whitespace
        $value = preg_replace('/\s(?=\s)/', '', $value);
        // Finally, replace any non-space whitespace, with a space
        $value = preg_replace('/[\n\r\t]/', ' ', $value);
        // Echo out: 'This line contains liberal use of whitespace.'
        if($no_space) {
            $value = preg_replace('/\s+/', '', $value);
        }
        
        return trim($value);
    }
    
    protected function getParamValue($name = '' , $default_value = '') {
        if(!empty($name)) {
            $value = Request::input($name);
            return (!empty($value))?$value:$default_value;
        }
        else {
            return Request::input();
        }
    }
   
    protected function pLang($name = '') {
        $lang_file = trans('_database');
        return (!empty($name) && !empty($lang_file[$name]))?$lang_file[$name]:'';
    }
    
    protected function setResultMessage($message, $code) {
        $this->_result_message = $message;
        $this->_result_code = $code;
    }
    
    protected function setPagination($total_row = 0) {
        $total_page = (int)ceil($total_row/$this->_page_size);
        $current_page = max(1, min($this->getParamValue('page', 1), $total_page));
        $this->_pagination = 
        [
            'page_size'     =>  $this->_page_size,
            'total'         =>  $total_row,
            'current_page'  =>  $current_page,
            'total_page'    =>  $total_page
        ];
        
        return $this->_pagination;
    }

    public function getResultMessage() {
        return $this->_result_message;
    }
    
    public function getResultCode() {
        return $this->_result_code;
    }

    public function getPagination() {
        return $this->_pagination;
    }
    
    protected function calcAge($birth_date = '') {
        if(!empty($birth_date)) {
            preg_match('/^(\d{2})(\/)(\d{2})(\/)(\d{4})$/', $birth_date, $date_match);
            if($date_match) {
                $birth_date = $date_match[5].'-'.$date_match[3].'-'.$date_match[1];
            }

            preg_match('/^(\d{4})(-)(\d{2})(-)(\d{2})$/', $birth_date, $birth_date_match);
            if(!empty($birth_date_match)) {
                $value = ['year' => 0, 'month' => 0, 'day' => 0];
                $time = strtotime($this->_today_datetime) - strtotime($birth_date);
                if($time > 31556926) {
                    $value['year'] = floor($time/31556926);
                    $time = ($time%31556926);
                }

                if($time > 2626560) {
                    $value['month'] = floor($time/2626560);
                    $time = ($time%2626560);
                }

                if($time > 86400) {
                    $value['day'] = floor($time/86400);
                    $time = ($time%86400);
                    if($time >= 0) {
                        $value['day']++;
                    }
                }

                return $value;
            }
        }
        return false;
    }
    
    protected function randomString($length = 8) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}