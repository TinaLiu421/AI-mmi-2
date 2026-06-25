@extends('admin.common')
@section('content')
<?php
$notifications = $_page_data['notifications'] ?? collect();
$unreadCount   = (int)($_page_data['unread_count'] ?? 0);
$statusFilter  = $_page_data['status_filter'] ?? '';
$csrf          = $_page_csrf_token ?? '';
$updateUrl     = url('admin/student_interests/update');
?>

<style>
.si-wrap          { padding: 24px; }
.si-top           { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.si-top h2        { margin: 0; font-size: 22px; font-weight: 700; }
.si-unread-badge  { background: #4361ee; color: #fff; padding: 3px 12px; border-radius: 100px; font-size: 13px; font-weight: 700; }
.si-filter-bar    { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 20px; background: #f8f9fa; padding: 14px 16px; border-radius: 8px; border: 1px solid #dee2e6; }
.si-filter-bar select { padding: 7px 12px; border: 1px solid #ced4da; border-radius: 6px; font-size: 13px; }
.si-filter-bar button { padding: 7px 18px; background: #4361ee; color: #fff; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; }
.si-filter-bar a.reset { font-size: 13px; color: #666; text-decoration: underline; }

table.si-table    { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; font-size: 13px; }
.si-table th      { background: #f1f3f9; font-weight: 700; padding: 12px 14px; text-align: left; color: #333; border-bottom: 2px solid #dee2e6; white-space: nowrap; }
.si-table td      { padding: 11px 14px; border-bottom: 1px solid #f0f0f0; vertical-align: top; color: #444; }
.si-table tbody tr:hover { background: #fafbff; }
.si-table tbody tr.si-row-unread { background: #fffbf0; }
.si-table tbody tr:last-child td { border-bottom: none; }

.si-member-cell   { display: flex; align-items: center; gap: 8px; }
.si-avatar-wrap   { width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0; overflow: hidden; background: #dee2e6; display: flex; align-items: center; justify-content: center; }
.si-avatar-img    { width: 100%; height: 100%; background-size: cover; background-position: center; }
.si-avatar-init   { font-weight: 700; font-size: 14px; color: #4361ee; }
.si-name          { font-weight: 600; font-size: 13px; color: #222; }
.si-sub           { font-size: 11px; color: #888; margin-top: 2px; }

.si-status        { display: inline-block; padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.si-status-pending    { background: #fff3cd; color: #856404; }
.si-status-contacted  { background: #d1e7dd; color: #155724; }
.si-status-closed     { background: #f8d7da; color: #721c24; }

.si-unread-dot    { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #4361ee; margin-right: 4px; vertical-align: middle; }

.si-actions       { display: flex; flex-direction: column; gap: 6px; min-width: 180px; }
.si-note-form     { display: flex; gap: 4px; }
.si-note-input    { flex: 1; border: 1px solid #ced4da; border-radius: 6px; padding: 5px 8px; font-size: 12px; resize: none; }
.si-btn           { padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; white-space: nowrap; }
.si-btn-blue      { background: #4361ee; color: #fff; }
.si-btn-green     { background: #198754; color: #fff; }
.si-btn-grey      { background: #6c757d; color: #fff; }
.si-btn-red       { background: #dc3545; color: #fff; }
.si-btn:hover     { opacity: 0.88; }
.si-btn:disabled  { opacity: 0.5; cursor: default; }

.si-toast         { position: fixed; bottom: 28px; left: 50%; transform: translateX(-50%) translateY(30px); background: #222; color: #fff; padding: 10px 24px; border-radius: 8px; font-size: 14px; opacity: 0; pointer-events: none; transition: all .3s; z-index: 9999; }
.si-toast.si-toast-show { opacity: 1; transform: translateX(-50%) translateY(0); }
.si-toast.si-toast-ok   { background: #198754; }
.si-toast.si-toast-err  { background: #dc3545; }

.si-profile-snippet { font-size: 12px; color: #666; margin-top: 4px; }
.si-profile-snippet .si-tag { display: inline-block; background: #e9ecef; color: #495057; border-radius: 4px; padding: 2px 7px; margin: 2px 2px 2px 0; font-size: 11px; }

.si-note-display  { font-size: 12px; color: #555; font-style: italic; margin-top: 6px; max-width: 260px; }
.si-view-link     { font-size: 12px; color: #4361ee; text-decoration: underline; }

.si-empty         { text-align: center; padding: 60px 20px; color: #888; }
.si-empty-icon    { font-size: 48px; margin-bottom: 12px; }
.si-stat-bar      { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
.si-stat-card     { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 12px 20px; font-size: 13px; color: #555; min-width: 130px; }
.si-stat-card strong { display: block; font-size: 24px; font-weight: 800; color: #4361ee; }
.si-pagination    { display: flex; gap: 6px; margin-top: 20px; }
.si-page-link     { padding: 6px 12px; border: 1px solid #dee2e6; border-radius: 6px; text-decoration: none; color: #333; font-size: 13px; }
.si-page-link.si-page-active { background: #4361ee; color: #fff; border-color: #4361ee; }
</style>

<div class="si-wrap">
    <div class="sp-toast" id="si-toast"></div>

    <div class="si-top">
        <h2>🎓 Student Interests
            <?php if ($unreadCount > 0): ?>
            <span class="si-unread-badge"><?php echo $unreadCount; ?> new</span>
            <?php endif; ?>
        </h2>
    </div>

    {{-- Stats --}}
    <?php
    $total      = $notifications->total();
    $pending    = $notifications->getCollection()->where('status', 'pending')->count();
    $contacted  = $notifications->getCollection()->where('status', 'contacted')->count();
    ?>
    <div class="si-stat-bar">
        <div class="si-stat-card"><strong><?php echo $notifications->total(); ?></strong> Total Matches</div>
        <div class="si-stat-card"><strong><?php echo $unreadCount; ?></strong> Unread</div>
    </div>

    {{-- Filter --}}
    <form method="GET" class="si-filter-bar">
        <select name="status" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="contacted" <?php echo $statusFilter === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
            <option value="closed" <?php echo $statusFilter === 'closed' ? 'selected' : ''; ?>>Closed</option>
        </select>
        <?php if ($statusFilter): ?>
        <a href="<?php echo url('admin/student_interests'); ?>" class="reset">✕ Clear</a>
        <?php endif; ?>
    </form>

    <?php if ($notifications->isEmpty()): ?>
    <div class="si-empty">
        <div class="si-empty-icon">🎓</div>
        <p>No institution interest notifications yet.</p>
    </div>
    <?php else: ?>

    <table class="si-table">
        <thead>
            <tr>
                <th>Institution</th>
                <th>Student</th>
                <th>Student Profile</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notifications as $n): ?>
            <?php
                $instName    = trim($n->inst_name ?? '') ?: trim($n->inst_company ?? '') ?: 'Institution #' . $n->institution_member_id;
                $stuName     = trim($n->stu_name ?? '') ?: trim(($n->stu_first ?? '') . ' ' . ($n->stu_last ?? ''));
                if (!$stuName) $stuName = 'Student #' . $n->student_member_id;
                $instInit    = strtoupper(mb_substr($instName, 0, 1));
                $stuInit     = strtoupper(mb_substr($stuName, 0, 1));
                $statusClass = ['pending' => 'si-status-pending', 'contacted' => 'si-status-contacted', 'closed' => 'si-status-closed'];
                $statusLabel = ['pending' => 'Pending', 'contacted' => '✓ Contacted', 'closed' => 'Closed'];
                $stuFields   = json_decode($n->stu_fields ?? '[]', true) ?: [];
                $date        = $n->created_at ? date('d M Y', strtotime($n->created_at)) : '—';
                $isUnread    = !$n->is_read;
            ?>
            <tr class="<?php echo $isUnread ? 'si-row-unread' : ''; ?>">
                <td>
                    <div class="si-member-cell">
                        <div class="si-avatar-wrap">
                            <?php if (!empty($n->inst_avatar)): ?>
                            <div class="si-avatar-img" style="background-image:url('/upload/member_avatar/<?php echo htmlspecialchars($n->inst_avatar, ENT_QUOTES); ?>')"></div>
                            <?php else: ?>
                            <div class="si-avatar-init"><?php echo htmlspecialchars($instInit); ?></div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="si-name"><?php echo htmlspecialchars($instName, ENT_QUOTES); ?></div>
                            <div class="si-sub">Institution · ID <?php echo (int)$n->institution_member_id; ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="si-member-cell">
                        <div class="si-avatar-wrap">
                            <?php if (!empty($n->stu_avatar)): ?>
                            <div class="si-avatar-img" style="background-image:url('/upload/member_avatar/<?php echo htmlspecialchars($n->stu_avatar, ENT_QUOTES); ?>')"></div>
                            <?php else: ?>
                            <div class="si-avatar-init"><?php echo htmlspecialchars($stuInit); ?></div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($isUnread): ?><span class="si-unread-dot"></span><?php endif; ?>
                            <div class="si-name"><?php echo htmlspecialchars($stuName, ENT_QUOTES); ?></div>
                            <div class="si-sub">Student · ID <?php echo (int)$n->student_member_id; ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="si-profile-snippet">
                        <?php if (!empty($n->stu_headline)): ?>
                        <div style="margin-bottom:4px;"><?php echo htmlspecialchars($n->stu_headline, ENT_QUOTES); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($n->stu_nationality)): ?>
                        <span class="si-tag">🌍 <?php echo htmlspecialchars($n->stu_nationality, ENT_QUOTES); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($n->stu_degree)): ?>
                        <span class="si-tag">🎓 <?php echo htmlspecialchars(ucfirst($n->stu_degree), ENT_QUOTES); ?></span>
                        <?php endif; ?>
                        <?php foreach (array_slice($stuFields, 0, 2) as $f): ?>
                        <span class="si-tag"><?php echo htmlspecialchars($f, ENT_QUOTES); ?></span>
                        <?php endforeach; ?>
                    </div>
                </td>
                <td><span class="si-status <?php echo $statusClass[$n->status] ?? 'si-status-pending'; ?>"><?php echo $statusLabel[$n->status] ?? ucfirst($n->status); ?></span></td>
                <td><?php echo $date; ?></td>
                <td>
                    <div class="si-actions">
                        <?php if ($n->status === 'pending'): ?>
                        <button class="si-btn si-btn-green" onclick="siUpdate(<?php echo $n->id; ?>, 'contacted', this)">✓ Mark Contacted</button>
                        <?php elseif ($n->status === 'contacted'): ?>
                        <button class="si-btn si-btn-grey" onclick="siUpdate(<?php echo $n->id; ?>, 'close', this)">Close</button>
                        <?php else: ?>
                        <button class="si-btn si-btn-grey" onclick="siUpdate(<?php echo $n->id; ?>, 'reopen', this)">Reopen</button>
                        <?php endif; ?>
                        <div class="si-note-form">
                            <textarea class="si-note-input" rows="2" id="note-<?php echo $n->id; ?>" placeholder="Add admin note…"><?php echo htmlspecialchars($n->admin_notes ?? '', ENT_QUOTES); ?></textarea>
                            <button class="si-btn si-btn-blue" onclick="siSaveNote(<?php echo $n->id; ?>, this)">Save</button>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    {{-- Pagination --}}
    <?php if ($notifications->lastPage() > 1): ?>
    <div class="si-pagination">
        <?php for ($p = 1; $p <= $notifications->lastPage(); $p++): ?>
        <a href="<?php echo url('admin/student_interests') . '?status=' . $statusFilter . '&page=' . $p; ?>"
            class="si-page-link<?php echo $p === $notifications->currentPage() ? ' si-page-active' : ''; ?>"><?php echo $p; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</div>

<script>
const SI_UPDATE_URL = '<?php echo $updateUrl; ?>';
const SI_TOKEN      = '<?php echo $csrf; ?>';

function siUpdate(id, action, btn) {
    btn.disabled = true;
    fetch(SI_UPDATE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': SI_TOKEN },
        body: JSON.stringify({ id, action })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 200) {
            siToast('✓ Updated', 'ok');
            setTimeout(() => location.reload(), 700);
        } else {
            btn.disabled = false;
            siToast('⚠ ' + (res.message || 'Error'), 'err');
        }
    })
    .catch(() => { btn.disabled = false; siToast('⚠ Network error', 'err'); });
}

function siSaveNote(id, btn) {
    const note = document.getElementById('note-' + id)?.value ?? '';
    btn.disabled = true;
    fetch(SI_UPDATE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': SI_TOKEN },
        body: JSON.stringify({ id, action: '', note })
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        if (res.status === 200) {
            siToast('✓ Note saved', 'ok');
        } else {
            siToast('⚠ ' + (res.message || 'Error'), 'err');
        }
    })
    .catch(() => { btn.disabled = false; siToast('⚠ Network error', 'err'); });
}

function siToast(msg, type) {
    const t = document.getElementById('si-toast');
    t.textContent = msg;
    t.className = 'sp-toast si-toast-' + type + ' si-toast-show';
    setTimeout(() => t.classList.remove('si-toast-show'), 3000);
}
</script>
@endsection
