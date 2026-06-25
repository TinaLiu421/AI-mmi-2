<?php
namespace App\Http\Controllers\Web;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;

class Nextgen_Challenge extends Home
{
    private const PRIVILEGED_MEDIA_VIEWER_EMAILS = ['info@ai-mmi.com', 'admin@wealthskey.com'];

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

    private function nextgenPrivateMediaDir(): string
    {
        return storage_path('app/nextgen_submissions');
    }

    private function nextgenLegacyPublicMediaDir(): string
    {
        return public_path('upload/nextgen_submissions');
    }

    private function resolveSubmissionMediaPath(?string $storedFileName): ?string
    {
        $fileName = trim((string)$storedFileName);
        if ($fileName === '') {
            return null;
        }

        $fileName = basename($fileName);
        $privateDir = $this->nextgenPrivateMediaDir();
        $privatePath = $privateDir . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($privatePath)) {
            return $privatePath;
        }

        $legacyPath = $this->nextgenLegacyPublicMediaDir() . DIRECTORY_SEPARATOR . $fileName;
        if (!is_file($legacyPath)) {
            return null;
        }

        if (!is_dir($privateDir)) {
            @mkdir($privateDir, 0755, true);
        }

        if (@rename($legacyPath, $privatePath)) {
            return $privatePath;
        }

        if (@copy($legacyPath, $privatePath)) {
            @unlink($legacyPath);
            return $privatePath;
        }

