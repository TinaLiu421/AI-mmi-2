@extends('web.common')

@push('css')
<style>
main.page-body .info-area {
    width: 100% !important;
    float: none !important;
    background: #0c1445 !important;
    background-image: none !important;
    background-color: #0c1445 !important;
    min-height: 100vh !important;
}
main.page-body .info-area::before { display: none !important; }
main.page-body .page-content { margin-right: 0 !important; }
body { background: #0c1445 !important; }
/* Hide fixed chat panel — this page has its own nav + chat link */
main.page-body .chat-area { display: none !important; }
main.page-body .mobile-chat-button { display: none !important; }
</style>
@endpush

@section('content')
<?php
// Guest mode — not logged in or wrong account type
if (!empty($_page_data['is_guest'])):
?>
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;color:#fff;text-align:center;padding:40px 20px;">
    <div style="font-size:3rem;margin-bottom:16px;">&#127979;</div>
    <h2 style="font-size:1.6rem;margin-bottom:10px;">Institution Hub</h2>
    <p style="color:#b0b8d0;margin-bottom:28px;max-width:420px;">Log in as an education institution to manage your profile, programs, and connect with students.</p>
    <a href="<?php echo htmlspecialchars($_page_base_url . '/account_login', ENT_QUOTES); ?>"
       style="background:#5c6bc0;color:#fff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:600;font-size:1rem;margin-bottom:12px;">
        Log In
    </a>
    <a href="<?php echo htmlspecialchars($_page_base_url . '/account_registration', ENT_QUOTES); ?>"
       style="color:#a0aacc;font-size:0.9rem;text-decoration:underline;">
        Don&rsquo;t have an account? Register
    </a>
</div>
<?php else: ?>
<?php
$_ihp_base = rtrim($_page_base_url, '/');
$_ihp_is_institution = !empty($_current_member) && (int)($_current_member['type'] ?? 0) === 3;
$_ihp_autoLang = !empty($_page_get_data['autolang']) ? $_page_get_data['autolang'] : session('autolang', '');
$appendAutoLang = function ($url) use ($_ihp_autoLang) {
    if (empty($_ihp_autoLang)) return $url;
    return $url . ((strpos($url, '?') !== false) ? '&' : '?') . 'autolang=' . urlencode($_ihp_autoLang);
};
?>
<div class="ihp-outer-wrap">

<!-- NAV SIDEBAR -->
<div class="ihp-nav-sidebar">
    <a href="<?php echo htmlspecialchars($appendAutoLang($_ihp_base.'/study_plans'), ENT_QUOTES); ?>">
        <span class="ihp-nav-icon"><i class="fa fa-star"></i></span>
        <span class="ihp-nav-label">Dreams</span>
    </a>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_ihp_base.'/study_college_match'), ENT_QUOTES); ?>">
        <span class="ihp-nav-icon"><i class="fa fa-graduation-cap"></i></span>
        <span class="ihp-nav-label">Matches</span>
    </a>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_ihp_base.'/nextgen_challenge'), ENT_QUOTES); ?>">
        <span class="ihp-nav-icon"><i class="fa fa-trophy"></i></span>
        <span class="ihp-nav-label">NextGen AI &amp;<br>Talent Challenge</span>
    </a>
    <?php if (!$_ihp_is_institution): ?>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_ihp_base.'/institution_explore'), ENT_QUOTES); ?>">
        <span class="ihp-nav-icon"><i class="fa fa-building"></i></span>
        <span class="ihp-nav-label">Colleges</span>
    </a>
    <?php else: ?>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_ihp_base.'/student_explore'), ENT_QUOTES); ?>">
        <span class="ihp-nav-icon"><i class="fa fa-users"></i></span>
        <span class="ihp-nav-label">Explore Students</span>
    </a>
    <?php endif; ?>
    <?php if (!empty($_current_member)): ?>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_ihp_is_institution ? $_ihp_base.'/institution_hub_profile' : ((int)($_current_member['type'] ?? 0) === 2 ? $_ihp_base.'/account/posts' : $_ihp_base.'/student_profile')), ENT_QUOTES); ?>" class="active">
        <span class="ihp-nav-icon"><i class="fa fa-id-card"></i></span>
        <span class="ihp-nav-label">My Profile</span>
    </a>
    <?php endif; ?>
    <a href="javascript:void(0);" class="do-toapply" data-sector="study" data-action-url="<?php echo htmlspecialchars($_ihp_base.'/agent_chat', ENT_QUOTES); ?>">
        <?php if (!empty($_current_member) && !empty($_current_member['avatar'])): ?>
        <?php if (file_exists(public_path('upload/member_avatar/'.$_current_member['avatar']))): ?>
        <div class="ihp-chat-av" style="background-image:url(upload/member_avatar/<?php echo htmlspecialchars($_current_member['avatar'], ENT_QUOTES); ?>)"></div>
        <?php else: ?>
        <div class="ihp-chat-av ihp-chat-av--init"><?php echo htmlspecialchars(mb_substr($_current_member['alias_name'] ?? 'A', 0, 1), ENT_QUOTES); ?></div>
        <?php endif; ?>
        <?php else: ?>
        <div class="ihp-chat-av ihp-chat-av--blank"></div>
        <?php endif; ?>
        <span class="ihp-nav-label">Chat with<br>AI-mmi</span>
    </a>
</div>
<!-- END NAV SIDEBAR -->

