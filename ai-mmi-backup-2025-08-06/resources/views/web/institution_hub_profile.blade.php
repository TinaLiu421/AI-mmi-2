@extends('web.common')
@section('content')
<div class="inner-panel">
    <?php
    $_profile = !empty($_page_data['profile']) ? $_page_data['profile'] : [];
    $_has_profile = !empty($_profile['institute_name']);
    ?>

    <h1 class="title">Education Institution Profile</h1>
    <p class="subtitle" style="color:#666; margin-bottom:24px;">Set up your institution profile. Enter your website URL and let AI extract your details automatically.</p>
    <div class="underline"></div>
    <div class="clearboth"></div>

    <!-- Step 1: Website URL entry -->
    <div id="profile-url-step" class="profile-step" style="<?php echo $_has_profile ? 'display:none;' : ''; ?>">
        <div class="profile-url-box">
            <label for="website_url_input" class="profile-field-label">Institution Website URL</label>
            <div class="profile-url-row">
                <input type="url" id="website_url_input" name="website_url"
                    class="profile-input"
                    placeholder="https://www.youruniversity.edu"
                    value="<?php echo htmlspecialchars($_profile['website_url'] ?? '', ENT_QUOTES); ?>">
                <button type="button" id="btn-extract" class="btn-primary" onclick="extractProfile()">
                    Extract with AI
                </button>
            </div>
            <p class="profile-hint">Our AI will visit your website and automatically fill in your institution profile sections below.</p>
        </div>
    </div>

    <!-- Loading indicator -->
    <div id="profile-loading" style="display:none; text-align:center; padding:40px 0;">
        <div class="profile-spinner"></div>
        <p style="color:#555; margin-top:16px; font-size:1em;">Extracting detailed profile from your website...</p>
        <p style="color:#999; font-size:0.85em;">AI is compiling programs, fees, admission requirements and more. This may take up to 2 minutes.</p>
    </div>

    <!-- Step 2: Edit sections -->
    <div id="profile-sections" style="<?php echo $_has_profile ? '' : 'display:none;'; ?>">

        <div class="profile-section-header">
            <h2 style="font-size:1.1em; color:#1a1a2e;">Profile Sections</h2>
            <button type="button" class="btn-outline-sm" onclick="showUrlStep()">Change Website URL</button>
        </div>

        <!-- AI Logo Generator -->
        <div class="profile-section-card logo-gen-card">
            <div class="profile-section-label">Institution Logo</div>
            <div class="logo-gen-body">
                <div class="logo-gen-preview" id="logo-preview-wrap">
                    <?php if (!empty($_page_data['member']['avatar']) && file_exists('upload/member_logo/' . $_page_data['member']['avatar'])) { ?>
                    <img id="logo-preview-img" src="<?php echo 'upload/member_logo/' . htmlspecialchars($_page_data['member']['avatar'], ENT_QUOTES); ?>" alt="Current logo">
                    <?php } else { ?>
                    <div id="logo-preview-placeholder" class="logo-gen-placeholder">No logo yet</div>
                    <img id="logo-preview-img" src="" alt="Generated logo" style="display:none;">
                    <?php } ?>
                    <div id="logo-gen-spinner" class="logo-gen-spinner" style="display:none;"></div>
                </div>
                <div class="logo-gen-actions">
                    <button type="button" class="btn-primary" id="btn-gen-logo" onclick="generateLogo()">
                        🔍 Fetch Logo from Website
                    </button>
                    <button type="button" class="btn-outline-sm" id="btn-apply-logo" onclick="applyLogo()" style="display:none;">
                        Use this logo
                    </button>
                    <span id="logo-gen-status" style="font-size:0.88em; color:#27ae60; margin-left:4px;"></span>
                </div>
            </div>
            <p class="profile-hint" style="margin-top:10px;">Automatically fetches the official logo from your institution website. Preview it, then click "Use this logo" to save.</p>
        </div>

        <form id="profile-form" method="post">
            <div>@csrf</div>
            <input type="hidden" id="profile_website_url" name="website_url"
                value="<?php echo htmlspecialchars($_profile['website_url'] ?? '', ENT_QUOTES); ?>">

            <div class="profile-section-card">
                <div class="profile-section-label">Institute Name</div>
                <input type="text" id="f_institute_name" name="institute_name"
                    class="profile-input"
                    placeholder="Official institution name"
                    value="<?php echo htmlspecialchars($_profile['institute_name'] ?? '', ENT_QUOTES); ?>">
            </div>

            <div class="profile-section-card">
                <div class="profile-section-label">Programs Offered</div>
                <textarea id="f_programs" name="programs" class="profile-textarea" rows="12"
                    placeholder="Describe the programs and courses offered..."><?php echo htmlspecialchars($_profile['programs'] ?? '', ENT_QUOTES); ?></textarea>
            </div>

            <div class="profile-section-card">
                <div class="profile-section-label">Admission Requirements</div>
                <textarea id="f_admission" name="admission" class="profile-textarea" rows="10"
                    placeholder="Admission requirements, process, and eligibility..."><?php echo htmlspecialchars($_profile['admission'] ?? '', ENT_QUOTES); ?></textarea>
            </div>

            <div class="profile-section-card">
                <div class="profile-section-label">Fees</div>
                <textarea id="f_fees" name="fees" class="profile-textarea" rows="10"
                    placeholder="Tuition fees and other costs..."><?php echo htmlspecialchars($_profile['fees'] ?? '', ENT_QUOTES); ?></textarea>
            </div>

            <div class="profile-section-card">
                <div class="profile-section-label">Summary</div>
                <textarea id="f_summary" name="summary" class="profile-textarea" rows="8"
                    placeholder="Brief summary of your institution..."><?php echo htmlspecialchars($_profile['summary'] ?? '', ENT_QUOTES); ?></textarea>
            </div>

            <div class="profile-section-card">
                <div class="profile-section-label">Key Dates</div>
                <textarea id="f_key_dates" name="key_dates" class="profile-textarea" rows="8"
                    placeholder="Application deadlines, intake dates, open days..."><?php echo htmlspecialchars($_profile['key_dates'] ?? '', ENT_QUOTES); ?></textarea>
            </div>

            <div style="margin-top: 28px; display:flex; gap:12px; flex-wrap:wrap;">
                <button type="button" class="btn-primary" onclick="saveProfile()">Save Profile</button>
                <span id="save-status" style="align-self:center; font-size:0.9em; color:#27ae60;"></span>
            </div>
        </form>
    </div>

    <!-- Students counters (education only) -->
    <div class="profile-counters" style="margin-top:36px;">
        <h2 style="font-size:1em; font-weight:700; color:#1a1a2e; margin-bottom:16px;">Student Activity</h2>
        <div class="counters-row">
            <div class="counter-card">
                <div class="counter-num"><?php echo (int)($_profile['students_matched'] ?? 0); ?></div>
                <div class="counter-label">Students Matched</div>
            </div>
            <div class="counter-card">
                <div class="counter-num"><?php echo (int)($_profile['students_applied'] ?? 0); ?></div>
                <div class="counter-label">Students Applied</div>
            </div>
            <div class="counter-card">
                <div class="counter-num"><?php echo (int)($_profile['students_accepted'] ?? 0); ?></div>
                <div class="counter-label">Students Accepted</div>
            </div>
        </div>
    </div>

