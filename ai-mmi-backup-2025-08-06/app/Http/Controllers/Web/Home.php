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

            $lastAi = \DB::table('chat_log')
                ->where('member_id', $this->_current_member['id'])
                ->where('type', 'reply')
                ->orderBy('id', 'desc')
                ->first();

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
                            'content'     => $$refusal,
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
            if ($new_reply === '' && $useRag === 1) {
                $rag = $this->callRagApi($rawQuestion, $__aimmi_label, $langCode); // <— 改动点：多传一个 tag
                if (is_string($rag) && trim($rag) !== '') {
                    $new_reply    = trim($rag);         // 仍保留 Markdown
                    $replySource  = 'rag-api';
                    $aiOwnerName  = 'AI-mmi (Policy)';
                    \Log::info('CHAT FLOW', ['case' => 'RAG API used', 'tag' => $__aimmi_label]);
                }
            }

            // ❸ 上述都失败 → 回落 Gemini（去 Markdown，返回纯文本）
            if ($new_reply === '') {
                $new_reply    = $this->callGeminiApi($rawQuestion, $has_subscription, $langCode);
                $replySource  = 'model';
                $aiOwnerName  = 'AI-mmi';
                \Log::info('CHAT FLOW', ['case' => 'Model generated']);
            }

            // —— 入库：ask / reply（同 related_id）——
            try {
                $nowUtc     = \Carbon\Carbon::now('UTC');
                $targetDate = (int)date('Ymd', strtotime($this->_today_date));

                \DB::beginTransaction();

                // ask
                $askId = \DB::table('chat_log')->insertGetId(array_filter([
                    'member_id'   => $memberId,
                    'guest_id'    => $guestId ?? null,
                    'session_id'  => $sessionId ?? null,
                    'related_id'  => 0,
                    'target_date' => $targetDate,
                    'type'        => 'ask',
                    'content'     => $rawQuestion,
                    'chat_mode' => $__aimmi_label,
                    'status'      => 1,
                    'created_at'  => $nowUtc,
                    'updated_at'  => $nowUtc,
                ]));
                \DB::table('chat_log')->where('id', $askId)->update(['related_id' => $askId]);

                if (!$member) {
                    $guestCount = ((int)$this->getSession('guest_chat_count')) + 1;
                    $this->setSession(['guest_chat_count' => $guestCount]);
                    $new_reply = rtrim((string)$new_reply) . "\n\n" . $signinHint;
                }

                // reply（RAG保留Markdown，Gemini已纯文本）
                \DB::table('chat_log')->insertGetId(array_filter([
                    'member_id'   => $memberId,
                    'guest_id'    => $guestId ?? null,
                    'session_id'  => $sessionId ?? null,
                    'related_id'  => $askId,
                    'target_date' => $targetDate,
                    'type'        => 'reply',
                    'content'     => $new_reply,
                    'chat_mode' => $__aimmi_label,
                    'status'      => 1,
                    'created_at'  => $nowUtc,
                    'updated_at'  => $nowUtc,
                ]));

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

    protected function callGeminiApi($question = '', $has_subscription = false, $lang = 'en') {
        if (empty($question)) return '';

        // ① 语言指令（只用传进来的 $lang，不再自动重判）
        if ($lang === 'zh-CN') {
            $langInstruction = "STRICT LANGUAGE: Answer ONLY in Simplified Chinese. Do not reply in English except for names or codes.";
        } elseif ($lang === 'zh-TW') {
            $langInstruction = "STRICT LANGUAGE: Answer ONLY in Traditional Chinese. Do not reply in English except for names or codes.";
        } else {
            $langInstruction = "STRICT LANGUAGE: Answer ONLY in English. Do not use Chinese characters.";
        }

        $system = $this->buildUnifiedPrompt($has_subscription)
                . "\n\n" . $langInstruction
                . "\n\nNOTE: Ignore conversation history language. Always follow STRICT LANGUAGE above.";

        // ② 当前用户（允许匿名）
        $member = $this->_current_member ?: null;

        // ③ 取历史并【按语言过滤 + 限制条数】避免语言漂移
        $history = collect();
        if (!empty($member)) {
            $history = DB::table('chat_log')
                ->where('member_id', $member['id'])
                ->orderBy('id', 'desc')
                ->limit(40)
                ->get()
                ->reverse();
        }

        // —— 语言过滤器：英文时尽量剔除含中文的历史；中文时保留含中文的历史 —— //
        $filtered = [];
        foreach ($history as $msg) {
            $t    = strtolower($msg->type ?? '');
            $role = ($t === 'reply') ? 'model' : 'user';
            $text = (string)($msg->content ?? '');
            if ($text === '') continue;

            // 截断过长
            if (mb_strlen($text) > 2000) $text = mb_substr($text, 0, 2000) . '...';

            $hasHan = (bool)preg_match('/\p{Han}/u', $text);
            if ($lang === 'en' && $hasHan) continue;           // 英文目标 → 去除中文历史
            if (($lang === 'zh-CN' || $lang === 'zh-TW') && !$hasHan) continue; // 中文目标 → 去除纯英文历史

            $filtered[] = ['role' => $role, 'parts' => [['text' => $text]]];
            if (count($filtered) >= 8) break; // 最多保留 8 条，避免上下文干扰
        }

        // ④ 组装 contents（过滤后的历史 + 当前问句）
        $contents = $filtered;
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
                        'maxOutputTokens'   => 2048,   // Enough for complete short answers
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

    ### 🟩 7. Next questions I need from you
    - 简短列出你需要补充的 3–5 个关键信息（如签证到期日/州/分数/英语/职业评估等）

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

    protected function callRagApi($question, $tag = 'policy', $lang = 'en')
    {
        $url = url('/api/rag/ask');
        $payload = json_encode(['q' => $question, 'tag' => $tag, 'lang' => $lang], JSON_UNESCAPED_UNICODE);

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
        if (preg_match('/^(yes|yeah|yep|sure|okay|ok|y|no|nope|nah|continue|go on|next|fine|great|thanks|thank you|好的|好|行|可以|是的|对|嗯|继续|可以的|沒問題|沒问题|好啊)$/iu', $raw)) {
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

        if (preg_match('/\p{Han}/u', $t)) {
            return preg_match('/[嗎麼為裏體臺國]/u', $t) ? 'zh-TW' : 'zh-CN';
        }

        if (preg_match('/[a-zA-Z]/', $t)) {
            if (mb_strlen($t) <= 5 && preg_match('/^(yes|ok|sure|yeah|y|pls|please)$/i', $t)) {
                $last = $this->getSession('aimmi_last_lang');
                if ($last) return $last;

                if ($memberId) {
                    $lastAsk = \DB::table('chat_log')
                        ->where('member_id', $memberId)->where('type', 'ask')
                        ->orderBy('id', 'desc')->first();
                    if ($lastAsk && preg_match('/\p{Han}/u', $lastAsk->content ?? '')) return 'zh-CN';
                }
                return 'en';
            }
            return 'en';
        }

        $last = $this->getSession('aimmi_last_lang');
        return $last ?: 'en';
    }



}
