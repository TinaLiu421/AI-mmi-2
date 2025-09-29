<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\QueryInput;


class Home extends WebController {
    
    public function index() {
        $home_page_data = $this->loadModel('pages')->getByID(1, $this->_current_lang_index, 
        [
            'media_files' => 
            [
                ['type' => 'page', 'category' => 'banner_'.$this->_current_lang_index],
                ['type' => 'page', 'category' => 'mobile_banner_'.$this->_current_lang_index]
            ]
        ]);
        if(!empty($home_page_data['media_files']['banner_'.$this->_current_lang_index])) { 
            foreach ($home_page_data['media_files']['banner_'.$this->_current_lang_index] as $banner_key => $banner) { 
                $home_page_data['media_files']['banner_'.$this->_current_lang_index][$banner_key]['url'] = $this->generateImage($banner, 1300, 245);
            }
        }
        if(!empty($home_page_data['media_files']['mobile_banner_'.$this->_current_lang_index])) { 
            foreach ($home_page_data['media_files']['mobile_banner_'.$this->_current_lang_index] as $banner_key => $banner) { 
                $home_page_data['media_files']['mobile_banner_'.$this->_current_lang_index][$banner_key]['url'] = $this->generateImage($banner, 800, 800);
            }
        }
        
        // news
        $list_news = $this->loadModel('posts')->getAll( 
        [
            'show_type'         =>  1,
            'show_lang'         =>  $this->_current_lang_index,
            'show_page_size'    =>  10,
            'show_highlight'    =>  1
        ]);

        if(!empty($list_news['data'])) {
            $list_news = $list_news['data'];
            foreach ($list_news as $news_key => $news) {
                if(empty($list_news[$news_key]['title'])) {
                    $list_news[$news_key]['title'] = mb_substr($this->toPlainText($news['content']), 0, 24);
                    if(md5($this->toPlainText(mb_substr($this->toPlainText($news['content']), 0, 24))) != md5($this->toPlainText(mb_substr($this->toPlainText($news['content']), 0, 25)))) {
                        $list_news[$news_key]['title'].= '...';
                    }
                }
                $list_news[$news_key]['url'] = $this->toURL('posts/details/'.$news['id']);
                if(!empty($news['photo'])) { 
                    $list_news[$news_key]['thumbnail'] = $this->generateImage(
                    [
                        'absolute_path' =>  'upload/member_posts/'.$news['photo'],
                        'file_path'     =>  'upload/member_posts/'.$news['photo']
                    ], 480, 320, true);
                }
                else {
                    $list_news[$news_key]['thumbnail'] = $this->generateImage(null, 480, 320, true);
                }
                $list_news[$news_key]['youtube_url'] = $this->getYoutubeEmbedUrl($news['youtube_url']);
            }
        }
        else {
            $list_news = false;
        }

        // events
        $list_events = $this->loadModel('posts')->getAll( 
        [
            'show_type'         =>  2,
            'show_lang'         =>  $this->_current_lang_index,
            'show_page_size'    =>  10,
            'show_highlight'    =>  1
        ]);

        if(!empty($list_events['data'])) {
            $list_events = $list_events['data'];
            foreach ($list_events as $events_key => $events) {
                if(empty($list_events[$events_key]['title'])) {
                    $list_events[$events_key]['title'] = mb_substr($this->toPlainText($events['content']), 0, 24);
                    if(md5($this->toPlainText(mb_substr($this->toPlainText($events['content']), 0, 24))) != md5($this->toPlainText(mb_substr($this->toPlainText($events['content']), 0, 25)))) {
                        $list_events[$events_key]['title'].= '...';
                    }
                }
                $list_events[$events_key]['url'] = $this->toURL('posts/details/'.$events['id']);
                if(!empty($events['photo'])) { 
                    $list_events[$events_key]['thumbnail'] = $this->generateImage(
                    [
                        'absolute_path' =>  'upload/member_posts/'.$events['photo'],
                        'file_path'     =>  'upload/member_posts/'.$events['photo']
                    ], 480, 320, true);
                }
                else {
                    $list_events[$events_key]['thumbnail'] = $this->generateImage(null, 480, 320, true);
                }
                $list_events[$events_key]['youtube_url'] = $this->getYoutubeEmbedUrl($events['youtube_url']);
            }
        }
        else {
            $list_events = false;
        }

        // load view
        $this->pageCss('slick.min', 'asset/lib/slider', false);
        $this->pageScript('slick.min', 'asset/lib/slider', false);
        
        return $this->pageData(
        [
            'details'       =>  $home_page_data,
            'list_news'     =>  $list_news,
            'list_events'   =>  $list_events
        ])->pageView();
    }
    
