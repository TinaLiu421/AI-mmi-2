<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Migration extends WebController {
    public function index() {
        // Set page meta
        $this->pageMeta([
            'title' => 'Migration Services',
            'description' => 'Get personalized assistance with your migration journey'
        ]);
        
        return $this->pageData([
            'page_data' => []
        ])->pageView('migration');
    }
}