<div class="ihp-content-area">
<div class="inner-panel">
    <?php
    $_profile = !empty($_page_data['profile']) ? $_page_data['profile'] : [];
    $_has_profile = !empty($_profile['institute_name']);
    $_is_admin_proxy = !empty($_page_data['is_admin_proxy']);
    $_proxy_member = !empty($_page_data['member']) ? $_page_data['member'] : [];
    // Parse courses JSON
    $_courses_json_raw = $_profile['courses_json'] ?? '';
    if (empty($_courses_json_raw) && !empty($_profile['programs'])) {
        $_programs_raw = (string)$_profile['programs'];
        if (strpos($_programs_raw, '__AIMMI_COURSES_JSON__:') === 0) {
            $_programs_raw = substr($_programs_raw, strlen('__AIMMI_COURSES_JSON__:'));
        }
        $_courses_json_raw = $_programs_raw;
    }
    $_courses_arr = [];
    if (!empty($_courses_json_raw)) {
        $decoded = json_decode($_courses_json_raw, true);
        if (is_array($decoded)) { $_courses_arr = $decoded; }
    }
    // Parse key_dates text into blocks (blank-line separated)
    $_key_dates_raw = $_profile['key_dates'] ?? '';
    $_key_dates_blocks = [];
    if (!empty($_key_dates_raw)) {
        $blocks = preg_split('/\n\s*\n/', $_key_dates_raw);
        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') continue;
            $lines = explode("\n", $block, 2);
            $_key_dates_blocks[] = [
                'title'   => rtrim($lines[0] ?? '', ':'),
                'details' => trim($lines[1] ?? ''),
            ];
        }
    }
    // Parse new structured JSON fields
    $_curriculum_arr       = json_decode($_profile['curriculum'] ?? '[]', true) ?: [];
    $_exam_boards_arr      = json_decode($_profile['exam_boards'] ?? '[]', true) ?: [];
    $_qualifications_arr   = json_decode($_profile['qualifications_awarded'] ?? '[]', true) ?: [];
    $_language_arr         = json_decode($_profile['language_of_instruction'] ?? '[]', true) ?: [];
    $_school_qualities_arr = json_decode($_profile['school_qualities'] ?? '[]', true) ?: [];
    $_social_links         = json_decode($_profile['social_links'] ?? '{}', true) ?: [];
    ?>

    {{-- Admin proxy banner --}}
    @if($_is_admin_proxy)
    <div style="background:#fffbeb; border:1px solid #fbbf24; border-radius:8px; padding:12px 18px; margin-bottom:22px; display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
        <i class="fa fa-user-secret" style="color:#d97706; font-size:1.3em;"></i>
        <span style="color:#92400e; font-size:0.95em;">
            <strong>Admin Proxy Mode:</strong> You are editing the profile of
            <strong><?php echo htmlspecialchars($_proxy_member['full_name'] ?? ('Account #' . ($_page_data['proxy_member_id'] ?? '')), ENT_QUOTES); ?></strong>
        </span>
        <a href="{{ url('/en/Admin_Edu_Agents') }}" style="margin-left:auto; font-size:0.85em; color:#1a5ca8;">← Back to Edu Agent Management</a>
    </div>
    @endif

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
        <p id="loading-status" style="color:#333; margin-top:16px; font-size:1em; font-weight:600;">Visiting institution homepage...</p>
        <p style="color:#999; font-size:0.85em; margin-top:6px;">AI is performing multiple live web searches to get accurate, up-to-date data.<br>This typically takes 2&ndash;4 minutes &mdash; please keep this page open.</p>
        <div style="margin-top:18px; display:flex; flex-direction:column; gap:4px; align-items:center; font-size:0.82em; color:#aaa;" id="loading-steps">
            <span id="ls-1">&#x25CB; Searching homepage</span>
            <span id="ls-2">&#x25CB; Searching programs &amp; courses</span>
            <span id="ls-3">&#x25CB; Searching tuition fees</span>
            <span id="ls-4">&#x25CB; Searching admission requirements</span>
            <span id="ls-5">&#x25CB; Searching key dates &amp; deadlines</span>
            <span id="ls-6">&#x25CB; Searching scholarships &amp; living costs</span>
        </div>
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
                <?php
                // Prefer per-profile logo over shared member avatar
                $_edit_logo = $_page_data['profile']['logo'] ?? '';
                if (empty($_edit_logo)) $_edit_logo = $_page_data['member']['avatar'] ?? '';
                $_edit_logo_src = (!empty($_edit_logo) && file_exists(public_path('upload/member_logo/' . $_edit_logo)))
                    ? 'upload/member_logo/' . htmlspecialchars($_edit_logo, ENT_QUOTES) : '';
                ?>
                <div class="logo-gen-preview" id="logo-preview-wrap">
                    <?php if (!empty($_edit_logo_src)): ?>
                    <img id="logo-preview-img" src="<?php echo $_edit_logo_src; ?>" alt="Current logo">
                    <?php else: ?>
                    <div id="logo-preview-placeholder" class="logo-gen-placeholder">No logo yet</div>
                    <img id="logo-preview-img" src="" alt="Generated logo" style="display:none;">
                    <?php endif; ?>
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
                <div class="logo-upload-manual" style="margin-top:10px; padding-top:10px; border-top:1px solid #eee;">
                    <label style="font-size:0.85em; color:#666; margin-bottom:4px; display:block;">Or upload manually (PNG, JPG, SVG, WEBP):</label>
                    <input type="file" id="logo-manual-input" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml,.svg" style="font-size:0.85em;">
                    <button type="button" class="btn-outline-sm" id="btn-upload-logo" onclick="uploadLogoManually()" style="margin-left:6px; vertical-align:middle;">Upload</button>
                    <span id="logo-upload-status" style="font-size:0.85em; color:#666; margin-left:6px;"></span>
                </div>
            </div>
            <p class="profile-hint" style="margin-top:10px;">Automatically fetches the official logo from your institution website. Preview it, then click "Use this logo" to save. If auto-fetch fails, upload a logo directly.</p>
        </div>

        <!-- Institution Photo Gallery -->
        <?php
        $_gallery_json_raw = $_profile['gallery_json'] ?? '';
        $_gallery_files = [];
        if (!empty($_gallery_json_raw)) {
            $gd = json_decode($_gallery_json_raw, true);
            if (is_array($gd)) { $_gallery_files = array_values(array_filter($gd)); }
        }
        ?>
        <div class="profile-section-card">
            <div class="profile-section-label">Institution Photos <span style="font-size:0.8em; font-weight:400; color:#888;">(shown on your public profile, max 8)</span></div>
            <div id="gallery-grid" style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:12px;">
                <?php foreach ($_gallery_files as $_gf): $_gsrc = 'upload/inst_gallery/' . htmlspecialchars($_gf, ENT_QUOTES); ?>
                <?php if (file_exists(public_path('upload/inst_gallery/' . $_gf))): ?>
                <div class="gallery-thumb-wrap" id="gwrap-<?php echo htmlspecialchars($_gf, ENT_QUOTES); ?>">
                    <img src="<?php echo $_gsrc; ?>" class="gallery-thumb-img" alt="Gallery photo">
                    <button class="gallery-thumb-del" onclick="deleteGalleryPhoto('<?php echo htmlspecialchars($_gf, ENT_QUOTES); ?>')" title="Delete photo">&times;</button>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
                <?php if (empty($_gallery_files)): ?>
                <div id="gallery-empty-msg" style="color:#bbb; font-size:0.9em; padding:12px 0;">No photos uploaded yet.</div>
                <?php endif; ?>
            </div>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <input type="file" id="gallery-upload-input" accept="image/png,image/jpeg,image/gif,image/webp" style="font-size:0.85em;">
                <button type="button" class="btn-outline-sm" onclick="uploadGalleryPhoto()">Upload Photo</button>
                <span id="gallery-upload-status" style="font-size:0.85em; color:#27ae60;"></span>
            </div>
            <p class="profile-hint" style="margin-top:8px;">Upload photos of your campus, facilities, or classrooms. These are shown as a photo gallery on your public profile page.</p>
        </div>

        <form id="profile-form" method="post">
            <div>@csrf</div>
            <input type="hidden" id="profile_website_url" name="website_url"
                value="<?php echo htmlspecialchars($_profile['website_url'] ?? '', ENT_QUOTES); ?>">

            {{-- Institute Name --}}
            <div class="profile-section-card">
                <div class="profile-section-label">Institute Name</div>
                <input type="text" id="f_institute_name" name="institute_name"
                    class="profile-input"
                    placeholder="Official institution name"
                    value="<?php echo htmlspecialchars($_profile['institute_name'] ?? '', ENT_QUOTES); ?>">
            </div>

            {{-- Institution Category --}}
            <div class="profile-section-card">
                <div class="profile-section-label">Institution Type</div>
                <select id="f_institution_category" name="institution_category" class="profile-input" style="cursor:pointer;">
                    <?php
                    $savedCat = $_profile['institution_category'] ?? 'university';
                    $cats = [
                        'university'           => 'University',
                        'vocational'           => 'Vocational Education (VET)',
                        'highschool'           => 'High School',
                        'college'              => 'College',
                        'language_school'      => 'Language School (ELICOS)',
                        'primary_school'       => 'Primary School',
                        'secondary_school'     => 'Secondary School',
                        'international_school' => 'International School',
                        'tutoring'             => 'Tutoring Centre',
                        'other'                => 'Other',
                    ];
                    foreach ($cats as $val => $label):
                    ?>
                    <option value="<?php echo $val; ?>" <?php echo $savedCat === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="profile-hint" style="margin-top:6px;">Select the type of institution. This helps students filter and find the right fit.</p>
            </div>

            {{-- Contact & Location --}}
            <div class="profile-section-card">
                <div class="profile-section-label">Contact &amp; Location</div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;">City</label>
                        <input type="text" id="f_city" name="city" class="profile-input" placeholder="e.g. Brisbane" value="<?php echo htmlspecialchars($_profile['city'] ?? '', ENT_QUOTES); ?>">
                    </div>
                    <div>
                        <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;">Phone</label>
                        <input type="text" id="f_phone" name="phone" class="profile-input" placeholder="e.g. +61 7 3522 1869" value="<?php echo htmlspecialchars($_profile['phone'] ?? '', ENT_QUOTES); ?>">
                    </div>
                </div>
                <div style="margin-top:10px;">
                    <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;">Street Address</label>
                    <input type="text" id="f_address" name="address" class="profile-input" placeholder="e.g. Level 1, 252 St Paul's Terrace, Fortitude Valley QLD 4006" value="<?php echo htmlspecialchars($_profile['address'] ?? '', ENT_QUOTES); ?>">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:10px;">
                    <div>
                        <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;">Annual Fees Range</label>
                        <input type="text" id="f_annual_fees_range" name="annual_fees_range" class="profile-input" placeholder="e.g. AUD 20,000–30,000" value="<?php echo htmlspecialchars($_profile['annual_fees_range'] ?? '', ENT_QUOTES); ?>">
                    </div>
                    <div>
                        <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;">School Phases / Levels</label>
                        <input type="text" id="f_school_phases" name="school_phases" class="profile-input" placeholder="e.g. Certificate, Diploma, Bachelor" value="<?php echo htmlspecialchars($_profile['school_phases'] ?? '', ENT_QUOTES); ?>">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:10px;">
                    <div>
                        <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;">Cost of Living <span style="font-weight:400;">(student estimate)</span></label>
                        <input type="text" id="f_cost_of_living" name="cost_of_living" class="profile-input" placeholder="e.g. AUD 21,000 per year" value="<?php echo htmlspecialchars($_profile['cost_of_living'] ?? '', ENT_QUOTES); ?>">
                    </div>
                    <div>
                        <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;">Registration / Provider Number</label>
                        <input type="text" id="f_registration_number" name="registration_number" class="profile-input" placeholder="e.g. CRICOS 00116K, RTO 0132" value="<?php echo htmlspecialchars($_profile['registration_number'] ?? '', ENT_QUOTES); ?>">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:10px;">
                    <div>
                        <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;">Intake Periods</label>
                        <input type="text" id="f_intakes" name="intakes" class="profile-input" placeholder="e.g. February, July, November" value="<?php echo htmlspecialchars($_profile['intakes'] ?? '', ENT_QUOTES); ?>">
                    </div>
                    <div>
                        <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;">Visa Requirements <span style="font-weight:400;">(brief)</span></label>
                        <input type="text" id="f_visa_requirements" name="visa_requirements" class="profile-input" placeholder="e.g. Student visa (500), OSHC required" value="<?php echo htmlspecialchars($_profile['visa_requirements'] ?? '', ENT_QUOTES); ?>">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:10px;">
                    <div>
                        <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;">Prospectus URL</label>
                        <input type="url" id="f_prospectus_url" name="prospectus_url" class="profile-input" placeholder="https://..." value="<?php echo htmlspecialchars($_profile['prospectus_url'] ?? '', ENT_QUOTES); ?>">
                    </div>
                    <div>
                        <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;">Banner Image URL</label>
                        <input type="url" id="f_banner_image" name="banner_image" class="profile-input" placeholder="https://..." value="<?php echo htmlspecialchars($_profile['banner_image'] ?? '', ENT_QUOTES); ?>">
                    </div>
                </div>
            </div>

            {{-- About / Summary (institution-level) --}}
            <div class="profile-section-card">
                <div class="profile-section-label">Short Summary</div>
                <textarea id="f_summary" name="summary" class="profile-textarea"
                    placeholder="Brief overview of your institution — history, values, campus life, strengths..."><?php echo htmlspecialchars($_profile['summary'] ?? '', ENT_QUOTES); ?></textarea>
                <p class="profile-hint" style="margin-top:4px;">Shown in the hero/header area of your public profile (2–3 sentences).</p>
            </div>

            {{-- Full Description --}}
            <div class="profile-section-card">
                <div class="profile-section-label">Full Description</div>
                <textarea id="f_description" name="description" class="profile-textarea" rows="6"
                    placeholder="Full multi-paragraph description of your institution — programs offered, campus life, industry links, student support..."><?php echo htmlspecialchars($_profile['description'] ?? '', ENT_QUOTES); ?></textarea>
                <p class="profile-hint" style="margin-top:4px;">Shown in the "About School" tab. Can be multiple paragraphs.</p>
            </div>

            {{-- Mission Statement --}}
            <div class="profile-section-card">
                <div class="profile-section-label">Mission Statement</div>
                <textarea id="f_mission_statement" name="mission_statement" class="profile-textarea" rows="3"
                    placeholder="Your institution's mission statement (1–3 sentences)..."><?php echo htmlspecialchars($_profile['mission_statement'] ?? '', ENT_QUOTES); ?></textarea>
            </div>

            {{-- Academic Details --}}
            <div class="profile-section-card">
                <div class="profile-section-label">Academic Details</div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;">Academic Year</label>
                        <input type="text" id="f_academic_year" name="academic_year" class="profile-input" placeholder="e.g. February – November" value="<?php echo htmlspecialchars($_profile['academic_year'] ?? '', ENT_QUOTES); ?>">
                    </div>
                    <div>
                        <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;">Student/Teacher Ratio</label>
                        <input type="text" id="f_student_teacher_ratio" name="student_teacher_ratio" class="profile-input" placeholder="e.g. 1:20" value="<?php echo htmlspecialchars($_profile['student_teacher_ratio'] ?? '', ENT_QUOTES); ?>">
                    </div>
                </div>
                <div style="margin-top:10px;">
                    <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;">Language of Instruction <span style="font-weight:400;">(comma-separated)</span></label>
                    <input type="text" id="f_language_of_instruction" name="language_of_instruction" class="profile-input" placeholder="e.g. English, Mandarin" value="<?php echo htmlspecialchars(implode(', ', $_language_arr), ENT_QUOTES); ?>">
                </div>
                <div style="margin-top:10px;">
                    <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;">Curriculum <span style="font-weight:400;">(comma-separated)</span></label>
                    <input type="text" id="f_curriculum" name="curriculum" class="profile-input" placeholder="e.g. Australian Curriculum, IB, CRICOS" value="<?php echo htmlspecialchars(implode(', ', $_curriculum_arr), ENT_QUOTES); ?>">
                </div>
                <div style="margin-top:10px;">
                    <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;">Exam Boards <span style="font-weight:400;">(comma-separated)</span></label>
                    <input type="text" id="f_exam_boards" name="exam_boards" class="profile-input" placeholder="e.g. ASQA, NESA" value="<?php echo htmlspecialchars(implode(', ', $_exam_boards_arr), ENT_QUOTES); ?>">
                </div>
                <div style="margin-top:10px;">
                    <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;">Qualifications Awarded <span style="font-weight:400;">(comma-separated)</span></label>
                    <input type="text" id="f_qualifications_awarded" name="qualifications_awarded" class="profile-input" placeholder="e.g. Certificate III, Diploma, Bachelor" value="<?php echo htmlspecialchars(implode(', ', $_qualifications_arr), ENT_QUOTES); ?>">
                </div>
                <div style="margin-top:10px;">
                    <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;">School Highlights / Qualities <span style="font-weight:400;">(comma-separated)</span></label>
                    <input type="text" id="f_school_qualities" name="school_qualities" class="profile-input" placeholder="e.g. Industry partnerships, Hands-on training, CRICOS registered" value="<?php echo htmlspecialchars(implode(', ', $_school_qualities_arr), ENT_QUOTES); ?>">
                </div>
            </div>

            {{-- Facilities & Features --}}
            <div class="profile-section-card">
                <div class="profile-section-label">Facilities &amp; Features</div>
                <p class="profile-hint" style="margin-bottom:12px;">Tick all that apply to your institution.</p>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <?php
                    $boolFieldMap = [
                        'has_boarding'                 => 'Boarding / Accommodation available',
                        'has_school_bus'               => 'School bus service',
                        'has_scholarships'             => 'Scholarships available',
                        'has_chinese_language_support' => 'Chinese language support',
                        'has_extra_languages'          => 'Extra language programs',
                    ];
                    foreach ($boolFieldMap as $bfName => $bfLabel):
                        $bfChecked = !empty($_profile[$bfName]) ? 'checked' : '';
                    ?>
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.92em; color:#333; padding:8px 10px; border:1px solid #e8ecf0; border-radius:6px; background:#f9fafb;">
                        <input type="checkbox" id="f_<?php echo $bfName; ?>" name="<?php echo $bfName; ?>" value="1" <?php echo $bfChecked; ?> style="width:16px; height:16px; cursor:pointer;">
                        <?php echo htmlspecialchars($bfLabel, ENT_QUOTES); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            {{-- Social Media Links --}}
            <div class="profile-section-card">
                <div class="profile-section-label">Social Media Links</div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <?php
                    $socialMap = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'twitter' => 'Twitter / X', 'youtube' => 'YouTube'];
                    foreach ($socialMap as $sk => $sl):
                    ?>
                    <div>
                        <label class="profile-hint" style="font-weight:600; margin-bottom:4px; display:block;"><?php echo htmlspecialchars($sl, ENT_QUOTES); ?></label>
                        <input type="url" id="f_social_<?php echo $sk; ?>" class="profile-input" placeholder="https://..." value="<?php echo htmlspecialchars($_social_links[$sk] ?? '', ENT_QUOTES); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            {{-- Key Dates (institution-level) – structured entry editor --}}
            <div class="profile-section-card courses-section-card">
                <div class="courses-section-header">
                    <div>
                        <div class="profile-section-label" style="margin-bottom:4px;">Key Dates &amp; Intake Information</div>
                        <p class="profile-hint" style="margin:0;">Add one entry per intake period (e.g. Semester 1, Semester 2). Label it and list all key dates underneath.</p>
                    </div>
                    <button type="button" class="btn-add-course" onclick="addDateEntry()">+ Add Entry</button>
                </div>
                <div id="date-entries-list"></div>
                <div id="date-entries-empty" class="courses-empty-state">
                    <div style="font-size:2em; margin-bottom:8px;">&#128197;</div>
                    <div style="font-weight:600; margin-bottom:4px;">No date entries yet</div>
                    <div style="color:#999;">Click <strong>+ Add Entry</strong> to add key dates, or use <strong>Extract with AI</strong> to auto-fill.</div>
                </div>
            </div>

            {{-- Courses (per-course structured data) --}}
            <div class="profile-section-card courses-section-card">
                <div class="courses-section-header">
                    <div>
                        <div class="profile-section-label" style="margin-bottom:4px;">Courses Offered</div>
                        <p class="profile-hint" style="margin:0;">Add each course individually. Set the course details, requirements, fees and scholarships per course.</p>
                    </div>
                    <button type="button" class="btn-add-course" onclick="addCourse()">+ Add Course</button>
                </div>
                <div id="courses-list"></div>
                <div id="courses-empty" class="courses-empty-state">
                    <div style="font-size:2em; margin-bottom:8px;">&#128218;</div>
                    <div style="font-weight:600; margin-bottom:4px;">No courses added yet</div>
                    <div style="color:#999;">Click <strong>+ Add Course</strong> to add your first course, or use <strong>Extract with AI</strong> to auto-fill from your website.</div>
                </div>
            </div>

            <div style="margin-top:28px; display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                <button type="button" class="btn-primary" onclick="saveProfile()">Save Profile</button>
                <a href="<?php echo $_page_base_url . '/account/profile?uid=' . ($_page_data['proxy_member_id'] ?? '') . '&preview=1'; ?>" target="_blank" style="display:inline-flex; align-items:center; gap:6px; padding:10px 18px; border:1.5px solid #1a73e8; color:#1a73e8; border-radius:8px; font-size:0.9em; font-weight:600; text-decoration:none; background:#fff;">&#128065; Preview Public Profile</a>
                <span id="save-status" style="font-size:0.9em; color:#27ae60;"></span>
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

