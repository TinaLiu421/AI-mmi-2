@extends('web.common')
@section('title', 'College Match')
@section('content')
<?php
$_prefs        = $_page_data['prefs'] ?? null;
$_is_logged_in = !empty($_current_member);
$_save_url     = $_page_base_url . '/study_college_match/save_preferences';
$_match_url    = $_page_base_url . '/study_college_match/find_matches';
$_csrf         = csrf_token();

$_pref_labels = [
    'country'    => 'Country',
    'city'       => 'City',
    'university' => 'University / College',
    'level'      => 'Level',
    'fields'     => 'Fields of study',
    'budget'     => 'Total budget (USD)',
    'year'       => 'Year of enrolment',
];
$_level_opts = ['','Bachelor','Master','PhD','Diploma','Certificate','Associate Degree','Foundation','Other'];
?>
<style>
.cm-wrap{display:flex;align-items:flex-start;gap:0;max-width:1100px;margin:0 auto;padding:0 0 100px;min-height:80vh;}
.cm-sidebar{width:110px;flex-shrink:0;padding:30px 0;display:flex;flex-direction:column;align-items:center;gap:28px;}
.cm-sidebar a{display:flex;flex-direction:column;align-items:center;gap:6px;text-decoration:none;color:#333;font-size:12px;font-weight:500;text-align:center;line-height:1.3;}
.cm-sidebar a .cm-sb-icon{width:56px;height:56px;border-radius:50%;background:#e8eaf0;display:flex;align-items:center;justify-content:center;font-size:22px;transition:background .2s;}
.cm-sidebar a.active .cm-sb-icon,.cm-sidebar a:hover .cm-sb-icon{background:#012169;color:#fff;}
.cm-sidebar a.active{color:#012169;font-weight:700;}
.cm-main{flex:1;padding:30px 20px 0;min-width:0;}
.cm-page-title{font-size:28px;font-weight:700;margin:0 0 24px;text-align:center;}
.cm-banner{background:#4a7ec7;border-radius:10px;padding:36px 28px;color:#fff;font-size:18px;font-weight:700;text-align:center;margin-bottom:24px;min-height:90px;display:flex;align-items:center;justify-content:center;}
.cm-prefs-header{display:flex;align-items:center;gap:10px;margin:0 0 10px;}
.cm-prefs-header h2{font-size:20px;font-weight:700;margin:0;}
.cm-prefs-table{width:100%;border-collapse:collapse;font-size:14px;margin-bottom:12px;}
.cm-prefs-table th,.cm-prefs-table td{border:1px solid #ddd;padding:7px 10px;text-align:center;vertical-align:middle;}
.cm-prefs-table th{background:#f5f5f5;font-weight:600;font-size:13px;}
.cm-prefs-table td:first-child{text-align:left;font-weight:500;background:#fafafa;width:160px;}
.cm-prefs-table input[type=text]{width:100%;border:1px solid #bbb;border-radius:4px;padding:5px 7px;font-size:13px;box-sizing:border-box;}
.cm-prefs-table input[type=text]:focus{outline:none;border-color:#012169;}
.cm-prefs-table select{width:100%;border:1px solid #bbb;border-radius:4px;padding:5px 4px;font-size:13px;background:#fff;box-sizing:border-box;}
.cm-prefs-table select:focus{outline:none;border-color:#012169;}
.cm-action-bar{display:flex;align-items:center;gap:12px;margin:12px 0 28px;flex-wrap:wrap;}
.cm-save-btn{background:#555;color:#fff;border:none;border-radius:6px;padding:9px 22px;font-size:14px;font-weight:600;cursor:pointer;}
.cm-save-btn:hover{background:#333;}
.cm-match-btn{background:#012169;color:#fff;border:none;border-radius:6px;padding:9px 28px;font-size:14px;font-weight:600;cursor:pointer;}
.cm-match-btn:hover{background:#023a9e;}
.cm-match-btn:disabled{background:#aaa;cursor:not-allowed;}
.cm-action-msg{font-size:13px;color:#028a0f;}
.cm-error-msg{font-size:13px;color:#c00;}
.cm-section-title{font-size:20px;font-weight:700;margin:28px 0 14px;}
.cm-loading{text-align:center;padding:40px 20px;color:#555;font-size:14px;display:none;}
.cm-spinner{display:inline-block;width:28px;height:28px;border:3px solid #ddd;border-top-color:#012169;border-radius:50%;animation:cm-spin .8s linear infinite;vertical-align:middle;margin-right:8px;}
@keyframes cm-spin{to{transform:rotate(360deg);}}
.cm-empty-state{text-align:center;padding:32px 20px;color:#888;font-size:14px;border:1px dashed #ccc;border-radius:8px;}
.cm-match-table{width:100%;border-collapse:collapse;font-size:14px;}
.cm-match-table th{background:#f0f0f0;font-weight:700;border-bottom:2px solid #ddd;padding:10px 12px;text-align:left;font-size:13px;}
.cm-match-table td{padding:10px 12px;vertical-align:top;border-bottom:1px solid #eee;}
.cm-inst-cell{width:130px;vertical-align:top;}
.cm-inst-logo-div{width:80px;height:64px;background-size:contain;background-repeat:no-repeat;background-position:center;background-color:#fff;border-radius:4px;margin-bottom:4px;}
.cm-inst-name{font-size:12px;color:#333;font-weight:700;line-height:1.3;margin-bottom:4px;}
.cm-progs-cell{vertical-align:top;padding:0!important;}
.cm-prog-table{width:100%;border-collapse:collapse;font-size:13px;}
.cm-prog-table thead tr{background:#f7f7f7;}
.cm-prog-table th{padding:6px 10px;font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #e8e8e8;white-space:nowrap;}
.cm-prog-table td{padding:7px 10px;border-bottom:1px solid #f0f0f0;vertical-align:middle;}
.cm-prog-table tbody tr:last-child td{border-bottom:none;}
.cm-prog-name{font-weight:600;color:#222;}
.cm-prog-adm{color:#555;min-width:130px;}
.cm-prog-fee{color:#012169;font-weight:600;white-space:nowrap;}
.cm-prog-name-cell{font-size:13px;font-weight:600;color:#222;vertical-align:middle;}
.cm-prog-adm-cell{font-size:12px;color:#555;vertical-align:middle;}
.cm-prog-fee-cell{font-size:13px;color:#012169;font-weight:600;white-space:nowrap;vertical-align:middle;}
.cm-view-cell{vertical-align:middle;text-align:center;}
.cm-also-logo{width:56px;height:44px;object-fit:contain;display:block;margin-bottom:5px;border-radius:4px;background:#fff;}
.cm-inst-gap td{padding:6px 0!important;border-bottom:2px solid #e0e0e0!important;background:#f9f9f9;}
.cm-first-prog-row td{border-top:none;}
.cm-match-reason{font-size:12px;color:#444;margin-top:5px;line-height:1.5;}
.cm-score-badge{display:inline-block;background:#012169;color:#fff;font-size:11px;font-weight:700;padding:2px 7px;border-radius:10px;margin-bottom:4px;}
.cm-view-btn{display:inline-block;background:#012169;color:#fff;font-size:12px;font-weight:600;padding:5px 12px;border-radius:5px;text-decoration:none;white-space:nowrap;}
.cm-view-btn:hover{background:#023a9e;color:#fff;}
.cm-ext-link{display:inline-block;background:#e0e0e0;color:#333;font-size:12px;font-weight:600;padding:5px 12px;border-radius:5px;text-decoration:none;white-space:nowrap;}
.cm-ext-link:hover{background:#ccc;}
.cm-bottom-bar{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid #ddd;display:flex;align-items:center;justify-content:center;gap:16px;padding:14px 24px;z-index:100;}
.cm-bottom-bar a,.cm-bottom-bar button{display:inline-block;padding:11px 32px;border-radius:6px;font-size:15px;font-weight:600;text-decoration:none;cursor:pointer;border:none;}
.cm-btn-chat{background:#f0f0f0;color:#333;}
.cm-btn-chat:hover{background:#e0e0e0;}
.cm-btn-apply{background:#012169;color:#fff;}
.cm-btn-apply:hover{background:#023a9e;}
@media(max-width:640px){
    .cm-sidebar{width:70px;gap:18px;}
    .cm-sidebar a .cm-sb-icon{width:42px;height:42px;font-size:17px;}
    .cm-sidebar a{font-size:10px;}
    .cm-prefs-table td:first-child{width:90px;font-size:12px;}
}
</style>

<div class="cm-wrap">
    <div class="cm-sidebar">
        <a href="<?php echo $_page_base_url.'/study'; ?>">
            <span class="cm-sb-icon">&#127891;</span>
            Study
        </a>
        <a href="<?php echo $_page_base_url.'/study_college_match'; ?>" class="active">
            <span class="cm-sb-icon">&#127959;</span>
            Matches
        </a>
        <a href="<?php echo $_page_base_url.'/agent_chat'; ?>">
            <span class="cm-sb-icon">&#128172;</span>
            Chat with AI-mmi
        </a>
    </div>

    <div class="cm-main">
        <h1 class="cm-page-title">Study Applications</h1>

        <div class="cm-banner">
            Fill in your study preferences below, then click <strong>Find My Match</strong> to get AI-powered university recommendations.
        </div>

        <div class="cm-prefs-header">
            <h2>My Study Preference</h2>
        </div>

        <form id="cm-prefs-form">
        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($_csrf, ENT_QUOTES); ?>">
        <table class="cm-prefs-table">
            <thead>
                <tr>
                    <th></th>
                    <th>1st Choice</th>
                    <th>2nd Choice</th>
                    <th>3rd Choice</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($_pref_labels as $key => $label): ?>
            <tr>
                <td><?php echo htmlspecialchars($label, ENT_QUOTES); ?></td>
                <?php for($c = 1; $c <= 3; $c++):
                    $fkey = 'choice_'.$c.'_'.$key;
                    $val  = htmlspecialchars($_prefs[$fkey] ?? '', ENT_QUOTES);
                ?>
                <td>
                    <?php if($key === 'level'): ?>
                    <select name="<?php echo $fkey; ?>">
                        <?php foreach($_level_opts as $opt):
                            $optLabel = ($opt === '') ? 'Select level' : htmlspecialchars($opt, ENT_QUOTES);
                            $optVal   = htmlspecialchars($opt, ENT_QUOTES);
                            $sel      = ($val === $optVal) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $optVal; ?>" <?php echo $sel; ?>><?php echo $optLabel; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php else: ?>
                    <input type="text" name="<?php echo $fkey; ?>" value="<?php echo $val; ?>"
                           placeholder="<?php echo htmlspecialchars($key === 'budget' ? 'e.g. 30000' : ($key === 'year' ? 'e.g. 2025' : 'Enter...'), ENT_QUOTES); ?>">
                    <?php endif; ?>
                </td>
                <?php endfor; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if($_is_logged_in): ?>
        <div class="cm-action-bar">
            <button type="button" class="cm-save-btn" id="cm-save-btn">Save Preferences</button>
            <button type="button" class="cm-match-btn" id="cm-match-btn">Find My Match</button>
            <span class="cm-action-msg" id="cm-action-msg" style="display:none;"></span>
            <span class="cm-error-msg" id="cm-error-msg" style="display:none;"></span>
        </div>
        <?php else: ?>
        <p style="color:#888;font-size:13px;margin:8px 0 24px;">Please <a href="<?php echo $_page_base_url.'/login'; ?>">log in</a> to save preferences and find matches.</p>
        <?php endif; ?>
        </form>

        <div class="cm-loading" id="cm-loading">
            <span class="cm-spinner"></span> Finding your best matches...
        </div>

        <div id="cm-results-wrap" style="display:none;">
            <div class="cm-section-title">Top Matches</div>
            <div id="cm-top-matches-block"></div>
            <div class="cm-section-title" id="cm-also-title" style="display:none;">Also for Considerations</div>
            <div id="cm-also-block"></div>
        </div>

    </div>
</div>

<div class="cm-bottom-bar">
    <a class="cm-btn-chat" href="<?php echo $_page_base_url.'/agent_chat'; ?>">Chat with AI-mmi</a>
    <button class="cm-btn-apply" onclick="alert('Application feature coming soon!')">Apply</button>
</div>

<script>
var _cm_csrf = <?php echo json_encode(csrf_token()); ?>;
(function(){
    var saveBtn     = document.getElementById('cm-save-btn');
    var matchBtn    = document.getElementById('cm-match-btn');
    var actionMsg   = document.getElementById('cm-action-msg');
    var errMsg      = document.getElementById('cm-error-msg');
    var loadingDiv  = document.getElementById('cm-loading');
    var resultsWrap = document.getElementById('cm-results-wrap');
    var topBlock    = document.getElementById('cm-top-matches-block');
    var alsoBlock   = document.getElementById('cm-also-block');
    var alsoTitle   = document.getElementById('cm-also-title');
    var form        = document.getElementById('cm-prefs-form');
    var saveUrl     = <?php echo json_encode($_save_url); ?>;
    var matchUrl    = <?php echo json_encode($_match_url); ?>;
    var baseUrl     = <?php echo json_encode(rtrim($_page_base_url, '/')); ?>;

    function showMsg(el, txt, dur) {
        el.textContent = txt;
        el.style.display = 'inline';
        if (dur) { setTimeout(function(){ el.style.display = 'none'; }, dur); }
    }
    function hideMsg(el) { el.style.display = 'none'; }

    function escHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function getFormData() {
        var fd = new FormData(form);
        fd.set('_token', _cm_csrf);
        return fd;
    }

    if (saveBtn) {
        saveBtn.addEventListener('click', function(){
            hideMsg(actionMsg); hideMsg(errMsg);
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';
            fetch(saveUrl, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: getFormData() })
            .then(function(r){ return r.json(); })
            .then(function(res){
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Preferences';
                if (res.status === 200) { showMsg(actionMsg, 'Preferences saved!', 3000); }
                else { showMsg(errMsg, res.message || 'Save failed.', 4000); }
            })
            .catch(function(){
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Preferences';
                showMsg(errMsg, 'Network error. Please try again.', 4000);
            });
        });
    }

    function buildMatchedTable(items) {
        if (!items || !items.length) {
            return '<div class="cm-empty-state">No matching institutions found for your preferences.</div>';
        }
        var html = '<table class="cm-match-table"><thead><tr><th style="width:160px">Institute</th><th>Programs</th><th></th></tr></thead><tbody>';
        items.forEach(function(inst){
            var logoHtml = inst.logo_url
                ? '<div class="cm-inst-logo-div" style="background-image:url(\'' + escHtml(inst.logo_url) + '\')"></div>'
                : '<div class="cm-inst-logo-div" style="background:#eee;"></div>';
            var topProgs = Array.isArray(inst.top_programs) ? inst.top_programs : [];
            var progHtml;
            if (topProgs.length) {
                progHtml = '<table class="cm-prog-table">'
                    + '<thead><tr><th>Program</th><th>Admission</th><th>Fees</th></tr></thead><tbody>';
                topProgs.forEach(function(p){
                    var name = (p && typeof p === 'object') ? (p.name || '') : String(p || '');
                    var adm  = (p && typeof p === 'object') ? (p.admission || '&mdash;') : '&mdash;';
                    var fee  = (p && typeof p === 'object') ? (p.fees || '&mdash;') : '&mdash;';
                    progHtml += '<tr>'
                        + '<td class="cm-prog-name">' + escHtml(name) + '</td>'
                        + '<td class="cm-prog-adm">' + escHtml(adm) + '</td>'
                        + '<td class="cm-prog-fee">' + escHtml(fee) + '</td>'
                        + '</tr>';
                });
                progHtml += '</tbody></table>';
            } else {
                progHtml = '&mdash;';
            }
            var scoreHtml = '<span class="cm-score-badge">&#9733; ' + escHtml(String(inst.match_score)) + '/10</span>';
            var reasonHtml = inst.match_reason
                ? '<div class="cm-match-reason">' + escHtml(inst.match_reason) + '</div>' : '';
            html += '<tr>'
                + '<td class="cm-inst-cell">' + logoHtml + '<div class="cm-inst-name">' + escHtml(inst.name) + '</div>' + scoreHtml + reasonHtml + '</td>'
                + '<td class="cm-progs-cell">' + progHtml + '</td>'
                + '<td><a class="cm-view-btn" href="' + escHtml(baseUrl + (inst.profile_url || '')) + '">View</a></td>'
                + '</tr>';
        });
        return html + '</tbody></table>';
    }

    function buildAlsoTable(items) {
        if (!items || !items.length) { return ''; }
        var html = '<table class="cm-match-table"><thead><tr><th style="width:160px">University / College</th><th>Programs</th><th>Why Recommended</th><th></th></tr></thead><tbody>';
        items.forEach(function(item){
            var progs = Array.isArray(item.programs) ? item.programs : [];
            var progHtml = '<table class="cm-prog-table"><thead><tr><th>Program</th><th>Fees</th></tr></thead><tbody>';
            if (progs.length) {
                progs.forEach(function(p){
                    var name = (typeof p === 'object' && p !== null) ? (p.name || '') : String(p);
                    var fee  = (typeof p === 'object' && p !== null) ? (p.fees || '&mdash;') : '&mdash;';
                    progHtml += '<tr><td class="cm-prog-name-cell">' + escHtml(name) + '</td><td class="cm-prog-fee-cell">' + escHtml(fee) + '</td></tr>';
                });
            } else {
                progHtml += '<tr><td colspan="2">&mdash;</td></tr>';
            }
            progHtml += '</tbody></table>';
            var domain = '';
            try {
                if (item.website) domain = (new URL(item.website)).hostname.replace(/^www\./, '');
            } catch(e) {}
            var logoHtml = domain
                ? '<img src="https://logo.clearbit.com/' + escHtml(domain) + '" onerror="this.src=\'https://www.google.com/s2/favicons?domain=' + escHtml(domain) + '&sz=64\';this.onerror=null;" class="cm-also-logo" alt="">'
                : '';
            html += '<tr>'
                + '<td class="cm-inst-cell">' + logoHtml + '<div class="cm-inst-name">' + escHtml(item.name) + '</div><div style="font-size:12px;color:#777;margin-top:2px;">' + escHtml(item.country) + '</div></td>'
                + '<td class="cm-progs-cell" style="padding:0!important">' + progHtml + '</td>'
                + '<td style="font-size:12px;color:#555;">' + escHtml(item.why_recommended) + '</td>'
                + '<td>' + (item.website ? '<a class="cm-ext-link" href="' + escHtml(item.website) + '" target="_blank" rel="noopener noreferrer">Visit</a>' : '') + '</td>'
                + '</tr>';
        });
        return html + '</tbody></table>';
    }

    if (matchBtn) {
        matchBtn.addEventListener('click', function(){
            hideMsg(actionMsg); hideMsg(errMsg);
            resultsWrap.style.display = 'none';
            loadingDiv.style.display  = 'block';
            matchBtn.disabled         = true;
            matchBtn.textContent      = 'Finding matches...';

            fetch(saveUrl, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: getFormData() })
            .then(function(r){ return r.json(); })
            .then(function(saveRes){
                if (saveRes.status !== 200) { throw new Error(saveRes.message || 'Could not save preferences.'); }
                return fetch(matchUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': _cm_csrf
                    },
                    body: new URLSearchParams({'_token': _cm_csrf})
                });
            })
            .then(function(r){ return r.json(); })
            .then(function(res){
                loadingDiv.style.display = 'none';
                matchBtn.disabled = false;
                matchBtn.textContent = 'Find My Match';
                if (res.status !== 200) { showMsg(errMsg, res.message || 'Matching failed.', 5000); return; }
                topBlock.innerHTML  = buildMatchedTable(res.matched);
                alsoBlock.innerHTML = buildAlsoTable(res.also_consider);
                alsoTitle.style.display = (res.also_consider && res.also_consider.length) ? 'block' : 'none';
                resultsWrap.style.display = 'block';
                resultsWrap.scrollIntoView({behavior: 'smooth', block: 'start'});
            })
            .catch(function(err){
                loadingDiv.style.display = 'none';
                matchBtn.disabled = false;
                matchBtn.textContent = 'Find My Match';
                showMsg(errMsg, err.message || 'Network error. Please try again.', 5000);
            });
        });
    }
})();
</script>
@endsection
