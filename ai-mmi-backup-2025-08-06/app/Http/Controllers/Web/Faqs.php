<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Faqs extends WebController {
    
    public function index() {
        $page_data = $this->loadModel('pages')->getByID(4, $this->_current_lang_index);
        
        // set meta
        $this->pageMeta(
        [
            'title'         =>  (!empty($page_data['meta_title']))?$page_data['meta_title']:$page_data['title'],
            'description'   =>  $page_data['meta_description'],
            'image'         =>  $page_data['meta_image']
        ]);
        
        // get list
        $list_data = $this->loadModel('pages', ['table' => 'faq'])->getAll($this->_current_lang_index, false, false);
        
        return $this->pageData(
        [
            'list'      =>  $list_data,
            'details'   =>  $page_data
        ])->pageView();
    }
}