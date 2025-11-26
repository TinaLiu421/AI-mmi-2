<?php
namespace App\Http\Controllers\Web;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Posts extends Home {
    
    protected $_posts_model = null;
    protected $_qa_table = 'posts_comments';
    protected $_ai_member_id = 0;
    
    public function __construct($data) {
        parent::__construct($data);
        
        // load model
        $this->_posts_model = $this->loadModel('posts');

        $this->_qa_table = $this->resolveQaTable();
        $this->_ai_member_id = (int)config('app_portal.ai_member_id', 0);
    }

    public function qaAsk(\Illuminate\Http\Request $request, $postId)
    {
        if (empty($this->_current_member)) {
            return response()->json([
                'status'  => 401,
                'message' => 'Please register or log in before asking a question.',
            ], 401);
        }

        $postId = (int)$postId;
        $question = trim((string)$request->input('question', ''));
        if ($question === '') {
            return response()->json([
                'status'  => 422,
                'message' => 'Question is required.',
            ], 422);
        }

        $language = $this->detectLanguage($question);
        $inScope  = $this->isScholarshipOrPartnerSchoolQuestion($question);

        // ensure post exists and active
        $postExists = DB::table('member_posts')
            ->where('id', $postId)
            ->where('status', '>', 0)
            ->exists();

        if (!$postExists) {
            return response()->json([
                'status'  => 404,
                'message' => 'Post not found.',
            ], 404);
        }

        $memberId = (int)$this->_current_member['id'];
        $now      = Carbon::now();
        $answerText = null;

        if ($inScope) {

            $systemPrompt = "
You are AI-mmi answering scholarship Q&A.

Rules:
- ONLY answer questions about the AI-mmi Scholarship or these partner schools: SBTA–SELA (Adelaide or Brisbane), QAT (Brisbane or Sydney), ACTI (Brisbane, Gold Coast, Cairns), QII (Brisbane), Rosehill College (Sydney).
- Always search AI-mmi internal collections first; use web search only if collections have no relevant information.
- If neither internal collections nor web search provide reliable information, state that you do not have enough information instead of guessing.
- Keep answers concise and factual.
- Detect whether the user's message is Chinese; if yes, reply in Chinese; otherwise reply in English.
- Do not mention xAI, Grok, LLMs, tools, citations, collection IDs, or technical details in the user-visible answer.
";

            $x = $this->callXaiResponses($question, [
                'temperature'        => 0.2,
                'max_output_tokens'  => 600,
                'model'              => 'grok-4-fast-reasoning',
                'enable_search'      => true,
                'collection_ids'     => ['collection_1c89e82d-3b05-4bb6-9bf7-aae3181a3a9c'],
                'vector_store_ids'   => [],
                'system'             => $systemPrompt,
            ]);

            if (!is_array($x) || empty($x['ok']) || empty($x['text'])) {
                // 这里是「模型挂了」的兜底提示，但仍然是 in-scope 的
                $answerText = is_array($x)
                    ? ($x['text'] ?? 'Sorry, I cannot process this question right now.')
                    : (string)$x;
            } else {
                $answerText = $x['text'];
            }

            $answerText = trim((string)$answerText);

            // 万一真的空，就直接不给回复，不写 AI 记录
            if ($answerText === '') {
                $answerText = null;
            } else {
                $maxTokens  = 600;
                $answerText = $this->truncateByTokens($answerText, $maxTokens);
                $answerText = $this->appendCtaLine($answerText, $language, $maxTokens);
            }
        }

        $questionPayload = [
            'member_id'  => $memberId,
            'posts_id'   => $postId,
            'content'    => $question,
            'status'     => 1,
            'created_by' => $memberId,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if ($this->qaHasParentColumn()) {
            $questionPayload['parent_id'] = null;
        }

        try {
            DB::beginTransaction();

            $questionId = DB::table($this->_qa_table)->insertGetId($questionPayload);

            $timestamps = [
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];

            $answerResponse = null;

            if ($inScope && $answerText !== null && trim($answerText) !== '') {
                $answerPayload = [
                    'member_id'  => $memberId,
                    'posts_id'   => $postId,
                    'content'    => $answerText,
                    'status'     => 1,
                    'created_by' => $this->_ai_member_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($this->qaHasParentColumn()) {
                    $answerPayload['parent_id'] = $questionId;
                }

                $answerId = DB::table($this->_qa_table)->insertGetId($answerPayload);

                $answerResponse = array_merge([
                    'id'            => $answerId,
                    'member_id'     => $memberId,
                    'posts_id'      => $postId,
                    'content'       => $answerText,
                    'status'        => 1,
                    'created_by'    => $this->_ai_member_id,
                    'created_human' => $now->diffForHumans(),
                    'owner'         => [
                        'name'   => 'AI-mmi',
                        'avatar' => 'asset/image/logo-mmi.png',
                        'badge'  => 'Assistant',
                    ],
                ], $timestamps);
            }

            DB::commit();

            return response()->json([
                'status'   => 200,
                'message'  => 'ok',
                'question' => array_merge([
                    'id'         => $questionId,
                    'member_id'  => $memberId,
                    'posts_id'   => $postId,
                    'content'    => $question,
                    'status'     => 1,
                    'created_by' => $memberId,
                    'created_human' => $now->diffForHumans(),
                    'owner'      => $this->memberPresenter($this->_current_member),
                ], $timestamps),
                'answer'   => $answerResponse,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('posts.qaAsk failed', [
                'post_id'   => $postId,
                'member_id' => $this->_current_member['id'] ?? null,
                'error'     => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Unable to process your question right now.',
            ], 500);
        }
    }
    
    public function details($id = 0) {
        $page_data = $this->_posts_model->getByID($id);
        if(empty($page_data)) {
           // return 404 if not found
            return abort(404); 
        }
        $qa_items = $this->fetchQaItems((int)$id);
        
        $youtube_id = '';
        if(!empty($page_data['youtube_url'])) {
            $shortUrlRegex = '/youtu.be\/([a-zA-Z0-9_-]+)\??/i';
            $longUrlRegex = '/youtube.com\/((?:embed)|(?:watch)|(?:shorts))((?:\?v\=)|(?:\/))([a-zA-Z0-9_-]+)/i';

            $youtube_id = '';
            if (preg_match($longUrlRegex, $page_data['youtube_url'], $matches)) {
                $youtube_id = $matches[count($matches) - 1];
            }
            else if (preg_match($shortUrlRegex, $page_data['youtube_url'], $matches)) {
                $youtube_id = $matches[count($matches) - 1];
            }
        }
        

        // set meta
        $this->pageMeta(
        [
            'title'         =>  ((!empty($page_data['title']))?$page_data['title']:''),
            'description'   =>  substr($this->toPlainText($page_data['content']), 0, 180).'...',
            'image'         =>  ((!empty($page_data['photo']) && file_exists('upload/member_posts/'.$page_data['photo']))?($this->_mapping_data['app_url'].'/'.('upload/member_posts/'.$page_data['photo'])):(!empty($youtube_id)?'https://img.youtube.com/vi/'.$youtube_id.'/maxresdefault.jpg':''))
        ]);
        
        return $this->pageData(
        [
            'details'   =>  $page_data,
            'qa'        =>  [
                'items'      => $qa_items,
                'can_ask'    => !empty($this->_current_member),
                'member_avatar' => !empty($this->_current_member)
                    ? $this->memberAvatarPath($this->_current_member['avatar'] ?? '')
                    : 'asset/image/icon-member.png',
                'guest_text' => 'Please log in to ask a question.',
                'placeholder'=> 'Ask AI-mmi anything about this post...',
            ],
        ])->pageView();
    }

    private function resolveQaTable(): string
    {
        if (Schema::hasTable('posts_comments')) {
            return 'posts_comments';
        }
        if (Schema::hasTable('member_posts_comment')) {
            return 'member_posts_comment';
        }
        return 'posts_comments';
    }

    private function qaHasParentColumn(): bool
    {
        return Schema::hasTable($this->_qa_table) && Schema::hasColumn($this->_qa_table, 'parent_id');
    }

    private function buildAiAnswerPlaceholder(string $question): string
    {
        return 'Thanks for your question. AI-mmi will review it and reply shortly.';
    }

    private function memberAvatarPath(?string $avatar): string
    {
        if (!empty($avatar)) {
            if (file_exists('upload/member_avatar/'.$avatar)) {
                return 'upload/member_avatar/'.$avatar;
            }
            if (file_exists('upload/member_logo/'.$avatar)) {
                return 'upload/member_logo/'.$avatar;
            }
        }
        return 'asset/image/icon-member.png';
    }

    private function memberPresenter(?array $member): array
    {
        $name   = $member['alias_name'] ?? 'User';
        $avatar = $this->memberAvatarPath($member['avatar'] ?? '');

        return [
            'name'   => $name,
            'avatar' => $avatar,
        ];
    }

    private function fetchQaItems(int $postId): array
    {
        $hasParent = $this->qaHasParentColumn();

        $questionsQuery = DB::table($this->_qa_table.' as c')
            ->leftJoin('member as m', 'c.member_id', '=', 'm.id')
            ->where('c.posts_id', $postId)
            ->where('c.status', 1)
            ->whereNull('c.deleted_at');

        if ($hasParent) {
            $questionsQuery->where(function ($q) {
                $q->whereNull('c.parent_id')->orWhere('c.parent_id', 0);
            });
        }

        $questions = $questionsQuery
            ->orderByDesc('c.created_at')
            ->select([
                'c.id',
                'c.content',
                'c.created_at',
                'c.member_id',
                'm.alias_name',
                'm.avatar',
            ])
            ->get();

        $questionIds = $questions->pluck('id')->all();
        $answersByParent = [];

        if ($hasParent && !empty($questionIds)) {
            $answers = DB::table($this->_qa_table.' as c')
                ->whereIn('c.parent_id', $questionIds)
                ->where('c.status', 1)
                ->whereNull('c.deleted_at')
                ->orderBy('c.created_at')
                ->select(['c.parent_id', 'c.content', 'c.created_at'])
                ->get();

            foreach ($answers as $answer) {
                $answersByParent[$answer->parent_id] = $answer;
            }
        }

        return $questions->map(function ($row) use ($answersByParent) {
            $question = [
                'id'              => $row->id,
                'content'         => $row->content,
                'member_name'     => $row->alias_name ?? 'User',
                'member_avatar'   => $this->memberAvatarPath($row->avatar ?? ''),
                'created_human'   => (!empty($row->created_at)) ? Carbon::parse($row->created_at)->diffForHumans() : '',
                'answer'          => null,
            ];

            if (!empty($answersByParent[$row->id])) {
                $ansContent = trim((string)$answersByParent[$row->id]->content);

                // 只有内容非空才认为有 AI 回答
                if ($ansContent !== '') {
                    $ans = $answersByParent[$row->id];
                    $question['answer'] = [
                        'content'       => $ansContent,
                        'member_name'   => 'AI-mmi',
                        'member_avatar' => 'asset/image/logo-mmi.png',
                        'created_human' => (!empty($ans->created_at)) ? Carbon::parse($ans->created_at)->diffForHumans() : '',
                        'badge'         => 'Assistant',
                    ];
                }
            }

            return $question;
        })->toArray();
    }

    private function detectLanguage(string $question): string
    {
        return preg_match('/[\x{4e00}-\x{9fff}]/u', $question) ? 'zh' : 'en';
    }

    private function isScholarshipOrPartnerSchoolQuestion(string $question): bool
    {
        $qLower = mb_strtolower($question, 'UTF-8');
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

    private function outOfScopeMessage(string $language): string
    {
        if ($language === 'zh') {
            return '当前 Q&A 仅用于解答 AI-mmi Scholarship 及合作院校相关问题…';
        }

        return 'This Q&A is reserved for questions about the AI-mmi Scholarship and partner schools…';
    }

    private function appendCtaLine(string $answer, string $language, int $maxTokens = 0): string
    {
        $cta = $language === 'zh'
            ? '欢迎使用位于POST卡片左下角的「Apply Now!」按钮通过AI-mmi快速申请，开启您的精彩留学之旅'
            : 'You’re welcome to use the “Apply Now!” button at the bottom-left of this post card to submit your AI-mmi application and start your exciting study journey.';

        $trimmed = rtrim($answer);
        $combined = $trimmed === '' ? $cta : $trimmed . "\n\n" . $cta;

        if ($maxTokens > 0) {
            $maxChars = $maxTokens * 4;
            if (mb_strlen($combined, 'UTF-8') > $maxChars) {
                $ctaChars = mb_strlen($cta, 'UTF-8');
                $budget   = $maxChars - $ctaChars - 2; // reserve for "\n\n"
                if ($budget <= 0) {
                    return $cta;
                }

                $trimmedAnswer = $trimmed === ''
                    ? ''
                    : rtrim(mb_substr($trimmed, 0, $budget, 'UTF-8'));

                return $trimmedAnswer === ''
                    ? $cta
                    : $trimmedAnswer . "\n\n" . $cta;
            }
        }

        return $combined;
    }

    private function truncateByTokens(string $text, int $maxTokens): string
    {
        if ($maxTokens <= 0) {
            return '';
        }

        $maxChars = $maxTokens * 4;
        $trimmed  = trim($text);

        if (mb_strlen($trimmed, 'UTF-8') <= $maxChars) {
            return $trimmed;
        }

        return mb_substr($trimmed, 0, $maxChars, 'UTF-8');
    }
}
