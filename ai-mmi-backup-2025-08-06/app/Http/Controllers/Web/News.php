<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class News extends WebController {
    
    public function details($id = 0) {
        $page_data = $this->loadModel('pages', ['table' => 'news'])->getByID((int)$id, $this->_current_lang_index);
        if(empty($page_data)) {
            $this->doRedirect($this->toURL('home')); 
        }
        
        // set meta
        $this->pageMeta(
        [
            'title'         =>  (!empty($page_data['meta_title']))?$page_data['meta_title']:$page_data['title'],
            'description'   =>  $page_data['meta_description'],
            'image'         =>  $page_data['meta_image']
        ]);
        
        return $this->pageData(
        [
            'details'   =>  $page_data
        ])->pageView();
    }
}