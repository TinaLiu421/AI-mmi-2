@extends('web.common')

@push('css')
<link href="/asset/css/web/student_profile.css?v=<?php echo date('Ymd'); ?>" rel="stylesheet">
<style>
main.page-body .page-content { margin-right: 450px !important; max-width: 100% !important; }
main.page-body .info-area {
    width: 100% !important;
    float: none !important;
    background: #0c1445 !important;
    background-image: none !important;
    background-color: #0c1445 !important;
    min-height: 100vh !important;
}
main.page-body .info-area::before { display: none !important; }
body {
    background: #0c1445 !important;
    background-image: none !important;
}
/* Fix: info-area > div { position: relative } in common.css overrides position: fixed */
.sp-modal-bg,
.sp-modal { position: fixed !important; }

/* ── My Profile page layout (sidebar + main) ── */
.mp-layout {
    display: flex;
    min-height: 100vh;
}
.mp-sidebar {
    width: 200px;
    flex-shrink: 0;
    background: linear-gradient(180deg, #0c1445 0%, #1a0533 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 28px 0;
    gap: 6px;
    position: sticky;
    top: var(--header-height, 80px);
    height: calc(100vh - var(--header-height, 80px));
    z-index: 100;
    box-shadow: 2px 0 16px rgba(0,0,0,0.25);
    overflow-y: auto;
}
.mp-sidebar a {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    padding: 12px 8px;
    text-decoration: none;
    color: rgba(255,255,255,0.75);
    border-radius: 14px;
    font-size: 11px;
    font-weight: 600;
    text-align: center;
    line-height: 1.3;
    width: 100%;
    transition: all 0.18s;
    -webkit-tap-highlight-color: transparent;
}
.mp-sidebar a:hover, .mp-sidebar a.active {
    background: rgba(255,255,255,0.14);
    color: #fff;
    text-decoration: none;
}
.mp-sb-icon {
    font-size: 22px;
    width: 46px;
    height: 46px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.1);
    flex-shrink: 0;
    transition: background 0.18s;
}
.mp-sidebar a.active .mp-sb-icon { background: rgba(255,255,255,0.25); }
.mp-sidebar a:hover:not(.active) .mp-sb-icon { background: rgba(255,255,255,0.15); }
.mp-sb-label { font-size: 11px; font-weight: 600; letter-spacing: .02em; text-align: center; line-height: 1.3; }
.mp-main { flex: 1; min-width: 0; }
@media (max-width: 900px) {
    .mp-sidebar { display: none; }
    .mp-main { padding-bottom: 60px; }
}
</style>
@endpush

@section('content')
<?php
// Guest mode — not logged in
if (!empty($_page_data['is_guest'])):
?>
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;color:#fff;text-align:center;padding:40px 20px;">
    <div style="font-size:3rem;margin-bottom:16px;">&#127891;</div>
    <h2 style="font-size:1.6rem;margin-bottom:10px;">My Academic Profile</h2>
    <p style="color:#b0b8d0;margin-bottom:28px;max-width:420px;">Log in to build and manage your academic profile — showcase your qualifications to institutions around the world.</p>
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
$profile      = $_page_data['profile'] ?? null;
$completeness = $_page_data['completeness'] ?? 0;
$member       = $_current_member;

// Safe display name
$displayName = trim($member['alias_name'] ?? '') ?: trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')) ?: 'Your Profile';
$initial     = strtoupper(mb_substr($displayName, 0, 1));

// Decode JSON fields
$eduHistory   = json_decode($profile->education_history ?? '[]', true) ?: [];
$langScores   = json_decode($profile->language_scores   ?? '[]', true) ?: [];
$achievements = json_decode($profile->achievements      ?? '[]', true) ?: [];
$workExp      = json_decode($profile->work_experience   ?? '[]', true) ?: [];
$targetFields = json_decode($profile->target_fields     ?? '[]', true) ?: [];

// Avatar
$avatarHtml = '';
if (!empty($member['avatar'])) {
    $avatarPath = public_path('upload/member_avatar/' . $member['avatar']);
    if (file_exists($avatarPath)) {
        $avatarHtml = '<div class="sp-avatar-img" style="background-image:url(\'upload/member_avatar/' . htmlspecialchars($member['avatar'], ENT_QUOTES) . '\')"  ></div>';
    }
}
if (!$avatarHtml) {
    $avatarHtml = '<div class="sp-avatar-initial">' . htmlspecialchars($initial) . '</div>';
}

// Cover photo
$coverStyle = '';
$coverPhoto = $member['coverphoto'] ?? '';
if (!empty($coverPhoto)) {
    $cpPath = 'upload/member_coverphoto/' . $coverPhoto;
    if (file_exists(public_path($cpPath))) {
        $coverStyle = 'background-image:url(\'/'. $cpPath . '\'); background-size:cover; background-position:center top;';
    }
}

// AutoLang helper (appends ?autolang= to URLs when active)
$autoLang = !empty($_page_get_data['autolang']) ? $_page_get_data['autolang'] : session('autolang', '');
$appendAutoLang = function ($url) use ($autoLang) {
    if (empty($autoLang)) return $url;
    return $url . ((strpos($url, '?') !== false) ? '&' : '?') . 'autolang=' . urlencode($autoLang);
};

// Institution flag — student_profile is only accessible to type=1 (individual) members
$_is_institution = (isset($_current_member['type']) && (int)$_current_member['type'] !== 1);
?>

