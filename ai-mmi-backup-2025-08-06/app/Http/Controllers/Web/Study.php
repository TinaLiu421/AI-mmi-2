<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Study extends WebController {
    public function index() {
        // Set page meta
        $this->pageMeta([
            'title' => 'Study Abroad Guidance',
            'description' => 'Get personalized assistance with your study abroad journey'
        ]);
        
        return $this->pageData([
            'page_data' => []
        ])->pageView('study');
    }
}