    public function qrcode() {
        require_once app_path('Libraries/phpqrcode/qrlib.php');
        \QRcode::png(urldecode($this->getParamValue('url')), false, QR_ECLEVEL_L, 16, 2);
        exit();
    }
    
    public function chat($init = 0) {
        // post
        $this->pageAction(function() {
            $question = $this->postParamValue('question');
            $chat_mode = $this->postParamValue('chat_mode', 'immigration'); // default to immigration

            // Save current chat mode to session
            $this->setSession(['current_chat_mode' => $chat_mode]);

            if(!empty($question) && !empty($this->_current_member)) {
                $can_do_reply = true;
                if(!empty($this->_current_member['expiration_ai_level'])) {
                    if($this->_current_member['expiration_ai_level'] == 1) {
                        $can_do_reply = false;
                    }
                    else if($this->_current_member['total_ask_question'] >= 3) {
                        $can_do_reply = false;
                    }
                }
                
                if($can_do_reply) {
                    $new_reply = '';
                    //$new_reply = $this->callDialogflowApi($this->postParamValue('question', ''));
                    if(empty($new_reply) || $this->toPlainText(strtolower($new_reply)) == 'unknown') {
                        $enhanced_question = $this->buildModeSpecificPrompt($this->postParamValue('question', ''), $chat_mode);
                        $new_reply = $this->callGeminiApi($enhanced_question);
                        //$new_reply = $this->callChatgptApi($this->postParamValue('question', ''));
                    }
                    
                    $new_chatlog = [
                        'member_id'     =>  $this->_current_member['id'],
                        'target_date'   =>  date('Ymd', strtotime($this->_today_date)),
                        'type'          =>  'ask',
                        'content'       =>  $this->postParamValue('question', ''),
                        'reply'         =>  $new_reply,
                        'chat_mode'     =>  $chat_mode,
                    ];
                    $this->loadModel('chatlog')->doSave($new_chatlog);
                    
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
                    $ai_owner_name = 'AI-mmi';
                    $ai_owner_avatar = 'asset/image/logo-mmi.png';
                    
                    $this->pageResult(
                    [
                        'status'    =>  200,
                        'content'   =>  $new_chatlog['content'],
                        'reply'     =>  nl2br($new_chatlog['reply']),
                        'chat_mode' =>  $chat_mode,
                        'member_owner_name' => $member_owner_name,
                        'member_owner_avatar' => $member_owner_avatar,
                        'ai_owner_name' => $ai_owner_name,
                        'ai_owner_avatar' => $ai_owner_avatar,
                    ]);
                }
                else {
                    $this->pageResult(
                    [
                        'status'    =>  403,
                        'message'   =>  $this->_page_lang['please_renew_ai'],
                        'url'       =>  $this->toURL('account_submission')
                    ]);
                }
            }
            else {
                $this->pageResult(
                [
                    'status'    =>  403,
                    'message'   =>  $this->_page_lang['please_login'],
                    'url'       =>  $this->toURL('account_login')
                ]);
            }
        });
        
        // Get current chat mode (from GET parameter, session, or default)
        $current_chat_mode = $this->getParamValue('chat_mode');
        if(empty($current_chat_mode)) {
            $current_chat_mode = $this->getSession('current_chat_mode');
        }
        if(empty($current_chat_mode)) {
            $current_chat_mode = 'immigration'; // default mode
        }

        $max_date_int = $this->getSession('max_chat_date_int');
        if(!empty($init)) {
            $max_date_int = '';
        }
        $chat_message = [];
        if(!empty($this->_current_member)) {
            $chat_message = $this->loadModel('chatlog')->getAll($this->_current_member['id'], $max_date_int, $current_chat_mode);
            if(!empty($chat_message)) {
                foreach ($chat_message as $message_key => $message) { 
                    if(strtolower($message['type']) == 'ask') {
                        $chat_message[$message_key]['owner_name'] = $this->_current_member['alias_name'];
                        $chat_message[$message_key]['owner_avatar'] = 'asset/image/icon-member.png';
                        if(!empty($this->_current_member['avatar'])) {
                            if(file_exists('upload/member_avatar/'.$this->_current_member['avatar'])) {
                                $chat_message[$message_key]['owner_avatar'] = 'upload/member_avatar/'.$this->_current_member['avatar'];
                            }
                            else {
                                $chat_message[$message_key]['owner_avatar'] = 'upload/member_logo/'.$this->_current_member['avatar'];
                            }
                        }
                    }
                    else {
                        $chat_message[$message_key]['owner_name'] = 'AI-mmi';
                        $chat_message[$message_key]['owner_avatar'] = 'asset/image/logo-mmi.png';
                    }
                    $chat_message[$message_key]['content'] = nl2br($message['content']);
                    $chat_message[$message_key]['chat_mode'] = isset($message['chat_mode']) ? $message['chat_mode'] : 'immigration';
                    $max_date_int = $message['target_date'];
                }
                $this->setSession(['max_chat_date_int' => $max_date_int]);
            }
        }
        echo json_encode($chat_message);
    }
    
