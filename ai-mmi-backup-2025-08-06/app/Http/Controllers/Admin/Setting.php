<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

class Setting extends AdminController {
    
    public function __construct($data) {
        parent::__construct($data);
        
        // set nav
        $this->pageNavigator($this->_page_lang['setting'], $this->_mapping_data['class']);
        
        // load model
        $this->_setting_model = $this->loadModel('setting');
    }
    
    public function index() {
        $this->doRedirect($this->toURL('setting/general'));
    }
    
    public function general() {
        // post
        $this->pageAction(function() {
            $result = $this->_setting_model->doSave(
            [
                'meta_title'        =>  $this->postParamValue('meta_title'),
                'meta_description'  =>  $this->postParamValue('meta_description'),
                'meta_image'        =>  $this->postParamValue('meta_image'),
                
                'contact_telephone' =>  $this->postParamValue('contact_telephone'),
                'contact_fax'       =>  $this->postParamValue('contact_fax'),
                'contact_email'     =>  $this->postParamValue('contact_email'),
                'contact_whatsapp'  =>  $this->postParamValue('contact_whatsapp'),
                'contact_facebook'  =>  $this->postParamValue('contact_facebook'),
                'contact_ig'        =>  $this->postParamValue('contact_ig'),
                'contact_address'   =>  $this->postParamValue('contact_address'),
                'contact_map'       =>  $this->postParamValue('contact_map')
            ]);
            
            // set page result
            $this->pageResult(
            [
                'status'    =>  $this->_setting_model->getResultCode(),
                'message'   =>  $this->_setting_model->getResultMessage()
            ], ((!empty($result))?true:false));
        });
        
        // set nav
        $this->pageNavigator($this->_page_lang['setting_general'], $this->toURL($this->_mapping_data['class'].'/general'));
        
        // load view
        return $this->pageData(
        [
            'meta_title'        =>  $this->_setting_model->getByName('meta_title'),
            'meta_description'  =>  $this->_setting_model->getByName('meta_description'),
            'meta_image'        =>  $this->_setting_model->getByName('meta_image'),
            
            'contact_telephone' =>  $this->_setting_model->getByName('contact_telephone'),
            'contact_fax'       =>  $this->_setting_model->getByName('contact_fax'),
            'contact_email'     =>  $this->_setting_model->getByName('contact_email'),
            'contact_whatsapp'  =>  $this->_setting_model->getByName('contact_whatsapp'),
            'contact_facebook'  =>  $this->_setting_model->getByName('contact_facebook'),
            'contact_ig'        =>  $this->_setting_model->getByName('contact_ig'),
            'contact_address'   =>  $this->_setting_model->getByName('contact_address'),
            'contact_map'       =>  $this->_setting_model->getByName('contact_map')
        ])->pageView('setting.general');
    }
    
    public function email() {
        // post
        $this->pageAction(function() {
            $result = $this->_setting_model->doSave(
            [
                'recipient_application'  =>  $this->postParamValue('recipient_application'),
                'recipient_contact'      =>  $this->postParamValue('recipient_contact'),
                'recipient_autofill'     =>  $this->postParamValue('recipient_autofill')
            ]);
            
            // set page result
            $this->pageResult(
            [
                'status'    =>  $this->_setting_model->getResultCode(),
                'message'   =>  $this->_setting_model->getResultMessage()
            ], ((!empty($result))?true:false));
        });
        
        // set nav
        $this->pageNavigator($this->_page_lang['setting_email'], $this->toURL($this->_mapping_data['class'].'/email'));
        
        // load view
        return $this->pageData(
        [
            'recipient_application'      =>  $this->_setting_model->getByName('recipient_application'),
            'recipient_contact'          =>  $this->_setting_model->getByName('recipient_contact'),
            'recipient_autofill'         =>  $this->_setting_model->getByName('recipient_autofill'),
        ])->pageView('setting.email');
    }
    
    public function whitelist() {
        // post
        $this->pageAction(function() {
            $result = $this->_setting_model->doSave(
            [
                'ip_whitelist'  =>  $this->postParamValue('ip_whitelist')
            ]);
            
            // set page result
            $this->pageResult(
            [
                'status'    =>  $this->_setting_model->getResultCode(),
                'message'   =>  $this->_setting_model->getResultMessage()
            ], ((!empty($result))?true:false));
        });
        
        // set nav
        $this->pageNavigator($this->_page_lang['setting_whitelist'], $this->toURL($this->_mapping_data['class'].'/whitelist'));
        
        // load view
        return $this->pageData(
        [
            'ip_whitelist'      =>  $this->_setting_model->getByName('ip_whitelist'),
            'you_ip_address'    =>  $this->getCurrentIP()
        ])->pageView('setting.whitelist');
    }
}
