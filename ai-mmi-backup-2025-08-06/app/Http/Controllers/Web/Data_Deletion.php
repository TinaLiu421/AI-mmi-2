<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Data_Deletion extends WebController {

    public function index() {
        // set meta
        $this->pageMeta(
        [
            'title'         =>  'User Data Deletion',
            'description'   =>  'Request deletion of your user data and account information',
            'image'         =>  ''
        ]);

        return $this->pageData([])->pageView();
    }
}
