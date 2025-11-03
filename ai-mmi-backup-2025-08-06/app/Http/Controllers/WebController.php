<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cookie;

class WebController extends CoreController {
    protected $_member_model = null;
    protected $_setting_model = null;
    protected $_visa_countries = [];

    public function __construct(array $data = []) {
        parent::__construct($data);
        $this->initialize();
      
    }
    
    protected function setMyCookie($name, $value)
    {
        
        return setcookie($name, $value, time() + (86400 * 30), '/'); // 86400 = 1 day
    }
    
    protected function getMyCookie($name)
    {
        return (isset($_COOKIE[$name]))?$_COOKIE[$name]:'';
    }
    
    protected function delMyCookie($name) {
        return setcookie($name, '', time() - 3600, '/');
    }

    // send email
    protected function sendEmail($email_address = '', $subject = '', $content = '') {
        if(is_string($email_address)) {
            $email_address = [$email_address];
        }
        foreach ($email_address as $key => $to_email) {
            $email_address[$key] = $this->toPlainText($to_email);
        }
        $email_address = array_unique(array_filter($email_address));
        
        if(!empty($email_address)) {
            $html = '<div style="font-family:Arial,微軟正黑體,PMingLiU,新細明體;padding:8px;">';
                $html.= '<div style="padding:10px;border:8px solid #002065;border-radius:4px;box-shadow:0px 0px 8px #222;">';
                $html.= $content;
                $html.= '</div>';
            $html.= '</div>';

            require app_path('Libraries/sendgrid/sendgrid-php.php');
            $email = new \SendGrid\Mail\Mail();

            $email->setFrom('no-reply@at-creative.com', 'AI-mmi');

            $email->setSubject($subject);
            foreach ($email_address as $key => $to_email) {
                $to_email = $this->toPlainText($to_email);
                if (filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
                    $email->addTo($to_email);
                }
            }
            $email->addContent(
                "text/html",
                $html
            );

            $sendgrid = new \SendGrid('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');
            try {
                if($sendgrid->send($email)) {
                    return true;
                }
                return false;
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }
    
    // init
    private function initialize() {
        $this->_setting_model = $this->loadModel('setting');
        $this->_member_model = $this->loadModel('member');

        
        if(!empty($this->getSession('member_access_token'))) {
            $this->_current_member = $this->_member_model->getByToken($this->getSession('member_access_token'));
        }
        
        if(empty($this->_current_member) && !empty($this->getMyCookie('member_access_token'))){
            $this->_current_member = $this->_member_model->getByToken($this->getMyCookie('member_access_token'));
        }

        $list_countries = $this->loadModel('pages', ['table' => 'country'])->getAll($this->_current_lang_index, 
        [
            'media_files'   => 
            [
                ['type' => 'page', 'category' => 'flag']
            ]
        ], false);
        if(!empty($list_countries)) {
            foreach ($list_countries as $country_key => $country) {
                $this->_visa_countries[$country_key]['id'] = $country['id'];
                $this->_visa_countries[$country_key]['title'] = $country['title'];
                $this->_visa_countries[$country_key]['url'] = $this->toURL('visa_options/?country='.$country['id']);
                if(!empty($country['media_files']['flag'])) { 
                    $this->_visa_countries[$country_key]['photo_flag'] = $this->generateImage(reset($country['media_files']['flag']), 300, 150);
                }
                else {
                    $this->_visa_countries[$country_key]['photo_flag'] = $this->generateImage(null, 300, 150, true);
                }
            }
        }
        $this->pageData(['visa_countries' => $this->_visa_countries]);

        // —— 访客ID（持久）——
        if (empty($this->getMyCookie('guest_id'))) {
            $gid = (string) \Str::uuid();
            $this->setMyCookie('guest_id', $gid);
        } else {
            $gid = $this->getMyCookie('guest_id');
        }

        // —— 访客聊天计数（会话级）——
        if (empty($this->getSession('guest_chat_count'))) {
            $this->setSession(['guest_chat_count' => 0]);
        }

    }
    
    function getYoutubeEmbedUrl($url) {
        $shortUrlRegex = '/youtu.be\/([a-zA-Z0-9_-]+)\??/i';
        $longUrlRegex = '/youtube.com\/((?:embed)|(?:watch)|(?:shorts))((?:\?v\=)|(?:\/))([a-zA-Z0-9_-]+)/i';

        $youtube_id = '';
        if (preg_match($longUrlRegex, $url, $matches)) {
            $youtube_id = $matches[count($matches) - 1];
        }
        else if (preg_match($shortUrlRegex, $url, $matches)) {
            $youtube_id = $matches[count($matches) - 1];
        }
        return (!empty($youtube_id))?('https://www.youtube.com/embed/'.$youtube_id):'';
    }
}