<div class="mp-layout">

    {{-- ── Study Section Sidebar ── --}}
    <div class="mp-sidebar">
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_plans'), ENT_QUOTES); ?>">
            <span class="mp-sb-icon"><i class="fa fa-star"></i></span>
            <span class="mp-sb-label">Dreams</span>
        </a>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_college_match'), ENT_QUOTES); ?>">
            <span class="mp-sb-icon"><i class="fa fa-graduation-cap"></i></span>
            <span class="mp-sb-label">Matches</span>
        </a>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/nextgen_challenge'), ENT_QUOTES); ?>">
            <span class="mp-sb-icon"><i class="fa fa-trophy"></i></span>
            <span class="mp-sb-label">NextGen AI &amp;<br>Talent Challenge</span>
        </a>
        <?php if (!$_is_institution): ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_explore'), ENT_QUOTES); ?>">
            <span class="mp-sb-icon"><i class="fa fa-building"></i></span>
            <span class="mp-sb-label">Colleges</span>
        </a>
        <?php else: ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/student_explore'), ENT_QUOTES); ?>">
            <span class="mp-sb-icon"><i class="fa fa-users"></i></span>
            <span class="mp-sb-label">Explore Students</span>
        </a>
        <?php endif; ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_is_institution ? $_page_base_url.'/institution_hub_profile' : $_page_base_url.'/student_profile'), ENT_QUOTES); ?>" class="active">
            <span class="mp-sb-icon"><i class="fa fa-id-card"></i></span>
            <span class="mp-sb-label">My Profile</span>
        </a>
        <a href="javascript:void(0);" class="do-toapply" data-sector="study" data-action-url="<?php echo htmlspecialchars($_page_base_url.'/agent_chat', ENT_QUOTES); ?>">
            <?php if (!empty($_current_member) && !empty($_current_member['avatar'])): ?>
            <?php if (file_exists(public_path('upload/member_avatar/'.$_current_member['avatar']))): ?>
            <div class="mp-chat-av" style="background-image:url(upload/member_avatar/<?php echo htmlspecialchars($_current_member['avatar'], ENT_QUOTES); ?>); width:36px;height:36px;border-radius:50%;background-size:cover;background-position:center;"></div>
            <?php else: ?>
            <div class="mp-chat-av" style="width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,0.18);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#fff;"><?php echo htmlspecialchars(mb_substr($_current_member['alias_name'] ?? 'A', 0, 1), ENT_QUOTES); ?></div>
            <?php endif; ?>
            <?php else: ?>
            <div style="width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.12);border:1.5px solid rgba(255,255,255,0.25);"></div>
            <?php endif; ?>
            <span class="mp-sb-label">Chat with<br>AI-mmi</span>
        </a>
    </div>

    <div class="mp-main">
