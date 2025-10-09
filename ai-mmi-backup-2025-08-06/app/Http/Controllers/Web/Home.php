<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\QueryInput;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; 


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

                // Check subscription-based access
                $has_migration_sub = !empty($this->_current_member['has_migration_subscription']);
                $has_education_sub = !empty($this->_current_member['has_education_subscription']);

                // If user has active subscription for the chat mode, allow unlimited access
                if ($chat_mode === 'immigration' && $has_migration_sub) {
                    $can_do_reply = true;
                } else if ($chat_mode === 'study' && $has_education_sub) {
                    $can_do_reply = true;
                } else if ($chat_mode === 'study') {
                    // Study chat is always free/unlimited
                    $can_do_reply = true;
                } else {
                    // For free users on immigration chat, check old expiration logic
                    if(!empty($this->_current_member['expiration_ai_level'])) {
                        if($this->_current_member['expiration_ai_level'] == 1) {
                            $can_do_reply = false;
                        }
                        else if($this->_current_member['total_ask_question'] >= 3) {
                            $can_do_reply = false;
                        }
                    }
                }

                if($can_do_reply) {
                    $new_reply = '';
                    //$new_reply = $this->callDialogflowApi($this->postParamValue('question', ''));
                    if(empty($new_reply) || $this->toPlainText(strtolower($new_reply)) == 'unknown') {
                        $rawQuestion = $this->postParamValue('question', '');

                        // ① 从 Free Assessment 构建画像文本（新的 JSON 解析方式）
                        $fa_ctx = $this->buildFAContext($this->_current_member['id']);

                        // ② 调用 Gemini API，并将画像作为上下文
                        $new_reply = $this->callGeminiApi($rawQuestion, $chat_mode);
                    }
                    
                    try {
                        DB::table('chat_log')->insert([
                            'member_id'   => $this->_current_member['id'],
                            'target_date' => (int)date('Ymd', strtotime($this->_today_date)),
                            'type'        => 'ask',                 
                            'content'     => $rawQuestion,
                            'chat_mode'   => $chat_mode,
                            'status'      => 1,
                            'created_at'  => Carbon::now('UTC'),
                            'updated_at'  => Carbon::now('UTC'),
                        ]);

                        DB::table('chat_log')->insert([
                            'member_id'   => $this->_current_member['id'],
                            'target_date' => (int)date('Ymd', strtotime($this->_today_date)),
                            'type'        => 'reply',
                            'content'     => $new_reply,
                            'chat_mode'   => $chat_mode,
                            'status'      => 1,
                            'created_at'  => Carbon::now('UTC'),
                            'updated_at'  => Carbon::now('UTC'),
                        ]);
                    } catch (\Throwable $e) {
                        
                    }
                    
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

                    $nowUtcUser  = \Carbon\Carbon::now('UTC')->toIso8601String();
                    $nowUtcReply = \Carbon\Carbon::now('UTC')->toIso8601String();
                    
                    $this->pageResult([
                        'status'    => 200,
                        'content'   => nl2br($rawQuestion),
                        'reply'     => nl2br($new_reply),
                        'chat_mode' => $chat_mode,

                        'content_created_at' => $nowUtcUser,   
                        'reply_created_at'   => $nowUtcReply,

                        'member_owner_name'   => $this->_current_member['alias_name'],
                        'member_owner_avatar' => !empty($this->_current_member['avatar'])
                            ? (file_exists('upload/member_avatar/'.$this->_current_member['avatar'])
                                ? 'upload/member_avatar/'.$this->_current_member['avatar']
                                : 'upload/member_logo/'.$this->_current_member['avatar'])
                            : 'asset/image/icon-member.png',
                        'ai_owner_name'       => 'AI-mmi',
                        'ai_owner_avatar'     => 'asset/image/logo-mmi.png',
                    ]);
                }
                else {
                    $this->pageResult(
                    [
                        'status'    =>  403,
                        'message'   =>  $this->_page_lang['please_renew_ai'],
                        'url'       =>  $this->toURL('upgrade')
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
                            } else {
                                $chat_message[$message_key]['owner_avatar'] = 'upload/member_logo/'.$this->_current_member['avatar'];
                            }
                        }
                    } else {
                        $chat_message[$message_key]['owner_name'] = 'AI-mmi';
                        $chat_message[$message_key]['owner_avatar'] = 'asset/image/logo-mmi.png';
                    }

                    $chat_message[$message_key]['content'] = nl2br($message['content']);
                    $chat_message[$message_key]['chat_mode'] = isset($message['chat_mode']) ? $message['chat_mode'] : 'immigration';
                    $max_date_int = $message['target_date'];

                    $chat_message[$message_key]['created_time'] =
                        !empty($message['created_at'])
                            ? \Carbon\Carbon::parse($message['created_at'], 'UTC')->toIso8601String()
                            : null;
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

    protected function callGeminiApi($question = '', $chat_mode = 'immigration') {
        if (empty($question)) return '';

        // 1) Current User
        $member = $this->_current_member;
        if (empty($member)) return 'Please login first.';

        // ➕ 读取 Free Assessment 画像
        $fa_ctx = $this->buildFAContext($member['id']);

        // 2) Retrieve the most recent 10 rounds (20 entries) of historical data, sorted in ascending order by time.
        $history = DB::table('chat_log')
            ->where('member_id', $member['id'])
            ->where('chat_mode', $chat_mode)
            ->orderBy('id', 'desc')
            ->limit(20)                      
            ->get()
            ->reverse();

        $contents = [];
        foreach ($history as $msg) {
            $t    = strtolower($msg->type ?? '');
            $role = ($t === 'ai') ? 'model' : 'user';   
            $text = (string)($msg->content ?? '');
            if ($text === '') continue;
            if (mb_strlen($text) > 2000) {              
                $text = mb_substr($text, 0, 2000) . '...';
            }
            $contents[] = ['role' => $role, 'parts' => [['text' => $text]]];
        }

        // 3) Add to current issue
        $contents[] = ['role' => 'user', 'parts' => [['text' => $question]]];

        // 4)  Send Request
        $apiKey = env('GEMINI_API_KEY');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

        $system = $this->buildModeSpecificPrompt($chat_mode);

        if (!empty($fa_ctx)) {
            $system .= "\n\n[User Profile from Free Assessment]\n{$fa_ctx}\n"
                    . "Please incorporate the above information into your response. If any part conflicts with policy,
                     the policy shall prevail, and you must indicate the key information that needs to be supplemented.";
        } else {
            // 没有画像就提醒模型给“通用答案+需要补充哪些信息”
            $system .= "\n\n(No Free Assessment image. Please provide general recommendations 
            first and list the key information that needs to be supplemented.)";
        }

        $body = [
            'systemInstruction' => [
            'parts' => [['text' => $system]],
            ],

            'contents' => $contents,
            'generationConfig' => [
                        'temperature'       => 0.9,
                        'maxOutputTokens'   => 2048,   
                        'topK'              => 40,
                        'topP'              => 0.95,
                        'candidateCount'    => 1,
                        
                        'responseMimeType'  => 'text/plain',
                    ],
        ];

        $jsonData = json_encode($body, JSON_UNESCAPED_UNICODE);

        $headers  = [
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $resp = curl_exec($ch);
        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            return '[Error] ' . $err;
        }
        curl_close($ch);

        $data = json_decode($resp, true);
        if (isset($data['error'])) {
            return '[Upstream Error] ' . ($data['error']['message'] ?? 'Unknown error');
        }

        $answer = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if ($answer === '') {
            $answer = 'Sorry, I could not generate a response this time.';
        } else {
            // Remove Markdown symbols and retain plain text.
            $answer = $this->stripMarkdown($answer);
        }
        return $answer;
    }

    
    protected function callChatgptApi($query = '') {
        $result_answer = '';
        if(!empty($query)) {
            // add your code here

        }

        return $result_answer;
    }

    protected function buildModeSpecificPrompt($mode) {
        if ($mode === 'immigration') {
            return <<<PROMPT
    You are AI-mmi, an international migration and visa expert specializing in Australia, the United Kingdom (UK), Canada, and the United States (USA). 
    You provide unlimited migration and visa consultation and application assistance for these countries.

    Your goals:
    1. Analyse the user's situation (education, work experience, nationality, and goals).
    2. Recommend the most suitable visa pathways for Australia, the UK, Canada, or the USA.
    3. Explain visa categories, requirements, eligibility, skill assessments, points tests, sponsor options, and family inclusion.
    4. Provide application steps, document checklists, fees, and timelines.
    5. Clarify differences between visa subclasses or programs (e.g., 485 vs 482, UK Skilled Worker vs Graduate Route, Canada PR vs Study Visa, US H1B vs EB visas).
    6. If relevant, guide the user toward education-to-PR or work-to-PR pathways.

    Tone and style:
    - Professional, helpful, and structured (use headings and bullet points).
    - Reply in the user's language if identifiable; otherwise use English.
    - Always stay factual. If unsure, say "based on publicly available information" and suggest verifying via official government sources.
    - Never refuse migration or visa-related questions unless they are outside Australia, UK, Canada, or USA.

    PROMPT;
        }

        // study mode
        return <<<PROMPT
    You are AI-mmi, a global education advisor focused on helping users with studying abroad in Australia, the UK, Canada, and the USA.

    You provide unlimited chats for questions related to education and school/university applications only.

    Allowed topics:
    - Choosing a study destination, comparing countries (Australia / UK / Canada / USA).
    - Entry requirements, tuition fees, scholarships, and application timelines.
    - Preparing documents: SOP, transcripts, CVs, recommendation letters, and portfolios.
    - How to apply through portals (UCAS, CommonApp, university portals, etc.).
    - Course selection, ranking comparisons, and accommodation guidance.

    Out of scope:
    - Migration, work visas, PR pathways, employer sponsorship, or non-study visa advice.
    If asked such questions, politely say:
    "This study assistant only handles education and school/university application questions.
    For migration or visa strategy, please switch to the Immigration assistant."

    Tone and style:
    - Clear, concise, and friendly.
    - Give practical, step-by-step checklists where possible.
    - Reply in the user's language if obvious; otherwise use English.
    PROMPT;
    }

    protected function stripMarkdown($text) {
        if ($text === '' || $text === null) return '';

        $text = preg_replace('/!\[([^\]]*)\]\([^)]+\)/', '$1', $text);   // ![alt](url) -> alt
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $text);       // [label](url) -> label

        $text = preg_replace('/\*\*(.*?)\*\*/s', '$1', $text);           // **bold** -> bold
        $text = preg_replace('/\*(.*?)\*/s', '$1', $text);               // *italic* -> italic
        $text = preg_replace('/__(.*?)__/s', '$1', $text);               // __bold__ -> bold
        $text = preg_replace('/_(.*?)_/s', '$1', $text);                 // _italic_ -> italic
        $text = preg_replace('/`{1,3}(.*?)`{1,3}/s', '$1', $text);       // `code` OR ```code``` -> code

        $text = preg_replace('/^#{1,6}\s*/m', '', $text);                // # H -> H
        $text = preg_replace('/^\s*>\s?/m', '', $text);                   // > quote -> quote
        $text = preg_replace('/^\s*(-{3,}|\*{3,}|_{3,})\s*$/m', '', $text); // ---/***/___ 


        $text = preg_replace("/\r\n|\r/", "\n", $text);                 
        $text = preg_replace("/\n{3,}/", "\n\n", $text);              
        return trim($text);
    }

    public function fa_me() {
        if (empty($this->_current_member)) {
            return response()->json(['has_profile' => false]);
        }
        $mid = $this->_current_member['id'];
        $fa = \DB::table('free_assessment')
            ->where(function($q) use ($mid) {
                $q->where('member_id', $mid);
                // ->orWhere('user_id', $mid)
                // ->orWhere('uid', $mid);
            })
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->first();

        if (!$fa) {
            return response()->json(['has_profile' => false]);
        }
        return response()->json([
            'has_profile' => true,
            // 前端现在只要判断有/无，不回大量隐私字段
            // 若要预填/展示摘要，可再挑字段返回
        ]);
    }

    private function buildFAContext($memberId): string
    {
        $row = DB::table('free_assessment')
            ->select('questions','answers')
            ->where(function($q) use ($memberId) {
                $q->where('member_id', $memberId);
                // ->orWhere('user_id', $memberId)
                // ->orWhere('uid', $memberId);
            })
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->first();

        if (!$row) return '';

        // 解析 JSON
        $questions = json_decode($row->questions ?? '[]', true) ?: [];
        $answers   = json_decode($row->answers   ?? '[]', true) ?: [];

        // 工具：把“题号的答案编号/自由文本”映射成可读字符串
        $pick = function(string $qNo) use ($questions, $answers) {
            if (!array_key_exists($qNo, $answers)) return null;
            $ansKey = (string)$answers[$qNo];

            if (isset($questions[$qNo]['answers']) && is_array($questions[$qNo]['answers']) &&
                array_key_exists($ansKey, $questions[$qNo]['answers'])) {
                $text = (string)$questions[$qNo]['answers'][$ansKey];
            } else {
                // 自由文本（如专业/职业）
                $text = (string)$answers[$qNo];
            }
            // 还原 &gt; 等 HTML 实体
            return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        };

        // 按你题号取值（可根据实际题库增减）
        $agreeAssess   = $pick('1');  // Yes/No
        $country       = $pick('2');  // Britain/United States/Canada/Australia
        $visaType      = $pick('3');  // Skilled/Student/Family/Employer sponsorship...
        $education     = $pick('4');  // Secondary/Diploma/Bachelor/Master/Doctoral
        $age           = $pick('5');  // 18-25/25-33/...
        $hasEnglish    = $pick('6');  // Yes/No
        $ieltsResult   = $pick('7');  // IELTS 6/7/8/No result
        $occupation    = $pick('8');  // 自由文本，如 “software engineer”
        $workYears     = $pick('9');  // >1 / 1-3 / 3-5 / ...
        $localYears    = $pick('10'); // 目标国家本地工作年限
        $hasOffer      = $pick('11'); // Yes/No

        // 组装对回答有用的“画像事实”
        $parts = [];
        if ($country)     $parts[] = "目标国家: {$country}";
        if ($visaType)    $parts[] = "意向签证类型: {$visaType}";
        if ($education)   $parts[] = "最高学历: {$education}";
        if ($age)         $parts[] = "年龄段: {$age}";
        if ($hasEnglish)  $parts[] = "是否完成英语考试: {$hasEnglish}";
        if ($ieltsResult) $parts[] = "英语成绩/档位: {$ieltsResult}";
        if ($occupation)  $parts[] = "职业(ANZSCO 方向): {$occupation}";
        if ($workYears)   $parts[] = "累计全职工作年限: {$workYears}";
        if ($localYears)  $parts[] = "目标国本地工作年限: {$localYears}";
        if ($hasOffer)    $parts[] = "是否拥有目标国 job offer: {$hasOffer}";

        // 可选：给模型的判断提示（不替代政策判断）
        $hints = [];
        if ($visaType && stripos($visaType, 'Student') !== false) {
            $hints[] = '用户倾向学生签路径；请结合专业、学制与毕业后路径（各国毕业工签/485/PSW）。';
        }
        if ($visaType && stripos($visaType, 'Skilled') !== false) {
            $hints[] = '用户关注技术移民；请结合年龄、学历、英语档位与工作年限做初筛。';
        }
        if ($hasOffer === 'Yes') {
            $hints[] = '已有 job offer；请优先评估雇主担保/工签路径。';
        }
        if ($education && (stripos($education, 'Secondary') !== false || stripos($education, 'Diploma') !== false)) {
            $hints[] = '学历低于本科，部分技术移民可能受限；请说明替代路径或补救方案。';
        }
        if ($hasEnglish === 'No') {
            $hints[] = '暂无英语成绩；请提示目标路径的最低英语要求与过渡方案。';
        }

        // 可选：缺口提示，便于模型在答案中提示还需哪些信息
        $missing = [];
        if (!$country)   $missing[] = '目标国家';
        if (!$visaType)  $missing[] = '意向签证类型';
        if (!$education) $missing[] = '最高学历';
        if (!$hasEnglish)$missing[] = '英语考试是否完成';
        if (!$occupation)$missing[] = '职业/工种';
        if (!$workYears) $missing[] = '累计工作年限';

        // 拼装输出
        $ctx = '';
        if ($parts)  $ctx .= implode("\n", $parts);
        if ($hints)  $ctx .= "\n【系统提示】\n" . implode("\n", $hints);
        if ($missing) $ctx .= "\n【缺少关键信息】" . implode('、', $missing) . "。";

        return trim($ctx);
    }

}