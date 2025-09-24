<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Forum extends WebController {
    protected $_forum_model = null;
    
    public function __construct($data) {
        parent::__construct($data);
        
        // load model
        $this->_forum_model = $this->loadModel('forum');
    }
    
    public function index() {
        // post
        $this->pageAction(function() {
            $new_forum_id = $this->_forum_model->doSave([
                'member_id'         =>  $this->_current_member['id'],
                'forum_country'     =>  $this->postParamValue('forum_country', 0),
                'forum_topic'       =>  $this->postParamValue('forum_topic'),
                'forum_content'     =>  $this->postParamValue('forum_content'),
                'last_comment_by'   =>  $this->_current_member['id'],
                'last_comment_at'   =>  $this->_today_datetime
            ]);
            
            $this->pageResult([
                'status'    =>  $this->_forum_model->getResultCode(),
                'message'   =>  (!empty($new_forum_id))?$this->_page_lang['forum_thanks']:$this->_forum_model->getResultMessage()
            ]);
        });
        
        $country_options = [];
        $list_countries = $this->loadModel('pages', ['table' => 'country'])->getAll($this->_current_lang_index, null, false);
        if(!empty($list_countries)) {
            foreach ($list_countries as $country) {
                $country_options[$country['id']] = $country['title'];
            }
        }
        $country_options[-1] = $this->_page_lang['forum_others'];
        
        // set meta
        $this->pageMeta(
        [
            'title'         =>  $this->_page_lang['forum'],
        ]);
        
        return $this->pageOptions(
        [
            'country' => $country_options
        ])->pageData(
        [
            'list_forum' => $this->_forum_model->getAll(['keywords' => $this->getParamValue('keywords'), 'country' => $this->getParamValue('country')])
        ])->pageView();
    }
    
    public function details($id = 0) {
        // post
        $this->pageAction(function() {
            $new_forum_comment_id = $this->_forum_model->doSaveComment([
                'member_id'         =>  $this->_current_member['id'],
                'content'           =>  $this->postParamValue('forum_content'),
                'last_comment_by'   =>  $this->_current_member['id'],
                'last_comment_at'   =>  $this->_today_datetime
            ], $this->postParamValue('forum_id'));
            
            $this->pageResult([
                'status'    =>  $this->_forum_model->getResultCode(),
                'message'   =>  (!empty($new_forum_comment_id))?$this->_page_lang['forum_thanks']:$this->_forum_model->getResultMessage()
            ]);
        });
        
        
        $target_forum = $this->_forum_model->getByID($id);
        if(empty($target_forum)) {
            $this->doRedirect($this->toURL('forum'));
        }
        
        $this->_forum_model->addTotalView($id);
        
        $country_options = [];
        $list_countries = $this->loadModel('pages', ['table' => 'country'])->getAll($this->_current_lang_index, null, false);
        if(!empty($list_countries)) {
            foreach ($list_countries as $country) {
                $country_options[$country['id']] = $country['title'];
            }
        }
        $country_options[-1] = $this->_page_lang['forum_others'];
        
        return $this->pageOptions(
        [
            'country' => $country_options
        ])->pageData(
        [
            'target_forum' => $target_forum,
            'list_forum_comment' => $this->_forum_model->getAllComment($id)
        ])->pageView();
    }
}