<div class="sp-wrap">

    {{-- ── Toast notification ── --}}
    <div class="sp-toast" id="sp-toast"></div>

    {{-- ── Hero Card ── --}}
    <div class="sp-hero-card">
        <div class="sp-hero-cover" id="sp-cover" style="<?php echo $coverStyle; ?>">
            <label class="sp-cover-edit-btn" title="Change cover photo">
                <i class="fa fa-camera"></i> Edit cover
                <input type="file" id="sp-cover-input" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="spUploadCover(this)">
            </label>
        </div>
        <div class="sp-hero-body">
            <div class="sp-avatar-col">
                <div class="sp-avatar-wrap" id="sp-avatar">
                    <?php echo $avatarHtml; ?>
                    <label class="sp-avatar-overlay" title="Change photo">
                        <i class="fa fa-camera"></i><span>Edit</span>
                        <input type="file" id="sp-avatar-input" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none" onchange="spUploadAvatar(this)">
                    </label>
                </div>
                <label class="sp-avatar-upload-btn">
                    <i class="fa fa-camera"></i> Change Photo
                    <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none" onchange="spUploadAvatar(this)">
                </label>
            </div>
            <div class="sp-hero-info">
                <h1 class="sp-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES); ?></h1>
                <p class="sp-headline" id="sp-headline-text">
                    <?php echo $profile && !empty($profile->headline)
                        ? htmlspecialchars($profile->headline, ENT_QUOTES)
                        : '<span class="sp-placeholder">Add a headline — e.g. "Aspiring Data Scientist from Indonesia"</span>'; ?>
                </p>
                <div class="sp-hero-badges">
                    <?php if (!empty($profile->nationality)): ?>
                    <span class="sp-badge">🌍 <?php echo htmlspecialchars($profile->nationality, ENT_QUOTES); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($profile->current_country)): ?>
                    <span class="sp-badge">📍 <?php echo htmlspecialchars($profile->current_country, ENT_QUOTES); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($profile->target_degree)): ?>
                    <span class="sp-badge sp-badge-blue">🎓 <?php echo htmlspecialchars(ucfirst($profile->target_degree), ENT_QUOTES); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <button class="sp-edit-btn" onclick="spOpenModal('hero')" title="Edit headline & location">
                <i class="fa fa-pencil"></i> Edit
            </button>
        </div>

        {{-- Profile completeness --}}
        <div class="sp-completeness">
            <div class="sp-comp-label">
                <span>Profile completeness</span>
                <span class="sp-comp-pct" id="sp-comp-pct"><?php echo $completeness; ?>%</span>
            </div>
            <div class="sp-comp-bar">
                <div class="sp-comp-fill" id="sp-comp-fill" style="width:<?php echo $completeness; ?>%"></div>
            </div>
            <?php if ($completeness < 60): ?>
            <p class="sp-comp-tip">💡 Complete your profile to be discovered by top universities.</p>
            <?php endif; ?>
        </div>

        {{-- Visibility notice --}}
        <div class="sp-visibility-notice">
            <i class="fa fa-eye"></i>
            Your profile is <strong>visible to all partner universities</strong> on AI-mmi.
        </div>
    </div>

    <!-- Light content area starts here (dark→light transition) -->
    <div class="sp-light-content">

    <div class="sp-body-grid">

        {{-- ── LEFT COLUMN ── --}}
        <div class="sp-col-main">

            {{-- About --}}
            <div class="sp-card" id="section-bio">
                <div class="sp-card-header">
                    <h2><i class="fa fa-user"></i> About</h2>
                    <button class="sp-edit-btn" onclick="spOpenModal('bio')"><i class="fa fa-pencil"></i> Edit</button>
                </div>
                <?php if (!empty($profile->bio)): ?>
                <p class="sp-bio-text"><?php echo nl2br(htmlspecialchars($profile->bio, ENT_QUOTES)); ?></p>
                <?php else: ?>
                <p class="sp-empty-hint">Tell universities about yourself — your background, passion, and goals.</p>
                <?php endif; ?>
            </div>

            {{-- Education History --}}
            <div class="sp-card" id="section-education">
                <div class="sp-card-header">
                    <h2><i class="fa fa-graduation-cap"></i> Education</h2>
                    <button class="sp-edit-btn" onclick="spOpenModal('education')"><i class="fa fa-pencil"></i> Edit</button>
                </div>
                <?php if (!empty($eduHistory)): ?>
                    <?php foreach ($eduHistory as $edu): ?>
                    <div class="sp-item">
                        <?php
                        $eduLogo = $edu['logo_url'] ?? '';
                        if ($eduLogo === '_removed_') $eduLogo = '';
                        $eduLogoHtml = $eduLogo
                            ? '<img src="'.htmlspecialchars($eduLogo, ENT_QUOTES).'" class="sp-item-logo" alt="" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'"><span class="sp-item-icon-fallback" style="display:none">�</span>'
                            : '🏛';
                        ?>
                        <div class="sp-item-icon sp-item-icon-logo"><?php echo $eduLogoHtml; ?></div>
                        <div class="sp-item-body">
                            <div class="sp-item-title"><?php echo htmlspecialchars($edu['degree'] ?? '', ENT_QUOTES); ?> in <?php echo htmlspecialchars($edu['field'] ?? '', ENT_QUOTES); ?></div>
                            <div class="sp-item-sub"><?php echo htmlspecialchars($edu['institution'] ?? '', ENT_QUOTES); ?><?php echo !empty($edu['country']) ? ', ' . htmlspecialchars($edu['country'], ENT_QUOTES) : ''; ?></div>
                            <div class="sp-item-meta">
                                <?php if (!empty($edu['gpa'])): ?><span class="sp-tag">GPA <?php echo htmlspecialchars($edu['gpa'], ENT_QUOTES); ?></span><?php endif; ?>
                                <?php if (!empty($edu['grad_year'])): ?><span class="sp-tag">Class of <?php echo htmlspecialchars($edu['grad_year'], ENT_QUOTES); ?></span><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <p class="sp-empty-hint">Add your education history — degree, institution, GPA, graduation year.</p>
                <?php endif; ?>
            </div>

            {{-- Work & Extracurricular --}}
            <div class="sp-card" id="section-work">
                <div class="sp-card-header">
                    <h2><i class="fa fa-briefcase"></i> Work & Extracurricular</h2>
                    <button class="sp-edit-btn" onclick="spOpenModal('work')"><i class="fa fa-pencil"></i> Edit</button>
                </div>
                <?php if (!empty($workExp)): ?>
                    <?php foreach ($workExp as $w): ?>
                    <div class="sp-item">
                        <?php
                        $workLogo = $w['logo_url'] ?? '';
                        if ($workLogo === '_removed_') $workLogo = '';
                        $workLogoHtml = $workLogo
                            ? '<img src="'.htmlspecialchars($workLogo, ENT_QUOTES).'" class="sp-item-logo" alt="" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'"><span class="sp-item-icon-fallback" style="display:none">💼</span>'
                            : '💼';
                        ?>
                        <div class="sp-item-icon sp-item-icon-logo"><?php echo $workLogoHtml; ?></div>
                        <div class="sp-item-body">
                            <div class="sp-item-title"><?php echo htmlspecialchars($w['title'] ?? '', ENT_QUOTES); ?></div>
                            <div class="sp-item-sub"><?php echo htmlspecialchars($w['company'] ?? '', ENT_QUOTES); ?><?php echo !empty($w['country']) ? ' · ' . htmlspecialchars($w['country'], ENT_QUOTES) : ''; ?></div>
                            <div class="sp-item-meta">
                                <span class="sp-tag"><?php echo htmlspecialchars($w['from'] ?? '', ENT_QUOTES); ?><?php echo !empty($w['to']) ? ' – ' . htmlspecialchars($w['to'], ENT_QUOTES) : (!empty($w['current']) ? ' – Present' : ''); ?></span>
                            </div>
                            <?php if (!empty($w['description'])): ?>
                            <p class="sp-item-desc"><?php echo htmlspecialchars($w['description'], ENT_QUOTES); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <p class="sp-empty-hint">Add internships, part-time jobs, volunteering, and club activities.</p>
                <?php endif; ?>
            </div>

            {{-- Achievements --}}
            <div class="sp-card" id="section-achievements">
                <div class="sp-card-header">
                    <h2><i class="fa fa-trophy"></i> Achievements & Awards</h2>
                    <button class="sp-edit-btn" onclick="spOpenModal('achievements')"><i class="fa fa-pencil"></i> Edit</button>
                </div>
                <?php if (!empty($achievements)): ?>
                    <?php foreach ($achievements as $ach): ?>
                    <div class="sp-item">
                        <div class="sp-item-icon">🏆</div>
                        <div class="sp-item-body">
                            <div class="sp-item-title"><?php echo htmlspecialchars($ach['title'] ?? '', ENT_QUOTES); ?></div>
                            <div class="sp-item-sub"><?php echo htmlspecialchars($ach['issuer'] ?? '', ENT_QUOTES); ?><?php echo !empty($ach['year']) ? ' · ' . htmlspecialchars($ach['year'], ENT_QUOTES) : ''; ?></div>
                            <?php if (!empty($ach['description'])): ?>
                            <p class="sp-item-desc"><?php echo htmlspecialchars($ach['description'], ENT_QUOTES); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <p class="sp-empty-hint">Scholarships, competitions, dean's list, certifications — add them here.</p>
                <?php endif; ?>
            </div>

        </div>

        {{-- ── RIGHT COLUMN ── --}}
        <div class="sp-col-side">

            {{-- Target Study --}}
            <div class="sp-card" id="section-target">
                <div class="sp-card-header">
                    <h2><i class="fa fa-compass"></i> Target Study</h2>
                    <button class="sp-edit-btn" onclick="spOpenModal('target')"><i class="fa fa-pencil"></i> Edit</button>
                </div>
                <?php if (!empty($profile->target_degree) || !empty($targetFields)): ?>
                <div class="sp-target-grid">
                    <?php if (!empty($profile->target_degree)): ?>
                    <div class="sp-target-item">
                        <div class="sp-target-label">Degree Level</div>
                        <div class="sp-target-val"><?php echo htmlspecialchars(ucfirst($profile->target_degree), ENT_QUOTES); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($profile->target_intake_year)): ?>
                    <div class="sp-target-item">
                        <div class="sp-target-label">Intake Year</div>
                        <div class="sp-target-val"><?php echo htmlspecialchars($profile->target_intake_year, ENT_QUOTES); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($profile->budget_usd_max)): ?>
                    <div class="sp-target-item">
                        <div class="sp-target-label">Budget (USD/yr)</div>
                        <div class="sp-target-val">
                            <?php echo !empty($profile->budget_usd_min) ? '$' . number_format($profile->budget_usd_min) . ' – ' : 'Up to '; ?>$<?php echo number_format($profile->budget_usd_max); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($targetFields)): ?>
                <div class="sp-fields-wrap">
                    <?php foreach ($targetFields as $f): ?>
                    <span class="sp-field-tag"><?php echo htmlspecialchars($f, ENT_QUOTES); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <p class="sp-empty-hint">Set your target degree, fields, budget, and intake year.</p>
                <?php endif; ?>
            </div>

            {{-- Language Test Scores --}}
            <div class="sp-card" id="section-language">
                <div class="sp-card-header">
                    <h2><i class="fa fa-language"></i> Language Scores</h2>
                    <button class="sp-edit-btn" onclick="spOpenModal('language')"><i class="fa fa-pencil"></i> Edit</button>
                </div>
                <?php if (!empty($langScores)): ?>
                    <?php foreach ($langScores as $ls): ?>
                    <div class="sp-lang-row">
                        <span class="sp-lang-test"><?php echo htmlspecialchars($ls['test'] ?? '', ENT_QUOTES); ?></span>
                        <span class="sp-lang-score"><?php echo htmlspecialchars($ls['score'] ?? '', ENT_QUOTES); ?></span>
                        <?php if (!empty($ls['date'])): ?>
                        <span class="sp-lang-date"><?php echo htmlspecialchars($ls['date'], ENT_QUOTES); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <p class="sp-empty-hint">Add IELTS, TOEFL, GMAT, GRE, or SAT scores.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

    </div><!-- /.sp-light-content -->

