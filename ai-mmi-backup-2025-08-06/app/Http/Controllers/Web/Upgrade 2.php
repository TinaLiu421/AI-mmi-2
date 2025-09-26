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

        // 2) 业务数据（示例：给前端塞 Stripe 定价表参数）
        //    你也可以从 config/.env 里读 key/id，再传给视图
        $data = [
            'pricing_table_id' => env('STRIPE_PRICING_TABLE_ID_1'),
            'stripe_pk'        => env('STRIPE_KEY'),
        ];

        // 3) 渲染
        return $this->pageData($data)->pageView();
        
    }
}
