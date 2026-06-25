<?php
namespace App\Http\Controllers\Web;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Request;

class Study_Plans extends Home
{
    private const COURSES_PREFIX = '__AIMMI_COURSES_JSON__:';

    private function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function hasColumnSafe(string $table, string $column): bool
    {
        try {
            return Schema::hasTable($table) && Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────

    private function isInstitutionAccount(): bool
    {
        if (empty($this->_current_member) || (int)($this->_current_member['type'] ?? 0) !== 3) {
            return false;
        }
        if (!$this->hasTable('member_details')) {
            return false;
        }
        $memberId = (int)$this->_current_member['id'];
        $details  = DB::table('member_details')->where('member_id', $memberId)->first();
        return $details && (int)$details->institution_type === 2;
    }

    private function getFeaturedPrograms(): array
    {
        if (!$this->hasTable('institution_profiles') || !$this->hasTable('member')) {
            return [];
        }

        $hasCoursesJson = $this->hasColumnSafe('institution_profiles', 'courses_json');
        $hasPrograms    = $this->hasColumnSafe('institution_profiles', 'programs');
        if (!$hasCoursesJson && !$hasPrograms) {
            return [];
        }

        $select = ['ip.id', 'ip.member_id', 'ip.institute_name', 'ip.summary', 'm.avatar', 'm.alias_name'];
        if ($hasCoursesJson) $select[] = 'ip.courses_json';
        if ($hasPrograms)    $select[] = 'ip.programs';

        $rows = DB::table('institution_profiles as ip')
            ->join('member as m', 'm.id', '=', 'ip.member_id')
            ->where('ip.status', 1)
            ->whereNull('ip.deleted_at')
            ->select($select)
            ->orderBy('ip.id', 'asc')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $arr     = (array)$row;
            $courses = $this->extractCourses($arr, $hasCoursesJson);
            if (!empty($courses)) {
                $arr['courses'] = $courses;
                $out[]          = $arr;
            }
        }
        return array_slice($out, 0, 6);
    }

    private function extractCourses(array $row, bool $hasCoursesJson): array
    {
        if ($hasCoursesJson && !empty($row['courses_json'])) {
            $d = json_decode($row['courses_json'], true);
            if (is_array($d)) {
                if (isset($d['courses']) && is_array($d['courses'])) $d = $d['courses'];
                if (array_keys($d) === range(0, count($d) - 1)) {
                    $c = array_filter($d, fn($i) => is_array($i) && !empty($i));
                    if (!empty($c)) return array_values($c);
                }
            }
        }
        $raw = (string)($row['programs'] ?? '');
        if (strpos($raw, self::COURSES_PREFIX) === 0) {
            $raw = substr($raw, strlen(self::COURSES_PREFIX));
        }
        if ($raw === '') return [];
        $d = json_decode($raw, true);
        if (!is_array($d)) return [];
        if (isset($d['courses']) && is_array($d['courses'])) $d = $d['courses'];
        if (array_keys($d) !== range(0, count($d) - 1)) return [];
        $c = array_filter($d, fn($i) => is_array($i) && !empty($i));
        return array_values($c);
    }

    private function getDreamByMember(int $memberId): ?array
    {
        if (!$this->hasTable('app_student_dreams')) {
            return null;
        }

        $row = DB::table('app_student_dreams')
            ->where('member_id', $memberId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->first();
        return $row ? (array)$row : null;
    }

    private function getDreamByMemberSafe(): ?array
    {
        $memberId = (int)($this->_current_member['id'] ?? 0);
        if (!$memberId) return null;
        return $this->getDreamByMember($memberId);
    }

    private function dreamSocialCounts(int $dreamId): array
    {
        $likes = 0;
        $comments = 0;

        if ($this->hasTable('app_student_dreams_like')) {
            $likes = DB::table('app_student_dreams_like')
                ->where('dream_id', $dreamId)->where('status', 1)->count();
        }
        if ($this->hasTable('app_student_dreams_comment')) {
            $comments = DB::table('app_student_dreams_comment')
                ->where('dream_id', $dreamId)->where('status', 1)->whereNull('deleted_at')->count();
        }

        return ['likes' => $likes, 'comments' => $comments];
    }

    private function userLikedDream(int $dreamId): bool
    {
        $memberId = (int)($this->_current_member['id'] ?? 0);
        if (!$memberId || !$this->hasTable('app_student_dreams_like')) return false;
        return DB::table('app_student_dreams_like')
            ->where('dream_id', $dreamId)
            ->where('member_id', $memberId)
            ->where('status', 1)
            ->exists();
    }

    // ─────────────────────────────────────────────────────────────
    //  Pages
    // ─────────────────────────────────────────────────────────────

    public function index()
    {
        $this->pageMeta([
            'title'       => 'Study Plans',
            'description' => 'Your personalised study journey — Dreams, Matches, and the NextGen Challenge',
        ]);

        $isInstitution = $this->isInstitutionAccount();
        $dream         = null;
        $likes         = 0;
        $comments      = 0;
        $commentRows   = [];
        $userLiked     = false;
        $recentDreams  = [];

        if (!$isInstitution) {
            $dream = $this->getDreamByMemberSafe();
            if ($dream) {
                $counts   = $this->dreamSocialCounts((int)$dream['id']);
                $likes    = $counts['likes'];
                $comments = $counts['comments'];
                $userLiked = $this->userLikedDream((int)$dream['id']);
                $commentRows = $this->loadDreamComments((int)$dream['id']);
            }
            // Recent dreams from other students for inspiration (max 6)
            if ($this->hasTable('app_student_dreams') && $this->hasTable('member')) {
                $memberId = (int)($this->_current_member['id'] ?? 0);
                $recentDreams = DB::table('app_student_dreams as d')
                    ->join('member as m', 'm.id', '=', 'd.member_id')
                    ->where('d.status', 1)
                    ->whereNull('d.deleted_at')
                    ->when($memberId, fn($q) => $q->where('d.member_id', '<>', $memberId))
                    ->select(['d.id', 'd.member_id', 'd.title', 'd.description', 'd.photo', 'm.alias_name', 'm.avatar'])
                    ->orderBy('d.id', 'desc')
                    ->limit(6)
                    ->get()
                    ->map(fn($r) => (array)$r)
                    ->toArray();
            }
        }

        $featuredPrograms = $this->getFeaturedPrograms();

        return $this->pageData([
            'is_institution'   => $isInstitution,
            'dream'            => $dream,
            'likes'            => $likes,
            'comments_count'   => $comments,
            'comments'         => $commentRows,
            'user_liked'       => $userLiked,
            'recent_dreams'    => $recentDreams,
            'featured_programs'=> $featuredPrograms,
        ])->pageView('study_plans');
    }

    // ─────────────────────────────────────────────────────────────
    //  AJAX: Dream CRUD
    // ─────────────────────────────────────────────────────────────

    public function save_dream()
    {
        if (!$this->hasTable('app_student_dreams')) {
            $this->pageResult(['status' => 503, 'message' => 'Dream feature is not available yet.']);
            return;
        }

        if (empty($this->_current_member)) {
            $this->pageResult(['status' => 401, 'message' => 'Please log in to save your dream.']);
            return;
        }
        if ($this->isInstitutionAccount()) {
            $this->pageResult(['status' => 403, 'message' => 'Not available for institution accounts.']);
            return;
        }

        $memberId    = (int)$this->_current_member['id'];
        $title       = trim((string)$this->getParamValue('title', ''));
        $description = trim((string)$this->getParamValue('description', ''));

        $existing = DB::table('app_student_dreams')
            ->where('member_id', $memberId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->first();

        $now = now();

        // Handle main photo upload
        $photoFilename = $existing ? ($existing->photo ?? null) : null;
        if (!empty($file = Request::file('photo'))) {
            $result = $this->uploadDreamFile($file, 'photo');
            if ($result['ok']) $photoFilename = $result['filename'];
        }

        // Handle gallery uploads (up to 5 extra images)
        $gallery = $existing ? json_decode($existing->gallery_json ?? '[]', true) : [];
        if (!is_array($gallery)) $gallery = [];
        if (!empty($files = Request::file('gallery'))) {
            if (!is_array($files)) $files = [$files];
            foreach ($files as $gf) {
                if (count($gallery) >= 5) break;
                $result = $this->uploadDreamFile($gf, 'gallery');
                if ($result['ok']) $gallery[] = $result['filename'];
            }
        }

        // Handle gallery deletion
        $deleteGallery = $this->getParamValue('delete_gallery', '');
        if (!empty($deleteGallery)) {
            $toDelete = explode(',', $deleteGallery);
            $gallery  = array_values(array_filter($gallery, fn($f) => !in_array($f, $toDelete)));
        }

        if ($existing) {
            DB::table('app_student_dreams')->where('id', $existing->id)->update([
                'title'        => $title ?: null,
                'description'  => $description ?: null,
                'photo'        => $photoFilename,
                'gallery_json' => json_encode($gallery),
                'updated_by'   => $memberId,
                'updated_at'   => $now,
            ]);
            $dreamId = (int)$existing->id;
        } else {
            $dreamId = DB::table('app_student_dreams')->insertGetId([
                'member_id'    => $memberId,
                'title'        => $title ?: null,
                'description'  => $description ?: null,
                'photo'        => $photoFilename,
                'gallery_json' => json_encode($gallery),
                'status'       => 1,
                'created_by'   => $memberId,
                'created_at'   => $now,
                'updated_by'   => $memberId,
                'updated_at'   => $now,
            ]);
        }

        $this->pageResult([
            'status'  => 'ok',
            'message' => 'Dream saved.',
            'dream_id'=> $dreamId,
            'photo'   => $photoFilename ? 'upload/student_dreams/'.$photoFilename : null,
            'gallery' => array_map(fn($f) => 'upload/student_dreams/'.$f, $gallery),
        ]);
    }

    private function uploadDreamFile($file, string $type): array
    {
        $ext      = strtolower($file->getClientOriginalExtension());
        $allowed  = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowed)) {
            return ['ok' => false, 'error' => 'Invalid file type.'];
        }
        if ($file->getSize() > 10 * 1024 * 1024) {
            return ['ok' => false, 'error' => 'File too large (max 10MB).'];
        }
        $filename = md5(uniqid(rand())).'.'.$ext;
        $location = public_path('upload/student_dreams');
        if (!file_exists($location)) {
            @mkdir($location, 0755, true);
        }
        if (!$file->move($location, $filename)) {
            return ['ok' => false, 'error' => 'Upload failed.'];
        }
        // Resize to reasonable dimensions
        try {
            \Intervention\Image\Facades\Image::make($location.'/'.$filename)
                ->resize(1200, 1200, function ($c) {
                    $c->aspectRatio();
                    $c->upsize();
                })->save($location.'/'.$filename);
        } catch (\Throwable $e) {
            // keep original if resize fails
        }
        return ['ok' => true, 'filename' => $filename];
    }

    // ─────────────────────────────────────────────────────────────
    //  AJAX: Likes
    // ─────────────────────────────────────────────────────────────

    public function toggle_like()
    {
        if (!$this->hasTable('app_student_dreams_like')) {
            $this->pageResult(['status' => 503, 'message' => 'Like feature is not available yet.']);
            return;
        }

        if (empty($this->_current_member)) {
            $this->pageResult(['status' => 401, 'message' => 'Please log in to like.']);
            return;
        }

        $dreamId  = (int)$this->getParamValue('dream_id', 0);
        $memberId = (int)$this->_current_member['id'];

        if (!$dreamId) {
            $this->pageResult(['status' => 400, 'message' => 'Invalid dream.']);
            return;
        }

        $existing = DB::table('app_student_dreams_like')
            ->where('dream_id', $dreamId)
            ->where('member_id', $memberId)
            ->first();

        if ($existing) {
            $newStatus = ((int)$existing->status === 1) ? 0 : 1;
            DB::table('app_student_dreams_like')->where('id', $existing->id)->update([
                'status'     => $newStatus,
                'updated_by' => $memberId,
                'updated_at' => now(),
            ]);
            $liked = $newStatus === 1;
        } else {
            DB::table('app_student_dreams_like')->insert([
                'member_id'  => $memberId,
                'dream_id'   => $dreamId,
                'status'     => 1,
                'created_by' => $memberId,
                'created_at' => now(),
                'updated_by' => $memberId,
                'updated_at' => now(),
            ]);
            $liked = true;
        }

        $count = DB::table('app_student_dreams_like')
            ->where('dream_id', $dreamId)
            ->where('status', 1)
            ->count();

        $this->pageResult(['status' => 'ok', 'liked' => $liked, 'count' => $count]);
    }

    // ─────────────────────────────────────────────────────────────
    //  AJAX: Comments
    // ─────────────────────────────────────────────────────────────

    private function loadDreamComments(int $dreamId): array
    {
        if (!$this->hasTable('app_student_dreams_comment') || !$this->hasTable('member')) {
            return [];
        }

        return DB::table('app_student_dreams_comment as c')
            ->join('member as m', 'm.id', '=', 'c.member_id')
            ->where('c.dream_id', $dreamId)
            ->where('c.status', 1)
            ->whereNull('c.deleted_at')
            ->where('c.parent_id', 0)
            ->select(['c.id', 'c.member_id', 'c.content', 'c.created_at', 'm.alias_name', 'm.avatar'])
            ->orderBy('c.created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(fn($r) => (array)$r)
            ->toArray();
    }

    public function add_comment()
    {
        if (!$this->hasTable('app_student_dreams') || !$this->hasTable('app_student_dreams_comment')) {
            $this->pageResult(['status' => 503, 'message' => 'Comment feature is not available yet.']);
            return;
        }

        if (empty($this->_current_member)) {
            $this->pageResult(['status' => 401, 'message' => 'Please log in to comment.']);
            return;
        }

        $dreamId  = (int)$this->getParamValue('dream_id', 0);
        $content  = trim((string)$this->getParamValue('content', ''));
        $memberId = (int)$this->_current_member['id'];

        if (!$dreamId || $content === '') {
            $this->pageResult(['status' => 400, 'message' => 'Invalid input.']);
            return;
        }

        // Ensure dream exists
        $dreamExists = DB::table('app_student_dreams')
            ->where('id', $dreamId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->exists();
        if (!$dreamExists) {
            $this->pageResult(['status' => 404, 'message' => 'Dream not found.']);
            return;
        }

        $now = now();
        $id  = DB::table('app_student_dreams_comment')->insertGetId([
            'member_id'  => $memberId,
            'dream_id'   => $dreamId,
            'parent_id'  => 0,
            'content'    => $content,
            'status'     => 1,
            'created_by' => $memberId,
            'created_at' => $now,
            'updated_by' => $memberId,
            'updated_at' => $now,
        ]);

        $member  = DB::table('member')->where('id', $memberId)->first();
        $count   = DB::table('app_student_dreams_comment')
            ->where('dream_id', $dreamId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->count();

        $this->pageResult([
            'status'     => 'ok',
            'comment_id' => $id,
            'count'      => $count,
            'comment'    => [
                'id'         => $id,
                'member_id'  => $memberId,
                'content'    => htmlspecialchars($content, ENT_QUOTES, 'UTF-8'),
                'created_at' => $now->format('Y-m-d H:i'),
                'alias_name' => $member->alias_name ?? 'User',
                'avatar'     => $member->avatar ?? null,
            ],
        ]);
    }

    public function get_comments()
    {
        $dreamId = (int)$this->getParamValue('dream_id', 0);
        if (!$dreamId) {
            $this->pageResult(['status' => 400, 'message' => 'Invalid dream.']);
            return;
        }
        $rows = $this->loadDreamComments($dreamId);
        $this->pageResult(['status' => 'ok', 'comments' => $rows]);
    }

    // ─────────────────────────────────────────────────────────────
    //  View a specific student's dream (public profile)
    // ─────────────────────────────────────────────────────────────

    public function view($memberId)
    {
        if (!$this->hasTable('app_student_dreams') || !$this->hasTable('member')) {
            return abort(404);
        }

        $memberId = (int)$memberId;
        if (!$memberId) return abort(404);

        $dream = $this->getDreamByMember($memberId);
        if (!$dream) return abort(404);

        $dreamOwner = DB::table('member')->where('id', $memberId)->first();
        if (!$dreamOwner) return abort(404);

        $counts   = $this->dreamSocialCounts((int)$dream['id']);
        $userLiked = $this->userLikedDream((int)$dream['id']);
        $comments = $this->loadDreamComments((int)$dream['id']);
        $featuredPrograms = $this->getFeaturedPrograms();

        $this->pageMeta([
            'title'       => ($dream['title'] ?? ($dreamOwner->alias_name ?? 'Student'))."'s Dream",
            'description' => substr(strip_tags($dream['description'] ?? ''), 0, 160),
        ]);

        return $this->pageData([
            'is_institution'   => false,
            'dream'            => $dream,
            'dream_owner'      => (array)$dreamOwner,
            'is_own_dream'     => (int)($this->_current_member['id'] ?? 0) === $memberId,
            'likes'            => $counts['likes'],
            'comments_count'   => $counts['comments'],
            'comments'         => $comments,
            'user_liked'       => $userLiked,
            'recent_dreams'    => [],
            'featured_programs'=> $featuredPrograms,
            'view_mode'        => true,
        ])->pageView('study_plans');
    }

    // ─────────────────────────────────────────────────────────────
    //  AJAX: Get scholarship courses from partner institutions
    // ─────────────────────────────────────────────────────────────

    /** Returns the active subscription plan code (e.g. 'free','all_ai','hybrid','vip') for a member. */
    private function getMemberPlanCode(int $memberId): string
    {
        if ($memberId <= 0) return 'free';
        try {
            $row = DB::table('subscriptions as s')
                ->join('plans as p', 'p.id', '=', 's.plan_id')
                ->where('s.member_id', $memberId)
                ->where('s.status', 'active')
                ->where(function ($q) {
                    $q->whereNull('s.ends_at')->orWhere('s.ends_at', '>', now());
                })
                ->orderByRaw("FIELD(p.code,'vip','hybrid','diy','all_ai') ASC")
                ->select('p.code')
                ->first();
            return $row ? (string)$row->code : 'free';
        } catch (\Throwable $e) {
            return 'free';
        }
    }

    public function get_scholarships()
    {
        if (!$this->hasTable('institution_profiles') || !$this->hasTable('member')) {
            $this->pageResult(['status' => 200, 'scholarships' => [], 'has_agent_plan' => false]);
            return;
        }

        $hasCoursesJson = $this->hasColumnSafe('institution_profiles', 'courses_json');
        $hasPrograms    = $this->hasColumnSafe('institution_profiles', 'programs');

        if (!$hasCoursesJson && !$hasPrograms) {
            $this->pageResult(['status' => 200, 'scholarships' => [], 'has_agent_plan' => false]);
            return;
        }

        $select = ['ip.id', 'ip.member_id', 'ip.institute_name', 'ip.summary', 'm.avatar', 'm.alias_name'];
        if ($hasCoursesJson) $select[] = 'ip.courses_json';
        if ($hasPrograms)    $select[] = 'ip.programs';

        $rows = DB::table('institution_profiles as ip')
            ->join('member as m', 'm.id', '=', 'ip.member_id')
            ->where('ip.status', 1)
            ->whereNull('ip.deleted_at')
            ->select($select)
            ->orderBy('ip.id', 'asc')
            ->get();

        $scholarships = [];
        foreach ($rows as $row) {
            $arr     = (array)$row;
            $courses = $this->extractCourses($arr, $hasCoursesJson);
            $instName = $arr['institute_name'] ?? $arr['alias_name'] ?? 'Institution';

            // Logo URL resolution
            $logo = null;
            if (!empty($arr['avatar'])) {
                if (file_exists(public_path('upload/member_logo/'.$arr['avatar']))) {
                    $logo = 'upload/member_logo/' . $arr['avatar'];
                } elseif (file_exists(public_path('upload/member_avatar/'.$arr['avatar']))) {
                    $logo = 'upload/member_avatar/' . $arr['avatar'];
                }
            }

            foreach ($courses as $course) {
                $schText = trim((string)($course['scholarships'] ?? ''));
                if ($schText === '') continue;

                $scholarships[] = [
                    'institution_id'   => (int)$arr['id'],
                    'member_id'        => (int)$arr['member_id'],
                    'institute_name'   => $instName,
                    'logo'             => $logo,
                    'course_name'      => $course['name'] ?? $course['code'] ?? 'Course',
                    'course_code'      => $course['code'] ?? '',
                    'delivery'         => $course['delivery'] ?? '',
                    'duration'         => $course['duration'] ?? '',
                    'fee_tuition'      => $course['fee_tuition'] ?? '',
                    'scholarship_text' => $schText,
                    'profile_url'      => rtrim(env('APP_URL', ''), '/') . '/en/institution_hub_profile/pub_view/' . (int)$arr['id'],
                ];
            }
        }

        // Determine user plan for fallback redirect
        $memberId    = (int)($this->_current_member['id'] ?? 0);
        $planCode    = $this->getMemberPlanCode($memberId);
        $hasAgentPlan = in_array($planCode, ['hybrid', 'vip', 'diy'], true);

        $this->pageResult([
            'status'        => 200,
            'scholarships'  => $scholarships,
            'has_agent_plan'=> $hasAgentPlan,
            'plan_code'     => $planCode,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  AJAX: Generate personalised Study Action Plan (xAI chat)
    // ─────────────────────────────────────────────────────────────

    public function generate_action_plan()
    {
        if (empty($this->_current_member)) {
            $this->pageResult(['status' => 401, 'message' => 'Please log in to generate your action plan.']);
            return;
        }
        if ($this->isInstitutionAccount()) {
            $this->pageResult(['status' => 403, 'message' => 'Not available for institution accounts.']);
            return;
        }

        $apiKey = env('XAI_API_KEY');
        if (!$apiKey) {
            $this->pageResult(['status' => 503, 'message' => 'Action plan generation is not configured.']);
            return;
        }

        $memberId = (int)$this->_current_member['id'];
        $dream    = $this->getDreamByMember($memberId);
        if (!$dream) {
            $this->pageResult(['status' => 404, 'message' => 'Please create your dream profile first.']);
            return;
        }

        $title       = strip_tags($dream['title'] ?? '');
        $description = strip_tags($dream['description'] ?? '');
        $name        = strip_tags($this->_current_member['alias_name'] ?? 'Student');

        $systemPrompt = 'You are a friendly study abroad counsellor at AI-mmi. Generate a concise, actionable study action plan. Format your response as numbered steps (e.g. 1. **Title**: explanation). Include 7-8 steps covering: self-assessment, target country/institution selection, academic preparation, English proficiency test, application process, scholarship search, visa, and arrival preparation. Keep each step to 2 sentences. Total response under 500 words. Be encouraging and specific.';

        $userPrompt = "Student: $name\nDream: $title"
            . ($description ? "\nDetails: $description" : '')
            . "\n\nGenerate a personalized study action plan for this student.";

        $ch = curl_init('https://api.x.ai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model'      => env('XAI_MODEL', 'grok-3-fast'),
                'max_tokens' => 700,
                'messages'   => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userPrompt],
                ],
            ]),
        ]);
        $body    = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr || !$body) {
            \Log::error('generate_action_plan curl error', ['err' => $curlErr]);
            $this->pageResult(['status' => 500, 'message' => 'Could not generate action plan. Please try again.']);
            return;
        }

        $decoded  = json_decode($body, true);
        $planText = $decoded['choices'][0]['message']['content'] ?? null;
        if (!$planText) {
            \Log::error('generate_action_plan empty response', ['body' => substr($body, 0, 500)]);
            $this->pageResult(['status' => 500, 'message' => 'AI returned no plan. Please try again.']);
            return;
        }

        $this->pageResult([
            'status' => 200,
            'plan'   => $planText,
            'name'   => $name,
            'dream'  => $title,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  AJAX: Generate "I Have a Dream" AI image card (xAI Aurora)
    // ─────────────────────────────────────────────────────────────

    public function generate_dream_image()
    {
        if (empty($this->_current_member)) {
            $this->pageResult(['status' => 401, 'message' => 'Please log in to generate your dream card.']);
            return;
        }
        if ($this->isInstitutionAccount()) {
            $this->pageResult(['status' => 403, 'message' => 'Not available for institution accounts.']);
            return;
        }

        $apiKey = env('XAI_API_KEY');
        if (!$apiKey) {
            $this->pageResult(['status' => 503, 'message' => 'Image generation is not configured.']);
            return;
        }

        $memberId = (int)$this->_current_member['id'];
        $dream    = $this->getDreamByMember($memberId);
        if (!$dream) {
            $this->pageResult(['status' => 404, 'message' => 'Please create your dream profile first.']);
            return;
        }

        $title       = strip_tags($dream['title'] ?? '');
        $description = strip_tags($dream['description'] ?? '');
        $name        = strip_tags($this->_current_member['alias_name'] ?? 'Student');

        // Build a cinematic prompt from the dream content
        $promptContext = $title ?: substr($description, 0, 120);
        $prompt = 'An inspiring, cinematic wide-angle scene visualizing the dream of: "'
                . $promptContext
                . '". Soft golden and blue tones, aspirational mood, university or global city backdrop, '
                . 'professional photography style, no text in the image, highly detailed, 16:9 format.';

        // — Call xAI image generation ———————————————————————————
        $ch = curl_init('https://api.x.ai/v1/images/generations');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model'           => 'grok-imagine-image',
                'prompt'          => $prompt,
                'n'               => 1,
                'response_format' => 'url',
            ]),
        ]);
        $body    = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr || !$body) {
            \Log::error('xAI Aurora curl error', ['err' => $curlErr]);
            $this->pageResult(['status' => 500, 'message' => 'Image generation failed. Please try again.']);
            return;
        }