</div>
    </div>{{-- /mp-main --}}
</div>{{-- /mp-layout --}}

{{-- ══════════════════════════════════════════════════════════
     MODALS
     ══════════════════════════════════════════════════════════ --}}
<div class="sp-modal-bg" id="sp-modal-bg" onclick="spCloseModal()"></div>

{{-- Hero Modal --}}
<div class="sp-modal" id="modal-hero">
    <div class="sp-modal-header"><h3>Edit Headline & Location</h3><button onclick="spCloseModal()">✕</button></div>
    <div class="sp-modal-body">
        <label>Headline <span class="sp-char-count" id="cc-headline">0/300</span></label>
        <input type="text" id="f-headline" maxlength="300" placeholder="e.g. Aspiring Data Scientist from Indonesia"
            value="<?php echo htmlspecialchars($profile->headline ?? '', ENT_QUOTES); ?>"
            oninput="document.getElementById('cc-headline').textContent=this.value.length+'/300'">
        <label>Nationality</label>
        <input type="text" id="f-nationality" placeholder="e.g. Indonesian"
            value="<?php echo htmlspecialchars($profile->nationality ?? '', ENT_QUOTES); ?>">
        <label>Current Country</label>
        <input type="text" id="f-current_country" placeholder="e.g. Australia"
            value="<?php echo htmlspecialchars($profile->current_country ?? '', ENT_QUOTES); ?>">
    </div>
    <div class="sp-modal-footer">
        <button class="sp-btn-cancel" onclick="spCloseModal()">Cancel</button>
        <button class="sp-btn-save" onclick="spSave('hero')">Save Changes</button>
    </div>
</div>

{{-- Bio Modal --}}
<div class="sp-modal" id="modal-bio">
    <div class="sp-modal-header"><h3>Edit About</h3><button onclick="spCloseModal()">✕</button></div>
    <div class="sp-modal-body">
        <label>About You <span class="sp-char-count" id="cc-bio">0/2000</span></label>
        <textarea id="f-bio" maxlength="2000" rows="7" placeholder="Tell universities about your background, interests, and study goals..."
            oninput="document.getElementById('cc-bio').textContent=this.value.length+'/2000'"><?php echo htmlspecialchars($profile->bio ?? '', ENT_QUOTES); ?></textarea>
    </div>
    <div class="sp-modal-footer">
        <button class="sp-btn-cancel" onclick="spCloseModal()">Cancel</button>
        <button class="sp-btn-save" onclick="spSave('bio')">Save Changes</button>
    </div>
</div>

{{-- Target Study Modal --}}
<div class="sp-modal" id="modal-target">
    <div class="sp-modal-header"><h3>Edit Target Study</h3><button onclick="spCloseModal()">✕</button></div>
    <div class="sp-modal-body">
        <label>Degree Level</label>
        <select id="f-target_degree">
            <option value="">— Select —</option>
            <?php foreach (['certificate','diploma','bachelor','master','phd'] as $d): ?>
            <option value="<?php echo $d; ?>" <?php echo ($profile->target_degree ?? '') === $d ? 'selected' : ''; ?>><?php echo ucfirst($d); ?></option>
            <?php endforeach; ?>
        </select>
        <label>Fields of Study <span class="sp-hint">(press Enter to add, click × to remove)</span></label>
        <div class="sp-tag-input-wrap" id="f-fields-wrap">
            <?php foreach ($targetFields as $f): ?>
            <span class="sp-field-chip"><?php echo htmlspecialchars($f, ENT_QUOTES); ?><button onclick="spRemoveChip(this)">×</button></span>
            <?php endforeach; ?>
            <input type="text" id="f-field-input" placeholder="e.g. Computer Science" onkeydown="spAddField(event)">
        </div>
        <label>Preferred Intake Year</label>
        <input type="text" id="f-target_intake_year" placeholder="e.g. 2026 Semester 1"
            value="<?php echo htmlspecialchars($profile->target_intake_year ?? '', ENT_QUOTES); ?>">
        <div class="sp-two-col">
            <div>
                <label>Min Budget (USD/yr)</label>
                <input type="number" id="f-budget_min" min="0" placeholder="0" value="<?php echo $profile->budget_usd_min ?? ''; ?>">
            </div>
            <div>
                <label>Max Budget (USD/yr)</label>
                <input type="number" id="f-budget_max" min="0" placeholder="50000" value="<?php echo $profile->budget_usd_max ?? ''; ?>">
            </div>
        </div>
    </div>
    <div class="sp-modal-footer">
        <button class="sp-btn-cancel" onclick="spCloseModal()">Cancel</button>
        <button class="sp-btn-save" onclick="spSave('target')">Save Changes</button>
    </div>
</div>

