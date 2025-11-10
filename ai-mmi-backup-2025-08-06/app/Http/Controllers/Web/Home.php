<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\QueryInput;
use Illuminate\Support\Facades\DB;
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
            // if (empty($this->_current_member)) {
            //     $this->pageResult([
            //         'status'  => 403,
            //         'message' => $this->_page_lang['please_login'],
            //         'url'     => $this->toURL('account_login'),
            //     ]);
            //     return;
            // }

            // —— 基本校验 ——
            $rawQuestion = trim((string)$question);
            if ($rawQuestion === '') {
                $this->pageResult([
                    'status'  => 400,
                    'message' => 'Please enter a question.',
                ]);
                return;
            }

            // —— 新增：访客上下文 + 引导语 + 超限拦截 —— //
            $member     = $this->_current_member ?: null;
            $memberId   = $member['id'] ?? null;
            $guestId    = $this->getMyCookie('guest_id');     // WebController 里已有 getMyCookie
            $sessionId  = session()->getId();
            $signinHint = 'To keep our chat going and explore your visa or study options through an instant eligibility check, please sign in first, it only takes a minute.';

            if (!$member) {
                $guestCount = (int) $this->getSession('guest_chat_count');
                if ($guestCount >= 3) {
                    // 第 4 次及以后：只返回引导语（不走 RAG/Model、不入库）
                    $nowUtcIso = \Carbon\Carbon::now('UTC')->toIso8601String();
                    $this->pageResult([
                        'status'               => 200,
                        'content'              => nl2br($rawQuestion),
                        'reply'                => $signinHint,
                        'answer_markdown'      => $signinHint,
                        'content_created_at'   => $nowUtcIso,
                        'reply_created_at'     => $nowUtcIso,
                        'member_owner_name'    => 'Guest',
                        'member_owner_avatar'  => 'asset/image/icon-member.png',
                        'ai_owner_name'        => 'AI-mmi',
                        'ai_owner_avatar'      => 'asset/image/logo-mmi.png',
                        'reply_source'         => 'guest-limit',
                        'flow_prompt'          => null,
                    ]);
                    return;
                }
            }

            /**
             * === Aimmi 领域守门（最小改动版，不改路由/不加中间件） ===
             * 命中 greeting：直接短回复
             * 不在允许域：直接拒绝并引导改写
             * 允许域：继续执行原有 RAG / Gemini 逻辑
             */
            $allowedDomains   = ['migration','education','relocation','accommodation','related_services'];
            $allowedSmalltalk = ['greeting'];

            $label = $this->aimmiQuickClassify($rawQuestion);

            // —— 语言判定（智能继承）——
            $langCode  = $this->detectLangSmart($rawQuestion, $memberId);

            // 存起来，供下一轮“yes/好的/嗯”继承使用
            $this->setSession(['aimmi_last_lang' => $langCode]);

            // 读取上次的领域（优先 session，其次 DB 最近一条）
            $lastLabel = $this->getSession('aimmi_last_domain') ?: $this->getLastDomainLabelFromHistory($memberId);

            if ($label === 'inherit') {
                $label = $lastLabel ?: 'migration';   // 没有就给默认
            }

            $lastAi = null;
            if ($memberId) {
                $lastAi = \DB::table('chat_log')
                    ->where('member_id', $memberId)
                    ->where('type', 'reply')
                    ->orderBy('id', 'desc')
                    ->first();
            }

            $aiIsAsking = $lastAi && (
                mb_stripos($lastAi->content, 'Next questions I need from you') !== false ||
                preg_match('/\?\s*$/u', trim(strip_tags($lastAi->content)))
            );

            if ($aiIsAsking && $label === 'reject') {
                $label = $lastLabel ?: 'migration';
            }

            // greeting：直接返回，不走 RAG/Gemini
            if (in_array($label, $allowedSmalltalk, true)) {
                $nowUtcIso = \Carbon\Carbon::now('UTC')->toIso8601String();

                // 入库 ask/reply（和你的原有结构保持一致）
                try {
                    $nowUtc     = \Carbon\Carbon::now('UTC');
                    $targetDate = (int)date('Ymd', strtotime($this->_today_date));

                    \DB::beginTransaction();
                    $askId = \DB::table('chat_log')->insertGetId(array_filter([
                        'member_id'   => $memberId,        // 允许为 null
                        'guest_id'    => $guestId ?? null, // 表里没有该字段也无妨
                        'session_id'  => $sessionId ?? null,
                        'related_id'  => 0,
                        'target_date' => $targetDate,
                        'type'        => 'ask',
                        'content'     => $rawQuestion,
                        'status'      => 1,
                        'created_at'  => $nowUtc,
                        'updated_at'  => $nowUtc,
                    ]));
                    \DB::table('chat_log')->where('id', $askId)->update(['related_id' => $askId]);

                    $greetReply = "Hi, I'm Aimmi. I specialize in immigration, study abroad, relocation, and rental housing matters. Just tell me your situation, and I'll provide you with a checklist of recommendations. 😊";

                    // —— 未登录：第 1～3 次在尾部追加引导语，并自增计数 —— //
                    if (!$member) {
                        $guestCount = ((int)$this->getSession('guest_chat_count')) + 1;
                        $this->setSession(['guest_chat_count' => $guestCount]);
                        $greetReply = rtrim($greetReply) . "\n\n" . $signinHint;
                    }

                    \DB::table('chat_log')->insertGetId(array_filter([
                        'member_id'   => $memberId,
                        'guest_id'    => $guestId ?? null,
                        'session_id'  => $sessionId ?? null,
                        'related_id'  => $askId,
                        'target_date' => $targetDate,
                        'type'        => 'reply',
                        'content'     => $greetReply,
                        'status'      => 1,
                        'created_at'  => $nowUtc,
                        'updated_at'  => $nowUtc,
                    ]));
                    \DB::commit();
                } catch (\Throwable $e) {
                    \DB::rollBack();
                    \Log::error('CHAT DB INSERT FAIL: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
                }

                if ($member) {
                    $member_owner_name   = $member['alias_name'];
                    $member_owner_avatar = 'asset/image/icon-member.png';
                    if (!empty($member['avatar'])) {
                        $member_owner_avatar = file_exists('upload/member_avatar/'.$member['avatar'])
                            ? 'upload/member_avatar/'.$member['avatar']
                            : 'upload/member_logo/'.$member['avatar'];
                    }
                } else {
                    $member_owner_name   = 'Guest';
                    $member_owner_avatar = 'asset/image/icon-member.png';
                }

                $this->pageResult([
                    'status'               => 200,
                    'content'              => nl2br($rawQuestion),
                    'reply'                => $greetReply,
                    'answer_markdown'      => $greetReply,
                    'content_created_at'   => $nowUtcIso,
                    'reply_created_at'     => $nowUtcIso,
                    'member_owner_name'    => $member_owner_name,
                    'member_owner_avatar'  => $member_owner_avatar,
                    'ai_owner_name'        => 'AI-mmi',
                    'ai_owner_avatar'      => 'asset/image/logo-mmi.png',
                    'reply_source'         => 'greeting',
                    'flow_prompt'          => null,
                ]);
                return;
            }

            // 非允许域：直接拒绝并引导改写
            if (!in_array($label, ['migration','education','relocation','accommodation','related_services'], true)) {
                // 若确实判到 reject，但用户输入很短（≤3词）→ 继承或放行
                if (!$lastLabel && str_word_count(preg_replace('/[^\p{L}\p{N} ]+/u',' ',$rawQuestion)) <= 3) {
                    $label = 'migration'; // 放一个温和默认
                } else if ($lastLabel) {
                    $label = $lastLabel;
                } else {
                    $nowUtcIso = \Carbon\Carbon::now('UTC')->toIso8601String();
                    $refusal = $this->aimmiRefusal();

                    if (!$member) {
                        $guestCount = ((int)$this->getSession('guest_chat_count')) + 1;
                        $this->setSession(['guest_chat_count' => $guestCount]);
                        $refusal = rtrim($refusal) . "\n\n" . $signinHint;
                    }

                    // 入库 ask/reply
                    try {
                        $nowUtc     = \Carbon\Carbon::now('UTC');
                        $targetDate = (int)date('Ymd', strtotime($this->_today_date));

                        \DB::beginTransaction();
                        $askId = \DB::table('chat_log')->insertGetId(array_filter([
                            'member_id'   => $memberId,        // 允许为 null
                            'guest_id'    => $guestId ?? null, // 表里没有该字段也无妨
                            'session_id'  => $sessionId ?? null,
                            'related_id'  => 0,
                            'target_date' => $targetDate,
                            'type'        => 'ask',
                            'content'     => $rawQuestion,
                            'status'      => 1,
                            'created_at'  => $nowUtc,
                            'updated_at'  => $nowUtc,
                        ]));
                        \DB::table('chat_log')->where('id', $askId)->update(['related_id' => $askId]);

                        \DB::table('chat_log')->insertGetId(array_filter([
                            'member_id'   => $memberId,
                            'guest_id'    => $guestId ?? null,
                            'session_id'  => $sessionId ?? null,
                            'related_id'  => $askId,
                            'target_date' => $targetDate,
                            'type'        => 'reply',
                            'content'     => $refusal,
                            'status'      => 1,
                            'created_at'  => $nowUtc,
                            'updated_at'  => $nowUtc,
                        ]));
                        \DB::commit();
                    } catch (\Throwable $e) {
                        \DB::rollBack();
                        \Log::error('CHAT DB INSERT FAIL: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
                    }

                    if ($member) {
                        $member_owner_name   = $member['alias_name'];
                        $member_owner_avatar = 'asset/image/icon-member.png';
                        if (!empty($member['avatar'])) {
                            $member_owner_avatar = file_exists('upload/member_avatar/'.$member['avatar'])
                                ? 'upload/member_avatar/'.$member['avatar']
                                : 'upload/member_logo/'.$member['avatar'];
                        }
                    } else {
                        $member_owner_name   = 'Guest';
                        $member_owner_avatar = 'asset/image/icon-member.png';
                    }

                    $this->pageResult([
                        'status'               => 200,
                        'content'              => nl2br($rawQuestion),
                        'reply'                => $refusal,
                        'answer_markdown'      => $refusal,
                        'content_created_at'   => $nowUtcIso,
                        'reply_created_at'     => $nowUtcIso,
                        'member_owner_name'    => $member_owner_name,
                        'member_owner_avatar'  => $member_owner_avatar,
                        'ai_owner_name'        => 'AI-mmi',
                        'ai_owner_avatar'      => 'asset/image/logo-mmi.png',
                        'reply_source'         => 'refused',
                        'flow_prompt'          => null,
                    ]);
                    return;
                }
            }

            $this->setSession(['aimmi_last_domain' => $label]);
            // 命中允许域：可把域标签用于 RAG 过滤
            $__aimmi_label = $label;

            // —— 订阅信息（现阶段聊天无限制，但留接口）——
            $has_migration_sub   = !empty($this->_current_member['has_migration_subscription']);
            $has_education_sub   = !empty($this->_current_member['has_education_subscription']);
            $has_subscription    = $has_migration_sub || $has_education_sub;

            // === 承接「短確認」：承接上一輪主題 + 強制 RAG（若為移民） ===
            $isAck = (bool)preg_match('/^(yes|yeah|yep|sure|ok|okay|y|好的|好|行|可以|是的|對|對的|嗯|要|要的)$/iu', trim($rawQuestion));

            if ($isAck) {
                // 1) 承接主題（以 session/歷史中的上一輪為準）
                $label = $lastLabel ?: 'migration';
                $__aimmi_label = $label; // 覆蓋本輪 topic
                $this->setSession(['aimmi_last_domain' => $label]);

                // 2) 把「yes」改寫成可執行追問（避免 RAG 因問題太短無法生成）
                $followupMap = [
                    'zh-TW' => "請沿用上一輪主題「{$label}」，用繁體中文繼續回答：請給我**下一步的操作建議與清單**（步驟、文件、時間線），不要切換到其他主題。",
                    'zh-CN' => "请沿用上一轮主题「{$label}」，用简体中文继续回答：请给我**下一步的操作建议与清单**（步骤、材料、时间线），不要切换到其他主题。",
                    'en'    => "Continue on the previous topic '{$label}' in English: give me the **next steps checklist** (steps, docs, timeline) without changing topics.",
                ];
                $rawQuestion = $followupMap[$langCode] ?? $followupMap['en'];

                // 3) 若為移民主題 → 這一輪一律走 RAG
                if (in_array($label, ['migration'], true)) {
                    $useRag = 1;          // 覆蓋前端傳來的 use_rag
                    $override = '';       // 確保不誤用前端 override
                    \Log::info('FORCE_RAG on short-ack', ['label' => $label, 'lang' => $langCode]);
                }
            }

            // —— 先用 Gemini 做“国家/签证主题”分类 —— //
            $class = $this->classifyJurisdictionWithGemini($rawQuestion, $langCode);
            $countryCode = strtoupper((string)($class['country'] ?? 'UNKNOWN'));           // e.g., AU/CA/PT/US/UK/...
            $visaTokens  = array_values(array_unique((array)($class['visa_tokens'] ?? [])));
            $conf        = (float)($class['confidence'] ?? 0.0);

            // 把识别结果存到 session，便于后续 yes/ok 继承
            $this->setSession([
                'aimmi_last_topic'   => $__aimmi_label,
                'aimmi_last_country' => $countryCode !== 'UNKNOWN' ? $countryCode : $this->getSession('aimmi_last_country'),
                'aimmi_last_tokens'  => $visaTokens,
            ]);

            // —— 路由策略：只有 AU 才尝试 RAG（你目前的索引库仅覆盖澳洲）；其他国家直接 Gemini —— //
            $ragEligible = ($countryCode === 'AU');

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

            // ❷ use_rag=1 但没带文本 → 后端再请求一次 RAG（把域标签当 tag 传过去）
            if ($new_reply === '' && $useRag === 1 && $ragEligible) {
                // 如果你用直調：
                $rag = $this->callRagDirect($rawQuestion, $__aimmi_label, $langCode, $countryCode);

                $rag = is_string($rag) ? trim($rag) : '';

                // —— 判斷 RAG 是否“像樣”，否則回落模型 —— //
                $looksSubstantive = (mb_strlen($rag) >= 120);  // 長度閾值
                $denyRx = '/\b(i\s+don[’\']?t\s+know|not\s+found|insufficient\s+(?:context|information)|'
                        . 'no\s+(?:details|specific)|cannot\s+answer|context\s+(?:missing|lacks))\b/i';

                if ($looksSubstantive && !preg_match($denyRx, $rag)) {
                    // ✅ RAG 可用
                    $new_reply   = $rag;
                    $replySource = 'rag-direct'; // 或 'rag-api'
                    $aiOwnerName = 'AI-mmi (Policy)';
                    \Log::info('CHAT FLOW', ['case' => 'RAG accepted', 'tag' => $__aimmi_label, 'lang' => $langCode]);
                } else {
                    // ❌ RAG 不夠用 → 回落到 Gemini（保持語言與主題）
                    \Log::warning('RAG weak/denied, fallback to model', [
                        'len' => mb_strlen($rag), 'rag_head' => mb_substr($rag,0,80), 'lang' => $langCode, 'tag' => $__aimmi_label
                    ]);

                    $new_reply   = $this->callGeminiApi($rawQuestion, $has_subscription, $langCode);
                    $replySource = 'rag-fallback-model';
                    $aiOwnerName = 'AI-mmi';
                }
            }

            // ❸ 上述都失败 → 回落 Gemini（去 Markdown，返回纯文本）
            if ($new_reply === '') {
                $new_reply = $this->callGeminiApi($rawQuestion, $has_subscription, $langCode, $visaTokens, $countryCode, $conf);
                $replySource  = $ragEligible ? 'rag-fallback-model' : 'model';
                $aiOwnerName  = 'AI-mmi';
                \Log::info('CHAT FLOW', ['case' => 'Model generated']);
            }

            // —— 入库：ask / reply（同 related_id）——
            try {
                $nowUtc     = \Carbon\Carbon::now('UTC');
                $targetDate = (int)date('Ymd', strtotime($this->_today_date));

                \DB::beginTransaction();

                // ask
                $baseAsk = [
                'member_id'   => $memberId,
                'guest_id'    => $guestId ?? null,
                'session_id'  => $sessionId ?? null,
                'related_id'  => 0,
                'target_date' => $targetDate,
                'type'        => 'ask',
                'content'     => $rawQuestion,
                'status'      => 1,
                'created_at'  => $nowUtc,
                'updated_at'  => $nowUtc,
                ];

                $extraAsk = [];
                if (Schema::hasColumn('chat_log','chat_mode'))          $extraAsk['chat_mode']          = $__aimmi_label;
                if (Schema::hasColumn('chat_log','chat_mode_topic'))    $extraAsk['chat_mode_topic']    = $__aimmi_label;
                if (Schema::hasColumn('chat_log','chat_mode_country'))  $extraAsk['chat_mode_country']  = $countryCode;
                if (Schema::hasColumn('chat_log','chat_mode_tokens'))   $extraAsk['chat_mode_tokens']   = json_encode($visaTokens, JSON_UNESCAPED_UNICODE);

                $askId = \DB::table('chat_log')->insertGetId(array_filter($baseAsk + $extraAsk));
                \DB::table('chat_log')->where('id', $askId)->update(['related_id' => $askId]);

                if (!$member) {
                    $guestCount = ((int)$this->getSession('guest_chat_count')) + 1;
                    $this->setSession(['guest_chat_count' => $guestCount]);
                    $new_reply = rtrim((string)$new_reply) . "\n\n" . $signinHint;
                }

                // reply（RAG保留Markdown，Gemini已纯文本）
                $baseReply = [
                'member_id'   => $memberId,
                'guest_id'    => $guestId ?? null,
                'session_id'  => $sessionId ?? null,
                'related_id'  => $askId,
                'target_date' => $targetDate,
                'type'        => 'reply',
                'content'     => $new_reply,
                'status'      => 1,
                'created_at'  => $nowUtc,
                'updated_at'  => $nowUtc,
                ];

                $extraReply = [];
                if (Schema::hasColumn('chat_log','chat_mode'))          $extraReply['chat_mode']          = $__aimmi_label;
                if (Schema::hasColumn('chat_log','chat_mode_topic'))    $extraReply['chat_mode_topic']    = $__aimmi_label;
                if (Schema::hasColumn('chat_log','chat_mode_country'))  $extraReply['chat_mode_country']  = $countryCode;
                if (Schema::hasColumn('chat_log','chat_mode_tokens'))   $extraReply['chat_mode_tokens']   = json_encode($visaTokens, JSON_UNESCAPED_UNICODE);

                \DB::table('chat_log')->insertGetId(array_filter($baseReply + $extraReply));

                \DB::commit();
            } catch (\Throwable $e) {
                \DB::rollBack();
                \Log::error('CHAT DB INSERT FAIL: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }

            // —— 构造头像/昵称 ——
            if ($member) {
                $member_owner_name   = $member['alias_name'];
                $member_owner_avatar = 'asset/image/icon-member.png';
                if (!empty($member['avatar'])) {
                    $member_owner_avatar = file_exists('upload/member_avatar/'.$member['avatar'])
                        ? 'upload/member_avatar/'.$member['avatar']
                        : 'upload/member_logo/'.$member['avatar'];
                }
            } else {
                $member_owner_name   = 'Guest';
                $member_owner_avatar = 'asset/image/icon-member.png';
            }

            // —— ConversationFlow 可选（保底不中断）
            $flowPrompt = null;
            try {
                $flowService = new \App\Services\ConversationFlowService($memberId ?: 0);
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
                'content'              => nl2br($rawQuestion),
                'reply'                => $new_reply,          // RAG: markdown; Gemini: 纯文本
                'answer_markdown'      => $new_reply,
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

                    if (strtolower($m['type']) === 'ask') {
                        // 仅用户消息做 nl2br，保证他们手动回车能看到换行
                        $chat_message[$k]['content'] = nl2br($m['content']);
                    } else {
                        // AI 消息：保留原始 Markdown
                        $chat_message[$k]['content'] = $m['content'];
                    }

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

    protected function callGeminiApi($question='', $has_subscription=false, $lang='en', $tokens=[], $jurisdiction='UNKNOWN', $conf=0.0){
        if (empty($question)) return '';

        // ① 语言指令（只用传进来的 $lang，不再自动重判）
        if ($lang === 'zh-CN') {
            $langInstruction = "STRICT LANGUAGE: 回答时必须使用简体中文，除非涉及专有名词，不得混用英文。";
        } elseif ($lang === 'zh-TW') {
            $langInstruction = "STRICT LANGUAGE: 回答時必須使用繁體中文（Traditional Chinese），避免使用簡體字或英文，維持正式、禮貌、自然的書面語。";
        } else {
            $langInstruction = "STRICT LANGUAGE: Answer ONLY in English. Do not use Chinese characters.";
        }

        $system = $this->buildUnifiedPrompt($has_subscription)
            . "\n\nSTRICT LANGUAGE: "
            . ($lang==='zh-TW' ? "Answer ONLY in Traditional Chinese."
               : ($lang==='zh-CN' ? "Answer ONLY in Simplified Chinese." : "Answer ONLY in English."))
            . "\n\nJURISDICTION:\n"
            . "Primary country/region: {$jurisdiction}.\n"
            . "You must scope legal/policy statements to this jurisdiction. "
            . "If the question does not match this jurisdiction, briefly say so and ask the user to confirm the intended country.\n";

        $system .= "\n\n### CONVERSATION CONTEXT RULE ###\n"
        . "If the user gives a short or single-word reply (e.g., 'yes', 'no', 'Master', 'Australia'), "
        . "interpret it as an answer to the last question(s) you asked in the previous message. "
        . "Then continue the conversation accordingly, filling in that missing detail before proceeding. "
        . "Do not start a new unrelated topic unless the user clearly changes subject.\n";

        // ② 当前用户（允许匿名）
        $member = $this->_current_member ?: null;

        // ③ 新：按“主题/国家/tokens/语言”构建短历史，避免串题
        $topic   = (string)($this->getSession('aimmi_last_topic') ?? 'other');        // 例如 immigration / exam
        $country = (string)($this->getSession('aimmi_last_country') ?? 'UNKNOWN');    // 例如 AU / US / GLOBAL
        $ctxTokens = (array)($this->getSession('aimmi_last_tokens') ?? []);
        $guardTokens = !empty($tokens) ? $tokens : $ctxTokens;

        // —— 主题护栏（签证 tokens）—— //
        if (!empty($guardTokens)) {
            $only = implode(', ', array_slice(array_map('strval', $guardTokens), 0, 6));
            $system .= "\nTOPIC GUARD:\nFocus ONLY on these current visa/program topics: {$only}. "
                    .  "Do NOT bring up previous or unrelated visas unless the user explicitly asks to compare.\n";
        }

        $historyParts = [];
        if (!empty($member)) {
            $historyParts = $this->buildContextMessages(
                (int)$member['id'],
                $lang,
                $topic,
                $country,
                $guardTokens,   // ✅
                6
            );
        }

        // ④ 组装 contents（过滤后的历史 + 当前问句）
        $contents = $historyParts;
        $contents[] = ['role' => 'user', 'parts' => [['text' => $question]]];

        //  Send Request
        $apiKey = env('GEMINI_API_KEY');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

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
                        'temperature'       => 0.25,   // More creative/conversational
                        'maxOutputTokens'   => 4096,   // Enough for complete short answers
                        'topK'              => 40,
                        'topP'              => 0.9,
                        'candidateCount'    => 1,
                        'thinkingConfig'    => [
                            'includeThoughts' => false,
                            'thinkingBudget'  => 1024
                        ],

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
        $maxRetries = 1; // Increased from 2 to 3
        $retryDelay = 1; // Increased from 2 to 3 seconds
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
        return $answer;
    }

    protected function classifyJurisdictionWithGemini(string $question, string $lang='en'): array
    {
        $apiKey = env('GEMINI_API_KEY');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

        // 让模型只输出 JSON，避免胡写
        $system = <<<SYS
    You are a strict classifier. Output ONLY valid minified JSON on one line, no backticks, no commentary.
    Schema:
    {
    "country": "<ISO2 like AU/US/UK/CA/NZ/PT/SG/EU or UNKNOWN>",
    "country_name": "<plain english name or UNKNOWN>",
    "visa_tokens": ["<short tokens like 189","d7","suv","h1b", ...],
    "confidence": <0.0-1.0 float>
    }
    Rules:
    - Infer the most likely COUNTRY of immigration/visa question from the user text.
    - If unclear, pick "UNKNOWN" and keep confidence low (<=0.5).
    - Extract short tokens for visa/program names (e.g., "d7","suv","h1b","pgwp","189","partner").
    - DO NOT invent facts; this is classification only.
    - Return JSON only.
    SYS;

        // 语言提示可选（用不到也无妨）
        $contents = [
            ['role'=>'user','parts'=>[['text'=>$question]]]
        ];

        $body = [
            'systemInstruction'=>['parts'=>[['text'=>$system]]],
            'contents'=>$contents,
            'generationConfig'=>[
                'temperature'=>0.0,
                'maxOutputTokens'=>256,
                'responseMimeType'=>'application/json',
            ],
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL=>$url,
            CURLOPT_POST=>1,
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
            CURLOPT_POSTFIELDS=>json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT=>30,
        ]);
        $resp = curl_exec($ch);
        if (curl_errno($ch)) {
            \Log::error('Gemini classify CURL Error: '.curl_error($ch));
            curl_close($ch);
            return ['country'=>'UNKNOWN','country_name'=>'UNKNOWN','visa_tokens'=>[],'confidence'=>0.0];
        }
        curl_close($ch);

        $data = json_decode($resp, true);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        $json = json_decode($text, true);
        if (!is_array($json)) {
            \Log::warning('Classifier JSON parse failed', ['raw'=>$text]);
            return ['country'=>'UNKNOWN','country_name'=>'UNKNOWN','visa_tokens'=>[],'confidence'=>0.0];
        }
        // 兜底字段
        $json += ['country'=>'UNKNOWN','country_name'=>'UNKNOWN','visa_tokens'=>[],'confidence'=>0.0];
        return $json;
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
    You are **Aimmi**, a specialist agent for: Migration, Education, Relocation, Accommodation & related services.
    Your answers must be **actionable, tidy, and written in Markdown** with clear sections.
    Keep tone warm and professional.

    # Output Style (MANDATORY)
    - Use **Markdown headings** (#, ##, ###).
    - Prefer **bullet points and numbered steps** over long paragraphs.
    - Use simple **icons** for readability: 🟩 section markers, ✅ checklist ticks, ⚠️ cautions, ℹ️ notes.
    - When comparing options, provide a **compact table**.
    - Include **dates/versions** if policy is referenced.
    - End with **Next questions I need from you**（接下来需要的信息） to keep the conversation moving.

    # Aimmi Response Template
    ### 🟩 1. Quick Summary
    - 1–3 bullet points that answer the user's core question directly.

    ### 🟩 2. Your Options (if applicable)
    | Option | Who it suits | Key requirements | Pros | Cons |
    |---|---|---|---|---|

    ### 🟩 3. Step-by-Step Plan
    1. Step one …
    2. Step two …
    3. Step three …
    *(Keep steps short, imperative verbs. Max 6 steps.)*

    ### 🟩 4. Documents / Evidence Checklist
    ✅ Item 1  
    ✅ Item 2  
    ✅ Item 3

    ### 🟩 5. Timeline & Fees (if relevant)
    - Typical processing time: X–Y months
    - Key dates / validity: …
    - Government fees / third-party costs: …

    ### 🟩 6. Risks / Notes
    - ⚠️ Risk or trap 1
    - ℹ️ Important note 1

    ### 🟩 7. Next question I need from you  （只問 1 個 / ONLY ONE）
    - **Ask exactly ONE** concise, highest-priority question that unblocks the next step.
    - If multiple details are missing, **choose only the most critical one** and ask that single question.
    - Keep it short (≤ 20 words). Do not add extra questions or alternatives.

    # Style Examples
    - If user asks “485 还有 4 个月到期，如何规划 190/491？”
    - Provide a **quick summary**, then **steps**: Skills assessment → Points → State nomination → EOI → Lodge → Bridging visa。
    - If user asks “858 GTI 流程？”
    - Use **Application Process Summary** + **Benefits** + **Typical Evidence**（同截图风格）.

    # Length
    - Default: concise but complete (200–500 tokens).
    - If user says "详细" / "展开" / "more details", you may extend.

    # 多语言风格增强
    - If replying in English → use concise, natural tone.
    - If replying in Simplified Chinese → use简洁自然的中文口语表达。
    - If replying in Traditional Chinese → 用書面體、繁體字、口吻禮貌專業。

    PROMPT;
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

    protected function callRagApi($question, $tag = 'policy', $lang = 'en', $country=null)
    {
        $url = url('/api/rag/ask');
        $payload = ['q'=>$question, 'tag'=>$tag, 'lang'=>$lang];
        if (!empty($country)) $payload['country'] = $country;
        $payload = json_encode($payload, JSON_UNESCAPED_UNICODE);

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

    /**
     * 轻量关键词分类：返回 migration / education / relocation / accommodation / related_services / greeting / reject
     */
    private function aimmiQuickClassify(string $query): string
    {
        $raw = trim($query);
        if ($raw === '') return 'reject';

        $q = $this->aimmiNorm($raw); // 统一小写、去空白、半全角、符号

        // ★ 新增：短确认/继续
        if (preg_match('/^(yes|yeah|yep|sure|okay|ok|y|no|nope|nah|continue|go on|next|fine|great|thanks|thank you|好的|好|行|可以|是的|对|嗯|继续|可以的|沒問題|沒问题|好啊|要|需要)$/iu', $raw)) {
            return 'inherit';
        }

        // --- 1) 规则：签证子类号/核心缩写的正则直判（命中直接判 migration） ---
        $visaCode = '/\b(189|190|191|188|187|186|491|494|482|485|476|400|407|408|500|590|600|651|870|887|888|820|801|309|100|300|Bridging\s?A|Bridging\s?B|BVA|BVB|BVC)\b/i';
        if (preg_match($visaCode, $raw)) return 'migration';

        // 常见移民缩写/词干（英语/中文混搭）
        $hardMigration = [
            'eoi','ea','acs','vetassess','imt','imt','casa','pmsol','independent visa','skilled visa','tss','ens','rsms',
            'expression of interest','points test','skill assessment','skills assessment','skills-select','skillselect',
            'doha','dha','homeaffairs','immiaccount','vevo','genuine temporary entrant','gte','s56','hap id','coe (visa)','case officer',
            'parent visa','partner visa','bridging visa','condition 8105','condition8105','s48','section48','tourist visa','work visa','pr','permanent residence','permanent resident',
            '州担保','职业评估','邀约','获邀','加分','打分','eoi','打分表','职业清单','中长期清单','短期清单','提名','州担','境内外','过桥签','过桥a','过桥b','过桥c','补料','移民局','递交','下签'
        ];
        if ($this->aimmiTokenHit($q, $hardMigration)) return 'migration';

        // --- 2) 关键词表（更全） ---
        $rules = [
            'migration' => [
                // 中文
                '签证','移民','pr','永居','州担保','独立技术','雇主担保','临签','学签','工签','访客签','打分','职业清单','技术评估','雅思','pte','托福',
                '过桥','补料','获邀','邀约','体检','无犯罪','递交','下签','移民局','home affairs','immi','vevo','eoi','skill assessment','points','nomination',
                // 英文常见
                'visa','pr','immigration','migration','points test','occupation list','state nomination','invitation','genuine temporary entrant','case officer'
            ],
            'education' => [
                // 中文
                '留学','学校','大学','研究生','本科','学院','课程','专业','录取','offer','拒信','奖学金','wAm','gpa','均分','转学','免课','coE','cricos','aqf','esos',
                '语言班','直入','预科','diploma','学分减免','成绩单','推荐信','个人陈述','ps','sop','cv','portfolio',
                // 英文/缩写
                'admission','university','college','scholarship','conditional offer','unconditional offer','intake','ranking','uac','unsw','uq','qtac','vtac','atar','ielts','pte','toefl','duolingo','orientation'
            ],
            'relocation' => [
                // 中文
                '搬家','落地','入境','清关','海关申报','行李','行前清单','银行开户','电话卡','医保','税号','tfn','abn','mygov','super','养老金',
                '驾照','换证','州交通卡','交通卡','公交卡','医保卡','中心链接','福利','租车','买车过户',
                // 英文
                'relocation','settlement','customs','quarantine','declare','medicare','mygov','tfn','abn','super','centrelink','driver license','licence','go card','opal card','myki'
            ],
            'accommodation' => [
                // 中文
                '租房','看房','公寓','宿舍','合租','整租','房东','物业','中介费','租约','解约','转租','退租','退押金','押金','bond','inspection','break lease','租期','水电网','mould','发霉','虫害',
                '租客','租赁仲裁','仲裁','reIQ','rta','ncat','vcat',
                // 英文
                'tenancy','lease','tenant','landlord','property manager','inspection','bond','condition report','break lease','keys','realestate.com.au','domain.com.au'
            ],
            'related_services' => [
                // 中文
                '中介','代理','移民代理','顾问','体检','指定医院','保险','oshc','ovhc','公证','海牙','apostille','认证','翻译','naati','接机','落地服务','行前','打印','复印','快递',
                // 英文
                'agent','migration agent','provider','oshc','ovhc','apostille','notary','naati','translation','pickup','pre-departure'
            ],
            'greeting' => [
                'hello','hi','hey','thanks','thank you','good morning','good evening','good night',
                '你好','您好','嗨','在吗','谢谢','多谢','辛苦了','早上好','晚上好','晚安'
            ],
        ];

        // --- 3) 命中任一规则即归类（优先级：migration > education > relocation > accommodation > related_services > greeting）---
        $priority = ['migration','education','relocation','accommodation','related_services','greeting'];
        foreach ($priority as $label) {
            if ($this->aimmiTokenHit($q, $rules[$label])) return $label;
        }

        // --- 4) 兜底：混合启发（同时出现“租/lease/tenant”等）---
        if (preg_match('/(租|lease|tenant|landlord|inspection|bond)/u', $raw)) return 'accommodation';
        if (preg_match('/(tfn|abn|medicare|mygov|super|tax)/i', $raw)) return 'relocation';

        return 'reject';
    }

    /**
     * 归一化：小写、去空白、统一全/半角，去掉常见标点
     */
    private function aimmiNorm(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        // 全角转半角
        $map = ['　' => ' ', '（' => '(', '）' => ')', '，' => ',', '。' => '.', '：' => ':', '；' => ';', '！' => '!', '？' => '?', '－' => '-', '—' => '-', '／' => '/', '、' => ',', '“' => '"', '”' => '"', '‘' => "'", '’' => "'"];
        $s = strtr($s, $map);
        // 去多空白
        $s = preg_replace('/\s+/u', ' ', $s);
        // 去控制符
        $s = preg_replace('/[^\P{C}]+/u', '', $s);
        return trim($s);
    }

    /**
     * 轻量“分词命中” + 模糊：把文本按非字母数字分成 token，
     * 1) 直接 contains
     * 2) 或 levenshtein 距离 ≤ 1（容忍小拼写错误）
     */
    private function aimmiTokenHit(string $normalizedText, array $candidates): bool
    {
        if ($normalizedText === '') return false;
        // 切 token（保留中英文与数字）
        $tokens = preg_split('/[^a-z0-9\x{4e00}-\x{9fa5}]+/ui', $normalizedText, -1, PREG_SPLIT_NO_EMPTY);
        if (!$tokens) $tokens = [$normalizedText];

        foreach ($candidates as $kw) {
            $kw = $this->aimmiNorm($kw);
            if ($kw === '') continue;

            // 直接子串命中
            if (mb_strpos($normalizedText, $kw) !== false) return true;

            // token 级模糊（错 1 个字符以内）
            foreach ($tokens as $t) {
                if ($t === '' || abs(mb_strlen($t) - mb_strlen($kw)) > 1) continue;
                if (levenshtein($t, $kw) <= 1) return true;
            }
        }
        return false;
    }

    /**
     * 统一拒绝文案（Markdown）
     */
    private function aimmiRefusal(): string
    {
        return "Sorry, I'm **Aimmi**.\n"
            ."I’m here to help with topics like migration, education, relocation, accommodation, and related services. 
                Please ask something in those areas so I can assist you better.\n";
    }

    /**
     * 自动检测用户输入语言（中/繁/英）并决定 Aimmi 回复语言。
     * 支持 “用中文回答”“reply in English” 等强制指令。
     */
    private function detectUserLanguage(string $text): string
    {
        $t = trim($text);

        // 1️⃣ 检测“强制指定语言”的指令（优先级最高）
        if (preg_match('/(用中文(回答|回复)|in\s+Chinese|answer\s+in\s+Chinese)/i', $t)) {
            return 'zh-CN';
        }
        if (preg_match('/(用英文(回答|回复)|in\s+English|answer\s+in\s+English)/i', $t)) {
            return 'en';
        }
        if (preg_match('/(用繁体|繁體|traditional\s+Chinese)/i', $t)) {
            return 'zh-TW';
        }

        // 2️⃣ 自动检测中文/繁体
        if (preg_match('/[\x{4e00}-\x{9fff}]/u', $t)) {
            // 检测是否繁体（常见繁体字）
            if (preg_match('/[體驗龍灣臺灣獨學業舉專國處網頁灣會總愛聯]/u', $t)) {
                return 'zh-TW';
            }
            return 'zh-CN';
        }

        // 3️⃣ 默认英文
        return 'en';
    }

    private function getLastDomainLabelFromHistory(?int $memberId): ?string
    {
        if (!$memberId) return null;
        $row = \DB::table('chat_log')
            ->where('member_id', $memberId)
            ->whereIn('type', ['reply','ask'])
            ->whereNotNull('chat_mode')
            ->orderBy('id','desc')
            ->first();
        return $row->chat_mode ?? null;
    }

    private function detectLangSmart(string $text, ?int $memberId): string
    {
        $t = trim($text);

        // —— 强制指令优先 —— 
        if (preg_match('/(用繁體|繁體|繁体|traditional\s*chinese)/iu', $t)) return 'zh-TW';
        if (preg_match('/(用中文|中文)/iu', $t)) return 'zh-CN';
        if (preg_match('/(用英文|english)/iu', $t)) return 'en';

        // —— 含汉字：再分繁/简 —— 
        if (preg_match('/\p{Han}/u', $t)) {
            // 1) 常见繁体专属字 + 粤语常用字
            $twOnly = '體臺檯兩裏裡叢幹麵徵績穫蹤齡齒顏額項號錢鎖鐘鑰鑽鬧鬱與學術應於將為點檔簽證訊聯絡帳號登入下載課程專案條列辦理應該週顧歡樂麥麗';
            $yue    = '係喺咩嘅啱唔冇嗰嚟邊喺乜咗喎噉嘢囉啩噶嗰啲啲啲啲'; // 常见粤语口语字符
            if (preg_match('/['.$twOnly.$yue.']/u', $t)) return 'zh-TW';

            // 2) 常见繁体词
            if (preg_match('/(簽證|檔案|訊息|聯絡|帳?號|學位|學程|專業|應用|將會|於是|裏面|臺灣|台灣|週)/u', $t)) return 'zh-TW';

            // 默认：简体
            return 'zh-CN';
        }

        // —— 纯英文 —— 
        if (preg_match('/[a-zA-Z]/', $t)) {
            // 超短确认 → 继承上一轮语言
            if (mb_strlen($t) <= 5 && preg_match('/^(yes|ok|okay|sure|y)$/i', $t)) {
                $last = $this->getSession('aimmi_last_lang');
                if ($last) return $last;

                if ($memberId) {
                    $lastAsk = \DB::table('chat_log')
                        ->where('member_id', $memberId)->where('type','ask')
                        ->orderBy('id','desc')->first();
                    if ($lastAsk && preg_match('/\p{Han}/u', (string)$lastAsk->content)) {
                        // 简单根据是否出现繁体专属字决定承接 zh-TW 还是 zh-CN
                        return preg_match('/['.$twOnly.']/u', (string)$lastAsk->content) ? 'zh-TW' : 'zh-CN';
                    }
                }
            }
            return 'en';
        }

        // —— 其它：继承上一轮或默认英文 —— 
        $last = $this->getSession('aimmi_last_lang');
        return $last ?: 'en';
    }

    // 不再 HTTP 自调，直接用服務層生成 RAG 答案
    protected function callRagDirect(string $question, ?string $tag, string $lang, ?string $country = null): string
    {
        try {
            /** @var \App\Services\Rag\Embeddings $emb */
            /** @var \App\Services\Rag\Pinecone   $pc  */
            /** @var \App\Services\Rag\Generator  $gen */
            $emb = app(\App\Services\Rag\Embeddings::class);
            $pc  = app(\App\Services\Rag\Pinecone::class);
            $gen = app(\App\Services\Rag\Generator::class);

            // —— 下面這段邏輯與 RagController@ask 基本一致（簡化版）——
            $qvec    = $emb->embed($question);
            $filter  = $tag ? ['tag' => $tag] : null;
            $matches = $pc->query($qvec, 15, $filter);

            // 拼上下文
            $contexts = [];
            if (is_array($matches)) {
                foreach ($matches as $m) {
                    $contexts[] = (string)($m['metadata']['content'] ?? '');
                }
            }
            $contexts = array_filter($contexts, fn($x) => $x !== '');
            $context  = implode("\n---\n", $contexts);

            $answer = $gen->answerWithContext($question, $context);
            return (string)trim($answer);
        } catch (\Throwable $e) {
            \Log::error('callRagDirect failed: '.$e->getMessage());
            return '';
        }
    }

    protected function buildContextMessages(
        int $memberId,
        string $targetLang,
        string $topic,
        string $country,          // e.g., AU/US/PT/GLOBAL/UNKNOWN
        array $tokens,            // 当前轮的短 token（d7 / h1b / ielts …）
        int $max = 6
    ): array {
        // 读最近 40 条
        $rows = \DB::table('chat_log')
            ->where('member_id', $memberId)
            ->whereIn('type', ['ask','reply'])
            ->orderBy('id','desc')->limit(40)->get()->reverse();

        // 语言过滤：与你现在的做法一致
        $keep = [];
        $rxTokens = $tokens ? '/('.implode('|', array_map(fn($t)=>preg_quote($t,'/'), $tokens)).')/i' : null;

        // 计算 token Jaccard 相似度需要上一轮 tokens
        $lastTokens = (array)$this->getSession('aimmi_last_tokens');
        $overlap = count(array_intersect($tokens, $lastTokens));
        $union   = max(1, count(array_unique(array_merge($tokens,$lastTokens))));
        $jaccard = $overlap / $union;

        // 如果主题或国家切换、或 token 相似度太低 → 彻底不带历史
        $lastTopic   = (string)$this->getSession('aimmi_last_topic');
        $lastCountry = (string)$this->getSession('aimmi_last_country');
        if (($topic && $lastTopic && $topic !== $lastTopic)
            || ($country && $lastCountry && $country !== $lastCountry && $country !== 'GLOBAL' && $lastCountry !== 'GLOBAL')
            || ($jaccard < 0.34))
        {
            return [];
        }

        foreach ($rows as $r) {
            $t    = strtolower($r->type ?? '');
            $role = ($t === 'reply') ? 'model' : 'user';
            $text = (string)($r->content ?? '');
            if ($text === '') continue;

            // 语言过滤
            $hasHan = (bool)preg_match('/\p{Han}/u', $text);
            if ($targetLang === 'en' && $hasHan) continue;
            if (($targetLang === 'zh-CN' || $targetLang === 'zh-TW') && !$hasHan) continue;

            // 主题与国家过滤（允许 GLOBAL 互通）
            $rtopic   = (string)($r->chat_mode_topic ?? '');
            $rcountry = (string)($r->chat_mode_country ?? '');
            if ($rtopic !== '' && $topic !== '' && $rtopic !== $topic) continue;
            if ($country !== 'GLOBAL' && $rcountry !== '' && $rcountry !== 'GLOBAL' && $rcountry !== $country) continue;

            // token 过滤：若当前轮有 tokens，则历史必须至少命中其一
            if ($rxTokens && !preg_match($rxTokens, $text)) continue;

            $keep[] = ['role'=>$role, 'parts'=>[['text'=>mb_substr($text, 0, 2000)]]];
            if (count($keep) >= $max) break;
        }

        return $keep;
    }
}