        $decoded = json_decode($body, true);
        $bgUrl   = $decoded['data'][0]['url'] ?? null;
        if (!$bgUrl) {
            \Log::error('xAI Aurora no image URL', ['body' => substr($body, 0, 500)]);
            $this->pageResult(['status' => 500, 'message' => 'Image generation returned no result. Please try again.']);
            return;
        }

        // — Download the generated background ——————————————————
        $bgData = @file_get_contents($bgUrl);
        if (!$bgData) {
            $this->pageResult(['status' => 500, 'message' => 'Could not retrieve the generated image.']);
            return;
        }

        // — Compose card with GD (1200 × 630 — standard OG share size) ——
        $canvas = imagecreatetruecolor(1200, 630);
        if (!$canvas) {
            $this->pageResult(['status' => 500, 'message' => 'Server image library unavailable.']);
            return;
        }

        // Load background and scale to fill canvas
        $bgImage = @imagecreatefromstring($bgData);
        if ($bgImage) {
            $bgW = imagesx($bgImage);
            $bgH = imagesy($bgImage);
            // Scale to cover (crop-free: stretch to fill)
            imagecopyresampled($canvas, $bgImage, 0, 0, 0, 0, 1200, 630, $bgW, $bgH);
            imagedestroy($bgImage);
        } else {
            // Fallback: deep navy background
            $navy = imagecolorallocate($canvas, 5, 15, 40);
            imagefill($canvas, 0, 0, $navy);
        }

