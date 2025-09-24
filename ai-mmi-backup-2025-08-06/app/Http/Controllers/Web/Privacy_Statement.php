<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Privacy_Statement extends WebController {
    
    public function index() {
        $page_data = $this->loadModel('pages')->getByID(10, $this->_current_lang_index);
        
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