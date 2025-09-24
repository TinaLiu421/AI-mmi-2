<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Contact_Us extends WebController {
    
    public function __construct($data) {
        parent::__construct($data);
        
        /*if(empty($this->_current_member)) {
            $this->doRedirect($this->toURL('account_login'));
        }
        
        if(strtotime($this->_current_member['expiration_date_visa_submission_human']) < strtotime($this->_today_date)) {
            $this->doRedirect($this->toURL('account_Submission'));
        }*/
    }
    
    public function index() {
        // post
        $this->pageAction(function() {
            $myanswers = $this->getSession('myanswers');
            if(empty($myanswers)) {
                $myanswers = [];
            }
            $myanswers[] = $this->postParamValue('inquiry');
            $next = $this->default_questions((count($myanswers)+1));
            
            $this->setSession(['myanswers' => $myanswers]);
            
            if(!empty($this->_current_member)) {
                $member_owner_name = $this->_current_member['alias_name'];
                $member_owner_avatar = 'asset/image/icon-member.png';
                if(!empty($this->_current_member['avatar'])) {
                    if(file_exists('upload/member_avatar/'.$this->_current_member['avatar'])) {
                        $member_owner_avatar = 'upload/member_avatar/'.$this->_current_member['avatar'];
                    }
                    else {
                        $member_owner_avatar = 'upload/member_logo/'.$this->_current_member['avatar'];
                    }
                }
            }
            else {
                $member_owner_name = 'Guest';
                $member_owner_avatar = 'asset/image/icon-member.png';
            }
            $ai_owner_name = 'AI-mmi';
            $ai_owner_avatar = 'asset/image/logo-mmi.png';
            
            if(!empty($next)) {
                $this->pageResult([
                    'status'    =>  200,
                    'message'   =>  nl2br($this->postParamValue('inquiry')),
                    'next'      =>  $next,
                    'member_owner_name' => $member_owner_name,
                    'member_owner_avatar' => $member_owner_avatar,
                    'ai_owner_name' => $ai_owner_name,
                    'ai_owner_avatar' => $ai_owner_avatar,
                ]);
            }
            else {
                $subject = $this->_today_datetime.' - Contact Us 聯絡我們';
                $body = '';
                $questions = $this->default_questions();
                foreach ($questions as $qkey => $q) {
                    if(!empty($myanswers[$qkey-1])) {
                        $body.= '<p><strong>'.($q['title']).':</strong><br/>'.(nl2br($myanswers[$qkey-1])).'</p>';
                    }
                }
                $recipient_contact = $this->_setting_model->getByName('recipient_contact');
                $recipient_contact = explode(PHP_EOL, str_ireplace(';', PHP_EOL, $recipient_contact));
                $this->sendEmail($recipient_contact, $subject, $body);
                $this->pageResult([
                    'status'    =>  200,
                    'message'   =>  nl2br($this->postParamValue('inquiry')),
                    'done'      =>  $this->_page_lang['thanks_inquiry'],
                    'member_owner_name' => $member_owner_name,
                    'member_owner_avatar' => $member_owner_avatar,
                    'ai_owner_name' => $ai_owner_name,
                    'ai_owner_avatar' => $ai_owner_avatar,
                ]);
            }
        });
        
        $this->delSession('myanswers');
        $page_data = $this->loadModel('pages')->getByID(8, $this->_current_lang_index);
        
        // set meta
        $this->pageMeta(
        [
            'title'         =>  (!empty($page_data['meta_title']))?$page_data['meta_title']:$page_data['title'],
            'description'   =>  $page_data['meta_description'],
            'image'         =>  $page_data['meta_image']
        ]);
        
        return $this->pageData(
        [
            'questions' =>  $this->default_questions(),
            'details'   =>  $page_data
        ])->pageView();
    }
    
    protected function default_questions($index = '') {
        $questions = [];
        $questions[1] = [
            1   =>  
            [
                'title'     =>  'Hi thanks for contacting us, how can we help you?',
                'answers'   =>  ''
            ],
            2   =>  
            [
                'title'     =>  'May I know which country would you like to move to?',
                'answers'   =>  
                [
                    1       =>  'Britain',
                    2       =>  'United States',
                    3       =>  'Canada',
                    4       =>  'Australia',
                ]
            ],
            3   =>  
            [
                'title'     =>  'May I know what types of visa are you considering?',
                'answers'   =>  
                [
                    1       =>  'Skilled based migration visa',
                    2       =>  'Employer sponsorship visa',
                    3       =>  'Business or entrepreneurship visa',
                    4       =>  'Investor visa',
                    5       =>  'Family visa',
                    6       =>  'Student visa',
                    7       =>  'Tourist visa',
                    8       =>  'Refugee visa'
                ]
            ],
            4   =>  
            [
                'title'     =>  'What’s your family name?',
                'answers'   =>  ''
            ],
            5   =>  
            [
                'title'     =>  'What’s know your given name?',
                'answers'   =>  ''
            ],
            6   =>  
            [
                'title'     =>  'What’s your mobile number (please include country code)?',
                'answers'   =>  ''
            ],
            7   =>  
            [
                'title'     =>  'What’s your email address?',
                'answers'   =>  ''
            ]
        ];
        
        $questions[2] = [
            1   =>  
            [
                'title'     =>  '您好，感謝您與我們聯繫，我們能為您提供什麼幫助？',
                'answers'   =>  ''
            ],
            2   =>  
            [
                'title'     =>  '我可以知道您想搬到哪個國家嗎？',
                'answers'   =>  
                [
                    1       =>  '英國',
                    2       =>  '美國',
                    3       =>  '加拿大',
                    4       =>  '澳洲',
                ]
            ],
            3   =>  
            [
                'title'     =>  '我可以知道您正在考慮什麼類型的簽證嗎？',
                'answers'   =>  
                [
                    1       =>  '技術移民簽證',
                    2       =>  '雇主擔保簽證',
                    3       =>  '商業或創業簽證',
                    4       =>  '投資者簽證',
                    5       =>  '家庭簽證',
                    6       =>  '學生簽證',
                    7       =>  '旅遊簽證',
                    8       =>  '難民簽證'
                ]
            ],
            4   =>  
            [
                'title'     =>  '您姓是什麼？',
                'answers'   =>  ''
            ],
            5   =>  
            [
                'title'     =>  '您名是什麼？',
                'answers'   =>  ''
            ],
            6   =>  
            [
                'title'     =>  '您的手機號碼是多少（請包含國家代碼）？',
                'answers'   =>  ''
            ],
            7   =>  
            [
                'title'     =>  '您的電子郵件地址是什麼？',
                'answers'   =>  ''
            ],
        ];
        
        $questions[3] = [
            1   =>  
            [
                'title'     =>  '您好，感谢您与我们联系，我们能为您提供什麽帮助？',
                'answers'   =>  ''
            ],
            2   =>  
            [
                'title'     =>  '我可以知道您想搬到哪个国家吗？',
                'answers'   =>  
                [
                    1       =>  '英国',
                    2       =>  '美国',
                    3       =>  '加拿大',
                    4       =>  '澳洲',
                ]
            ],
            3   =>  
            [
                'title'     =>  '我可以知道您正在考虑什麽类型的签证吗？',
                'answers'   =>  
                [
                    1       =>  '技术移民签证',
                    2       =>  '雇主担保签证',
                    3       =>  '商业或创业签证',
                    4       =>  '投资者签证',
                    5       =>  '家庭签证',
                    6       =>  '学生签证',
                    7       =>  '旅游签证',
                    8       =>  '难民签证'
                ]
            ],
            4   =>  
            [
                'title'     =>  '您姓是什麽？',
                'answers'   =>  ''
            ],
            5   =>  
            [
                'title'     =>  '您名是什麽？',
                'answers'   =>  ''
            ],
            6   =>  
            [
                'title'     =>  '您的手机号码是多少（请包含国家代码）？',
                'answers'   =>  ''
            ],
            7   =>  
            [
                'title'     =>  '您的电子邮件地址是什麽？',
                'answers'   =>  ''
            ],
        ];
        
        return (!empty($index))?((!empty($questions[$this->_current_lang_index][$index]))?$questions[$this->_current_lang_index][$index]:''):$questions[$this->_current_lang_index];
    }
}