        // Dark overlay (full image — 55% darkening)
        $overlayTmp = imagecreatetruecolor(1200, 630);
        imagefill($overlayTmp, 0, 0, imagecolorallocate($overlayTmp, 0, 5, 20));
        imagecopymerge($canvas, $overlayTmp, 0, 0, 0, 0, 1200, 630, 55);

        // Heavier gradient at bottom 280px so text reads clearly
        for ($i = 0; $i < 280; $i++) {
            $pct = min(85, (int)($i * 85 / 280));
            imagecopymerge($canvas, $overlayTmp, 0, 350 + $i, 0, 0, 1200, 1, $pct);
        }
        imagedestroy($overlayTmp);

        // — Colours ——————————————————————————————————————————————
        $white     = imagecolorallocate($canvas, 255, 255, 255);
        $gold      = imagecolorallocate($canvas, 245, 158,  11);  // amber
        $lightGrey = imagecolorallocate($canvas, 200, 215, 235);

        // — Fonts ——————————————————————————————————————————————
        $assetPath  = public_path('asset/fonts/');
        $fontBold   = $assetPath . 'ArialBold.ttf';
        $fontNormal = $assetPath . 'Arial.ttf';
        // Fallback: a font that's guaranteed in the project
        $fallback   = public_path('asset/lib/icon/fonts/fontawesome-webfont.ttf');
        if (!file_exists($fontBold))   $fontBold   = $fallback;
        if (!file_exists($fontNormal)) $fontNormal = $fallback;

