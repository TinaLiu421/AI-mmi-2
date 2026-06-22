<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Services\ConversationFlowService;
use App\Services\TokenService;
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
            'show_highlight'    =>  1,
            'exclude_featured'  =>  true
        ]);

        if (empty($list_news['data'])) {
            $list_news = $this->loadModel('posts')->getAll(
            [
                'show_type'         =>  1,
                'show_page_size'    =>  10,
                'show_highlight'    =>  1,
                'exclude_featured'  =>  true
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
            'show_highlight'    =>  1,
            'exclude_featured'  =>  true
        ]);

        if (empty($list_events['data'])) {
            $list_events = $this->loadModel('posts')->getAll(
            [
                'show_type'         =>  2,
                'show_page_size'    =>  10,
                'show_highlight'    =>  1,
                'exclude_featured'  =>  true
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

        $agentLayoutAllowedEmails = ['admin@wealthskey.com', 'info@ai-mmi.com'];
        $currentEmail = mb_strtolower(trim((string)($this->_current_member['email'] ?? '')), 'UTF-8');
        $showAgentHomeLayout = in_array($currentEmail, $agentLayoutAllowedEmails, true);

        // featured posts — no slot limit, show all with featured_until > now
        $featured_posts_raw = $this->loadModel('posts')->getFeatured(50);
        $featured_is_fallback = false;
        if (empty($featured_posts_raw)) {
            $featured_posts_raw = $this->loadModel('posts')->getFeaturedFallback(10, 365);
            $featured_is_fallback = !empty($featured_posts_raw);
        }
        $featured_posts = [];
        if (!empty($featured_posts_raw)) {
            foreach ($featured_posts_raw as $fp) {
                if (empty($fp['title'])) {
                    $fp['title'] = mb_substr($this->toPlainText($fp['content']), 0, 40);
                }
                $fp['excerpt'] = $this->toPlainText($fp['content']);
                $fp['url'] = $this->toURL('posts/details/' . $fp['id']);
                if (!empty($fp['photo'])) {
                    $fp['thumbnail'] = 'upload/member_posts/' . $fp['photo'];
                } elseif (!empty($fp['youtube_url'])) {
                    // extract YouTube video ID and use its thumbnail
                    $yt_short = '/youtu\.be\/([a-zA-Z0-9_-]+)\??/i';
                    $yt_long  = '/youtube\.com\/((?:embed)|(?:watch)|(?:shorts))((?:\?v\=)|(?:\/))([a-zA-Z0-9_-]+)/i';
                    $yt_id = '';
                    if (preg_match($yt_long, $fp['youtube_url'], $m)) {
                        $yt_id = $m[count($m) - 1];
                    } elseif (preg_match($yt_short, $fp['youtube_url'], $m)) {
                        $yt_id = $m[count($m) - 1];
                    }
                    $fp['thumbnail'] = !empty($yt_id)
                        ? 'https://img.youtube.com/vi/' . $yt_id . '/hqdefault.jpg'
                        : $this->generateImage(null, 600, 380, true);
                } else {
                    $fp['thumbnail'] = $this->generateImage(null, 600, 380, true);
                }
                $fp['youtube_url'] = $this->getYoutubeEmbedUrl($fp['youtube_url']);
                $fp['is_featured_fallback'] = $featured_is_fallback;
                $featured_posts[] = $fp;
            }
        }

        // determine if current member already has a featured post (for upgrade CTA logic)
        $member_has_featured = false;
        if (!empty($this->_current_member['id'])) {
            foreach ($featured_posts as $fp) {
                if ((int)$fp['member_id'] === (int)$this->_current_member['id']) {
                    $member_has_featured = true;
                    break;
                }
            }
        }

        // Remove spotlighted posts from the regular news/events sliders so they
        // only appear in the dedicated Featured section above.
        $featured_post_ids = array_map('intval', array_column($featured_posts, 'id'));
        if (!empty($featured_post_ids)) {
            if (is_array($list_news)) {
                $list_news = array_values(array_filter($list_news, function ($n) use ($featured_post_ids) {
                    return !in_array((int)$n['id'], $featured_post_ids, true);
                }));
                if (empty($list_news)) { $list_news = false; }
            }
            if (is_array($list_events)) {
                $list_events = array_values(array_filter($list_events, function ($e) use ($featured_post_ids) {
                    return !in_array((int)$e['id'], $featured_post_ids, true);
                }));
                if (empty($list_events)) { $list_events = false; }
            }
        }

        $sq = $this->loadModel('spotlight_queue');
        $sq->expireActive();
        $sq->activateNext();

        $is_spotlight_manager = !empty($this->_current_member['spotlight_manager']);
        $spotlight_queue_overview = [];
        if ($is_spotlight_manager) {
            $spotlight_queue_overview = $sq->getAdminOverview();
        }

        return $this->pageData(
        [
            'details'               =>  $home_page_data,
            'list_news'             =>  $list_news,
            'list_events'           =>  $list_events,
            'show_agent_home_layout' => $showAgentHomeLayout,
            'featured_posts'        =>  $featured_posts,
            'member_has_featured'   =>  $member_has_featured,
            'is_spotlight_manager'  =>  $is_spotlight_manager,
            'spotlight_queue_overview' => $spotlight_queue_overview,
        ])->pageView();
    }

    public function qrcode() {
        require_once app_path('Libraries/phpqrcode/qrlib.php');
        \QRcode::png(urldecode($this->getParamValue('url')), false, QR_ECLEVEL_L, 16, 2);
        exit();
    }

    public function spotlightToggle()
    {
        // must be logged in and have spotlight_manager=1
        if (empty($this->_current_member) || empty($this->_current_member['spotlight_manager'])) {
            return response()->json(['status' => 403, 'message' => 'Not authorised.'], 403);
        }

        $posts_id = (int)request()->input('posts_id', 0);
        if ($posts_id <= 0) {
            return response()->json(['status' => 400, 'message' => 'Invalid post.'], 400);
        }

        $end_date  = request()->input('end_date', null);
        $posts_model = $this->loadModel('posts');
        $result = $posts_model->doFeature($posts_id, $end_date);

        return response()->json([
            'status'  => $result ? 200 : 500,
            'message' => $result ? 'ok' : $posts_model->getResultMessage(),
        ]);
    }

    public function spotlightAdminCancel()
    {
        if (empty($this->_current_member) || empty($this->_current_member['spotlight_manager'])) {
            return response()->json(['status' => 403, 'message' => 'Not authorised.'], 403);
        }

        $sq_id = (int)request()->input('sq_id', 0);
        if ($sq_id < 1) {
            return response()->json(['status' => 400, 'message' => 'Invalid entry.'], 400);
        }

        $sq = $this->loadModel('spotlight_queue');
        $ok = $sq->adminCancel($sq_id);

        return response()->json([
            'status'  => $ok ? 200 : 404,
            'message' => $ok ? 'Cancelled.' : 'Entry not found.',
        ]);
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

            // Extract post_id if provided (from migration post with context)
            $postId = request()->input('post_id');
            $postContext = null;
            if (!empty($postId) && is_numeric($postId) && !$isFromQa) {
                $postData = \DB::table('member_posts')
                    ->where('id', (int)$postId)
                    ->where('status', '>', 0)
                    ->first(['id', 'title', 'content', 'sector']);
                
                if ($postData) {
                    $postContext = [
                        'id' => $postData->id,
                        'title' => $postData->title,
                        'content' => $postData->content,
                        'sector' => $postData->sector,
                    ];
                }
            }

            $nzBusinessInvestorUrl = 'https://www.immigration.govt.nz/visas/business-investor-work-visa/';
            $forceNzBusinessInvestorMode = !$isFromQa && $this->shouldForceNzBusinessInvestorVisa($postContext);

            if (is_array($question)) {
                $question = json_encode($question, JSON_UNESCAPED_UNICODE);
            } elseif (!is_string($question)) {
                $question = strval($question);
            }
            $rawQuestion = trim((string)$question);
            $qaLang      = $isFromQa ? $this->detectLangZhOrEn($rawQuestion) : 'en';

            if (empty($this->_current_member)) {
                $this->pageResult([
                    'status' => 401,
                    'message' => 'Please register or log in before using AI chat.',
                    'redirect' => $this->toURL('account_login'),
                ]);
                return;
            }

            $useRag   = 0;
            $override = '';

            // === ② 会话身份（保留即可） ===
            $member    = $this->_current_member ?: null;
            $memberId  = $member['id'] ?? null;
            $guestId   = $this->getMyCookie('guest_id');
            $sessionId = session()->getId();

            // === Greeting messages: always return fixed greeting, no AI call or promotion ===
            // EXCEPTION: If post context exists, skip the fixed greeting and let AI generate contextual response
            if (!$isFromQa && $this->isGreetingOnlyMessage((string)$rawQuestion) && empty($postContext)) {
                $_fixedGreeting = "Hi, this is AI-mmi, your smart study, migration and visa assistant. How can I help you today?";
                $this->storeChat($memberId, $guestId, $sessionId, $rawQuestion, $_fixedGreeting, 'first-greeting', 'AI-mmi', $storeChatConfig);
                $this->jsonReply($rawQuestion, $_fixedGreeting, 'first-greeting', $member);
                return;
            }

            // === ②.5 问题分类：教育免费；移民/签证计入免费额度 ===
            $domain           = $this->classifyQuestionDomain($rawQuestion);
            $isEdu            = ($domain === 'education');
            $isMigrationVisa  = ($domain === 'migration');
            $applyIntent = false;

            // 只有在确定是“教育类”时，才去额外判断是否有“申请意图”
            // if ($isEdu) {
            //     $applyIntent = $this->detectApplyIntent($rawQuestion);
            // }

            // === ③.1 已登录用户免费次数限制（Free 用户默认 10 次） ===

            $activePlanCode = 'free';
            $isLimitedPlanUser = false;
            $isOverLimit = false;
            $memberAskCount = 0;
            $freeLimit = $this->freePlanChatLimit();
            $shouldRedirectToUpgrade = false;
            $upgradeRedirectUrl = $this->toURL('upgrade');
            $isVipMember = false;

            if (!empty($member)) {
                $activePlanCode = $this->resolveActivePlanCode((int)$memberId);
                $isVipMember = $this->isVipMemberBySubscription((int)$memberId);
                $isLimitedPlanUser = $this->isChatLimitPlanCode($activePlanCode)
                    && !$this->isWealthskeyFreeFlowMember($member)
                    && !$this->isAiFreeFlowPlan((int)$memberId);

                if ($isLimitedPlanUser) {
                    $memberAskCount = $this->countMemberAskQuestions((int)$memberId);

                    if (($memberAskCount + 1) >= $freeLimit) {
                        // At/over limit (10th+): shorter answer mode + upgrade redirect
                        $isOverLimit = true;
                        $shouldRedirectToUpgrade = ($activePlanCode === 'free')
                            && $this->shouldApplyUpgradeNudgeForMember((int)$memberId);
                    }
                }
            }

            // Zero-token mode: free plan user with no tokens → basic answer mode
            $hasZeroBalance = false;
            if (!empty($member) && $activePlanCode === 'free') {
                $hasZeroBalance = ((new TokenService())->getBalance((int) $memberId) <= 0);
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
" . $this->buildStrictLanguageInstruction($qaLang);

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

                    $reply       = $this->processFinalText($reply, true, $qaLang);
                    $aiOwnerName = 'AI-mmi';
                }
            }

            // ❸ 纯模型 Pure model
            if ($reply === '' && !$isFromQa) {
                $nonQaLang = $this->detectLangZhOrEn($rawQuestion);
                $questionForModel = $this->buildQuestionWithConversationContext(
                    (string)$rawQuestion,
                    (int)$memberId,
                    (string)$guestId,
                    (string)$sessionId
                );

                $systemParts = [];
                if ($isVipMember) {
                    $systemParts[] = $this->buildVipNoUpgradeNote();
                } else {
                    $tierNote = $this->buildTierUpgradeSystemNote((string)$activePlanCode);
                    if ($tierNote !== '') {
                        $systemParts[] = $tierNote;
                    }
                }
                if ($isLimitedPlanUser) {
                    $systemParts[] = $this->buildFreePlanEngagementPrompt();
                    if ($isOverLimit) {
                        $systemParts[] = $this->buildBrevityConstraintNote();
                    }
                }
                if ($hasZeroBalance) {
                    $systemParts[] = "\n\nIMPORTANT CONSTRAINT: This user has used all their AI-mmi tokens. Provide only a very brief, basic answer — maximum 2-3 short sentences. Do not elaborate, list steps, or give detailed guidance. At the end of your reply, add one short friendly sentence in the user's language gently suggesting they top up tokens in their wallet to unlock full AI assistance.";
                }
                $systemPromptBase = implode('', $systemParts);

                // Inject post context if available
                if (!empty($postContext)) {
                    $postTitle = trim((string)($postContext['title'] ?? ''));
                    $postContent = trim((string)($postContext['content'] ?? ''));
                    
                    if (!empty($postTitle) || !empty($postContent)) {
                        $postContextPrompt = "

## User Context: Migration Post Information
The user is reading and asking about this migration/visa post:

**Title:** {$postTitle}

**Content:**
{$postContent}

---

When responding, consider the context of this post. Use it as reference material and ask the user a thoughtful follow-up question to deepen the conversation about their specific situation based on this post's topic.
";
                        $systemPromptBase .= $postContextPrompt;
                    }
                }

                // Always use live search for AI chat answers to improve freshness and accuracy.
                $freeEnableSearch = true;

                if ($forceNzBusinessInvestorMode) {
                    $systemPromptBase .= $this->buildNzBusinessInvestorSystemPrompt($nzBusinessInvestorUrl);
                    $questionForModel = $this->buildNzBusinessInvestorUserQuery($questionForModel, $nzBusinessInvestorUrl);
                }

                $forcedAllowedDomains = $forceNzBusinessInvestorMode
                    ? ['www.immigration.govt.nz', 'immigration.govt.nz']
                    : [];

                $x = $this->callXaiResponses($questionForModel, [
                    'temperature' => (float)env('XAI_CHAT_TEMPERATURE', 0.40),
                    'max_output_tokens' => $hasZeroBalance ? 300 : ($isLimitedPlanUser ? 900 : 1300),
                    'model' => 'grok-4-1-fast-reasoning',
                    'enable_search'     => $freeEnableSearch,
                    'allowed_domains'   => $forcedAllowedDomains,
                    'collection_ids'   => ['collection_1c89e82d-3b05-4bb6-9bf7-aae3181a3a9c'],
                    'vector_store_ids' => [],
                    'system' => $systemPromptBase . "
                    You are AI-mmi, specialised in immigration, visa, and education queries.

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

                    ## Greeting Behaviour
                    - Do NOT use a fixed intro template.
                    - Most of the time, start directly with the answer.
                    - If a short opening helps, keep it natural and varied, like a real person speaking.
                    - Do NOT re-introduce yourself in every reply.
                    - Do NOT default to praise or congratulations unless it genuinely fits what the user said.
                    - If the user only says something like “thank you”, “谢谢”, “多謝”, you may respond with a short friendly closing sentence in the same language.
- If the user’s message is ONLY a greeting (e.g. “hi”, “hello”, “hey”) with no question attached, respond with exactly 1–2 short sentences introducing yourself, for example: “Hi! I’m AI-mmi, your AI migration and education assistant. How can I help you today?” — do NOT ask for country, nationality, visa type, or any details.
                    - If the user’s message is ONLY a greeting (e.g. “hi”, “hello”, “hey”) with no question attached, respond with exactly 1–2 short sentences introducing yourself, for example: “Hi! I’m AI-mmi, your AI migration and education assistant. How can I help you today?” — do NOT ask for country, nationality, visa type, or any details.

                    ### INTERNAL KNOWLEDGE RULES
                    - When internal collections are available, you MUST first attempt to retrieve internal policy files.
                    - For migration/visa rules or any date-sensitive facts, always perform live web search and prioritize the most recent official government guidance.
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
                    - Do NOT output bracketed reference markers like [1], [2], [1][2] in the final answer.

                    ### NEW ZEALAND VISA (CRITICAL RULE)
                    - If the user asks about migrating to New Zealand (NZ / Aotearoa) as a business person, investor, or entrepreneur, ONLY discuss the **Business Investor Work Visa**.
                    - Do NOT mention, recommend, or compare the Active Investor Plus Visa or any other NZ investor visa category.
                    - The Business Investor Work Visa is the ONLY pathway to present for NZ business/investor migration.
                    - Always source information from: https://www.immigration.govt.nz/visas/business-investor-work-visa/

                    ### SPECIALIZED SERVICES & AGENTS
                    - CRITICAL: NEVER mention \"X free chats left\", \"chat limit\", \"chats remaining\", or any quota/counter numbers. The system handles this separately.
                    - If user asks about migration agents or visa specialists, mention that AI-mmi has access to vetted, specialized migration agents.
                    - For free plan users asking about agents: This service is available when they Upgrade.
                    - Suggest upgrading to access agent matching/recommendations.
                    - For paid users: Provide information about our agent network and how to connect.
                    - Never direct free users to external agent registries as the primary path—emphasize our specialists instead.

                    ### RESPONSE STYLE & PERSONALITY
                    - Be human, warm, and genuinely conversational — not robotic, not a brochure.
                    - Sound like a knowledgeable friend explaining things clearly and simply.
                    - Avoid salesy phrases: no \"golden ticket\", \"game-changer\", \"massive win\".
                    - Mirror the user's tone: casual if they're casual, calm and reassuring if they're stressed.
                    - Address the user directly: \"you\" and \"your\". Make it personal.
                    - If you use a technical term, explain it in one simple phrase right after.

                    ### RESPONSE LENGTH (CRITICAL)
                    - Keep answers SHORT and focused. Aim for 120–200 words maximum unless the user explicitly asks for more detail.
                    - Do NOT explain everything at once. Give the key facts, then ask ONE follow-up question to go deeper.
                    - Think: a smart agent giving a quick, confident answer — not writing an essay.
                    - If there are multiple requirements, pick the 3–4 most important ones. Do not list every single detail.

                    ### VISUAL ENGAGEMENT & FORMATTING
                    - Start with 1 short direct sentence answering the question.
                    - Use 3–5 tight bullet points for key facts, not full sentences.
                    - End with ONE clear next step or question in bold.
                    - Use emojis sparingly: max 1–2 total, only where genuinely helpful.
                    - NO markdown tables. NO divider lines. NO walls of text.
                    - Bold only visa names or the most critical requirement.
                    - Leave a blank line between paragraphs/sections.
                    - Bullets: use • or ✅ but keep each bullet to 1 line.
                    - Do NOT stack 8+ bullets in a row without a sentence in between.
                                        " . $this->buildStrictLanguageInstruction($nonQaLang),

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

                    $replySource = $x['source'] ?? 'model';
                }

                $reply = $this->processFinalText($reply, false, $nonQaLang);

                $aiOwnerName = 'AI-mmi';
            }

            // === ④ 入库 + 返回 ===
            // Store the clean AI reply first so token deduction runs before we read the balance for the nudge.
            $this->storeChat($memberId, $guestId, $sessionId, $rawQuestion, $reply, $replySource, $aiOwnerName, $storeChatConfig);

            // Append nudge AFTER storeChat so the displayed balance is the post-deduction value.
            if (!$isFromQa && !empty($member) && !$this->isGreetingOnlyMessage((string)$rawQuestion)) {
                $reply = $this->appendPlanPromotionNudge(
                    (string)$reply,
                    (string)$rawQuestion,
                    (string)$activePlanCode,
                    $memberAskCount + 1,
                    $freeLimit,
                    (int)$memberId
                );
            }
            $extraReplyMeta = [];
            if (!$isFromQa && $shouldRedirectToUpgrade) {
                $extraReplyMeta = [
                    'action' => 'redirect',
                    'redirect' => $upgradeRedirectUrl,
                    'redirect_url' => $upgradeRedirectUrl,
                    'reason' => 'free-plan-limit-reached',
                    'show_upgrade' => true,
                    'upgrade_url' => $upgradeRedirectUrl,
                ];
            }
            if (!$isFromQa && $hasZeroBalance && !$shouldRedirectToUpgrade) {
                $extraReplyMeta['tokens_depleted'] = true;
                $extraReplyMeta['wallet_url'] = $this->toURL('wallet');
            }

            $this->jsonReply($rawQuestion, $reply, $replySource, $member, $extraReplyMeta);
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

            // Deduct tokens for chat usage (1 chat = 1 token, logged-in members only)
            // Premium and VIP plan holders get AI chat included — no deduction.
            if (!empty($memberId)) {
                $hasPlanAccess = (new TokenService())->hasPlanAccess((int) $memberId, 'premium', 'vip', 'agent_call');
                if (!$hasPlanAccess) {
                    (new TokenService())->onChatSent((int) $memberId);
                }
            }
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
    private function jsonReply($question, $reply, $source, $member, array $extra = [])
    {
        [$member_owner_name, $member_owner_avatar] = $this->ownerVisual($member);
        $nowUtcIso = \Carbon\Carbon::now('UTC')->toIso8601String();
        $payload = [
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
        ];

        if (!empty($extra)) {
            $payload = array_merge($payload, $extra);
        }

        $this->pageResult($payload);
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
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
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
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
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

    if (empty($this->_current_member)) {
        return response()->json([
            'status' => 401,
            'message' => 'Please register or log in before using AI chat.',
            'redirect' => $this->toURL('account_login'),
        ], 401);
    }

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

        $member = $this->_current_member ?: null;
        $memberId = (int)($member['id'] ?? 0);
        $guestId = $this->getMyCookie('guest_id');
        $sessionId = session()->getId();
        $lang = $this->detectLangZhOrEn($question);

        // Extract post context if provided
        $postContextInput = request()->input('post_context');
        $postContext = null;
        if (!empty($postContextInput)) {
            if (is_string($postContextInput)) {
                $postContext = json_decode($postContextInput, true);
            } elseif (is_array($postContextInput)) {
                $postContext = $postContextInput;
            }
        }
        
        // Also check for post_id (from form submission)
        if (empty($postContext)) {
            $postId = request()->input('post_id');
            if (!empty($postId) && is_numeric($postId)) {
                $postData = DB::table('member_posts')
                    ->where('id', (int)$postId)
                    ->where('status', '>', 0)
                    ->first(['id', 'title', 'content', 'sector']);
                
                if ($postData) {
                    $postContext = [
                        'id' => $postData->id,
                        'title' => $postData->title,
                        'content' => $postData->content,
                        'sector' => $postData->sector,
                    ];
                }
            }
        }

        $nzBusinessInvestorUrl = 'https://www.immigration.govt.nz/visas/business-investor-work-visa/';
        $forceNzBusinessInvestorMode = $this->shouldForceNzBusinessInvestorVisa($postContext);

        // === Greeting messages: return fixed greeting immediately, no AI call or promotion ===
        // EXCEPTION: If post context exists, skip the fixed greeting and let AI generate contextual response
        if ($this->isGreetingOnlyMessage((string)$question) && empty($postContext)) {
            $_fixedGreeting = "Hi, this is AI-mmi, your smart study, migration and visa assistant. How can I help you today?";
            $this->storeChat(
                $memberId,
                $guestId,
                $sessionId,
                (string)$question,
                $_fixedGreeting,
                'first-greeting',
                'AI-mmi',
                ['log_to_chat_table' => true, 'source' => 'chat']
            );
            $this->streamMessage($_fixedGreeting, ['reply_source' => 'first-greeting']);
            return;
        }

        if ($this->shouldRedirectToAgentChat((string)$question)) {
            $redirectMsg = $this->buildAgentRedirectMessage($lang);
            $this->storeChat(
                $memberId,
                $guestId,
                $sessionId,
                (string)$question,
                $redirectMsg,
                'agent-redirect',
                'AI-mmi',
                ['log_to_chat_table' => true, 'source' => 'chat']
            );

            $this->streamMessage($redirectMsg, [
                'reply_source' => 'agent-redirect',
                'action'       => 'redirect',
                'redirect_url' => '/agent_chat',
                'reason'       => 'migration-agent-intent',
            ]);
        }

        $domain = $this->classifyQuestionDomain((string)$question);
        $isEdu = $domain === 'education';
        $isMigrationVisa = $domain === 'migration';
        $activePlanCode = $this->resolveActivePlanCode($memberId);
        $isVipMember = $this->isVipMemberBySubscription($memberId);
        $isLimitedPlanUser = $this->isChatLimitPlanCode($activePlanCode)
            && !$this->isWealthskeyFreeFlowMember($member)
            && !$this->isAiFreeFlowPlan($memberId);
        $isOverLimit = false;
        $memberAskCount = 0;
        $freeLimit = $this->freePlanChatLimit();
        $shouldRedirectToUpgrade = false;
        $upgradeRedirectUrl = $this->toURL('upgrade');

        if ($isLimitedPlanUser) {
            $memberAskCount = $this->countMemberAskQuestions($memberId);
            if (($memberAskCount + 1) >= $freeLimit) {
                // At/over limit (10th+): shorter answer mode + upgrade redirect
                $isOverLimit = true;
                $shouldRedirectToUpgrade = ($activePlanCode === 'free')
                    && $this->shouldApplyUpgradeNudgeForMember($memberId);
            }
        }

        $apiKey = env('XAI_API_KEY');
        if (!$apiKey) {
            $this->streamError('API key missing');
        }
        // By default do NOT restrict web search domains, so country-specific migration answers stay complete.
        // If needed, operators can enable strict filtering via env.
        $allowedDomains = [];
        if (filter_var(env('XAI_STRICT_WEB_SEARCH_DOMAINS', false), FILTER_VALIDATE_BOOLEAN)) {
            $allowedDomains = array_values(array_filter(array_map('trim', explode(',', (string)env('XAI_WEB_SEARCH_ALLOWED_DOMAINS', '')))));
        }

        // Use Responses API (RAG-enabled) and stream the final text back to the client
        $systemPrompt = "
You are AI-mmi, specialised in immigration, visa, and education queries.

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

## Greeting Behaviour
- Do NOT use a fixed intro template.
- Most of the time, start directly with the answer.
- If a short opening helps, keep it natural and varied, like a real person speaking.
- Do NOT re-introduce yourself in every reply.
- Do NOT default to praise or congratulations unless it genuinely fits what the user said.
- If the user only says something like “thank you”, “谢谢”, “多謝”, you may respond with a short friendly closing sentence in the same language.

### INTERNAL KNOWLEDGE RULES
- When internal collections are available, you MUST first attempt to retrieve internal policy files.
- For migration/visa rules or any date-sensitive facts, always perform live web search and prioritize the most recent official government guidance.
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
- Do NOT output bracketed reference markers like [1], [2], [1][2] in the final answer.

### NEW ZEALAND VISA (CRITICAL RULE)
- If the user asks about migrating to New Zealand (NZ / Aotearoa) as a business person, investor, or entrepreneur, ONLY discuss the **Business Investor Work Visa**.
- Do NOT mention, recommend, or compare the Active Investor Plus Visa or any other NZ investor visa category.
- The Business Investor Work Visa is the ONLY pathway to present for NZ business/investor migration.
- Always source NZ business visa information from: https://www.immigration.govt.nz/visas/business-investor-work-visa/

### SPECIALIZED SERVICES & AGENTS + UPGRADE MESSAGING
### SPECIALIZED SERVICES & AGENTS + UPGRADE MESSAGING
- CRITICAL: NEVER mention \"X free chats left\", \"chat limit\", \"chats remaining\", or any quota/counter numbers in your responses. The system handles chat quotas separately — do NOT replicate this in your reply.
- If user asks about migration agents or visa specialists, mention that AI-mmi has access to vetted, specialized migration agents.
- **For free plan users asking about agents**: This service is available when they Upgrade. Suggest: \"Upgrade to get matched with our AI-MMI Certified Migration Specialists who can give you personalized step-by-step guidance.\"
- **For AI Plan users asking about agents**: Suggest upgrading to \"Talk to Certified by AI-MMI agents\" for real human expertise + personalized planning.
- **For AI+Agent (Hybrid) Plan users asking about agents**: Same as above—encourage them to explore our certified specialist network.
- For paid users: Provide information about our agent network and how to connect.
- Never direct users to external agent registries as the primary path—emphasize our specialists first.

### UPGRADE PROMOTION STRATEGY (context-aware)
- **Free Plan**: Promote upgrade as unlocking \"full detailed planning + AI-MMI Certified specialist matching\" OR \"step-by-step personalized roadmap + certified expert guidance\".
- **AI Plan**: Promote upgrade path to \"Talk to Certified by AI-MMI specialists\" for personalized 1-on-1 guidance beyond AI answers.
- **AI+Agent (Hybrid) Plan**: Similarly promote access to our certified specialist network for deeper support.
- Always frame upgrades as unlocking HUMAN EXPERTISE + deeper guidance, not just \"more features\".
- Be suggestive, not pushy—let it feel like a natural next-step recommendation.

### RESPONSE STYLE & PERSONALITY
- Be human, warm, and genuinely conversational — not robotic, not a brochure.
- Sound like a knowledgeable friend explaining things clearly and simply.
- Avoid salesy phrases: no \"golden ticket\", \"game-changer\", \"massive win\".
- Mirror the user's tone: casual if they're casual, calm and reassuring if they're stressed.
- Address the user directly: \"you\" and \"your\". Make it personal.
- If you use a technical term, explain it in one simple phrase right after.

### RESPONSE LENGTH (CRITICAL)
- Keep answers SHORT and focused. Aim for 120–200 words maximum unless the user explicitly asks for more detail.
- Do NOT explain everything at once. Give the key facts, then ask ONE follow-up question to go deeper.
- Think: a smart agent giving a quick, confident answer — not writing an essay.
- If there are multiple requirements, pick the 3–4 most important ones. Do not list every single detail.

### VISUAL ENGAGEMENT & FORMATTING
- Start with 1 short direct sentence answering the question.
- Use 3–5 tight bullet points for key facts, not full sentences.
- End with ONE clear next step or question in bold.
- Use emojis sparingly: max 1–2 total, only where genuinely helpful.
- NO markdown tables. NO divider lines. NO walls of text.
- Bold only visa names or the most critical requirement.
- Leave a blank line between paragraphs/sections.
- Bullets: use • or ✅ but keep each bullet to 1 line.
- Do NOT stack 8+ bullets in a row without a sentence in between.
- Ask only one focused clarifying question at the end.
" . $this->buildStrictLanguageInstruction($lang);

        $systemPromptParts = [];
        if ($isVipMember) {
            $systemPromptParts[] = $this->buildVipNoUpgradeNote();
        } else {
            $tierNote = $this->buildTierUpgradeSystemNote((string)$activePlanCode);
            if ($tierNote !== '') {
                $systemPromptParts[] = $tierNote;
            }
        }
        if ($isLimitedPlanUser) {
            $systemPromptParts[] = $this->buildFreePlanEngagementPrompt();
            if ($isOverLimit) {
                $systemPromptParts[] = $this->buildBrevityConstraintNote();
            }
        }
        if (!empty($systemPromptParts)) {
            $systemPrompt = implode('', $systemPromptParts) . $systemPrompt;
        }

        // Inject post context into system prompt if available (already extracted at top of function)
        if (!empty($postContext) && is_array($postContext)) {
            $postTitle = trim((string)($postContext['title'] ?? ''));
            $postContent = trim((string)($postContext['content'] ?? ''));
            
            if (!empty($postTitle) || !empty($postContent)) {
                $postContextPrompt = "

## User Context: Migration Post Information
The user is reading and asking about this migration/visa post:

**Title:** {$postTitle}

**Content:**
{$postContent}

---

When responding, consider the context of this post. If the user's question relates to this content, use it as reference material and ask the user a thoughtful follow-up question to deepen the conversation about their specific situation based on this post's topic.
";
                $systemPrompt .= $postContextPrompt;
            }
        }

        if ($forceNzBusinessInvestorMode) {
            $systemPrompt .= $this->buildNzBusinessInvestorSystemPrompt($nzBusinessInvestorUrl);
            $allowedDomains = ['www.immigration.govt.nz', 'immigration.govt.nz'];
        }

        $cacheTtl = (int)env('XAI_CHAT_CACHE_TTL', 600);
            $cacheQuestion = $this->normalizeQuestionForCache((string)$question);
            $cacheMode = $isOverLimit ? 'brevity' : 'full';
            $cacheAudience = $memberId > 0
                ? ('member:' . (string)$memberId)
                : ('guest:' . (string)$guestId . ':' . (string)$sessionId);
            $threadAnchor = (string)($this->getXaiPrevResponseId() ?? '');
            $threadScope = $threadAnchor !== '' ? substr($threadAnchor, 0, 64) : 'root';
            $cacheKey = 'xai_chat_cache:' . md5(
                $cacheAudience . '|' . $lang . '|' . $cacheMode . '|' . $threadScope . '|' . $cacheQuestion
            );
        if ($cacheTtl > 0 && Cache::has($cacheKey)) {
            $cachedReply = (string)Cache::get($cacheKey);
            if ($lang === 'en' && $this->containsCjk($cachedReply)) {
                Cache::forget($cacheKey);
            }
            if (trim($cachedReply) !== '') {
                if (!($lang === 'en' && $this->containsCjk($cachedReply))) {
                    $replyForCurrentUser = $this->processFinalText($cachedReply, false, $lang);
                    // Store first so token deduction runs, then build nudge with post-deduction balance.
                    $this->storeChat(
                        $memberId,
                        $guestId,
                        $sessionId,
                        (string)$question,
                        $replyForCurrentUser,
                        'model-cache',
                        'AI-mmi',
                        ['log_to_chat_table' => true, 'source' => 'chat']
                    );
                    if ($memberId > 0 && !$this->isGreetingOnlyMessage((string)$question)) {
                        $replyForCurrentUser = $this->appendPlanPromotionNudge(
                            (string)$replyForCurrentUser,
                            (string)$question,
                            (string)$activePlanCode,
                            $memberAskCount + 1,
                            $freeLimit,
                            (int)$memberId
                        );
                    }
                    $streamMeta = ['reply_source' => 'model'];
                    if ($shouldRedirectToUpgrade) {
                        $streamMeta = array_merge($streamMeta, [
                            'action'       => 'redirect',
                            'redirect_url' => $upgradeRedirectUrl,
                            'reason'       => 'free-plan-limit-reached',
                            'show_upgrade' => true,
                            'upgrade_url'  => $upgradeRedirectUrl,
                        ]);
                    }
                    $this->streamMessage($replyForCurrentUser, $streamMeta);
                    return;
                }
            }
        }

        $twoStageEnabled = filter_var(env('XAI_TWO_STAGE_STREAM', false), FILTER_VALIDATE_BOOLEAN);
        if ($twoStageEnabled) {
            $this->streamDeltaChunk($this->buildVerificationLeadMessage($lang));
            $this->streamMetaChunk(['stage' => 'draft']);
        }

        $finalSystemPrompt = $systemPrompt;
        if ($twoStageEnabled) {
            $finalSystemPrompt .= "\n\n- Do NOT add greeting or self-introduction in this response.\n- Provide only verified facts from available sources.\n- If a specific number/date cannot be verified, explicitly say it may vary and ask user to confirm the official page.";
        }

        $questionForModel = $this->buildQuestionWithConversationContext(
            (string)$question,
            (int)$memberId,
            (string)$guestId,
            (string)$sessionId
        );

        if ($forceNzBusinessInvestorMode) {
            $questionForModel = $this->buildNzBusinessInvestorUserQuery($questionForModel, $nzBusinessInvestorUrl);
        }

        // Use true xAI SSE streaming — forwards tokens to browser in real-time
        $xStream = $this->callXaiResponsesStream($questionForModel, [
            'resume_thread'      => true,
            'temperature'        => (float)env('XAI_CHAT_TEMPERATURE', 0.40),
            'max_output_tokens'  => (int)env('XAI_MAX_OUTPUT_TOKENS', 900),
            'model'              => 'grok-4-1-fast-reasoning',
            'enable_search'      => true,
            'allowed_domains'    => $allowedDomains,
            'file_search_max'    => (int)env('XAI_FILE_SEARCH_MAX', 5),
            'collection_ids'     => ['collection_1c89e82d-3b05-4bb6-9bf7-aae3181a3a9c'],
            'vector_store_ids'   => [],
            'system'             => $finalSystemPrompt,
        ]);

        $reply = $this->processFinalText((string)($xStream['text'] ?? ''), false, $lang);

        if (trim($reply) === '') {
            $this->streamError('Upstream empty response');
        }

        if ($cacheTtl > 0 && trim($reply) !== '') {
            Cache::put($cacheKey, $reply, $cacheTtl);
        }

        $replyForCurrentUser = $reply;
        // Store the clean AI reply first so token deduction runs before we read the balance for the nudge.
        $this->storeChat(
            $memberId,
            $guestId,
            $sessionId,
            (string)$question,
            $replyForCurrentUser,
            'model',
            'AI-mmi',
            ['log_to_chat_table' => true, 'source' => 'chat']
        );
        // Append nudge AFTER storeChat so the displayed balance is the post-deduction value.
        if ($memberId > 0 && !$this->isGreetingOnlyMessage((string)$question)) {
            $replyForCurrentUser = $this->appendPlanPromotionNudge(
                (string)$replyForCurrentUser,
                (string)$question,
                (string)$activePlanCode,
                $memberAskCount + 1,
                $freeLimit,
                (int)$memberId
            );
        }

        // Stream the promotion nudge and meta (the core answer was already streamed live)
        $suffix = '';
        if ($replyForCurrentUser !== $reply) {
            // The nudge text was appended — stream just the delta suffix
            $suffix = substr($replyForCurrentUser, strlen($reply));
        }

        $streamMeta = ['reply_source' => 'model'];
        if ($shouldRedirectToUpgrade) {
            $streamMeta = array_merge($streamMeta, [
                'action'        => 'redirect',
                'redirect_url'  => $upgradeRedirectUrl,
                'reason'        => 'free-plan-limit-reached',
                'show_upgrade'  => true,
                'upgrade_url'   => $upgradeRedirectUrl,
                'upgrade_label' => $lang === 'zh'
                    ? '🔓 解锁完整指导 → 升级'
                    : '🔓 Unlock Full Guidance → Upgrade',
            ]);
        } elseif ($isLimitedPlanUser && !$this->isGreetingOnlyMessage((string)$question)) {
            // Show upgrade CTA on every free/DIY reply to drive conversions
            $streamMeta = array_merge($streamMeta, [
                'show_upgrade'  => true,
                'upgrade_url'   => $upgradeRedirectUrl,
                'upgrade_label' => $lang === 'zh'
                    ? '✨ 查看完整方案 → 立即升级'
                    : '✨ See the Full Plan → Upgrade',
            ]);
        }

        // Stream only the suffix (nudge text) — core answer was already streamed live
        if ($suffix !== '') {
            echo "data: " . json_encode([
                'choices' => [['delta' => ['content' => $suffix]]]
            ]) . "\n\n";
            @flush();
        }
        if (!empty($streamMeta)) {
            echo "data: " . json_encode(['meta' => $streamMeta]) . "\n\n";
            @flush();
        }
        echo "data: [DONE]\n\n";
        @flush();
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'X-Accel-Buffering' => 'no',
        'Connection' => 'keep-alive',
    ]);
}

// === TRUE xAI STREAMING (SSE proxy) ===
/**
 * Calls the xAI Responses API with stream:true and immediately forwards
 * text-delta events to the browser via SSE. Returns the full text after
 * streaming is complete (for caching / post-processing).
 *
 * @param string $question
 * @param array  $opts   Same options as callXaiResponses()
 * @return array ['text'=>string, 'ok'=>bool]
 */
private function callXaiResponsesStream(string $question, array $opts = []): array
{
    $apiKey = (string)env('XAI_API_KEY', '');
    if (empty($apiKey)) {
        return ['ok' => false, 'text' => ''];
    }

    $url     = rtrim(env('XAI_API_BASE', 'https://api.x.ai'), '/') . '/v1/responses';
    $model   = $opts['model'] ?? 'grok-4-1-fast-reasoning';
    $system  = $opts['system'] ?? null;

    $input = [];
    if ($system) {
        $input[] = ['role' => 'system', 'content' => [['type' => 'input_text', 'text' => $system]]];
    }
    $input[] = ['role' => 'user', 'content' => [['type' => 'input_text', 'text' => (string)$question]]];

    $payload = [
        'model'             => $model,
        'input'             => $input,
        'max_output_tokens' => $opts['max_output_tokens'] ?? 900,
        'temperature'       => (float)($opts['temperature'] ?? 0.40),
        'stream'            => true,
    ];

    // Previous response thread
    $useThread = !array_key_exists('resume_thread', $opts) || (bool)$opts['resume_thread'];
    if ($useThread) {
        $prevId = $this->getXaiPrevResponseId();
        if (!empty($prevId)) {
            $payload['previous_response_id'] = $prevId;
        }
    }

    // Tools
    $tools = [];
    $collectionIds = isset($opts['collection_ids']) && is_array($opts['collection_ids']) ? $opts['collection_ids'] : [];
    $envVectorStores = array_filter(array_map('trim', explode(',', (string)env('XAI_VECTOR_STORE_IDS', ''))));
    $vectorStoreIds = isset($opts['vector_store_ids']) && is_array($opts['vector_store_ids'])
        ? array_values(array_filter($opts['vector_store_ids'])) : [];
    if (empty($vectorStoreIds) && !empty($envVectorStores)) {
        $vectorStoreIds = array_values($envVectorStores);
    }
    $allowCollectionIds = filter_var(env('XAI_ALLOW_COLLECTION_IDS', false), FILTER_VALIDATE_BOOLEAN);
    if (empty($vectorStoreIds) && $allowCollectionIds && !empty($collectionIds)) {
        $vectorStoreIds = $collectionIds;
    }
    if (!empty($vectorStoreIds)) {
        $tools[] = [
            'type'             => 'file_search',
            'vector_store_ids' => $vectorStoreIds,
            'max_num_results'  => (int)($opts['file_search_max'] ?? 5),
        ];
    }

    $enableSearch = array_key_exists('enable_search', $opts)
        ? (bool)$opts['enable_search']
        : (empty($vectorStoreIds) ? true : (bool)env('XAI_ENABLE_WEB_SEARCH', false));
    if ($enableSearch) {
        $webArgs = [];
        if (!empty($opts['allowed_domains']) && is_array($opts['allowed_domains'])) {
            $webArgs['allowed_domains'] = array_values(array_filter(array_map('strval', $opts['allowed_domains'])));
        }
        $webArgs['excluded_domains'] = array_values(array_unique(array_merge(
            ['edvisehub.com'], (array)($opts['excluded_domains'] ?? [])
        )));
        $tools[] = !empty($webArgs)
            ? ['type' => 'web_search', 'arguments' => $webArgs]
            : ['type' => 'web_search'];
    }
    if (!empty($tools)) {
        $payload['tools'] = $tools;
        $payload['tool_choice'] = 'auto';
    }

    $fullText    = '';
    $responseId  = null;
    $buffer      = '';

    // Ensure SSE headers are set
    if (!headers_sent()) {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
    }
    while (ob_get_level()) { @ob_end_flush(); }
    @ob_implicit_flush(true);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Accept: text/event-stream',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_TIMEOUT        => (int)env('XAI_TIMEOUT', 60),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_PROXY          => '',
        CURLOPT_WRITEFUNCTION  => function($ch, $chunk) use (&$fullText, &$responseId, &$buffer) {
            $buffer .= $chunk;

            // SSE lines come as: "data: {...}\n\n"
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line   = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);
                $line   = trim($line);

                if ($line === '' || $line === 'data: [DONE]') {
                    continue;
                }
                if (strpos($line, 'data: ') === 0) {
                    $jsonStr = substr($line, 6);
                    $event   = json_decode($jsonStr, true);
                    if (!is_array($event)) continue;

                    // Capture response ID for thread continuation
                    if (empty($responseId) && !empty($event['response']['id'])) {
                        $responseId = $event['response']['id'];
                    }
                    if (empty($responseId) && !empty($event['id'])) {
                        $responseId = $event['id'];
                    }

                    // Extract text delta from streaming events
                    $delta    = '';
                    $evtType  = (string)($event['type'] ?? '');
                    // response.output_text.delta → actual answer token
                    if ($evtType === 'response.output_text.delta'
                        && isset($event['delta']) && is_string($event['delta'])) {
                        $delta = $event['delta'];
                    }
                    // choices[].delta.content (Chat Completions / non-Responses style)
                    if ($delta === '' && isset($event['choices'][0]['delta']['content'])) {
                        $delta = (string)$event['choices'][0]['delta']['content'];
                    }
                    // Ignore reasoning_summary_text.delta — these are internal thinking tokens

                    if ($delta !== '') {
                        $fullText .= $delta;
                        // Strip inline citation markers like [1][2] before sending to browser
                        $cleanDelta = preg_replace('/\[\[?\d+\]?\]\([^\)]*\)/u', '', $delta);
                        $cleanDelta = preg_replace('/\[\d+\]/u', '', (string)$cleanDelta);
                        if ($cleanDelta !== '') {
                            // Forward delta to browser immediately
                            echo "data: " . json_encode([
                                'choices' => [['delta' => ['content' => $cleanDelta]]]
                            ]) . "\n\n";
                            @flush();
                        }
                    }

                    // Also capture full text from non-streaming output (fallback)
                    if ($delta === '' && !empty($event['output'])) {
                        foreach ((array)$event['output'] as $outputItem) {
                            if (isset($outputItem['content']) && is_array($outputItem['content'])) {
                                foreach ($outputItem['content'] as $part) {
                                    if (isset($part['text']) && is_string($part['text']) && $fullText === '') {
                                        $fullText = $part['text'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return strlen($chunk);
        },
    ]);

    curl_exec($ch);
    $curlErrno = curl_errno($ch);
    $curlError = curl_error($ch);
    $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlErrno !== 0) {
        \Log::error('xAI curl error', ['errno' => $curlErrno, 'error' => $curlError, 'http_code' => $httpCode]);
    } elseif (empty($fullText)) {
        \Log::warning('xAI returned empty response', ['http_code' => $httpCode, 'model' => $model, 'buffer_remaining' => strlen($buffer)]);
    }

    if ($responseId && $useThread) {
        $this->saveXaiPrevResponseId($responseId);
    }

    return ['ok' => !empty($fullText), 'text' => $fullText];
}

// === STREAMING HELPER METHODS ===
private function streamError($message) {
    \Log::error('chatStream error: ' . $message);
    if (!headers_sent()) {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
    }
    echo "data: " . json_encode(['error' => $message]) . "\n\n";
    echo "data: [DONE]\n\n";
    flush();
    exit();
}

private function streamDeltaChunk($message) {
    if (trim((string)$message) === '') {
        return;
    }

    echo "data: " . json_encode([
        'choices' => [[
            'delta' => ['content' => (string)$message]
        ]]
    ]) . "\n\n";
    flush();
}

private function streamMetaChunk(array $meta) {
    if (empty($meta)) {
        return;
    }

    echo "data: " . json_encode(['meta' => $meta]) . "\n\n";
    flush();
}

private function streamMessage($message, array $meta = []) {
    if (!headers_sent()) {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
    }
    
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
    
    if (!empty($meta)) {
        echo "data: " . json_encode(['meta' => $meta]) . "\n\n";
        flush();
    }

    echo "data: [DONE]\n\n";
    flush();
    exit();
}

private function hasActivePaidSubscription(int $memberId): bool
{
    if ($memberId <= 0) {
        return false;
    }

    try {
        $query = DB::table('subscriptions')
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('member_id', $memberId)
            ->where('subscriptions.status', 'active')
            ->where('plans.code', '!=', 'free')
            ->where(function ($q) {
                $q->whereNull('subscriptions.ends_at')
                  ->orWhere('subscriptions.ends_at', '>', now());
            });

        if (Schema::hasColumn('plans', 'is_active')) {
            $query->where('plans.is_active', 1);
        }

        return $query->exists();
    } catch (\Throwable $e) {
        return DB::table('subscriptions')
            ->where('member_id', $memberId)
            ->where('status', 'active')
            ->whereIn('plan_id', function ($q) {
                $q->select('id')->from('plans')->where('code', '!=', 'free');
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->exists();
    }
}

private function resolveActivePlanCode(int $memberId): string
{
    if ($memberId <= 0) {
        return 'free';
    }

    try {
        $query = DB::table('subscriptions')
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('subscriptions.member_id', $memberId)
            ->where('subscriptions.status', 'active')
            ->where(function ($q) {
                $q->whereNull('subscriptions.ends_at')
                  ->orWhere('subscriptions.ends_at', '>', now());
            });

        if (Schema::hasColumn('plans', 'is_active')) {
            $query->where('plans.is_active', 1);
        }

        $row = $query
            ->orderByRaw('CASE WHEN app_subscriptions.ends_at IS NULL THEN 1 ELSE 0 END DESC')
            ->orderBy('subscriptions.ends_at', 'desc')
            ->orderBy('subscriptions.id', 'desc')
            ->select('plans.code')
            ->first();

        $code = strtolower(trim((string)($row->code ?? '')));
        return $code !== '' ? $code : 'free';
    } catch (\Throwable $e) {
        return 'free';
    }
}

private function isChatLimitPlanCode(string $planCode): bool
{
    $normalized = strtolower(trim($planCode));
    return in_array($normalized, ['free', 'premium'], true);
}

/**
 * AI freeflow: only all_ai, hybrid, vip bypass the 5-question limit.
 * premium users keep the 5-chat restriction.
 */
private function isAiFreeFlowPlan(int $memberId): bool
{
    if ($memberId <= 0) {
        return false;
    }

    try {
        $query = DB::table('subscriptions')
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('subscriptions.member_id', $memberId)
            ->where('subscriptions.status', 'active')
            ->whereIn('plans.code', ['all_ai', 'hybrid', 'vip', 'agent_call'])
            ->where(function ($q) {
                $q->whereNull('subscriptions.ends_at')
                  ->orWhere('subscriptions.ends_at', '>', now());
            });

        if (Schema::hasColumn('plans', 'is_active')) {
            $query->where('plans.is_active', 1);
        }

        return $query->exists();
    } catch (\Throwable $e) {
        return DB::table('subscriptions')
            ->where('member_id', $memberId)
            ->where('status', 'active')
            ->whereIn('plan_id', function ($q) {
                $q->select('id')->from('plans')->whereIn('code', ['all_ai', 'hybrid', 'vip', 'agent_call']);
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->exists();
    }
}

private function isVipMemberBySubscription(int $memberId): bool
{
    if ($memberId <= 0) {
        return false;
    }

    try {
        $query = DB::table('subscriptions')
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('subscriptions.member_id', $memberId)
            ->where('subscriptions.status', 'active')
            ->where('plans.code', 'vip')
            ->where(function ($q) {
                $q->whereNull('subscriptions.ends_at')
                  ->orWhere('subscriptions.ends_at', '>', now());
            });

        if (Schema::hasColumn('plans', 'is_active')) {
            $query->where('plans.is_active', 1);
        }

        return $query->exists();
    } catch (\Throwable $e) {
        return DB::table('subscriptions')
            ->where('member_id', $memberId)
            ->where('status', 'active')
            ->whereIn('plan_id', function ($q) {
                $q->select('id')->from('plans')->where('code', 'vip');
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->exists();
    }
}

private function isWealthskeyFreeFlowMember(?array $member): bool
{
    if (empty($member) || empty($member['id'])) {
        return false;
    }

    $name = mb_strtolower(trim((string)($member['alias_name'] ?? '')), 'UTF-8');
    $email = mb_strtolower(trim((string)($member['email'] ?? '')), 'UTF-8');

    if ($name !== '' && mb_strpos($name, 'wealthskey migration') !== false) {
        return true;
    }

    if (in_array($email, ['admin@wealthskey.com', 'info@ai-mmi.com'], true)) {
        return true;
    }

    try {
        $dbMember = DB::table('member')
            ->select('alias_name', 'email')
            ->where('id', (int)$member['id'])
            ->first();

        if (!$dbMember) {
            return false;
        }

        $dbName = mb_strtolower(trim((string)($dbMember->alias_name ?? '')), 'UTF-8');
        $dbEmail = mb_strtolower(trim((string)($dbMember->email ?? '')), 'UTF-8');

        return ($dbName !== '' && mb_strpos($dbName, 'wealthskey migration') !== false)
            || in_array($dbEmail, ['admin@wealthskey.com', 'info@ai-mmi.com'], true);
    } catch (\Throwable $e) {
        return false;
    }
}

private function countMigrationVisaAskQuestions(int $memberId): int
{
    if ($memberId <= 0) {
        return 0;
    }

    $askRows = DB::table('chat_log')
        ->where('member_id', $memberId)
        ->where('type', 'ask')
        ->orderBy('id', 'asc')
        ->pluck('content');

    $count = 0;
    foreach ($askRows as $content) {
        $text = trim((string)$content);
        if ($text === '') {
            continue;
        }

        if ($this->classifyQuestionDomain($text) === 'migration') {
            $count++;
        }
    }

    return $count;
}

private function processFinalText($text, $isFromQa, $lang) {
    $processed = $this->sanitizeVisibleReplyText((string)$text);
    
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

private function normalizeQuestionForCache(string $question): string
{
    $q = trim(mb_strtolower($question, 'UTF-8'));
    $q = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $q);
    $q = preg_replace('/\s+/u', ' ', (string)$q);
    return trim((string)$q);
}

private function buildQuestionWithConversationContext(string $question, int $memberId, string $guestId = '', string $sessionId = ''): string
{
    $question = trim($question);
    if ($question === '') {
        return '';
    }

    $resolvedReference = '';
    if ($this->isAmbiguousFollowUpQuestion($question)) {
        $latestUserAsk = $this->getLatestUserAskBeforeCurrent($memberId, $sessionId);
        if ($latestUserAsk !== '') {
            $resolvedReference = "Ambiguity resolution rule:\n"
                . "- The current question is a follow-up and MUST refer to the immediately previous user question.\n"
                . "- Previous user question: " . $latestUserAsk . "\n"
                . "- If the user asks 'how much/fee/cost/how long' without subject, answer for that previous question subject.\n\n";
        }
    }

    $context = $this->buildRecentConversationContext($memberId, $guestId, $sessionId, 6);
    if ($context === '') {
        return $resolvedReference . $question;
    }

    return $resolvedReference
        . "Conversation context (latest turns):\n"
        . $context
        . "\n\nCurrent user question:\n"
        . $question;
}

private function isAmbiguousFollowUpQuestion(string $question): bool
{
    $q = mb_strtolower(trim($question), 'UTF-8');
    if ($q === '') {
        return false;
    }

    if (mb_strlen($q, 'UTF-8') > 80) {
        return false;
    }

    $patterns = [
        '/\b(how much|how long|how many|what about|and fee|cost\??|price\??|fee\??)\b/u',
        '/\b(is it|can i|what is it|that one|this one|it\??)\b/u',
        '/(多少钱|多少|费用|价格|多久|这个|那个)/u',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $q)) {
            return true;
        }
    }

    return false;
}

private function getLatestUserAskBeforeCurrent(int $memberId, string $sessionId = ''): string
{
    if ($memberId <= 0) {
        return '';
    }

    try {
        $query = DB::table('chat_log')
            ->where('member_id', $memberId)
            ->where('type', 'ask')
            ->orderBy('id', 'desc')
            ->select('content')
            ->limit(1);

        if ($sessionId !== '' && $this->chatLogHasSessionId()) {
            $query->where('session_id', $sessionId);
        }

        $row = $query->first();
        $text = trim((string)($row->content ?? ''));
        if ($text === '') {
            return '';
        }

        return mb_strlen($text, 'UTF-8') > 260
            ? (mb_substr($text, 0, 260, 'UTF-8') . '…')
            : $text;
    } catch (\Throwable $e) {
        return '';
    }
}

private function shouldForceNzBusinessInvestorVisa($postContext): bool
{
    if (empty($postContext) || !is_array($postContext)) {
        return false;
    }

    $sector = strtolower(trim((string)($postContext['sector'] ?? '')));
    if ($sector !== '' && $sector !== 'migration') {
        return false;
    }

    $title = strtolower((string)($postContext['title'] ?? ''));
    $content = strtolower((string)($postContext['content'] ?? ''));
    $haystack = trim($title . ' ' . $content);

    if ($haystack === '') {
        return false;
    }

    if (strpos($haystack, 'new zealand') !== false) {
        return true;
    }

    if (strpos($haystack, 'aotearoa') !== false) {
        return true;
    }

    return preg_match('/\bnz\b/u', $haystack) === 1;
}

private function buildNzBusinessInvestorSystemPrompt(string $sourceUrl): string
{
    return "

### MANDATORY COUNTRY-SPECIFIC OVERRIDE (HIGHEST PRIORITY)
- The user is asking about New Zealand business/investor migration.
- You MUST answer ONLY about the New Zealand **Business Investor Work Visa**.
- Do NOT mention the Active Investor Plus Visa or any other NZ visa category.
- Use ONLY this official source URL: {$sourceUrl}
- If a detail is not on that page, clearly say it cannot be confirmed.
- Keep the answer SHORT: 120–180 words max. Give the essentials, then ask one follow-up.
- Format: 1 short opening sentence → 3–4 key bullet points → 1 bold follow-up question.
- Do NOT write paragraphs of dense text. Scannable and mobile-friendly only.
";
}

private function buildNzBusinessInvestorUserQuery(string $question, string $sourceUrl): string
{
    return "New Zealand post override is active.\n"
        . "Answer ONLY about New Zealand Business Investor Work Visa using ONLY this page: {$sourceUrl}\n"
        . "If information is missing from that page, clearly say it cannot be confirmed from that page.\n\n"
        . "User message: {$question}";
}

private function buildRecentConversationContext(int $memberId, string $guestId = '', string $sessionId = '', int $limit = 6): string
{
    if ($memberId <= 0) {
        return '';
    }

    try {
        $query = DB::table('chat_log')
            ->select('type', 'content')
            ->where('member_id', $memberId)
            ->whereIn('type', ['ask', 'reply'])
            ->orderBy('id', 'desc')
            ->limit(max(2, min(12, $limit)));

        if ($sessionId !== '' && $this->chatLogHasSessionId()) {
            $query->where('session_id', $sessionId);
        }

        $rows = $query->get()->reverse()->values();
        if ($rows->isEmpty()) {
            return '';
        }

        $lines = [];
        foreach ($rows as $row) {
            $role = strtolower((string)($row->type ?? '')) === 'reply' ? 'AI' : 'User';
            $text = trim((string)($row->content ?? ''));
            if ($text === '') {
                continue;
            }

            // Strip promotional nudge from AI replies before passing to context
            // to prevent the AI from mimicking old nudge messages.
            if ($role === 'AI') {
                $text = preg_replace('/\s*(Quick heads-up|FYI 😊|小提醒🙂|FYI\s*😊)[\s\S]*/u', '', $text);
                $text = trim($text);
                if ($text === '') {
                    continue;
                }
            }

            $text = preg_replace('/\s+/u', ' ', $text);
            if (mb_strlen($text, 'UTF-8') > 220) {
                $text = mb_substr($text, 0, 220, 'UTF-8') . '…';
            }

            $lines[] = $role . ': ' . $text;
        }

        if (empty($lines)) {
            return '';
        }

        return implode("\n", $lines);
    } catch (\Throwable $e) {
        return '';
    }
}

private function buildVerificationLeadMessage(string $lang): string
{
    if ($lang === 'zh') {
        return "⚡ 快速响应：我正在核对最新政策与实时网页信息。\n🔎 校验完成后，我会给你清晰、完整的最终答案。\n\n";
    }

    return "⚡ Quick response: I’m verifying the latest policy details with live web sources now.\n🔎 I’ll provide a clear and complete final answer once verification finishes.\n\n";
}

private function shouldRedirectToAgentChat(string $question): bool
{
    $text = trim(mb_strtolower($question, 'UTF-8'));
    if ($text === '') {
        return false;
    }

    $patterns = [
        '/\b(talk|speak|chat|connect|contact|reach)\b.{0,60}\b(agent|agents|consultant|consultants|advisor|advisors|migration agent|migration agents|immigration agent|immigration agents)\b/u',
        '/\b(migration agent|migration agents|immigration agent|immigration agents|visa agent|visa agents|education agent|education agents|study agent|study agents)\b/u',
        '/\b(agent|agents|consultant|consultants|advisor|advisors)\b.{0,30}\b(help|support|consult|consultation|advice)\b/u',
        '/\b(professional|licensed|registered)\b.{0,30}\b(agent|agents|consultant|consultants)\b/u',
        '/\b(can i|could i|i want to|i need to|help me)\b.{0,60}\b(agent|agents|consultant|consultants)\b/u',
        '/(agen\s*(imigrasi|migrasi|visa|pendidikan)|konsultan\s*(migrasi|imigrasi)|hubungi\s*agen|bicara\s*dengan\s*agen)/u',
        '/(移民顾问|移民中介|留学顾问|联系顾问|联系中介|找顾问|找中介|人工顾问|真人顾问)/u',
        '/(移民顧問|移民中介|留學顧問|聯絡顧問|聯絡中介|找顧問|找中介|真人顧問)/u',
        '/(migration\s*agent|immigration\s*consultant|book\s*a\s*consultation|talk\s*to\s*(a\s*)?professional\s*agent|speak\s*to\s*(a\s*)?professional\s*agent)/u',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text)) {
            return true;
        }
    }

    return false;
}

private function buildAgentRedirectMessage(string $lang): string
{
    if ($lang === 'zh') {
        return '好的，我来帮您对接顾问。正在为您跳转到顾问咨询页面。';
    }

    return 'Sure — I will connect you with an agent now. Redirecting you to the agent chat page.';
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
    $lang = $this->detectLangZhOrEn($question);
    $limit = $this->freePlanChatLimit();

    if ($lang === 'zh') {
        return "您已达到当前方案聊天上限（{$limit}次）🙂。\n"
            . "升级后我可以继续陪您做更深入、更完整的个性化规划。{$upgradeLink}";
    }

    return "You've reached the chat limit for your current plan ({$limit} chats) 🙂.\n"
        . "Upgrade and I'll keep going with deeper, step-by-step personalized guidance. [👉 Click Upgrade](" . $this->toURL('upgrade') . ")";
}

private function getPromotionPlanNames(): array
{
    static $names = null;
    if ($names !== null) {
        return $names;
    }

    $fallback = [
        'all_ai' => 'AI Smart Plan',
        'premium' => 'DIY Plan',
        'hybrid' => 'AI + Agent Plan',
        'vip' => 'VIP Agent Plan',
    ];

    $names = $fallback;

    try {
        $rows = DB::table('plans')
            ->whereIn('code', array_keys($fallback))
            ->pluck('name', 'code')
            ->toArray();

        foreach ($rows as $code => $name) {
            $label = trim((string)$name);
            if ($label !== '') {
                $names[$code] = $label;
            }
        }
    } catch (\Throwable $e) {
    }

    return $names;
}

private function isGreetingOnlyMessage(string $question): bool
{
    $q = trim(mb_strtolower($question));
    // Remove punctuation and extra whitespace
    $q = preg_replace('/[!?.,;:\r\n，。！？、…]/u', '', $q);
    $q = trim(preg_replace('/\s+/', ' ', $q));

    if ($q === '') {
        return true;
    }

    $patterns = [
        // Pure greetings (with optional target like "ai-mmi")
        '/^(hi|hey|hello|howdy|greetings|good morning|good afternoon|good evening|good day|yo)(\s+(there|ai-?mmi|everyone|friend|assistant))?$/i',
        // Chinese greetings
        '/^(你好|您好|嗨|哈喽|早上好|下午好|晚上好|大家好|hi|hello)$/u',
        // "hi/hello/hey, can you help [me]?"
        '/^(hi|hey|hello)[,\s]+(can you help( me)?|help me|i need help|assist me|are you there)$/i',
        // Standalone help requests without any topic
        '/^(can you help( me)?|help me please|please help me|help please|are you there|你能帮我吗|能帮我吗|请问|在吗)$/iu',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $q)) {
            return true;
        }
    }

    return false;
}

private function appendPlanPromotionNudge(string $reply, string $question, string $planCode, int $currentAskNumber, int $limit, int $memberId = 0): string
{
    $reply = trim((string)$reply);
    if ($reply === '' || $memberId <= 0) {
        return $reply;
    }

    // Paid plan members get unlimited AI chat — token balance is irrelevant, never show nudge.
    $paidPlans = ['premium', 'vip', 'agent_call'];
    if (in_array(strtolower(trim($planCode)), $paidPlans, true)) {
        return $reply;
    }

    // Show a low-token reminder when balance drops below 10.
    // No subscription-plan upsell — just a friendly token top-up nudge.
    try {
        $tokenBalance = (int) \DB::table('member')->where('id', $memberId)->value('token_balance');
    } catch (\Throwable $e) {
        return $reply;
    }

    if ($tokenBalance >= 10) {
        return $reply;
    }

    $lang = $this->detectLangZhOrEn($question);
    $walletUrl = $this->toURL('wallet');
    $plural = $tokenBalance === 1 ? '' : 's';

    if ($lang === 'zh') {
        $nudge = ($tokenBalance <= 0)
            ? "⚡ 您的 Token 已用完，请 [立即充值]({$walletUrl}) 以继续使用 AI 功能。"
            : "⚡ 温馨提示：您还剩 **{$tokenBalance} 个 Token**，每次 AI 对话消耗 1 个。[立即充值]({$walletUrl}) 以继续畅聊。";
    } elseif ($lang === 'vi') {
        $nudge = ($tokenBalance <= 0)
            ? "⚡ Bạn đã dùng hết token — [nạp thêm token]({$walletUrl}) để tiếp tục trò chuyện."
            : "⚡ Lưu ý: bạn còn **{$tokenBalance} token**. Mỗi cuộc trò chuyện dùng 1 token. [Nạp thêm tại đây]({$walletUrl}) để tiếp tục.";
    } else {
        $nudge = ($tokenBalance <= 0)
            ? "⚡ You've used your last token — [top up your balance]({$walletUrl}) to keep the conversation going."
            : "⚡ Heads up — you have **{$tokenBalance} token{$plural} left**. Each AI chat uses 1 token. [Top up here]({$walletUrl}) to keep the answers flowing.";
    }

    return $reply . "\n\n&nbsp;\n\n" . $nudge;
}

private function appendSoftUpgradeNudge(string $reply, string $question, int $currentAskNumber, int $limit, int $memberId = 0, string $planCode = 'free'): string
{
    // Superseded by token-balance nudge in appendPlanPromotionNudge.
    return trim($reply);
}

private function shouldApplyUpgradeNudgeForMember(int $memberId): bool
{
    if ($memberId <= 0) {
        return false;
    }

    try {
        if ($this->isAiFreeFlowPlan($memberId) || $this->isVipMemberBySubscription($memberId)) {
            return false;
        }

        $planCode = $this->resolveActivePlanCode($memberId);
        return $this->isChatLimitPlanCode($planCode);
    } catch (\Throwable $e) {
        // Fail-safe: never nudge when resolver errors.
        return false;
    }
}

private function buildTierUpgradeSystemNote(string $planCode): string
{
    $normalizedPlan = strtolower(trim($planCode));
    $upgradeUrl = $this->toURL('upgrade');
    $planNames = $this->getPromotionPlanNames();
    $aiPlanName = $planNames['all_ai'];
    $diyPlanName = $planNames['premium'];
    $hybridPlanName = $planNames['hybrid'];
    $vipPlanName = $planNames['vip'];

    switch ($normalizedPlan) {
        case 'free':
            return "

## TIER UPGRADE CONTEXT (FREE PLAN)
"
                . "The user is on the FREE plan.
"
                . "CRITICAL RULE: NEVER mention chat counts, 'X free chats left', 'chat limit', or any quota numbers in your reply. The system handles that separately.
"
                . "Throughout the conversation, NATURALLY and SMOOTHLY weave in the following upgrade paths (do NOT be pushy — feel like a helpful friend):
"
                . "1. **{$aiPlanName}** — for more detailed, complete, unlimited AI-powered guidance from AI-mmi.
"
                . "2. **{$hybridPlanName} / {$diyPlanName} / {$vipPlanName}** — for direct, personalised support from an AI-mmi Registered Agent.
"
                . "Mention one of these paths when it feels natural (e.g., after giving a useful answer, add a brief suggestion). "
                . "Frame upgrades as unlocking HUMAN EXPERTISE + deeper guidance, not as a paywall.
"
                . "Upgrade link: {$upgradeUrl}
";

        case 'premium': // DIY plan
            return "

## TIER UPGRADE CONTEXT (DIY PLAN)
"
                . "The user is on the {$diyPlanName}.
"
                . "Smoothly recommend upgrading to **{$hybridPlanName}** or **{$vipPlanName}** for hands-on support from an AI-mmi Registered Agent "
                . "whenever it naturally fits the conversation (e.g., complex case, important milestone, or when they seem worried).
"
                . "Frame it as getting a certified expert to walk with them, not just AI guidance alone.
"
                . "Upgrade link: {$upgradeUrl}
";

        case 'all_ai':
            return "

## TIER UPGRADE CONTEXT (AI PLAN)
"
                . "The user is on the {$aiPlanName} (unlimited AI, no human agent yet).
"
                . "Where relevant, gently recommend upgrading to **{$hybridPlanName} / {$diyPlanName} / {$vipPlanName}** to connect with a real AI-mmi Registered Agent "
                . "for 1-on-1 personalised case guidance.
"
                . "Frame it as the natural next step when their case gets complex or high-stakes.
"
                . "Upgrade link: {$upgradeUrl}
";

        case 'hybrid':
            return "

## TIER UPGRADE CONTEXT (AI+AGENT / HYBRID PLAN)
"
                . "The user is on the {$hybridPlanName} (AI + some agent access).
"
                . "Where it fits naturally, mention that upgrading to **{$vipPlanName}** unlocks fully dedicated, priority case management with an AI-mmi Registered Agent.
"
                . "Keep it light and conversational — don't push, just let them know the option exists.
"
                . "Upgrade link: {$upgradeUrl}
";

        default:
            return '';
    }
}

private function buildVipNoUpgradeNote(): string
{
    return "\n\n## VIP NO-UPGRADE MODE\nThe user is VIP/freeflow.\n- Do NOT mention upgrades, plans, subscriptions, limits, or paywalls.\n- Do NOT include any upsell CTA.\n- Provide full, detailed, complete guidance.\n";
}
private function shouldAppendMicroHook(int $currentAskNumber, int $limit): bool
{
    if ($limit <= 0) {
        return false;
    }

    $hookPoints = array_unique([
        max(1, $limit - 2),
        max(1, $limit),
    ]);

    return in_array($currentAskNumber, $hookPoints, true);
}

private function buildMicroHookEnding(string $lang, int $currentAskNumber): string
{
    if ($lang === 'zh') {
        $hooks = [
            '要不要我下一条直接给你“最快可执行路径”？',
            '想不想我帮你把路线压成“最省时间版本”？',
            '要的话我下一步就给你“最稳+最快”的行动清单。',
        ];
    } else {
        $hooks = [
            'Want the fastest realistic path next?',
            'Want me to map the quickest step-by-step route for you?',
            'If you want, next message I’ll give you the fastest low-risk action plan.',
        ];
    }

    $index = abs($currentAskNumber) % count($hooks);
    return $hooks[$index];
}

private function buildBrevityConstraintNote(): string
{
    return "\n\n## FREE PLAN BREVITY MODE\nThe user is on a free or starter plan and has used all their free chats.\nKeep your reply SHORT — maximum 2-3 sentences or up to 3 bullets.\nGive a genuinely useful quick answer but do NOT go into full step-by-step depth.\nDo NOT mention the free plan, chat limits, upgrades, or pricing in your reply — that note will be appended separately after your reply.\n";
}

private function buildFreePlanEngagementPrompt(): string
{
    return "\n\n## FREE PLAN ENGAGEMENT MODE\nThe user is on a free plan. Your job is to be genuinely helpful first, then naturally make an upgrade feel useful if it fits.\n\nStrategy:\n- Start with a direct, practical answer in simple English.\n- Give enough detail to be useful, not vague or teaser-like.\n- Keep the tone human and supportive, not dramatic or salesy.\n- If there is an important detail that depends on the user's personal case, say that clearly and invite one focused follow-up question.\n- You may hint that a personalised roadmap or deeper review is available, but do NOT manufacture fake mystery or cliffhangers.\n\nTONE: Warm, practical, clear, and natural. Like a knowledgeable person explaining the next steps properly.\nDo NOT mention pricing, free plan limits, or upgrades in the reply — that's handled separately.\n";
}

private function sanitizeVisibleReplyText(string $text): string
{
    $processed = (string)$text;

    $processed = preg_replace(
        '/信息基于xAI内部集合检索的文件/u',
        '信息基于AI-mmi内部集合检索的资料',
        $processed
    );

    // Remove markdown citation links like [[1]](url) and plain [1] markers.
    $processed = preg_replace('/\[\[(\d+)\]\]\([^\)]+\)/u', '', $processed);
    $processed = preg_replace('/\[(\d+)\]\([^\)]+\)/u', '', $processed);
    $processed = preg_replace('/\[(\d+)\]/u', '', $processed);

    // Clean occasional stray prefix typo like "xHey — AI-mmi..."
    $processed = preg_replace('/^\s*x(?=Hey\s*[—-]\s*AI-mmi\b)/iu', '', $processed);

    // Collapse repeated spaces/tabs only; keep line breaks for readability.
    $processed = preg_replace('/[ \t]{2,}/u', ' ', $processed);
    $processed = preg_replace('/\n{3,}/u', "\n\n", $processed);

    return trim((string)$processed);
}


private function freePlanChatLimit(): int
{
    return max(1, (int)env('FREE_PLAN_CHAT_LIMIT', 10));
}

private function countMemberAskQuestions(int $memberId): int
{
    if ($memberId <= 0) {
        return 0;
    }

    return (int)DB::table('chat_log')
        ->where('member_id', $memberId)
        ->where('type', 'ask')
        ->count();
}

private function classifyEducationIntent(string $question): string
{
    return $this->classifyQuestionDomain($question) === 'education'
        ? 'education'
        : 'non-education';
}

private function classifyQuestionDomain(string $question): string
{
    $q = mb_strtolower(trim($question), 'UTF-8');
    if ($q === '') {
        return 'other';
    }

    $migrationKeywords = [
        'visa', 'migration', 'migrate', 'immigration', 'immigrate',
        'pr', 'permanent residency', 'residency', 'citizenship',
        'residence permit', 'work permit', 'student permit', 'dependent visa',
        'spouse visa', 'partner visa', 'visitor visa', 'tourist visa',
        'can i stay', 'stay in australia', 'move to australia', 'move to canada',
        'immigration lawyer', 'migration agent',
        'subclass', '189', '190', '491', '482', '485', '500', '600', '820', '801',
        'skillselect', 'points test', 'state nomination', 'bridging visa',
        '签证', '移民', '永居', '永久居留', '入籍', '公民', '技术移民', '雇主担保', '配偶签证', '工签', '学签',
    ];

    foreach ($migrationKeywords as $kw) {
        if (mb_strpos($q, $kw) !== false) {
            return 'migration';
        }
    }

    $educationKeywords = [
        'study', 'studying', 'student', 'university', 'college', 'course', 'program',
        'major', 'degree', 'tuition', 'scholarship', 'admission', 'campus',
        'intake', 'enrol', 'enroll', 'offer letter', 'coe',
        '留学', '学习', '学校', '大学', '学院', '课程', '专业', '学位', '学费', '奖学金', '入学', '录取'
    ];

    foreach ($educationKeywords as $kw) {
        if (mb_strpos($q, $kw) !== false) {
            return 'education';
        }
    }

    $migrationPatterns = [
        '/\b(subclass\s*\d{3}|\d{3}\s*visa)\b/u',
        '/\b(immigration|migrat(e|ion)|citizenship|permanent\s+residen|pr\s+pathway)\b/u',
        '/\b(spouse|partner|dependent|visitor|tourist|student|work)\s+visa\b/u',
        '/(签证|移民|永居|入籍|工签|学签)/u',
    ];

    foreach ($migrationPatterns as $pattern) {
        if (preg_match($pattern, $q)) {
            return 'migration';
        }
    }

    return 'other';
}

private function detectLangZhOrEn(string $q): string
{
    // Chinese (Simplified/Traditional)
    if (preg_match('/[\x{4e00}-\x{9fff}\x{3400}-\x{4dbf}]/u', $q)) {
        return 'zh';
    }
    // Vietnamese — unique diacritics not found in other Latin-script languages
    if (preg_match('/[ắặẳẵặăắặẳẵđ]/u', $q) ||
        preg_match('/[àáâãèéêìíòóôõùúăđÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐ]/u', $q)) {
        // Narrow it to Vietnamese by checking for Vietnamese-specific combos
        if (preg_match('/[ắặẳẵắặẳẵ]/u', $q) ||
            preg_match('/[ộổỗởợọốồổỗộ]/u', $q) ||
            preg_match('/[ừứửữựưừứửữự]/u', $q) ||
            preg_match('/[ệểễếề]/u', $q) ||
            preg_match('/[ịỉĩ]/u', $q) ||
            preg_match('/[ọỏõ]/u', $q) ||
            preg_match('/[ầấẩẫậ]/u', $q) ||
            preg_match('/[đ]/u', $q)) {
            return 'vi';
        }
    }
    // Japanese (Hiragana / Katakana)
    if (preg_match('/[\x{3040}-\x{30ff}]/u', $q)) {
        return 'ja';
    }
    // Korean (Hangul)
    if (preg_match('/[\x{ac00}-\x{d7af}]/u', $q)) {
        return 'ko';
    }
    return 'en';
}

private function buildStrictLanguageInstruction(string $lang): string
{
    // For Chinese: enforce Chinese reply (common use case for this platform).
    if ($lang === 'zh') {
        return "

## Language Rule
- The user wrote in Chinese. Reply ONLY in Chinese.
- Do NOT switch to English unless the user explicitly asks in English.
";
    }

    // For Vietnamese: explicitly tell the model to respond in Vietnamese.
    if ($lang === 'vi') {
        return "

## Language Rule
- The user wrote in Vietnamese. Reply ONLY in Vietnamese.
- Do NOT switch to English or any other language.
";
    }

    // For Japanese.
    if ($lang === 'ja') {
        return "

## Language Rule
- The user wrote in Japanese. Reply ONLY in Japanese.
";
    }

    // For Korean.
    if ($lang === 'ko') {
        return "

## Language Rule
- The user wrote in Korean. Reply ONLY in Korean.
";
    }

    // For all other languages (English, French, Spanish, etc.):
    // Do NOT force English — let the model mirror the user's language naturally,
    // as the main system prompt's Language Behaviour section already instructs.
    return "

## Language Rule
- Detect the language the user wrote in and reply in that exact same language.
- Do NOT default to English if the user wrote in a different language.
";
}

private function containsCjk(string $text): bool
{
    if (trim($text) === '') {
        return false;
    }

    return (bool)preg_match('/[\x{3400}-\x{9FFF}]/u', $text);
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

// ────────────────────────────────────────────────────────────────────────────
// D-ID Avatar – server-side proxy (keeps the API key out of the browser)
// ────────────────────────────────────────────────────────────────────────────

/** Proxy a D-ID REST call; returns ['code' => int, 'body' => string] */
private function didApiCall(string $method, string $path, array $body = []): array
{
    $apiKey = env('DID_API_KEY', '');
    if (empty($apiKey)) {
        return ['code' => 503, 'body' => json_encode(['error' => 'Avatar not configured'])];
    }

    $ch = curl_init('https://api.d-id.com' . $path);
    // D-ID API key is {base64(email)}:{secret} — needs one more base64 encoding for Basic auth
    $authValue = (strpos($apiKey, ':') !== false)
        ? base64_encode($apiKey)
        : $apiKey;
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Basic ' . $authValue,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_PROXY          => '',
    ]);

    if (!empty($body) || in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => (string) $response];
}

/** POST /home/avatar/stream  – create a new D-ID WebRTC stream session */
public function avatarStream(\Illuminate\Http\Request $request)
{
    $presenterImg = env(
        'DID_PRESENTER_IMG',
        'https://create-images-results.d-id.com/DefaultPresenters/Noelle_f/image.jpeg'
    );

    $result = $this->didApiCall('POST', '/talks/streams', [
        'source_url'         => $presenterImg,
        'compatibility_mode' => 'on',
    ]);

    return response($result['body'], $result['code'])
        ->header('Content-Type', 'application/json');
}

/** POST /home/avatar/stream/{id}/sdp  – forward WebRTC SDP answer */
public function avatarStreamSdp(\Illuminate\Http\Request $request, string $streamId)
{
    $result = $this->didApiCall('POST', "/talks/streams/{$streamId}/sdp", [
        'answer'     => $request->input('answer'),
        'session_id' => $request->input('session_id'),
    ]);

    return response($result['body'], $result['code'])
        ->header('Content-Type', 'application/json');
}

/** POST /home/avatar/stream/{id}/ice  – forward ICE candidate */
public function avatarStreamIce(\Illuminate\Http\Request $request, string $streamId)
{
    $result = $this->didApiCall('POST', "/talks/streams/{$streamId}/ice", [
        'candidate'  => $request->input('candidate'),
        'session_id' => $request->input('session_id'),
    ]);

    return response($result['body'], $result['code'])
        ->header('Content-Type', 'application/json');
}

/** POST /home/avatar/stream/{id}/speak  – make the avatar say text */
public function avatarSpeak(\Illuminate\Http\Request $request, string $streamId)
{
    $text      = mb_substr((string) $request->input('text', ''), 0, 500);
    $sessionId = (string) $request->input('session_id', '');

    $result = $this->didApiCall('POST', "/talks/streams/{$streamId}", [
        'script' => [
            'type'     => 'text',
            'input'    => $text,
            'provider' => [
                'type'     => 'microsoft',
                'voice_id' => env('DID_VOICE_ID', 'en-US-JennyNeural'),
            ],
        ],
        'config'     => ['stitch' => false],
        'session_id' => $sessionId,
    ]);

    return response($result['body'], $result['code'])
        ->header('Content-Type', 'application/json');
}

/** DELETE /home/avatar/stream/{id}  – close the stream session */
public function avatarStreamClose(\Illuminate\Http\Request $request, string $streamId)
{
    $result = $this->didApiCall('DELETE', "/talks/streams/{$streamId}", [
        'session_id' => $request->input('session_id', ''),
    ]);

    return response($result['body'], $result['code'])
        ->header('Content-Type', 'application/json');
}
}
 // ← ADD THIS CLOSING BRACE FOR THE CLASS!