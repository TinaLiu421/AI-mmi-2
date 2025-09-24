<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Posts extends WebController {
    
    protected $_posts_model = null;
    
    public function __construct($data) {
        parent::__construct($data);
        
        // load model
        $this->_posts_model = $this->loadModel('posts');
    }
    
    public function details($id = 0) {
        $page_data = $this->_posts_model->getByID($id);
        if(empty($page_data)) {
           // return 404 if not found
            return abort(404); 
        }
        
        $youtube_id = '';
        if(!empty($page_data['youtube_url'])) {
            $shortUrlRegex = '/youtu.be\/([a-zA-Z0-9_-]+)\??/i';
            $longUrlRegex = '/youtube.com\/((?:embed)|(?:watch)|(?:shorts))((?:\?v\=)|(?:\/))([a-zA-Z0-9_-]+)/i';

            $youtube_id = '';
            if (preg_match($longUrlRegex, $page_data['youtube_url'], $matches)) {
                $youtube_id = $matches[count($matches) - 1];
            }
            else if (preg_match($shortUrlRegex, $page_data['youtube_url'], $matches)) {
                $youtube_id = $matches[count($matches) - 1];
            }
        }
        

        // set meta
        $this->pageMeta(
        [
            'title'         =>  ((!empty($page_data['title']))?$page_data['title']:''),
            'description'   =>  substr($this->toPlainText($page_data['content']), 0, 180).'...',
            'image'         =>  ((!empty($page_data['photo']) && file_exists('upload/member_posts/'.$page_data['photo']))?($this->_mapping_data['app_url'].'/'.('upload/member_posts/'.$page_data['photo'])):(!empty($youtube_id)?'https://img.youtube.com/vi/'.$youtube_id.'/maxresdefault.jpg':''))
        ]);
        
        return $this->pageData(
        [
            'details'   =>  $page_data
        ])->pageView();
    }
}