        // — "I HAVE A DREAM" label ——————————————————————————————
        imagettftext($canvas, 13, 0, 60, 85, $gold, $fontBold, 'I HAVE A DREAM');

        // — Dream title (bold, wrap at ~40 chars, up to 2 lines) ————
        $titleText  = $title ?: 'My Dream';
        $titleLines = explode("\n", wordwrap($titleText, 40, "\n", true));
        $ty = 140;
        foreach (array_slice($titleLines, 0, 2) as $line) {
            imagettftext($canvas, 36, 0, 60, $ty, $white, $fontBold, $line);
            $ty += 50;
        }

        // — Description snippet (up to 2 lines) ——————————————————
        if (!empty($description)) {
            $descLines = explode("\n", wordwrap(substr($description, 0, 145), 72, "\n", true));
            $dy = $ty + 16;
            foreach (array_slice($descLines, 0, 2) as $dline) {
                imagettftext($canvas, 15, 0, 60, $dy, $lightGrey, $fontNormal, $dline);
                $dy += 24;
            }
        }

        // — Student name ——————————————————————————————————————————
        imagettftext($canvas, 15, 0, 60, 548, $white, $fontBold, $name);

        // — Share URL CTA ———————————————————————————————————————
        $shareUrl = rtrim(env('APP_URL', ''), '/') . '/study_plans/view/' . $memberId;
        imagettftext($canvas, 12, 0, 60, 575, $lightGrey, $fontNormal, $shareUrl);