</div><!-- /.inner-panel -->
</div><!-- /.ihp-content-area -->
</div><!-- /.ihp-outer-wrap -->

{{-- Styles moved to public/asset/css/web/institution_hub_profile.css --}}

<script>
var _scrape_url      = '<?php echo $_page_base_url.'/institution_hub_profile/scrape'; ?>';
var _save_url        = '<?php echo $_page_base_url.'/institution_hub_profile/save'; ?>';
var _gen_logo_url    = '<?php echo $_page_base_url.'/institution_hub_profile/generate_logo'; ?>';
var _apply_logo_url  = '<?php echo $_page_base_url.'/institution_hub_profile/apply_logo'; ?>';
var _upload_logo_url = '<?php echo $_page_base_url.'/institution_hub_profile/upload_logo'; ?>';
var _generate_course_url = '<?php echo $_page_base_url.'/institution_hub_profile/generate_course'; ?>';
var _upload_gallery_url  = '<?php echo $_page_base_url.'/institution_hub_profile/upload_gallery'; ?>';
var _delete_gallery_url  = '<?php echo $_page_base_url.'/institution_hub_profile/delete_gallery_photo'; ?>';
var _csrf_token      = '<?php echo csrf_token(); ?>';
var _pending_logo_file = null;

// Progress step cycling for the loading panel
var _loadingStepInterval = null;
var _loadingStepIndex    = 0;
var _loadingSteps = [
    { id: 'ls-1', label: 'Searching homepage' },
    { id: 'ls-2', label: 'Searching programs &amp; courses' },
    { id: 'ls-3', label: 'Searching tuition fees' },
    { id: 'ls-4', label: 'Searching admission requirements' },
    { id: 'ls-5', label: 'Searching key dates &amp; deadlines' },
    { id: 'ls-6', label: 'Searching scholarships &amp; living costs' },
];
var _loadingMessages = [
    'Visiting institution homepage…',
    'Scanning programs and courses…',
    'Checking current tuition fees…',
    'Reading admission requirements…',
    'Gathering key dates and deadlines…',
    'Compiling scholarship information…',
    'Building your complete profile…',
    'Almost done — processing results…',
];
function _startLoadingProgress() {
    _loadingStepIndex = 0;
    // Reset all step indicators
    _loadingSteps.forEach(function(s) {
        var el = document.getElementById(s.id);
        if (el) { el.innerHTML = '&#x25CB; ' + s.label; el.style.color = '#aaa'; }
    });
    document.getElementById('loading-status').textContent = _loadingMessages[0];
    var msgIdx = 0;
    _loadingStepInterval = setInterval(function() {
        // Tick the current step to "done"
        var stepEl = document.getElementById(_loadingSteps[_loadingStepIndex] ? _loadingSteps[_loadingStepIndex].id : '');
        if (stepEl) { stepEl.innerHTML = '&#x2714; ' + _loadingSteps[_loadingStepIndex].label; stepEl.style.color = '#4caf50'; }
        _loadingStepIndex = Math.min(_loadingStepIndex + 1, _loadingSteps.length - 1);
        // Advance the status message
        msgIdx = (msgIdx + 1) % _loadingMessages.length;
        document.getElementById('loading-status').textContent = _loadingMessages[msgIdx];
    }, 22000); // ~22s per search step (6 searches over ~130s)
}
function _stopLoadingProgress() {
    if (_loadingStepInterval) { clearInterval(_loadingStepInterval); _loadingStepInterval = null; }
}

