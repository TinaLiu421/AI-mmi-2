<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * Admin panel for NextGen AI & Talent Challenge.
 * Access restricted to: info@ai-mmi.com and admin@wealthskey.com
 */
class Nextgen_Challenge extends AdminController
{
    private const ALLOWED_ADMIN_EMAILS = ['info@ai-mmi.com', 'admin@wealthskey.com'];

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

    public function __construct($data)
    {
        parent::__construct($data);
        $this->pageIndex('nextgen_challenge');
    }

    // ─── Access guard ────────────────────────────────────────────
    private function checkAccess(): bool
    {
        $email = strtolower(trim((string)($this->_current_user['email'] ?? '')));
        return in_array($email, self::ALLOWED_ADMIN_EMAILS, true);
    }

    private function denyAccess()
    {
        $this->pageResult(['status' => 403, 'message' => 'Access denied.']);
        exit;
    }

    // ─── Main listing ────────────────────────────────────────────
    public function index($action = '', $id = 0)
    {
        if (!$this->checkAccess()) { $this->denyAccess(); }

        // Route pattern maps /admin/nextgen_challenge/{action}/{id} into index().
        // Handle details explicitly so the "View" button opens the detail screen.
        if ($action === 'details' && (int)$id > 0) {
            return $this->details((int)$id);
        }

        $search   = trim((string)$this->getParamValue('search', ''));
        $stream   = trim((string)$this->getParamValue('stream', ''));
        $statusF  = $this->getParamValue('status_filter', '');
        $page     = max(1, (int)$this->getParamValue('page', 1));
        $perPage  = 20;

        $q = DB::table('app_nextgen_submissions as s')
            ->leftJoin('member as m', 'm.id', '=', 's.member_id')
            ->where('s.status', 1)
            ->whereNull('s.deleted_at');

        if ($search !== '') {
            $q->where(function($qq) use ($search) {
                $qq->where('s.title', 'like', "%{$search}%")
                   ->orWhere('s.full_name', 'like', "%{$search}%")
                   ->orWhere('s.email', 'like', "%{$search}%");
            });
        }
        if ($stream === 'AI' || $stream === 'Talent') {
            $q->where('s.stream', $stream);
        }
        if ($statusF === 'pending')   $q->where('s.admin_status', 0);
        if ($statusF === 'approved')  $q->where('s.admin_status', 1);
        if ($statusF === 'rejected')  $q->where('s.admin_status', 2);
        if ($statusF === 'published') $q->where('s.published', 1);

        $total = $q->count();
        $rows  = $q->select([
                    's.id', 's.member_id', 's.stream', 's.title', 's.full_name', 's.email',
                    's.admin_status', 's.published', 's.youtube_link', 's.video_path',
                    's.created_at', 's.youtube_sent_at',
                    'm.alias_name'
                  ])
                  ->orderBy('s.id', 'desc')
                  ->offset(($page - 1) * $perPage)
                  ->limit($perPage)
                  ->get()
                  ->map(fn($r) => (array)$r)
                  ->toArray();

        $interestCounts = [];
        $interestPreviews = [];
        if (!empty($rows) && DB::getSchemaBuilder()->hasTable('app_nextgen_submission_interests')) {
            $submissionIds = array_values(array_filter(array_map(fn($row) => (int)($row['id'] ?? 0), $rows)));
            if (!empty($submissionIds)) {
                $interestCounts = DB::table('app_nextgen_submission_interests')
                    ->whereIn('submission_id', $submissionIds)
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->selectRaw('submission_id, COUNT(*) as total')
                    ->groupBy('submission_id')
                    ->pluck('total', 'submission_id')
                    ->map(fn($count) => (int)$count)
                    ->toArray();

                $interestPreviewRows = DB::table('app_nextgen_submission_interests')
                    ->whereIn('submission_id', $submissionIds)
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->orderBy('created_at', 'desc')
                    ->get(['submission_id', 'institution_name', 'contact_email', 'created_at'])
                    ->map(fn($row) => (array)$row)
                    ->toArray();

                foreach ($interestPreviewRows as $interestRow) {
                    $submissionId = (int)($interestRow['submission_id'] ?? 0);
                    if ($submissionId > 0 && empty($interestPreviews[$submissionId])) {
                        $interestPreviews[$submissionId] = $interestRow;
                    }
                }
            }
        }

        $totalPages = (int)ceil($total / $perPage);

        return $this->pageData([
            'rows'         => $rows,
            'interest_counts' => $interestCounts,
            'interest_previews' => $interestPreviews,
            'total'        => $total,
            'page'         => $page,
            'total_pages'  => $totalPages,
            'search'       => $search,
            'stream'       => $stream,
            'status_filter'=> $statusF,
        ])->pageView('nextgen_challenge_admin');
    }

