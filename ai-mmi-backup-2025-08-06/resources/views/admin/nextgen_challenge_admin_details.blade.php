@extends('admin.common')
@section('content')
<?php
$row       = $_page_data['row']      ?? [];
$likes     = (int)($_page_data['likes']    ?? 0);
$comments  = $_page_data['comments'] ?? [];
$interests = $_page_data['interests'] ?? [];

$subId     = (int)($row['id'] ?? 0);
$adminStatus = (int)($row['admin_status'] ?? 0);
$published   = (int)($row['published'] ?? 0);
$videoPath   = $row['video_path'] ?? '';
$ytLink      = $row['youtube_link'] ?? '';
$ytSentAt    = $row['youtube_sent_at'] ?? '';

$listUrl   = url('admin/nextgen_challenge/index');
$actionBase= url('admin/nextgen_challenge');

$statusColors = [0 => '#ff9800', 1 => '#2196f3', 2 => '#f44336'];
$statusNames  = [0 => '⏳ Pending', 1 => '✅ Approved', 2 => '❌ Rejected'];
?>

<style>
.ngd-wrap { padding: 24px; max-width: 1100px; }
.ngd-back { display: inline-flex; align-items: center; gap: 6px; text-decoration: none; color: #4361ee; font-weight: 600; font-size: 14px; margin-bottom: 20px; }
.ngd-back:hover { text-decoration: underline; color: #4361ee; }
.ngd-title { font-size: 24px; font-weight: 800; margin: 0 0 4px; }
.ngd-subtitle { color: #666; font-size: 14px; margin-bottom: 24px; }

.ngd-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
@media(max-width: 800px) { .ngd-grid { grid-template-columns: 1fr; } }

.ngd-panel { background: #fff; border: 1px solid #dee2e6; border-radius: 10px; padding: 20px; }
.ngd-panel h3 { font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #333; margin: 0 0 16px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; }

.ngd-field { margin-bottom: 12px; }
.ngd-field label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #888; margin-bottom: 3px; letter-spacing: .05em; }
.ngd-field .val { font-size: 14px; color: #333; word-break: break-word; }

.ngd-badge { display: inline-block; padding: 3px 12px; border-radius: 100px; font-size: 12px; font-weight: 700; }
.ngd-badge-ai      { background: #ede9fe; color: #7c3aed; }
.ngd-badge-talent  { background: #fef3c7; color: #d97706; }

/* Status panel */
.ngd-status-panel { margin-bottom: 24px; }
.ngd-status-panel .current-badge {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 18px; border-radius: 8px; font-size: 14px; font-weight: 700;
    margin-bottom: 16px;
}

.ngd-radio-group label { display: flex; align-items: center; gap: 8px; padding: 8px 0; cursor: pointer; font-size: 14px; }
.ngd-radio-group input[type=radio] { width: 16px; height: 16px; cursor: pointer; }
textarea.ngd-notes { width: 100%; min-height: 90px; padding: 10px 12px; border: 1px solid #ced4da; border-radius: 8px; font-size: 13px; resize: vertical; box-sizing: border-box; }
.btn-ngd { display: inline-flex; align-items: center; gap: 6px; padding: 9px 22px; border: none; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; transition: opacity .15s; }
.btn-ngd:hover { opacity: .85; }
.btn-ngd:disabled { opacity: .5; cursor: not-allowed; }
.btn-ngd-save    { background: #4361ee; color: #fff; }
.btn-ngd-publish { background: #4caf50; color: #fff; }
.btn-ngd-unpub   { background: #ff9800; color: #fff; }
.btn-ngd-yt      { background: #ff0000; color: #fff; }
.btn-ngd-dl      { background: #198754; color: #fff; text-decoration: none; }
.btn-ngd-dl:hover { color: #fff; text-decoration: none; }

input.ngd-yt-input {
    width: 100%; padding: 9px 12px; border: 1px solid #ced4da; border-radius: 8px;
    font-size: 13px; box-sizing: border-box; margin-bottom: 10px;
}
.ngd-message { padding: 10px 14px; border-radius: 6px; font-size: 13px; margin-top: 10px; display: none; }
.ngd-message.ok  { background: #d4edda; color: #155724; display: block; }
.ngd-message.err { background: #f8d7da; color: #721c24; display: block; }

/* Video */
.ngd-video-box { background: #000; border-radius: 10px; overflow: hidden; text-align: center; }
.ngd-video-box video { max-width: 100%; max-height: 340px; display: block; }

/* Comments */
.ngd-comment-list { margin: 0; padding: 0; list-style: none; }
.ngd-comment-item { padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
.ngd-comment-item:last-child { border-bottom: none; }
.ngd-comment-author { font-size: 12px; font-weight: 700; color: #4361ee; }
.ngd-comment-text   { font-size: 13px; color: #444; margin-top: 3px; }
.ngd-comment-date   { font-size: 11px; color: #aaa; margin-top: 2px; }

.ngd-interest-item { padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
.ngd-interest-item:last-child { border-bottom: none; }
.ngd-interest-name { font-size: 14px; font-weight: 700; color: #198754; }
.ngd-interest-meta { font-size: 12px; color: #666; margin-top: 4px; }
.ngd-interest-note { font-size: 12px; color: #444; margin-top: 6px; }
.ngd-interest-actions { margin-top: 8px; }
.ngd-interest-actions a { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; background: #eef4ff; color: #4361ee; font-size: 12px; font-weight: 700; text-decoration: none; }
.ngd-interest-actions a:hover { text-decoration: none; background: #dfeaff; }
</style>

<div class="ngd-wrap">
    <a href="<?php echo $listUrl; ?>" class="ngd-back">&#8592; Back to Submissions</a>

    <div class="ngd-title"><?php echo htmlspecialchars($row['title'] ?? 'Submission #' . $subId, ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="ngd-subtitle">Submission ID #<?php echo $subId; ?> &bull; Submitted on <?php echo htmlspecialchars($row['created_at'] ?? '—', ENT_QUOTES); ?></div>

    <!-- Action panels row -->
    <div class="ngd-grid">

        <!-- Participant info -->
        <div class="ngd-panel">
            <h3>Participant Details</h3>
            <div class="ngd-field"><label>Full Name</label><div class="val"><?php echo htmlspecialchars($row['full_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div></div>
            <div class="ngd-field"><label>Platform Alias</label><div class="val"><?php echo htmlspecialchars($row['alias_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div></div>
            <div class="ngd-field"><label>Email</label><div class="val"><a href="mailto:<?php echo htmlspecialchars($row['email'] ?? '', ENT_QUOTES); ?>"><?php echo htmlspecialchars($row['email'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></a></div></div>
            <div class="ngd-field"><label>Phone</label><div class="val"><?php echo htmlspecialchars($row['phone'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div></div>
            <div class="ngd-field"><label>Country</label><div class="val"><?php echo htmlspecialchars($row['country'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div></div>
            <div class="ngd-field"><label>Age</label><div class="val"><?php echo htmlspecialchars($row['age'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div></div>
            <div class="ngd-field">
                <label>Stream</label>
                <div class="val">
                    <?php if (($row['stream'] ?? '') === 'AI'): ?>
                    <span class="ngd-badge ngd-badge-ai">🤖 AI Innovation</span>
                    <?php else: ?>
                    <span class="ngd-badge ngd-badge-talent">🎤 Performing Talent</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Submission info -->
        <div class="ngd-panel">
            <h3>Submission Info</h3>
            <div class="ngd-field"><label>Title</label><div class="val"><?php echo htmlspecialchars($row['title'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div></div>
            <div class="ngd-field"><label>Description</label><div class="val" style="white-space:pre-wrap;"><?php echo htmlspecialchars($row['description'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div></div>
            <div class="ngd-field"><label>Tags</label><div class="val"><?php echo htmlspecialchars($row['tags'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div></div>
            <div class="ngd-field">
                <label>IP / Copyright Agreed</label>
                <div class="val">
                    <?php if ($row['copyright_consent'] ?? false): ?>
                    <span style="color:#4caf50;font-weight:700;">✔ Yes, agreed</span>
                    <?php else: ?>
                    <span style="color:#f44336;">✘ Not confirmed</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="ngd-field">
                <label>YouTube Consent</label>
                <div class="val">
                    <?php if ($row['youtube_consent'] ?? false): ?>
                    <span style="color:#4caf50;font-weight:700;">✔ Yes</span>
                    <?php else: ?>
                    <span style="color:#f44336;">✘ No</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="ngd-field"><label>Social Stats</label><div class="val">👍 <?php echo $likes; ?> Likes &bull; 💬 <?php echo count($comments); ?> Comments</div></div>
        </div>
    </div>

    <!-- Review + Publish + YouTube row -->
    <div class="ngd-grid" style="grid-template-columns: 1.2fr 1fr 1fr;">

        <!-- Status management -->
        <div class="ngd-panel ngd-status-panel">
            <h3>Review &amp; Status</h3>
            <div class="current-badge" style="background:<?php echo $statusColors[$adminStatus] ?? '#ff9800'; ?>22;color:<?php echo $statusColors[$adminStatus] ?? '#ff9800'; ?>;">
                <span>Current: <?php echo $statusNames[$adminStatus] ?? '⏳ Pending'; ?></span>
            </div>
            <div class="ngd-radio-group">
                <label><input type="radio" name="ng_admin_status" value="0" <?php echo $adminStatus == 0 ? 'checked' : ''; ?>> ⏳ Pending Review</label>
                <label><input type="radio" name="ng_admin_status" value="1" <?php echo $adminStatus == 1 ? 'checked' : ''; ?>> ✅ Approve</label>
                <label><input type="radio" name="ng_admin_status" value="2" <?php echo $adminStatus == 2 ? 'checked' : ''; ?>> ❌ Reject</label>
            </div>
            <div style="margin:12px 0 8px;font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.05em;">Notes to participant (rejection reason)</div>
            <textarea class="ngd-notes" id="ng_admin_notes" placeholder="Optional note visible to participant when rejected..."><?php echo htmlspecialchars($row['admin_notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            <button class="btn-ngd btn-ngd-save" id="btn_save_status">Save Status</button>
            <div class="ngd-message" id="msg_status"></div>
        </div>

        <!-- Publish toggle -->
        <div class="ngd-panel">
            <h3>Public Feed</h3>
            <p style="font-size:13px;color:#555;margin:0 0 16px;">
                Control whether this submission appears in the public NextGen Challenge feed visible to everyone including universities.
            </p>
            <?php if ($published): ?>
            <div style="background:#d4edda;color:#155724;padding:10px 14px;border-radius:8px;font-weight:700;font-size:13px;margin-bottom:14px;">
                🌍 Currently LIVE on public feed
            </div>
            <button class="btn-ngd btn-ngd-unpub" id="btn_toggle_publish">⬇ Unpublish</button>
            <?php else: ?>
            <div style="background:#f8f9fa;color:#666;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px;">
                🔒 Not published — only visible to admins
            </div>
            <button class="btn-ngd btn-ngd-publish" id="btn_toggle_publish">🌍 Publish to Feed</button>
            <?php endif; ?>
            <div class="ngd-message" id="msg_publish"></div>
        </div>

        <!-- YouTube link -->
        <div class="ngd-panel">
            <h3>YouTube Link</h3>
            <?php if ($ytSentAt): ?>
            <div style="background:#d4edda;color:#155724;padding:8px 12px;border-radius:6px;font-size:12px;font-weight:700;margin-bottom:12px;">
                ✉ Link emailed to participant on <?php echo htmlspecialchars(substr($ytSentAt, 0, 10), ENT_QUOTES); ?>
            </div>
            <?php endif; ?>
            <p style="font-size:13px;color:#555;margin:0 0 10px;">After uploading to YouTube, paste the link here. The participant will be notified by email automatically.</p>
            <input type="url" class="ngd-yt-input" id="ng_yt_link" placeholder="https://www.youtube.com/watch?v=..." value="<?php echo htmlspecialchars($ytLink, ENT_QUOTES, 'UTF-8'); ?>" />
            <button class="btn-ngd btn-ngd-yt" id="btn_send_yt">📧 Save &amp; Email Participant</button>
            <div class="ngd-message" id="msg_yt"></div>
        </div>
    </div>

    <!-- Video player + Download -->
    <!-- Video / Drive link -->
    <?php
    $isGDriveUrl = !empty($videoPath) && preg_match('/^https?:\/\//i', $videoPath);
    $isLocalFile = !empty($videoPath) && !$isGDriveUrl;
    ?>
    <?php if ($isGDriveUrl): ?>
    <div class="ngd-panel" style="margin-bottom:24px;">
        <h3>Submitted Video (Google Drive)</h3>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
            <a href="<?php echo htmlspecialchars($videoPath, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn-ngd" style="background:#1a73e8;color:#fff;gap:8px;">
                <svg style="width:18px;height:16px;flex-shrink:0;vertical-align:middle;" viewBox="0 0 87.3 78" xmlns="http://www.w3.org/2000/svg"><path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8H0c0 1.55.4 3.1 1.2 4.5z" fill="#0066da"/><path d="M43.65 25 29.9 1.2C28.55 2 27.4 3.1 26.6 4.5L1.2 48.5C.4 49.9 0 51.45 0 53h27.5z" fill="#00ac47"/><path d="M73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5H59.8l5.85 11.5z" fill="#ea4335"/><path d="M43.65 25 57.4 1.2C56.05.4 54.5 0 52.95 0H34.35c-1.55 0-3.1.45-4.45 1.2z" fill="#00832d"/><path d="M59.8 53H27.5L13.75 76.8c1.35.8 2.9 1.2 4.45 1.2h50.9c1.55 0 3.1-.4 4.45-1.2z" fill="#2684fc"/><path d="M73.4 26.5c-.8-1.4-1.95-2.5-3.3-3.3L56.3 0H43.65L59.8 53l27.5.5c0-1.55-.4-3.1-1.2-4.5z" fill="#ffba00"/></svg>
                Open in Google Drive
            </a>
            <span style="font-size:12px;color:#666;word-break:break-all;"><?php echo htmlspecialchars($videoPath, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <p style="font-size:12px;color:#888;margin:12px 0 0;">Review the video on Drive, then upload it to YouTube and save the link in the YouTube panel below. The participant will be notified automatically.</p>
    </div>
    <?php elseif ($isLocalFile): ?>
    <div class="ngd-panel" style="margin-bottom:24px;">
        <h3>Submitted Video (Legacy Local File)</h3>
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:14px;flex-wrap:wrap;">
            <span style="font-size:13px;color:#666;">File: <?php echo htmlspecialchars(basename($videoPath), ENT_QUOTES, 'UTF-8'); ?></span>
            <a href="<?php echo url('admin/nextgen_challenge/download_video/' . $subId); ?>" class="btn-ngd btn-ngd-dl">⬇ Download Video</a>
        </div>
        <div class="ngd-video-box">
            <video controls preload="metadata">
                <source src="<?php echo url('admin/nextgen_challenge/media/' . $subId); ?>" type="video/mp4">
                Your browser does not support the video element.
            </video>
        </div>
    </div>
    <?php else: ?>
    <div class="ngd-panel" style="margin-bottom:24px;text-align:center;padding:40px;">
        <div style="font-size:36px;margin-bottom:8px;">🎬</div>
        <p style="color:#666;font-size:14px;">No video link submitted yet.</p>
    </div>
    <?php endif; ?>

    <?php if (!empty($interests)): ?>
    <div class="ngd-panel" style="margin-bottom:24px;">
        <h3>Interested Institutions (<?php echo count($interests); ?>)</h3>
        <?php foreach ($interests as $interest): ?>
        <?php $interestEmail = $interest['contact_email'] ?? $interest['member_email'] ?? ''; ?>
        <div class="ngd-interest-item">
            <div class="ngd-interest-name"><?php echo htmlspecialchars($interest['institution_name'] ?? $interest['alias_name'] ?? 'Education Institution', ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="ngd-interest-meta">
                Contact:
                <?php if (!empty($interestEmail)): ?>
                <a href="mailto:<?php echo htmlspecialchars($interestEmail, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($interestEmail, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php else: ?>
                —
                <?php endif; ?>
                &bull; Sent: <?php echo htmlspecialchars(substr((string)($interest['created_at'] ?? ''), 0, 16), ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <?php if (!empty($interest['message'])): ?>
            <div class="ngd-interest-note"><?php echo htmlspecialchars($interest['message'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if (!empty($interestEmail)): ?>
            <div class="ngd-interest-actions">
                <a href="mailto:<?php echo htmlspecialchars($interestEmail, ENT_QUOTES, 'UTF-8'); ?>?subject=<?php echo rawurlencode('NextGen scholarship discussion for ' . ($row['title'] ?? ('submission #' . $subId))); ?>"><i class="fa fa-envelope"></i> Contact Institution</a>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Comments -->
    <?php if (!empty($comments)): ?>
    <div class="ngd-panel" style="margin-bottom:24px;">
        <h3>Comments (<?php echo count($comments); ?>)</h3>
        <ul class="ngd-comment-list">
            <?php foreach ($comments as $c): ?>
            <li class="ngd-comment-item">
                <div class="ngd-comment-author"><?php echo htmlspecialchars($c['commenter_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="ngd-comment-text"><?php echo htmlspecialchars($c['content'] ?? $c['comment'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="ngd-comment-date"><?php echo htmlspecialchars($c['created_at'] ?? '', ENT_QUOTES); ?></div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

</div>

<script>
(function() {
    var token    = typeof _token !== 'undefined' ? _token : '';
    var baseUrl  = typeof _page_base_url !== 'undefined' ? _page_base_url : '';
    var subId    = <?php echo $subId; ?>;
    var isPublished = <?php echo $published ? 'true' : 'false'; ?>;

    function showMsg(el, type, text) {
        el.className = 'ngd-message ' + type;
        el.textContent = text;
        if (type === 'ok') setTimeout(function(){ el.style.display = 'none'; }, 4000);
    }

    function ajax(url, data, cb) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', token);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try { cb(null, JSON.parse(xhr.responseText)); }
                catch(e) { cb('Parse error'); }
            }
        };
        xhr.send(JSON.stringify(data));
    }

    // Save Status
    document.getElementById('btn_save_status').addEventListener('click', function() {
        var self    = this;
        var msgEl   = document.getElementById('msg_status');
        var checked = document.querySelector('input[name="ng_admin_status"]:checked');
        if (!checked) { showMsg(msgEl, 'err', 'Please select a status.'); return; }
        var adminStatus = parseInt(checked.value);
        var notes       = document.getElementById('ng_admin_notes').value.trim();
        self.disabled = true;
        self.textContent = 'Saving...';
        ajax(baseUrl + '/nextgen_challenge/update_status', {
            submission_id: subId, admin_status: adminStatus, admin_notes: notes, _token: token
        }, function(err, res) {
            self.disabled = false;
            self.textContent = 'Save Status';
            if (err || !res || res.status !== 'ok') {
                showMsg(msgEl, 'err', (res && res.message) || 'Error saving status.');
            } else {
                showMsg(msgEl, 'ok', '✔ Status updated successfully.');
                setTimeout(function(){ location.reload(); }, 1500);
            }
        });
    });

    // Toggle Publish
    document.getElementById('btn_toggle_publish').addEventListener('click', function() {
        var self  = this;
        var msgEl = document.getElementById('msg_publish');
        var nextPublished = isPublished ? 0 : 1;
        self.disabled = true;
        self.textContent = 'Updating...';
        ajax(baseUrl + '/nextgen_challenge/toggle_publish', {
            submission_id: subId, published: nextPublished, _token: token
        }, function(err, res) {
            self.disabled = false;
            if (err || !res || res.status !== 'ok') {
                self.textContent = 'Error — retry';
                showMsg(msgEl, 'err', (res && res.message) || 'Error toggling publish state.');
            } else {
                showMsg(msgEl, 'ok', '✔ ' + (res.published ? 'Now LIVE on public feed!' : 'Removed from public feed.'));
                setTimeout(function(){ location.reload(); }, 1500);
            }
        });
    });

    // Send YouTube link
    document.getElementById('btn_send_yt').addEventListener('click', function() {
        var self   = this;
        var msgEl  = document.getElementById('msg_yt');
        var yt     = document.getElementById('ng_yt_link').value.trim();
        if (!yt) { showMsg(msgEl, 'err', 'Please enter a YouTube URL.'); return; }
        if (!/^https?:\/\//.test(yt)) { showMsg(msgEl, 'err', 'Please enter a valid URL (https://...).'); return; }
        self.disabled = true;
        self.textContent = 'Sending...';
        ajax(baseUrl + '/nextgen_challenge/send_youtube_link', {
            submission_id: subId, youtube_link: yt, _token: token
        }, function(err, res) {
            self.disabled = false;
            self.textContent = '📧 Save & Email Participant';
            if (err || !res || res.status !== 'ok') {
                showMsg(msgEl, 'err', (res && res.message) || 'Error sending YouTube link.');
            } else {
                showMsg(msgEl, 'ok', '✔ YouTube link saved and email sent to participant!');
                setTimeout(function(){ location.reload(); }, 2000);
            }
        });
    });
})();
</script>
@endsection