function extractProfile() {
    var url = document.getElementById('website_url_input').value.trim();
    if (!url) {
        alert('Please enter your institution website URL.');
        return;
    }

    document.getElementById('profile-url-step').style.display  = 'none';
    document.getElementById('profile-loading').style.display   = 'block';
    document.getElementById('profile-sections').style.display  = 'none';
    _startLoadingProgress();

    jQuery.ajax({
        url: _scrape_url,
        type: 'POST',
        data: { website_url: url, _token: _csrf_token },
        timeout: 310000,
        success: function(resp) {
            _stopLoadingProgress();
            document.getElementById('profile-loading').style.display = 'none';
            if (resp && resp.status === 200 && resp.data) {
                fillSections(resp.data);
                document.getElementById('profile-sections').style.display = 'block';
                document.getElementById('profile_website_url').value = url;
                // Show notice if all extracted fields are empty
                var allEmpty = ['institute_name','programs','admission','fees','summary'].every(function(f){ return !resp.data[f]; }) && !resp.data['key_dates'];
                if (allEmpty) {
                    alert('AI could not extract data from this website. Please fill in the sections manually.');
                }
            } else {
                alert('Could not extract profile data. Please fill in the sections manually.');
                document.getElementById('profile-sections').style.display = 'block';
                document.getElementById('profile-url-step').style.display = 'block';
            }
        },
        error: function(xhr, status) {
            _stopLoadingProgress();
            document.getElementById('profile-loading').style.display = 'none';
            var msg = (status === 'timeout')
                ? 'AI extraction timed out (the website may be very large). Please try again or fill in the sections manually.'
                : 'AI extraction failed. Please try again or fill in the sections manually.';
            alert(msg);
            document.getElementById('profile-sections').style.display = 'block';
            document.getElementById('profile-url-step').style.display  = 'block';
        }
    });
}

// ── Key Dates data (seeded from PHP) ───────────────────────────────
var _dateEntries = <?php echo json_encode(array_values($_key_dates_blocks)); ?>;

