@extends('web.common')
@section('content')
<?php
$_agents = $_page_data['agents'] ?? [];
$_grants = $_page_data['grants'] ?? [];
?>
<div class="inner-panel">
    <h1 class="title">Education Agent Management</h1>
    <p class="subtitle" style="color:#666; margin-bottom:24px;">Manage education institution agent accounts and grant pre-built accounts to new schools.</p>
    <div class="underline"></div>
    <div class="clearboth"></div>

    {{-- Flash message --}}
    <div id="edu-admin-msg" style="display:none; margin:16px 0; padding:12px 16px; border-radius:6px; font-size:0.95em;"></div>

    {{-- ============================================================ --}}
    {{-- Section 0: Create Full Profile from URL (AI-powered) --}}
    {{-- ============================================================ --}}
    <div class="card" style="margin-top:28px; padding:24px; border:2px solid #1a5ca8;">
        <h2 style="font-size:1.05em; margin-bottom:6px; color:#1a5ca8;"><i class="fa fa-magic"></i> &nbsp;Create Institution Profile from URL <span style="font-size:0.72em; background:#dbeafe; color:#1d4ed8; padding:2px 8px; border-radius:20px; margin-left:6px; font-weight:600;">AI-Powered</span></h2>
        <p style="color:#555; font-size:0.9em; margin-bottom:20px;">
            Enter the institution's website URL. The AI will automatically research and fill the full profile (name, summary, programs, fees, admission, curriculum, boarding, scholarships, and more).
            A stub account and claim link will also be created so the institution can take over the profile later.
        </p>
        <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
            <div style="flex:3; min-width:240px;">
                <label style="font-size:0.85em; color:#333; display:block; margin-bottom:4px;">Institution Website URL *</label>
                <input type="url" id="from-url-input" class="profile-input" style="width:100%;" placeholder="https://www.universityname.edu.au">
            </div>
            <div style="flex:1; min-width:180px;">
                <label style="font-size:0.85em; color:#333; display:block; margin-bottom:4px;">Company Name (optional — AI will detect)</label>
                <input type="text" id="from-url-name" class="profile-input" style="width:100%;" placeholder="Auto-detected from URL">
            </div>
            <div>
                <button type="button" id="from-url-btn" class="btn-primary" onclick="createFromUrl()" style="background:#1a5ca8; min-width:160px;">
                    <i class="fa fa-magic"></i> Build Profile
                </button>
            </div>
        </div>

        {{-- Progress indicator --}}
        <div id="from-url-progress" style="display:none; margin-top:18px; padding:14px 16px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <div class="edu-spinner" style="width:18px;height:18px;border:3px solid #bfdbfe;border-top-color:#1a5ca8;border-radius:50%;animation:edu-spin 0.8s linear infinite;flex-shrink:0;"></div>
                <span id="from-url-progress-text" style="color:#1d4ed8; font-size:0.9em; font-weight:500;">Initialising AI research...</span>
            </div>
            <p style="color:#64748b; font-size:0.82em; margin:0;">This typically takes 2–4 minutes. The AI is performing multiple live web searches.</p>
        </div>

        {{-- Result box --}}
        <div id="from-url-result" style="display:none; margin-top:18px; padding:16px; background:#f0fdf4; border:1px solid #86efac; border-radius:8px;">
            <p style="font-size:0.88em; color:#16a34a; margin:0 0 10px; font-weight:600;"><i class="fa fa-check-circle"></i> Profile created successfully!</p>
            <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:10px;">
                <a id="from-url-view-link" href="#" target="_blank" class="btn-outline-sm" style="background:#dcfce7; border-color:#86efac; color:#15803d;"><i class="fa fa-eye"></i> View Profile</a>
                <a id="from-url-edit-link" href="#" class="btn-outline-sm"><i class="fa fa-pencil"></i> Edit Profile</a>
            </div>
            <div style="margin-top:8px;">
                <label style="font-size:0.8em; color:#555; display:block; margin-bottom:4px;">Claim link for institution:</label>
                <div style="display:flex; gap:8px;">
                    <input type="text" id="from-url-claim-val" readonly style="flex:1; padding:8px 12px; border:1px solid #ccc; border-radius:6px; font-size:0.88em; background:#fff;">
                    <button type="button" class="btn-outline-sm" onclick="copyClaimUrlFromUrl()">Copy</button>
                </div>
            </div>
        </div>

        <style>@keyframes edu-spin{to{transform:rotate(360deg)}}</style>
    </div>
    <div class="card" style="margin-top:28px; padding:24px;">
        <h2 style="font-size:1.05em; margin-bottom:16px; color:#1a1a2e;">Create Pre-built Account for School</h2>
        <p style="color:#555; font-size:0.9em; margin-bottom:20px;">
            Creates a dormant education agent account. You will receive a unique claim link to share with the school.
            The account will only become active once the school sets their email and password.
        </p>
        <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
            <div style="flex:1; min-width:200px;">
                <label style="font-size:0.85em; color:#333; display:block; margin-bottom:4px;">Institution / Company Name *</label>
                <input type="text" id="new-company-name" class="profile-input" style="width:100%;" placeholder="e.g. University of Melbourne">
            </div>
            <div style="flex:2; min-width:260px;">
                <label style="font-size:0.85em; color:#333; display:block; margin-bottom:4px;">Notes (optional)</label>
                <input type="text" id="new-notes" class="profile-input" style="width:100%;" placeholder="e.g. Sent via email on 2026-01-15">
            </div>
            <div>
                <button type="button" class="btn-primary" onclick="createAccount()">Create & Get Link</button>
            </div>
        </div>

        {{-- Claim URL result box --}}
        <div id="claim-url-box" style="display:none; margin-top:20px; background:#f0fdf4; border:1px solid #86efac; border-radius:8px; padding:16px;">
            <p style="font-size:0.85em; color:#16a34a; margin:0 0 8px;">Account created! Share this claim link with the school:</p>
            <div style="display:flex; gap:8px; align-items:center;">
                <input type="text" id="claim-url-value" readonly style="flex:1; padding:8px 12px; border:1px solid #ccc; border-radius:6px; font-size:0.9em; background:#fff;">
                <button type="button" class="btn-outline-sm" onclick="copyClaimUrl()">Copy</button>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- Section 2: Pending Grants --}}
    {{-- ============================================================ --}}
    <div class="card" style="margin-top:24px; padding:24px;">
        <h2 style="font-size:1.05em; margin-bottom:16px; color:#1a1a2e;">Pending Grant Links</h2>
        @if(empty($_grants))
            <p style="color:#888; font-size:0.9em;">No pending grants at this time.</p>
        @else
        <div style="overflow-x:auto;">
            <table class="data-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Notes</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th style="width:220px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($_grants as $g)
                    <tr id="grant-row-{{ $g['grant_id'] }}">
                        <td><?php echo htmlspecialchars($g['company_name'] ?? $g['full_name'] ?? '-', ENT_QUOTES); ?></td>
                        <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo htmlspecialchars($g['notes'] ?? '', ENT_QUOTES); ?>">
                            <?php echo htmlspecialchars($g['notes'] ?? '-', ENT_QUOTES); ?>
                        </td>
                        <td>
                            @if((int)$g['grant_status'] === 1)
                                <span style="color:mediumaquamarine; font-weight:600;">Claimed</span>
                            @else
                                <span style="color:#f59e0b; font-weight:600;">Pending</span>
                            @endif
                        </td>
                        <td><?php echo htmlspecialchars(substr($g['grant_created_at'] ?? '', 0, 10), ENT_QUOTES); ?></td>
                        <td style="display:flex; gap:8px; flex-wrap:wrap;">
                            @if((int)$g['grant_status'] === 0)
                            <button class="btn-outline-sm" onclick="showGrantUrl({{ $g['grant_id'] }})">Get Link</button>
                            <button class="btn-outline-sm" style="color:#e53e3e;" onclick="revokeGrant({{ $g['grant_id'] }}, this)">Revoke</button>
                            @endif
                            @if(!empty($g['member_id']))
                            <a href="{{ url('/en/Admin_Edu_Agents/edit_profile/' . $g['member_id']) }}" class="btn-outline-sm">Edit Profile</a>
                            <a href="{{ url('/en/Admin_Edu_Agents/access_full/' . $g['member_id']) }}" class="btn-outline-sm" style="background:#e0f2fe; border-color:#7dd3fc; color:#075985;">Access Full Account</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ============================================================ --}}
    {{-- Section 3: Existing Education Agents --}}
    {{-- ============================================================ --}}
    <div class="card" style="margin-top:24px; padding:24px; margin-bottom:40px;">
        <h2 style="font-size:1.05em; margin-bottom:16px; color:#1a1a2e;">Education Agent Accounts</h2>
        @if(empty($_agents))
            <p style="color:#888; font-size:0.9em;">No education agent accounts found.</p>
        @else
        <div style="overflow-x:auto;">
            <table class="data-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Verified</th>
                        <th>Joined</th>
                        <th style="width:220px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($_agents as $a)
                    <tr>
                        <td><?php echo htmlspecialchars($a['company_name'] ?? '-', ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars($a['full_name'] ?? '-', ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars($a['email'] ?? '-', ENT_QUOTES); ?></td>
                        <td>
                            @if((int)$a['status'] === 1)
                                <span style="color:mediumaquamarine;">Active</span>
                            @else
                                <span style="color:#aaa;">Inactive</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if((int)$a['verified'] === 1)
                                <i class="fa fa-check-circle" style="color:mediumaquamarine;"></i>
                            @else
                                <i class="fa fa-ban" style="color:lightpink;"></i>
                            @endif
                        </td>
                        <td><?php echo htmlspecialchars(substr($a['created_at'] ?? '', 0, 10), ENT_QUOTES); ?></td>
                        <td style="display:flex; gap:8px; flex-wrap:wrap;">
                            <a href="{{ url('/en/Admin_Edu_Agents/edit_profile/' . $a['id']) }}" class="btn-outline-sm">Edit Profile</a>
                            <a href="{{ url('/en/Admin_Edu_Agents/access_full/' . $a['id']) }}" class="btn-outline-sm" style="background:#e0f2fe; border-color:#7dd3fc; color:#075985;">Access Full Account</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<script>
var _csrf = '{{ csrf_token() }}';

function showMsg(msg, ok) {
    var el = document.getElementById('edu-admin-msg');
    el.textContent = msg;
    el.style.display = 'block';
    el.style.background = ok ? '#f0fdf4' : '#fff1f2';
    el.style.color = ok ? '#16a34a' : '#dc2626';
    el.style.border = '1px solid ' + (ok ? '#86efac' : '#fca5a5');
    setTimeout(function(){ el.style.display='none'; }, 6000);
}

function createAccount() {
    var name  = document.getElementById('new-company-name').value.trim();
    var notes = document.getElementById('new-notes').value.trim();
    if (!name) { showMsg('Company name is required.', false); return; }

    fetch('/en/Admin_Edu_Agents/create_account', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _csrf },
        body: JSON.stringify({ company_name: name, notes: notes })
    })
    .then(r => r.json())
    .then(function(d) {
        if (d.status === 200) {
            document.getElementById('claim-url-value').value = d.claim_url;
            document.getElementById('claim-url-box').style.display = 'block';
            document.getElementById('new-company-name').value = '';
            document.getElementById('new-notes').value = '';
            showMsg('Account created successfully!', true);
            setTimeout(function(){ location.reload(); }, 3000);
        } else {
            showMsg(d.message || 'Failed to create account.', false);
        }
    })
    .catch(function(){ showMsg('Network error. Please try again.', false); });
}

