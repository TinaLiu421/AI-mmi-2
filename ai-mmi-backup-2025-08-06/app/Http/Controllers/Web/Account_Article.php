<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Validator;

class Account_Article extends WebController {
    
    protected $_posts_model = null;
    
    public function __construct($data) {
        parent::__construct($data);
        
        // load model
        $this->_posts_model = $this->loadModel('posts');
    }
    
    public function index() {
        $target_member_id = $this->getParamValue('mid');
        if(!empty($target_member_id)) {
            $list_posts = $this->_posts_model->getAll(['member_id' => $target_member_id]);
        }
        else {
            $list_posts = $this->_posts_model->getAll(['member_id' => $target_member_id, 'show_lang' => $this->_current_lang_index]);
        }

        // load view
        return $this->pageData(
        [
            'show_current_member'   =>  $this->_current_member,
            'list_posts'            =>  $list_posts
        ])->pageView('', false, false);
    }
    
    public function fullcontent($id = 0) {
        $page_data = $this->_posts_model->getByID($id);
        if(!empty($page_data)) {
            echo nl2br($page_data['content']);
        }
    }


    public function ticklike() {
        // post event
        $this->pageAction(function() {
            if(!empty($this->_current_member)) {
                if($this->_posts_model->changeLike($this->postParamValue('posts_id', 0), $this->_current_member['id'])) {
                    $this->pageResult(
                    [
                        'status'    =>  200,
                        'total'     =>  number_format($this->_posts_model->getTotalLike($this->postParamValue('posts_id', 0)))
                    ]);
                }
                else {
                    $this->pageResult(
                    [
                        'status'    =>  $this->_posts_model->getResultCode(),
                        'message'   =>  $this->_posts_model->getResultMessage()
                    ]);
                }
            }
            else {
                $this->pageResult(
                [
                    'status'    =>  403,
                    'message'   =>  $this->_page_lang['account.permission_denied'],
                    'url'       =>  $this->toURL('account_login')
                ]);
            }
        });
    }
    
    public function comment($id = 0) {
        // post event
        $this->pageAction(function() {
            if(!empty($this->_current_member)) {
                // do checking
                $validator = Validator::make($this->_page_post_data, 
                [
                    'content'   =>  'required'
                ]);

                $this->_page_post_data['member_id'] = $this->_current_member['id'];
                if(!$validator->fails()) {
                    if($this->_posts_model->saveComment($this->_page_post_data)) {
                        $this->pageResult(
                        [
                            'status'    =>  200,
                            'total'     =>  number_format($this->_posts_model->getTotalComment($this->postParamValue('posts_id', 0)))
                        ]);
                    }
                    else {
                        $this->pageResult(
                        [
                            'status'    =>  $this->_posts_model->getResultCode(),
                            'message'   =>  $this->_posts_model->getResultMessage()
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
                $this->pageResult(
                [
                    'status'    =>  403,
                    'message'   =>  $this->_page_lang['account.permission_denied'],
                    'url'       =>  $this->toURL('account_login')
                ]);
            }
        });
        
        // load view
        return $this->pageData(
        [
            'reply' =>  $this->_posts_model->getAllComment($id)
        ])->pageView('', false, false);
    }
}