        // — AI-MMI branding top-right ————————————————————————————
        $brand     = 'AI-MMI.COM';
        $brandBox  = imagettfbbox(13, 0, $fontBold, $brand);
        $brandW    = abs($brandBox[4] - $brandBox[0]);
        imagettftext($canvas, 13, 0, 1200 - $brandW - 50, 55, $gold, $fontBold, $brand);

        // — "Give me a heart for support" bottom-right ——————————
        $cta    = 'Give me a heart for support  >>';
        $ctaBox = imagettfbbox(12, 0, $fontNormal, $cta);
        $ctaW   = abs($ctaBox[4] - $ctaBox[0]);
        imagettftext($canvas, 12, 0, 1200 - $ctaW - 50, 575, $gold, $fontNormal, $cta);

        // — Save to disk ——————————————————————————————————————————
        $saveDir  = public_path('upload/dream_cards');
        if (!file_exists($saveDir)) @mkdir($saveDir, 0755, true);
        $filename = 'member_' . $memberId . '.jpg';
        imagejpeg($canvas, $saveDir . '/' . $filename, 88);
        imagedestroy($canvas);

        $cardUrl = rtrim(env('APP_URL', ''), '/') . '/upload/dream_cards/' . $filename . '?v=' . time();

        $this->pageResult([
            'status'    => 'ok',
            'card_url'  => $cardUrl,
            'share_url' => $shareUrl,
        ]);
    }
}
