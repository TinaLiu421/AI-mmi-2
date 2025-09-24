<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Validator;

class Account_Submission extends WebController {
    
    public function __construct($data) {
        parent::__construct($data);
        if(empty($this->_current_member)) {
            $this->doRedirect($this->toURL('account_login'));
        }
    }
    
    public function index() {
        $page_data = $this->loadModel('pages')->getByID(6, $this->_current_lang_index);
        
        // set meta
        $this->pageMeta(
        [
            'title'         =>  (!empty($page_data['meta_title']))?$page_data['meta_title']:$page_data['title'],
            'description'   =>  $page_data['meta_description'],
            'image'         =>  $page_data['meta_image']
        ]);

        // get list
        $list_plan_visa_submission = $this->loadModel('pages', ['table' => 'plan_visa_submission'])->getAll($this->_current_lang_index, null, false);
        
        // load view
        return $this->pageData(
        [
            'list_plan_visa_submission' =>  $list_plan_visa_submission,
            'details'                   =>  $page_data
        ])->pageView();
    }
    
    public function payment($id = 0) {
        // post event
        $this->pageAction(function() {
            $plan_visa_submission = $this->loadModel('pages', ['table' => 'plan_visa_submission'])->getByID((int)$this->getSession('selected_plan_visa_submission'), $this->_current_lang_index);
            if(!empty($plan_visa_submission)) {
                $item = 
                [
                    [
                        'name'      =>  $plan_visa_submission['title'],
                        'price'     =>  $plan_visa_submission['price'],
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
                $paypal_api->cancelURL($this->toURL([$this->_mapping_data['class']]));
                $paypal_url = $paypal_api->checkout($item, $shipTo);

                if($paypal_url) {
                    if($this->_member_model->doSavePayment('visa_submission', [
                        'member_id'             =>  $this->_current_member['id'],
                        'payment_item_id'       =>  $plan_visa_submission['id'],
                        'payment_method'        =>  $this->_page_post_data['payment_method'],
                        'payment_valid_days'    =>  max(0, (int)$plan_visa_submission['valid_days']),
                        'payment_amt'           =>  $plan_visa_submission['price'],
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
        
        
        $plan_visa_submission = $this->loadModel('pages', ['table' => 'plan_visa_submission'])->getByID((int)$id, $this->_current_lang_index);
        if(empty($plan_visa_submission)) {
            $this->doRedirect($this->toURL($this->_mapping_data['class']));
        }
        
        $this->setSession(['selected_plan_visa_submission' => $id]);
        
        // load view
        return $this->pageData(
        [
            'plan_visa_submission'  =>  $plan_visa_submission
        ])->pageView();
    }

    public function paypal_feedback_account() {
        $member_plan_visa_submission = $this->_member_model->getPaymentByToken('visa_submission', $this->_page_get_data['token']);
        if(!empty($member_plan_visa_submission)) {
            // confirm payment
            $paypal_api = new \App\Libraries\PaypalApi();
            $paypal_api->setCurrency('USD');
            if($paypal_api->confirm((double)$member_plan_visa_submission['payment_amt'])) {
                // renew
                $new_member = $this->_member_model->getByID($member_plan_visa_submission['member_id']);
                if((int)$member_plan_visa_submission['payment_item_id'] == 2) {
                    $new_expiration_date_ai = date('Y-m-d', strtotime('+'.max(0, (int)$member_plan_visa_submission['payment_valid_days']).' days', max(strtotime($new_member['expiration_date_visa_submission_ai']), strtotime($this->_today_date))));
                    $new_expiration_date_human = date('Y-m-d', strtotime('+'.max(0, (int)$member_plan_visa_submission['payment_valid_days']).' days', max(strtotime($new_member['expiration_date_visa_submission_human']), strtotime($this->_today_date))));
                    if($this->_member_model->renewExpirationDate('visa_submission', $member_plan_visa_submission['member_id'], [
                        'new_expiration_date_ai'    =>  $new_expiration_date_ai,
                        'new_expiration_date_human' =>  $new_expiration_date_human, 
                        'token'                     =>  $this->_page_get_data['token'],
                        'transaction_id'            =>  $paypal_api->getTransactionID()
                    ])) {
                        // do redirect
                        $this->doRedirect($this->toURL([$this->_mapping_data['class'], 'payment_done']));
                    }
                }
                else {
                    $new_expiration_date_ai = date('Y-m-d', strtotime('+'.max(0, (int)$member_plan_visa_submission['payment_valid_days']).' days', max(strtotime($new_member['expiration_date_visa_submission_ai']), strtotime($this->_today_date))));
                    if($this->_member_model->renewExpirationDate('visa_submission', $member_plan_visa_submission['member_id'], [
                        'new_expiration_date_ai'    =>  $new_expiration_date_ai,
                        'token'                     =>  $this->_page_get_data['token'],
                        'transaction_id'            =>  $paypal_api->getTransactionID()
                    ])) {
                        // do redirect
                        $this->doRedirect($this->toURL([$this->_mapping_data['class'], 'payment_done']));
                    }
                }
            } 
        }
        
        // do redirect
        $this->doRedirect($this->toURL([$this->_mapping_data['class']]));
    }
    
    public function payment_done() {
        return $this->pageView();
    }
}