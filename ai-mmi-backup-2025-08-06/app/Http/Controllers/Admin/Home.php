<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

class Home extends AdminController {
    
    public function index() {
        $this->doRedirect($this->toURL('pages'));
    }
}