{{-- Education Modal --}}
<div class="sp-modal sp-modal-lg" id="modal-education">
    <div class="sp-modal-header"><h3>Edit Education History</h3><button onclick="spCloseModal()">✕</button></div>
    <div class="sp-modal-body">
        <div id="edu-entries">
            <?php foreach ($eduHistory as $i => $edu): ?>
            <div class="sp-entry-card" data-idx="<?php echo $i; ?>">
                <button class="sp-entry-remove" onclick="spRemoveEntry(this)">×</button>
                <?php $elu = $edu['logo_url'] ?? ''; if ($elu === '_removed_') $elu = ''; ?>
                <div class="sp-entry-logo-row">
                    <div class="sp-entry-logo-preview">
                        <?php if ($elu): ?>
                        <img src="<?php echo htmlspecialchars($elu, ENT_QUOTES); ?>" class="sp-entry-logo-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <span class="sp-entry-logo-placeholder" style="display:none">�</span>
                        <?php else: ?>
                        <span class="sp-entry-logo-placeholder">🏛</span>
                        <?php endif; ?>
                    </div>
                    <div class="sp-entry-logo-meta">
                        <span class="sp-entry-logo-label">Institution logo <span style="color:#6366f1;font-size:10px;font-weight:700;background:#ede9fe;padding:2px 8px;border-radius:999px;margin-left:4px;">✦ AI auto-generates on save</span></span>
                        <div class="sp-entry-logo-btns">
                            <label class="sp-logo-change-btn">
                                <i class="fa fa-upload"></i> Upload own
                                <input type="file" accept="image/*" style="display:none" onchange="spUploadEntryLogo(this)">
                            </label>
                            <button type="button" class="sp-logo-remove-btn" onclick="spRemoveEntryLogo(this, '🏛')" title="Remove logo">×</button>
                        </div>
                    </div>
                    <input type="hidden" name="logo_url" value="<?php echo htmlspecialchars($elu, ENT_QUOTES); ?>">
                </div>
                <div class="sp-two-col">
                    <div><label>Degree</label><input type="text" name="degree" placeholder="e.g. Bachelor of Science" value="<?php echo htmlspecialchars($edu['degree'] ?? '', ENT_QUOTES); ?>"></div>
                    <div><label>Field of Study</label><input type="text" name="field" placeholder="e.g. Computer Science" value="<?php echo htmlspecialchars($edu['field'] ?? '', ENT_QUOTES); ?>"></div>
                </div>
                <label>Institution Name</label>
                <input type="text" name="institution" placeholder="e.g. University of Indonesia" value="<?php echo htmlspecialchars($edu['institution'] ?? '', ENT_QUOTES); ?>">
                <div class="sp-three-col">
                    <div><label>Country</label><input type="text" name="country" placeholder="Indonesia" value="<?php echo htmlspecialchars($edu['country'] ?? '', ENT_QUOTES); ?>"></div>
                    <div><label>GPA</label><input type="text" name="gpa" placeholder="3.8/4.0" value="<?php echo htmlspecialchars($edu['gpa'] ?? '', ENT_QUOTES); ?>"></div>
                    <div><label>Graduation Year</label><input type="text" name="grad_year" placeholder="2024" value="<?php echo htmlspecialchars($edu['grad_year'] ?? '', ENT_QUOTES); ?>"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="sp-add-btn" onclick="spAddEduEntry()"><i class="fa fa-plus"></i> Add Education</button>
    </div>
    <div class="sp-modal-footer">
        <button class="sp-btn-cancel" onclick="spCloseModal()">Cancel</button>
        <button class="sp-btn-save" onclick="spSave('education')">Save Changes</button>
    </div>
</div>

{{-- Language Modal --}}
<div class="sp-modal" id="modal-language">
    <div class="sp-modal-header"><h3>Edit Language Test Scores</h3><button onclick="spCloseModal()">✕</button></div>
    <div class="sp-modal-body">
        <div id="lang-entries">
            <?php foreach ($langScores as $ls): ?>
            <div class="sp-entry-card">
                <button class="sp-entry-remove" onclick="spRemoveEntry(this)">×</button>
                <div class="sp-three-col">
                    <div>
                        <label>Test</label>
                        <select name="test">
                            <?php foreach (['IELTS','TOEFL iBT','TOEFL PBT','PTE Academic','Duolingo English Test','GMAT','GRE','SAT','ACT','Cambridge English','Other'] as $t): ?>
                            <option value="<?php echo $t; ?>" <?php echo ($ls['test'] ?? '') === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Score</label><input type="text" name="score" placeholder="e.g. 7.5" value="<?php echo htmlspecialchars($ls['score'] ?? '', ENT_QUOTES); ?>"></div>
                    <div><label>Date Taken</label><input type="text" name="date" placeholder="2024-03" value="<?php echo htmlspecialchars($ls['date'] ?? '', ENT_QUOTES); ?>"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="sp-add-btn" onclick="spAddLangEntry()"><i class="fa fa-plus"></i> Add Test Score</button>
    </div>
    <div class="sp-modal-footer">
        <button class="sp-btn-cancel" onclick="spCloseModal()">Cancel</button>
        <button class="sp-btn-save" onclick="spSave('language')">Save Changes</button>
    </div>
</div>

{{-- Achievements Modal --}}
<div class="sp-modal sp-modal-lg" id="modal-achievements">
    <div class="sp-modal-header"><h3>Edit Achievements & Awards</h3><button onclick="spCloseModal()">✕</button></div>
    <div class="sp-modal-body">
        <div id="ach-entries">
            <?php foreach ($achievements as $ach): ?>
            <div class="sp-entry-card">
                <button class="sp-entry-remove" onclick="spRemoveEntry(this)">×</button>
                <div class="sp-two-col">
                    <div><label>Title</label><input type="text" name="title" placeholder="e.g. Dean's List" value="<?php echo htmlspecialchars($ach['title'] ?? '', ENT_QUOTES); ?>"></div>
                    <div><label>Issuer / Organisation</label><input type="text" name="issuer" placeholder="e.g. University of Jakarta" value="<?php echo htmlspecialchars($ach['issuer'] ?? '', ENT_QUOTES); ?>"></div>
                </div>
                <div class="sp-two-col">
                    <div><label>Year</label><input type="text" name="year" placeholder="2023" value="<?php echo htmlspecialchars($ach['year'] ?? '', ENT_QUOTES); ?>"></div>
                    <div><label>Description (optional)</label><input type="text" name="description" placeholder="Brief note" value="<?php echo htmlspecialchars($ach['description'] ?? '', ENT_QUOTES); ?>"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="sp-add-btn" onclick="spAddAchEntry()"><i class="fa fa-plus"></i> Add Achievement</button>
    </div>
    <div class="sp-modal-footer">
        <button class="sp-btn-cancel" onclick="spCloseModal()">Cancel</button>
        <button class="sp-btn-save" onclick="spSave('achievements')">Save Changes</button>
    </div>
</div>