    // ─── View single submission ──────────────────────────────────
    public function details($subId = 0)
    {
        if (!$this->checkAccess()) { $this->denyAccess(); }

        $subId = (int)$subId;
        if (!$subId) return abort(404);

        $row = DB::table('app_nextgen_submissions as s')
            ->leftJoin('member as m', 'm.id', '=', 's.member_id')
            ->where('s.id', $subId)
            ->select(['s.*', 'm.alias_name', 'm.avatar'])
            ->first();

        if (!$row) return abort(404);

        $likes    = DB::table('app_nextgen_likes')->where('submission_id', $subId)->where('status', 1)->count();
        $comments = DB::table('app_nextgen_comments as c')
            ->join('member as m2', 'm2.id', '=', 'c.member_id')
            ->where('c.submission_id', $subId)
            ->where('c.status', 1)
            ->select(['c.*', 'm2.alias_name as commenter_name'])
            ->orderBy('c.created_at', 'asc')
            ->get()->map(fn($r) => (array)$r)->toArray();

        $interests = [];
        if (DB::getSchemaBuilder()->hasTable('app_nextgen_submission_interests')) {
            $interests = DB::table('app_nextgen_submission_interests as i')
                ->leftJoin('member as m3', 'm3.id', '=', 'i.member_id')
                ->where('i.submission_id', $subId)
                ->where('i.status', 1)
                ->whereNull('i.deleted_at')
                ->select(['i.*', 'm3.alias_name', 'm3.email as member_email'])
                ->orderBy('i.created_at', 'desc')
                ->get()
                ->map(fn($r) => (array)$r)
                ->toArray();
        }

        return $this->pageData([
            'row'      => (array)$row,
            'likes'    => $likes,
            'comments' => $comments,
            'interests'=> $interests,
        ])->pageView('nextgen_challenge_admin_details');
    }

    // ─── Download video ──────────────────────────────────────────
    public function download_video($subId = 0)
    {
        if (!$this->checkAccess()) { $this->denyAccess(); }

        $subId = (int)$subId;
        $row   = DB::table('app_nextgen_submissions')
            ->where('id', $subId)->where('status', 1)->first();

        if (!$row || empty($row->video_path)) {
            return abort(404);
        }

        $path = $this->resolveSubmissionMediaPath($row->video_path);
        if (!$path || !file_exists($path)) return abort(404);

        return response()->download($path, 'submission_'.$subId.'_'.basename($row->video_path));
    }