function renderDateEntries() {
    var list  = document.getElementById('date-entries-list');
    var empty = document.getElementById('date-entries-empty');
    if (!list) return;
    list.innerHTML = '';
    if (_dateEntries.length === 0) {
        if (empty) empty.style.display = 'block';
        return;
    }
    if (empty) empty.style.display = 'none';
    _dateEntries.forEach(function(entry, i) {
        var card = document.createElement('div');
        card.className = 'course-edit-card';
        card.id = 'date-entry-card-' + i;
        card.innerHTML =
            '<div class="course-card-header">' +
                '<span class="course-card-num">Entry ' + (i + 1) + '</span>' +
                '<button type="button" class="course-delete-btn" onclick="removeDateEntry(' + i + ')">&#x2715; Remove</button>' +
            '</div>' +
            '<div style="padding:0 18px 16px;">' +
                '<input type="text" id="date_entry_' + i + '_title" class="course-name-input" ' +
                    'placeholder="Period label — e.g. Semester 1 2026" ' +
                    'value="' + _esc(entry.title || '') + '">' +
                '<textarea id="date_entry_' + i + '_details" class="course-textarea" ' +
                    'placeholder="List the key dates for this period:\n- Application opens: 1 Sep\n- Enrolment closes: 19 Feb\n- Teaching starts: 23 Feb" ' +
                    'style="min-height:90px; margin-top:10px;">' + _esc(entry.details || '') + '</textarea>' +
            '</div>';
        list.appendChild(card);
        // auto-grow the textarea
        var ta = document.getElementById('date_entry_' + i + '_details');
        if (ta) {
            function makeGrow(t) { return function() { t.style.height='auto'; t.style.height=(t.scrollHeight+4)+'px'; }; }
            var grow = makeGrow(ta);
            ta.addEventListener('input', grow);
            grow();
        }
    });
}

function addDateEntry() {
    // Persist current DOM values back to _dateEntries
    _dateEntries = _dateEntries.map(function(e, i) {
        return {
            title:   (document.getElementById('date_entry_' + i + '_title')   || {}).value || e.title,
            details: (document.getElementById('date_entry_' + i + '_details') || {}).value || e.details,
        };
    });
    _dateEntries.push({ title: '', details: '' });
    renderDateEntries();
    // Focus the new title input
    var newTitle = document.getElementById('date_entry_' + (_dateEntries.length - 1) + '_title');
    if (newTitle) newTitle.focus();
}

function removeDateEntry(idx) {
    if (!confirm('Remove this date entry?')) return;
    _dateEntries = _dateEntries.map(function(e, i) {
        return {
            title:   (document.getElementById('date_entry_' + i + '_title')   || {}).value || e.title,
            details: (document.getElementById('date_entry_' + i + '_details') || {}).value || e.details,
        };
    });
    _dateEntries.splice(idx, 1);
    renderDateEntries();
}

function _collectDateEntries() {
    return _dateEntries.map(function(e, i) {
        var title   = ((document.getElementById('date_entry_' + i + '_title')   || {}).value || e.title   || '').trim();
        var details = ((document.getElementById('date_entry_' + i + '_details') || {}).value || e.details || '').trim();
        if (!title && !details) return '';
        return title ? (title + ':\n' + details) : details;
    }).filter(Boolean).join('\n\n');
}

// ── Courses data (seeded from PHP) ──────────────────────────────────
var _courses = <?php echo json_encode(array_values($_courses_arr)); ?>;
var _courseTabState = {};

function _esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function _fid(idx, field) { return 'course_' + idx + '_' + field; }

function _readCourse(idx) {
    var fields = ['name','code','cricos','delivery','duration','entry','overview',
                  'req_academic','req_ielts','req_pte','req_toefl','req_cambridge','req_duolingo','req_documents','req_notes',
                  'fee_tuition','fee_application','fee_oshc','fee_living','fee_notes',
                  'scholarships'];
    var course = {};
    fields.forEach(function(f) {
        var el = document.getElementById(_fid(idx, f));
        course[f] = el ? el.value : (_courses[idx] ? (_courses[idx][f] || '') : '');
    });
    return course;
}

