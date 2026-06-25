@extends('admin.common')
@section('content')
<?php
$row = $_page_data['row'] ?? null;
if (!$row) { echo '<p>Not found.</p>'; return; }

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
.ipd-wrap { padding: 24px; max-width: 860px; }
.ipd-back { display:inline-flex; align-items:center; gap:6px; color:#4361ee; text-decoration:none; font-size:13px; font-weight:600; margin-bottom:18px; }
.ipd-back:hover { text-decoration:underline; }
.ipd-card { background:#fff; border:1px solid #dee2e6; border-radius:12px; overflow:hidden; margin-bottom:20px; }
.ipd-card-header { background:#f1f3f9; padding:14px 20px; border-bottom:1px solid #dee2e6; display:flex; align-items:center; justify-content:space-between; }
.ipd-card-header h3 { margin:0; font-size:15px; font-weight:700; color:#333; }
.ipd-card-body { padding:20px; }

.ipd-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.ipd-field label { display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#888; margin-bottom:5px; }
.ipd-field .val { font-size:14px; color:#222; word-break:break-word; }
.ipd-field .val a { color:#4361ee; }

.ipd-message-box { background:#fafafa; border:1px solid #efefef; border-radius:8px; padding:16px; font-size:14px; color:#333; line-height:1.65; white-space:pre-wrap; word-break:break-word; }

.ip-badge { display:inline-block; padding:3px 10px; border-radius:100px; font-size:11px; font-weight:600; }
.ip-badge-new       { background:#dbeafe; color:#1d4ed8; }
.ip-badge-read      { background:#f3f4f6; color:#374151; }
.ip-badge-contacted { background:#d1fae5; color:#065f46; }
.ip-badge-closed    { background:#fee2e2; color:#991b1b; }

.ipd-status-form { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.ipd-status-form select { padding:7px 12px; border:1px solid #ced4da; border-radius:6px; font-size:13px; }
.ipd-status-form button { padding:7px 18px; background:#4361ee; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; }
.ipd-status-form button:hover { background:#3651d4; }

.ipd-email-btn { display:inline-flex; align-items:center; gap:6px; padding:9px 20px; background:#059669; color:#fff; text-decoration:none; border-radius:8px; font-size:13px; font-weight:600; }
.ipd-email-btn:hover { opacity:0.88; color:#fff; }

@media (max-width:600px) { .ipd-grid { grid-template-columns:1fr; } }
</style>

<div class="ipd-wrap">
    <a href="<?php echo url('admin/institution_partner/index'); ?>" class="ipd-back">
        <i class="fa fa-arrow-left"></i> Back to All Enquiries
    </a>

    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
        <div>
            <h2 style="margin:0 0 4px; font-size:22px; font-weight:800;">
                <?php echo htmlspecialchars($row->institution_name, ENT_QUOTES); ?>
            </h2>
            <div style="font-size:13px; color:#888;">Submitted: <?php echo date('d M Y, H:i', strtotime($row->created_at)); ?>&nbsp;&nbsp;<?php echo $statusBadge($row->status); ?></div>
        </div>
        <a href="mailto:<?php echo htmlspecialchars($row->email, ENT_QUOTES); ?>?subject=Re: Partnership Enquiry - <?php echo rawurlencode($row->institution_name); ?>" class="ipd-email-btn">
            <i class="fa fa-envelope"></i> Email <?php echo htmlspecialchars($row->contact_person, ENT_QUOTES); ?>
        </a>
    </div>

    <!-- Institution Details -->
    <div class="ipd-card">
        <div class="ipd-card-header"><h3><i class="fa fa-building-o"></i>&nbsp; Institution Details</h3></div>
        <div class="ipd-card-body">
            <div class="ipd-grid">
                <div class="ipd-field"><label>Institution Name</label><div class="val"><?php echo htmlspecialchars($row->institution_name, ENT_QUOTES); ?></div></div>
                <div class="ipd-field"><label>Institution Type</label><div class="val"><?php echo htmlspecialchars($row->institution_type ?? '-', ENT_QUOTES); ?></div></div>
                <div class="ipd-field"><label>Country / Region</label><div class="val"><?php echo htmlspecialchars($row->country ?? '-', ENT_QUOTES); ?></div></div>
                <div class="ipd-field"><label>Int'l Students (approx.)</label><div class="val"><?php echo htmlspecialchars($row->intl_students ?? '-', ENT_QUOTES); ?></div></div>
                <div class="ipd-field" style="grid-column:1/-1;">
                    <label>Website</label>
                    <div class="val">
                        <?php if (!empty($row->website)): ?>
                        <a href="<?php echo htmlspecialchars($row->website, ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($row->website, ENT_QUOTES); ?></a>
                        <?php else: ?>-<?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Person -->
    <div class="ipd-card">
        <div class="ipd-card-header"><h3><i class="fa fa-user-o"></i>&nbsp; Contact Person</h3></div>
        <div class="ipd-card-body">
            <div class="ipd-grid">
                <div class="ipd-field"><label>Full Name</label><div class="val"><?php echo htmlspecialchars($row->contact_person, ENT_QUOTES); ?></div></div>
                <div class="ipd-field"><label>Phone</label><div class="val"><?php echo htmlspecialchars($row->phone, ENT_QUOTES); ?></div></div>
                <div class="ipd-field" style="grid-column:1/-1;">
                    <label>Email Address</label>
                    <div class="val">
                        <a href="mailto:<?php echo htmlspecialchars($row->email, ENT_QUOTES); ?>">
                            <?php echo htmlspecialchars($row->email, ENT_QUOTES); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Partnership Message -->
    <div class="ipd-card">
        <div class="ipd-card-header"><h3><i class="fa fa-comments-o"></i>&nbsp; Partnership Message</h3></div>
        <div class="ipd-card-body">
            <div class="ipd-message-box"><?php echo htmlspecialchars($row->message, ENT_QUOTES); ?></div>
        </div>
    </div>

    <!-- Status Management -->
    <div class="ipd-card">
        <div class="ipd-card-header"><h3><i class="fa fa-tag"></i>&nbsp; Update Status</h3></div>
        <div class="ipd-card-body">
            <form method="POST" action="<?php echo url('admin/institution_partner/details/' . $row->id); ?>">
                @csrf
                <div class="ipd-status-form">
                    <select name="status">
                        <option value="new"       <?php echo $row->status==='new'?'selected':''; ?>>New</option>
                        <option value="read"      <?php echo $row->status==='read'?'selected':''; ?>>Read</option>
                        <option value="contacted" <?php echo $row->status==='contacted'?'selected':''; ?>>Contacted</option>
                        <option value="closed"    <?php echo $row->status==='closed'?'selected':''; ?>>Closed</option>
                    </select>
                    <button type="submit"><i class="fa fa-save"></i> Update Status</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
