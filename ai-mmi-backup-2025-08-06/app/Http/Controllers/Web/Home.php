<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\QueryInput;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\ConversationFlowService;

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
    
    public function chat($init = 0)
    {
        // 1) 处理 POST：写入一轮对话并返回答案
        $this->pageAction(function () {

            // —— 取参数（兼容多命名）——
            $question = $this->postParamValue('question');
            if ($question === null || $question === '') {
                $question = request()->input('question', request()->get('question', ''));
                if ($question === '' || $question === null) {
                    $question = request()->input('ask', request()->get('ask', ''));
                }
            }
            $useRag   = (int)($this->postParamValue('use_rag', request()->input('use_rag', 0)));
            $override = (string)$this->postParamValue('override_reply', request()->input('override_reply', ''));

            // —— 登录校验 —— 
            if (empty($this->_current_member)) {
                $this->pageResult([
                    'status'  => 403,
                    'message' => $this->_page_lang['please_login'],
                    'url'     => $this->toURL('account_login'),
                ]);
                return;
            }

            // —— 基本校验 —— 
            $rawQuestion = trim((string)$question);
            if ($rawQuestion === '') {
                $this->pageResult([
                    'status'  => 400,
                    'message' => 'Please enter a question.',
                ]);
                return;
            }

            // —— 订阅信息（现阶段聊天无限制，但留接口）——
            $has_migration_sub   = !empty($this->_current_member['has_migration_subscription']);
            $has_education_sub   = !empty($this->_current_member['has_education_subscription']);
            $has_subscription    = $has_migration_sub || $has_education_sub;

            // —— 先 RAG，后回落 Gemini —— 
            $new_reply   = '';
            $replySource = 'model';
            $aiOwnerName = 'AI-mmi';
            $aiOwnerAvatar = 'asset/image/logo-mmi.png';

            // ❶ 前端已命中 RAG：直接用（保留 Markdown）
            if ($useRag === 1 && trim($override) !== '') {
                $new_reply    = trim($override);
                $replySource  = 'rag-override';
                $aiOwnerName  = 'AI-mmi (Policy)';
                \Log::info('CHAT FLOW', ['case' => 'RAG override used']);
            }

            // ❷ use_rag=1 但没带文本 → 后端再请求一次 RAG
            if ($new_reply === '' && $useRag === 1) {
                $rag = $this->callRagApi($rawQuestion);
                if (is_string($rag) && trim($rag) !== '') {
                    $new_reply    = trim($rag);         // 仍保留 Markdown
                    $replySource  = 'rag-api';
                    $aiOwnerName  = 'AI-mmi (Policy)';
                    \Log::info('CHAT FLOW', ['case' => 'RAG API used']);
                }
            }

            // ❸ 上述都失败 → 回落 Gemini（去 Markdown，返回纯文本）
            if ($new_reply === '') {
                $new_reply    = $this->callGeminiApi($rawQuestion, $has_subscription);
                $replySource  = 'model';
                $aiOwnerName  = 'AI-mmi';
                // 这里 callGeminiApi 内部已做 stripMarkdown
                \Log::info('CHAT FLOW', ['case' => 'Model generated']);
            }

            // —— 入库：ask / reply（同 related_id）——
            try {
                $nowUtc     = \Carbon\Carbon::now('UTC');
                $targetDate = (int)date('Ymd', strtotime($this->_today_date));

                \DB::beginTransaction();

                // ask
                $askId = \DB::table('chat_log')->insertGetId([
                    'member_id'   => $this->_current_member['id'],
                    'related_id'  => 0,
                    'target_date' => $targetDate,
                    'type'        => 'ask',
                    'content'     => $rawQuestion,      // 原文
                    'status'      => 1,
                    'created_at'  => $nowUtc,
                    'updated_at'  => $nowUtc,
                ]);
                \DB::table('chat_log')->where('id', $askId)->update(['related_id' => $askId]);

                // reply（RAG保留Markdown，Gemini已纯文本）
                \DB::table('chat_log')->insertGetId([
                    'member_id'   => $this->_current_member['id'],
                    'related_id'  => $askId,
                    'target_date' => $targetDate,
                    'type'        => 'reply',
                    'content'     => $new_reply,
                    'status'      => 1,
                    'created_at'  => $nowUtc,
                    'updated_at'  => $nowUtc,
                ]);

                \DB::commit();
            } catch (\Throwable $e) {
                \DB::rollBack();
                \Log::error('CHAT DB INSERT FAIL: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }

            // —— 构造头像/昵称 —— 
            $member_owner_name   = $this->_current_member['alias_name'];
            $member_owner_avatar = 'asset/image/icon-member.png';
            if (!empty($this->_current_member['avatar'])) {
                $member_owner_avatar = file_exists('upload/member_avatar/'.$this->_current_member['avatar'])
                    ? 'upload/member_avatar/'.$this->_current_member['avatar']
                    : 'upload/member_logo/'.$this->_current_member['avatar'];
            }

            // —— ConversationFlow 可选（保底不中断）
            $flowPrompt = null;
            try {
                $flowService = new \App\Services\ConversationFlowService($this->_current_member['id']);
                $userProfile = [
                    'has_subscription'  => $has_subscription,
                    'subscription_tier' => $this->_current_member['primary_plan_code'] ?? 'free',
                ];
                $flowResp   = $flowService->analyzeAndTrigger($rawQuestion, $new_reply, $userProfile);
                $flowPrompt = $flowResp ? $flowService->formatForFrontend($flowResp) : null;
            } catch (\Throwable $e) {
                \Log::error('Flow service error: '.$e->getMessage());
            }

            // —— 只返回一次，结构与前端一致 —— 
            $nowUtcIso = \Carbon\Carbon::now('UTC')->toIso8601String();
            $this->pageResult([
                'status'               => 200,
                'content'              => nl2br($rawQuestion), // 仅用户文本 nl2br
                'reply'                => $new_reply,          // RAG: markdown; Gemini: 纯文本
                'content_created_at'   => $nowUtcIso,
                'reply_created_at'     => $nowUtcIso,

                'member_owner_name'    => $member_owner_name,
                'member_owner_avatar'  => $member_owner_avatar,
                'ai_owner_name'        => $aiOwnerName,
                'ai_owner_avatar'      => $aiOwnerAvatar,

                'reply_source'         => $replySource,
                'flow_prompt'          => $flowPrompt,
            ]);
            return;
        });

        if (request()->isMethod('post')) {
            return;
        }

        // 2) 处理 GET：拉取历史（按日期分组）
        $max_date_int = $this->getSession('max_chat_date_int');
        if (!empty($init)) {
            $max_date_int = '';
        }

        $chat_message = [];
        if (!empty($this->_current_member)) {
            $chat_message = $this->loadModel('chatlog')->getAll($this->_current_member['id'], $max_date_int);
            if (!empty($chat_message)) {
                foreach ($chat_message as $k => $m) {
                    // 归属信息
                    if (strtolower($m['type']) === 'ask') {
                        $chat_message[$k]['owner_name'] = $this->_current_member['alias_name'];
                        $chat_message[$k]['owner_avatar'] = 'asset/image/icon-member.png';
                        if (!empty($this->_current_member['avatar'])) {
                            $chat_message[$k]['owner_avatar'] = file_exists('upload/member_avatar/'.$this->_current_member['avatar'])
                                ? 'upload/member_avatar/'.$this->_current_member['avatar']
                                : 'upload/member_logo/'.$this->_current_member['avatar'];
                        }
                    } else {
                        $chat_message[$k]['owner_name']   = 'AI-mmi';
                        $chat_message[$k]['owner_avatar'] = 'asset/image/logo-mmi.png';
                    }

                    // 历史内容仍旧 nl2br（保留与你前端一致的展示）
                    $chat_message[$k]['content'] = nl2br($m['content']);
                    $max_date_int = $m['target_date'];

                    $chat_message[$k]['created_time'] =
                        !empty($m['created_at'])
                            ? \Carbon\Carbon::parse($m['created_at'], 'UTC')->toIso8601String()
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

    protected function callGeminiApi($question = '', $has_subscription = false) {
        if (empty($question)) return '';

        // 1) Current User
        $member = $this->_current_member;
        if (empty($member)) return 'Please login first.';

        // 2) Retrieve the most recent 10 rounds (20 entries) of historical data, sorted in ascending order by time.
        $history = DB::table('chat_log')
            ->where('member_id', $member['id'])
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get()
            ->reverse();

        $contents = [];
        foreach ($history as $msg) {
            $t    = strtolower($msg->type ?? '');
            // Fix: chat_log stores 'reply' for AI messages, not 'ai'
            $role = ($t === 'reply') ? 'model' : 'user';
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
        // Using gemini-2.0-flash-exp (with retry logic to handle overload)
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key={$apiKey}";

        $system = $this->buildUnifiedPrompt($has_subscription);

        // Add subscription-based guidance
        if (!$has_subscription) {
            $system .= "\n\n**IMPORTANT - FREE TIER USER:**\n"
                    . "This user is on the FREE tier. You must:\n"
                    . "- Provide ONLY general guidance and overview information\n"
                    . "- DO NOT give specific case advice or detailed step-by-step instructions\n"
                    . "- Keep answers broad and educational\n"
                    . "- For specific questions, acknowledge them but explain that detailed advice requires Premium subscription\n"
                    . "- Example: 'For general guidance, [broad answer]. For detailed case-specific advice including document checklists and strategies, Premium members get access to Migration Agents.'\n";
        } else {
            $system .= "\n\n**PREMIUM SUBSCRIBER:**\n"
                    . "This user has an active subscription. You can:\n"
                    . "- Provide detailed, specific advice\n"
                    . "- Give step-by-step instructions\n"
                    . "- Offer case-specific strategies\n"
                    . "- Be as thorough and specific as needed\n";
        }

        $body = [
            'systemInstruction' => [
            'parts' => [['text' => $system]],
            ],

            'contents' => $contents,
            'generationConfig' => [
                        'temperature'       => 0.9,   // More creative/conversational
                        'maxOutputTokens'   => 400,   // Enough for complete short answers (increased from 200)
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

        // Add retry logic for overloaded API
        $maxRetries = 3; // Increased from 2 to 3
        $retryDelay = 3; // Increased from 2 to 3 seconds
        $resp = null;
        $data = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $resp = curl_exec($ch);

            if (curl_errno($ch)) {
                $err = curl_error($ch);
                curl_close($ch);
                \Log::error('Gemini CURL Error: ' . $err);
                return '[Error] ' . $err;
            }

            $data = json_decode($resp, true);

            // Check if API is overloaded (503 error)
            if (isset($data['error']) && $data['error']['code'] === 503) {
                \Log::warning("Gemini API overloaded (attempt {$attempt}/{$maxRetries}): " . json_encode($data['error']));

                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                    continue; // Retry
                } else {
                    curl_close($ch);
                    return 'The AI service is currently busy. Please try again in a few moments.';
                }
            }

            // If no 503 error, break out of retry loop
            break;
        }

        curl_close($ch);

        // Log raw response for debugging
        \Log::info('Gemini API Response: ' . substr($resp, 0, 500));

        if (isset($data['error'])) {
            \Log::error('Gemini API Error: ' . json_encode($data['error']));
            return '[Upstream Error] ' . ($data['error']['message'] ?? 'Unknown error');
        }

        // Check if response structure is valid
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            \Log::error('Gemini unexpected response structure: ' . json_encode($data));
            return 'Sorry, I received an unexpected response format. Please try again.';
        }

        $answer = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if ($answer === '') {
            \Log::error('Gemini returned empty text');
            return 'Sorry, I could not generate a response this time.';
        }

        // Remove Markdown symbols and retain plain text.
        $answer = $this->stripMarkdown($answer);
        return $answer;
    }

    
    protected function callChatgptApi($query = '') {
        $result_answer = '';
        if(!empty($query)) {
            // add your code here

        }

        return $result_answer;
    }

    protected function buildUnifiedPrompt($has_subscription = false) {
        return <<<PROMPT
    You are AI-mmi, a friendly migration and study abroad advisor having natural conversations about moving to or studying in Australia, UK, Canada, or USA.

    CONVERSATION STYLE (VERY IMPORTANT):
    - Talk like a real person, not a knowledge base
    - Give SHORT answers (2-3 sentences max)
    - Ask ONE follow-up question to understand their situation better
    - Don't dump all information at once - let the conversation flow naturally
    - Use conversational language: "Let me help you with that", "That's a great question", "I'd need to know a bit more"

    RESPONSE FORMAT (CRITICAL):
    1. Brief answer to their question (2-3 sentences)
    2. ONE clarifying question OR offer to explain more

    TOPICS I CAN HELP WITH:
    - Migration/Immigration: Visas, permanent residence, work permits, skilled migration, family sponsorship
    - Study Abroad: Universities, courses, requirements, application process, scholarships
    - Both: Student visas, post-study work visas, pathways from study to PR

    ASK QUESTIONS TO UNDERSTAND:
    - If they ask about visas: Ask about their current status (student/worker/etc)
    - If they ask about studying: Ask their field of interest and budget
    - If they ask about points: Ask their age, English level, work experience
    - If they ask about universities: Ask their academic background and goals
    - If unclear: Ask them to clarify before giving detailed answer

    WHEN TO GIVE MORE DETAILS:
    Only when user explicitly says "tell me more", "give me details", "explain fully", or similar

    Examples:
    User: "Can I migrate to Australia?"
    Good: "Yes, there are several pathways to migrate to Australia! The best option depends on your situation. Are you currently a student, working professional, or looking at family sponsorship?"

    User: "Which country is best for studying?"
    Good: "That depends on what you're looking for! Are you more interested in lower costs, post-study work opportunities, or specific programs? What field do you want to study?"

    Reply in their language. Be warm, helpful, and conversational!

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

    public function logRag(\Illuminate\Http\Request $request)
    {
        try {
            $member = $this->_current_member; // 你项目基类里已有的当前用户
            if (empty($member)) {
                return response()->json(['status'=>403,'message'=>'please login'], 403);
            }

            // 兼容两套字段名：ask/reply 或 question/answer
            $ask   = (string)($request->input('ask', $request->input('question', '')));
            $reply = (string)($request->input('reply', $request->input('answer', '')));
            $mode  = (string)$request->input('chat_mode', 'immigration');

            if ($ask === '' || $reply === '') {
                \Log::warning('chat.log missing ask/reply', ['payload' => $request->all()]);
                return response()->json(['status'=>422,'message'=>'ask/reply required'], 422);
            }

            $nowUtc     = \Carbon\Carbon::now('UTC');
            $targetDate = (int)date('Ymd', strtotime($this->_today_date));

            \DB::beginTransaction();

            // 先插 ask，related_id 先占位
            $askId = \DB::table('chat_log')->insertGetId([
                'member_id'   => $member['id'],
                'related_id'  => 0,
                'target_date' => $targetDate,
                'type'        => 'ask',
                'content'     => $ask,
                'chat_mode'   => $mode,
                'status'      => 1,
                'created_at'  => $nowUtc,
                'updated_at'  => $nowUtc,
            ]);

            // 把 ask 自己的 id 回写到 related_id
            \DB::table('chat_log')->where('id', $askId)->update(['related_id' => $askId]);

            // 再插 reply，共享同一个 related_id
            $replyId = \DB::table('chat_log')->insertGetId([
                'member_id'   => $member['id'],
                'related_id'  => $askId,
                'target_date' => $targetDate,
                'type'        => 'reply',
                'content'     => $reply,
                'chat_mode'   => $mode,
                'status'      => 1,
                'created_at'  => $nowUtc,
                'updated_at'  => $nowUtc,
            ]);

            \DB::commit();

            \Log::info('chat.log ok', [
                'member_id' => $member['id'],
                'ask_id'    => $askId,
                'reply_id'  => $replyId,
                'mode'      => $mode,
            ]);

            return response()->json(['status'=>200], 200);
        } catch (\Throwable $e) {
            \DB::rollBack();
            \Log::error('chat.log failed: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return response()->json(['status'=>500,'message'=>'log failed'], 500);
        }
    }

    protected function callRagApi($question)
    {
        $url = url('/api/rag/ask');
        $payload = json_encode(['q' => $question, 'tag' => 'policy'], JSON_UNESCAPED_UNICODE);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($resp, true);
        return $data['answer'] ?? '';
    }
}