function renderCourses() {
    var list = document.getElementById('courses-list');
    var empty = document.getElementById('courses-empty');
    if (!list) return;
    list.innerHTML = '';
    if (_courses.length === 0) {
        empty.style.display = 'block';
        return;
    }
    empty.style.display = 'none';
    var tabs = [
        { key:'overview',     label:'What to Expect' },
        { key:'requirements', label:'Requirements' },
        { key:'fees',         label:'Fees' },
        { key:'scholarships', label:'Scholarships' }
    ];
    _courses.forEach(function(course, idx) {
        var activeTab = _courseTabState[idx] || 'overview';
        var tabsNav = tabs.map(function(t) {
            return '<button type="button" class="course-tab-btn' + (activeTab === t.key ? ' active' : '') +
                '" onclick="switchCourseTab(' + idx + ',\'' + t.key + '\')">' + t.label + '</button>';
        }).join('');

        // What to Expect tab
        var overviewTab = '<div id="tab-' + idx + '-overview" class="course-tab-content" style="' + (activeTab === 'overview' ? '' : 'display:none;') + '">' +
            '<textarea id="' + _fid(idx,'overview') + '" class="profile-textarea course-textarea" rows="7" ' +
                'placeholder="What students can expect — curriculum, learning outcomes, career paths...">' +
            _esc(course.overview || '') + '</textarea></div>';

        // Requirements tab — structured fields
        var reqTab = '<div id="tab-' + idx + '-requirements" class="course-tab-content" style="' + (activeTab === 'requirements' ? '' : 'display:none;') + '">' +
            '<div class="req-section">' +
                '<div class="req-field-row">' +
                    '<label class="req-label">Academic Entry Requirement</label>' +
                    '<input type="text" id="' + _fid(idx,'req_academic') + '" class="profile-input req-full-input" ' +
                        'placeholder="e.g. Year 12 equivalent, ATAR 65+ or AQF Diploma" value="' + _esc(course.req_academic||'') + '">' +
                '</div>' +
                '<div class="req-section-header">English Proficiency</div>' +
                '<div class="req-scores-grid">' +
                    '<div class="req-score-cell"><label>IELTS Academic</label>' +
                        '<input type="text" id="' + _fid(idx,'req_ielts') + '" class="profile-input" ' +
                        'placeholder="e.g. 6.5 (no band below 6.0)" value="' + _esc(course.req_ielts||'') + '"></div>' +
                    '<div class="req-score-cell"><label>PTE Academic</label>' +
                        '<input type="text" id="' + _fid(idx,'req_pte') + '" class="profile-input" ' +
                        'placeholder="e.g. 58 (no score below 50)" value="' + _esc(course.req_pte||'') + '"></div>' +
                    '<div class="req-score-cell"><label>TOEFL iBT</label>' +
                        '<input type="text" id="' + _fid(idx,'req_toefl') + '" class="profile-input" ' +
                        'placeholder="e.g. 79 (writing 21)" value="' + _esc(course.req_toefl||'') + '"></div>' +
                    '<div class="req-score-cell"><label>Cambridge English</label>' +
                        '<input type="text" id="' + _fid(idx,'req_cambridge') + '" class="profile-input" ' +
                        'placeholder="e.g. 176 overall" value="' + _esc(course.req_cambridge||'') + '"></div>' +
                    '<div class="req-score-cell"><label>Duolingo English</label>' +
                        '<input type="text" id="' + _fid(idx,'req_duolingo') + '" class="profile-input" ' +
                        'placeholder="e.g. 105" value="' + _esc(course.req_duolingo||'') + '"></div>' +
                '</div>' +
                '<div class="req-field-row">' +
                    '<label class="req-label">Required Documents</label>' +
                    '<textarea id="' + _fid(idx,'req_documents') + '" class="profile-textarea course-textarea" rows="5" ' +
                        'placeholder="List required application documents:&#10;- Certified academic transcripts&#10;- English test results&#10;- Passport copy&#10;- Statement of purpose (if required)">' +
                    _esc(course.req_documents||'') + '</textarea>' +
                '</div>' +
                '<div class="req-field-row">' +
                    '<label class="req-label">Other Requirements</label>' +
                    '<textarea id="' + _fid(idx,'req_notes') + '" class="profile-textarea course-textarea" rows="3" ' +
                        'placeholder="Work experience, portfolio, interview, RPL, age requirements, licensing...">' +
                    _esc(course.req_notes||'') + '</textarea>' +
                '</div>' +
            '</div>' +
        '</div>';

        // Fees tab — structured fields
        var feesTab = '<div id="tab-' + idx + '-fees" class="course-tab-content" style="' + (activeTab === 'fees' ? '' : 'display:none;') + '">' +
            '<div class="fee-section">' +
                '<div class="req-field-row">' +
                    '<label class="req-label">Annual Tuition (International)</label>' +
                    '<input type="text" id="' + _fid(idx,'fee_tuition') + '" class="profile-input req-full-input" ' +
                        'placeholder="e.g. AUD 29,800 per year (2026)" value="' + _esc(course.fee_tuition||'') + '">' +
                '</div>' +
                '<div class="fee-two-col">' +
                    '<div class="req-field-row">' +
                        '<label class="req-label">Application / Enrolment Fee</label>' +
                        '<input type="text" id="' + _fid(idx,'fee_application') + '" class="profile-input" ' +
                            'placeholder="e.g. None or AUD 100" value="' + _esc(course.fee_application||'') + '">' +
                    '</div>' +
                    '<div class="req-field-row">' +
                        '<label class="req-label">OSHC (Health Cover / year)</label>' +
                        '<input type="text" id="' + _fid(idx,'fee_oshc') + '" class="profile-input" ' +
                            'placeholder="e.g. ~AUD 720/year single cover" value="' + _esc(course.fee_oshc||'') + '">' +
                    '</div>' +
                '</div>' +
                '<div class="req-field-row">' +
                    '<label class="req-label">Estimated Living Cost / year</label>' +
                    '<input type="text" id="' + _fid(idx,'fee_living') + '" class="profile-input req-full-input" ' +
                        'placeholder="e.g. AUD 29,710/year (Dept. of Home Affairs guide)" value="' + _esc(course.fee_living||'') + '">' +
                '</div>' +
                '<div class="req-field-row">' +
                    '<label class="req-label">Payment Notes</label>' +
                    '<textarea id="' + _fid(idx,'fee_notes') + '" class="profile-textarea course-textarea" rows="3" ' +
                        'placeholder="Payment schedule, refund policy, instalment options...">' +
                    _esc(course.fee_notes||'') + '</textarea>' +
                '</div>' +
            '</div>' +
        '</div>';

        // Scholarships tab
        var scholTab = '<div id="tab-' + idx + '-scholarships" class="course-tab-content" style="' + (activeTab === 'scholarships' ? '' : 'display:none;') + '">' +
            '<textarea id="' + _fid(idx,'scholarships') + '" class="profile-textarea course-textarea" rows="7" ' +
                'placeholder="Available scholarships, bursaries, grants and how to apply...">' +
            _esc(course.scholarships || '') + '</textarea></div>';

        var card = document.createElement('div');
        card.className = 'course-edit-card';
        card.id = 'course-card-' + idx;
        card.innerHTML =
            '<div class="course-card-header">' +
                '<span class="course-card-num">' + (idx + 1) + '</span>' +
                '<input type="text" id="' + _fid(idx,'name') + '" class="course-name-input" placeholder="Course name — e.g. Bachelor of Arts" value="' + _esc(course.name || '') + '">' +
                '<button type="button" id="course-gen-btn-' + idx + '" class="btn-generate-course" onclick="generateCourseDetails(' + idx + ')" title="Generate all course details with AI">&#x1F916; Generate with AI</button>' +
                '<button type="button" class="course-delete-btn" onclick="removeCourse(' + idx + ')" title="Remove course">&#x2715;</button>' +
            '</div>' +
            '<div class="course-details-row">' +
                '<div class="course-detail-field"><label>Course Code</label><input type="text" id="' + _fid(idx,'code') + '" class="profile-input course-detail-input" placeholder="e.g. BA01" value="' + _esc(course.code || '') + '"></div>' +
                '<div class="course-detail-field"><label>CRICOS Code</label><input type="text" id="' + _fid(idx,'cricos') + '" class="profile-input course-detail-input" placeholder="e.g. 003491G" value="' + _esc(course.cricos || '') + '"></div>' +
                '<div class="course-detail-field"><label>Delivery</label><input type="text" id="' + _fid(idx,'delivery') + '" class="profile-input course-detail-input" placeholder="e.g. On-campus, Darwin" value="' + _esc(course.delivery || '') + '"></div>' +
                '<div class="course-detail-field"><label>Duration</label><input type="text" id="' + _fid(idx,'duration') + '" class="profile-input course-detail-input" placeholder="e.g. 3 years full-time" value="' + _esc(course.duration || '') + '"></div>' +
                '<div class="course-detail-field"><label>Entry</label><input type="text" id="' + _fid(idx,'entry') + '" class="profile-input course-detail-input" placeholder="e.g. February, July" value="' + _esc(course.entry || '') + '"></div>' +
            '</div>' +
            '<div class="course-tabs-nav">' + tabsNav + '</div>' +
            '<div class="course-tabs-body">' + overviewTab + reqTab + feesTab + scholTab + '</div>';
        list.appendChild(card);
    });
}

function switchCourseTab(idx, tabKey) {
    _courseTabState[idx] = tabKey;
    ['overview','requirements','fees','scholarships'].forEach(function(t) {
        var content = document.getElementById('tab-' + idx + '-' + t);
        var btn = document.querySelector('#course-card-' + idx + ' .course-tab-btn[onclick*="\'' + t + '\'"]');
        if (content) content.style.display = (t === tabKey) ? '' : 'none';
        if (btn) { btn.classList.toggle('active', t === tabKey); }
    });
}

function generateCourseDetails(idx) {
    var nameEl = document.getElementById(_fid(idx, 'name'));
    var courseName = nameEl ? nameEl.value.trim() : '';
    if (!courseName) { alert('Please enter a course name first, then click Generate with AI.'); return; }
    var institutionName = ((document.getElementById('f_institute_name') || {}).value || '').trim();
    var websiteUrl      = ((document.getElementById('profile_website_url') || {}).value || '').trim();
    if (!institutionName && !websiteUrl) { alert('Please enter the institution name or website URL in the profile above first.'); return; }
    var btn = document.getElementById('course-gen-btn-' + idx);
    if (btn) { btn.disabled = true; btn.textContent = '\u23F3 Generating...'; }
    jQuery.ajax({
        url: _generate_course_url,
        type: 'POST',
        data: { _token: _csrf_token, institution_name: institutionName, website_url: websiteUrl, course_name: courseName },
        timeout: 150000,
        success: function(resp) {
            if (btn) { btn.disabled = false; btn.innerHTML = '&#x1F916; Generate with AI'; }
            if (resp && resp.status === 200 && resp.data) {
                var d = resp.data;
                var fillField = function(field, val) {
                    if (!val) return;
                    var el = document.getElementById(_fid(idx, field));
                    if (!el) return;
                    el.value = val;
                    if (el.tagName === 'TEXTAREA') { el.style.height = 'auto'; el.style.height = (el.scrollHeight + 4) + 'px'; }
                };
                ['code','cricos','delivery','duration','entry','overview',
                 'req_academic','req_ielts','req_pte','req_toefl','req_cambridge','req_duolingo','req_documents','req_notes',
                 'fee_tuition','fee_application','fee_oshc','fee_living','fee_notes','scholarships'].forEach(function(f) { fillField(f, d[f]); });
            } else {
                alert('AI generation failed. Please try again.');
            }
        },
        error: function() {
            if (btn) { btn.disabled = false; btn.innerHTML = '&#x1F916; Generate with AI'; }
            alert('AI generation timed out or failed. Please try again.');
        }
    });
}

