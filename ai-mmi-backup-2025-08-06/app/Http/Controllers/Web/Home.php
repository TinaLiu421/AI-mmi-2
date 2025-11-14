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
        $this->pageAction(function () {

            // === ① 读取参数（仅保留必要项） ===
            $question = $this->postParamValue('question', request()->input('question', ''));
            if (trim($question) === '') {
                $this->pageResult(['status' => 400, 'message' => 'Please enter a question.']);
                return;
            }

            if (is_array($question)) {
                $question = json_encode($question, JSON_UNESCAPED_UNICODE);
            } elseif (!is_string($question)) {
                $question = strval($question);
            }
            $rawQuestion = trim((string)$question);

            // RAG 已停用，保留原参数读取逻辑供参考
            // $useRag      = (int)($this->postParamValue('use_rag', request()->input('use_rag', 0)));
            // $override    = (string)$this->postParamValue('override_reply', request()->input('override_reply', ''));
            $useRag   = 0;
            $override = '';

            // === ② 会话身份（保留即可） ===
            $member    = $this->_current_member ?: null;
            $memberId  = $member['id'] ?? null;
            $guestId   = $this->getMyCookie('guest_id');
            $sessionId = session()->getId();

            // === ③ 生成回复：不做任何“Agent 限制/语言判断/历史读取”===
            $reply       = '';
            $replySource = 'model';
            $aiOwnerName = 'AI-mmi';

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

            // ❸ 纯模型
            if ($reply === '') {
                $x = $this->callXaiResponses($rawQuestion, [
                    'temperature' => 0.2,
                    'max_output_tokens' => 2048,
                    'model' => 'grok-4-fast-reasoning',
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
                } else {
                    $reply = $x['text'];
                    $reply = preg_replace(
                        '/信息基于xAI内部集合检索的文件/u',
                        '信息基于AI-mmi内部集合检索的资料',
                        $reply
                    );

                    // if (!empty($x['citations'])) {
                    //     $reply .= "\n\n---\n**Sources**:\n";
                    //     foreach ($x['citations'] as $i => $c) {
                    //         $title = $c['title'] ?: ('Source #' . ($i + 1));
                    //         $url   = $c['url']   ?: '';
                    //         $reply .= "- {$title}" . ($url ? " — {$url}" : "") . "\n";
                    //     }
                    // }
                }

                $replySource = 'model';
                $aiOwnerName = 'AI-mmi';
            }

            // === ④ 入库 + 返回 ===
            $this->storeChat($memberId, $guestId, $sessionId, $rawQuestion, $reply, $replySource, $aiOwnerName);
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
                $m['content']      = strtolower($m['type']) === 'ask' ? nl2br($m['content']) : $m['content'];
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
    private function storeChat($memberId, $guestId, $sessionId, $question, $reply, $source, $aiOwnerName)
    {
        try {
            $nowUtc = \Carbon\Carbon::now('UTC');
            $targetDate = (int)date('Ymd');
            \DB::beginTransaction();

            $askId = \DB::table('chat_log')->insertGetId([
                'member_id'   => $memberId,
                'guest_id'    => $guestId,
                'session_id'  => $sessionId,
                'related_id'  => 0,
                'target_date' => $targetDate,
                'type'        => 'ask',
                'content'     => $question,
                'status'      => 1,
                'created_at'  => $nowUtc,
                'updated_at'  => $nowUtc,
            ]);
            \DB::table('chat_log')->where('id', $askId)->update(['related_id' => $askId]);

            \DB::table('chat_log')->insert([
                'member_id'   => $memberId,
                'guest_id'    => $guestId,
                'session_id'  => $sessionId,
                'related_id'  => $askId,
                'target_date' => $targetDate,
                'type'        => 'reply',
                'content'     => $reply,
                'status'      => 1,
                'created_at'  => $nowUtc,
                'updated_at'  => $nowUtc,
            ]);

            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollBack();
            \Log::error('CHAT DB INSERT FAIL: ' . $e->getMessage());
        }
    }

    // ✅ 输出统一处理
    private function jsonReply($question, $reply, $source, $member)
    {
        [$member_owner_name, $member_owner_avatar] = $this->ownerVisual($member);
        $nowUtcIso = \Carbon\Carbon::now('UTC')->toIso8601String();
        $this->pageResult([
            'status'               => 200,
            'content'              => nl2br($question),
            'reply'                => $reply,
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
            return ['ok'=>false, 'text'=>'[Upstream Error] Missing API key', 'citations'=>[], 'raw'=>null];
        }

        $url     = rtrim(env('XAI_API_BASE', 'https://api.x.ai'), '/') . '/v1/responses';
        $model   = $opts['model'] ?? 'grok-4-fast-reasoning';
        $system  = $opts['system'] ?? null;

        // —— 温度：给默认值 + 边界
        $temperature = array_key_exists('temperature', $opts)
            ? (float)$opts['temperature']
            : (float)env('XAI_TEMPERATURE', 0.2);
        if ($temperature < 0) $temperature = 0.0;
        if ($temperature > 1) $temperature = 1.0;

        // —— 多轮上下文：上一轮 response_id（调试时你也可以先注释掉，避免旧对话干扰）
        $prevId = $this->getXaiPrevResponseId();

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

        // === File Search（用你的 collection id 当作 vector_store_ids）===
        $collectionIds  = isset($opts['collection_ids']) && is_array($opts['collection_ids'])
            ? array_values($opts['collection_ids']) : [];

        // 如果你确认 xAI 那边就是用 collection_xxx 作为向量库 id，那这里就直接塞进去
        $vectorStoreIds = $collectionIds;  

        if (!empty($vectorStoreIds)) {
            $tools[] = [
                'type'             => 'file_search',
                'vector_store_ids' => $vectorStoreIds,
                'max_num_results'  => (int)($opts['file_search_max'] ?? 15),
            ];
        }

        // === Web Search（可选）===
        $enableSearch = array_key_exists('enable_search', $opts) ? (bool)$opts['enable_search'] : true;
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

        \Log::info('xAI payload => ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // —— 请求
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
        ]);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            \Log::error('xAI CURL error: '.$err);
            return ['ok'=>false, 'text'=>'[Upstream Error] '.$err, 'citations'=>[], 'raw'=>null];
        }
        curl_close($ch);

        $data = json_decode($resp, true);

        // 额外日志：看看工具调用情况（Responses 原始 JSON 字段）
        \Log::info('xAI raw response (truncated) => ' . mb_substr($resp ?? '', 0, 800));

        if ($http < 200 || $http >= 300 || !is_array($data)) {
            \Log::error("xAI HTTP {$http} body: ".$resp);
            return ['ok'=>false, 'text'=>"[Upstream Error] HTTP {$http}", 'citations'=>[], 'raw'=>$resp];
        }

        // —— 保存本轮 response_id，供下一轮续接
        if (!empty($data['id']) && is_string($data['id'])) {
            $this->saveXaiPrevResponseId($data['id']);
        }

        // —— 提取文本 & 引用
        $text      = $this->xaiExtractText($data);
        $citations = $this->xaiExtractCitations($data);

        $toolCalls = $this->xaiExtractToolCalls($data);
        \Log::info('xAI tool_calls(extracted) => ' . json_encode($toolCalls, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        \Log::info('xAI citations(extracted)  => ' . json_encode($citations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));


        if ($text === '') {
            \Log::warning('xAI parsed empty text; payload head='.mb_substr($resp, 0, 300));
            return ['ok'=>false, 'text'=>'[Upstream Error] Empty output', 'citations'=>[], 'raw'=>$data];
        }

        return ['ok'=>true, 'text'=>$text, 'citations'=>$citations, 'raw'=>$data];
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




}
