@extends('admin.common')
@section('content')
<?php
$rows         = $_page_data['rows']          ?? [];
$total        = (int)($_page_data['total']   ?? 0);
$page         = (int)($_page_data['page']    ?? 1);
$totalPages   = (int)($_page_data['total_pages'] ?? 1);
$search       = $_page_data['search']        ?? '';
$statusFilter = $_page_data['status_filter'] ?? 'active';
$dreamsMissing = $_page_data['dreams_missing'] ?? false;

$baseUrl = url('admin/student_dreams');

$pageUrl = function($pg) use ($search, $statusFilter, $baseUrl) {
    $params = http_build_query(array_filter([
        'search'        => $search,
        'status_filter' => $statusFilter,
        'page'          => $pg,
    ], fn($v) => $v !== '' && $v !== null));
    return $baseUrl . ($params ? '?' . $params : '');
};

$csrf = $_page_csrf_token ?? '';
?>

<style>
.sd-wrap          { padding: 24px; }
.sd-top           { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.sd-top h2        { margin: 0; font-size: 22px; font-weight: 700; }
.sd-filter-bar    { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 20px; background: #f8f9fa; padding: 14px 16px; border-radius: 8px; border: 1px solid #dee2e6; }
.sd-filter-bar input,
.sd-filter-bar select { padding: 7px 12px; border: 1px solid #ced4da; border-radius: 6px; font-size: 13px; }
.sd-filter-bar button { padding: 7px 18px; background: #4361ee; color: #fff; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; }
.sd-filter-bar button:hover { background: #3651d4; }
.sd-filter-bar a.reset { font-size: 13px; color: #666; text-decoration: underline; }

.sd-stat          { display: inline-block; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 10px 20px; margin-bottom: 20px; font-size: 13px; color: #555; }
.sd-stat strong   { font-size: 20px; font-weight: 800; color: #4361ee; margin-right: 6px; }

table.sd-table    { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; font-size: 13px; }
.sd-table th      { background: #f1f3f9; font-weight: 700; padding: 12px 14px; text-align: left; color: #333; border-bottom: 2px solid #dee2e6; white-space: nowrap; }
.sd-table td      { padding: 11px 14px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; color: #444; }
.sd-table tbody tr:hover { background: #fafbff; }
.sd-table tbody tr:last-child td { border-bottom: none; }
.sd-table .col-title  { max-width: 260px; }
.sd-table .col-desc   { max-width: 300px; color: #777; font-size: 12px; }

.sd-member-cell   { display: flex; align-items: center; gap: 8px; }
.sd-avatar        { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; background: #eee; flex-shrink: 0; }
.sd-member-name   { font-weight: 600; font-size: 13px; }
.sd-member-email  { font-size: 11px; color: #888; }

span.badge-active  { background: #4caf50; color: #fff; padding: 3px 10px; border-radius: 100px; font-size: 11px; white-space: nowrap; }
span.badge-removed { background: #f44336; color: #fff; padding: 3px 10px; border-radius: 100px; font-size: 11px; white-space: nowrap; }

.sd-actions       { display: flex; gap: 6px; flex-wrap: nowrap; }
.btn-sd-view      { background: #4361ee; color: #fff; padding: 5px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; white-space: nowrap; border: none; cursor: pointer; }
.btn-sd-view:hover { background: #3651d4; color: #fff; text-decoration: none; }
.btn-sd-remove    { background: #dc3545; color: #fff; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; white-space: nowrap; border: none; cursor: pointer; }
.btn-sd-remove:hover { background: #b02a37; }
.btn-sd-restore   { background: #198754; color: #fff; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; white-space: nowrap; border: none; cursor: pointer; }
.btn-sd-restore:hover { background: #146c43; }

.sd-pagination    { display: flex; align-items: center; gap: 6px; margin-top: 20px; flex-wrap: wrap; }
.sd-pagination a,
.sd-pagination span { padding: 6px 12px; border-radius: 6px; font-size: 13px; border: 1px solid #dee2e6; background: #fff; color: #333; text-decoration: none; }
.sd-pagination a:hover { background: #f1f3f9; text-decoration: none; }
.sd-pagination span.current { background: #4361ee; color: #fff; border-color: #4361ee; }

.sd-empty         { text-align: center; padding: 48px 24px; color: #888; font-size: 14px; }
.sd-alert         { background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 16px 20px; color: #7d5a00; margin-bottom: 20px; }
</style>

<div class="sd-wrap">

    <div class="sd-top">
        <h2>&#127775; Student Dreams</h2>
    </div>

    <?php if ($dreamsMissing): ?>
    <div class="sd-alert">
        The <code>app_student_dreams</code> table does not exist yet. No dreams have been created.
    </div>
    <?php else: ?>

    {{-- Filter bar --}}
    <form class="sd-filter-bar" method="GET" action="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES); ?>">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>" placeholder="Search name, email or dream title&hellip;" style="width:240px;">
        <select name="status_filter">
            <option value="active"  <?php echo $statusFilter === 'active'  ? 'selected' : ''; ?>>Active only</option>
            <option value="removed" <?php echo $statusFilter === 'removed' ? 'selected' : ''; ?>>Removed only</option>
            <option value="all"     <?php echo $statusFilter === 'all'     ? 'selected' : ''; ?>>All</option>
        </select>
        <button type="submit">Filter</button>
        <a class="reset" href="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES); ?>">Reset</a>
    </form>

    {{-- Row count --}}
    <div class="sd-stat"><strong><?php echo $total; ?></strong> dream<?php echo $total !== 1 ? 's' : ''; ?> found</div>

    <?php if (empty($rows)): ?>
    <div class="sd-empty">No dreams match your filter.</div>
    <?php else: ?>

    <table class="sd-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Member</th>
                <th class="col-title">Dream Title</th>
                <th class="col-desc">Description</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $i => $row):
            $isRemoved  = !empty($row['deleted_at']) || (int)($row['status'] ?? 1) === 0;
            $avatarSrc  = !empty($row['avatar']) ? '/' . ltrim($row['avatar'], '/') : '/asset/image/icon-member.png';
            $previewUrl = url('en/study_plans/view/' . (int)$row['member_id']);
            $excerpt    = mb_strlen($row['description'] ?? '') > 100
                            ? mb_substr(strip_tags($row['description']), 0, 100) . '…'
                            : strip_tags($row['description'] ?? '');
        ?>
        <tr id="dream-row-<?php echo (int)$row['id']; ?>" class="<?php echo $isRemoved ? 'sd-row-removed' : ''; ?>">
            <td><?php echo ($page - 1) * 20 + $i + 1; ?></td>
            <td>
                <div class="sd-member-cell">
                    <img class="sd-avatar" src="<?php echo htmlspecialchars($avatarSrc, ENT_QUOTES); ?>" alt="">
                    <div>
                        <div class="sd-member-name"><?php echo htmlspecialchars($row['alias_name'] ?? '—', ENT_QUOTES); ?></div>
                        <div class="sd-member-email"><?php echo htmlspecialchars($row['email'] ?? '', ENT_QUOTES); ?></div>
                    </div>
                </div>
            </td>
            <td class="col-title"><?php echo htmlspecialchars($row['title'] ?? '—', ENT_QUOTES); ?></td>
            <td class="col-desc"><?php echo htmlspecialchars($excerpt, ENT_QUOTES); ?></td>
            <td>
                <?php if ($isRemoved): ?>
                <span class="badge-removed">Removed</span>
                <?php else: ?>
                <span class="badge-active">Active</span>
                <?php endif; ?>
            </td>
            <td><?php echo !empty($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : '—'; ?></td>
            <td>
                <div class="sd-actions">
                    <a class="btn-sd-view" href="<?php echo htmlspecialchars($previewUrl, ENT_QUOTES); ?>" target="_blank">View</a>
                    <?php if ($isRemoved): ?>
                    <button class="btn-sd-restore" onclick="sdAction('restore', <?php echo (int)$row['id']; ?>, this)">Restore</button>
                    <?php else: ?>
                    <button class="btn-sd-remove" onclick="sdAction('remove', <?php echo (int)$row['id']; ?>, this)">Remove</button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    {{-- Pagination --}}
    <?php if ($totalPages > 1): ?>
    <div class="sd-pagination">
        <?php if ($page > 1): ?>
            <a href="<?php echo htmlspecialchars($pageUrl($page - 1), ENT_QUOTES); ?>">&laquo; Prev</a>
        <?php endif; ?>

        <?php for ($pg = max(1, $page - 3); $pg <= min($totalPages, $page + 3); $pg++): ?>
            <?php if ($pg === $page): ?>
                <span class="current"><?php echo $pg; ?></span>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars($pageUrl($pg), ENT_QUOTES); ?>"><?php echo $pg; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="<?php echo htmlspecialchars($pageUrl($page + 1), ENT_QUOTES); ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php endif; // rows not empty ?>
    <?php endif; // dreamsMissing ?>

</div>

<script>
function sdAction(action, dreamId, btn) {
    var label = action === 'remove' ? 'remove' : 'restore';
    if (!confirm('Are you sure you want to ' + label + ' this dream?')) return;

    btn.disabled = true;
    btn.textContent = action === 'remove' ? 'Removing…' : 'Restoring…';

    fetch('<?php echo addslashes(url('admin/student_dreams')); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': '<?php echo addslashes($csrf); ?>'
        },
        body: new URLSearchParams({
            page_action: action,
            id: dreamId
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data && (data.status === 200 || data.status === 'ok')) {
            // Flip the row UI without a full reload
            var row = document.getElementById('dream-row-' + dreamId);
            if (!row) { location.reload(); return; }

            var badgeCell   = row.querySelector('span.badge-active, span.badge-removed');
            var actionsCell = row.querySelector('.sd-actions');

            if (action === 'remove') {
                if (badgeCell) { badgeCell.className = 'badge-removed'; badgeCell.textContent = 'Removed'; }
                if (actionsCell) {
                    var restoreBtn = document.createElement('button');
                    restoreBtn.className = 'btn-sd-restore';
                    restoreBtn.textContent = 'Restore';
                    restoreBtn.onclick = function() { sdAction('restore', dreamId, restoreBtn); };
                    actionsCell.replaceChild(restoreBtn, btn);
                }
            } else {
                if (badgeCell) { badgeCell.className = 'badge-active'; badgeCell.textContent = 'Active'; }
                if (actionsCell) {
                    var removeBtn = document.createElement('button');
                    removeBtn.className = 'btn-sd-remove';
                    removeBtn.textContent = 'Remove';
                    removeBtn.onclick = function() { sdAction('remove', dreamId, removeBtn); };
                    actionsCell.replaceChild(removeBtn, btn);
                }
            }
        } else {
            alert(data && data.message ? data.message : 'Action failed. Please try again.');
            btn.disabled = false;
            btn.textContent = action === 'remove' ? 'Remove' : 'Restore';
        }
    })
    .catch(function() {
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.textContent = action === 'remove' ? 'Remove' : 'Restore';
    });
}
</script>

@endsection
