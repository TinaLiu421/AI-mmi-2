@extends('admin.common')
@section('content')
<?php
$rows         = $_page_data['rows']          ?? [];
$total        = (int)($_page_data['total']   ?? 0);
$page         = (int)($_page_data['page']    ?? 1);
$totalPages   = (int)($_page_data['total_pages'] ?? 1);
$interestCounts = $_page_data['interest_counts'] ?? [];
$interestPreviews = $_page_data['interest_previews'] ?? [];
$search       = $_page_data['search']        ?? '';
$streamF      = $_page_data['stream']        ?? '';
$statusF      = $_page_data['status_filter'] ?? '';

$baseUrl = url('admin/nextgen_challenge/index');

// Pagination helper
$pageUrl = function($pg) use ($search, $streamF, $statusF, $baseUrl) {
    $params = http_build_query(array_filter([
        'search'        => $search,
        'stream'        => $streamF,
        'status_filter' => $statusF,
        'page'          => $pg,
    ]));
    return $baseUrl . ($params ? '?' . $params : '');
};

$statusLabel = function($as, $pub) {
    if ($pub)     return '<span class="label-success">Live</span>';
    if ($as == 1) return '<span class="label-info">Approved</span>';
    if ($as == 2) return '<span class="label-danger">Rejected</span>';
    return '<span class="label-warning">Pending</span>';
};
?>

