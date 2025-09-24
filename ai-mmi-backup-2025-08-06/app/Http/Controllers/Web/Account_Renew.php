<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Validator;

class Account_Renew extends WebController {
    
    public function __construct($data) {
        parent::__construct($data);
        if(empty($this->_current_member)) {
            $this->doRedirect($this->toURL('account_login'));
        }
    }
    
    public function payment($id = 0) {
        $id = max(2, $id);
        // post event
        $this->pageAction(function() {
            $plan_account = $this->loadModel('pages', ['table' => 'plan_account'])->getByID((int)$this->getSession('selected_plan_account'), $this->_current_lang_index);
            if(!empty($plan_account)) {
                $item = 
                [
                    [
                        'name'      =>  $plan_account['title'],
                        'price'     =>  $plan_account['price'],
                        'quantity'  =>  1
                    ]
                ];

                $shipTo = 
                [
                    'name'          =>  '',
                    'email'         =>  $this->_current_member['email'],
                    'street'        =>  'HK',
                    'city'          =>  'HK',
                    'state'         =>  'HK',
                    'country_code'  =>  'HK',
                    'zip'           =>  '000000',
                    'street2'       =>  '',
                    'phone_num'     =>  '',
                ];

                // call paypal
                $paypal_api = new \App\Libraries\PaypalApi();
                $paypal_api->setCurrency('USD');
                $paypal_api->returnURL($this->toURL([$this->_mapping_data['class'],'paypal_feedback_account']));
                $paypal_api->cancelURL($this->toURL([$this->_mapping_data['class'],'payment' ,$plan_account['id']]));
                $paypal_url = $paypal_api->checkout($item, $shipTo);

                if($paypal_url) {
                    if($this->_member_model->doSavePayment('account', [
                        'member_id'             =>  $this->_current_member['id'],
                        'payment_item_id'       =>  $plan_account['id'],
                        'payment_method'        =>  $this->_page_post_data['payment_method'],
                        'payment_valid_days'    =>  max(0, (int)$plan_account['valid_days']),
                        'payment_amt'           =>  $plan_account['price'],
                        'payment_token'         =>  $paypal_api->getToken()
                    ])) {
                        $this->pageResult(
                        [
                            'status'    =>  200,
                            'url'   =>  $paypal_url
                        ]);
                    }
                    else {
                        $this->pageResult(
                        [
                            'status'    =>  200,
                            'url'   =>  $this->toURL([$this->_mapping_data['class'],'migration_service_provider'])
                        ]);
                    }
                }
            }
            else {
                $this->pageResult(
                [
                    'status'    =>  400,
                    'message'   =>  $this->_page_lang['bad_request']
                ]);
            }
        });
        
        
        $plan_account = $this->loadModel('pages', ['table' => 'plan_account'])->getByID((int)$id, $this->_current_lang_index);
        if(empty($plan_account)) {
            $this->doRedirect($this->toURL($this->_mapping_data['class']));
        }
        
        $this->setSession(['selected_plan_account' => $id]);
        
        // load view
        return $this->pageData(
        [
            'plan_account'  =>  $plan_account
        ])->pageView();
    }

    public function paypal_feedback_account() {
        $member_plan_account = $this->_member_model->getPaymentByToken('account', $this->_page_get_data['token']);
        if(!empty($member_plan_account)) {
            // confirm payment
            $paypal_api = new \App\Libraries\PaypalApi();
            $paypal_api->setCurrency('USD');
            if($paypal_api->confirm((double)$member_plan_account['payment_amt'])) {
                
                // renew
                $new_member = $this->_member_model->getByID($member_plan_account['member_id']);
                $new_expiration_date = date('Y-m-d', strtotime('+'.max(0, (int)$member_plan_account['payment_valid_days']).' days', max(strtotime($new_member['expiration_date_account']), strtotime($this->_today_date))));
                if($this->_member_model->renewExpirationDate('account', $member_plan_account['member_id'], [
                    'new_expiration_date'   =>  $new_expiration_date, 
                    'token'                 =>  $this->_page_get_data['token'],
                    'transaction_id'        =>  $paypal_api->getTransactionID()
                ])) {
                    // do redirect
                    $this->doRedirect($this->toURL([$this->_mapping_data['class'], 'payment_done']));
                }
            } 
        }
        
        // do redirect
        $this->doRedirect($this->toURL(['account', 'profile']));
    }
    
    public function payment_done() {
        return $this->pageView();
    }
}