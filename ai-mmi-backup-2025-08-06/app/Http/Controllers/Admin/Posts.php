<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Validator;

class Posts extends AdminController {
    
    public function __construct($data) {
        parent::__construct($data);
        
        $this->pageIndex('member_area');
        
        // load model
        $this->_posts_model = $this->loadModel('posts');
    }

    public function index($action = '', $id = 0) {
        // post
        $this->pageAction(function() {
            switch(strtolower($this->postParamValue('page_action'))) {
                case 'delete':
                    $result = $this->_posts_model->doDelete($this->postParamValue('id', 0));
                    $this->pageResult(
                    [
                        'status'    =>  $this->_posts_model->getResultCode(),
                        'message'   =>  $this->_posts_model->getResultMessage()
                    ], ((!empty($result))?true:false));
                    break;
                case 'delete_sub':
                    $result = $this->_posts_model->doDeleteSub($this->postParamValue('id', 0));
                    $this->pageResult(
                    [
                        'status'    =>  $this->_posts_model->getResultCode(),
                        'message'   =>  $this->_posts_model->getResultMessage()
                    ], ((!empty($result))?true:false));
                    break;
                case 'highlight':
                    $result = $this->_posts_model->doHighlight($this->postParamValue('id', 0));
                    $this->pageResult(
                    [
                        'status'    =>  $this->_posts_model->getResultCode(),
                        'message'   =>  $this->_posts_model->getResultMessage()
                    ], ((!empty($result))?true:false));
                    break;
                case 'feature':
                    $end_date = $this->postParamValue('end_date', null);
                    $result = $this->_posts_model->doFeature($this->postParamValue('id', 0), $end_date);
                    $this->pageResult(
                    [
                        'status'    =>  $this->_posts_model->getResultCode(),
                        'message'   =>  $this->_posts_model->getResultMessage()
                    ], ((!empty($result))?true:false));
                    break;
            }
        });

        // page data
        $action = strtolower($action);
        if(!empty($action)) {
            if(in_array($action, ['add', 'edit', 'details'])) {
                // check role if need 
                $target_posts = $this->_posts_model->getByID($id);
                if(($action == 'edit' || $action == 'details') && empty($target_posts)) {
                    $this->doRedirect($this->toURL('posts'));
                }
                $list_posts_comment = $this->_posts_model->getAllComment($id);

                // set nav
                $this->pageNavigator($this->_page_lang['member_area']);
                $this->pageNavigator($this->_page_lang['posts'], $this->toURL('posts'));
                
                if(!empty($action) && in_array($action, ['add', 'edit', 'details'])) {
                    $this->pageNavigator($this->_page_lang[strtolower($action)]);
                }
  
                // load view
                $country_options = [];
                $list_countries = $this->loadModel('pages', ['table' => 'country'])->getAll($this->_current_lang_index, null, false);
                if(!empty($list_countries)) {
                    foreach ($list_countries as $country) {
                        $country_options[$country['id']] = $country['title'];
                    }
                }
                $country_options[0] = $this->_page_lang['posts_others'];

                return $this->pageOptions(
                [
                    'country'               =>  $country_options
                ])->pageData(
                [
                    'target_posts'          =>  $target_posts,
                    'list_posts_comment'    =>  $list_posts_comment
                ])->pageView('member_area.posts_details');
            }
            else {
                $this->doRedirect($this->toURL('posts'));
            }
        }
        else {
            // set nav
            $this->pageNavigator($this->_page_lang['member_area']);
            $this->pageNavigator($this->_page_lang['posts'], $this->toURL('posts'));

            // load view
            $country_options = [];
            $list_countries = $this->loadModel('pages', ['table' => 'country'])->getAll($this->_current_lang_index, null, false);
            if(!empty($list_countries)) {
                foreach ($list_countries as $country) {
                    $country_options[$country['id']] = $country['title'];
                }
            }
            $country_options[0] = '-';
            
            $list_posts = $this->_posts_model->getAll(['keywords' => $this->getParamValue('keywords')]);
            if(!empty($list_posts['data'])) {
                foreach ($list_posts['data'] as $data_key => $data) {
                    $list_posts['data'][$data_key]['topic'] = mb_substr($this->toPlainText($data['content']), 0, 30);
                    if(md5($this->toPlainText(mb_substr($this->toPlainText($data['content']), 0, 30))) != md5($this->toPlainText(mb_substr($this->toPlainText($data['content']), 0, 31)))) {
                        $list_posts['data'][$data_key]['topic'].= '...';
                    }
                    
                    if(!empty($list_posts['data'][$data_key]['highlight'])) {
                        $list_posts['data'][$data_key]['highlight'] = '<a class="set-highlight" data-id="'.$data['id'].'"><i class="fa fa-check-circle" style="font-size:18px;color:mediumaquamarine"></i></i></a>';
                    }
                    else {
                        $list_posts['data'][$data_key]['highlight'] = '<a class="set-highlight" data-id="'.$data['id'].'"><i class="fa fa-ban" style="font-size:18px;color:lightpink"></i></a>';
                    }

                    // featured status
                    $fu = $data['featured_until'] ?? null;
                    $now = date('Y-m-d H:i:s');
                    if (!empty($fu) && $fu > $now) {
                        $list_posts['data'][$data_key]['featured_until'] =
                            '<a class="set-feature" data-id="'.$data['id'].'" title="Featured until '.$fu.' — click to unfeature">'
                            .'<i class="fa fa-star" style="font-size:18px;color:gold"></i>'
                            .'<br><small style="font-size:10px;color:#888;">until '.date('d/m/y', strtotime($fu)).'</small>'
                            .'</a>';
                    } else {
                        $list_posts['data'][$data_key]['featured_until'] =
                            '<a class="set-feature" data-id="'.$data['id'].'" title="Click to feature for 7 days">'
                            .'<i class="fa fa-star-o" style="font-size:18px;color:#ccc"></i>'
                            .'</a>';
                    }
                }
            }

            return $this->templateListView(
            [
                'can_add'               =>  false,
                'can_edit'              =>  false,
                'can_details'           =>  true,
                'can_disabled'          =>  false,
                'can_seq'               =>  false,
                'columns'               => 
                [
                    [
                        'name'          =>  'created_at',            
                        'alias'         =>  $this->_page_lang['posts_last_dt'],
                        'style'         =>  'width:150px;'
                    ],
                    [
                        'name'          =>  'category_type',            
                        'alias'         =>  'Type',
                        'options'       =>  [0 => '-', 1 => 'News', 2 => 'Event', 3 => 'Others'],
                        'style'         =>  'width:80px;'
                    ],
                    [
                        'name'          =>  'category_lang',            
                        'alias'         =>  'Language',
                        'options'       =>  [0 => '-', 1 => 'Eng', 2 => '繁體', 3 => '简体'],
                        'style'         =>  'width:90px;'
                    ],
                    [
                        'name'          =>  'category_country',            
                        'alias'         =>  $this->_page_lang['country'],
                        'options'       =>  $country_options,
                        'style'         =>  'width:130px;'
                    ],
                    [
                        'name'          =>  'topic',            
                        'alias'         =>  $this->_page_lang['posts_content']
                    ],
                    [
                        'name'          =>  'alias_name',            
                        'alias'         =>  $this->_page_lang['posts_author']
                    ],
                    [
                        'name'          =>  'total_like',            
                        'alias'         =>  $this->_page_lang['posts_total_1'],
                        'type'          =>  'number',
                        'style'         =>  'width:80px;'
                    ],
                    [
                        'name'          =>  'total_comment',            
                        'alias'         =>  $this->_page_lang['posts_total_2'],
                        'type'          =>  'number',
                        'style'         =>  'width:80px;'
                    ],
                    [
                        'name'          =>  'highlight',            
                        'alias'         =>  'Highlight',
                        'style'         =>  'width:80px;text-align:center;'
                    ],
                    [
                        'name'          =>  'featured_until',
                        'alias'         =>  'Featured',
                        'style'         =>  'width:90px;text-align:center;'
                    ]
                ]
            ], $list_posts);
        }
    }
    
    public function renew($id = 0) {
        // post event
        $this->pageAction(function() {
            // do checking
            $validator = Validator::make($this->_page_post_data, 
            [
                'title'     =>  'required',
                'content'   =>  'required'
            ]);
            
            if(!$validator->fails()) {
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
        
        $target_posts = $this->loadModel('posts')->getByID($id);

        return $this->pageData(
        [
            'show_current_member'       =>  $this->loadModel('member')->getByID($target_posts['member_id']),
            'posts'                     =>  $this->loadModel('posts')->getByID($id)
        ])->pageView('member_area/posts_renew', false, false);
    }
}