{{-- Work Experience Modal --}}
<div class="sp-modal sp-modal-lg" id="modal-work">
    <div class="sp-modal-header"><h3>Edit Work & Extracurricular</h3><button onclick="spCloseModal()">✕</button></div>
    <div class="sp-modal-body">
        <div id="work-entries">
            <?php foreach ($workExp as $w): ?>
            <div class="sp-entry-card">
                <button class="sp-entry-remove" onclick="spRemoveEntry(this)">×</button>
                <?php $wlu = $w['logo_url'] ?? ''; if ($wlu === '_removed_') $wlu = ''; ?>
                <div class="sp-entry-logo-row">
                    <div class="sp-entry-logo-preview">
                        <?php if ($wlu): ?>
                        <img src="<?php echo htmlspecialchars($wlu, ENT_QUOTES); ?>" class="sp-entry-logo-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <span class="sp-entry-logo-placeholder" style="display:none">💼</span>
                        <?php else: ?>
                        <span class="sp-entry-logo-placeholder">💼</span>
                        <?php endif; ?>
                    </div>
                    <div class="sp-entry-logo-meta">
                        <span class="sp-entry-logo-label">Company logo <span style="color:#0891b2;font-size:10px;font-weight:700;background:#cffafe;padding:2px 8px;border-radius:999px;margin-left:4px;">✦ AI auto-generates on save</span></span>
                        <div class="sp-entry-logo-btns">
                            <label class="sp-logo-change-btn">
                                <i class="fa fa-upload"></i> Upload own
                                <input type="file" accept="image/*" style="display:none" onchange="spUploadEntryLogo(this)">
                            </label>
                            <button type="button" class="sp-logo-remove-btn" onclick="spRemoveEntryLogo(this, '💼')" title="Remove logo">×</button>
                        </div>
                    </div>
                    <input type="hidden" name="logo_url" value="<?php echo htmlspecialchars($wlu, ENT_QUOTES); ?>">
                </div>
                <div class="sp-two-col">
                    <div><label>Role / Title</label><input type="text" name="title" placeholder="e.g. Software Intern" value="<?php echo htmlspecialchars($w['title'] ?? '', ENT_QUOTES); ?>"></div>
                    <div><label>Company / Organisation</label><input type="text" name="company" placeholder="e.g. Tokopedia" value="<?php echo htmlspecialchars($w['company'] ?? '', ENT_QUOTES); ?>"></div>
                </div>
                <div class="sp-three-col">
                    <div><label>Country</label><input type="text" name="country" placeholder="Indonesia" value="<?php echo htmlspecialchars($w['country'] ?? '', ENT_QUOTES); ?>"></div>
                    <div><label>From</label><input type="text" name="from" placeholder="2023-06" value="<?php echo htmlspecialchars($w['from'] ?? '', ENT_QUOTES); ?>"></div>
                    <div><label>To</label><input type="text" name="to" placeholder="2023-12 or leave blank" value="<?php echo htmlspecialchars($w['to'] ?? '', ENT_QUOTES); ?>"></div>
                </div>
                <label class="sp-checkbox-label">
                    <input type="checkbox" name="current" value="1" <?php echo !empty($w['current']) ? 'checked' : ''; ?>> Currently in this role
                </label>
                <label>Description</label>
                <textarea name="description" rows="2" placeholder="What did you do? Key achievements..."><?php echo htmlspecialchars($w['description'] ?? '', ENT_QUOTES); ?></textarea>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="sp-add-btn" onclick="spAddWorkEntry()"><i class="fa fa-plus"></i> Add Experience</button>
    </div>
    <div class="sp-modal-footer">
        <button class="sp-btn-cancel" onclick="spCloseModal()">Cancel</button>
        <button class="sp-btn-save" onclick="spSave('work')">Save Changes</button>
    </div>
</div>

<script>
const SP_SAVE_URL       = '<?php echo htmlspecialchars($_page_base_url . '/student_profile/save', ENT_QUOTES); ?>';
const SP_LOGO_URL       = '<?php echo htmlspecialchars($_page_base_url . '/student_profile/fetch_logos', ENT_QUOTES); ?>';
const SP_AVATAR_URL     = '<?php echo htmlspecialchars($_page_base_url . '/student_profile/upload_avatar', ENT_QUOTES); ?>';
const SP_COVER_URL      = '<?php echo htmlspecialchars($_page_base_url . '/student_profile/upload_cover', ENT_QUOTES); ?>';
const SP_ENTRY_LOGO_URL = '<?php echo htmlspecialchars($_page_base_url . '/student_profile/upload_entry_logo', ENT_QUOTES); ?>';
const SP_TOKEN          = '<?php echo csrf_token(); ?>';

// ── Modal management ──────────────────────────────────────
function spOpenModal(section) {
    document.querySelectorAll('.sp-modal').forEach(m => m.classList.remove('sp-modal-open'));
    const modal = document.getElementById('modal-' + section);
    if (!modal) return;
    modal.classList.add('sp-modal-open');
    document.getElementById('sp-modal-bg').classList.add('sp-modal-bg-open');
    document.body.style.overflow = 'hidden';
    // Init char counts
    const h = document.getElementById('f-headline');
    if (h) { document.getElementById('cc-headline').textContent = h.value.length + '/300'; }
    const b = document.getElementById('f-bio');
    if (b) { document.getElementById('cc-bio').textContent = b.value.length + '/2000'; }
}

function spCloseModal() {
    document.querySelectorAll('.sp-modal').forEach(m => m.classList.remove('sp-modal-open'));
    document.getElementById('sp-modal-bg').classList.remove('sp-modal-bg-open');
    document.body.style.overflow = '';
}

// ── AJAX save ────────────────────────────────────────────
function spSave(section) {
    const btn = document.querySelector(`#modal-${section} .sp-btn-save`);
    btn.disabled = true; btn.textContent = 'Saving…';

    const collectedData = spCollect(section);

    fetch(SP_SAVE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': SP_TOKEN },
        body: JSON.stringify({ section, data: collectedData })
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false; btn.textContent = 'Save Changes';
        if (res.status === 200) {
            spCloseModal();
            if (res.completeness !== undefined) spUpdateCompleteness(res.completeness);

            const needsLogos = (section === 'education' || section === 'work')
                && (collectedData.entries || []).some(e => !e.logo_url);

            if (needsLogos) {
                spToast('✓ Saved! Fetching logos…', 'ok');
                const timeout = new Promise(r => setTimeout(r, 14000));
                Promise.race([spFetchLogos(section, collectedData), timeout])
                    .finally(() => location.reload());
            } else {
                spToast('✓ ' + res.message, 'ok');
                setTimeout(() => location.reload(), 800);
            }
        } else {
            spToast('⚠ ' + (res.message || 'Save failed'), 'err');
        }
    })
    .catch(() => { btn.disabled = false; btn.textContent = 'Save Changes'; spToast('⚠ Network error', 'err'); });
}

