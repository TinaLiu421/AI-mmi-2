<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Pay_Success extends WebController
{
    public function index()
    {
        // 设置页面 Meta 信息
        $this->pageMeta([
            'title'       => $this->_page_lang['payment_success'] ?? 'Payment Success',
            'description' => '',
            'image'       => ''
        ]);

        // 如果需要额外数据，可以在这里准备
        $data = [];

        // 返回视图（约定是 web/pay_success.blade.php）
        return $this->pageData($data)->pageView();
    }
}
