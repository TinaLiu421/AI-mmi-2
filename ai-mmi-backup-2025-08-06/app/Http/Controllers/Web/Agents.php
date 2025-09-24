<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Agents extends WebController {
    
    public function index() {
        // set meta
        $this->pageMeta(
        [
            'title'         =>  $this->_page_lang['list_agents'],
            'description'   =>  '',
            'image'         =>  ''
        ]);
        
        $incldued_country_id = [];
        if(!empty($this->_visa_countries)) {
            foreach($this->_visa_countries as $country) {
                $incldued_country_id[] = $country['id'];
            }
        }
        $list_agents = $this->_member_model->getAgentByCountryID($incldued_country_id);
        return $this->pageData(
        [
            'list'      =>  $list_agents,
        ])->pageView();
    }
}