// ── Data collection per section ───────────────────────────
function spCollect(section) {
    if (section === 'hero') return {
        headline: document.getElementById('f-headline').value,
        nationality: document.getElementById('f-nationality').value,
        current_country: document.getElementById('f-current_country').value,
    };
    if (section === 'bio') return { bio: document.getElementById('f-bio').value };
    if (section === 'target') {
        const chips = [...document.querySelectorAll('#f-fields-wrap .sp-field-chip')].map(c => c.textContent.replace('×','').trim());
        return {
            target_degree: document.getElementById('f-target_degree').value,
            target_fields: chips,
            target_intake_year: document.getElementById('f-target_intake_year').value,
            budget_usd_min: document.getElementById('f-budget_min').value,
            budget_usd_max: document.getElementById('f-budget_max').value,
        };
    }
    if (section === 'education') return { entries: spCollectEntries('#edu-entries', ['degree','field','institution','country','gpa','grad_year']) };
    if (section === 'language')  return { entries: spCollectEntries('#lang-entries', ['test','score','date']) };
    if (section === 'achievements') return { entries: spCollectEntries('#ach-entries', ['title','issuer','year','description']) };
    if (section === 'work') {
        const entries = [...document.querySelectorAll('#work-entries .sp-entry-card')].map(card => ({
            title: card.querySelector('[name=title]')?.value || '',
            company: card.querySelector('[name=company]')?.value || '',
            country: card.querySelector('[name=country]')?.value || '',
            from: card.querySelector('[name=from]')?.value || '',
            to: card.querySelector('[name=to]')?.value || '',
            current: card.querySelector('[name=current]')?.checked ? 1 : 0,
            description: card.querySelector('[name=description]')?.value || '',
            logo_url: card.querySelector('[name=logo_url]')?.value || '',
        }));
        return { entries };
    }
    return {};
}

function spCollectEntries(selector, fields) {
    return [...document.querySelectorAll(selector + ' .sp-entry-card')].map(card => {
        const obj = {};
        fields.forEach(f => { obj[f] = card.querySelector('[name=' + f + ']')?.value || ''; });
        obj.logo_url = card.querySelector('[name=logo_url]')?.value || '';
        return obj;
    });
}

// ── Entry add/remove ──────────────────────────────────────
function spRemoveEntry(btn) { btn.closest('.sp-entry-card').remove(); }

function spAddEduEntry() {
    document.getElementById('edu-entries').insertAdjacentHTML('beforeend', `
    <div class="sp-entry-card">
        <button class="sp-entry-remove" onclick="spRemoveEntry(this)">×</button>
        <div class="sp-entry-logo-row">
            <div class="sp-entry-logo-preview"><span class="sp-entry-logo-placeholder">🏛</span></div>
            <div class="sp-entry-logo-meta">
                <span class="sp-entry-logo-label">Institution logo <span style="color:#6366f1;font-size:10px;font-weight:700;background:#ede9fe;padding:2px 8px;border-radius:999px;margin-left:4px;">✦ AI auto-generates on save</span></span>
                <div class="sp-entry-logo-btns">
                    <label class="sp-logo-change-btn"><i class="fa fa-upload"></i> Upload own<input type="file" accept="image/*" style="display:none" onchange="spUploadEntryLogo(this)"></label>
                    <button type="button" class="sp-logo-remove-btn" onclick="spRemoveEntryLogo(this,'🏛')" title="Remove">×</button>
                </div>
            </div>
            <input type="hidden" name="logo_url" value="">
        </div>
        <div class="sp-two-col">
            <div><label>Degree</label><input type="text" name="degree" placeholder="e.g. Bachelor of Science"></div>
            <div><label>Field of Study</label><input type="text" name="field" placeholder="e.g. Computer Science"></div>
        </div>
        <label>Institution Name</label>
        <input type="text" name="institution" placeholder="e.g. University of Indonesia">
        <div class="sp-three-col">
            <div><label>Country</label><input type="text" name="country" placeholder="Indonesia"></div>
            <div><label>GPA</label><input type="text" name="gpa" placeholder="3.8/4.0"></div>
            <div><label>Graduation Year</label><input type="text" name="grad_year" placeholder="2024"></div>
        </div>
    </div>`);
}

function spAddLangEntry() {
    const tests = ['IELTS','TOEFL iBT','TOEFL PBT','PTE Academic','Duolingo English Test','GMAT','GRE','SAT','ACT','Cambridge English','Other'];
    document.getElementById('lang-entries').insertAdjacentHTML('beforeend', `
    <div class="sp-entry-card">
        <button class="sp-entry-remove" onclick="spRemoveEntry(this)">×</button>
        <div class="sp-three-col">
            <div><label>Test</label><select name="test">${tests.map(t=>`<option>${t}</option>`).join('')}</select></div>
            <div><label>Score</label><input type="text" name="score" placeholder="e.g. 7.5"></div>
            <div><label>Date Taken</label><input type="text" name="date" placeholder="2024-03"></div>
        </div>
    </div>`);
}

function spAddAchEntry() {
    document.getElementById('ach-entries').insertAdjacentHTML('beforeend', `
    <div class="sp-entry-card">
        <button class="sp-entry-remove" onclick="spRemoveEntry(this)">×</button>
        <div class="sp-two-col">
            <div><label>Title</label><input type="text" name="title" placeholder="e.g. Dean's List"></div>
            <div><label>Issuer</label><input type="text" name="issuer" placeholder="e.g. University"></div>
        </div>
        <div class="sp-two-col">
            <div><label>Year</label><input type="text" name="year" placeholder="2023"></div>
            <div><label>Description</label><input type="text" name="description" placeholder="Brief note"></div>
        </div>
    </div>`);
}

