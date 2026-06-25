@extends('admin.common')
@section('content')
<?php
$rows        = $_page_data['rows']          ?? collect();
$total       = (int)($_page_data['total']   ?? 0);
$page        = (int)($_page_data['page']    ?? 1);
$totalPages  = (int)($_page_data['total_pages'] ?? 1);
$search      = $_page_data['search']        ?? '';
$statusF     = $_page_data['status_filter'] ?? '';

$baseUrl = url('admin/institution_partner/index');

$pageUrl = function($pg) use ($search, $statusF, $baseUrl) {
    $params = http_build_query(array_filter(['search' => $search, 'status_filter' => $statusF, 'page' => $pg]));
    return $baseUrl . ($params ? '?' . $params : '');
};

$statusBadge = function($s) {
    switch ($s) {
        case 'new':       return '<span class="ip-badge ip-badge-new">New</span>';
        case 'read':      return '<span class="ip-badge ip-badge-read">Read</span>';
        case 'contacted': return '<span class="ip-badge ip-badge-contacted">Contacted</span>';
        case 'closed':    return '<span class="ip-badge ip-badge-closed">Closed</span>';
        default:          return '<span class="ip-badge ip-badge-read">' . htmlspecialchars($s) . '</span>';
    }
};
?>

<style>
.ip-admin-wrap { padding: 24px; }
.ip-admin-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:12px; }
.ip-admin-top h2 { margin:0; font-size:22px; font-weight:700; }