<style>
.ng-admin-wrap { padding: 24px; }
.ng-admin-top  { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.ng-admin-top h2 { margin: 0; font-size: 22px; font-weight: 700; }
.ng-filter-bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 20px; background: #f8f9fa; padding: 14px 16px; border-radius: 8px; border: 1px solid #dee2e6; }
.ng-filter-bar input, .ng-filter-bar select { padding: 7px 12px; border: 1px solid #ced4da; border-radius: 6px; font-size: 13px; }
.ng-filter-bar button { padding: 7px 18px; background: #4361ee; color: #fff; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; }
.ng-filter-bar button:hover { background: #3651d4; }
.ng-filter-bar a.reset { font-size: 13px; color: #666; text-decoration: underline; }

.ng-stats { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
.ng-stat-card { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 16px 20px; min-width: 140px; }
.ng-stat-num  { font-size: 28px; font-weight: 800; color: #4361ee; }
.ng-stat-lbl  { font-size: 12px; color: #666; margin-top: 2px; }

table.ng-table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; font-size: 13px; }
.ng-table th { background: #f1f3f9; font-weight: 700; padding: 12px 14px; text-align: left; color: #333; border-bottom: 2px solid #dee2e6; white-space: nowrap; }
.ng-table td { padding: 12px 14px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; color: #444; }
.ng-table tbody tr:hover { background: #fafbff; }
.ng-table tbody tr:last-child td { border-bottom: none; }

label.label-warning { background: #ff9800; color: #fff; padding: 3px 10px; border-radius: 100px; font-size: 11px; white-space: nowrap; }
label.label-success, span.label-success { background: #4caf50; color: #fff; padding: 3px 10px; border-radius: 100px; font-size: 11px; white-space: nowrap; }
span.label-warning { background: #ff9800; color: #fff; padding: 3px 10px; border-radius: 100px; font-size: 11px; white-space: nowrap; }
span.label-info    { background: #2196f3; color: #fff; padding: 3px 10px; border-radius: 100px; font-size: 11px; white-space: nowrap; }
span.label-danger  { background: #f44336; color: #fff; padding: 3px 10px; border-radius: 100px; font-size: 11px; white-space: nowrap; }
.ng-interest-pill { display:inline-flex; align-items:center; gap:6px; background:#e8f7ee; color:#198754; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700; white-space:nowrap; }
.ng-interest-pill--none { background:#f5f5f5; color:#999; }
.ng-interest-meta { margin-top:6px; font-size:11px; line-height:1.35; color:#666; }
.ng-interest-meta strong { color:#198754; }
.ng-interest-meta a { color:#4361ee; text-decoration:none; }
.ng-interest-meta a:hover { text-decoration:underline; }

.ng-actions { display: flex; gap: 6px; flex-wrap: nowrap; }
.btn-ng-details { background: #4361ee; color: #fff; padding: 5px 14px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; white-space: nowrap; }
.btn-ng-details:hover { background: #3651d4; color: #fff; text-decoration: none; }
.btn-ng-download { background: #198754; color: #fff; padding: 5px 14px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; white-space: nowrap; }
.btn-ng-download:hover { background: #146c43; color: #fff; text-decoration: none; }

.ng-pagination { display: flex; align-items: center; gap: 6px; margin-top: 20px; flex-wrap: wrap; }
.ng-pagination a, .ng-pagination span {
    padding: 6px 12px; border-radius: 6px; font-size: 13px; border: 1px solid #dee2e6;
    background: #fff; color: #333; text-decoration: none;
}
.ng-pagination a:hover { background: #f1f3f9; text-decoration: none; }
.ng-pagination span.current { background: #4361ee; color: #fff; border-color: #4361ee; }
.ng-pagination span.disabled { color: #aaa; }

.ng-no-rows { text-align: center; padding: 60px 20px; color: #666; }
.ng-no-rows .icon { font-size: 48px; margin-bottom: 12px; }
</style>

<div class="ng-admin-wrap">
    <div class="ng-admin-top">
        <h2>🏆 NextGen Challenge Submissions</h2>
        <span style="color:#666;font-size:13px;">Access: info@ai-mmi.com &amp; admin@wealthskey.com only</span>
    </div>

    <!-- Summary stats -->
    <?php
    $counts = \Illuminate\Support\Facades\DB::table('app_nextgen_submissions')
        ->where('status', 1)->whereNull('deleted_at')
        ->selectRaw('COUNT(*) as total, SUM(admin_status=0) as pending, SUM(admin_status=1) as approved, SUM(admin_status=2) as rejected, SUM(published=1) as published')
        ->first();
    ?>
    <div class="ng-stats">
        <div class="ng-stat-card"><div class="ng-stat-num"><?php echo (int)($counts->total ?? 0); ?></div><div class="ng-stat-lbl">Total Submissions</div></div>
        <div class="ng-stat-card"><div class="ng-stat-num" style="color:#ff9800;"><?php echo (int)($counts->pending ?? 0); ?></div><div class="ng-stat-lbl">Pending Review</div></div>
        <div class="ng-stat-card"><div class="ng-stat-num" style="color:#2196f3;"><?php echo (int)($counts->approved ?? 0); ?></div><div class="ng-stat-lbl">Approved</div></div>
        <div class="ng-stat-card"><div class="ng-stat-num" style="color:#4caf50;"><?php echo (int)($counts->published ?? 0); ?></div><div class="ng-stat-lbl">Published Live</div></div>
        <div class="ng-stat-card"><div class="ng-stat-num" style="color:#f44336;"><?php echo (int)($counts->rejected ?? 0); ?></div><div class="ng-stat-lbl">Rejected</div></div>
    </div>

    <!-- Filters -->
    <form method="GET" action="<?php echo url('admin/nextgen_challenge/index'); ?>">
        <div class="ng-filter-bar">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>" placeholder="Search name, email, title..." style="min-width:220px;" />
            <select name="stream">
                <option value="">All Streams</option>
                <option value="AI"     <?php echo $streamF === 'AI'     ? 'selected' : ''; ?>>🤖 AI Stream</option>
                <option value="Talent" <?php echo $streamF === 'Talent' ? 'selected' : ''; ?>>🎤 Talent Stream</option>
            </select>
            <select name="status_filter">
                <option value="">All Statuses</option>
                <option value="pending"   <?php echo $statusF === 'pending'   ? 'selected' : ''; ?>>⏳ Pending</option>
                <option value="approved"  <?php echo $statusF === 'approved'  ? 'selected' : ''; ?>>✅ Approved</option>
                <option value="rejected"  <?php echo $statusF === 'rejected'  ? 'selected' : ''; ?>>❌ Rejected</option>
                <option value="published" <?php echo $statusF === 'published' ? 'selected' : ''; ?>>🌍 Published</option>
            </select>
            <button type="submit">Search</button>
            <?php if ($search || $streamF || $statusF): ?>
            <a href="<?php echo url('admin/nextgen_challenge/index'); ?>" class="reset">Clear filters</a>
            <?php endif; ?>
            <span style="margin-left:auto;color:#666;font-size:12px;">
                <?php echo number_format($total); ?> submission<?php echo $total !== 1 ? 's' : ''; ?>
            </span>
        </div>
    </form>

    <!-- Table -->
    <?php if (!empty($rows)): ?>
    <table class="ng-table">
        <thead>
            <tr>
                <th>#ID</th>
                <th>Participant</th>
                <th>Stream</th>
                <th>Title</th>
                <th>Status</th>
                <th>Video</th>
                <th>YouTube</th>
                <th>Interest</th>
                <th>Submitted</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
            <?php $interestCount = (int)($interestCounts[(int)($r['id'] ?? 0)] ?? 0); ?>
            <?php $interestPreview = $interestPreviews[(int)($r['id'] ?? 0)] ?? null; ?>
            <tr>
                <td><?php echo (int)$r['id']; ?></td>
                <td>
                    <strong><?php echo htmlspecialchars($r['full_name'] ?? $r['alias_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></strong><br>
                    <span style="font-size:11px;color:#888;"><?php echo htmlspecialchars($r['email'] ?? '—', ENT_QUOTES); ?></span>
                </td>
                <td>
                    <?php if ($r['stream'] === 'AI'): ?>
                    <span style="color:#7c3aed;font-weight:700;">🤖 AI</span>
                    <?php else: ?>
                    <span style="color:#d97706;font-weight:700;">🎤 Talent</span>
                    <?php endif; ?>
                </td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?php echo htmlspecialchars($r['title'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                </td>
                <td><?php echo $statusLabel((int)($r['admin_status'] ?? 0), (int)($r['published'] ?? 0)); ?></td>
                <td>
                    <?php
                    if (!empty($r['video_path'])) {
                        if (preg_match('/^https?:\/\//i', $r['video_path'])) {
                            echo '<a href="' . htmlspecialchars($r['video_path'], ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener" style="color:#1a73e8;font-weight:700;">&#128279; Drive</a>';
                        } else {
                            echo '<span style="color:#4caf50;">✓ Local file</span>';
                        }
                    } else {
                        echo '<span style="color:#aaa;">—</span>';
                    }
                    ?>
                </td>
                <td>
                    <?php if (!empty($r['youtube_link'])): ?>
                    <a href="<?php echo htmlspecialchars($r['youtube_link'], ENT_QUOTES); ?>" target="_blank" rel="noopener" style="color:#ff0000;font-weight:700;">▶ Watch</a>
                    <?php elseif (!empty($r['youtube_sent_at'])): ?>
                    <span style="color:#2196f3;">Sent</span>
                    <?php else: ?>
                    <span style="color:#aaa;">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($interestCount > 0): ?>
                    <span class="ng-interest-pill"><i class="fa fa-university"></i> <?php echo $interestCount; ?> interested</span>
                    <?php if (!empty($interestPreview)): ?>
                    <div class="ng-interest-meta">
                        <strong><?php echo htmlspecialchars($interestPreview['institution_name'] ?? 'Institution', ENT_QUOTES, 'UTF-8'); ?></strong><br>
                        <?php if (!empty($interestPreview['contact_email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($interestPreview['contact_email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($interestPreview['contact_email'], ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else: ?>
                        <span>—</span>
                        <?php endif; ?>
                        <?php if ($interestCount > 1): ?>
                        <br><span>+<?php echo $interestCount - 1; ?> more in details</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="ng-interest-pill ng-interest-pill--none">—</span>
                    <?php endif; ?>
                </td>
                <td style="white-space:nowrap;font-size:12px;color:#666;">
                    <?php echo htmlspecialchars(substr($r['created_at'] ?? '', 0, 10), ENT_QUOTES); ?>
                </td>
                <td>
                    <div class="ng-actions">
                        <a href="<?php echo url('admin/nextgen_challenge/details/' . (int)$r['id']); ?>" class="btn-ng-details">View</a>
                        <?php if (!empty($r['video_path'])): ?>
                        <a href="<?php echo url('admin/nextgen_challenge/download_video/' . (int)$r['id']); ?>" class="btn-ng-download">⬇</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="ng-pagination">
        <?php if ($page > 1): ?>
        <a href="<?php echo $pageUrl($page - 1); ?>">&laquo; Prev</a>
        <?php else: ?>
        <span class="disabled">&laquo; Prev</span>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 3);
        $end   = min($totalPages, $page + 3);
        for ($pg = $start; $pg <= $end; $pg++):
        ?>
        <?php if ($pg === $page): ?>
        <span class="current"><?php echo $pg; ?></span>
        <?php else: ?>
        <a href="<?php echo $pageUrl($pg); ?>"><?php echo $pg; ?></a>
        <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
        <a href="<?php echo $pageUrl($page + 1); ?>">Next &raquo;</a>
        <?php else: ?>
        <span class="disabled">Next &raquo;</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="ng-no-rows">
        <div class="icon">🎬</div>
        <p>No submissions found<?php echo ($search || $streamF || $statusF) ? ' for this filter' : ' yet'; ?>.</p>
    </div>
    <?php endif; ?>

</div>
@endsection
