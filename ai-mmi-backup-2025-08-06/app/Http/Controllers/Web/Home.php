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
    
    public function chat($init = 0) {
        // post
        $this->pageAction(function() {
            $question = $this->postParamValue('question');

            if(!empty($question) && !empty($this->_current_member)) {
                $can_do_reply = true;

                // Check subscription-based access
                $has_migration_sub = !empty($this->_current_member['has_migration_subscription']);
                $has_education_sub = !empty($this->_current_member['has_education_subscription']);
                $has_subscription = $has_migration_sub || $has_education_sub;

                // Chat is now unlimited for all users
                // Conversation flow will promote subscription at strategic points
                $can_do_reply = true;

                if($can_do_reply) {
                    $rawQuestion = $this->postParamValue('question', '');

                    // Validate question is not empty
                    if(empty($rawQuestion)) {
                        $this->pageResult([
                            'status'  => 400,
                            'message' => 'Please enter a question.'
                        ]);
                        return;
                    }

                    // Call Gemini API with user question and subscription status
                    $new_reply = $this->callGeminiApi($rawQuestion, $has_subscription);

                    // Validate reply is not empty
                    if(empty($new_reply)) {
                        $this->pageResult([
                            'status'  => 500,
                            'message' => 'Sorry, the AI service is temporarily unavailable. Please try again.'
                        ]);
                        return;
                    }
                    
                    try {
                        DB::table('chat_log')->insert([
                            'member_id'   => $this->_current_member['id'],
                            'target_date' => (int)date('Ymd', strtotime($this->_today_date)),
                            'type'        => 'ask',
                            'content'     => $rawQuestion,
                            'status'      => 1,
                            'created_at'  => Carbon::now('UTC'),
                            'updated_at'  => Carbon::now('UTC'),
                        ]);

                        DB::table('chat_log')->insert([
                            'member_id'   => $this->_current_member['id'],
                            'target_date' => (int)date('Ymd', strtotime($this->_today_date)),
                            'type'        => 'reply',
                            'content'     => $new_reply,
                            'status'      => 1,
                            'created_at'  => Carbon::now('UTC'),
                            'updated_at'  => Carbon::now('UTC'),
                        ]);
                    } catch (\Throwable $e) {

                    }

                    $nowUtcUser  = Carbon::now('UTC')->toIso8601String();
                    $nowUtcReply = Carbon::now('UTC')->toIso8601String();

                    // Check conversation flow for promotional/guidance prompts
                    $flowService = new ConversationFlowService($this->_current_member['id']);
                    $userProfile = [
                        'has_subscription' => $has_subscription,
                        'subscription_tier' => $this->_current_member['primary_plan_code'] ?? 'free'
                    ];

                    try {
                        $flowResponse = $flowService->analyzeAndTrigger($rawQuestion, $new_reply, $userProfile);
                    } catch (\Exception $e) {
                        \Log::error('Flow service error: ' . $e->getMessage());
                        $flowResponse = null;
                    }

                    $this->pageResult([
                        'status'    => 200,
                        'content'   => nl2br($rawQuestion),
                        'reply'     => nl2br($new_reply),

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

                        // Add flow response if triggered
                        'flow_prompt'         => $flowResponse ? $flowService->formatForFrontend($flowResponse) : null,
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
        
        $max_date_int = $this->getSession('max_chat_date_int');
        if(!empty($init)) {
            $max_date_int = '';
        }

        $chat_message = [];
        if(!empty($this->_current_member)) {
            $chat_message = $this->loadModel('chatlog')->getAll($this->_current_member['id'], $max_date_int);

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

    protected function callGeminiApi($question = '', $has_subscription = false) {
        if (empty($question)) return '';

        // 1) Current User
        $member = $this->_current_member;
        if (empty($member)) return 'Please login first.';

        //  Read Free Assessment Profile
        $fa_ctx = $this->buildFAContext($member['id']);

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

        if (!empty($fa_ctx)) {
            $system .= "\n\n[User Profile from Free Assessment]\n{$fa_ctx}\n"
                    . "Please incorporate the above information into your response. If any part conflicts with policy,
                     the policy shall prevail, and you must indicate the key information that needs to be supplemented.";
        } else {
            // If no portrait is provided, prompt the model to give a "generic response + what additional information is needed."
            $system .= "\n\n(No Free Assessment image. Please provide general recommendations
            first and list the key information that needs to be supplemented.)";
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

        $resp = curl_exec($ch);
        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            \Log::error('Gemini CURL Error: ' . $err);
            return '[Error] ' . $err;
        }
        curl_close($ch);

        // Log raw response for debugging
        \Log::info('Gemini API Response: ' . substr($resp, 0, 500));

        $data = json_decode($resp, true);
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

    public function fa_me() {
        if (empty($this->_current_member)) {
            return response()->json(['has_profile' => false]);
        }
        $mid = $this->_current_member['id'];
        $fa = \DB::table('free_assessment')
            ->where(function($q) use ($mid) {
                $q->where('member_id', $mid);
            })
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->first();

        if (!$fa) {
            return response()->json(['has_profile' => false]);
        }
        return response()->json([
            'has_profile' => true,
        ]);
    }

    private function buildFAContext($memberId): string
    {
        $row = DB::table('free_assessment')
            ->select('questions','answers')
            ->where(function($q) use ($memberId) {
                $q->where('member_id', $memberId);
            })
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->first();

        if (!$row) return '';

        // Parsing JSON
        $questions = json_decode($row->questions ?? '[]', true) ?: [];
        $answers   = json_decode($row->answers   ?? '[]', true) ?: [];

        // Tool: Map “Question Number/Answer Number/Free Text” to a readable string
        $pick = function(string $qNo) use ($questions, $answers) {
            if (!array_key_exists($qNo, $answers)) return null;
            $ansKey = (string)$answers[$qNo];

            if (isset($questions[$qNo]['answers']) && is_array($questions[$qNo]['answers']) &&
                array_key_exists($ansKey, $questions[$qNo]['answers'])) {
                $text = (string)$questions[$qNo]['answers'][$ansKey];
            } else {
                // Free text (e.g., profession/occupation)
                $text = (string)$answers[$qNo];
            }
            // Render &gt; as HTML entities
            return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        };

        // Assign values based on question numbers (adjustable according to the actual question bank)
        $agreeAssess   = $pick('1');  // Yes/No
        $country       = $pick('2');  // Britain/United States/Canada/Australia
        $visaType      = $pick('3');  // Skilled/Student/Family/Employer sponsorship...
        $education     = $pick('4');  // Secondary/Diploma/Bachelor/Master/Doctoral
        $age           = $pick('5');  // 18-25/25-33/...
        $hasEnglish    = $pick('6');  // Yes/No
        $ieltsResult   = $pick('7');  // IELTS 6/7/8/No result
        $occupation    = $pick('8');  // Free text, such as “software engineer”
        $workYears     = $pick('9');  // >1 / 1-3 / 3-5 / ...
        $localYears    = $pick('10'); // Years of local work experience in the target country
        $hasOffer      = $pick('11'); // Yes/No

        // Assemble “portrait facts” that are useful for answering questions.
        $parts = [];
        if ($country)     $parts[] = "Target Country: {$country}";
        if ($visaType)    $parts[] = "Intended Visa Type: {$visaType}";
        if ($education)   $parts[] = "Highest level of education attained: {$education}";
        if ($age)         $parts[] = "Age group: {$age}";
        if ($hasEnglish)  $parts[] = "Has the English exam been completed: {$hasEnglish}";
        if ($ieltsResult) $parts[] = "English Score/Level: {$ieltsResult}";
        if ($occupation)  $parts[] = "Occupation (ANZSCO Direction): {$occupation}";
        if ($workYears)   $parts[] = "Total years of full-time work experience: {$workYears}";
        if ($localYears)  $parts[] = "Years of local work experience in the target country: {$localYears}";
        if ($hasOffer)    $parts[] = "Have a job offer in the target country: {$hasOffer}";

        // Optional: Provide judgment prompts for the model (does not replace policy decisions)
        $hints = [];
        if ($visaType && stripos($visaType, 'Student') !== false) {
            $hints[] = 'Students tend to prioritize visa pathways; please consider your major, program duration, 
            and post-graduation options (including work visas in various countries, H-1B, PSW, etc.).';
        }
        if ($visaType && stripos($visaType, 'Skilled') !== false) {
            $hints[] = 'Users are interested in skilled migration; conduct an initial 
            screening based on age, education level, English proficiency level, and years of work experience.';
        }
        if ($hasOffer === 'Yes') {
            $hints[] = 'A job offer has been received; please prioritize evaluating the employer sponsorship/work visa pathway.';
        }
        if ($education && (stripos($education, 'Secondary') !== false || stripos($education, 'Diploma') !== false)) {
            $hints[] = "Applicants with educational qualifications below a bachelor's degree 
            may face restrictions for certain skilled migration pathways; please outline alternative routes or remedial measures.";
        }
        if ($hasEnglish === 'No') {
            $hints[] = 'No English scores available; please indicate the minimum English requirement for the target pathway and the transition plan.';
        }

        // Optional: Gap prompts, enabling the model to indicate what additional information is needed in the answer.
        $missing = [];
        if (!$country)   $missing[] = 'Target Country';
        if (!$visaType)  $missing[] = 'Intended Visa Type';
        if (!$education) $missing[] = 'Highest level of education attained';
        if (!$hasEnglish)$missing[] = 'Has the English exam been completed';
        if (!$occupation)$missing[] = 'Occupation/Job Type';
        if (!$workYears) $missing[] = 'Total years of service';

        // Assembly output
        $ctx = '';
        if ($parts)  $ctx .= implode("\n", $parts);
        if ($hints)  $ctx .= "\n【System Prompt】\n" . implode("\n", $hints);
        if ($missing) $ctx .= "\n【Lack of critical information】" . implode('、', $missing) . "。";

        return trim($ctx);
    }

}