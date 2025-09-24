<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Posts extends BaseModel {
    protected $_member_table = 'member';
    protected $_posts_table = 'member_posts';

    public function __construct($data) {
        parent::__construct($data);
    }

    public function getAll($options = []) {
        $subquery_like = DB::table($this->_posts_table.'_like')->where([
            ['status', '>', 0],
        ])->groupBy('posts_id')->select([
            'posts_id AS like_posts_id',
            DB::raw('COUNT(*) AS `total_like`')
        ]);
        
        $subquery_content = DB::table($this->_posts_table.'_comment')->where([
            ['status', '>', 0],
        ])->groupBy('posts_id')->select([
            'posts_id AS comment_posts_id',
            DB::raw('COUNT(*) AS `total_comment`')
        ]);
        
        $query = DB::table($this->_posts_table)->where($this->_posts_table.'.status', '>', 0);
        $query->leftJoin($this->_member_table, $this->_posts_table.'.member_id', '=', $this->_member_table.'.id');
        $query->leftJoinSub($subquery_like, 'like', function ($join) {
            $join->on($this->_posts_table.'.id', '=', 'like.like_posts_id');
        });
        $query->leftJoinSub($subquery_content, 'comment', function ($join) {
            $join->on($this->_posts_table.'.id', '=', 'comment.comment_posts_id');
        });
        
        $query->where($this->_member_table.'.status', '>', 0);
        $member_id = (!empty($options) && !empty($options['member_id']))?$options['member_id']:0;
        if(!empty($member_id)) {
            $query->where($this->_posts_table.'.member_id', '=', (int)$member_id);
        }
        
        $keywords = (!empty($options) && !empty($options['keywords']))?$options['keywords']:'';
        if(!empty($keywords)) {
            $query->where($this->_posts_table.'.content', 'LIKE', '%'.$this->specialChars($keywords).'%');
        }
        
        if(!empty($options['show_type'])) {
            $query->where($this->_posts_table.'.category_type', '=', (int)$options['show_type']);
        }
        
        if(!empty($options['show_lang'])) {
            $show_lang = $options['show_lang'];
            $query->where(function($query) use ($show_lang) {
                $query->where($this->_posts_table.'.category_lang', '=', (int)$show_lang);
                $query->orWhere($this->_posts_table.'.category_lang', '=', 0);
            });
        }
        
        if(!empty($options['show_country'])) {
            $query->whereIn($this->_posts_table.'.category_country', [0, (int)$options['show_country']]);
        }
        
        if(!empty($options['show_page_size'])) {
            $this->setPageSize((int)$options['show_page_size']);
        }
        
        $query->select([
            $this->_posts_table.'.id',
            $this->_posts_table.'.category_type',
            $this->_posts_table.'.category_lang',
            $this->_posts_table.'.category_country',
            $this->_posts_table.'.title',
            $this->_posts_table.'.content',
            $this->_posts_table.'.photo',
            $this->_posts_table.'.youtube_url',
            $this->_posts_table.'.highlight',
            $this->_posts_table.'.created_at',
            $this->_posts_table.'.member_id',
            $this->_member_table.'.avatar',
            $this->_member_table.'.alias_name',
            'like.total_like',
            'comment.total_comment',
        ]);
        
        if(!empty($options['show_highlight'])) {
            $query->orderBy($this->_posts_table.'.highlight', 'DESC');
        }
        
        $query->orderBy($this->_posts_table.'.created_at', 'DESC');

        $result = $query->paginate($this->_page_size);
        $this->setPagination($result->total());
        return 
        [
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
    
    public function getByID($posts_id = 0) {
        $subquery_like = DB::table($this->_posts_table.'_like')->where([
            ['status', '>', 0],
        ])->groupBy('posts_id')->select([
            'posts_id AS like_posts_id',
            DB::raw('COUNT(*) AS `total_like`')
        ]);
        
        $subquery_content = DB::table($this->_posts_table.'_comment')->where([
            ['status', '>', 0],
        ])->groupBy('posts_id')->select([
            'posts_id AS comment_posts_id',
            DB::raw('COUNT(*) AS `total_comment`')
        ]);
        
        $query = DB::table($this->_posts_table)->where($this->_posts_table.'.status', '>', 0);
        $query->leftJoin($this->_member_table, $this->_posts_table.'.member_id', '=', $this->_member_table.'.id');
        $query->leftJoinSub($subquery_like, 'like', function ($join) {
            $join->on($this->_posts_table.'.id', '=', 'like.like_posts_id');
        });
        $query->leftJoinSub($subquery_content, 'comment', function ($join) {
            $join->on($this->_posts_table.'.id', '=', 'comment.comment_posts_id');
        });
        $query->where($this->_member_table.'.status', '>', 0);
        $query->where($this->_posts_table.'.id', '=', (int)$posts_id);
        
        $query->select([
            $this->_posts_table.'.id',
            $this->_posts_table.'.category_type',
            $this->_posts_table.'.category_lang',
            $this->_posts_table.'.category_country',
            $this->_posts_table.'.title',
            $this->_posts_table.'.content',
            $this->_posts_table.'.photo',
            $this->_posts_table.'.youtube_url',
            $this->_posts_table.'.highlight',
            $this->_posts_table.'.created_at',
            $this->_posts_table.'.member_id',
            $this->_member_table.'.avatar',
            $this->_member_table.'.alias_name',
            'like.total_like',
            'comment.total_comment',
        ]);
        
        $query->orderBy($this->_posts_table.'.id', 'DESC');

        $result = $this->revisedData($query->get()->map(function($items) {
            $data = [];
            foreach ($items as $item_key => $item_value) {
                $data[$item_key] = $item_value;
            }
            return $data;
        })->toArray(), true);
        
        return reset($result);
    }
    
    public function doSave($data = [], $posts_id = 0) {
        // tyr to upload logo
        if(!empty($file = \Illuminate\Support\Facades\Request::file('mypostsphoto'))) {
            // get file info
            $file_ori_name = $file->getClientOriginalName();
            $file_extension = $file->getClientOriginalExtension();
            $file_size = $file->getSize();
            $file_name = md5(uniqid(rand())).'.'. strtolower($file_extension);

            // upload folder
            $location = ('upload/member_posts');
            if(!file_exists(public_path($location))){
                @mkdir(public_path($location), 0755, true);
            }

            // move & resize
            if($file->move(public_path($location), $file_name)) {
                if(file_exists(public_path($location.'/'.$file_name))) {
                    \Intervention\Image\Facades\Image::make(public_path($location.'/'.$file_name))->resize(1200, 1200, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })->save(public_path($location.'/'.$file_name));
                }
                $data['photo'] = $file_name;
            }
        }
        unset($data['posts_id']);

        return (((int)$data['member_id'] > 0)?$this->setWhere(
        [
            ['id', '=', (int)$posts_id],
            ['member_id', '=', (int)$data['member_id']]
        ])->queryInsertData($this->_posts_table, $data):false);
    }
    
    
    public function doDelete($posts_id = 0) {
        if(!empty($posts_id)) {
            if(is_numeric($posts_id)) {
                $posts_id = [$posts_id];
            }
            else if(is_string($posts_id)){
                $posts_id = explode(',', $posts_id);
            }
            else {
                $posts_id = (array)$posts_id;
            }
        }
        
        return $this->queryTransaction(function($posts_id) {
            $this->setWhere(
            [
                'name'      =>  'id', 
                'operate'   =>  'in', 
                'value'     =>  $posts_id
            ])->queryDeleteData($this->_posts_table);
            
            $this->setWhere(
            [
                'name'      =>  'posts_id', 
                'operate'   =>  'in', 
                'value'     =>  $posts_id
            ])->queryDeleteData($this->_posts_table.'_comment');
            
            return true;
        }, $posts_id);
    }
    
    public function deleteSelfPost($posts_id = 0, $member_id = 0) {
        return $this->setWhere(
        [
            [
                'name'      =>  'id', 
                'operate'   =>  'in', 
                'value'     =>  $posts_id
            ],
            [
                'name'      =>  'member_id', 
                'operate'   =>  '=', 
                'value'     =>  $member_id
            ]
        ])->queryDeleteData($this->_posts_table);
    }
    
    public function doHighlight($posts_id = 0) {
        $target_post = $this->getByID($posts_id);
        $new_highlight = 0;
        if(empty($target_post['highlight'])) {
            $new_highlight = 1;
        }
        
        return $this->setWhere(
        [
            'name'      =>  'id', 
            'operate'   =>  '=', 
            'value'     =>  $posts_id
        ])->queryUpdateData($this->_posts_table, ['highlight' => $new_highlight]);
    }

    public function changeLike($posts_id = 0, $member_id = 0) {
        DB::beginTransaction();
        try {
            $temp_total = $this->setWhere(
            [
                [
                    'name'      =>  'posts_id', 
                    'operate'   =>  '=', 
                    'value'     =>  (int)$posts_id
                ],
                [
                    'name'      =>  'member_id', 
                    'operate'   =>  '=', 
                    'value'     =>  (int)$member_id
                ]
            ])->queryListTotal($this->_posts_table.'_like');
            
            // disable all first
            $this->setWhere(
            [
                [
                    'name'      =>  'posts_id', 
                    'operate'   =>  '=', 
                    'value'     =>  (int)$posts_id
                ],
                [
                    'name'      =>  'member_id', 
                    'operate'   =>  '=', 
                    'value'     =>  (int)$member_id
                ]
            ])->queryDeleteData($this->_posts_table.'_like');
            
            if(empty($temp_total)) {
                $this->setWhere(
                [
                    [
                        'name'      =>  'posts_id', 
                        'operate'   =>  '=', 
                        'value'     =>  (int)$posts_id
                    ],
                    [
                        'name'      =>  'member_id', 
                        'operate'   =>  '=', 
                        'value'     =>  (int)$member_id
                    ]
                ])->queryInsertData($this->_posts_table.'_like', 
                [
                    'posts_id'  =>  (int)$posts_id,
                    'member_id' =>  (int)$member_id,
                    'status'    =>  1
                ], true);
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
    
    public function getTotalLike($posts_id = 0) {
        return $this->setWhere(
        [
            [
                'name'      =>  'posts_id', 
                'operate'   =>  '=', 
                'value'     =>  (int)$posts_id
            ]
        ])->queryListTotal($this->_posts_table.'_like');
    }
    
    public function getAllComment($posts_id = 0) {
        $query = DB::table($this->_posts_table)->where($this->_posts_table.'.status', '>', 0);
        $query->Join($this->_posts_table.'_comment', $this->_posts_table.'.id', '=', $this->_posts_table.'_comment.posts_id');
        $query->Join($this->_member_table, $this->_posts_table.'_comment.member_id', '=', $this->_member_table.'.id');
        
        $query->where($this->_posts_table.'.id', '=', (int)$posts_id);
        $query->where($this->_posts_table.'_comment.status', '>', 0);
        $query->where($this->_member_table.'.status', '>', 0);
        
        $query->select([
            $this->_posts_table.'_comment.id',
            $this->_member_table.'.id AS member_id',
            $this->_member_table.'.avatar',
            $this->_member_table.'.alias_name',
            $this->_posts_table.'_comment.content AS comment_content',
            $this->_posts_table.'_comment.created_at'
        ]);
        
        $query->orderBy($this->_posts_table.'_comment.id', 'DESC');
        
        return $this->revisedData($query->get()->map(function($items) {
            $data = [];
            foreach ($items as $item_key => $item_value) {
                $data[$item_key] = $item_value;
            }
            return $data;
        })->toArray(), true);
    }
    
    public function saveComment($data = []) {
        return $this->queryInsertData($this->_posts_table.'_comment', $data);
    }
    
    public function doDeleteSub($posts_comment_id = 0) {
        if(!empty($posts_comment_id)) {
            if(is_numeric($posts_comment_id)) {
                $posts_comment_id = [$posts_comment_id];
            }
            else if(is_string($posts_comment_id)){
                $posts_comment_id = explode(',', $posts_comment_id);
            }
            else {
                $posts_comment_id = (array)$posts_comment_id;
            }
        }
        
        return $this->setWhere(
        [
            'name'      =>  'id', 
            'operate'   =>  'in', 
            'value'     =>  $posts_comment_id
        ])->queryDeleteData($this->_posts_table.'_comment');
    }
    
    public function getTotalComment($posts_id = 0) {
        return $this->setWhere(
        [
            [
                'name'      =>  'posts_id', 
                'operate'   =>  '=', 
                'value'     =>  (int)$posts_id
            ]
        ])->queryListTotal($this->_posts_table.'_comment');
    }
}