function addCourse() {
    // Save current DOM values first
    _courses = _courses.map(function(c, i) { return _readCourse(i); });
    _courses.push({name:'',code:'',cricos:'',delivery:'',duration:'',entry:'',overview:'',
                   req_academic:'',req_ielts:'',req_pte:'',req_toefl:'',req_cambridge:'',req_duolingo:'',
                   req_documents:'',req_notes:'',
                   fee_tuition:'',fee_application:'',fee_oshc:'',fee_living:'',fee_notes:'',
                   scholarships:''});
    _courseTabState[_courses.length - 1] = 'overview';
    renderCourses();
    var newCard = document.getElementById('course-card-' + (_courses.length - 1));
    if (newCard) newCard.scrollIntoView({ behavior:'smooth', block:'start' });
    var nameInput = document.getElementById(_fid(_courses.length - 1, 'name'));
    if (nameInput) setTimeout(function(){ nameInput.focus(); }, 100);
}

function removeCourse(idx) {
    if (!confirm('Remove this course?')) return;
    _courses = _courses.map(function(c, i) { return _readCourse(i); });
    _courses.splice(idx, 1);
    var newTabState = {};
    Object.keys(_courseTabState).forEach(function(k) {
        var n = parseInt(k);
        if (n < idx) newTabState[n] = _courseTabState[k];
        else if (n > idx) newTabState[n - 1] = _courseTabState[k];
    });
    _courseTabState = newTabState;
    renderCourses();
}

function _parseProgramsText(text) {
    if (!text) return [];
    var kw = /\b(bachelor|master|doctor|phd|certificate|diploma|graduate|postgraduate|associate|honours|honors|juris|bsc|ba|llb|bcom|mba|meng|msc|med)\b/i;
    return text.split('\n').map(function(line) {
        return line.trim().replace(/^[\-\u2022\*\u2013\u2014\d\.]+\s*/, '').trim();
    }).filter(function(n) {
        if (n.length <= 4 || n.length >= 120) return false;
        if (!kw.test(n)) return false;
        // Exclude section headings: lines that end with ':' and contain no lowercase letters (e.g. "CERTIFICATE III:", "DIPLOMA:")
        if (/:\s*$/.test(n) && !/[a-z]/.test(n)) return false;
        // Exclude very short heading-style lines (all caps + colon)
        if (/^[A-Z\s\-IV]+:\s*$/.test(n)) return false;
        return true;
    })
    .filter(function(n, i, a) { return a.indexOf(n) === i; }).slice(0, 40);
}

