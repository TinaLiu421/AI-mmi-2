<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

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
            $guestId    = $this->getMyCookie('guest_id');
            $sessionId  = session()->getId();

            // 语言判定（智能继承）
            $langCode  = $this->detectLangSmart($rawQuestion, $memberId);
            $signinHint = $this->signinHintByLang($langCode);

            if (!$member) {
                $guestCount = (int) $this->getSession('guest_chat_count');
                if ($guestCount >= 3) {
                    $nowUtcIso = \Carbon\Carbon::now('UTC')->toIso8601String();
                    $reply = $this->appendFooterIfMissing($signinHint, $langCode);
                    $this->pageResult([
                        'status'               => 200,
                        'content'              => nl2br($rawQuestion),
                        'reply'                => $reply,
                        'answer_markdown'      => $reply,
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
             * === Aimmi 领域守门（最小改动版） ===
             * 命中 greeting：直接短回复
             * 不在允许域：直接拒绝并引导改写
             * 允许域：继续执行原有 RAG / Gemini 逻辑
             */
            $allowedDomains   = ['migration','education','relocation','accommodation','related_services'];
            $allowedSmalltalk = ['greeting'];

            $label = $this->aimmiQuickClassify($rawQuestion);

            // 存起来，供下一轮“yes/好的/嗯”继承使用
            $this->setSession(['aimmi_last_lang' => $langCode]);

            // 读取上次的领域（优先 session，其次 DB 最近一条）
            $lastLabel = $this->getSession('aimmi_last_domain') ?: $this->getLastDomainLabelFromHistory($memberId);

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

                // 入库 ask/reply
                try {
                    $nowUtc     = \Carbon\Carbon::now('UTC');
                    $targetDate = (int)date('Ymd', strtotime($this->_today_date));

                    \DB::beginTransaction();
                    $askId = \DB::table('chat_log')->insertGetId(array_filter([
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
                    ]));
                    \DB::table('chat_log')->where('id', $askId)->update(['related_id' => $askId]);

                    $greetReply = $this->greetingByLang($langCode);

                    if (!$member) {
                        $guestCount = ((int)$this->getSession('guest_chat_count')) + 1;
                        $this->setSession(['guest_chat_count' => $guestCount]);
                        $greetReply = rtrim($greetReply) . "\n\n" . $signinHint;
                    }
                    $greetReply = $this->appendFooterIfMissing($greetReply, $langCode);

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

                [$member_owner_name, $member_owner_avatar] = $this->ownerVisual($member);

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

            // 非允许域：拒绝
            if (!in_array($label, $allowedDomains, true)) {
                if (!$lastLabel && str_word_count(preg_replace('/[^\p{L}\p{N} ]+/u',' ',$rawQuestion)) <= 3) {
                    $label = 'migration';
                } else if ($lastLabel) {
                    $label = $lastLabel;
                } else {
                    $nowUtcIso = \Carbon\Carbon::now('UTC')->toIso8601String();
                    $refusal = $this->aimmiRefusal($langCode);

                    if (!$member) {
                        $guestCount = ((int)$this->getSession('guest_chat_count')) + 1;
                        $this->setSession(['guest_chat_count' => $guestCount]);
                        $refusal = rtrim($refusal) . "\n\n" . $signinHint;
                    }
                    $refusal = $this->appendFooterIfMissing($refusal, $langCode);

                    // 入库 ask/reply
                    try {
                        $nowUtc     = \Carbon\Carbon::now('UTC');
                        $targetDate = (int)date('Ymd', strtotime($this->_today_date));

                        \DB::beginTransaction();
                        $askId = \DB::table('chat_log')->insertGetId(array_filter([
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

                    [$member_owner_name, $member_owner_avatar] = $this->ownerVisual($member);

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
            $__aimmi_label = $label;

            // 订阅信息（预留）
            $has_migration_sub   = !empty($this->_current_member['has_migration_subscription']);
            $has_education_sub   = !empty($this->_current_member['has_education_subscription']);
            $has_subscription    = $has_migration_sub || $has_education_sub;

            // —— 仅此处识别短确认 —— //
            $isAck = (bool)preg_match('/^(yes|yeah|yep|sure|ok|okay|y|好的|好|行|可以|是的|對|对的|嗯|要|要的)$/iu', trim($rawQuestion));
            if ($isAck) {
                $label = $lastLabel ?: 'migration';
                $__aimmi_label = $label;
                $this->setSession(['aimmi_last_domain' => $label]);

                $followupMap = [
                    'zh-TW' => "請沿用上一輪主題「{$label}」，用繁體中文繼續回答：請給我**下一步的操作建議與清單**（步驟、文件、時間線），不要切換到其他主題。",
                    'zh-CN' => "请沿用上一轮主题「{$label}」，用简体中文继续回答：请给我**下一步的操作建议与清单**（步骤、材料、时间线），不要切换到其他主题。",
                    'en'    => "Continue on the previous topic '{$label}' in English: give me the **next steps checklist** (steps, docs, timeline) without changing topics.",
                ];
                $rawQuestion = $followupMap[$langCode] ?? $followupMap['en'];

                if (in_array($label, ['migration'], true)) {
                    $useRag = 1;
                    $override = '';
                    \Log::info('FORCE_RAG on short-ack', ['label' => $label, 'lang' => $langCode]);
                }
            }

            // —— 先做国家/签证主题分类 —— //
            $class = $this->classifyJurisdictionWithGemini($rawQuestion, $langCode);
            $countryCode = strtoupper((string)($class['country'] ?? 'UNKNOWN'));
            $visaTokens  = array_values(array_unique((array)($class['visa_tokens'] ?? [])));
            $conf        = (float)($class['confidence'] ?? 0.0);

            $this->setSession([
                'aimmi_last_topic'   => $__aimmi_label,
                'aimmi_last_country' => $countryCode !== 'UNKNOWN' ? $countryCode : $this->getSession('aimmi_last_country'),
                'aimmi_last_tokens'  => $visaTokens,
            ]);

            $ragEligible = ($countryCode === 'AU');

            $new_reply   = '';
            $replySource = 'model';
            $aiOwnerName = 'AI-mmi';
            $aiOwnerAvatar = 'asset/image/logo-mmi.png';

            // ❶ RAG override
            if ($useRag === 1 && trim($override) !== '') {
                $new_reply    = trim($override);
                $replySource  = 'rag-override';
                $aiOwnerName  = 'AI-mmi (Policy)';
                \Log::info('CHAT FLOW', ['case' => 'RAG override used']);
            }

            // ❷ 后端直调 RAG
            if ($new_reply === '' && $useRag === 1 && $ragEligible) {
                $rag = $this->callRagDirect($rawQuestion, $__aimmi_label, $langCode, $countryCode);
                $rag = is_string($rag) ? trim($rag) : '';

                $looksSubstantive = (mb_strlen($rag) >= 120);
                $denyRx = '/\b(i\s+don[’\']?t\s+know|not\s+found|insufficient\s+(?:context|information)|no\s+(?:details|specific)|cannot\s+answer|context\s+(?:missing|lacks))\b/i';

                if ($looksSubstantive && !preg_match($denyRx, $rag)) {
                    $new_reply   = $rag;
                    $replySource = 'rag-direct';
                    $aiOwnerName = 'AI-mmi (Policy)';
                    \Log::info('CHAT FLOW', ['case' => 'RAG accepted', 'tag' => $__aimmi_label, 'lang' => $langCode]);
                } else {
                    \Log::warning('RAG weak/denied, fallback to model', [
                        'len' => mb_strlen($rag), 'rag_head' => mb_substr($rag,0,80), 'lang' => $langCode, 'tag' => $__aimmi_label
                    ]);
                    $new_reply   = $this->callGeminiApi($rawQuestion, $has_subscription, $langCode, $visaTokens, $countryCode, $conf);
                    $replySource = 'rag-fallback-model';
                    $aiOwnerName = 'AI-mmi';
                }

                $new_reply = $this->appendFooterIfMissing($new_reply ?? '', $langCode);
            }

            // ❸ 直接模型
            if ($new_reply === '') {
                $new_reply = $this->callGeminiApi($rawQuestion, $has_subscription, $langCode, $visaTokens, $countryCode, $conf);
                $replySource  = $ragEligible ? 'rag-fallback-model' : 'model';
                $aiOwnerName  = 'AI-mmi';
                \Log::info('CHAT FLOW', ['case' => 'Model generated']);
            }

            $new_reply = $this->appendFooterIfMissing($new_reply ?? '', $langCode);

            // —— 入库：ask / reply —— 
            try {
                $nowUtc     = \Carbon\Carbon::now('UTC');
                $targetDate = (int)date('Ymd', strtotime($this->_today_date));

                \DB::beginTransaction();

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

            // —— 头像/昵称 —— 
            [$member_owner_name, $member_owner_avatar] = $this->ownerVisual($member);

            // —— ConversationFlow（容错） —— 
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

            $nowUtcIso = \Carbon\Carbon::now('UTC')->toIso8601String();
            $this->pageResult([
                'status'               => 200,
                'content'              => nl2br($rawQuestion),
                'reply'                => $new_reply,
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
                        $chat_message[$k]['content'] = nl2br($m['content']);
                    } else {
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

    protected function callGeminiApi($question='', $has_subscription=false, $lang='en', $tokens=[], $jurisdiction='UNKNOWN', $conf=0.0){
        if (empty($question)) return '';

        $system = $this->buildUnifiedPrompt($has_subscription)
            . "\n\nSTRICT LANGUAGE: "
            . ($lang==='zh-TW' ? "Answer ONLY in Traditional Chinese."
               : ($lang==='zh-CN' ? "Answer ONLY in Simplified Chinese." : "Answer ONLY in English."))
            . "\n\nJURISDICTION:\n"
            . "Primary country/region: {$jurisdiction}.\n"
            . "You must scope legal/policy statements to this jurisdiction. "
            . "If the question does not match this jurisdiction, briefly say so and ask the user to confirm the intended country.\n"
            . "\n### CONVERSATION CONTEXT RULE ###\n"
            . "If the user gives a short or single-word reply (e.g., 'yes', 'no', 'Master', 'Australia'), "
            . "interpret it as an answer to the last question(s) you asked in the previous message. "
            . "Then continue the conversation accordingly. Do not start a new unrelated topic.\n";

        $member = $this->_current_member ?: null;

        $topic   = (string)($this->getSession('aimmi_last_topic') ?? 'other');
        $country = (string)($this->getSession('aimmi_last_country') ?? 'UNKNOWN');
        $ctxTokens = (array)($this->getSession('aimmi_last_tokens') ?? []);
        $guardTokens = !empty($tokens) ? $tokens : $ctxTokens;

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
                $guardTokens,
                6
            );
        }

        // 组装 contents
        $contents = $historyParts;
        $contents[] = ['role' => 'user', 'parts' => [['text' => $question]]];

        $apiKey = env('GEMINI_API_KEY');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

        if (!$has_subscription) {
            $system .= "\n\n**IMPORTANT - FREE TIER USER:**\n"
                    . "Provide ONLY general guidance and overview information; avoid detailed, case-specific steps. "
                    . "Explain that Premium is required for tailored strategies and full checklists.\n";
        } else {
            $system .= "\n\n**PREMIUM SUBSCRIBER:**\n"
                    . "You may provide detailed, specific, step-by-step guidance and tailored strategies.\n";
        }

        $body = [
            'systemInstruction' => [
                'parts' => [['text' => $system]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature'       => 0.25,
                'maxOutputTokens'   => 4096,
                'topK'              => 40,
                'topP'              => 0.9,
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

        $maxRetries = 1;
        $retryDelay = 1;
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

            if (isset($data['error']) && $data['error']['code'] === 503) {
                \Log::warning("Gemini API overloaded (attempt {$attempt}/{$maxRetries}): " . json_encode($data['error']));
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                    continue;
                } else {
                    curl_close($ch);
                    return 'The AI service is currently busy. Please try again in a few moments.';
                }
            }
            break;
        }

        curl_close($ch);

        \Log::info('Gemini API Response: ' . substr($resp, 0, 500));

        if (isset($data['error'])) {
            \Log::error('Gemini API Error: ' . json_encode($data['error']));
            return '[Upstream Error] ' . ($data['error']['message'] ?? 'Unknown error');
        }

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

        $system = <<<SYS
You are a strict classifier. Output ONLY minified JSON on one line.
Schema: {"country":"<ISO2 or UNKNOWN>","country_name":"<name or UNKNOWN>","visa_tokens":["..."],"confidence":<0.0-1.0>}
Rules:
- Infer likely country from the text; else use "UNKNOWN" with confidence <=0.5.
- Extract short tokens for visas/programs ("189","d7","suv","h1b","pgwp","partner",...).
- No extra text; JSON only.
SYS;

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
        $json += ['country'=>'UNKNOWN','country_name'=>'UNKNOWN','visa_tokens'=>[],'confidence'=>0.0];
        return $json;
    }

    protected function buildUnifiedPrompt($has_subscription = false) {
        return <<<PROMPT
You are **Aimmi**, a specialist agent for: Migration, Education, Relocation, Accommodation & related services.
Your answers must be **actionable, tidy, and written in Markdown** with clear sections. Keep tone warm and professional.

# Output Style (MANDATORY)
- Use **Markdown headings** (#, ##, ###).
- Prefer **bullet points and numbered steps** over long paragraphs.
- Use simple **icons** for readability: 🟩 section markers, ✅ checklist ticks, ⚠️ cautions, ℹ️ notes.
- When comparing options, provide a **compact table**.
- Include **dates/versions** if policy is referenced.
- **不要**在结尾追问任何“后续问题”。若用户需要，会主动继续提问。

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

# Length
- Default: concise but complete (200–500 tokens). If user asks for more detail, you may extend.

# Multi-language
- English: concise, natural.
- Simplified Chinese: 简洁自然。
- Traditional Chinese: 書面體、禮貌專業。
PROMPT;
    }

    public function logRag(\Illuminate\Http\Request $request)
    {
        try {
            $member = $this->_current_member;
            if (empty($member)) {
                return response()->json(['status'=>403,'message'=>'please login'], 403);
            }

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

            $askRow = [
                'member_id'   => $member['id'],
                'related_id'  => 0,
                'target_date' => $targetDate,
                'type'        => 'ask',
                'content'     => $ask,
                'status'      => 1,
                'created_at'  => $nowUtc,
                'updated_at'  => $nowUtc,
            ];
            if (Schema::hasColumn('chat_log','chat_mode'))         $askRow['chat_mode']         = $mode;
            if (Schema::hasColumn('chat_log','chat_mode_topic'))   $askRow['chat_mode_topic']   = $mode;

            $askId = \DB::table('chat_log')->insertGetId($askRow);
            \DB::table('chat_log')->where('id', $askId)->update(['related_id' => $askId]);

            $replyRow = [
                'member_id'   => $member['id'],
                'related_id'  => $askId,
                'target_date' => $targetDate,
                'type'        => 'reply',
                'content'     => $reply,
                'status'      => 1,
                'created_at'  => $nowUtc,
                'updated_at'  => $nowUtc,
            ];
            if (Schema::hasColumn('chat_log','chat_mode'))         $replyRow['chat_mode']         = $mode;
            if (Schema::hasColumn('chat_log','chat_mode_topic'))   $replyRow['chat_mode_topic']   = $mode;

            $replyId = \DB::table('chat_log')->insertGetId($replyRow);

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

    /**
     * 轻量关键词分类：返回 migration / education / relocation / accommodation / related_services / greeting / reject
     * （已移除“短确认/继续”的承接，避免与 $isAck 重复）
     */
    private function aimmiQuickClassify(string $query): string
    {
        $raw = trim($query);
        if ($raw === '') return 'reject';

        $q = $this->aimmiNorm($raw);

        // --- 1) 直判签证代号 → migration ---
        $visaCode = '/\b(189|190|191|188|187|186|491|494|482|485|476|400|407|408|500|590|600|651|870|887|888|820|801|309|100|300|Bridging\s?A|Bridging\s?B|BVA|BVB|BVC)\b/i';
        if (preg_match($visaCode, $raw)) return 'migration';

        $hardMigration = [
            'eoi','ea','acs','vetassess','pmsol','independent visa','skilled visa','tss','ens','rsms',
            'expression of interest','points test','skill assessment','skills assessment','skillselect',
            'doha','dha','homeaffairs','immiaccount','vevo','genuine temporary entrant','gte','s56','hap id','coe (visa)','case officer',
            'parent visa','partner visa','bridging visa','condition 8105','condition8105','s48','section48','tourist visa','work visa','pr','permanent residence',
            '州担保','职业评估','邀约','获邀','加分','打分','职业清单','提名','州担','过桥','补料','移民局','递交','下签'
        ];
        if ($this->aimmiTokenHit($q, $hardMigration)) return 'migration';

        $rules = [
            'migration' => [
                '签证','移民','pr','永居','州担保','独立技术','雇主担保','临签','学签','工签','访客签','打分','职业清单','技术评估','雅思','pte','托福',
                '过桥','补料','获邀','邀约','体检','无犯罪','递交','下签','移民局','home affairs','immi','vevo','eoi','skill assessment','points','nomination',
                'visa','immigration','migration','points test','occupation list','state nomination','invitation','genuine temporary entrant','case officer'
            ],
            'education' => [
                '留学','学校','大学','研究生','本科','学院','课程','专业','录取','offer','拒信','奖学金','gpa','均分','转学','免课','coe','cricos','aqf','esos',
                '语言班','直入','预科','diploma','学分减免','成绩单','推荐信','个人陈述','ps','sop','cv','portfolio',
                'admission','university','college','scholarship','conditional offer','unconditional offer','intake','ranking','uac','unsw','uq','qtac','vtac','atar','ielts','pte','toefl','duolingo','orientation'
            ],
            'relocation' => [
                '搬家','落地','入境','清关','海关申报','行李','行前清单','银行开户','电话卡','医保','税号','tfn','abn','mygov','super','养老金',
                '驾照','换证','州交通卡','医保卡','中心链接','福利','租车','买车过户',
                'relocation','settlement','customs','quarantine','declare','medicare','mygov','tfn','abn','super','centrelink','driver license','licence','go card','opal card','myki'
            ],
            'accommodation' => [
                '租房','看房','公寓','宿舍','合租','整租','房东','物业','中介费','租约','解约','转租','退租','退押金','押金','bond','inspection','break lease','租期','水电网','mould','发霉','虫害',
                '租客','租赁仲裁','仲裁','reIQ','rta','ncat','vcat',
                'tenancy','lease','tenant','landlord','property manager','inspection','bond','condition report','break lease','keys'
            ],
            'related_services' => [
                '中介','代理','移民代理','顾问','体检','指定医院','保险','oshc','ovhc','公证','海牙','apostille','认证','翻译','naati','接机','落地服务','行前','打印','复印','快递',
                'agent','migration agent','provider','oshc','ovhc','apostille','notary','naati','translation','pickup','pre-departure'
            ],
            'greeting' => [
                'hello','hi','hey','thanks','thank you','good morning','good evening','good night',
                '你好','您好','嗨','在吗','谢谢','多谢','辛苦了','早上好','晚上好','晚安'
            ],
        ];

        $priority = ['migration','education','relocation','accommodation','related_services','greeting'];
        foreach ($priority as $label) {
            if ($this->aimmiTokenHit($q, $rules[$label])) return $label;
        }

        if (preg_match('/(租|lease|tenant|landlord|inspection|bond)/u', $raw)) return 'accommodation';
        if (preg_match('/(tfn|abn|medicare|mygov|super|tax)/i', $raw)) return 'relocation';

        return 'reject';
    }

    private function aimmiNorm(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $map = ['　' => ' ', '（' => '(', '）' => ')', '，' => ',', '。' => '.', '：' => ':', '；' => ';', '！' => '!', '？' => '?', '－' => '-', '—' => '-', '／' => '/', '、' => ',', '“' => '"', '”' => '"', '‘' => "'", '’' => "'"];
        $s = strtr($s, $map);
        $s = preg_replace('/\s+/u', ' ', $s);
        $s = preg_replace('/[^\P{C}]+/u', '', $s);
        return trim($s);
    }

    private function aimmiTokenHit(string $normalizedText, array $candidates): bool
    {
        if ($normalizedText === '') return false;
        $tokens = preg_split('/[^a-z0-9\x{4e00}-\x{9fa5}]+/ui', $normalizedText, -1, PREG_SPLIT_NO_EMPTY);
        if (!$tokens) $tokens = [$normalizedText];

        foreach ($candidates as $kw) {
            $kw = $this->aimmiNorm($kw);
            if ($kw === '') continue;

            if (mb_strpos($normalizedText, $kw) !== false) return true;

            foreach ($tokens as $t) {
                if ($t === '' || abs(mb_strlen($t) - mb_strlen($kw)) > 1) continue;
                if (levenshtein($t, $kw) <= 1) return true;
            }
        }
        return false;
    }

    private function aimmiRefusal(string $lang): string
    {
        switch ($lang) {
            case 'zh-TW':
                return "抱歉，我是 **Aimmi**。\n我主要協助：移民、留學、落地安置、租房與相關服務。請在這些領域內提問，我才能更好地幫到你。";
            case 'zh-CN':
                return "抱歉，我是 **Aimmi**。\n我主要帮助：移民、留学、落地安置、租房与相关服务。请在这些领域内提问，我才能更好地帮助你。";
            default:
                return "Sorry, I'm **Aimmi**.\nI help with migration, education, relocation, accommodation, and related services. Please ask within these areas so I can assist you better.";
        }
    }

    private function greetingByLang(string $lang): string
    {
        switch ($lang) {
            case 'zh-TW':
                return "哈囉，我是 Aimmi。我專長於移民、留學、落地安置與租房等問題。說說你的情況，我會給你一份建議清單。😊";
            case 'zh-CN':
                return "你好，我是 Aimmi。我专长于移民、留学、落地安置和租房等问题。说说你的情况，我会给你一份建议清单。😊";
            default:
                return "Hi, I'm Aimmi. I specialize in immigration, study abroad, relocation, and rental housing. Tell me your situation and I'll give you a quick checklist. 😊";
        }
    }

    private function detectUserLanguage(string $text): string
    {
        $t = trim($text);
        if (preg_match('/(用中文(回答|回复)|in\s+Chinese|answer\s+in\s+Chinese)/i', $t)) return 'zh-CN';
        if (preg_match('/(用英文(回答|回复)|in\s+English|answer\s+in\s+English)/i', $t)) return 'en';
        if (preg_match('/(用繁体|繁體|traditional\s+Chinese)/i', $t)) return 'zh-TW';

        if (preg_match('/[\x{4e00}-\x{9fff}]/u', $t)) {
            if (preg_match('/[體臺檯兩裏裡簽證檔訊聯絡帳號學將為週]/u', $t)) return 'zh-TW';
            return 'zh-CN';
        }
        return 'en';
    }

    private function getLastDomainLabelFromHistory(?int $memberId): ?string
    {
        if (!$memberId) return null;
        $row = \DB::table('chat_log')
            ->where('member_id', $memberId)
            ->whereIn('type', ['reply','ask'])
            ->orderBy('id','desc')
            ->first();
        if (!$row) return null;
        if (!empty($row->chat_mode_topic)) return (string)$row->chat_mode_topic;
        if (!empty($row->chat_mode))       return (string)$row->chat_mode;
        return null;
    }

    private function detectLangSmart(string $text, ?int $memberId): string
    {
        $t = trim($text);

        if (preg_match('/(用繁體|繁體|繁体|traditional\s*chinese)/iu', $t)) return 'zh-TW';
        if (preg_match('/(用中文|中文)/iu', $t)) return 'zh-CN';
        if (preg_match('/(用英文|english)/iu', $t)) return 'en';

        // —— 含汉字：再分繁/简 —— 
        if (preg_match('/\p{Han}/u', $t)) {
            // 粤語/繁體常見字（擴充，命中其一即判 zh-TW）
            $twYueChars = '體臺檯兩裏裡檔簽證訊聯絡帳號學歷應於將為點專案週聯係喺咩嘅啱唔冇嗰嚟邊啲囉噉嘢噃喇嘍嗱嚟';
            if (preg_match('/['.$twYueChars.']/u', $t)) return 'zh-TW';

            // 常見繁體詞
            if (preg_match('/(簽證|檔案|訊息|聯絡|帳?號|學位|學程|專業|應用|將會|於是|裏面|臺灣|台灣|週)/u', $t)) return 'zh-TW';

            // 默認簡體
            return 'zh-CN';
        }

        if (preg_match('/[a-zA-Z]/', $t)) {
            if (mb_strlen($t) <= 5 && preg_match('/^(yes|ok|okay|sure|y)$/i', $t)) {
                $last = $this->getSession('aimmi_last_lang');
                if ($last) return $last;
                if ($memberId) {
                    $lastAsk = \DB::table('chat_log')
                        ->where('member_id', $memberId)->where('type','ask')
                        ->orderBy('id','desc')->first();
                    if ($lastAsk && preg_match('/\p{Han}/u', (string)$lastAsk->content)) {
                        return preg_match('/['.$twOnly.']/u', (string)$lastAsk->content) ? 'zh-TW' : 'zh-CN';
                    }
                }
            }
            return 'en';
        }

        $last = $this->getSession('aimmi_last_lang');
        return $last ?: 'en';
    }

    // 不再 HTTP 自调，直接用服務層生成 RAG 答案（帶語言控制 & 尾注）
    protected function callRagDirect(string $question, ?string $tag, string $lang, ?string $country = null): string
    {
        try {
            /** @var \App\Services\Rag\Embeddings $emb */
            /** @var \App\Services\Rag\Pinecone   $pc  */
            /** @var \App\Services\Rag\Generator  $gen */
            $emb = app(\App\Services\Rag\Embeddings::class);
            $pc  = app(\App\Services\Rag\Pinecone::class);
            $gen = app(\App\Services\Rag\Generator::class);

            // ① 語言前綴（與 RagController@ask 一致）
            $prefix = $this->buildLangPrefix($lang);
            $qWithLang = $prefix . $question;

            // ② 檢索
            $qvec    = $emb->embed($qWithLang);
            $filter  = $tag ? ['tag' => $tag] : null;
            $matches = $pc->query($qvec, 15, $filter);

            // ③ 拼上下文
            $contexts = [];
            if (is_array($matches)) {
                foreach ($matches as $m) {
                    $contexts[] = (string)($m['metadata']['content'] ?? '');
                }
            }
            $contexts = array_filter($contexts, function($x){ return $x !== ''; });
            $context  = implode("\n---\n", $contexts);

            // ④ 生成
            $answer = (string)trim($gen->answerWithContext($qWithLang, $context));

            // ⑤ 補上多語言尾注（若缺失）
            $answer = $this->appendFooterIfMissing($answer, $lang);

            return $answer;
        } catch (\Throwable $e) {
            \Log::error('callRagDirect failed: '.$e->getMessage());
            return '';
        }
    }


    protected function buildContextMessages(
        int $memberId,
        string $targetLang,
        string $topic,
        string $country,
        array $tokens,
        int $max = 6
    ): array {
        $rows = \DB::table('chat_log')
            ->where('member_id', $memberId)
            ->whereIn('type', ['ask','reply'])
            ->orderBy('id','desc')->limit(40)->get()->reverse();

        $keep = [];
        $rxTokens = $tokens ? '/('.implode('|', array_map(fn($t)=>preg_quote($t,'/'), $tokens)).')/i' : null;

        $lastTokens = (array)$this->getSession('aimmi_last_tokens');
        $overlap = count(array_intersect($tokens, $lastTokens));
        $union   = max(1, count(array_unique(array_merge($tokens,$lastTokens))));
        $jaccard = $overlap / $union;

        $lastTopic   = (string)$this->getSession('aimmi_last_topic');
        $lastCountry = (string)$this->getSession('aimmi_last_country');
        if (($topic && $lastTopic && $topic !== $lastTopic)
            || ($country && $lastCountry && $country !== $lastCountry && $country !== 'GLOBAL' && $lastCountry !== 'GLOBAL')
            || ($jaccard < 0.20))
        {
            // 硬切换：不带历史
            return [];
        }

        foreach ($rows as $r) {
            $t    = strtolower($r->type ?? '');
            $role = ($t === 'reply') ? 'model' : 'user';
            $text = (string)($r->content ?? '');
            if ($text === '') continue;

            $hasHan = (bool)preg_match('/\p{Han}/u', $text);
            if ($targetLang === 'en' && $hasHan) continue;
            if (($targetLang === 'zh-CN' || $targetLang === 'zh-TW') && !$hasHan) continue;

            $rtopic   = (string)($r->chat_mode_topic ?? '');
            $rcountry = (string)($r->chat_mode_country ?? '');
            if ($rtopic !== '' && $topic !== '' && $rtopic !== $topic) continue;
            if ($country !== 'GLOBAL' && $rcountry !== '' && $rcountry !== 'GLOBAL' && $rcountry !== $country) continue;

            if ($rxTokens && !preg_match($rxTokens, $text)) continue;

            $keep[] = ['role'=>$role, 'parts'=>[['text'=>mb_substr($text, 0, 2000)]]];
            if (count($keep) >= $max) break;
        }

        // 兜底：如果严格过滤导致没有历史，给最近 2 条同语言，避免“承接 yes”时无上下文
        if (empty($keep) && $max > 0) {
            $fallback = [];
            foreach (array_reverse($rows->toArray()) as $r) {
                $text = (string)($r->content ?? '');
                if ($text === '') continue;
                $hasHan = (bool)preg_match('/\p{Han}/u', $text);
                if ($targetLang === 'en' && $hasHan) continue;
                if (($targetLang === 'zh-CN' || $targetLang === 'zh-TW') && !$hasHan) continue;

                $role = (strtolower($r->type ?? '') === 'reply') ? 'model' : 'user';
                $fallback[] = ['role'=>$role, 'parts'=>[['text'=>mb_substr($text,0,1200)]]];
                if (count($fallback) >= 2) break;
            }
            return $fallback;
        }

        return $keep;
    }

    private function aimmiFooterByLang(string $lang): string
    {
        switch ($lang) {
            case 'zh-TW':
                return "\n\n🟦 **AI-mmi也可能會犯錯。請核查重要資訊。**";
            case 'en':
                return "\n\n🟦 **AI-mmi may make mistakes. Please verify important information.**";
            default:
                return "\n\n🟦 **AI-mmi也可能会犯错。请核查重要信息。**";
        }
    }

    private function appendFooterIfMissing(string $text, string $lang): string
    {
        $hasCn = mb_strpos($text, '核查重要信息') !== false;
        $hasTw = mb_strpos($text, '核查重要資訊') !== false;
        $hasEn = (stripos($text, 'verify important information') !== false);

        if (!($hasCn || $hasTw || $hasEn)) {
            $text = rtrim($text) . $this->aimmiFooterByLang($lang);
        }
        return $text;
    }

    private function signinHintByLang(string $lang): string
    {
        switch ($lang) {
            case 'zh-TW':
                return "要繼續與我聊天並使用即時資格檢查，請先登入，僅需一分鐘即可完成。";
            case 'zh-CN':
                return "要继续与我聊天并使用即时资格检测，请先登录，只需一分钟即可完成。";
            default:
                return "To keep our chat going and run an instant eligibility check, please sign in first — it only takes a minute.";
        }
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

    private function buildLangPrefix(string $lang): string
    {
        if ($lang === 'zh-TW') {
            return "請用繁體中文回答下列問題，避免使用簡體字或英文，並維持正式、自然的語氣。\n\n";
        } elseif ($lang === 'zh-CN') {
            return "請用簡體中文回答下列問題，除非涉及專有名詞，否則不要混用英文。\n\n";
        }
        return "Please answer in English.\n\n";
    }

}
