<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Upgrade extends WebController
{
    public function index()
    {
        // 1) 页面 Meta
        $this->pageMeta([
            'title'       => $this->_page_lang['upgrade'] ?? 'Upgrade',
            'description' => '',
            'image'       => ''
        ]);

        // 2) 业务数据
        $data = [
            'pricing_table_id' => env('STRIPE_PRICING_TABLE_ID_1'),
            'stripe_pk'        => env('STRIPE_KEY'),
            'member_id'        => $this->_current_member['id'] ?? null,
            'customer_email'   => $this->_current_member['email'] ?? '',
            'current_plan'     => null,
        ];

        // Get current plan info if member is logged in
        if (!empty($this->_current_member)) {
            $memberModel = $this->loadModel('member');
            $data['current_plan'] = $memberModel->getCurrentPlanInfo($this->_current_member['id']);
        }

        // 3) 渲染
        return $this->pageData($data)->pageView();

    }
}
