<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Forum extends BaseModel {
    protected $_member_table = 'member';
    protected $_forum_table = 'forum';

    public function __construct($data) {
        parent::__construct($data);
    }
    
    public function getAll($options = []) {
        $subquery = DB::table($this->_forum_table.'_comment');
        $subquery->where($this->_forum_table.'_comment.status', '>', 0);
        $subquery->groupBy($this->_forum_table.'_comment.forum_id');
        $subquery->select([
            $this->_forum_table.'_comment.forum_id',
            DB::raw('COUNT(*) AS `total_comment`')
        ]);
        
        $query = DB::table($this->_forum_table);
        
        $query->leftJoin($this->_member_table.' AS author', function ($join) {
            $join->on($this->_forum_table.'.member_id', '=', 'author.id');
        });
        
        $query->leftJoin($this->_member_table.' AS publisher', function ($join) {
            $join->on($this->_forum_table.'.last_comment_by', '=', 'publisher.id');
        });
        
        $query->leftJoinSub($subquery, 'sub', function ($sub_join) {
            $sub_join->on($this->_forum_table.'.id', '=', 'sub.forum_id');
        });
        
        $query->select(
        [
            $this->_forum_table.'.id', 
            $this->_forum_table.'.forum_country', 
            $this->_forum_table.'.forum_topic', 
            $this->_forum_table.'.total_view', 
            $this->_forum_table.'.last_comment_at', 
            'author.alias_name AS author_name',
            'publisher.alias_name AS publisher_name',
            DB::raw('IFNULL('.$this->_db_prefix.'sub.total_comment, 0) AS total_comment')
        ]);
        
        $query->where($this->_forum_table.'.status', '>', 0);
        
        $country = (!empty($options) && !empty($options['country']))?$options['country']:0;
        if(!empty($country)) {
            $query->where($this->_forum_table.'.forum_country', '=', (int)$country);
        }
        
        $keywords = (!empty($options) && !empty($options['keywords']))?$options['keywords']:'';
        if(!empty($keywords)) {
            $query->where(function($query) use ($keywords) {
                $query->where($this->_forum_table.'.forum_topic', 'LIKE', '%'.$this->specialChars($keywords).'%');
                $query->orWhere($this->_forum_table.'.forum_content', 'LIKE', '%'.$this->specialChars($keywords).'%');
            });
        }
        
        $query->orderBy($this->_forum_table.'.id', 'desc');
        $result = $query->paginate($this->_page_size);
        $this->setPagination($result->total());
        
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
                
        return $list_data;
    }
    
    public function getByID($forum_id = 0) {
        $subquery = DB::table($this->_forum_table.'_comment');
        $subquery->where($this->_forum_table.'_comment.status', '>', 0);
        $subquery->groupBy($this->_forum_table.'_comment.forum_id');
        $subquery->select([
            $this->_forum_table.'_comment.forum_id',
            DB::raw('COUNT(*) AS `total_comment`')
        ]);
        
        $query = DB::table($this->_forum_table);
        
        $query->leftJoin($this->_member_table.' AS author', function ($join) {
            $join->on($this->_forum_table.'.member_id', '=', 'author.id');
        });
        
        $query->leftJoin($this->_member_table.' AS publisher', function ($join) {
            $join->on($this->_forum_table.'.last_comment_by', '=', 'publisher.id');
        });
        
        $query->leftJoinSub($subquery, 'sub', function ($sub_join) {
            $sub_join->on($this->_forum_table.'.id', '=', 'sub.forum_id');
        });
        
        $query->select(
        [
            $this->_forum_table.'.id', 
            $this->_forum_table.'.member_id',
            $this->_forum_table.'.forum_country', 
            $this->_forum_table.'.forum_topic', 
            $this->_forum_table.'.forum_content', 
            $this->_forum_table.'.total_view',
            $this->_forum_table.'.created_at As first_comment_at', 
            $this->_forum_table.'.last_comment_at', 
            'author.avatar AS author_avatar',
            'author.alias_name AS author_name',
            'publisher.alias_name AS publisher_name',
            DB::raw('IFNULL('.$this->_db_prefix.'sub.total_comment, 0) AS total_comment')
        ]);
        
        $query->where($this->_forum_table.'.id', '=', (int)$forum_id);
        $query->where($this->_forum_table.'.status', '>', 0);
        
        // fetch
        $data = $this->revisedData($query->get()->map(function($items) {
            $data = [];
            foreach ($items as $item_key => $item_value) {
                $data[$item_key] = $item_value;
            }
            return $data;
        })->toArray(), true);

        return reset($data);
    }

    public function doSave($data = []) {
        // do checking
        $validator = Validator::make($data, 
        [
            'member_id'         =>  'required',
            'forum_country'     =>  'required',
            'forum_topic'       =>  'required',
            'forum_content'     =>  'required',
            'last_comment_by'   =>  'required'
        ]);
        if(!$validator->fails()) {
            $data['last_comment_at'] = $this->_today_datetime;
            return $this->queryInsertData($this->_forum_table, $data);
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return false;
    }
    
    public function doDelete($forum_id = 0) {
        if(!empty($forum_id)) {
            if(is_numeric($forum_id)) {
                $forum_id = [$forum_id];
            }
            else if(is_string($forum_id)){
                $forum_id = explode(',', $forum_id);
            }
            else {
                $forum_id = (array)$forum_id;
            }
        }
        
        return $this->queryTransaction(function($forum_id) {
            $this->setWhere(
            [
                'name'      =>  'id', 
                'operate'   =>  'in', 
                'value'     =>  $forum_id
            ])->queryDeleteData($this->_forum_table);
            
            $this->setWhere(
            [
                'name'      =>  'forum_id', 
                'operate'   =>  'in', 
                'value'     =>  $forum_id
            ])->queryDeleteData($this->_forum_table.'_comment');
            
            return true;
        }, $forum_id);
    }
    
    public function addTotalView($forum_id = 0) {
        $target_forum = $this->getByID($forum_id);
        if(!empty($target_forum)) {
            $new_total_view = $target_forum['total_view'] + 1;
            return $this->setWhere(['id', '=', $forum_id])->queryUpdateData($this->_forum_table, ['total_view' => $new_total_view]);
        }
        return false;
    }
    
    public function doSaveComment($data = [], $forum_id = 0) {
        $forum_id = max(0, (int)$forum_id);
        // do checking
        $validator = Validator::make($data, 
        [
            'member_id'         =>  'required',
            'content'           =>  'required',
            'last_comment_by'   =>  'required'
        ]);
        if(!$validator->fails() && $forum_id > 0) {
            $data['forum_id'] = $forum_id;
            $data['last_comment_at'] = $this->_today_datetime;
            return $this->queryTransaction(function($data, $forum_id) {
                $new_forum_comment_id = $this->queryInsertData($this->_forum_table.'_comment', $data);
                
                $this->setWhere(['id', '=', $forum_id])->queryUpdateData($this->_forum_table, 
                [
                    'last_comment_by' => $data['last_comment_by'], 
                    'last_comment_at' => $data['last_comment_at']
                ]);
                
                return $new_forum_comment_id;
            }, $data, $forum_id);
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return false;
    }
    
    public function doDeleteSub($forum_comment_id = 0) {
        if(!empty($forum_comment_id)) {
            if(is_numeric($forum_comment_id)) {
                $forum_comment_id = [$forum_comment_id];
            }
            else if(is_string($forum_comment_id)){
                $forum_comment_id = explode(',', $forum_comment_id);
            }
            else {
                $forum_comment_id = (array)$forum_comment_id;
            }
        }
        
        return $this->setWhere(
        [
            'name'      =>  'id', 
            'operate'   =>  'in', 
            'value'     =>  $forum_comment_id
        ])->queryDeleteData($this->_forum_table.'_comment');
    }
    
    public function getAllComment($forum_id = 0) {
        $this->_page_size = 10;
        
        $query = DB::table($this->_forum_table.'_comment');
        
        $query->leftJoin($this->_member_table.' AS publisher', function ($join) {
            $join->on($this->_forum_table.'_comment.last_comment_by', '=', 'publisher.id');
        });
        
        $query->select(
        [
            $this->_forum_table.'_comment.id',
            $this->_forum_table.'_comment.member_id', 
            $this->_forum_table.'_comment.content AS forum_content', 
            $this->_forum_table.'_comment.last_comment_by',
            $this->_forum_table.'_comment.last_comment_at', 
            'publisher.avatar AS publisher_avatar',
            'publisher.alias_name AS publisher_name'
        ]);
        
        $query->where($this->_forum_table.'_comment.forum_id', '=', (int)$forum_id);
        $query->where($this->_forum_table.'_comment.status', '>', 0);
        $query->orderBy($this->_forum_table.'_comment.id', 'desc');
        $result = $query->paginate($this->_page_size);
        $this->setPagination($result->total());
        
        // if the total number of pages is exceeded, load the last page of data
        if((int)$this->_pagination['total_page'] > 0 && ((int)max(1, $this->getParamValue('page', 1)) > (int)$this->_pagination['total_page'])) {
            \Illuminate\Pagination\Paginator::currentPageResolver(fn() => (int)$this->_pagination['total_page']); 
            
            $query = DB::table($this->_forum_table.'_comment');
       
            $query->leftJoin($this->_member_table.' AS publisher', function ($join) {
                $join->on($this->_forum_table.'_comment.last_comment_by', '=', 'publisher.id');
            });

            $query->select(
            [
                $this->_forum_table.'_comment.id',
                $this->_forum_table.'_comment.member_id', 
                $this->_forum_table.'_comment.content AS forum_content', 
                $this->_forum_table.'_comment.last_comment_by',
                $this->_forum_table.'_comment.last_comment_at', 
                'publisher.avatar AS publisher_avatar',
                'publisher.alias_name AS publisher_name'
            ]);

            $query->where($this->_forum_table.'_comment.forum_id', '=', (int)$forum_id);
            $query->where($this->_forum_table.'_comment.status', '>', 0);
            $query->orderBy($this->_forum_table.'_comment.id', 'desc');
            $result = $query->paginate($this->_page_size);
            $this->setPagination($result->total());
        }
        
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
                
        return $list_data;
    }
}