    protected function callDialogflowApi($query = '') {
        $result_answer = '';
        if(!empty($query)) {
        
            // Authentication credentials path
            $credentialsPath = storage_path('google-credentials.json');

            // Create SessionsClient instance
            $sessionsClient = new SessionsClient(['credentials' => $credentialsPath]);

            // Dialogflow project ID
            $projectId = 'ai-mmi-chat-elgf';

            // Session ID can be any string you define
            $sessionId = uniqid();

            // Specify your language code
            $languageCode = 'en-US';

            // Assemble session name
            $session = $sessionsClient->sessionName($projectId, $sessionId);

            // Create QueryInput instance
            $textInput = (new TextInput())
                ->setText($query)
                ->setLanguageCode($languageCode);

            // set QueryInput
            $queryInput = (new QueryInput())
                ->setText($textInput);

            // Send a request and get a response
            $response = $sessionsClient->detectIntent($session, $queryInput);

            // Parse response
            $queryResult = $response->getQueryResult();
            $result_answer = $queryResult->getFulfillmentText();

            // Close the SessionsClient instance
            $sessionsClient->close();
        }
        
        return $result_answer;
    }

    protected function callGeminiApi($query = '') {
        // Request URL
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=AIzaSyCAH31vTsmetLcAmkKiWteEuviLFTfm-F8';

        // Request data
        $data = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $query
                        )
                    )
                )
            )
        );

        // Convert data to JSON format
        $jsonData = json_encode($data);

        // Set request header
        $headers = array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        );

        // Initialize cURL
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute cURL request and get response
        $response = curl_exec($ch);

        // Check if an error occurred
        if(curl_errno($ch)){
            //echo 'Curl error: ' . curl_error($ch);
            return '';
        }

        // Close cURL resource
        curl_close($ch);
        
        
        $result_answer = '';
        $response = (array)json_decode($response, true);

        if(!empty($response['error'])) {
            $result_answer = (!empty($response['error']['message']))?$response['error']['message']:'';
        }
        else if(!empty($response['candidates'])) {
            if(!empty($response['candidates'][0])) {
                if(!empty($response['candidates'][0]['content'])) {
                    if(!empty($response['candidates'][0]['content']['parts'])) {
                        if(!empty($response['candidates'][0]['content']['parts'][0])) {
                            if(!empty($response['candidates'][0]['content']['parts'][0]['text'])) {
                                $result_answer = $response['candidates'][0]['content']['parts'][0]['text'];
                            }
                        }
                    }
                }
            }
        }
  
        return $result_answer;
    }
    
    protected function callChatgptApi($query = '') {
        $result_answer = '';
        if(!empty($query)) {
            // add your code here

        }

        return $result_answer;
    }

    protected function buildModeSpecificPrompt($question, $mode) {
        $system_prompts = [
            'immigration' => "You are an expert immigration and migration consultant specializing in Australian immigration law and visa processes. You help people understand visa requirements, migration pathways, and provide accurate, up-to-date immigration advice. Always provide specific, actionable guidance about visa options, requirements, and processes. Be professional and empathetic in your responses.",
            'study' => "You are a study abroad and education consultant specializing in international education opportunities, particularly in Australia. You help students find suitable courses, understand admission requirements, compare educational institutions, and navigate the student visa process. Provide comprehensive guidance about study options, costs, and academic pathways."
        ];

        $context_prompt = isset($system_prompts[$mode]) ? $system_prompts[$mode] : $system_prompts['immigration'];

        return $context_prompt . "\n\nUser Question: " . $question . "\n\nPlease provide a helpful, accurate response:";
    }

}