.ip-filter-bar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:20px; background:#f8f9fa; padding:14px 16px; border-radius:8px; border:1px solid #dee2e6; }
.ip-filter-bar input, .ip-filter-bar select { padding:7px 12px; border:1px solid #ced4da; border-radius:6px; font-size:13px; }
.ip-filter-bar button { padding:7px 18px; background:#4361ee; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; }
.ip-filter-bar button:hover { background:#3651d4; }
.ip-filter-bar a.reset { font-size:13px; color:#666; text-decoration:underline; }

.ip-stat-row { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.ip-stat-card { background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:14px 20px; min-width:130px; }
.ip-stat-num { font-size:26px; font-weight:800; color:#4361ee; }
.ip-stat-lbl { font-size:12px; color:#666; margin-top:2px; }

table.ip-table { width:100%; border-collapse:collapse; background:#fff; border:1px solid #dee2e6; border-radius:8px; overflow:hidden; font-size:13px; }
.ip-table th { background:#f1f3f9; font-weight:700; padding:11px 14px; text-align:left; color:#333; border-bottom:2px solid #dee2e6; white-space:nowrap; }
.ip-table td { padding:11px 14px; border-bottom:1px solid #f0f0f0; vertical-align:middle; color:#444; }
.ip-table tbody tr:hover { background:#fafbff; }
.ip-table tbody tr:last-child td { border-bottom:none; }

.ip-badge { display:inline-block; padding:3px 10px; border-radius:100px; font-size:11px; font-weight:600; }
.ip-badge-new       { background:#dbeafe; color:#1d4ed8; }
.ip-badge-read      { background:#f3f4f6; color:#374151; }
.ip-badge-contacted { background:#d1fae5; color:#065f46; }
.ip-badge-closed    { background:#fee2e2; color:#991b1b; }

.ip-view-btn { padding:5px 13px; background:#4361ee; color:#fff; text-decoration:none; border-radius:6px; font-size:12px; font-weight:600; white-space:nowrap; }
.ip-view-btn:hover { background:#3651d4; color:#fff; }

.ip-pagination { margin-top:18px; display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
.ip-pagination a, .ip-pagination span { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; padding:0 8px; border:1px solid #dee2e6; border-radius:6px; font-size:13px; text-decoration:none; color:#333; background:#fff; }
.ip-pagination a:hover { background:#4361ee; color:#fff; border-color:#4361ee; }
.ip-pagination span.active { background:#4361ee; color:#fff; border-color:#4361ee; font-weight:700; }
.ip-pagination span.disabled { color:#aaa; }
</style>

<div class="ip-admin-wrap">
    <div class="ip-admin-top">
        <h2><i class="fa fa-university"></i> Institution Partnership Enquiries</h2>
        <div style="font-size:13px; color:#666;">Total: <strong><?php echo $total; ?></strong> enquiries</div>
    </div>

    <!-- Stats -->
    <?php
    $statCounts = \Illuminate\Support\Facades\DB::table('institution_partner_inquiries')
        ->selectRaw('status, COUNT(*) as cnt')
        ->groupBy('status')
        ->pluck('cnt', 'status')
        ->toArray();
    ?>
    <div class="ip-stat-row">
        <div class="ip-stat-card"><div class="ip-stat-num"><?php echo $total; ?></div><div class="ip-stat-lbl">Total</div></div>
        <div class="ip-stat-card"><div class="ip-stat-num" style="color:#1d4ed8;"><?php echo $statCounts['new'] ?? 0; ?></div><div class="ip-stat-lbl">New</div></div>
        <div class="ip-stat-card"><div class="ip-stat-num" style="color:#374151;"><?php echo $statCounts['read'] ?? 0; ?></div><div class="ip-stat-lbl">Read</div></div>
        <div class="ip-stat-card"><div class="ip-stat-num" style="color:#065f46;"><?php echo $statCounts['contacted'] ?? 0; ?></div><div class="ip-stat-lbl">Contacted</div></div>
        <div class="ip-stat-card"><div class="ip-stat-num" style="color:#991b1b;"><?php echo $statCounts['closed'] ?? 0; ?></div><div class="ip-stat-lbl">Closed</div></div>
    </div>

    <!-- Filter bar -->
    <form method="GET" action="<?php echo url('admin/institution_partner/index'); ?>" class="ip-filter-bar">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>" placeholder="Search name, email, country...">
        <select name="status_filter">
            <option value="">All Status</option>
            <option value="new"       <?php echo $statusF==='new'?'selected':''; ?>>New</option>
            <option value="read"      <?php echo $statusF==='read'?'selected':''; ?>>Read</option>
            <option value="contacted" <?php echo $statusF==='contacted'?'selected':''; ?>>Contacted</option>
            <option value="closed"    <?php echo $statusF==='closed'?'selected':''; ?>>Closed</option>
        </select>
        <button type="submit"><i class="fa fa-search"></i> Search</button>
        <?php if (!empty($search) || !empty($statusF)): ?>
        <a href="<?php echo url('admin/institution_partner/index'); ?>" class="reset"><i class="fa fa-times"></i> Reset</a>
        <?php endif; ?>
    </form>

    <!-- Table -->
    <table class="ip-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Institution</th>
                <th>Type</th>
                <th>Country</th>
                <th>Contact Person</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Date</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if ($rows->isEmpty()): ?>
            <tr><td colspan="10" style="text-align:center; padding:32px; color:#999;">No enquiries found.</td></tr>
        <?php else: foreach ($rows as $row): ?>
            <tr>
                <td><?php echo (int)$row->id; ?></td>
                <td><strong><?php echo htmlspecialchars($row->institution_name, ENT_QUOTES); ?></strong></td>
                <td><?php echo htmlspecialchars($row->institution_type ?? '-', ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars($row->country ?? '-', ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars($row->contact_person, ENT_QUOTES); ?></td>
                <td>
                    <a href="mailto:<?php echo htmlspecialchars($row->email, ENT_QUOTES); ?>">
                        <?php echo htmlspecialchars($row->email, ENT_QUOTES); ?>
                    </a>
                </td>
                <td><?php echo htmlspecialchars($row->phone, ENT_QUOTES); ?></td>
                <td style="white-space:nowrap;"><?php echo date('d M Y', strtotime($row->created_at)); ?></td>
                <td><?php echo $statusBadge($row->status); ?></td>
                <td>
                    <a href="<?php echo url('admin/institution_partner/details/' . $row->id); ?>" class="ip-view-btn">
                        <i class="fa fa-eye"></i> View
                    </a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="ip-pagination">
        <?php if ($page > 1): ?>
            <a href="<?php echo $pageUrl($page - 1); ?>">&laquo;</a>
        <?php else: ?>
            <span class="disabled">&laquo;</span>
        <?php endif; ?>
        <?php for ($p = max(1, $page - 3); $p <= min($totalPages, $page + 3); $p++): ?>
            <?php if ($p === $page): ?>
                <span class="active"><?php echo $p; ?></span>
            <?php else: ?>
                <a href="<?php echo $pageUrl($p); ?>"><?php echo $p; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="<?php echo $pageUrl($page + 1); ?>">&raquo;</a>
        <?php else: ?>
            <span class="disabled">&raquo;</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>
@endsection
