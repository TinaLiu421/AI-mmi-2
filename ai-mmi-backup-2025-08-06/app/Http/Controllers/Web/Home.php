<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Services\ConversationFlowService;
use Illuminate\Support\Facades\Schema;

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

        if (empty($list_news['data'])) {
            $list_news = $this->loadModel('posts')->getAll(
            [
                'show_type'         =>  1,
                'show_page_size'    =>  10,
                'show_highlight'    =>  1
            ]);
        }

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

        if (empty($list_events['data'])) {
            $list_events = $this->loadModel('posts')->getAll(
            [
                'show_type'         =>  2,
                'show_page_size'    =>  10,
                'show_highlight'    =>  1
            ]);
        }

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
        $this->pageAction(function () {

            // === ① 读取参数（仅保留必要项） ===
            $question = $this->postParamValue('question', request()->input('question', ''));
            if (trim($question) === '') {
                $this->pageResult(['status' => 400, 'message' => 'Please enter a question.']);
                return;
            }
            $sourceInput     = request()->input('source', null);
            $fromQaLegacy    = request()->boolean('from_qa', false);
            $isFromQa        = ($sourceInput === 'qa') || $fromQaLegacy;
            $chatSource      = $isFromQa ? 'qa' : 'chat';
            $logToChatTable  = !$isFromQa;
            $storeChatConfig = [
                'log_to_chat_table' => $logToChatTable,
                'source'            => $chatSource,
            ];

            if (is_array($question)) {
                $question = json_encode($question, JSON_UNESCAPED_UNICODE);
            } elseif (!is_string($question)) {
                $question = strval($question);
            }
            $rawQuestion = trim((string)$question);
            $qaLang      = $isFromQa ? $this->detectLangZhOrEn($rawQuestion) : 'en';

            $useRag   = 0;
            $override = '';

            // === ② 会话身份（保留即可） ===
            $member    = $this->_current_member ?: null;
            $memberId  = $member['id'] ?? null;
            $guestId   = $this->getMyCookie('guest_id');
            $sessionId = session()->getId();

            // === ②.5 留学类问题（education）→ 永远免费，且可触发升学引导 ===
            $category    = $this->classifyEducationIntent($rawQuestion);
            $isEdu       = ($category === 'education');
            $applyIntent = false;

            // 只有在确定是“教育类”时，才去额外判断是否有“申请意图”
            // if ($isEdu) {
            //     $applyIntent = $this->detectApplyIntent($rawQuestion);
            // }

            // === ③.1 已登录用户免费次数限制（Free 用户 5 次） ===

            if (!empty($member)) {

                // ① 判断是否付费用户
                $isPaidUser = DB::table('subscriptions')
                    ->where('member_id', $memberId)
                    ->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('ends_at')
                        ->orWhere('ends_at', '>', now());
                    })
                    ->exists();

                // 如果不是付费用户，则 Free Plan 限制生效
                if (!$isPaidUser && !$isEdu) {

                    // ② 统计用户提问次数（只数 type='ask'）
                    $memberAskCount = DB::table('chat_log')
                        ->where('member_id', $memberId)
                        ->where('type', 'ask')
                        ->count();

                    // ③ 超过 5 次 → 返回简短回答 + 升级提示
                    if ($memberAskCount >= 5) {

                        $limitMsg = $this->buildPaidPlanLimitReply($rawQuestion);

                        // 入库
                        $this->storeChat($memberId, $guestId, $sessionId, $rawQuestion, $limitMsg, 'free-plan-limit', 'AI-mmi', $storeChatConfig);

                        // 返回用户
                        $this->jsonReply($rawQuestion, $limitMsg, 'free-plan-limit', $member);
                        return;
                    }
                }
            }

            // === ③ 生成回复：不做任何“Agent 限制/语言判断/历史读取”===
            $reply       = '';
            $replySource = 'model';
            $aiOwnerName = 'AI-mmi';

            // === ②.1 未登录用户免费额度限制（游客最多 3 次提问） ===
            if (empty($member)) {
                $guestAskCount = 0;
                if ($this->chatLogHasGuestId()) {
                    $guestAskCount = \DB::table('chat_log')
                        ->whereNull('member_id')      // 只统计未登录的提问
                        ->where('guest_id', $guestId)
                        ->where('type', 'ask')
                        ->count();
                }

                if ($guestAskCount >= 3) {
                    // ★ 交给 Grok 生成「同语言」的限制提示
                    $limitMsg = $this->buildLimitReply($rawQuestion);

                    // 记录到 chat_log 里（方便你之后分析）
                    $this->storeChat($memberId, $guestId, $sessionId, $rawQuestion, $limitMsg, 'free-limit', 'AI-mmi', $storeChatConfig);

                    // 返回前端
                    $this->jsonReply($rawQuestion, $limitMsg, 'free-limit', $member);
                    return; // 终止后续逻辑（不再回答问题）
                }
            }

            /*
             * ❶/❷ RAG override 及后端直连逻辑已停用，留存注释便于将来恢复。
             * if ($useRag === 1 && $override !== '') {
             *     $reply       = trim($override);
             *     $replySource = 'rag-override';
             *     $aiOwnerName = 'AI-mmi (Policy)';
             * }
             *
             * if ($reply === '' && $useRag === 1) {
             *     $rag = $this->callRagDirect($rawQuestion, null, '', null);
             *     $rag = is_string($rag) ? trim($rag) : '';
             *     if ($rag !== '' && mb_strlen($rag) >= 30) {
             *         $reply       = $rag;
             *         $replySource = 'rag-direct';
             *         $aiOwnerName = 'AI-mmi (Policy)';
             *     }
             * }
             */

            // ❸（QA 专用）主题限定 + CTA
            if ($reply === '' && $isFromQa) {
                if (!$this->isScholarshipOrPartnerSchoolQuestion($rawQuestion)) {
                    // Out-of-scope: do not call xAI and do not return text
                    $reply       = null;
                    $replySource = 'qa-out-of-scope';
                    $aiOwnerName = 'AI-mmi';
                } else {
                    $qaSystemPrompt = "
You are AI-mmi handling scholarship and partner-school Q&A.

Rules:
- ONLY answer questions about the AI-mmi Scholarship or these partner schools: SBTA–SELA (Adelaide or Brisbane), Queensland Academy of Technology (QAT) (Brisbane or Sydney), Australia College of Tourism & Information Technology (ACTI) (Brisbane, Gold Coast, Cairns), Queensland International Institute (QII) (Brisbane), Rosehill College (Sydney).
- Always search AI-mmi internal collections first; use web search only if collections have no relevant information.
- If neither internal collections nor web search provide reliable information, clearly say you do not have enough information instead of guessing or hallucinating.
- Keep answers concise and factual.
- Detect whether the user's message is Chinese; if yes, reply in Chinese; otherwise reply in English.
- Do not mention xAI, Grok, LLMs, tools, citations, collection IDs, or technical details in the user-visible answer.
";

                    $x = $this->callXaiResponses($rawQuestion, [
                        'temperature'        => 0.2,
                        'max_output_tokens'  => 600,
                        'model'              => 'grok-4-1-fast-reasoning',
                        'enable_search'      => true,
                        'collection_ids'     => ['collection_1c89e82d-3b05-4bb6-9bf7-aae3181a3a9c'],
                        'vector_store_ids'   => [],
                        'system'             => $qaSystemPrompt,
                        'resume_thread'      => false,
                    ]);

                    if (!is_array($x) || empty($x['ok'])) {
                        $reply = is_array($x) ? ($x['text'] ?? '[Error: Unknown reply]') : (string)$x;
                        $replySource = is_array($x) && !empty($x['source']) ? $x['source'] : 'upstream-error';
                    } else {
                        $reply = $x['text'];
                        $reply = preg_replace(
                            '/信息基于xAI内部集合检索的文件/u',
                            '信息基于AI-mmi内部集合检索的资料',
                            $reply
                        );
                        $replySource = $x['source'] ?? 'model';
                    }

                    if (mb_strlen($reply, 'UTF-8') > 2500) {
                        $reply = mb_substr($reply, 0, 2500, 'UTF-8');
                    }

                    $reply       = $this->appendQaCta($reply, $qaLang);
                    $aiOwnerName = 'AI-mmi';
                }
            }

            // ❸ 纯模型 Pure model
            if ($reply === '' && !$isFromQa) {
                $x = $this->callXaiResponses($rawQuestion, [
                    'temperature' => 0.2,
                    'max_output_tokens' => 2048,
                    'model' => 'grok-4-1-fast-reasoning',
                    'enable_search'     => true,
                    'collection_ids'   => ['collection_1c89e82d-3b05-4bb6-9bf7-aae3181a3a9c'],
                    'vector_store_ids' => [],
                    'system' => "
                    You are AI-mmi, specialised in immigration and visa queries.

                    ## Identity & Naming
                    - Always refer to yourself as “AI-mmi”.
                    - Never mention xAI, Grok, LLM, model, provider names or any tool names in the answer.
                    - Do NOT mention things like file_search, collections_search, web_search, vector stores, file IDs or collection IDs.
                    - Do NOT output citations, file IDs, collection IDs or any other technical identifiers in user-visible text.

                    ## Language Behaviour (auto follow user language)
                    - Detect the language of the user's latest message and reply in that same language.
                    - If the user writes in Simplified Chinese, reply in Simplified Chinese.
                    - If the user writes in Traditional Chinese, reply in Traditional Chinese.
                    - If the user writes in Japanese, reply in Japanese.
                    - For any other language (e.g. French, Spanish, etc.), mirror that language naturally.
                    - If the user mixes languages, default to the language used in the last sentence that contains the real question.

                    ## Greeting Behaviour (only first reply)
                    - Only when this is the FIRST reply in a new conversation thread, start with a short greeting + self-introduction.
                    - First reply greeting templates:
                        - English: “Hi this is AI-mmi, your smart education and migration assistant.”
                        - Simplified Chinese: “您好！我是 AI-mmi，您的智能留学和移民助理。”
                        - Traditional Chinese: “您好！我是 AI-mmi，您的智能留學和移民助理。”
                        - Other languages: translate the English sentence naturally into that language.
                    - In all later replies in the SAME conversation:
                        - Do NOT introduce yourself again.
                        - Normally do NOT add extra greetings; answer the question directly.
                    - If the user only says something like “thank you”, “谢谢”, “多謝”, you may respond with a short friendly closing sentence in the same language, without re-introducing yourself.

                    ### INTERNAL KNOWLEDGE RULES
                    - When internal collections are available, you MUST first attempt to retrieve internal policy files.
                    - If you used internal collections, you may add one natural-language sentence such as: 
                    'The above information is based on data retrieved from AI-mmi's internal repository.' 
                    or equivalent wording in the user's language.
                    - If no relevant internal document is found, fall back to public web sources.
                    - If public sources are used, you may optionally state in natural language:
                    “本次仅使用公开资料（未使用 AI-mmi 内部资料库）。”
                    or equivalent wording in the user's language.
                    - DO NOT fabricate or guess internal documents or policies.

                    ### CITATION & TECHNICAL INFO
                    - Citations, technical IDs, raw metadata are for internal logging only.
                    - They MUST NOT appear in user-visible output.

                    ### RESPONSE STYLE
                    - Provide precise, structured, factual information.
                    - Keep the tone professional, clear and user-friendly.",

                ]);

                if (!is_array($x) || empty($x['ok'])) {
                    $reply = is_array($x) ? ($x['text'] ?? '[Error: Unknown reply]') : (string)$x;
                    $replySource = is_array($x) && !empty($x['source']) ? $x['source'] : 'upstream-error';
                } else {
                    $reply = $x['text'];

                    // === 如果是留学问题 + 有申请意图 → 添加升学申请引导 ===
                    // if ($isEdu && $applyIntent) {
                    //     $eduFooter = $this->buildEducationApplyMessage($rawQuestion);
                    //     if ($eduFooter !== '') {
                    //         $reply .= "\n\n" . $eduFooter;
                    //     }
                    // }

                    $reply = preg_replace(
                        '/信息基于xAI内部集合检索的文件/u',
                        '信息基于AI-mmi内部集合检索的资料',
                        $reply
                    );

                    $replySource = $x['source'] ?? 'model';
                }

                $aiOwnerName = 'AI-mmi';
            }

            // === ④ 入库 + 返回 ===
            $this->storeChat($memberId, $guestId, $sessionId, $rawQuestion, $reply, $replySource, $aiOwnerName, $storeChatConfig);
            $this->jsonReply($rawQuestion, $reply, $replySource, $member);
        });

        if (request()->isMethod('post')) return;

        // === ⑤ GET 拉历史（保持不变） ===
        $max_date_int = $this->getSession('max_chat_date_int');
        if (!empty($init)) $max_date_int = '';
        $chat_message = [];
        if (!empty($this->_current_member)) {
            $chat_message = $this->loadModel('chatlog')->getAll($this->_current_member['id'], $max_date_int);
            foreach ($chat_message as &$m) {
                $m['owner_name']   = strtolower($m['type']) === 'ask'
                    ? ($this->_current_member['alias_name'] ?? 'User') : 'AI-mmi';
                $m['owner_avatar'] = strtolower($m['type']) === 'ask'
                    ? 'asset/image/icon-member.png' : 'asset/image/logo-mmi.png';
                $m['content_raw']  = $m['content'];
                $m['content_html'] = nl2br(e($m['content']));
                $m['created_time'] = !empty($m['created_at'])
                    ? \Carbon\Carbon::parse($m['created_at'], 'UTC')->toIso8601String() : null;
            }
        }
        echo json_encode($chat_message);
    }

    public function logRag(\Illuminate\Http\Request $request)
    {
        /*
         * 原 RAG 记录逻辑（数据库写入等）已禁用：
         * try {
         *     ...
         * }
         */

        return response()->json([
            'status'  => 410,
            'message' => 'RAG logging is disabled in this environment.',
        ], 410);
    }

    protected function callRagDirect(string $question, ?string $tag, string $langIgnored = '', ?string $country = null): string
    {
        /*
         * 原 RAG 直连逻辑（Gemini + Pinecone 检索）已禁用：
         * try {
         *     ...
         * }
         */

        return '';
    }

    private function ownerVisual(?array $member): array
    {
        if ($member) {
            $name   = $member['alias_name'];
            $avatar = 'asset/image/icon-member.png';
            if (!empty($member['avatar'])) {
                $avatar = file_exists('upload/member_avatar/'.$member['avatar'])
                    ? 'upload/member_avatar/'.$member['avatar']
                    : 'upload/member_logo/'.$member['avatar'];
            }
            return [$name, $avatar];
        }
        return ['Guest','asset/image/icon-member.png'];
    }

    // ✅ 入库统一处理
    private function storeChat($memberId, $guestId, $sessionId, $question, $reply, $source, $aiOwnerName, array $options = [])
    {
        $logToChatTable = $options['log_to_chat_table'] ?? true;
        if (!$logToChatTable) {
            return;
        }

        try {
            $nowUtc = \Carbon\Carbon::now('UTC');
            $targetDate = (int)date('Ymd');
            \DB::beginTransaction();

            $hasReplySourceColumn = $this->chatLogHasReplySource();

            $askPayload = [
                'member_id'   => $memberId,
                'related_id'  => 0,
                'target_date' => $targetDate,
                'type'        => 'ask',
                'content'     => $question,
                'status'      => 1,
                'created_at'  => $nowUtc,
                'updated_at'  => $nowUtc,
            ];
            if ($this->chatLogHasGuestId()) {
                $askPayload['guest_id'] = $guestId;
            }
            if ($this->chatLogHasSessionId()) {
                $askPayload['session_id'] = $sessionId;
            }

            $askId = \DB::table('chat_log')->insertGetId($askPayload);
            \DB::table('chat_log')->where('id', $askId)->update(['related_id' => $askId]);

            $replyPayload = [
                'member_id'   => $memberId,
                'related_id'  => $askId,
                'target_date' => $targetDate,
                'type'        => 'reply',
                'content'     => $reply,
                'status'      => 1,
                'created_at'  => $nowUtc,
                'updated_at'  => $nowUtc,
            ];
            if ($this->chatLogHasGuestId()) {
                $replyPayload['guest_id'] = $guestId;
            }
            if ($this->chatLogHasSessionId()) {
                $replyPayload['session_id'] = $sessionId;
            }
            if ($hasReplySourceColumn) {
                $replyPayload['reply_source'] = $source;
            }

            \DB::table('chat_log')->insert($replyPayload);

            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollBack();
            \Log::error('CHAT DB INSERT FAIL: ' . $e->getMessage());
        }
    }

    private function chatLogHasReplySource(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        try {
            $cached = \Illuminate\Support\Facades\Schema::hasColumn('chat_log', 'reply_source');
        } catch (\Throwable $e) {
            $cached = false;
        }

        return $cached;
    }

    private function chatLogHasGuestId(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        try {
            $cached = \Illuminate\Support\Facades\Schema::hasColumn('chat_log', 'guest_id');
        } catch (\Throwable $e) {
            $cached = false;
        }

        return $cached;
    }

    private function chatLogHasSessionId(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        try {
            $cached = \Illuminate\Support\Facades\Schema::hasColumn('chat_log', 'session_id');
        } catch (\Throwable $e) {
            $cached = false;
        }

        return $cached;
    }

    // ✅ 输出统一处理
    private function jsonReply($question, $reply, $source, $member)
    {
        [$member_owner_name, $member_owner_avatar] = $this->ownerVisual($member);
        $nowUtcIso = \Carbon\Carbon::now('UTC')->toIso8601String();
        $this->pageResult([
            'status'               => 200,
            'content'              => (string)$question,
            'content_raw'          => (string)$question,
            'content_html'         => nl2br(e($question)),
            'reply'                => $reply,
            'reply_raw'            => $reply,
            'reply_html'           => nl2br(e($reply)),
            'answer_markdown'      => $reply,
            'content_created_at'   => $nowUtcIso,
            'reply_created_at'     => $nowUtcIso,
            'member_owner_name'    => $member_owner_name,
            'member_owner_avatar'  => $member_owner_avatar,
            'ai_owner_name'        => 'AI-mmi',
            'ai_owner_avatar'      => 'asset/image/logo-mmi.png',
            'reply_source'         => $source,
            'flow_prompt'          => null,
        ]);
    }

    public function resetGrokConversation()
    {
        $this->setSession(['grok_conversation_id' => null]);
        return response()->json(['status'=>200,'message'=>'grok conversation reset']);
    }

    protected function callXaiResponses(string $question, array $opts = []): array
    {
        $apiKey = env('XAI_API_KEY');
        if (!$apiKey) {
            \Log::error('XAI_API_KEY missing');
            return $this->friendlyFallbackResult($question, 'upstream-error', 'missing-api-key');
        }

        if ($this->isUpstreamCircuitOpen()) {
            \Log::warning('xAI circuit breaker open; skip upstream call');
            return $this->friendlyFallbackResult($question, 'upstream-error', 'circuit-open');
        }

        $url     = rtrim(env('XAI_API_BASE', 'https://api.x.ai'), '/') . '/v1/responses';
        $model   = $opts['model'] ?? 'grok-4-1-fast-reasoning';
        $system  = $opts['system'] ?? null;
        $timeoutInitial = (float)($opts['timeout'] ?? env('XAI_TIMEOUT', 60));
        // 保底 60s，避免误用更短值导致 30s 超时
        if ($timeoutInitial < 60) $timeoutInitial = 60;
        if ($timeoutInitial > 180) $timeoutInitial = 180;
        $timeoutRetry = (float)($opts['retry_timeout'] ?? env('XAI_RETRY_TIMEOUT', 75));
        if ($timeoutRetry < $timeoutInitial) $timeoutRetry = $timeoutInitial;
        if ($timeoutRetry > 240) $timeoutRetry = 240;
        $timeoutInitialMs = (int)($opts['timeout_ms'] ?? env('XAI_TIMEOUT_MS', 0));
        $timeoutRetryMs   = (int)($opts['retry_timeout_ms'] ?? env('XAI_RETRY_TIMEOUT_MS', 0));
        $connectTimeout = (float)($opts['connect_timeout'] ?? env('XAI_CONNECT_TIMEOUT', 10));
        if ($connectTimeout < 1) $connectTimeout = 1;
        $connectTimeout = min($connectTimeout, $timeoutInitial, $timeoutRetry);
        // avoid PHP max_execution_time fatals during long upstream waits
        @set_time_limit((int)ceil($timeoutRetry + 10));

        // —— 温度：给默认值 + 边界
        $temperature = array_key_exists('temperature', $opts)
            ? (float)$opts['temperature']
            : (float)env('XAI_TEMPERATURE', 0.2);
        if ($temperature < 0) $temperature = 0.0;
        if ($temperature > 1) $temperature = 1.0;

        $useThread = !array_key_exists('resume_thread', $opts) || (bool)$opts['resume_thread'];

        // —— 多轮上下文：上一轮 response_id
        $prevId = null;
        if ($useThread) {
            $prevId = $this->getXaiPrevResponseId();
            if (!empty($prevId)) {
                $payload['previous_response_id'] = $prevId;
            }
        }

        // —— 输入结构（支持可选 system）
        $input = [];
        if ($system) {
            $input[] = [
                'role'    => 'system',
                'content' => [[ 'type'=>'input_text', 'text'=>$system ]],
            ];
        }
        $input[] = [
            'role'    => 'user',
            'content' => [[ 'type'=>'input_text', 'text'=>(string)$question ]],
        ];

        $payload = [
            'model'            => $model,
            'input'            => $input,
            'max_output_tokens'=> $opts['max_output_tokens'] ?? 2048,
            'temperature'      => $temperature,
        ];
        if (!empty($prevId)) {
            $payload['previous_response_id'] = $prevId;
        }

        // —— 统一维护一个工具数组
        $tools = [];

        // === File Search（需要 vector_store_ids）===
        $collectionIds  = isset($opts['collection_ids']) && is_array($opts['collection_ids'])
            ? array_values($opts['collection_ids']) : [];

        // Prefer explicit vector store IDs (env or opts). Do NOT substitute collection IDs here.
        $envVectorStores = array_filter(array_map('trim', explode(',', (string)env('XAI_VECTOR_STORE_IDS', ''))));
        $vectorStoreIds = isset($opts['vector_store_ids']) && is_array($opts['vector_store_ids'])
            ? array_values(array_filter($opts['vector_store_ids']))
            : [];
        if (empty($vectorStoreIds) && !empty($envVectorStores)) {
            $vectorStoreIds = array_values($envVectorStores);
        }
        $allowCollectionIds = filter_var(env('XAI_ALLOW_COLLECTION_IDS', false), FILTER_VALIDATE_BOOLEAN);
        if (empty($vectorStoreIds) && $allowCollectionIds && !empty($collectionIds)) {
            $vectorStoreIds = $collectionIds;
            \Log::warning('xAI file_search using collection_ids as vector_store_ids (compat mode).');
        } elseif (empty($vectorStoreIds) && !empty($collectionIds)) {
            \Log::warning('xAI file_search disabled: vector_store_ids missing (collection_ids ignored).');
        }

        if (!empty($vectorStoreIds)) {
            $tools[] = [
                'type'             => 'file_search',
                'vector_store_ids' => $vectorStoreIds,
                'max_num_results'  => (int)($opts['file_search_max'] ?? 15),
            ];
        }

        // === Web Search（可选）===
        $enableSearch = array_key_exists('enable_search', $opts)
            ? (bool)$opts['enable_search']
            : (empty($vectorStoreIds) ? true : (bool)env('XAI_ENABLE_WEB_SEARCH', false));
        if ($enableSearch) {
            $webArgs = [];
            if (!empty($opts['allowed_domains']) && is_array($opts['allowed_domains'])) {
                $webArgs['allowed_domains'] = array_values(array_filter(array_map('strval', $opts['allowed_domains'])));
            }
            $defaultExcluded = ['edvisehub.com'];
            $webArgs['excluded_domains'] = array_values(array_unique(array_merge(
                $defaultExcluded, (array)($opts['excluded_domains'] ?? [])
            )));

            $tools[] = !empty($webArgs)
                ? ['type' => 'web_search', 'arguments' => $webArgs]
                : ['type' => 'web_search'];
        }

        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $payloadLite = $payload;
        if (isset($payloadLite['tools'])) {
            unset($payloadLite['tools'], $payloadLite['tool_choice']);
        }
        $payloadLite['max_output_tokens'] = min(512, (int)$payloadLite['max_output_tokens']);
        $useLitePayload = false;

        \Log::info('xAI payload => ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $maxAttempts = 2; // 1 retry
        $backoffMs   = [300, 800];

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($attempt > 1) {
                $sleepMs = $backoffMs[$attempt - 2] ?? end($backoffMs);
                if ($sleepMs > 0) {
                    usleep((int)$sleepMs * 1000);
                }
            }

            $timeoutToUse   = $attempt === 1 ? $timeoutInitial : $timeoutRetry;
            $timeoutMsToUse = $attempt === 1 ? $timeoutInitialMs : $timeoutRetryMs;
            $timeLimitGuard = $timeoutMsToUse > 0 ? max($timeoutToUse, $timeoutMsToUse / 1000) : $timeoutToUse;
            @set_time_limit((int)ceil($timeLimitGuard + 10));

            $payloadToSend = $useLitePayload ? $payloadLite : $payload;

            $ch = curl_init();
            $curlOptions = [
                CURLOPT_URL            => $url,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_POSTFIELDS     => json_encode($payloadToSend, JSON_UNESCAPED_UNICODE),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeoutToUse,
                CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            ];

            if ($timeoutMsToUse > 0) {
                $curlOptions[CURLOPT_TIMEOUT_MS] = $timeoutMsToUse;
            }

            curl_setopt_array($ch, $curlOptions);

            $startTime   = microtime(true);
            $resp        = curl_exec($ch);
            $http        = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErrNo   = curl_errno($ch);
            $curlErrMsg  = $curlErrNo ? curl_error($ch) : '';
            curl_close($ch);
            $elapsedMs   = (int)round((microtime(true) - $startTime) * 1000);

            $requestId   = $this->extractXaiRequestId($resp);
            $isTimeout   = $curlErrNo === CURLE_OPERATION_TIMEDOUT;
            $isHttp5xx   = $http >= 500 && $http < 600;
            $isRateLimit = $http === 429;
            $errorType   = null;

            if ($curlErrNo) {
                $errorType = $isTimeout ? 'timeout' : 'curl';
            } elseif ($http < 200 || $http >= 300) {
                $errorType = $isRateLimit ? 'rate-limit' : ($isHttp5xx ? 'http-5xx' : 'http-error');
            }

            if ($errorType !== null) {
                $this->logUpstreamFailure($errorType, $http, $curlErrNo, $curlErrMsg, $elapsedMs, $requestId, $attempt);
                $shouldRetry = ($isTimeout || $isHttp5xx || $isRateLimit) && $attempt < $maxAttempts;
                if ($shouldRetry) {
                    if ($isRateLimit && !$useLitePayload) {
                        $useLitePayload = true;
                    }
                    continue;
                }

                $this->recordUpstreamFailure($errorType);
                return $this->friendlyFallbackResult(
                    $question,
                    $isTimeout ? 'upstream-timeout' : 'upstream-error',
                    $errorType,
                    ['http_status' => $http, 'request_id' => $requestId]
                );
            }

            $data = json_decode($resp, true);
            if (!is_array($data)) {
                $this->logUpstreamFailure('non-json', $http, $curlErrNo, $curlErrMsg, $elapsedMs, $requestId, $attempt, [
                    'resp_head' => mb_substr((string)$resp, 0, 300)
                ]);

                $shouldRetry = $isHttp5xx && $attempt < $maxAttempts;
                if ($shouldRetry) {
                    continue;
                }

                $this->recordUpstreamFailure('non-json');
                return $this->friendlyFallbackResult(
                    $question,
                    'upstream-error',
                    'non-json',
                    ['http_status' => $http, 'request_id' => $requestId]
                );
            }

            // 额外日志：看看工具调用情况（Responses 原始 JSON 字段）
            \Log::info('xAI raw response (truncated) => ' . mb_substr($resp ?? '', 0, 800));

            // —— 保存本轮 response_id，供下一轮续接
            if ($useThread && !empty($data['id']) && is_string($data['id'])) {
                $this->saveXaiPrevResponseId($data['id']);
            }

            // —— 提取文本 & 引用
            $text      = $this->xaiExtractText($data);
            $citations = $this->xaiExtractCitations($data);

            $toolCalls = $this->xaiExtractToolCalls($data);
            \Log::info('xAI tool_calls(extracted) => ' . json_encode($toolCalls, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            \Log::info('xAI citations(extracted)  => ' . json_encode($citations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            if ($text === '') {
                $this->logUpstreamFailure('empty-output', $http, $curlErrNo, $curlErrMsg, $elapsedMs, $requestId, $attempt);
                $this->recordUpstreamFailure('empty-output');
                return $this->friendlyFallbackResult(
                    $question,
                    'upstream-error',
                    'empty-output',
                    ['http_status' => $http, 'request_id' => $requestId]
                );
            }

            $this->resetUpstreamFailureWindow();

            return ['ok'=>true, 'text'=>$text, 'citations'=>$citations, 'raw'=>$data, 'source'=>'model'];
        }

        // 极端情况：循环未返回时兜底
        $this->recordUpstreamFailure('unknown');
        return $this->friendlyFallbackResult($question, 'upstream-error', 'unknown');
    }


    private function friendlyFallbackResult(string $question, string $replySource, string $errorType, array $meta = []): array
    {
        return [
            'ok'         => false,
            'text'       => $this->buildFallbackMessage($question),
            'citations'  => [],
            'raw'        => null,
            'source'     => $replySource,
            'error_type' => $errorType,
            'meta'       => $meta,
        ];
    }

    private function buildFallbackMessage(string $question): string
    {
        $lang = $this->detectFallbackLanguage($question);
        $messages = [
            'en' => "Sorry, I’m having trouble reaching the knowledge service right now. Please try again in a moment, or rephrase your question. If you want, tell me your country and goal and I’ll start with a quick overview.",
            'zh' => "抱歉，我现在连接服务有点不稳定。请稍后再试或换个问法。你也可以告诉我国家和目标，我先给你一个简要的方向。",
            'es' => "Lo siento, tengo problemas para conectar con el servicio de conocimiento. Intenta de nuevo en un momento o reformula tu pregunta. Si quieres, dime tu país y objetivo y te doy una visión rápida.",
            'fr' => "Désolé, j’ai du mal à joindre le service d’information. Réessaie dans un instant ou reformule ta question. Si tu veux, dis-moi ton pays et ton objectif et je te donne un aperçu rapide.",
            'pt' => "Desculpe, estou com dificuldade para acessar o serviço de conhecimento. Tente novamente em instantes ou reformule sua pergunta. Se quiser, diga-me seu país e objetivo e faço um resumo rápido.",
            'de' => "Entschuldigung, ich kann den Wissensdienst gerade nicht erreichen. Bitte versuche es gleich noch einmal oder formuliere deine Frage neu. Wenn du möchtest, nenne mir dein Land und dein Ziel, ich gebe dir einen kurzen Überblick.",
            'ja' => "申し訳ありません。知識サービスに接続しづらい状態です。少し待ってから再試行するか、質問を言い換えてください。国と目的を教えていただければ、まず簡単な概要をお伝えします。",
            'ko' => "죄송합니다. 지식 서비스 연결이 원활하지 않습니다. 잠시 후 다시 시도하거나 질문을 바꿔 주세요. 원하시면 국가와 목표를 알려주시면 먼저 간단히 안내드릴게요.",
            'ru' => "Извините, сейчас не удаётся подключиться к сервису знаний. Попробуйте позже или переформулируйте вопрос. Если хотите, скажите страну и цель, и я дам краткий обзор.",
            'ar' => "عذرًا، أواجه صعوبة في الوصول إلى خدمة المعرفة الآن. جرّب مرة أخرى بعد قليل أو أعد صياغة سؤالك. أخبرني ببلدك وهدفك وسأقدم لك ملخصًا سريعًا.",
        ];

        if (!isset($messages[$lang])) {
            $lang = 'en';
        }

        return $messages[$lang];
    }

    private function detectFallbackLanguage(string $question): string
    {
        $lower = mb_strtolower($question, 'UTF-8');

        if (preg_match('/[\x{4e00}-\x{9fff}]/u', $question)) return 'zh';
        if (preg_match('/[\x{3040}-\x{30ff}]/u', $question)) return 'ja';
        if (preg_match('/[\x{1100}-\x{11ff}\x{3130}-\x{318f}\x{ac00}-\x{d7af}]/u', $question)) return 'ko';
        if (preg_match('/[\x{0600}-\x{06ff}]/u', $question)) return 'ar';
        if (preg_match('/[\x{0400}-\x{04FF}]/u', $question)) return 'ru';
        if (preg_match('/[äöüß]/iu', $lower)) return 'de';
        if (preg_match('/[ãõáéíóúç]/iu', $lower)) return 'pt';
        if (preg_match('/[áéíóúñ¿¡]/iu', $lower)) return 'es';
        if (preg_match('/[àâçéèêëîïôûùü]/iu', $lower)) return 'fr';

        return 'en';
    }

    private function logUpstreamFailure(string $errorType, ?int $httpStatus, ?int $curlErrNo, ?string $curlErrMsg, int $elapsedMs, ?string $requestId, int $attempt, array $extra = []): void
    {
        $context = [
            'error_type'  => $errorType,
            'http_status' => $httpStatus,
            'curl_errno'  => $curlErrNo,
            'curl_error'  => $curlErrMsg,
            'elapsed_ms'  => $elapsedMs,
            'request_id'  => $requestId,
            'attempt'     => $attempt,
        ];

        if (!empty($extra)) {
            $context = array_merge($context, $extra);
        }

        \Log::error('xAI upstream failure', $context);
    }

    private function recordUpstreamFailure(string $errorType): void
    {
        $now          = microtime(true);
        $windowKey    = $this->failureWindowCacheKey();
        $breakerKey   = $this->breakerCacheKey();
        $windowSecs   = 120;
        $breakerSecs  = 120;
        $failures     = Cache::get($windowKey, []);
        if (!is_array($failures)) {
            $failures = [];
        }

        $failures[] = $now;
        $failures = array_values(array_filter($failures, function ($ts) use ($now, $windowSecs) {
            return ($now - (float)$ts) <= $windowSecs;
        }));

        Cache::put($windowKey, $failures, now()->addSeconds($windowSecs + 30));

        if (count($failures) >= 3) {
            Cache::put($breakerKey, $now + $breakerSecs, now()->addSeconds($breakerSecs + 5));
            \Log::warning('xAI circuit breaker opened', [
                'failures_last_2m' => count($failures),
                'last_error_type'  => $errorType,
            ]);
        }
    }

    private function resetUpstreamFailureWindow(): void
    {
        Cache::forget($this->failureWindowCacheKey());
    }

    private function isUpstreamCircuitOpen(): bool
    {
        $openUntil = Cache::get($this->breakerCacheKey());
        if (is_numeric($openUntil) && (float)$openUntil > microtime(true)) {
            return true;
        }

        if ($openUntil) {
            Cache::forget($this->breakerCacheKey());
        }

        return false;
    }

    private function failureWindowCacheKey(): string
    {
        return 'xai_responses_failures_window';
    }

    private function breakerCacheKey(): string
    {
        return 'xai_responses_breaker_until';
    }

    private function extractXaiRequestId($resp): ?string
    {
        if (!is_string($resp) || $resp === '') {
            return null;
        }

        $data = json_decode($resp, true);
        if (!is_array($data)) {
            return null;
        }

        if (!empty($data['id']) && is_string($data['id'])) {
            return $data['id'];
        }
        if (!empty($data['response_id']) && is_string($data['response_id'])) {
            return $data['response_id'];
        }
        if (!empty($data['response']['id']) && is_string($data['response']['id'])) {
            return $data['response']['id'];
        }
        if (!empty($data['error']['request_id']) && is_string($data['error']['request_id'])) {
            return $data['error']['request_id'];
        }

        return null;
    }

    private function xaiHttpPost(string $url, array $headers, array $payload): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
        ]);

        $resp   = curl_exec($ch);
        $http   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        return [$http, $resp, $curlErr];
    }


    /** 从 xAI Responses payload 中提取文本（兼容不同返回形态） */
    private function xaiExtractText(?array $data): string
    {
        if (!is_array($data)) return '';

        if (!empty($data['output_text']) && is_string($data['output_text'])) {
            return trim($data['output_text']);
        }

        $chunks = [];
        if (!empty($data['output']) && is_array($data['output'])) {
            foreach ($data['output'] as $block) {
                if (empty($block['content']) || !is_array($block['content'])) continue;
                foreach ($block['content'] as $seg) {
                    if (($seg['type'] ?? '') === 'output_text') {
                        $t = trim((string)($seg['text'] ?? ''));
                        if ($t !== '') $chunks[] = $t;
                    }
                }
            }
        }

        return trim(implode("\n", $chunks));
    }


    /** 提取引用/来源（若模型返回） */
    private function xaiExtractCitations(?array $data): array
    {
        if (!is_array($data)) return [];
        $out = [];

        $push = function($c) use (&$out) {
            $title = $c['title'] ?? '';
            $url   = $c['url']   ?? ($c['source_url'] ?? '');
            if ($title || $url) $out[] = ['title'=>$title, 'url'=>$url];
        };

        if (!empty($data['citations']) && is_array($data['citations'])) {
            foreach ($data['citations'] as $c) $push($c);
        }
        if (empty($out) && !empty($data['output'][0]['citations'])) {
            foreach ($data['output'][0]['citations'] as $c) $push($c);
        }
        if (empty($out) && !empty($data['response']['citations'])) {
            foreach ($data['response']['citations'] as $c) $push($c);
        }

        return $out;
    }

    /** 从 xAI Responses 里提取内置工具调用（file_search / web_search / collections_search） */
    private function xaiExtractToolCalls(?array $data): array
    {
        $calls = [];
        if (!is_array($data) || empty($data['output']) || !is_array($data['output'])) {
            return $calls;
        }

        foreach ($data['output'] as $block) {
            // 1) built-in 工具调用：type 形如 "file_search_call" / "web_search_call"
            if (!empty($block['type']) && is_string($block['type']) && substr($block['type'], -5) === '_call') {
                $calls[] = [
                    'type'      => $block['type'] ?? null,      // file_search_call / web_search_call
                    'name'      => $block['name'] ?? null,      // collections_search / web_search 等
                    'arguments' => $block['arguments'] ?? null, // 一般是 JSON string
                    'status'    => $block['status'] ?? null,
                    'id'        => $block['id'] ?? null,
                ];
            }

            // 2) 某些版本可能把工具调用塞在 block['tool_call'] 之类，这里顺手兜一下
            if (!empty($block['tool_call']) && is_array($block['tool_call'])) {
                $tc = $block['tool_call'];
                $calls[] = [
                    'type'      => $tc['type'] ?? null,
                    'name'      => $tc['name'] ?? null,
                    'arguments' => $tc['arguments'] ?? null,
                    'status'    => $tc['status'] ?? null,
                    'id'        => $tc['id'] ?? null,
                ];
            }
        }

        return $calls;
    }

    /** —— 会话续接：把上一次 response_id 存/取到 session —— */
    private function getXaiPrevResponseId(): ?string
    {
        $key = 'xai_prev_response_id:' . (($this->_current_member['id'] ?? 'guest') . ':' . session()->getId());
        return session($key) ?: null;
    }

    private function saveXaiPrevResponseId(?string $rid): void
    {
        $key = 'xai_prev_response_id:' . (($this->_current_member['id'] ?? 'guest') . ':' . session()->getId());
        if ($rid) session([$key => $rid]);
        else session()->forget($key);
    }

    public function resetXaiThread()
    {
        // 和 get/save 使用同一命名方式
        $key = 'xai_prev_response_id:' . (($this->_current_member['id'] ?? 'guest') . ':' . session()->getId());
        session()->forget($key);
        return response()->json(['status'=>200,'message'=>'xAI thread reset']);
    }

    public function chatStream()
{
    $question = request()->input('question', '');

    // Use a StreamedResponse so Laravel can handle streaming properly.
    return response()->stream(function() use ($question) {
        // Disable buffers and ensure immediate flush inside the stream.
        @ini_set('zlib.output_compression', 'Off');
        @ini_set('implicit_flush', 1);
        while (ob_get_level()) { @ob_end_flush(); }
        @ob_implicit_flush(true);
        ignore_user_abort(true);
        set_time_limit(0);

        // Basic validations
        if (trim($question) === '') {
            echo "data: " . json_encode(['error' => 'Empty question']) . "\n\n";
            flush();
            return;
        }

        $apiKey = env('XAI_API_KEY');
        if (!$apiKey) {
            $this->streamError('API key missing');
        }

        // Apply same validations as regular chat()
        $member = $this->_current_member;
        $guestId = $this->getMyCookie('guest_id');

        if (empty($member)) {
            $guestAskCount = 0;
            if ($this->chatLogHasGuestId()) {
                $guestAskCount = \DB::table('chat_log')
                    ->whereNull('member_id')
                    ->where('guest_id', $guestId)
                    ->where('type', 'ask')
                    ->count();
            }

            if ($guestAskCount >= 3) {
                echo "data: " . json_encode(['content' => 'Looks like you\'ve used up your free questions. Please register or log in.']) . "\n\n";
                echo "data: [DONE]\n\n";
                flush();
                return;
            }
        }

        // Use Responses API (RAG-enabled) and stream the final text back to the client
        $systemPrompt = "
You are AI-mmi, specialised in immigration and visa queries.

## Identity & Naming
- Always refer to yourself as “AI-mmi”.
- Never mention xAI, Grok, LLM, model, provider names or any tool names in the answer.
- Do NOT mention things like file_search, collections_search, web_search, vector stores, file IDs or collection IDs.
- Do NOT output citations, file IDs, collection IDs or any other technical identifiers in user-visible text.

## Language Behaviour (auto follow user language)
- Detect the language of the user's latest message and reply in that same language.
- If the user writes in Simplified Chinese, reply in Simplified Chinese.
- If the user writes in Traditional Chinese, reply in Traditional Chinese.
- If the user writes in Japanese, reply in Japanese.
- For any other language (e.g. French, Spanish, etc.), mirror that language naturally.
- If the user mixes languages, default to the language used in the last sentence that contains the real question.

## Greeting Behaviour (only first reply)
- Only when this is the FIRST reply in a new conversation thread, start with a short greeting + self-introduction.
- First reply greeting templates:
    - English: “Hi this is AI-mmi, your smart education and migration assistant.”
    - Simplified Chinese: “您好！我是 AI-mmi，您的智能留学和移民助理。”
    - Traditional Chinese: “您好！我是 AI-mmi，您的智能留學和移民助理。”
    - Other languages: translate the English sentence naturally into that language.
- In all later replies in the SAME conversation:
    - Do NOT introduce yourself again.
    - Normally do NOT add extra greetings; answer the question directly.
- If the user only says something like “thank you”, “谢谢”, “多謝”, you may respond with a short friendly closing sentence in the same language, without re-introducing yourself.

### INTERNAL KNOWLEDGE RULES
- When internal collections are available, you MUST first attempt to retrieve internal policy files.
- If you used internal collections, you may add one natural-language sentence such as:
'The above information is based on data retrieved from AI-mmi's internal repository.'
or equivalent wording in the user's language.
- If no relevant internal document is found, fall back to public web sources.
- If public sources are used, you may optionally state in natural language:
“本次仅使用公开资料（未使用 AI-mmi 内部资料库）。”
or equivalent wording in the user's language.
- DO NOT fabricate or guess internal documents or policies.

### CITATION & TECHNICAL INFO
- Citations, technical IDs, raw metadata are for internal logging only.
- They MUST NOT appear in user-visible output.

### RESPONSE STYLE
- Provide precise, structured, factual information.
- Keep the tone professional, clear and user-friendly.
";

        $lang = $this->detectLangZhOrEn($question);
        $cacheTtl = (int)env('XAI_CHAT_CACHE_TTL', 600);
        $cacheKey = 'xai_chat_cache:' . md5($lang . '|' . trim($question));
        if ($cacheTtl > 0 && Cache::has($cacheKey)) {
            $cachedReply = (string)Cache::get($cacheKey);
            if (trim($cachedReply) !== '') {
                $this->streamMessage($cachedReply);
                return;
            }
        }

        $x = $this->callXaiResponses($question, [
            'temperature' => 0.2,
            'max_output_tokens' => (int)env('XAI_MAX_OUTPUT_TOKENS', 1024),
            'model' => 'grok-4-1-fast-reasoning',
            'enable_search'     => (bool)env('XAI_ENABLE_WEB_SEARCH', false),
            'file_search_max'   => (int)env('XAI_FILE_SEARCH_MAX', 8),
            'collection_ids'   => ['collection_1c89e82d-3b05-4bb6-9bf7-aae3181a3a9c'],
            'vector_store_ids' => [],
            'system' => $systemPrompt,
        ]);

        $reply = '';
        if (!is_array($x) || empty($x['text'])) {
            $reply = is_array($x) ? ($x['text'] ?? '') : (string)$x;
        } else {
            $reply = $x['text'];
        }

        $reply = $this->processFinalText($reply, false, $lang);

        if (trim($reply) === '') {
            $this->streamError('Upstream empty response');
        }

        if ($cacheTtl > 0 && trim($reply) !== '') {
            Cache::put($cacheKey, $reply, $cacheTtl);
        }

        $this->streamMessage($reply);
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'X-Accel-Buffering' => 'no',
        'Connection' => 'keep-alive',
    ]);
}

// === STREAMING HELPER METHODS ===
private function streamError($message) {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    echo "data: " . json_encode(['error' => $message]) . "\n\n";
    echo "data: [DONE]\n\n";
    flush();
    exit();
}

private function streamMessage($message) {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    
    $delayMs = (int)env('XAI_STREAM_DELAY_MS', 0);
    if ($delayMs <= 0) {
        echo "data: " . json_encode([
            'choices' => [[
                'delta' => ['content' => $message]
            ]]
        ]) . "\n\n";
        flush();
    } else {
        // Stream the message word by word (like AI response)
        $words = explode(' ', $message);
        foreach ($words as $word) {
            echo "data: " . json_encode([
                'choices' => [[
                    'delta' => ['content' => $word . ' ']
                ]]
            ]) . "\n\n";
            flush();
            usleep($delayMs * 1000);
        }
    }
    
    echo "data: [DONE]\n\n";
    flush();
    exit();
}

private function processFinalText($text, $isFromQa, $lang) {
    // Apply same post-processing as chat()
    $processed = preg_replace(
        '/信息基于xAI内部集合检索的文件/u',
        '信息基于AI-mmi内部集合检索的资料',
        $text
    );
    
    // Add QA CTA if needed (same as chat())
    if ($isFromQa) {
        $processed = $this->appendQaCta($processed, $lang);
    }
    
    // Truncate if too long (same as chat())
    if (mb_strlen($processed, 'UTF-8') > 8000) {
        $processed = $this->truncateAtSentence($processed, 8000);
    }
    
    return trim($processed);
}

private function truncateAtSentence(string $text, int $maxChars): string
{
    $trimmed = trim($text);
    if ($maxChars <= 0 || mb_strlen($trimmed, 'UTF-8') <= $maxChars) {
        return $trimmed;
    }

    $slice = mb_substr($trimmed, 0, $maxChars, 'UTF-8');
    $punct = ['.', '!', '?', '。', '！', '？'];
    $lastPos = -1;
    $len = mb_strlen($slice, 'UTF-8');
    for ($i = 0; $i < $len; $i++) {
        $ch = mb_substr($slice, $i, 1, 'UTF-8');
        if (!in_array($ch, $punct, true)) {
            continue;
        }
        $next = ($i + 1 < $len) ? mb_substr($slice, $i + 1, 1, 'UTF-8') : '';
        if ($next === '' || preg_match('/\s/u', $next)) {
            $lastPos = $i;
        }
    }

    if ($lastPos >= 0 && $lastPos > (int)($maxChars * 0.6)) {
        return trim(mb_substr($slice, 0, $lastPos + 1, 'UTF-8'));
    }

    $spacePos = mb_strrpos($slice, ' ', 0, 'UTF-8');
    if ($spacePos !== false && $spacePos > 0) {
        return trim(mb_substr($slice, 0, $spacePos, 'UTF-8'));
    }

    return trim($slice);
}

private function saveStreamedReply($fullText, $question, $memberId, $guestId, $sessionId, $logToChatTable, $chatSource) {
    try {
        if (!$logToChatTable) {
            return;
        }
        
        $askId = $this->getSession('current_streaming_ask_id');
        if (!$askId) {
            // If no ask ID in session, create new one
            $nowUtc = \Carbon\Carbon::now('UTC');
            $targetDate = (int)date('Ymd');
            
            $askId = \DB::table('chat_log')->insertGetId([
                'member_id'   => $memberId,
                'guest_id'    => $guestId,
                'session_id'  => $sessionId,
                'related_id'  => 0,
                'target_date' => $targetDate,
                'type'        => 'ask',
                'content'     => $question,
                'status'      => 1,
                'is_streaming' => 1,
                'created_at'  => $nowUtc,
                'updated_at'  => $nowUtc,
            ]);
            \DB::table('chat_log')->where('id', $askId)->update(['related_id' => $askId]);
        }
        
        $nowUtc = \Carbon\Carbon::now('UTC');
        $targetDate = (int)date('Ymd');
        
        $replyPayload = [
            'member_id'   => $memberId,
            'guest_id'    => $guestId,
            'session_id'  => $sessionId,
            'related_id'  => $askId,
            'target_date' => $targetDate,
            'type'        => 'reply',
            'content'     => $fullText,
            'status'      => 1,
            'is_streaming' => 1,
            'created_at'  => $nowUtc,
            'updated_at'  => $nowUtc,
            'reply_source' => 'model',
        ];
        
        \DB::table('chat_log')->insert($replyPayload);
        
        $this->delSession('current_streaming_ask_id');
        
    } catch (\Exception $e) {
        \Log::error('Stream reply save failed: ' . $e->getMessage());
    }
}

private function buildLimitReply(string $question): string
{
    // 专门的 system prompt：只生成限制提示，不回答问题
    $system = "
You are AI-mmi.

The user has reached their free Q&A limit.
Your task:

- Detect the language of the user's message.
- Reply ONLY in that same language.
- Do NOT answer the user's immigration or visa question.
- Instead, give ONE short, polite sentence with this meaning:
'Looks like you've used up your free questions for now.
If you'd like to continue, just register or log in — it only takes a moment.'
- Do not mention xAI, Grok, LLM, tools, providers, or technical details.
- Do not add extra explanations, headings or formatting.
- Do not introduce yourself again unless the user explicitly asks who you are.
";

    $x = $this->callXaiResponses($question, [
        'temperature'       => 0.2,
        'max_output_tokens' => 128,
        'model'             => 'grok-4-1-fast-reasoning',
        'enable_search'     => false,           // 不需要联网/RAG
        'collection_ids'    => [],             // 不用内部库
        'system'            => $system,
    ]);

    if (is_array($x) && !empty($x['ok']) && !empty($x['text'])) {
        return trim($x['text']);
    }

    // 兜底：万一上游挂了，至少有一条英文提示
    return "Looks like you've used up your free questions for now.
        If you'd like to continue, just register or log in — it only takes a moment.";
    }

private function buildPaidPlanLimitReply(string $question): string
{
    $system = "
You are AI-mmi.

The user is a FREE PLAN user and has exceeded their 5-message limit.
Your job:

1. Detect the user's language.
2. Provide a VERY SHORT (2–3 lines) general non-specific statement (do NOT answer the visa question).
3. Then append this upgrade message translated into the user's language:

'You've reached your free chat limit 😊
I now provide general information only.
For more detailed guidance and access to our full planning and comparison tools, please upgrade to a paid plan.
You'll enjoy unlimited Q&A, updated information, personalized tools, and huge savings in time and cost.
👉 Click Upgrade to continue your journey.'

Rules:
- Do NOT mention xAI, Grok, LLM, provider names or tools.
- No citations or IDs.
- Output ONE block of text only.
";

    $x = $this->callXaiResponses($question, [
        'temperature'       => 0.25,
        'max_output_tokens' => 256,
        'model'             => 'grok-4-1-fast-reasoning',
        'enable_search'     => false,
        'collection_ids'    => [],
        'system'            => $system,
    ]);

    if (is_array($x) && !empty($x['ok']) && !empty($x['text'])) {
        return trim($x['text']);
    }

    // fallback
    return "You've reached your free chat limit. Please upgrade to continue.";
}

private function classifyEducationIntent(string $question): string
{
    $q = mb_strtolower(trim($question), 'UTF-8');
    if ($q === '') {
        return 'non-education';
    }

    $keywords = [
        'study', 'studying', 'student', 'university', 'college', 'course', 'program',
        'major', 'degree', 'tuition', 'scholarship', 'admission', 'apply', 'application',
        'campus', 'intake', 'enrol', 'enroll',
        // Chinese
        '留学', '学习', '学校', '大学', '学院', '课程', '专业', '学位', '学费', '奖学金', '申请', '入学'
    ];

    foreach ($keywords as $kw) {
        if (mb_strpos($q, $kw) !== false) {
            return 'education';
        }
    }

    return 'non-education';
}

private function detectLangZhOrEn(string $q): string
{
    return preg_match('/[\x{4e00}-\x{9fff}]/u', $q) ? 'zh' : 'en';
}

private function isScholarshipOrPartnerSchoolQuestion(string $q): bool
{
    $qLower = mb_strtolower($q, 'UTF-8');
    $qLower = str_replace(['—', '–', '－'], '-', $qLower);

    $keywords = [
        // Scholarship / AI-mmi
        'ai-mmi scholarship',
        'ai mmi scholarship',
        'scholarship',
        '奖学金',
        'ai-mmi奖学金',

        // Partner schools
        'sbta',
        'sela',
        'sbta–sela',
        'sbta-sela',
        'sbta sela',
        'queensland academy of technology',
        'qat',
        'australia college of tourism & information technology',
        'australia college of tourism and information technology',
        'acti',
        'queensland international institute',
        'qii',
        'rosehill college',
    ];

    foreach ($keywords as $kw) {
        $kwLower = mb_strtolower($kw, 'UTF-8');
        if (mb_strpos($qLower, $kwLower) !== false) {
            return true;
        }
    }

    return false;
}

private function qaOutOfScopeMessage(string $lang): string
{
    if ($lang === 'zh') {
        return '当前 Q&A 仅用于解答 AI-mmi Scholarship 及合作院校相关问题。请围绕奖学金或上述学校（SBTA–SELA、QAT、ACTI、QII、Rosehill College）提问。';
    }

    return 'This Q&A is reserved for questions about the AI-mmi Scholarship and our partner schools (SBTA–SELA, QAT, ACTI, QII, Rosehill College). Please ask about scholarships or the listed schools.';
}

private function appendQaCta(string $answer, string $lang): string
{
    $cta = $lang === 'zh'
        ? '欢迎使用位于POST卡片左下角的「Apply Now!」按钮通过AI-mmi快速申请，开启您的精彩留学之旅'
        : 'You\'re welcome to use the "Apply Now!" button at the bottom-left of this post card to submit your AI-mmi application and start your exciting study journey.';

    $trimmed = rtrim($answer);
    return $trimmed === '' ? $cta : $trimmed . "\n" . $cta;
 }
}
 // ← ADD THIS CLOSING BRACE FOR THE CLASS!