function fillSections(data) {
    // Core text fields
    ['institute_name','summary','description','mission_statement',
     'city','phone','address','annual_fees_range','school_phases',
     'prospectus_url','banner_image','academic_year','student_teacher_ratio',
     'cost_of_living','registration_number','intakes','visa_requirements'].forEach(function(f) {
        var el = document.getElementById('f_' + f);
        if (el && data[f] !== undefined) {
            el.value = data[f] || '';
            if (el.tagName === 'TEXTAREA') { el.style.height = 'auto'; el.style.height = (el.scrollHeight + 4) + 'px'; }
        }
    });
    // Also handle institution_category if AI returned one
    if (data.institution_category) {
        var catEl = document.getElementById('f_institution_category');
        if (catEl) catEl.value = data.institution_category;
    }
    // JSON array fields → comma-separated text in inputs
    var jsonArrayFields = ['curriculum','exam_boards','qualifications_awarded',
                           'language_of_instruction','school_qualities'];
    jsonArrayFields.forEach(function(f) {
        var el = document.getElementById('f_' + f);
        if (!el) return;
        var val = data[f + '_json'] || data[f];
        if (Array.isArray(val)) {
            el.value = val.join(', ');
        } else if (typeof val === 'string' && val) {
            try { var p = JSON.parse(val); el.value = Array.isArray(p) ? p.join(', ') : val; }
            catch(e) { el.value = val; }
        }
    });
    // Bool / checkbox fields
    ['has_boarding','has_school_bus','has_scholarships',
     'has_chinese_language_support','has_extra_languages'].forEach(function(f) {
        var el = document.getElementById('f_' + f);
        if (el) el.checked = (data[f] == 1 || data[f] === true);
    });
    // Social links
    var slData = data.social_links_json || data.social_links;
    if (slData) {
        if (typeof slData === 'string') { try { slData = JSON.parse(slData); } catch(e) { slData = {}; } }
        ['facebook','instagram','linkedin','twitter','youtube'].forEach(function(p) {
            var el = document.getElementById('f_social_' + p);
            if (el && slData[p]) el.value = slData[p];
        });
    }
    // Parse key_dates from AI into date entries
    if (data.key_dates) {
        var blocks = data.key_dates.split(/\n\s*\n/);
        _dateEntries = blocks.map(function(block) {
            block = block.trim();
            if (!block) return null;
            var lines = block.split('\n');
            return {
                title:   lines[0].replace(/:+$/, '').trim(),
                details: lines.slice(1).join('\n').trim(),
            };
        }).filter(Boolean);
        renderDateEntries();
    }
    // Parse programs text → course names (structured fields start empty, use "Generate with AI" per course)
    if (data.programs) {
        var names = _parseProgramsText(data.programs);
        if (names.length > 0) {
            _courses = names.map(function(name) {
                return { name:name, code:'', cricos:'', delivery:'', duration:'', entry:'', overview:'',
                         req_academic:'', req_ielts:'', req_pte:'', req_toefl:'', req_cambridge:'', req_duolingo:'',
                         req_documents:'', req_notes:'',
                         fee_tuition:'', fee_application:'', fee_oshc:'', fee_living:'', fee_notes:'',
                         scholarships:'' };
            });
            _courseTabState = {};
            renderCourses();
        }
    }
    // Gallery photos returned by scrape (Call D)
    if (Array.isArray(data.gallery) && data.gallery.length > 0) {
        var grid = document.getElementById('gallery-grid');
        var emptyMsg = document.getElementById('gallery-empty-msg');
        if (grid) {
            data.gallery.forEach(function(fname) {
                var src = 'upload/inst_gallery/' + fname;
                var wrap = document.createElement('div');
                wrap.className = 'gallery-thumb-wrap';
                wrap.id = 'gwrap-' + fname;
                wrap.innerHTML = '<img src="' + src + '" class="gallery-thumb-img" alt="Gallery photo">' +
                    '<button type="button" class="gallery-thumb-del" onclick="deleteGalleryPhoto(\'' + fname.replace(/'/g,'\\\'') + '\')" title="Delete photo">&times;</button>';
                grid.appendChild(wrap);
            });
            if (emptyMsg) emptyMsg.style.display = 'none';
        }
    }
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
        data:    {
            website_url:    websiteUrl,
            institute_name: (document.getElementById('f_institute_name') || {}).value || '',
            _token:         _csrf_token
        },
        timeout: 150000,
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
                if (resp.warning) {
                    statusEl.style.color = '#e67e22';
                    statusEl.textContent = resp.warning;
                } else {
                    statusEl.style.color = '#888';
                    statusEl.textContent = 'Not applied yet — click "Use this logo" to save.';
                }
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

function uploadLogoManually() {
    var input     = document.getElementById('logo-manual-input');
    var statusEl  = document.getElementById('logo-upload-status');
    var btn       = document.getElementById('btn-upload-logo');
    var previewImg  = document.getElementById('logo-preview-img');
    var placeholder = document.getElementById('logo-preview-placeholder');

    if (!input || !input.files || !input.files.length) {
        alert('Please choose a logo file first.');
        return;
    }
    var file = input.files[0];
    var ext = file.name.split('.').pop().toLowerCase();
    var allowed = ['png','jpg','jpeg','gif','webp','svg'];
    if (allowed.indexOf(ext) === -1) {
        alert('Unsupported file type. Please use PNG, JPG, GIF, WEBP, or SVG.');
        return;
    }

    var formData = new FormData();
    formData.append('logo_file', file);
    formData.append('_token', _csrf_token);

    btn.disabled = true;
    btn.textContent = 'Uploading...';
    statusEl.textContent = '';

    jQuery.ajax({
        url:         _upload_logo_url,
        type:        'POST',
        data:        formData,
        processData: false,
        contentType: false,
        timeout:     30000,
        success: function(resp) {
            btn.disabled    = false;
            btn.textContent = 'Upload';
            if (resp && resp.status === 200) {
                // Show preview + trigger apply
                _pending_logo_file = resp.logo_file;
                if (placeholder) placeholder.style.display = 'none';
                previewImg.src          = resp.logo_url + '?t=' + Date.now();
                previewImg.style.display = 'block';
                previewImg.style.opacity = '1';
                // Auto-apply immediately
                jQuery.ajax({
                    url: _apply_logo_url, type: 'POST',
                    data: { logo_file: resp.logo_file, _token: _csrf_token },
                    success: function(applyResp) {
                        if (applyResp && applyResp.status === 200) {
                            statusEl.style.color = '#27ae60';
                            statusEl.textContent = 'Logo saved!';
                            _pending_logo_file = null;
                        } else {
                            statusEl.style.color = '#888';
                            statusEl.textContent = 'Uploaded — click "Use this logo" to apply.';
                            document.getElementById('btn-apply-logo').style.display = 'inline-block';
                        }
                    },
                    error: function() {
                        statusEl.style.color = '#888';
                        statusEl.textContent = 'Uploaded — click "Use this logo" to apply.';
                        document.getElementById('btn-apply-logo').style.display = 'inline-block';
                    }
                });
            } else {
                statusEl.style.color = '#e74c3c';
                statusEl.textContent = (resp && resp.message) ? resp.message : 'Upload failed.';
            }
        },
        error: function(xhr) {
            btn.disabled    = false;
            btn.textContent = 'Upload';
            var msg = 'Upload failed.';
            try { var r = JSON.parse(xhr.responseText); if (r.message) msg = r.message; } catch(e) {}
            statusEl.style.color = '#e74c3c';
            statusEl.textContent = msg;
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
    // Collect all course data from DOM before saving
    var courseData = _courses.map(function(c, i) { return _readCourse(i); });
    var formData = { _token: _csrf_token };
    formData['website_url']          = document.getElementById('profile_website_url').value;
    formData['institute_name']       = document.getElementById('f_institute_name').value;
    formData['institution_category'] = document.getElementById('f_institution_category').value;
    formData['summary']              = document.getElementById('f_summary').value;
    formData['key_dates']            = _collectDateEntries();
    formData['courses_json']         = JSON.stringify(courseData);

    // New structured string fields
    ['city','phone','address','annual_fees_range','school_phases',
     'prospectus_url','banner_image','academic_year','student_teacher_ratio',
     'description','mission_statement','cost_of_living','registration_number',
     'intakes','visa_requirements'].forEach(function(f) {
        var el = document.getElementById('f_' + f);
        if (el) formData[f] = el.value;
    });

    // JSON array fields (comma-separated text → JSON array)
    ['curriculum','exam_boards','qualifications_awarded',
     'language_of_instruction','school_qualities'].forEach(function(f) {
        var el = document.getElementById('f_' + f);
        if (!el) return;
        var val = el.value.trim();
        formData[f] = val
            ? JSON.stringify(val.split(',').map(function(s){ return s.trim(); }).filter(Boolean))
            : '[]';
    });

    // Bool fields (checkboxes)
    ['has_boarding','has_school_bus','has_scholarships',
     'has_chinese_language_support','has_extra_languages'].forEach(function(f) {
        var el = document.getElementById('f_' + f);
        formData[f] = (el && el.checked) ? '1' : '0';
    });

    // Social links → single JSON object
    var sl = {};
    ['facebook','instagram','linkedin','twitter','youtube'].forEach(function(p) {
        var el = document.getElementById('f_social_' + p);
        if (el && el.value.trim()) sl[p] = el.value.trim();
    });
    formData['social_links'] = JSON.stringify(sl);

    var btn = document.querySelector('#profile-form .btn-primary');
    var statusEl = document.getElementById('save-status');
    btn.disabled = true;
    btn.textContent = 'Saving...';
    statusEl.textContent = '';
    statusEl.style.color = '#27ae60';

    jQuery.ajax({
        url: _save_url,
        type: 'POST',
        data: formData,
        success: function(resp) {
            btn.disabled = false;
            btn.textContent = 'Save Profile';
            if (resp && resp.status === 200) {
                statusEl.textContent = 'Saved successfully!';
                statusEl.style.color = '#27ae60';
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

// Init course cards on page load
document.addEventListener('DOMContentLoaded', function() {
    renderCourses();
    renderDateEntries();
    // Auto-grow summary textarea
    var summaryTa = document.getElementById('f_summary');
    if (summaryTa) {
        function growSummary() { summaryTa.style.height = 'auto'; summaryTa.style.height = (summaryTa.scrollHeight + 4) + 'px'; }
        summaryTa.addEventListener('input', growSummary);
        growSummary();
    }
});

function uploadGalleryPhoto() {
    var fileInput = document.getElementById('gallery-upload-input');
    var statusEl  = document.getElementById('gallery-upload-status');
    if (!fileInput || !fileInput.files || !fileInput.files[0]) {
        statusEl.style.color = '#e74c3c';
        statusEl.textContent = 'Please choose a photo first.';
        return;
    }
    var file = fileInput.files[0];
    // 8 MB client check
    if (file.size > 8 * 1024 * 1024) {
        statusEl.style.color = '#e74c3c';
        statusEl.textContent = 'File too large (max 8 MB).';
        return;
    }
    statusEl.style.color = '#888';
    statusEl.textContent = 'Uploading...';
    var fd = new FormData();
    fd.append('gallery_photo', file);
    fd.append('_token', _csrf_token);
    jQuery.ajax({
        url: _upload_gallery_url,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function(resp) {
            fileInput.value = '';
            if (resp && resp.status === 200) {
                statusEl.style.color = '#27ae60';
                statusEl.textContent = 'Photo uploaded!';
                _addGalleryThumb(resp.file, resp.url);
                // Remove empty message if present
                var emptyMsg = document.getElementById('gallery-empty-msg');
                if (emptyMsg) emptyMsg.remove();
                setTimeout(function(){ statusEl.textContent = ''; }, 3000);
            } else {
                statusEl.style.color = '#e74c3c';
                statusEl.textContent = (resp && resp.message) ? resp.message : 'Upload failed.';
            }
        },
        error: function(xhr) {
            fileInput.value = '';
            statusEl.style.color = '#e74c3c';
            var msg = 'Upload failed.';
            try { var r = JSON.parse(xhr.responseText); if (r.message) msg = r.message; } catch(e){}
            statusEl.textContent = msg;
        }
    });
}

function _addGalleryThumb(fileName, url) {
    var grid = document.getElementById('gallery-grid');
    var wrap = document.createElement('div');
    wrap.className = 'gallery-thumb-wrap';
    wrap.id = 'gwrap-' + fileName;
    wrap.innerHTML = '<img src="' + url + '" class="gallery-thumb-img" alt="Gallery photo">'
        + '<button class="gallery-thumb-del" onclick="deleteGalleryPhoto(\'' + fileName.replace(/'/g, "\\'") + '\')" title="Delete photo">&times;</button>';
    grid.appendChild(wrap);
}

function deleteGalleryPhoto(fileName) {
    if (!confirm('Remove this photo from your profile?')) return;
    var statusEl = document.getElementById('gallery-upload-status');
    statusEl.style.color = '#888';
    statusEl.textContent = 'Removing...';
    jQuery.ajax({
        url: _delete_gallery_url,
        type: 'POST',
        data: { file: fileName, _token: _csrf_token },
        success: function(resp) {
            if (resp && resp.status === 200) {
                var wrap = document.getElementById('gwrap-' + fileName);
                if (wrap) wrap.remove();
                statusEl.style.color = '#27ae60';
                statusEl.textContent = 'Removed.';
                // Show empty msg if no thumbs left
                var grid = document.getElementById('gallery-grid');
                if (grid && grid.querySelectorAll('.gallery-thumb-wrap').length === 0) {
                    var em = document.createElement('div');
                    em.id = 'gallery-empty-msg';
                    em.style.cssText = 'color:#bbb;font-size:0.9em;padding:12px 0;';
                    em.textContent = 'No photos uploaded yet.';
                    grid.appendChild(em);
                }
                setTimeout(function(){ statusEl.textContent = ''; }, 3000);
            } else {
                statusEl.style.color = '#e74c3c';
                statusEl.textContent = 'Failed to remove photo.';
            }
        },
        error: function() {
            statusEl.style.color = '#e74c3c';
            statusEl.textContent = 'Failed to remove photo.';
        }
    });
}
</script>
<?php endif; // end guest check ?>
@endsection
