<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Free_Assessment extends WebController {
    protected $_free_assessment_model = null;

    public function index() {
        // post
        $this->pageAction(function() {
            $this->_free_assessment_model = $this->loadModel('free_assessment');
            $questions = $this->default_questions();
            if($new_free_assessment_id = $this->_free_assessment_model->doSave([
                'questions'         =>  $questions,
                'answers'           =>  $this->postParamValue('answers'),
                'full_name'         =>  $this->postParamValue('full_name'),
                'email'             =>  $this->postParamValue('email'),
                'telephone_code'    =>  preg_replace('/^(\+)(.*)/i', '$2', $this->postParamValue('telephone_code')),
                'telephone_num'     =>  $this->postParamValue('telephone_num'),
            ])) {
                $this->toAdminEmail($new_free_assessment_id);
                
                $this->pageResult([
                    'status'    =>  200,
                    'message'   => $this->_page_lang['chat_robot.start_free_thanks']
                ]);
            }
            else {
                $this->pageResult([
                    'status'    =>  $this->_free_assessment_model->getResultCode(),
                    'message'   =>  $this->_free_assessment_model->getResultMessage()
                ]);
            }
        });
        
        $page_data = $this->loadModel('pages')->getByID(7, $this->_current_lang_index);
        
        // set meta
        $this->pageMeta(
        [
            'title'         =>  (!empty($page_data['meta_title']))?$page_data['meta_title']:$page_data['title'],
            'description'   =>  $page_data['meta_description'],
            'image'         =>  $page_data['meta_image']
        ]);

        return $this->pageData(
        [
            'details'   =>  $page_data,
            'questions' =>  $this->default_questions()
        ])->pageView();
    }
    
    protected function default_questions() {
        $questions = [];
        
        $questions[1] = [
            1   =>  
            [
                'title'     =>  'Would you like AI-mmi to assess your eligibility for a particular visa and give you an assessment?',
                'answers'   =>  
                [
                    1       =>  'Yes',
                    2       =>  'No',
                ]
            ],
            2   =>  
            [
                'title'     =>  'Which country you like to move?',
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
                'title'     =>  'What types of visa you would like to explore?',
                'answers'   =>  
                [
                    1       =>  'Skilled immigrant visas',
                    2       =>  'Start Up Visa',
                    3       =>  'Student visa',
                    4       =>  'Family visas',
                    5       =>  'Employer sponsorship visas',
                ]
            ],
            4   =>  
            [
                'title'     =>  'What is your education level?',
                'answers'   =>  
                [
                    1       =>  'Secondary',
                    2       =>  'Diploma or Associate Degreee',
                    3       =>  'Bachelor',
                    4       =>  'Master',
                    5       =>  'Doctoral',
                ],
                'remark'    =>  'If below Bachelor degree, not qualified for skilled visas'
            ],
            5   =>  
            [
                'title'     =>  'What is your age?',
                'answers'   =>  
                [
                    1       =>  '18-25',
                    2       =>  '25-33',
                    3       =>  '33-40',
                    4       =>  '40-45',
                    5       =>  '45-55',
                    6       =>  '>55',
                ],
                'remark'    =>  'If over 45, not qulaified for Australia skilled visas'
            ],
            6   =>  
            [
                'title'     =>  'Have you completed an English test?',
                'answers'   =>  
                [
                    1       =>  'Yes',
                    2       =>  'No',
                ],
                'remark'    =>  'If answer is No, state that you need to sit for English test before submitting an application.'
            ],
            7   =>  
            [
                'title'     =>  'If yes, what are the test results?',
                'answers'   =>  
                [
                    1       =>  'IELTS 6 overall',
                    2       =>  'IELTS 6',
                    3       =>  'IELTS 7',
                    4       =>  'IELTS 8',
                    5       =>  'Don’t have IELTS results yet',
                ]
            ],
            8   =>  
            [
                'title'     =>  'What is your occupation?',
                'answers'   =>  '',
            ],
            9   =>  
            [
                'title'     =>  'How many years full time work experience you got?',
                'answers'   =>  
                [
                    1       =>  '> 1 year',
                    2       =>  '1-3 years',
                    3       =>  '3-5 years',
                    4       =>  '5-8 year',
                    5       =>  '> 8 years',
                ],
                'remark'    =>  'If below 3 years, not qualified for skilled visas',
            ],
            10   =>  
            [
                'title'     =>  'How many years full time work you gained in the country that you are planning to move?',
                'answers'   =>  
                [
                    1       =>  '0 year',
                    2       =>  '1-3 years',
                    3       =>  '3-5 years',
                    4       =>  '5-8 year',
                    5       =>  '> 8 years',
                ]
            ],
            11   =>  
            [
                'title'     =>  'Do you have a job offer from the target country?',
                'answers'   =>  
                [
                    1       =>  'Yes',
                    2       =>  'No',
                ],
                'remark'    =>  'If yes, qualified for skilled and employer sponsor visas',
            ]
        ];
        
        $questions[2] = [
            1 =>
            [
                'title'     =>  '您希望 AI-mmi 評估您獲得特定簽證的資格並為您提供評估嗎？',
                'answers'   =>
                [
                    1       =>  '是',
                    2       =>  '否',
                ]
            ],
            2 =>
            [
                'title'     =>  '你想搬到哪個國家？',
                'answers'   =>
                [
                    1       =>  '英國',
                    2       =>  '美國',
                    3       =>  '加拿大',
                    4       =>  '澳洲',
                ]
            ],
            3 =>
            [
                'title'     =>  '您想探索哪種類型的簽證？',
                'answers'   =>
                [
                    1       =>  '技術移民簽證',
                    2       =>  '創業簽證',
                    3       =>  '學生簽證',
                    4       =>  '家庭簽證',
                    5       =>  '雇主擔保簽證',
                ]
            ],
            4 =>
            [
                'title'     =>  '您的教育程度是多少？',
                'answers'   =>
                [
                    1       =>  '中學',
                    2       =>  '文憑或副學士學位',
                    3       =>  '學士',
                    4       =>  '碩士',
                    5       =>  '博士',
                ],
                'remark'    =>  '如果學士以下學歷，不符合技術簽證資格'
            ],
            5 =>
            [
                'title'     =>  '你的年齡是多少？',
                'answers'   =>
                [
                    1       =>  '18-25',
                    2       =>  '25-33',
                    3       =>  '33-40',
                    4       =>  '40-45',
                    5       =>  '45-55',
                    6       =>  '>55',
                ],
                'remark'    =>  '如果超過45歲，沒有資格獲得澳洲技術簽證'
            ],
            6 =>
            [
                'title'     =>  '你完成英文測驗了嗎？',
                'answers'   =>
                [
                    1       =>  '是',
                    2       =>  '否',
                ],
                'remark'    =>  '如果答案為否，請說明您在提交申請之前需要參加英語測試。'
            ],
            7 =>
            [
                'title'     =>  '如果是，測試結果是什麼？',
                'answers'   =>
                [
                    1       =>  '雅思總分 6',
                    2       =>  '雅思6分',
                    3       =>  '雅思7分',
                    4       =>  '雅思8分',
                    5       =>  '還沒有雅思成績',
                ]
            ],
            8 =>
            [
                'title'     =>  '你的職業是什麼？',
                'answers'   =>  '',
            ],
            9 =>
            [
                'title'     =>  '您有多少年全職工作經驗？',
                'answers'   =>
                [
                    1       =>  '> 1 年',
                    2       =>  '1-3 年',
                    3       =>  '3-5 年',
                    4       =>  '5-8 年',
                    5       =>  '> 8 年',
                ],
                'remark'    =>  '如果不滿3年，不符合技術簽證資格',
            ],
            10 =>
            [
                'title'     =>  '您在計劃移居的國家/地區獲得了多少年全職工作？',
                'answers'   =>
                [
                    1       =>  '0 年',
                    2       =>  '1-3 年',
                    3       =>  '3-5 年',
                    4       =>  '5-8 年',
                    5       =>  '> 8 年',
                ]
            ],
            11 =>
            [
                'title'     =>  '您有目標國家的工作機會嗎？',
                'answers'   =>
                [
                    1       =>  '是',
                    2       =>  '否',
                ],
                'remark'    =>  '如果是，則有資格獲得技術簽證和雇主擔保簽證',
            ]
        ];
        
        $questions[3] = [
            1 =>
            [
                'title'     =>  '您希望 AI-mmi 评估您获得特定签证的资格并为您提供评估吗？',
                'answers'   =>
                [
                    1       =>  '是',
                    2       =>  '否',
                ]
            ],
            2 =>
            [
                'title'     =>  '你想搬到哪个国家？',
                'answers'   =>
                [
                    1       =>  '英国',
                    2       =>  '美国',
                    3       =>  '加拿大',
                    4       =>  '澳洲',
                ]
            ],
            3 =>
            [
                'title'     =>  '您想探索哪种类型的签证？',
                'answers'   =>
                [
                    1       =>  '技术移民签证',
                    2       =>  '创业签证',
                    3       =>  '学生签证',
                    4       =>  '家庭签证',
                    5       =>  '雇主担保签证',
                ]
            ],
            4 =>
            [
                'title'     =>  '您的教育程度是多少？',
                'answers'   =>
                [
                    1       =>  '中学',
                    2       =>  '文凭或副学士学位',
                    3       =>  '学士',
                    4       =>  '硕士',
                    5       =>  '博士',
                ],
                'remark'    =>  '如果学士以下学历，不符合技术签证资格'
            ],
            5 =>
            [
                'title'     =>  '你的年龄是多少？',
                'answers'   =>
                [
                    1       =>  '18-25',
                    2       =>  '25-33',
                    3       =>  '33-40',
                    4       =>  '40-45',
                    5       =>  '45-55',
                    6       =>  '>55',
                ],
                'remark'    =>  '如果超过45岁，没有资格获得澳洲技术签证'
            ],
            6 =>
            [
                'title'     =>  '你完成英文测验了吗？',
                'answers'   =>
                [
                    1       =>  '是',
                    2       =>  '否',
                ],
                'remark'    =>  '如果答案为否，请说明您在提交申请之前需要参加英语测试。'
            ],
            7 =>
            [
                'title'     =>  '如果是，测试结果是什麽？',
                'answers'   =>
                [
                    1       =>  '雅思总分 6',
                    2       =>  '雅思6分',
                    3       =>  '雅思7分',
                    4       =>  '雅思8分',
                    5       =>  '还没有雅思成绩',
                ]
            ],
            8 =>
            [
                'title'     =>  '你的职业是什麽？',
                'answers'   =>  '',
            ],
            9 =>
            [
                'title'     =>  '您有多少年全职工作经验？',
                'answers'   =>
                [
                    1       =>  '> 1 年',
                    2       =>  '1-3 年',
                    3       =>  '3-5 年',
                    4       =>  '5-8 年',
                    5       =>  '> 8 年',
                ],
                'remark'    =>  '如果不满3年，不符合技术签证资格',
            ],
            10 =>
            [
                'title'     =>  '您在计划移居的国家/地区获得了多少年全职工作？',
                'answers'   =>
                [
                    1       =>  '0 年',
                    2       =>  '1-3 年',
                    3       =>  '3-5 年',
                    4       =>  '5-8 年',
                    5       =>  '> 8 年',
                ]
            ],
            11 =>
            [
                'title'     =>  '您有目标国家的工作机会吗？',
                'answers'   =>
                [
                    1       =>  '是',
                    2       =>  '否',
                ],
                'remark'    =>  '如果是，则有资格获得技术签证和雇主担保签证',
            ]
        ];
        
        return $questions[$this->_current_lang_index];
    }
    
    protected function toAdminEmail($id = 0) {
        $this->_free_assessment_model = $this->loadModel('free_assessment');
        $target_free_assessment = $this->_free_assessment_model->getByID($id);
        if(!empty($target_free_assessment)) {
            $subject = $this->_today_datetime.' - Free Assessment 免費評估';
            
            $body = '<p style="text-align:center;"><img src="'.$this->_mapping_data['app_url'].'/asset/image/logo-mmi.png" alt="logo"></p>';
            $body.= '<p><hr/></p>';
            
            $total_point = 0;
            $target_age_index = 0;
            $target_country = '';
            $target_country_index = 0;
            
            if(!empty($target_free_assessment['answers'])) {
                foreach ($target_free_assessment['answers'] as $answers_key => $answers) {
                    if(!empty($target_free_assessment['questions'][$answers_key])) {
                        if(is_array($target_free_assessment['questions'][$answers_key]['answers'])) {
                            $body.= '<p>';
                            $body.= '<strong>'.(str_pad($answers_key, 2, '0', STR_PAD_LEFT)).'. '.($target_free_assessment['questions'][$answers_key]['title']).'</strong>';
                            $body.= '<br/>';
                            $body.= ((!empty($target_free_assessment['questions'][$answers_key]['answers'][$answers]))?$target_free_assessment['questions'][$answers_key]['answers'][$answers]:'');
                            $body.= '</p>';
                        }
                        else {
                            $body.= '<p>';
                            $body.= '<strong>'.(str_pad($answers_key, 2, '0', STR_PAD_LEFT)).'. '.($target_free_assessment['questions'][$answers_key]['title']).'</strong>';
                            $body.= '<br/>';
                            $body.= $answers;
                            $body.= '</p>';
                        }
                        switch ($answers_key) {
                            case 2:
                                $target_country_index = $answers;
                                $target_country = ((!empty($target_free_assessment['questions'][$answers_key]['answers'][$answers]))?$target_free_assessment['questions'][$answers_key]['answers'][$answers]:'');
                                break;
                            // What Is Your Education Level? 
                            case 4:
                                if($answers == 1) {
                                    $total_point += 0;  // Secondary
                                }
                                else if($answers == 2) {
                                    $total_point += 10;  // Diploma or Associate Degreee
                                }
                                else if($answers == 3) {
                                    $total_point += 10; // Bachelor
                                }
                                else if($answers == 4) {
                                    $total_point += 15; // Master
                                }
                                else if($answers == 5) {
                                    $total_point += 20; // Doctoral
                                }
                                break;
                            // What Is Your Age?
                            case 5:
                                $target_age_index = $answers;
                                if($answers == 1) {
                                    $total_point += 25;  // 18-25
                                }
                                else if($answers == 2) {
                                    $total_point += 30;  // 25-33
                                }
                                else if($answers == 3) {
                                    $total_point += 25; // 33-40
                                }
                                else if($answers == 4) {
                                    $total_point += 15; // 40-45
                                }
                                else if($answers == 5) {
                                    $total_point += 0; // 45-55
                                }
                                else if($answers == 6) {
                                    $total_point += 0; // >55
                                }
                                break;
                            // If yes, what are the test results?
                            case 7:
                                if($answers == 1) {
                                    $total_point += 0;  // IELTS 6 overall
                                }
                                else if($answers == 2) {
                                    $total_point += 0;  // IELTS 6
                                }
                                else if($answers == 3) {
                                    $total_point += 10; // IELTS 7
                                }
                                else if($answers == 4) {
                                    $total_point += 20; // IELTS 8
                                }
                                else if($answers == 5) {
                                    $total_point += 0; // Don’t have IELTS results yet
                                }
                                break;
                            // How many years full time work experience you got?
                            case 9:
                                if($answers == 1) {
                                    $total_point += 0;  // > 1 year
                                }
                                else if($answers == 2) {
                                    $total_point += 0;  // 1-3 years
                                }
                                else if($answers == 3) {
                                    $total_point += 5; // 3-5 years
                                }
                                else if($answers == 4) {
                                    $total_point += 10; // 5-8 year
                                }
                                else if($answers == 5) {
                                    $total_point += 15; // > 8 years
                                }
                                break;
                            // How Many Years Full Time Work You Gained In The Country That You Are Planning To Move?
                            case 10:
                                if($answers == 1) {
                                    $total_point += 0;  // 0 year
                                }
                                else if($answers == 2) {
                                    $total_point += 5;  // 1-3 years
                                }
                                else if($answers == 3) {
                                    $total_point += 10; // 3-5 years
                                }
                                else if($answers == 4) {
                                    $total_point += 15; // 5-8 year
                                }
                                else if($answers == 5) {
                                    $total_point += 20; // > 8 years
                                }
                                break;
                            // Do You Have A Job Offer From The Target Country?
                            case 11:
                                if($answers == 1) {
                                    $total_point += 20;  // Yes
                                }
                                else if($answers == 2) {
                                    $total_point += 0;  // No
                                }
                        }
                    }
                }
            }
            $body.= '<p><hr/></p>';
            $body.= '<p><strong>'.($this->_page_lang['account.name']).':</strong><br/>'.($target_free_assessment['full_name']).'</p>';
            $body.= '<p><strong>'.($this->_page_lang['account.email']).':</strong><br/>'.($target_free_assessment['email']).'</p>';
            $body.= '<p><strong>'.($this->_page_lang['account.telephone']).':</strong><br/>+'.(preg_replace('/^(\+)(.*)/i', '$2', $target_free_assessment['telephone_code']).' '.$target_free_assessment['telephone_num']).'</p>';
            
            $body.= '<p><hr/></p>';
            if($total_point >= 65) {
                $body.= '<p style="color:red;"><strong>Congratrulations! You may be qualified for the skilled migration program for '.$target_country.'</strong></p>';
            }
            else {
                if($target_age_index > 4 && $target_country_index == 4) {
                    $body.= '<p style="color:red;"><strong>You will not be qualified for the Australia skilled migration program, however you may still be qualified for some other visa categories. Talk to us to seek further help.</strong></p>';
                }
                else {
                    $body.= '<p style="color:red;"><strong>We may need to talk to you further to determine what types of visa would suit you better. Please contact us to seek further help.</strong></p>';
                }
            }
            

            $client_body = '<p style="text-align:center;"><img src="'.$this->_mapping_data['app_url'].'/asset/image/logo-mmi.png" alt="logo"></p>';
            $client_body.= '<p><hr/></p>';
            if($total_point >= 65) {
                $client_body.= '<p>Thanks for completing the free assessment from AI-mmi.com.</p>';
                $client_body.= '<p>&nbsp;</p>';
                $client_body.= '<p>Congratulations! Based on the information you provided, you may be qualified for the skilled or business migration program of your designated country.</p>';
                $client_body.= '<p>&nbsp;</p>';
                $client_body.= '<p>We will contact you for the next steps to assist you with the visa application process, or you may use our AI robotic migration consultant to help with your questions and application.</p>';
                $client_body.= '<p>&nbsp;</p>';
                $client_body.= '<p>Best regards,</p>';
                $client_body.= '<p>AI-mmi.com</p>';
            }
            else {
                if($target_age_index > 4 && $target_country_index == 4) {
                    $client_body.= '<p>Thanks for completing the free assessment from AI-mmi.com.</p>';
                    $client_body.= '<p>&nbsp;</p>';
                    $client_body.= '<p>Based on the information you provided, you will not be qualified for the Australia skilled migration program. However you may still be qualified for some other visa categories. Our migration consultant may contact you shortly by email or phone to assist you with this further.</p>';
                    $client_body.= '<p>&nbsp;</p>';
                    $client_body.= '<p>Best regards,</p>';
                    $client_body.= '<p>AI-mmi.com</p>';
                }
                else {
                    $client_body.= '<p>Thanks for completing the free assessment from AI-mmi.com.</p>';
                    $client_body.= '<p>&nbsp;</p>';
                    $client_body.= '<p>Based on the information you provided, it is unclear if you may be qualified for a migration visa program. We may need to talk to you further to determine what types of visa would suit you better. Our migration consultant may contact you shortly by email or phone.</p>';
                    $client_body.= '<p>&nbsp;</p>';
                    $client_body.= '<p>Best regards,</p>';
                    $client_body.= '<p>AI-mmi.com</p>';
                }
            }

            $recipient_application = $this->_setting_model->getByName('recipient_application');
            $recipient_application = explode(PHP_EOL, str_ireplace(';', PHP_EOL, $recipient_application));
            $this->sendEmail($recipient_application, $subject, $body);
            
            $this->sendEmail($target_free_assessment['email'], $subject, $client_body);
        }
    }
}