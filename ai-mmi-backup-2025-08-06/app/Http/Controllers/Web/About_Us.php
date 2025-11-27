<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class About_Us extends WebController {
    
    public function index() {
        $page_data = $this->loadModel('pages')->getByID(2, $this->_current_lang_index);
        
        // set meta
        $this->pageMeta(
        [
            'title'         =>  (!empty($page_data['meta_title']))?$page_data['meta_title']:$page_data['title'],
            'description'   =>  $page_data['meta_description'],
            'image'         =>  $page_data['meta_image']
        ]);
        
        // get list
        $list_data = $this->loadModel('pages', ['table' => 'faq'])->getAll($this->_current_lang_index, false, false);
        
        return $this->pageData(
        [
            'details'   =>  $page_data,
            'list'      =>  $list_data,
        ])->pageView();
    }
    
    public function contact() {
        // post
        $this->pageAction(function() {
            // server-side reCAPTCHA verification
            $token = $this->postParamValue('g-recaptcha-response') ?: request()->input('g-recaptcha-response');
            if (empty($token) || !$this->verifyRecaptcha($token)) {
                $this->pageResult([
                    'status'  => 400,
                    'message' => $this->_page_lang['recaptcha_failed'] ?? 'reCAPTCHA verification failed'
                ]);
                return;
            }

            $subject = $this->_today_datetime.' - Contact Us 聯絡我們';
            $body = '<table style="border-spacing:0;border-collapse:collapse;width:100%;">';
                $body.= '<tr>';
                    $body.= '<td style="padding:8px;border-top:0px solid #ddd;width:100px;"><strong>'.$this->_page_lang['contact_us_form.name'].':</strong></td>';
                    $body.= '<td style="padding:8px;border-top:0px solid #ddd">'.$this->postParamValue('name').'</td>';
                $body.= '</tr>';
                
                $body.= '<tr>';
                    $body.= '<td style="padding:8px;border-top:1px solid #ddd"><strong>'.$this->_page_lang['contact_us_form.email'].':</strong></td>';
                    $body.= '<td style="padding:8px;border-top:1px solid #ddd">'.$this->postParamValue('email').'</td>';
                $body.= '</tr>';
                
                $body.= '<tr>';
                    $body.= '<td style="padding:8px;border-top:1px solid #ddd"><strong>'.$this->_page_lang['contact_us_form.subject'].':</strong></td>';
                    $body.= '<td style="padding:8px;border-top:1px solid #ddd">'.$this->postParamValue('subject').'</td>';
                $body.= '</tr>';
                
                $body.= '<tr>';
                    $body.= '<td style="padding:8px;border-top:1px solid #ddd"><strong>'.$this->_page_lang['contact_us_form.content'].':</strong></td>';
                    $body.= '<td style="padding:8px;border-top:1px solid #ddd">'.nl2br($this->postParamValue('content')).'</td>';
                $body.= '</tr>';
            $body.= '</table>';
            
            $recipient_contact = $this->_setting_model->getByName('recipient_contact');
            $recipient_contact = explode(PHP_EOL, str_ireplace(';', PHP_EOL, $recipient_contact));
            $this->sendEmail($recipient_contact, $subject, $body);

            $this->pageResult([
                'status'    =>  200,
                'message'   =>  $this->_page_lang['thanks_inquiry']
            ]);
        });
    }
}