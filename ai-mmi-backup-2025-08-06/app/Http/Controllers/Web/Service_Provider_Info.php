<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Service_Provider_Info extends WebController {

    public function index() {
        $member = $this->_current_member;
        if (!empty($member)
            && in_array((int) ($member['type'] ?? 0), [2, 3], true)
            && (int) ($member['status'] ?? 0) === 1) {
            return redirect($this->toURL('job_applications'));
        }

        // set meta
        $this->pageMeta(
        [
            'title'         =>  $this->_page_lang['service_provider_info.title'].' - AI-mmi',
            'description'   =>  $this->_page_lang['service_provider_info.headline'].' - '.$this->_page_lang['service_provider_info.subheadline'],
            'image'         =>  ''
        ]);

        return $this->pageData([])->pageView();
    }
}
