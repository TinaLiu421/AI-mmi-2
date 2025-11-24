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

        $answerText = $this->buildAiAnswerPlaceholder($question);

        try {
            DB::beginTransaction();

            $questionId = DB::table($this->_qa_table)->insertGetId($questionPayload);

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

            DB::commit();

            $timestamps = [
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];

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
                'answer'   => array_merge([
                    'id'         => $answerId,
                    'member_id'  => $memberId,
                    'posts_id'   => $postId,
                    'content'    => $answerText,
                    'status'     => 1,
                    'created_by' => $this->_ai_member_id,
                    'created_human' => $now->diffForHumans(),
                    'owner'      => [
                        'name'   => 'AI-mmi',
                        'avatar' => 'asset/image/logo-mmi.png',
                        'badge'  => 'Assistant',
                    ],
                ], $timestamps),
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
                $ans = $answersByParent[$row->id];
                $question['answer'] = [
                    'content'       => $ans->content,
                    'member_name'   => 'AI-mmi',
                    'member_avatar' => 'asset/image/logo-mmi.png',
                    'created_human' => (!empty($ans->created_at)) ? Carbon::parse($ans->created_at)->diffForHumans() : '',
                    'badge'         => 'Assistant',
                ];
            }

            return $question;
        })->toArray();
    }
}