        return $legacyPath;
    }

    private function isPrivilegedMediaViewer(): bool
    {
        $email = strtolower(trim((string)($this->_current_member['email'] ?? '')));
        return in_array($email, self::PRIVILEGED_MEDIA_VIEWER_EMAILS, true);
    }

    private function canViewSubmissionMedia(object $submission): bool
    {
        $memberId = (int)($this->_current_member['id'] ?? 0);
        if ($memberId > 0 && (int)($submission->member_id ?? 0) === $memberId) {
            return true;
        }

        return $this->isPrivilegedMediaViewer();
    }

    private function getSubmissionByMember(int $memberId): ?array
    {
        if (!$this->hasTable('app_nextgen_submissions')) {
            return null;
        }

        $q = DB::table('app_nextgen_submissions')
            ->where('member_id', $memberId)
            ->orderBy('id', 'desc');

        if ($this->hasColumnSafe('app_nextgen_submissions', 'status')) {
            $q->where('status', 1);
        }
        if ($this->hasColumnSafe('app_nextgen_submissions', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        $row = $q->first();
        return $row ? (array)$row : null;
    }

    private function submissionSocialCounts(int $submissionId): array
    {
        $likes = 0;
        $comments = 0;

        if ($this->hasTable('app_nextgen_likes')) {
            $qLikes = DB::table('app_nextgen_likes')->where('submission_id', $submissionId);
            if ($this->hasColumnSafe('app_nextgen_likes', 'status')) {
                $qLikes->where('status', 1);
            }
            $likes = $qLikes->count();
        }

        if ($this->hasTable('app_nextgen_comments')) {
            $qComments = DB::table('app_nextgen_comments')->where('submission_id', $submissionId);
            if ($this->hasColumnSafe('app_nextgen_comments', 'status')) {
                $qComments->where('status', 1);
            }
            if ($this->hasColumnSafe('app_nextgen_comments', 'deleted_at')) {
                $qComments->whereNull('deleted_at');
            }
            $comments = $qComments->count();
        }

        return ['likes' => $likes, 'comments' => $comments];
    }

    private function userLikedSubmission(int $submissionId): bool
    {
        $memberId = (int)($this->_current_member['id'] ?? 0);
        if (!$memberId || !$this->hasTable('app_nextgen_likes')) return false;

        $q = DB::table('app_nextgen_likes')
            ->where('submission_id', $submissionId)
            ->where('member_id', $memberId);
        if ($this->hasColumnSafe('app_nextgen_likes', 'status')) {
            $q->where('status', 1);
        }

        return $q->exists();
    }

    private function loadSubmissionComments(int $submissionId): array
    {
        if (!$this->hasTable('app_nextgen_comments') || !$this->hasTable('member')) {
            return [];
        }

        return DB::table('app_nextgen_comments as c')
            ->join('member as m', 'm.id', '=', 'c.member_id')
            ->where('c.submission_id', $submissionId)
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

    private function getViewerEducationInstitutionMeta(): array
    {
        $memberId = (int)($this->_current_member['id'] ?? 0);
        if (!$memberId) {
            return ['is_education_institution' => false, 'institution_name' => '', 'interested_submission_ids' => []];
        }

        if (!$this->hasTable('member_details')) {
            return ['is_education_institution' => false, 'institution_name' => '', 'interested_submission_ids' => []];
        }

        $details = DB::table('member_details')->where('member_id', $memberId)->first();
        $isEducationInstitution = ((int)($this->_current_member['type'] ?? 0) === 3)
            && ((int)($details->institution_type ?? 0) === 2);

        if (!$isEducationInstitution) {
            return ['is_education_institution' => false, 'institution_name' => '', 'interested_submission_ids' => []];
        }

        $profile = $this->hasTable('institution_profiles')
            ? DB::table('institution_profiles')->where('member_id', $memberId)->first()
            : null;
        $institutionName = trim((string)($profile->institute_name ?? $this->_current_member['alias_name'] ?? $this->_current_member['full_name'] ?? 'Education Institution'));

        $interestedSubmissionIds = [];
        if ($this->hasTable('app_nextgen_submission_interests')) {
            $interestedSubmissionIds = DB::table('app_nextgen_submission_interests')
                ->where('member_id', $memberId)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->pluck('submission_id')
                ->map(fn($id) => (int)$id)
                ->toArray();
        }

        return [
            'is_education_institution'  => true,
            'institution_name'          => $institutionName,
            'interested_submission_ids' => $interestedSubmissionIds,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  Main page
    // ─────────────────────────────────────────────────────────────

    public function index()
    {
        $this->pageMeta([
            'title'       => 'NextGen AI & Talent Challenge',
            'description' => 'Create. Perform. Get Discovered. Win a Global Scholarship.',
        ]);

        $submission    = null;
        $likes         = 0;
        $commentsCount = 0;
        $comments      = [];
        $userLiked     = false;

        $memberId = (int)($this->_current_member['id'] ?? 0);
        $viewerEducationMeta = $this->getViewerEducationInstitutionMeta();
        if ($memberId) {
            $submission = $this->getSubmissionByMember($memberId);
            if ($submission) {
                $counts        = $this->submissionSocialCounts((int)$submission['id']);
                $likes         = $counts['likes'];
                $commentsCount = $counts['comments'];
                $userLiked     = $this->userLikedSubmission((int)$submission['id']);
                $comments      = $this->loadSubmissionComments((int)$submission['id']);
            }
        }

        $publicFeed = [];
        if ($this->hasTable('app_nextgen_submissions') && $this->hasTable('member')) {
            // Public feed: use published filter when column exists; otherwise fallback to status-only.
            $feedQuery = DB::table('app_nextgen_submissions as s')
                ->join('member as m', 'm.id', '=', 's.member_id');

            if ($this->hasColumnSafe('app_nextgen_submissions', 'status')) {
                $feedQuery->where('s.status', 1);
            }
            if ($this->hasColumnSafe('app_nextgen_submissions', 'published')) {
                $feedQuery->where('s.published', 1);
            }
            if ($this->hasColumnSafe('app_nextgen_submissions', 'deleted_at')) {
                $feedQuery->whereNull('s.deleted_at');
            }
            if ($memberId) {
                $feedQuery->where('s.member_id', '<>', $memberId);
            }

            $select = ['s.id', 's.member_id', 's.title', 's.description', 'm.alias_name', 'm.avatar'];
            if ($this->hasColumnSafe('app_nextgen_submissions', 'stream')) {
                $select[] = 's.stream';
            }
            if ($this->hasColumnSafe('app_nextgen_submissions', 'youtube_link')) {
                $select[] = 's.youtube_link';
            }

            $publicFeed = $feedQuery
                ->select($select)
                ->orderBy('s.id', 'desc')
                ->limit(12)
                ->get()
                ->map(fn($r) => (array)$r)
                ->toArray();
        }

        return $this->pageData([
            'submission'     => $submission,
            'likes'          => $likes,
            'comments_count' => $commentsCount,
            'comments'       => $comments,
            'user_liked'     => $userLiked,
            'public_feed'    => $publicFeed,
            'viewer_is_education_institution' => $viewerEducationMeta['is_education_institution'],
            'viewer_institution_name'         => $viewerEducationMeta['institution_name'],
            'interested_submission_ids'       => $viewerEducationMeta['interested_submission_ids'],
        ])->pageView('nextgen_challenge');
    }

    // ─────────────────────────────────────────────────────────────
    //  AJAX: Submission CRUD
    // ─────────────────────────────────────────────────────────────

    public function save_submission()
    {
        if (empty($this->_current_member)) {
            $this->pageResult(['status' => 401, 'message' => 'Please log in to submit.']);
            return;
        }

        $memberId = (int)$this->_current_member['id'];
        $stream   = trim((string)$this->getParamValue('stream', ''));
        $title    = trim((string)$this->getParamValue('title', ''));
        $desc     = trim((string)$this->getParamValue('description', ''));
        $tags     = trim((string)$this->getParamValue('tags', ''));
        $ytConsent  = ((string)$this->getParamValue('youtube_consent', '0') === '1') ? 1 : 0;
        $cpConsent  = ((string)$this->getParamValue('copyright_consent', '0') === '1') ? 1 : 0;
        $fullName = trim((string)$this->getParamValue('full_name', ''));
        $country  = trim((string)$this->getParamValue('country', ''));
        $age      = (int)$this->getParamValue('age', 0);
        $email    = trim((string)$this->getParamValue('email', $this->_current_member['email'] ?? ''));
        $phone    = trim((string)$this->getParamValue('phone', ''));
        $ytLink   = trim((string)$this->getParamValue('youtube_link', ''));

        if (!in_array($stream, ['AI', 'Talent'])) {
            $this->pageResult(['status' => 400, 'message' => 'Please select a stream (AI or Talent).']);
            return;
        }
        if ($title === '') {
            $this->pageResult(['status' => 400, 'message' => 'Title is required.']);
            return;
        }

        $existing = DB::table('app_nextgen_submissions')
            ->where('member_id', $memberId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->orderBy('id', 'desc')
            ->first();

        $now = now();

        // Handle Google Drive link submission
        $videoPath = $existing ? ($existing->video_path ?? null) : null;
        $gdLink = trim((string)$this->getParamValue('google_drive_link', ''));
        if ($gdLink !== '') {
            // Accept Google Drive, Dropbox, OneDrive, or any https URL
            if (!preg_match('/^https?:\/\//i', $gdLink)) {
                $this->pageResult(['status' => 400, 'message' => 'Please enter a valid video link (must start with https://).']);
                return;
            }
            $videoPath = $gdLink;
        } elseif (!$existing) {
            // New submission with no link provided
            $this->pageResult(['status' => 400, 'message' => 'Please provide a Google Drive link to your video.']);
            return;
        }

        $data = [
            'stream'            => $stream,
            'title'             => $title,
            'description'       => $desc ?: null,
            'tags'              => $tags ?: null,
            'youtube_consent'   => $ytConsent,
            'copyright_consent' => $cpConsent,
            'full_name'         => $fullName ?: null,
            'country'           => $country ?: null,
            'age'               => $age ?: null,
            'video_path'        => $videoPath,
            'email'             => $email ?: null,
            'phone'             => $phone ?: null,
            'youtube_link'      => $ytLink ?: null,
            'updated_by'        => $memberId,
            'updated_at'        => $now,
        ];

        if ($existing) {
            DB::table('app_nextgen_submissions')
                ->where('id', $existing->id)
                ->update($data);
            $submissionId = (int)$existing->id;
        } else {
            $data['member_id']    = $memberId;
            $data['admin_status'] = 0;
            $data['status']       = 1;
            $data['created_by']   = $memberId;
            $data['created_at']   = $now;
            $submissionId = DB::table('app_nextgen_submissions')->insertGetId($data);
        }

        $isNew = !$existing;

        // ── Email: notify admin of new submission ──────────────────────────
        if ($isNew) {
            $adminUrl  = url('/admin/nextgen_challenge/details/'.$submissionId);
            $safeName  = htmlspecialchars($fullName ?: ($this->_current_member['alias_name'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8');
            $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
            $adminBody = "
                <h2 style='color:#1a3a6b;'>🏆 New NextGen Challenge Submission</h2>
                <table style='border-collapse:collapse;width:100%;font-size:14px;'>
                    <tr><td style='padding:6px 12px;font-weight:bold;width:140px;'>Submission ID</td><td style='padding:6px 12px;'>#{$submissionId}</td></tr>
                    <tr style='background:#f3f4f6;'><td style='padding:6px 12px;font-weight:bold;'>Name</td><td style='padding:6px 12px;'>{$safeName}</td></tr>
                    <tr><td style='padding:6px 12px;font-weight:bold;'>Email</td><td style='padding:6px 12px;'>{$safeEmail}</td></tr>
                    <tr style='background:#f3f4f6;'><td style='padding:6px 12px;font-weight:bold;'>Stream</td><td style='padding:6px 12px;'>{$stream}</td></tr>
                    <tr><td style='padding:6px 12px;font-weight:bold;'>Title</td><td style='padding:6px 12px;'>{$safeTitle}</td></tr>
                    <tr style='background:#f3f4f6;'><td style='padding:6px 12px;font-weight:bold;'>Has Video Link</td><td style='padding:6px 12px;'>" . ($videoPath ? '✅ Yes' : '— No') . "</td></tr>
                </table>
                <p style='margin-top:16px;'>
                    <a href='{$adminUrl}' style='background:#1a3a6b;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:bold;'>View &amp; Review Submission</a>
                </p>
            ";
            try { $this->sendEmail(['info@ai-mmi.com', 'admin@wealthskey.com'], 'New NextGen Challenge Submission — #'.$submissionId, $adminBody); } catch (\Exception $e) {}
        }

        // ── Email: confirm to submitter ────────────────────────────────────
        if ($isNew && !empty($email)) {
            $safeName  = htmlspecialchars($fullName ?: ($this->_current_member['alias_name'] ?? 'Participant'), ENT_QUOTES, 'UTF-8');
            $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            $userBody  = "
                <h2 style='color:#7c3aed;'>🎉 Your NextGen Entry Has Been Received!</h2>
                <p>Dear {$safeName},</p>
                <p>Thank you for submitting to the <strong>NextGen AI &amp; Talent Challenge</strong>!</p>
                <p>Your submission — <strong>{$safeTitle}</strong> — is now <em>pending review</em> by our team.</p>
                <h3 style='color:#1a3a6b;'>What happens next?</h3>
                <ol>
                    <li><strong>Review:</strong> Our team will review your submission within a few business days.</li>
                    <li><strong>YouTube Upload:</strong> If approved, your video will be uploaded to the official AI-mmi YouTube channel.</li>
                    <li><strong>You'll be notified:</strong> We'll email you the YouTube link once your video is live.</li>
                    <li><strong>Judge Review:</strong> Universities and judges worldwide will review all published submissions.</li>
                </ol>
                <p><strong>Reminder:</strong> By submitting, you have agreed that AI-mmi owns the intellectual property rights to your submission as per the terms you consented to.</p>
                <p>Good luck! 🌟</p>
                <p>Best regards,<br>The AI-mmi Team<br><a href='https://www.ai-mmi.com'>www.ai-mmi.com</a></p>
            ";
            try { $this->sendEmail($email, 'NextGen Challenge — Submission Received ✅', $userBody); } catch (\Exception $e) {}
        }

        $this->pageResult([
            'status'        => 'ok',
            'message'       => 'Submission saved. Awaiting review.',
            'submission_id' => $submissionId,
            'video_path'    => $videoPath ?: null,
            'media_url'     => $videoPath ? $this->toURL(['nextgen_challenge', 'media', $submissionId]) : null,
        ]);
    }

    public function media($subId = 0)
    {
        $subId = (int)$subId;
        if (!$subId) {
            return abort(404);
        }

        $submission = DB::table('app_nextgen_submissions')
            ->where('id', $subId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->first(['id', 'member_id', 'video_path']);

        if (!$submission || empty($submission->video_path)) {
            return abort(404);
        }

        if (!$this->canViewSubmissionMedia($submission)) {
            return abort(403);
        }

        $path = $this->resolveSubmissionMediaPath($submission->video_path);
        if (!$path || !is_file($path)) {
            return abort(404);
        }

        return response()->file($path, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function update_youtube_link()
    {
        if (empty($this->_current_member)) {
            $this->pageResult(['status' => 401, 'message' => 'Please log in.']);
            return;
        }

        $memberId = (int)$this->_current_member['id'];
        $ytLink   = trim((string)$this->getParamValue('youtube_link', ''));
        $subId    = (int)$this->getParamValue('submission_id', 0);

        if (!$subId || $ytLink === '') {
            $this->pageResult(['status' => 400, 'message' => 'Invalid input.']);
            return;
        }

        $existing = DB::table('app_nextgen_submissions')
            ->where('id', $subId)
            ->where('member_id', $memberId)
            ->where('status', 1)
            ->first();
        if (!$existing) {
            $this->pageResult(['status' => 404, 'message' => 'Submission not found.']);
            return;
        }

        DB::table('app_nextgen_submissions')->where('id', $subId)->update([
            'youtube_link' => $ytLink,
            'updated_by'   => $memberId,
            'updated_at'   => now(),
        ]);

        $this->pageResult(['status' => 'ok', 'message' => 'YouTube link updated.']);
    }

    // ─────────────────────────────────────────────────────────────
    //  AJAX: Likes
    // ─────────────────────────────────────────────────────────────

    public function toggle_like()
    {
        if (empty($this->_current_member)) {
            $this->pageResult(['status' => 401, 'message' => 'Please log in to like.']);
            return;
        }

        $submissionId = (int)$this->getParamValue('submission_id', 0);
        $memberId     = (int)$this->_current_member['id'];

        if (!$submissionId) {
            $this->pageResult(['status' => 400, 'message' => 'Invalid submission.']);
            return;
        }

        $existing = DB::table('app_nextgen_likes')
            ->where('submission_id', $submissionId)
            ->where('member_id', $memberId)
            ->first();

        if ($existing) {
            $newStatus = ((int)$existing->status === 1) ? 0 : 1;
            DB::table('app_nextgen_likes')->where('id', $existing->id)->update([
                'status'     => $newStatus,
                'updated_by' => $memberId,
                'updated_at' => now(),
            ]);
            $liked = $newStatus === 1;
        } else {
            DB::table('app_nextgen_likes')->insert([
                'member_id'     => $memberId,
                'submission_id' => $submissionId,
                'status'        => 1,
                'created_by'    => $memberId,
                'created_at'    => now(),
                'updated_by'    => $memberId,
                'updated_at'    => now(),
            ]);
            $liked = true;
        }

        $count = DB::table('app_nextgen_likes')
            ->where('submission_id', $submissionId)
            ->where('status', 1)
            ->count();

        $this->pageResult(['status' => 'ok', 'liked' => $liked, 'count' => $count]);
    }

    // ─────────────────────────────────────────────────────────────
    //  AJAX: Comments
    // ─────────────────────────────────────────────────────────────

    public function add_comment()
    {
        if (empty($this->_current_member)) {
            $this->pageResult(['status' => 401, 'message' => 'Please log in to comment.']);
            return;
        }

        $submissionId = (int)$this->getParamValue('submission_id', 0);
        $content      = trim((string)$this->getParamValue('content', ''));
        $memberId     = (int)$this->_current_member['id'];

        if (!$submissionId || $content === '') {
            $this->pageResult(['status' => 400, 'message' => 'Invalid input.']);
            return;
        }

        $exists = DB::table('app_nextgen_submissions')
            ->where('id', $submissionId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->exists();
        if (!$exists) {
            $this->pageResult(['status' => 404, 'message' => 'Submission not found.']);
            return;
        }

        $now = now();
        $id  = DB::table('app_nextgen_comments')->insertGetId([
            'member_id'     => $memberId,
            'submission_id' => $submissionId,
            'parent_id'     => 0,
            'content'       => $content,
            'status'        => 1,
            'created_by'    => $memberId,
            'created_at'    => $now,
            'updated_by'    => $memberId,
            'updated_at'    => $now,
        ]);

        $member = DB::table('member')->where('id', $memberId)->first();
        $count  = DB::table('app_nextgen_comments')
            ->where('submission_id', $submissionId)
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

    public function express_interest()
    {
        if (empty($this->_current_member)) {
            $this->pageResult(['status' => 401, 'message' => 'Please log in first.']);
            return;
        }

        if (!DB::getSchemaBuilder()->hasTable('app_nextgen_submission_interests')) {
            $this->pageResult(['status' => 500, 'message' => 'Interest tracking is not ready yet.']);
            return;
        }

        $memberId = (int)$this->_current_member['id'];
        $details = DB::table('member_details')->where('member_id', $memberId)->first();
        $isEducationInstitution = ((int)($this->_current_member['type'] ?? 0) === 3)
            && ((int)($details->institution_type ?? 0) === 2);

        if (!$isEducationInstitution) {
            $this->pageResult(['status' => 403, 'message' => 'Only education institutions can express interest.']);
            return;
        }

        $submissionId = (int)$this->getParamValue('submission_id', 0);
        if (!$submissionId) {
            $this->pageResult(['status' => 400, 'message' => 'Invalid submission.']);
            return;
        }

        $submission = DB::table('app_nextgen_submissions as s')
            ->join('member as m', 'm.id', '=', 's.member_id')
            ->where('s.id', $submissionId)
            ->where('s.status', 1)
            ->where('s.published', 1)
            ->whereNull('s.deleted_at')
            ->select(['s.id', 's.member_id', 's.title', 's.stream', 's.email', 's.full_name', 's.youtube_link', 'm.alias_name'])
            ->first();

        if (!$submission) {
            $this->pageResult(['status' => 404, 'message' => 'Submission not found.']);
            return;
        }

        if ((int)$submission->member_id === $memberId) {
            $this->pageResult(['status' => 400, 'message' => 'You cannot express interest in your own submission.']);
            return;
        }

        $existing = DB::table('app_nextgen_submission_interests')
            ->where('submission_id', $submissionId)
            ->where('member_id', $memberId)
            ->whereNull('deleted_at')
            ->first();

        if ($existing && (int)($existing->status ?? 0) === 1) {
            $this->pageResult(['status' => 400, 'message' => 'Interest already sent for this participant.']);
            return;
        }

        $profile = DB::table('institution_profiles')->where('member_id', $memberId)->first();
        $institutionName = trim((string)($profile->institute_name ?? $this->_current_member['alias_name'] ?? $this->_current_member['full_name'] ?? 'Education Institution'));
        $contactEmail = trim((string)($this->_current_member['email'] ?? ''));
        $now = now();

        $payload = [
            'institution_name' => $institutionName,
            'contact_email'    => $contactEmail ?: null,
            'message'          => 'Institution expressed interest from NextGen public feed.',
            'status'           => 1,
            'updated_by'       => $memberId,
            'updated_at'       => $now,
            'deleted_at'       => null,
            'deleted_by'       => 0,
        ];

        if ($existing) {
            DB::table('app_nextgen_submission_interests')->where('id', $existing->id)->update($payload);
        } else {
            DB::table('app_nextgen_submission_interests')->insert(array_merge($payload, [
                'submission_id' => $submissionId,
                'member_id'     => $memberId,
                'created_by'    => $memberId,
                'created_at'    => $now,
            ]));
        }

        $safeInstitutionName = htmlspecialchars($institutionName, ENT_QUOTES, 'UTF-8');
        $safeContactEmail = htmlspecialchars($contactEmail ?: '—', ENT_QUOTES, 'UTF-8');
        $safeParticipantName = htmlspecialchars((string)($submission->full_name ?: $submission->alias_name ?: 'Participant'), ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars((string)($submission->title ?? ''), ENT_QUOTES, 'UTF-8');
        $adminDetailUrl = url('/admin/nextgen_challenge/details/' . $submissionId);
        $youtubeLine = !empty($submission->youtube_link)
            ? "<p style='margin-top:12px;'><strong>YouTube:</strong> <a href='" . htmlspecialchars((string)$submission->youtube_link, ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars((string)$submission->youtube_link, ENT_QUOTES, 'UTF-8') . "</a></p>"
            : '';

        $adminBody = "
            <h2 style='color:#1a3a6b;'>New Education Institution Interest</h2>
            <p>An education institution has expressed interest in a published NextGen participant.</p>
            <table style='border-collapse:collapse;width:100%;font-size:14px;'>
                <tr><td style='padding:6px 12px;font-weight:bold;width:180px;'>Institution</td><td style='padding:6px 12px;'>{$safeInstitutionName}</td></tr>
                <tr style='background:#f3f4f6;'><td style='padding:6px 12px;font-weight:bold;'>Institution Email</td><td style='padding:6px 12px;'>{$safeContactEmail}</td></tr>
                <tr><td style='padding:6px 12px;font-weight:bold;'>Participant</td><td style='padding:6px 12px;'>{$safeParticipantName}</td></tr>
                <tr style='background:#f3f4f6;'><td style='padding:6px 12px;font-weight:bold;'>Submission Title</td><td style='padding:6px 12px;'>{$safeTitle}</td></tr>
                <tr><td style='padding:6px 12px;font-weight:bold;'>Stream</td><td style='padding:6px 12px;'>" . htmlspecialchars((string)($submission->stream ?? ''), ENT_QUOTES, 'UTF-8') . "</td></tr>
                <tr style='background:#f3f4f6;'><td style='padding:6px 12px;font-weight:bold;'>Submission ID</td><td style='padding:6px 12px;'>#{$submissionId}</td></tr>
            </table>
            <p style='margin-top:16px;'>
                <a href='{$adminDetailUrl}' style='background:#1a3a6b;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;font-weight:bold;'>Open Admin Details</a>
            </p>
            {$youtubeLine}
        ";

        try { $this->sendEmail('info@ai-mmi.com', 'NextGen Interest — ' . $institutionName . ' interested in #' . $submissionId, $adminBody); } catch (\Exception $e) {}

        $this->pageResult(['status' => 'ok', 'message' => 'Interest sent to AI-mmi. Our team will follow up.']);
    }
}