</div>

<style>
.profile-step { margin: 24px 0; }
.profile-url-box {
    background: #f7f9fc;
    border: 1px solid #dde3ed;
    border-radius: 10px;
    padding: 24px 22px;
    max-width: 640px;
}
.profile-field-label {
    font-weight: 600;
    font-size: 0.95em;
    color: #333;
    display: block;
    margin-bottom: 10px;
}
.profile-url-row {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}
.profile-input {
    flex: 1;
    min-width: 200px;
    padding: 10px 14px;
    border: 1.5px solid #ccd4e0;
    border-radius: 7px;
    font-size: 0.95em;
    outline: none;
    transition: border-color 0.2s;
}
.profile-input:focus { border-color: #1a73e8; }
.profile-hint { font-size: 0.82em; color: #888; margin-top: 10px; }
.profile-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 24px 0 16px 0;
    flex-wrap: wrap;
    gap: 8px;
}
.profile-section-card {
    background: #fff;
    border: 1.5px solid #e4e8f0;
    border-radius: 10px;
    padding: 18px 18px 16px 18px;
    margin-bottom: 16px;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.profile-section-card:focus-within {
    border-color: #1a73e8;
    box-shadow: 0 2px 10px rgba(26,115,232,0.1);
}
.profile-section-label {
    font-weight: 700;
    font-size: 0.9em;
    color: #1a73e8;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 10px;
}
.profile-textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1.5px solid #d0d8e8;
    border-radius: 7px;
    font-size: 0.93em;
    resize: vertical;
    outline: none;
    font-family: inherit;
    box-sizing: border-box;
    transition: border-color 0.2s;
}
.profile-textarea:focus { border-color: #1a73e8; }
.btn-primary {
    background: #1a73e8;
    color: #fff;
    border: none;
    padding: 11px 22px;
    border-radius: 7px;
    font-size: 0.95em;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-primary:hover { background: #1558b0; }
.btn-outline-sm {
    background: transparent;
    color: #1a73e8;
    border: 1.5px solid #1a73e8;
    padding: 7px 14px;
    border-radius: 7px;
    font-size: 0.85em;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
}
.btn-outline-sm:hover { background: #f0f6ff; }
/* Spinner */
.profile-spinner {
    width: 44px; height: 44px;
    border: 4px solid #e0e8f8;
    border-top-color: #1a73e8;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto;
}
@keyframes spin { to { transform: rotate(360deg); } }
/* Counters */
.counters-row {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}
.counter-card {
    flex: 1;
    min-width: 130px;
    background: #f7f9fc;
    border: 1.5px solid #dde3ed;
    border-radius: 10px;
    padding: 20px 16px;
    text-align: center;
}
.counter-num {
    font-size: 2em;
    font-weight: 800;
    color: #1a73e8;
}
.counter-label {
    font-size: 0.82em;
    color: #666;
    margin-top: 4px;
}
/* Logo generator */
.logo-gen-card { }
.logo-gen-body {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    flex-wrap: wrap;
}
.logo-gen-preview {
    width: 110px;
    height: 110px;
    border: 2px dashed #c8d3e6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
    background: #fff;
    position: relative;
}
.logo-gen-preview img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 8px;
    border-radius: 0;
    box-sizing: border-box;
}
.logo-gen-placeholder {
    font-size: 0.78em;
    color: #aaa;
    text-align: center;
    padding: 8px;
}
.logo-gen-spinner {
    position: absolute;
    width: 36px; height: 36px;
    border: 3px solid #e0e8f8;
    border-top-color: #1a73e8;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
.logo-gen-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    justify-content: center;
}
</style>

<script>
var _scrape_url      = '<?php echo $_page_base_url.'/institution_hub_profile/scrape'; ?>';
var _save_url        = '<?php echo $_page_base_url.'/institution_hub_profile/save'; ?>';
var _gen_logo_url    = '<?php echo $_page_base_url.'/institution_hub_profile/generate_logo'; ?>';
var _apply_logo_url  = '<?php echo $_page_base_url.'/institution_hub_profile/apply_logo'; ?>';
var _csrf_token      = '<?php echo csrf_token(); ?>';
var _pending_logo_file = null;

function extractProfile() {
    var url = document.getElementById('website_url_input').value.trim();
    if (!url) {
        alert('Please enter your institution website URL.');
        return;
    }

    document.getElementById('profile-url-step').style.display  = 'none';
    document.getElementById('profile-loading').style.display   = 'block';
    document.getElementById('profile-sections').style.display  = 'none';

    jQuery.ajax({
        url: _scrape_url,
        type: 'POST',
        data: { website_url: url, _token: _csrf_token },
        timeout: 180000,
        success: function(resp) {
            document.getElementById('profile-loading').style.display = 'none';
            if (resp && resp.status === 200 && resp.data) {
                fillSections(resp.data);
                document.getElementById('profile-sections').style.display = 'block';
                document.getElementById('profile_website_url').value = url;
            } else {
                alert('Could not extract profile data. Please fill in the sections manually.');
                document.getElementById('profile-sections').style.display = 'block';
                document.getElementById('profile-url-step').style.display = 'block';
            }
        },
        error: function() {
            document.getElementById('profile-loading').style.display = 'none';
            alert('Extraction failed. Please fill in the sections manually.');
            document.getElementById('profile-sections').style.display = 'block';
            document.getElementById('profile-url-step').style.display  = 'block';
        }
    });
}

function fillSections(data) {
    var fields = ['institute_name','programs','admission','fees','summary','key_dates'];
    fields.forEach(function(f) {
        var el = document.getElementById('f_' + f);
        if (el && data[f] !== undefined) {
            el.value = data[f];
        }
    });
}

function showUrlStep() {
    document.getElementById('profile-url-step').style.display = 'block';
    document.getElementById('website_url_input').value = document.getElementById('profile_website_url').value;
}

function generateLogo() {
    var websiteUrl = (document.getElementById('profile_website_url') || {}).value || '';
    websiteUrl = websiteUrl.trim();
    if (!websiteUrl) {
        websiteUrl = (document.getElementById('website_url_input') || {}).value || '';
        websiteUrl = websiteUrl.trim();
    }
    if (!websiteUrl) {
        alert('Please enter your institution website URL first.');
        return;
    }

    var btnGen      = document.getElementById('btn-gen-logo');
    var btnApply    = document.getElementById('btn-apply-logo');
    var statusEl    = document.getElementById('logo-gen-status');
    var spinner     = document.getElementById('logo-gen-spinner');
    var previewImg  = document.getElementById('logo-preview-img');
    var placeholder = document.getElementById('logo-preview-placeholder');

    btnGen.disabled        = true;
    btnGen.textContent     = 'Fetching...';
    btnApply.style.display = 'none';
    statusEl.textContent   = '';
    spinner.style.display  = 'block';
    if (previewImg) previewImg.style.opacity = '0.3';

    jQuery.ajax({
        url:     _gen_logo_url,
        type:    'POST',
        data:    { website_url: websiteUrl, _token: _csrf_token },
        timeout: 30000,
        success: function(resp) {
            spinner.style.display = 'none';
            btnGen.disabled       = false;
            btnGen.textContent    = '🔍 Fetch Logo from Website';
            if (resp && resp.status === 200) {
                _pending_logo_file = resp.logo_file;
                if (placeholder) placeholder.style.display = 'none';
                previewImg.src          = resp.logo_url + '?t=' + Date.now();
                previewImg.style.display  = 'block';
                previewImg.style.opacity  = '1';
                btnApply.style.display    = 'inline-block';
                statusEl.style.color      = '#888';
                statusEl.textContent      = 'Not applied yet — click "Use this logo" to save.';
            } else {
                statusEl.style.color = '#e74c3c';
                statusEl.textContent = (resp && resp.message) ? resp.message : 'Could not fetch logo.';
                if (previewImg) previewImg.style.opacity = '1';
            }
        },
        error: function() {
            spinner.style.display = 'none';
            btnGen.disabled       = false;
            btnGen.textContent    = '🔍 Fetch Logo from Website';
            if (previewImg) previewImg.style.opacity = '1';
            statusEl.style.color = '#e74c3c';
            statusEl.textContent = 'Could not fetch logo. Please try again or upload manually.';
        }
    });
}

function applyLogo() {
    if (!_pending_logo_file) return;
    var btnApply = document.getElementById('btn-apply-logo');
    var statusEl = document.getElementById('logo-gen-status');
    btnApply.disabled = true;
    btnApply.textContent = 'Applying...';

    jQuery.ajax({
        url: _apply_logo_url,
        type: 'POST',
        data: { logo_file: _pending_logo_file, _token: _csrf_token },
        success: function(resp) {
            btnApply.disabled = false;
            btnApply.textContent = 'Use this logo';
            if (resp && resp.status === 200) {
                statusEl.style.color = '#27ae60';
                statusEl.textContent = 'Logo applied!';
                _pending_logo_file = null;
                btnApply.style.display = 'none';
            } else {
                statusEl.style.color = '#e74c3c';
                statusEl.textContent = 'Failed to apply logo.';
            }
        },
        error: function() {
            btnApply.disabled = false;
            btnApply.textContent = 'Use this logo';
            statusEl.style.color = '#e74c3c';
            statusEl.textContent = 'Failed to apply logo.';
        }
    });
}

function saveProfile() {
    var fields = ['institute_name','programs','admission','fees','summary','key_dates'];
    var formData = { _token: _csrf_token };
    formData['website_url'] = document.getElementById('profile_website_url').value;
    fields.forEach(function(f) {
        var el = document.getElementById('f_' + f);
        formData[f] = el ? el.value : '';
    });

    var btn = document.querySelector('#profile-form .btn-primary');
    var statusEl = document.getElementById('save-status');
    btn.disabled = true;
    btn.textContent = 'Saving...';
    statusEl.textContent = '';

    jQuery.ajax({
        url: _save_url,
        type: 'POST',
        data: formData,
        success: function(resp) {
            btn.disabled = false;
            btn.textContent = 'Save Profile';
            if (resp && resp.status === 200) {
                statusEl.textContent = 'Saved successfully!';
                setTimeout(function(){ statusEl.textContent = ''; }, 3000);
            } else {
                statusEl.style.color = '#e74c3c';
                statusEl.textContent = 'Save failed. Please try again.';
            }
        },
        error: function() {
            btn.disabled = false;
            btn.textContent = 'Save Profile';
            statusEl.style.color = '#e74c3c';
            statusEl.textContent = 'Save failed. Please try again.';
        }
    });
}
</script>
@endsection
