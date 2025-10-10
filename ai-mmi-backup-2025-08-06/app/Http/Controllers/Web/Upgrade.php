<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Upgrade extends WebController
{
    public function index()
    {
        $member = $this->_current_member ?? null;

        if (empty($member)) {
            // Retrieve the current full URL, ensuring proper redirection after login
            $currentUrl = url()->current();
            // Generate a login page link with the redirect parameter included
            $loginUrl = $this->toURL('account_login') . '?redirect=' . urlencode($currentUrl);
            return redirect()->to($loginUrl);
        }

        // Logged in: Upgrade page rendering normally
        $this->pageMeta([
            'title'       => $this->_page_lang['upgrade'] ?? 'Upgrade',
            'description' => '',
            'image'       => ''
        ]);

        $data = [
            'pricing_table_id' => env('STRIPE_PRICING_TABLE_ID_1'),
            'stripe_pk'        => env('STRIPE_KEY'),
        ];

        return $this->pageData($data)->pageView();
    }
}