function spAddWorkEntry() {
    document.getElementById('work-entries').insertAdjacentHTML('beforeend', `
    <div class="sp-entry-card">
        <button class="sp-entry-remove" onclick="spRemoveEntry(this)">×</button>
        <div class="sp-entry-logo-row">
            <div class="sp-entry-logo-preview"><span class="sp-entry-logo-placeholder">💼</span></div>
            <div class="sp-entry-logo-meta">
                <span class="sp-entry-logo-label">Company logo <span style="color:#0891b2;font-size:10px;font-weight:700;background:#cffafe;padding:2px 8px;border-radius:999px;margin-left:4px;">✦ AI auto-generates on save</span></span>
                <div class="sp-entry-logo-btns">
                    <label class="sp-logo-change-btn"><i class="fa fa-upload"></i> Upload own<input type="file" accept="image/*" style="display:none" onchange="spUploadEntryLogo(this)"></label>
                    <button type="button" class="sp-logo-remove-btn" onclick="spRemoveEntryLogo(this,'💼')" title="Remove">×</button>
                </div>
            </div>
            <input type="hidden" name="logo_url" value="">
        </div>
        <div class="sp-two-col">
            <div><label>Role / Title</label><input type="text" name="title" placeholder="e.g. Software Intern"></div>
            <div><label>Company / Organisation</label><input type="text" name="company" placeholder="e.g. Tokopedia"></div>
        </div>
        <div class="sp-three-col">
            <div><label>Country</label><input type="text" name="country" placeholder="Indonesia"></div>
            <div><label>From</label><input type="text" name="from" placeholder="2023-06"></div>
            <div><label>To</label><input type="text" name="to" placeholder="2023-12"></div>
        </div>
        <label class="sp-checkbox-label"><input type="checkbox" name="current" value="1"> Currently in this role</label>
        <label>Description</label>
        <textarea name="description" rows="2" placeholder="What did you do? Key achievements..."></textarea>
    </div>`);
}

// ── Field chips ───────────────────────────────────────────
function spAddField(e) {
    if (e.key !== 'Enter' && e.key !== ',') return;
    e.preventDefault();
    const input = document.getElementById('f-field-input');
    const val = input.value.trim();
    if (!val) return;
    const chips = document.querySelectorAll('#f-fields-wrap .sp-field-chip');
    if (chips.length >= 10) return;
    const chip = document.createElement('span');
    chip.className = 'sp-field-chip';
    chip.innerHTML = `${val}<button onclick="spRemoveChip(this)">×</button>`;
    document.getElementById('f-fields-wrap').insertBefore(chip, input);
    input.value = '';
}
function spRemoveChip(btn) { btn.closest('.sp-field-chip').remove(); }

function spSetAvatarPreview(url) {
    const wrap = document.getElementById('sp-avatar');
    if (!wrap) return;
    const oldPreview = wrap.querySelector('.sp-avatar-img, .sp-avatar-initial');
    if (oldPreview) oldPreview.remove();
    const preview = document.createElement('div');
    preview.className = 'sp-avatar-img';
    preview.style.backgroundImage = `url('${url}')`;
    wrap.insertBefore(preview, wrap.firstChild);
}

function spSetCoverPreview(url) {
    const cover = document.getElementById('sp-cover');
    if (!cover) return;
    cover.style.backgroundImage = `url('${url}')`;
    cover.style.backgroundSize = 'cover';
    cover.style.backgroundPosition = 'center top';
}

function spUploadFile(url, fieldName, file) {
    const formData = new FormData();
    formData.append(fieldName, file);
    return fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': SP_TOKEN },
        body: formData
    }).then(r => r.json());
}

function spUploadAvatar(input) {
    const file = input.files && input.files[0];
    if (!file) return;
    spUploadFile(SP_AVATAR_URL, 'avatar', file)
        .then(res => {
            if (res.status === 200 && res.avatar_url) {
                spSetAvatarPreview('/' + res.avatar_url + '?t=' + Date.now());
                spToast('✓ Photo updated', 'ok');
                setTimeout(() => location.reload(), 700);
            } else {
                spToast('⚠ ' + (res.message || 'Upload failed'), 'err');
            }
        })
        .catch(() => spToast('⚠ Network error', 'err'))
        .finally(() => { input.value = ''; });
}

function spUploadCover(input) {
    const file = input.files && input.files[0];
    if (!file) return;
    spUploadFile(SP_COVER_URL, 'cover', file)
        .then(res => {
            if (res.status === 200 && res.cover_url) {
                spSetCoverPreview('/' + res.cover_url + '?t=' + Date.now());
                spToast('✓ Cover updated', 'ok');
                setTimeout(() => location.reload(), 700);
            } else {
                spToast('⚠ ' + (res.message || 'Upload failed'), 'err');
            }
        })
        .catch(() => spToast('⚠ Network error', 'err'))
        .finally(() => { input.value = ''; });
}

function spFetchLogos(section, data) {
    return fetch(SP_LOGO_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': SP_TOKEN
        },
        body: JSON.stringify({ type: section, entries: data.entries || [] })
    }).then(r => r.json());
}

function spUploadEntryLogo(input) {
    const file = input.files && input.files[0];
    if (!file) return;
    const row = input.closest('.sp-entry-logo-row');
    const preview = row ? row.querySelector('.sp-entry-logo-preview') : null;
    const hidden = row ? row.querySelector('[name=logo_url]') : null;
    spUploadFile(SP_ENTRY_LOGO_URL, 'logo', file)
        .then(res => {
            if (res.status === 200 && res.logo_url && preview && hidden) {
                hidden.value = res.logo_url;
                preview.innerHTML = '<img src="/' + res.logo_url + '?t=' + Date.now() + '" class="sp-entry-logo-img" alt="">';
                spToast('✓ Logo uploaded', 'ok');
            } else {
                spToast('⚠ ' + (res.message || 'Upload failed'), 'err');
            }
        })
        .catch(() => spToast('⚠ Network error', 'err'))
        .finally(() => { input.value = ''; });
}

function spRemoveEntryLogo(btn, placeholder) {
    const row = btn.closest('.sp-entry-logo-row');
    if (!row) return;
    const preview = row.querySelector('.sp-entry-logo-preview');
    const hidden = row.querySelector('[name=logo_url]');
    if (hidden) hidden.value = '_removed_';
    if (preview) preview.innerHTML = '<span class="sp-entry-logo-placeholder">' + placeholder + '</span>';
}

// ── Completeness update ───────────────────────────────────
function spUpdateCompleteness(pct) {
    document.getElementById('sp-comp-pct').textContent = pct + '%';
    document.getElementById('sp-comp-fill').style.width = pct + '%';
}

// ── Toast ─────────────────────────────────────────────────
function spToast(msg, type) {
    const t = document.getElementById('sp-toast');
    t.textContent = msg;
    t.className = 'sp-toast sp-toast-' + type + ' sp-toast-show';
    setTimeout(() => t.classList.remove('sp-toast-show'), 3000);
}
</script>
<?php endif; // end guest check ?>
@endsection