    public function media($subId = 0)
    {
        if (!$this->checkAccess()) { $this->denyAccess(); }

        $subId = (int)$subId;
        $row = DB::table('app_nextgen_submissions')
            ->where('id', $subId)
            ->where('status', 1)
            ->first(['id', 'video_path']);

        if (!$row || empty($row->video_path)) {
            return abort(404);
        }

        $path = $this->resolveSubmissionMediaPath($row->video_path);
        if (!$path || !is_file($path)) {
            return abort(404);
        }

        return response()->file($path, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    // ─── AJAX: Update admin status ───────────────────────────────
    public function update_status()
    {
        if (!$this->checkAccess()) { $this->pageResult(['status' => 403, 'message' => 'Access denied.']); return; }

        $subId      = (int)$this->getParamValue('submission_id', 0);
        $status     = (int)$this->getParamValue('admin_status', 0);
        $notes      = trim((string)$this->getParamValue('admin_notes', ''));

        if (!$subId || !in_array($status, [0, 1, 2], true)) {
            $this->pageResult(['status' => 400, 'message' => 'Invalid input.']); return;
        }

        $row = DB::table('app_nextgen_submissions')->where('id', $subId)->first();
        if (!$row) { $this->pageResult(['status' => 404, 'message' => 'Not found.']); return; }

        DB::table('app_nextgen_submissions')->where('id', $subId)->update([
            'admin_status' => $status,
            'admin_notes'  => $notes ?: null,
            'updated_at'   => now(),
        ]);

        // Notify submitter of rejection
        if ($status === 2 && !empty($row->email)) {
            $name = $row->full_name ?? 'Participant';
            $body = "
                <h2 style='color:#1a3a6b;'>Thank you for your submission</h2>
                <p>Dear {$name},</p>
                <p>Thank you for entering the <strong>NextGen AI &amp; Talent Challenge</strong>.</p>
                <p>After careful review, we are unable to proceed with your submission at this time.</p>
                " . (!empty($notes) ? "<p><strong>Feedback:</strong> " . htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') . "</p>" : "") . "
                <p>We encourage you to refine your work and consider resubmitting if future challenges are announced.</p>
                <p>Best regards,<br>The AI-mmi Team</p>
            ";
            $this->sendEmail($row->email, 'NextGen Challenge — Submission Update', $body);
        }

        $this->pageResult(['status' => 'ok', 'message' => 'Status updated.']);
    }

    // ─── AJAX: Publish / unpublish to public feed ────────────────
    public function toggle_publish()
    {
        if (!$this->checkAccess()) { $this->pageResult(['status' => 403, 'message' => 'Access denied.']); return; }

        $subId     = (int)$this->getParamValue('submission_id', 0);
        $published = $this->getParamValue('published', null);

        if (!$subId) { $this->pageResult(['status' => 400, 'message' => 'Invalid input.']); return; }

        $row = DB::table('app_nextgen_submissions')->where('id', $subId)->first();
        if (!$row) { $this->pageResult(['status' => 404, 'message' => 'Not found.']); return; }

        $published = is_null($published) ? ((int)($row->published ?? 0) ? 0 : 1) : ((int)$published ? 1 : 0);

        DB::table('app_nextgen_submissions')->where('id', $subId)->update([
            'published'  => $published,
            'updated_at' => now(),
        ]);

        $this->pageResult(['status' => 'ok', 'published' => (bool)$published]);
    }

    // ─── AJAX: Send YouTube link to submitter ────────────────────
    public function send_youtube_link()
    {
        if (!$this->checkAccess()) { $this->pageResult(['status' => 403, 'message' => 'Access denied.']); return; }

        $subId     = (int)$this->getParamValue('submission_id', 0);
        $ytLink    = trim((string)$this->getParamValue('youtube_link', ''));

        if (!$subId || empty($ytLink)) {
            $this->pageResult(['status' => 400, 'message' => 'Submission ID and YouTube link are required.']); return;
        }

        if (!filter_var($ytLink, FILTER_VALIDATE_URL)) {
            $this->pageResult(['status' => 400, 'message' => 'Invalid YouTube URL.']); return;
        }

        $row = DB::table('app_nextgen_submissions')->where('id', $subId)->first();
        if (!$row) { $this->pageResult(['status' => 404, 'message' => 'Not found.']); return; }

        // Save link & timestamp
        DB::table('app_nextgen_submissions')->where('id', $subId)->update([
            'youtube_link'    => $ytLink,
            'youtube_sent_at' => now(),
            'admin_status'    => 1, // auto-approve when YouTube is sent
            'published'       => 1, // make the entry live on-platform when the YouTube link is sent
            'updated_at'      => now(),
        ]);

        // Email submitter
        if (!empty($row->email)) {
            $name     = htmlspecialchars($row->full_name ?? 'Participant', ENT_QUOTES, 'UTF-8');
            $title    = htmlspecialchars($row->title ?? '', ENT_QUOTES, 'UTF-8');
            $ytEsc    = htmlspecialchars($ytLink, ENT_QUOTES, 'UTF-8');
            $body     = "
                <h2 style='color:#7c3aed;'>🎉 Your Video is Live on YouTube!</h2>
                <p>Dear {$name},</p>
                <p>Congratulations! Your NextGen AI &amp; Talent Challenge submission — <strong>{$title}</strong> — has been approved and is now published on the <strong>AI-mmi YouTube channel</strong>.</p>
                <p style='margin:20px 0;'>
                    <a href='{$ytEsc}' style='background:#FF0000;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;'>▶ Watch on YouTube</a>
                </p>
                <p>Your video is now visible to universities and institutions worldwide through our platform. Judges will review your submission over the coming weeks.</p>
                <p>Please log in to your AI-mmi account to view your updated submission status.</p>
                <p>Best of luck,<br>The AI-mmi Team</p>
                <hr style='margin:20px 0;'>
                <p style='font-size:12px;color:#888;'>© AI-mmi. All submitted content, once approved, is subject to the intellectual property agreement you consented to during submission.</p>
            ";
            $this->sendEmail($row->email, '🎉 Your NextGen Entry is Live on YouTube!', $body);
        }

        $this->pageResult(['status' => 'ok', 'message' => 'YouTube link saved and notification sent.', 'youtube_link' => $ytLink]);
    }
}