function copyClaimUrl() {
    var val = document.getElementById('claim-url-value').value;
    navigator.clipboard.writeText(val).then(function(){
        showMsg('Claim link copied to clipboard!', true);
    });
}

function copyClaimUrlFromUrl() {
    var val = document.getElementById('from-url-claim-val').value;
    navigator.clipboard.writeText(val).then(function(){
        showMsg('Claim link copied!', true);
    });
}

function createFromUrl() {
    var url  = document.getElementById('from-url-input').value.trim();
    var name = document.getElementById('from-url-name').value.trim();
    if (!url) { showMsg('Website URL is required.', false); return; }

    var btn  = document.getElementById('from-url-btn');
    var prog = document.getElementById('from-url-progress');
    var res  = document.getElementById('from-url-result');
    btn.disabled = true;
    prog.style.display = 'block';
    res.style.display  = 'none';

    // Animate progress text
    var steps = [
        'Visiting institution website…',
        'Researching programs and qualifications…',
        'Extracting fees and admission requirements…',
        'Gathering structured details (boarding, curriculum, etc.)…',
        'Building profile — almost done…',
    ];
    var stepIdx = 0;
    var stepTimer = setInterval(function() {
        stepIdx = (stepIdx + 1) % steps.length;
        var el = document.getElementById('from-url-progress-text');
        if (el) el.textContent = steps[stepIdx];
    }, 30000);

    fetch('/en/Admin_Edu_Agents/create_from_url', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _csrf },
        body: JSON.stringify({ website_url: url, company_name: name })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        clearInterval(stepTimer);
        prog.style.display = 'none';
        btn.disabled = false;
        if (d.status === 200) {
            document.getElementById('from-url-claim-val').value = d.claim_url || '';
            var viewLink = document.getElementById('from-url-view-link');
            var editLink = document.getElementById('from-url-edit-link');
            if (viewLink) viewLink.href = d.profile_url || '#';
            if (editLink && d.member_id) editLink.href = '/en/Admin_Edu_Agents/edit_profile/' + d.member_id;
            res.style.display = 'block';
            document.getElementById('from-url-input').value = '';
            document.getElementById('from-url-name').value  = '';
            showMsg('Profile for "' + (d.institute_name || 'Institution') + '" created!', true);
        } else if (d.status === 409) {
            showMsg('A profile for this URL already exists (ID: ' + d.profile_id + ').', false);
            if (d.profile_url) window.open(d.profile_url, '_blank');
        } else {
            showMsg(d.message || 'Failed to create profile.', false);
        }
    })
    .catch(function(err) {
        clearInterval(stepTimer);
        prog.style.display = 'none';
        btn.disabled = false;
        showMsg('Network error. Please try again.', false);
    });
}

function showGrantUrl(grantId) {
    fetch('/en/Admin_Edu_Agents/get_claim_url/' + grantId, {
        headers: { 'X-CSRF-TOKEN': _csrf }
    })
    .then(r => r.json())
    .then(function(d) {
        if (d.status === 200) {
            prompt('Claim link for grant #' + grantId + ':', d.claim_url);
        } else {
            showMsg(d.message || 'Could not retrieve link.', false);
        }
    });
}

function revokeGrant(grantId, btn) {
    if (!confirm('Are you sure you want to revoke this grant and delete the unclaimed account?')) return;
    btn.disabled = true;

    fetch('/en/Admin_Edu_Agents/revoke_grant', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _csrf },
        body: JSON.stringify({ grant_id: grantId })
    })
    .then(r => r.json())
    .then(function(d) {
        if (d.status === 200) {
            var row = document.getElementById('grant-row-' + grantId);
            if (row) row.remove();
            showMsg('Grant revoked.', true);
        } else {
            showMsg(d.message || 'Failed to revoke.', false);
            btn.disabled = false;
        }
    })
    .catch(function(){ showMsg('Network error.', false); btn.disabled = false; });
}
</script>
@endsection
