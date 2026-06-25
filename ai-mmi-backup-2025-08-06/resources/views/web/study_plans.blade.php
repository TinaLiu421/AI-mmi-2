@extends('web.common')
@section('title', 'Dream Plans')
@push('css')
<link rel="stylesheet" href="/asset/css/web/study_plans.css?v={{ date('YmdHi') }}">
<style>
/* ── Light-content area: force dark text on #dce8f8 background ── */
.sp-light-content .sp-country-selector-title{color:#0c1445!important}
.sp-light-content .sp-country-selector-sub{color:rgba(12,20,69,.62)!important}
.sp-light-content .sp-study-action-card{border-bottom-color:rgba(12,20,69,.12)!important;color:#0c1445!important}
.sp-light-content .sp-study-action-card:hover{background:rgba(12,20,69,.05)!important;color:#1d4ed8!important}
.sp-light-content .sp-study-action-title{color:#0c1445!important}
.sp-light-content .sp-study-action-text{color:rgba(12,20,69,.65)!important}
.sp-light-content .sp-sac-arr{color:rgba(12,20,69,.4)!important}
.sp-light-content .sp-empty-state h2{color:#0c1445!important}
.sp-light-content .sp-empty-state p{color:rgba(12,20,69,.7)!important}
.sp-light-content .sp-empty-icon .fa{color:rgba(12,20,69,.25)!important}
.sp-light-content .sp-dream-description{color:rgba(12,20,69,.8)!important}
.sp-light-content .sp-section-title{color:#0c1445!important}
.sp-light-content .sp-section-sub{color:rgba(12,20,69,.65)!important}
.sp-light-content .sp-prog-name{color:#0c1445!important}
.sp-light-content .sp-prog-summary{color:rgba(12,20,69,.65)!important}
.sp-light-content .sp-prog-advertise-card{border-top-color:rgba(12,20,69,.12)!important;color:#0c1445!important}
.sp-light-content .sp-inst-num{color:#0c1445!important}
.sp-light-content .sp-inst-lbl{color:rgba(12,20,69,.65)!important}
.sp-light-content .sp-pitch-title{color:#0c1445!important}
.sp-light-content .sp-pitch-item strong{color:#0c1445!important}
.sp-light-content .sp-pitch-item p{color:rgba(12,20,69,.7)!important}
.sp-light-content .sp-pitch-tag{color:rgba(12,20,69,.55)!important}
.sp-light-content .sp-dream-card{border-color:rgba(12,20,69,.12)!important}
.sp-light-content .sp-comment-time{color:rgba(12,20,69,.45)!important}
.sp-light-content .sp-comment-text{color:rgba(12,20,69,.8)!important}
.sp-light-content .sp-social-btn{color:rgba(12,20,69,.75)!important;background:rgba(12,20,69,.05)!important}
.sp-light-content .sp-social-btn:hover{background:rgba(12,20,69,.08)!important}
</style>
@endpush
@section('content')
<?php
$_is_institution  = $_page_data['is_institution']    ?? false;
$_dream           = $_page_data['dream']             ?? null;
$_dream_owner     = $_page_data['dream_owner']       ?? $_current_member ?? null;
$_is_own_dream    = $_page_data['is_own_dream']      ?? (!empty($_current_member) && !$_is_institution);
$_view_mode       = $_page_data['view_mode']         ?? false;
$_likes           = (int)($_page_data['likes']       ?? 0);
$_comments_count  = (int)($_page_data['comments_count'] ?? 0);
$_comments        = $_page_data['comments']          ?? [];
$_user_liked      = (bool)($_page_data['user_liked'] ?? false);
$_recent_dreams   = $_page_data['recent_dreams']     ?? [];
$_programs        = $_page_data['featured_programs'] ?? [];
$_save_url        = $_page_base_url . '/study_plans/save_dream';
$_like_url        = $_page_base_url . '/study_plans/toggle_like';
$_comment_url     = $_page_base_url . '/study_plans/add_comment';
$_generate_url    = $_page_base_url . '/study_plans/generate_dream_image';
$_action_plan_url = $_page_base_url . '/study_plans/generate_action_plan';
$_csrf            = csrf_token();

$autoLang = !empty($_page_get_data['autolang']) ? $_page_get_data['autolang'] : session('autolang', '');
$appendAutoLang = function ($url) use ($autoLang) {
    if (empty($autoLang)) return $url;
    return $url . ((strpos($url, '?') !== false) ? '&' : '?') . 'autolang=' . urlencode($autoLang);
};

$memberAvatar = function($avatar) {
    if (empty($avatar)) return '<div class="sp-av-initial"></div>';
    if (file_exists(public_path('upload/member_avatar/'.$avatar))) {
        return '<div class="sp-av" style="background-image:url(upload/member_avatar/' . htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') . ')"></div>';
    }
    if (file_exists(public_path('upload/member_logo/'.$avatar))) {
        return '<div class="sp-av sp-av--logo" style="background-image:url(upload/member_logo/' . htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') . ')"></div>';
    }
    return '<div class="sp-av-initial"></div>';
};

$dreamId = (int)($_dream['id'] ?? 0);
$gallery = [];
if (!empty($_dream['gallery_json'])) {
    $g = json_decode($_dream['gallery_json'], true);
    if (is_array($g)) $gallery = $g;
}
?>

<div class="sp-wrap">

    <!-- SIDEBAR (matches nextgen style) -->
    <div class="sp-sidebar">
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_plans'), ENT_QUOTES); ?>" class="active">
            <span class="sp-sb-icon"><i class="fa fa-star"></i></span>
            <span class="sp-sb-label">Dreams</span>
        </a>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_college_match'), ENT_QUOTES); ?>">
            <span class="sp-sb-icon"><i class="fa fa-graduation-cap"></i></span>
            <span class="sp-sb-label">Matches</span>
        </a>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/nextgen_challenge'), ENT_QUOTES); ?>">
            <span class="sp-sb-icon"><i class="fa fa-trophy"></i></span>
            <span class="sp-sb-label">NextGen AI &amp;<br>Talent Challenge</span>
        </a>
        <?php if (!$_is_institution): ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_explore'), ENT_QUOTES); ?>">
            <span class="sp-sb-icon"><i class="fa fa-building"></i></span>
            <span class="sp-sb-label">Colleges</span>
        </a>
        <?php else: ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/student_explore'), ENT_QUOTES); ?>">
            <span class="sp-sb-icon"><i class="fa fa-users"></i></span>
            <span class="sp-sb-label">Explore Students</span>
        </a>
        <?php endif; ?>
        <?php if (!empty($_current_member)): ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_is_institution ? $_page_base_url.'/institution_hub_profile' : ((int)($_current_member['type'] ?? 0) === 2 ? $_page_base_url.'/account/posts' : $_page_base_url.'/student_profile')), ENT_QUOTES); ?>">
            <span class="sp-sb-icon"><i class="fa fa-id-card"></i></span>
            <span class="sp-sb-label"><?php echo $_is_institution ? 'My Profile' : 'My Profile'; ?></span>
        </a>
        <?php endif; ?>
        <a href="javascript:void(0);" class="do-toapply" data-sector="study" data-action-url="<?php echo htmlspecialchars($_page_base_url.'/agent_chat', ENT_QUOTES); ?>">
            <?php if (!empty($_current_member) && !empty($_current_member['avatar'])): ?>
            <?php if (file_exists(public_path('upload/member_avatar/'.$_current_member['avatar']))): ?>
            <div class="sp-chat-av" style="background-image:url(upload/member_avatar/<?php echo htmlspecialchars($_current_member['avatar'], ENT_QUOTES); ?>)"></div>
            <?php else: ?>
            <div class="sp-chat-av sp-chat-av--init"><?php echo htmlspecialchars(mb_substr($_current_member['alias_name'] ?? 'A', 0, 1), ENT_QUOTES); ?></div>
            <?php endif; ?>
            <?php else: ?>
            <div class="sp-chat-av sp-chat-av--blank"></div>
            <?php endif; ?>
            <span class="sp-sb-label">Chat with<br>AI-mmi</span>
        </a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="sp-main">

        <!-- MOBILE PAGE TABS (shown only on mobile ≤900px, hidden on desktop) -->
        <div class="sp-page-tabs">
            <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_plans'), ENT_QUOTES); ?>" class="sp-page-tab active">
                <i class="fa fa-star"></i>Dreams
            </a>
            <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_college_match'), ENT_QUOTES); ?>" class="sp-page-tab">
                <i class="fa fa-graduation-cap"></i>Match
            </a>
            <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/nextgen_challenge'), ENT_QUOTES); ?>" class="sp-page-tab">
                <i class="fa fa-trophy"></i>NextGen
            </a>
            <?php if (!$_is_institution): ?>
            <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_explore'), ENT_QUOTES); ?>" class="sp-page-tab">
                <i class="fa fa-building"></i>Colleges
            </a>
            <?php else: ?>
            <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/student_explore'), ENT_QUOTES); ?>" class="sp-page-tab">
                <i class="fa fa-users"></i>Students
            </a>
            <?php endif; ?>
            <?php if (!empty($_current_member)): ?>
            <a href="<?php echo htmlspecialchars($appendAutoLang($_is_institution ? $_page_base_url.'/institution_hub_profile' : ((int)($_current_member['type'] ?? 0) === 2 ? $_page_base_url.'/account/posts' : $_page_base_url.'/student_profile')), ENT_QUOTES); ?>" class="sp-page-tab">
                <i class="fa fa-id-card"></i>My Profile
            </a>
            <?php endif; ?>
        </div>

        <!-- HERO BANNER -->
        <div class="sp-hero">
            <div class="sp-hero-particles">
                <span></span><span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span><span></span>
            </div>
            <div class="sp-hero-content">
                <?php if ($_is_institution): ?>
                <div class="sp-hero-badge"><i class="fa fa-university"></i> College Partner Hub</div>
                <h1 class="sp-hero-title">The School Hub</h1>
                <p class="sp-hero-sub">Partner with AI-mmi to connect with talented global students and grow international enrolment.</p>
                <a href="<?php echo htmlspecialchars($_page_base_url.'/institution_partner', ENT_QUOTES); ?>" class="sp-hero-cta">Partner with Us &rarr;</a>
                <?php elseif (!empty($_current_member)): ?>
                <div class="sp-hero-badge"><i class="fa fa-star"></i> Student Dream Profile</div>
                <h1 class="sp-hero-title">
                    <?php echo !empty($_dream['title']) ? htmlspecialchars($_dream['title'], ENT_QUOTES, 'UTF-8') : 'What is your dream?'; ?>
                </h1>
                <p class="sp-hero-sub">Share your education &amp; career dreams with the world. Let universities discover your ambition.</p>
                <button class="sp-hero-cta" id="sp-edit-dream-btn">
                    <?php echo empty($_dream) ? '<i class="fa fa-plus-circle"></i> Create My Dream Profile' : '<i class="fa fa-pencil"></i> Edit My Dream'; ?>
                </button>
                <?php else: ?>
                <div class="sp-hero-badge"><i class="fa fa-star"></i> Dream Profiles</div>
                <h1 class="sp-hero-title">Share Your Dream</h1>
                <p class="sp-hero-sub">Create your dream profile, share with family and friends, and let universities discover your ambitions.</p>
                <div class="sp-hero-cta-group">
                    <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/account_registration'), ENT_QUOTES); ?>" class="sp-hero-cta"><i class="fa fa-arrow-right"></i> Create Free Account</a>
                    <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/account_login'), ENT_QUOTES); ?>" class="sp-hero-cta sp-hero-cta--outline">Log In</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Light content area starts here (dark→light transition) -->
        <div class="sp-light-content">

        <!-- ── INSTITUTION TYPE SELECTOR ────────────────────────── -->
        <div class="sp-itype-selector">
            <div class="sp-country-selector-header">
                <h2 class="sp-country-selector-title"><i class="fa fa-university"></i> Browse by College Type</h2>
                <p class="sp-country-selector-sub">Choose a college category to explore matching programs</p>
            </div>
            <div class="sp-itype-grid">
                <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_explore?category=university'), ENT_QUOTES); ?>" class="sp-itype-card" style="background-image:url('https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=900&q=75')">
                    <span class="sp-itype-icon"><i class="fa fa-university"></i></span>
                    <span class="sp-itype-name">College</span>
                    <span class="sp-itype-desc">Bachelor's, Master's &amp; PhD programs</span>
                </a>
                <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_explore?category=vocational'), ENT_QUOTES); ?>" class="sp-itype-card" style="background-image:url('https://images.unsplash.com/photo-1504328345606-18bbc8c9d7d1?auto=format&fit=crop&w=900&q=75')">
                    <span class="sp-itype-icon"><i class="fa fa-wrench"></i></span>
                    <span class="sp-itype-name">Vocational Education</span>
                    <span class="sp-itype-desc">Diplomas, certificates &amp; trade skills</span>
                </a>
                <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_explore?category=highschool'), ENT_QUOTES); ?>" class="sp-itype-card" style="background-image:url('https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=900&q=75')">
                    <span class="sp-itype-icon"><i class="fa fa-book"></i></span>
                    <span class="sp-itype-name">High School</span>
                    <span class="sp-itype-desc">Secondary education &amp; foundation years</span>
                </a>
            </div>
        </div>

        <!-- ── COUNTRY SELECTOR ─────────────────────────────────── -->
        <div class="sp-country-selector">
            <div class="sp-country-selector-header">
                <h2 class="sp-country-selector-title"><i class="fa fa-globe"></i> Explore Colleges by Country</h2>
                <p class="sp-country-selector-sub">Select a country to browse our partner colleges</p>
            </div>
            <div class="sp-country-grid">
                <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_by_country?country=australia'), ENT_QUOTES); ?>" class="sp-country-card" style="background-image:url('https://flagcdn.com/w640/au.jpg')">
                    <span class="sp-country-name">Australia</span>
                </a>
                <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_by_country?country=new-zealand'), ENT_QUOTES); ?>" class="sp-country-card" style="background-image:url('https://flagcdn.com/w640/nz.jpg')">
                    <span class="sp-country-name">New Zealand</span>
                </a>
                <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_by_country?country=us'), ENT_QUOTES); ?>" class="sp-country-card" style="background-image:url('https://flagcdn.com/w640/us.jpg')">
                    <span class="sp-country-name">United States</span>
                </a>
                <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_by_country?country=canada'), ENT_QUOTES); ?>" class="sp-country-card" style="background-image:url('https://flagcdn.com/w640/ca.jpg')">
                    <span class="sp-country-name">Canada</span>
                </a>
                <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_by_country?country=uk'), ENT_QUOTES); ?>" class="sp-country-card" style="background-image:url('https://flagcdn.com/w640/gb.jpg')">
                    <span class="sp-country-name">United Kingdom</span>
                </a>
                <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_by_country?country=hong-kong'), ENT_QUOTES); ?>" class="sp-country-card" style="background-image:url('https://flagcdn.com/w640/hk.jpg')">
                    <span class="sp-country-name">Hong Kong</span>
                </a>
            </div>
        </div>

        <!-- ── VISUAL INSPIRATION STRIP ─────────────────────────── -->
        <div class="sp-inspire-strip">
            <div class="sp-inspire-card">
                <img src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=600&q=70"
                     alt="Prestigious university campus" loading="lazy">
                <div class="sp-inspire-overlay">
                    <div class="sp-inspire-tag">Prestigious Universities</div>
                    <div class="sp-inspire-caption">World-class campuses worldwide</div>
                </div>
            </div>
            <div class="sp-inspire-card">
                <img src="https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=600&q=70"
                     alt="Academic library" loading="lazy">
                <div class="sp-inspire-overlay">
                    <div class="sp-inspire-tag">Research &amp; Knowledge</div>
                    <div class="sp-inspire-caption">World-class libraries &amp; resources</div>
                </div>
            </div>
            <div class="sp-inspire-card">
                <img src="https://images.unsplash.com/photo-1491895200222-0fc4a4c35e18?auto=format&fit=crop&w=600&q=70"
                     alt="Graduation ceremony" loading="lazy">
                <div class="sp-inspire-overlay">
                    <div class="sp-inspire-tag">Your Dream Graduation</div>
                    <div class="sp-inspire-caption">Achieve your academic goals</div>
                </div>
            </div>
            <div class="sp-inspire-card">
                <img src="https://images.unsplash.com/photo-1529390079861-591de354faf5?auto=format&fit=crop&w=600&q=70"
                     alt="Study abroad travel" loading="lazy">
                <div class="sp-inspire-overlay">
                    <div class="sp-inspire-tag">Study Anywhere</div>
                    <div class="sp-inspire-caption">180+ countries, unlimited opportunities</div>
                </div>
            </div>
        </div>

        <?php if ($_is_institution): ?>
        <!-- ══════════════════════════════════════════
             INSTITUTION VIEW
        ══════════════════════════════════════════════ -->
        <div class="sp-inst-section">
            <div class="sp-inst-stats-row">
                <div class="sp-inst-stat"><div class="sp-inst-num">150+</div><div class="sp-inst-lbl">Student Nationalities</div></div>
                <div class="sp-inst-stat"><div class="sp-inst-num">10%</div><div class="sp-inst-lbl">Application Conversion Boost</div></div>
                <div class="sp-inst-stat"><div class="sp-inst-num">40%</div><div class="sp-inst-lbl">Less Manual Processing</div></div>
                <div class="sp-inst-stat"><div class="sp-inst-num">180+</div><div class="sp-inst-lbl">Countries Covered</div></div>
            </div>
            <div class="sp-pitch-card">
                <div class="sp-pitch-tag">PARTNER INSTITUTIONS</div>
                <h2 class="sp-pitch-title">How AI-mmi Helps Partner Institutions</h2>
                <div class="sp-pitch-grid">
                    <div class="sp-pitch-item">
                        <span class="sp-pitch-icon"><i class="fa fa-globe"></i></span>
                        <div><strong>Global Student Access</strong><p>Diversify your enrolment with students from 180+ nationalities actively seeking international education.</p></div>
                    </div>
                    <div class="sp-pitch-item">
                        <span class="sp-pitch-icon"><i class="fa fa-bar-chart"></i></span>
                        <div><strong>Higher Conversion Rates</strong><p>Receive pre-qualified, motivated applications and improve your enrolment conversion by up to 10%.</p></div>
                    </div>
                    <div class="sp-pitch-item">
                        <span class="sp-pitch-icon"><i class="fa fa-cog"></i></span>
                        <div><strong>Automation &amp; AI Tools</strong><p>Leverage proven AI to save time and reduce manual processing by 40%. Smart matching, automated follow-ups.</p></div>
                    </div>
                    <div class="sp-pitch-item">
                        <span class="sp-pitch-icon"><i class="fa fa-trophy"></i></span>
                        <div><strong>NextGen Challenge</strong><p>Sponsor or judge the NextGen AI &amp; Talent Challenge to discover exceptional scholarship candidates worldwide.</p></div>
                    </div>
                </div>
                <a href="https://wa.me/85298684187?text=Hi%2C%20I%27m%20interested%20in%20featuring%20my%20institution%20on%20AI-mmi.%20Could%20you%20tell%20me%20more%3F" target="_blank" rel="noopener noreferrer" class="sp-pitch-btn">Get Started &rarr;</a>
            </div>
        </div>

        <?php else: ?>
        <!-- ══════════════════════════════════════════
             STUDENT DREAM VIEW
        ══════════════════════════════════════════════ -->
        <div class="sp-study-actions">
            <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/eligibility_check'), ENT_QUOTES); ?>" class="sp-study-action-card">
                <div class="sp-sac-icon"><i class="fa fa-check-circle"></i></div>
                <div class="sp-sac-body">
                    <div class="sp-study-action-title">Eligibility Check</div>
                    <div class="sp-study-action-text">Am I eligible to apply based on my profile?</div>
                </div>
                <i class="fa fa-chevron-right sp-sac-arr"></i>
            </a>

            <?php if (empty($_current_member) || (int)($_current_member['type'] ?? 0) !== 3 || strpos(mb_strtolower(trim($_current_member['email'] ?? ''), 'UTF-8'), '@wealthskey.com') !== false): ?>
            <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/agent_chat'), ENT_QUOTES); ?>" class="sp-study-action-card">
                <div class="sp-sac-icon sp-sac-agent"><i class="fa fa-user-circle"></i></div>
                <div class="sp-sac-body">
                    <div class="sp-study-action-title">Talk to Agent</div>
                    <div class="sp-study-action-text">Personalized support for your application</div>
                </div>
                <i class="fa fa-chevron-right sp-sac-arr"></i>
            </a>
            <?php endif; ?>

            <button type="button" class="sp-study-action-card" id="sp-scholarship-btn">
                <div class="sp-sac-icon sp-sac-schol"><i class="fa fa-graduation-cap"></i></div>
                <div class="sp-sac-body">
                    <div class="sp-study-action-title">Scholarship Finder</div>
                    <div class="sp-study-action-text">Grants &amp; bursaries from partner universities</div>
                </div>
                <i class="fa fa-chevron-right sp-sac-arr"></i>
            </button>
        </div>

        <div class="sp-dream-section" id="sp-dream-section">

            <?php if (empty($_dream)): ?>
            <!-- EMPTY STATE -->
            <div class="sp-empty-state" id="sp-dream-empty">
                <div class="sp-empty-icon"><i class="fa fa-star"></i></div>
                <h2>You haven&rsquo;t shared your dream yet!</h2>
                <p>Share your education &amp; career aspirations with the world. Let parents, friends, and universities discover what drives you.<br><br>Your dream profile can help open doors to scholarships, mentorships, and global opportunities.</p>
                <?php if ($_is_own_dream): ?>
                <button class="sp-btn-primary sp-empty-cta" id="sp-create-dream-btn"><i class="fa fa-plus-circle"></i> Create My Dream Profile</button>
                <?php endif; ?>
            </div>

            <?php else: ?>
            <!-- DREAM CARD (two-column) -->
            <div class="sp-dream-card" id="sp-dream-content">
                <!-- Left: photo + gallery -->
                <div class="sp-dream-media-col">
                    <?php if (!empty($_dream['photo'])): ?>
                    <div class="sp-dream-main-photo-wrap">
                        <img src="upload/student_dreams/<?php echo htmlspecialchars($_dream['photo'], ENT_QUOTES); ?>" alt="My Dream" class="sp-dream-main-photo" />
                    </div>
                    <?php else: ?>
                    <div class="sp-dream-photo-placeholder"><i class="fa fa-image"></i></div>
                    <?php endif; ?>
                    <?php if (!empty($gallery)): ?>
                    <div class="sp-gallery-strip">
                        <?php foreach (array_slice($gallery, 0, 4) as $gf): ?>
                        <div class="sp-gallery-thumb">
                            <img src="upload/student_dreams/<?php echo htmlspecialchars($gf, ENT_QUOTES); ?>" alt="" />
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <!-- Right: info -->
                <div class="sp-dream-info-col">
                    <div class="sp-dream-info-top">
                        <div class="sp-dream-avatar-wrap"><?php echo $memberAvatar($_current_member['avatar'] ?? null); ?></div>
                        <div class="sp-dream-meta">
                            <div class="sp-dream-owner-name"><?php echo htmlspecialchars($_current_member['alias_name'] ?? 'Student', ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php if (!empty($_dream['created_at'])): ?>
                            <div class="sp-dream-since">Dreaming since <?php echo date('M Y', strtotime($_dream['created_at'])); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($_dream['description'])): ?>
                    <div class="sp-dream-description"><?php echo nl2br(htmlspecialchars($_dream['description'], ENT_QUOTES, 'UTF-8')); ?></div>
                    <?php endif; ?>
                    <!-- Social bar -->
                    <div class="sp-social-bar">
                        <button class="sp-social-btn sp-like-btn <?php echo $_user_liked ? 'liked' : ''; ?>"
                                data-dream-id="<?php echo $dreamId; ?>"
                                data-count="<?php echo $_likes; ?>"
                                <?php echo empty($_current_member) ? 'onclick="window.location=\''.htmlspecialchars($appendAutoLang($_page_base_url.'/account_login'), ENT_QUOTES).'\'"' : ''; ?>>
                            <span class="sp-social-icon"><i class="fa fa-heart"></i></span>
                            <span class="sp-social-count" id="sp-like-count"><?php echo number_format($_likes); ?></span>
                            <span class="sp-social-label">Likes</span>
                        </button>
                        <button class="sp-social-btn sp-comment-focus-btn">
                            <span class="sp-social-icon"><i class="fa fa-comment"></i></span>
                            <span class="sp-social-count" id="sp-comment-count"><?php echo number_format($_comments_count); ?></span>
                            <span class="sp-social-label">Comments</span>
                        </button>
                        <button class="sp-social-btn sp-share-btn" data-dream-id="<?php echo $dreamId; ?>">
                            <span class="sp-social-icon"><i class="fa fa-share-alt"></i></span>
                            <span class="sp-social-label">Share</span>
                        </button>
                        <?php if ($_is_own_dream): ?>
                        <button class="sp-social-btn sp-ai-card-btn" id="sp-ai-card-btn" title="Generate AI shareable image">
                            <span class="sp-social-icon"><i class="fa fa-magic"></i></span>
                            <span class="sp-social-label">AI Card</span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- COMMENTS SECTION -->
            <div class="sp-comments-section" id="sp-comments-section">
                <h3 class="sp-comments-title"><i class="fa fa-comments"></i> Comments <span class="sp-comments-count"><?php echo number_format($_comments_count); ?></span></h3>
                <?php if (empty($_comments)): ?>
                <p class="sp-no-comments">Be the first to send encouragement!</p>
                <?php endif; ?>
                <?php foreach (array_slice($_comments, 0, 5) as $c): ?>
                <div class="sp-comment-row">
                    <div class="sp-comment-avatar"><?php echo $memberAvatar($c['avatar'] ?? null); ?></div>
                    <div class="sp-comment-bubble">
                        <div class="sp-comment-author"><?php echo htmlspecialchars($c['alias_name'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?><span class="sp-comment-time"><?php echo htmlspecialchars(substr($c['created_at'] ?? '', 0, 10), ENT_QUOTES); ?></span></div>
                        <div class="sp-comment-text"><?php echo nl2br(htmlspecialchars($c['content'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (!empty($_current_member)): ?>
                <div class="sp-add-comment-row" id="sp-comment-form-row">
                    <div class="sp-comment-avatar"><?php echo $memberAvatar($_current_member['avatar'] ?? null); ?></div>
                    <div class="sp-comment-input-wrap">
                        <input type="text" class="sp-comment-input" id="sp-comment-input" placeholder="Add a supportive comment..." maxlength="500" />
                        <button class="sp-comment-submit" id="sp-comment-submit">Post</button>
                    </div>
                </div>
                <?php else: ?>
                <p class="sp-login-prompt"><a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/account_login'), ENT_QUOTES); ?>">Log in</a> to leave a comment.</p>
                <?php endif; ?>
            </div>

            <?php if ($_is_own_dream): ?>
            <?php
            $_member_id_for_share = (int)($_dream['member_id'] ?? ($_current_member['id'] ?? 0));
            $_share_dream_url     = rtrim($_page_base_url, '/') . '/study_plans/view/' . $_member_id_for_share;
            $_share_dream_title   = strip_tags($_dream['title'] ?? 'my dream');
            $_share_owner_name    = strip_tags($_current_member['alias_name'] ?? 'I');
            ?>

            <!-- ── SHARE WITH PARENTS & FRIENDS ────────────────────── -->
            <div class="sp-share-family-section">
                <div class="sp-section-header">
                    <h2 class="sp-section-title"><i class="fa fa-users"></i> Share with Parents &amp; Friends</h2>
                    <p class="sp-section-sub">Let your family discover your dream and cheer you on!</p>
                </div>
                <div class="sp-share-family-url-row">
                    <input type="text" id="sp-dream-public-url" class="sp-share-url-input" readonly
                           value="<?php echo htmlspecialchars($_share_dream_url, ENT_QUOTES); ?>" />
                    <button class="sp-share-copy-btn" id="sp-copy-dream-link">
                        <i class="fa fa-copy"></i> Copy Link
                    </button>
                </div>
                <div class="sp-share-family-btns">
                    <a class="sp-share-family-btn sp-share-wa"
                       href="<?php echo htmlspecialchars('https://wa.me/?text=' . urlencode($_share_owner_name . ' shared their dream! "' . $_share_dream_title . '" — ' . $_share_dream_url), ENT_QUOTES); ?>"
                       target="_blank" rel="noopener noreferrer">
                        <i class="fa fa-whatsapp"></i> WhatsApp
                    </a>
                    <a class="sp-share-family-btn sp-share-email"
                       href="<?php echo htmlspecialchars('mailto:?subject=' . urlencode($_share_owner_name . ' wants to share their dream with you') . '&body=' . urlencode("Hi!\n\n" . $_share_owner_name . " shared their study dream with you:\n\n\"" . $_share_dream_title . "\"\n\nView it here: " . $_share_dream_url . "\n\nShow your support!"), ENT_QUOTES); ?>">
                        <i class="fa fa-envelope"></i> Email
                    </a>
                    <a class="sp-share-family-btn sp-share-fb"
                       href="<?php echo htmlspecialchars('https://www.facebook.com/sharer/sharer.php?u=' . urlencode($_share_dream_url), ENT_QUOTES); ?>"
                       target="_blank" rel="noopener noreferrer">
                        <i class="fa fa-facebook"></i> Facebook
                    </a>
                </div>
            </div>

            <!-- ── STUDY ACTION PLAN ────────────────────────────────── -->
            <div class="sp-action-plan-section">
                <div class="sp-section-header">
                    <h2 class="sp-section-title"><i class="fa fa-list-ol"></i> My Study Action Plan</h2>
                    <p class="sp-section-sub">A personalized AI-powered roadmap to achieve your dream &mdash; share it with parents!</p>
                </div>
                <div id="sp-action-plan-wrap">
                    <div class="sp-action-plan-empty" id="sp-action-plan-empty">
                        <p>Generate a step-by-step study plan tailored to your dream, powered by AI.</p>
                        <button class="sp-btn-primary" id="sp-gen-plan-btn"><i class="fa fa-bolt"></i> Generate My Action Plan</button>
                    </div>
                    <div id="sp-action-plan-loading" style="display:none;text-align:center;padding:32px 0;">
                        <div class="sp-ai-card-spinner"></div>
                        <p style="margin-top:12px;color:#6b7280;">AI is building your action plan&hellip;<br><small>About 10 seconds</small></p>
                    </div>
                    <div id="sp-action-plan-result" class="sp-action-plan-result-box" style="display:none;"></div>
                    <div id="sp-action-plan-actions" class="sp-share-family-btns" style="display:none;margin-top:16px;">
                        <a id="sp-plan-email-btn" href="#" class="sp-share-family-btn sp-share-email">
                            <i class="fa fa-envelope"></i> Email to Parents
                        </a>
                        <a id="sp-plan-wa-btn" href="#" target="_blank" rel="noopener noreferrer" class="sp-share-family-btn sp-share-wa">
                            <i class="fa fa-whatsapp"></i> WhatsApp
                        </a>
                        <button id="sp-plan-regen-btn" class="sp-share-family-btn">
                            <i class="fa fa-refresh"></i> Regenerate
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; /* is_own_dream */ ?>

            <?php endif; /* end has dream */ ?>
        </div>

        <!-- AI DREAM CARD MODAL -->
        <?php if ($_is_own_dream): ?>
        <div class="sp-modal-overlay" id="sp-ai-card-modal" style="display:none;">
            <div class="sp-modal sp-ai-card-modal-inner">
                <div class="sp-modal-header">
                    <h3><i class="fa fa-magic"></i> Your AI Dream Card</h3>
                    <button class="sp-modal-close" id="sp-ai-card-modal-close" type="button"><i class="fa fa-times"></i></button>
                </div>
                <div class="sp-modal-body" id="sp-ai-card-body">
                    <!-- Loading state -->
                    <div id="sp-ai-card-loading" style="text-align:center;padding:40px 20px;">
                        <div class="sp-ai-card-spinner"></div>
                        <p style="margin-top:16px;color:#94a3b8;">Generating your dream card with AI&hellip;<br><small>This takes about 20&ndash;30 seconds</small></p>
                    </div>
                    <!-- Result state (hidden until generated) -->
                    <div id="sp-ai-card-result" style="display:none;">
                        <p style="margin:0 0 12px;font-size:13px;color:#94a3b8;">Share this card with your parents and friends to get their support!</p>
                        <div style="text-align:center;">
                            <img id="sp-ai-card-img" src="" alt="Your AI Dream Card" style="max-width:100%;border-radius:8px;box-shadow:0 4px 24px rgba(0,0,0,0.4);" />
                        </div>
                        <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap;">
                            <a id="sp-ai-card-download" href="#" download="my-dream-card.jpg" class="sp-btn-primary" style="flex:1;text-align:center;padding:10px;">
                                <i class="fa fa-download"></i> Download
                            </a>
                            <button id="sp-ai-card-share-fb" class="sp-btn-outline" style="flex:1;" onclick="">
                                <i class="fa fa-facebook"></i> Share
                            </button>
                            <button id="sp-ai-card-copy" class="sp-btn-outline" style="flex:1;">
                                <i class="fa fa-link"></i> Copy Link
                            </button>
                        </div>
                        <div style="margin-top:10px;text-align:center;">
                            <button class="sp-social-btn" id="sp-ai-card-regen" style="font-size:12px;opacity:0.7;">
                                <i class="fa fa-refresh"></i> Regenerate
                            </button>
                        </div>
                    </div>
                    <!-- Error state -->
                    <div id="sp-ai-card-error" style="display:none;text-align:center;padding:30px 20px;">
                        <p style="color:#ef4444;" id="sp-ai-card-error-msg">Something went wrong.</p>
                        <button class="sp-btn-primary" id="sp-ai-card-retry" style="margin-top:12px;">Try Again</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- DREAM EDIT MODAL -->
        <?php if ($_is_own_dream): ?>
        <div class="sp-modal-overlay" id="sp-dream-modal" style="display:none;">
            <div class="sp-modal">
                <div class="sp-modal-header">
                    <h3><?php echo empty($_dream) ? '<i class="fa fa-plus-circle"></i> Create My Dream Profile' : '<i class="fa fa-pencil"></i> Edit My Dream'; ?></h3>
                    <button class="sp-modal-close" id="sp-modal-close" type="button"><i class="fa fa-times"></i></button>
                </div>
                <div class="sp-modal-body">
                    <form id="sp-dream-form" enctype="multipart/form-data">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($_csrf, ENT_QUOTES); ?>">

                        <div class="sp-form-group">
                            <label class="sp-form-label">Dream Title</label>
                            <input type="text" name="title" class="sp-form-input" maxlength="200"
                                   value="<?php echo htmlspecialchars($_dream['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   placeholder="e.g. Study Computer Science at University of Melbourne" />
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label">About My Dream</label>
                            <textarea name="description" class="sp-form-textarea" rows="4" maxlength="1000"
                                      placeholder="These are my dreams on education &amp; career. Please give me your support..."><?php echo htmlspecialchars($_dream['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label">Main Photo</label>
                            <?php if (!empty($_dream['photo'])): ?>
                            <div class="sp-current-photo-wrap">
                                <img src="upload/student_dreams/<?php echo htmlspecialchars($_dream['photo'], ENT_QUOTES); ?>" alt="Current Photo" class="sp-current-photo-preview" />
                            </div>
                            <?php endif; ?>
                            <input type="file" name="photo" class="sp-form-file" accept="image/jpeg,image/png,image/gif,image/webp" />
                            <div class="sp-form-hint">JPG, PNG, GIF or WebP &mdash; max 10MB.</div>
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label">Gallery Photos <span class="sp-form-hint-inline">(up to 5 images)</span></label>
                            <?php if (!empty($gallery)): ?>
                            <div class="sp-gallery-edit-grid" id="sp-gallery-edit-grid">
                                <?php foreach ($gallery as $gf): ?>
                                <div class="sp-gallery-edit-item" data-file="<?php echo htmlspecialchars($gf, ENT_QUOTES); ?>">
                                    <img src="upload/student_dreams/<?php echo htmlspecialchars($gf, ENT_QUOTES); ?>" alt="" />
                                    <button type="button" class="sp-gallery-delete-btn" data-file="<?php echo htmlspecialchars($gf, ENT_QUOTES); ?>"><i class="fa fa-times"></i></button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <input type="file" name="gallery[]" id="sp-gallery-input" class="sp-form-file" accept="image/jpeg,image/png,image/gif,image/webp" multiple />
                            <input type="hidden" name="delete_gallery" id="sp-delete-gallery-input" value="" />
                        </div>

                        <div class="sp-form-msg" id="sp-dream-msg" style="display:none;"></div>
                        <div class="sp-modal-actions">
                            <button type="button" class="sp-btn-cancel" id="sp-cancel-dream-btn">Cancel</button>
                            <button type="submit" class="sp-btn-save" id="sp-save-dream-btn"><i class="fa fa-floppy-o"></i> Save Dream</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; /* end !institution */ ?>

        <!-- ── SCHOLARSHIP FINDER MODAL (all users) ──────────────────── -->
        <div class="sp-modal-overlay sp-sch-overlay" id="sp-scholarship-modal" style="display:none;" role="dialog" aria-modal="true" aria-label="Scholarship Finder">
            <div class="sp-modal sp-sch-modal">
                <div class="sp-modal-header">
                    <h3><i class="fa fa-graduation-cap"></i> Scholarship Finder</h3>
                    <button class="sp-modal-close" id="sp-sch-close" type="button" aria-label="Close"><i class="fa fa-times"></i></button>
                </div>
                <div class="sp-modal-body sp-sch-body">
                    <!-- Loading -->
                    <div id="sp-sch-loading" style="display:none;text-align:center;padding:40px 0;">
                        <div class="sp-spinner"></div>
                        <p style="color:#94a3b8;margin-top:12px;">Finding scholarships&hellip;</p>
                    </div>
                    <!-- Results -->
                    <div id="sp-sch-results" style="display:none;">
                        <p class="sp-sch-intro">Scholarships currently offered by our partner universities:</p>
                        <div id="sp-sch-list" class="sp-sch-list"></div>
                    </div>
                    <!-- Empty state -->
                    <div id="sp-sch-empty" style="display:none;text-align:center;padding:32px 16px;">
                        <div class="sp-sch-empty-icon"><i class="fa fa-graduation-cap"></i></div>
                        <h4 style="color:#e2e8f0;margin:12px 0 8px;">No Scholarships Listed Yet</h4>
                        <p id="sp-sch-empty-msg" style="color:#94a3b8;margin-bottom:20px;">
                            Our partner universities haven't listed scholarships yet. Speak with an advisor for personalised options.
                        </p>
                        <a id="sp-sch-fallback-btn" href="#" class="sp-btn-primary" style="display:inline-flex;align-items:center;gap:8px;">
                            <i class="fa fa-comments"></i> <span id="sp-sch-fallback-label">Chat with Advisor</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- PARTNER UNIVERSITIES (all views) -->
        <?php if (!empty($_programs)): ?>
        <section class="sp-programs-section">
            <div class="sp-section-header">
                <h2 class="sp-section-title"><i class="fa fa-university"></i> Partner Universities</h2>
                <p class="sp-section-sub">Accredited institutions worldwide actively seeking talented international students.</p>
            </div>
            <div class="sp-programs-grid">
                <?php foreach (array_slice($_programs, 0, 6) as $inst):
                    $logo = null;
                    $instName = htmlspecialchars($inst['institute_name'] ?? ($inst['alias_name'] ?? 'Institution'), ENT_QUOTES, 'UTF-8');
                    if (!empty($inst['avatar'])) {
                        if (file_exists(public_path('upload/member_logo/'.$inst['avatar']))) {
                            $logo = 'upload/member_logo/'.htmlspecialchars($inst['avatar'], ENT_QUOTES);
                        } elseif (file_exists(public_path('upload/member_avatar/'.$inst['avatar']))) {
                            $logo = 'upload/member_avatar/'.htmlspecialchars($inst['avatar'], ENT_QUOTES);
                        }
                    }
                    $summary = htmlspecialchars(substr($inst['summary'] ?? '', 0, 120), ENT_QUOTES, 'UTF-8');
                    $courses = is_array($inst['courses'] ?? null) ? $inst['courses'] : [];
                    $profileUrl = $_page_base_url.'/institution_hub_profile/pub_view/'.$inst['id'];
                ?>
                <a class="sp-prog-card" href="<?php echo htmlspecialchars($appendAutoLang($profileUrl), ENT_QUOTES); ?>">
                    <span class="sp-prog-partner-badge"><i class="fa fa-certificate"></i> Partner</span>
                    <div class="sp-prog-thumb">
                        <?php if ($logo): ?>
                        <img src="<?php echo $logo; ?>" alt="<?php echo $instName; ?>" class="sp-prog-logo" />
                        <?php else: ?>
                        <div class="sp-prog-logo-placeholder"><?php echo mb_substr(strip_tags($instName), 0, 1); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="sp-prog-body">
                        <div class="sp-prog-name"><?php echo $instName; ?></div>
                        <?php if (!empty($courses[0])): ?>
                        <div class="sp-prog-location"><i class="fa fa-map-marker"></i>
                            <?php
                            $city    = htmlspecialchars($courses[0]['city'] ?? $courses[0]['location'] ?? '', ENT_QUOTES, 'UTF-8');
                            $country = htmlspecialchars($courses[0]['country'] ?? '', ENT_QUOTES, 'UTF-8');
                            echo trim(implode(', ', array_filter([$city, $country])));
                            ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($summary): ?>
                        <p class="sp-prog-summary"><?php echo $summary; ?>&hellip;</p>
                        <?php endif; ?>
                        <span class="sp-prog-cta">View Programs &rarr;</span>
                    </div>
                </a>
                <?php endforeach; ?>
                <div class="sp-prog-advertise-card">
                    <div class="sp-prog-advertise-icon"><i class="fa fa-bullhorn"></i></div>
                    <div class="sp-prog-body">
                        <div class="sp-prog-name">Feature Your Institution</div>
                        <p class="sp-prog-summary">Reach thousands of motivated international students. Advertise your programs on AI-mmi.</p>
                        <a href="https://wa.me/85298684187?text=Hi%2C%20I%27m%20interested%20in%20featuring%20my%20institution%20on%20AI-mmi.%20Could%20you%20tell%20me%20more%3F" target="_blank" rel="noopener noreferrer" class="sp-prog-cta sp-prog-advertise-btn">Get Started &rarr;</a>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        </div><!-- /.sp-light-content -->

    </div><!-- /.sp-main -->

    <!-- MOBILE BOTTOM NAV (visible only on small screens) -->
    <nav class="sp-mobile-nav" aria-label="Page navigation">
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_plans'), ENT_QUOTES); ?>" class="sp-mn-item<?php echo (!isset($_GET['section']) || empty($_GET['section'])) ? ' active' : ''; ?>">
            <span class="sp-mn-icon"><i class="fa fa-star"></i></span>
            <span class="sp-mn-label">Dreams</span>
        </a>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_college_match'), ENT_QUOTES); ?>" class="sp-mn-item">
            <span class="sp-mn-icon"><i class="fa fa-graduation-cap"></i></span>
            <span class="sp-mn-label">Match</span>
        </a>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/nextgen_challenge'), ENT_QUOTES); ?>" class="sp-mn-item">
            <span class="sp-mn-icon"><i class="fa fa-trophy"></i></span>
            <span class="sp-mn-label">NextGen</span>
        </a>
        <?php if (!$_is_institution): ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_explore'), ENT_QUOTES); ?>" class="sp-mn-item">
            <span class="sp-mn-icon"><i class="fa fa-building"></i></span>
            <span class="sp-mn-label">Colleges</span>
        </a>
        <?php else: ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/student_explore'), ENT_QUOTES); ?>" class="sp-mn-item">
            <span class="sp-mn-icon"><i class="fa fa-users"></i></span>
            <span class="sp-mn-label">Students</span>
        </a>
        <?php endif; ?>
        <a href="javascript:void(0);" class="sp-mn-item do-toapply" data-sector="study" data-action-url="<?php echo htmlspecialchars($_page_base_url.'/agent_chat', ENT_QUOTES); ?>">
            <span class="sp-mn-icon"><i class="fa fa-comments"></i></span>
            <span class="sp-mn-label">Chat</span>
        </a>
    </nav>

</div><!-- /.sp-wrap -->

<script>
const _sp_dream_id     = <?php echo $dreamId ?: 'null'; ?>;
const _sp_save_url     = '<?php echo htmlspecialchars($appendAutoLang($_save_url), ENT_QUOTES); ?>';
const _sp_like_url     = '<?php echo htmlspecialchars($appendAutoLang($_like_url), ENT_QUOTES); ?>';
const _sp_comment_url  = '<?php echo htmlspecialchars($appendAutoLang($_comment_url), ENT_QUOTES); ?>';
const _sp_token        = '<?php echo htmlspecialchars($_csrf, ENT_QUOTES); ?>';
const _sp_is_logged_in = <?php echo !empty($_current_member) ? 'true' : 'false'; ?>;
const _sp_base_url     = '<?php echo htmlspecialchars($_page_base_url, ENT_QUOTES); ?>';
const _sp_is_own_dream    = <?php echo ($_is_own_dream && !$_is_institution) ? 'true' : 'false'; ?>;
const _sp_generate_url    = '<?php echo htmlspecialchars($appendAutoLang($_generate_url), ENT_QUOTES); ?>';
const _sp_action_plan_url = '<?php echo htmlspecialchars($appendAutoLang($_action_plan_url), ENT_QUOTES); ?>';
const _sp_scholarship_url = '<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_plans/get_scholarships'), ENT_QUOTES); ?>';
const _sp_apply_url       = '<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/apply'), ENT_QUOTES); ?>';
const _sp_chat_url        = '<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/agent_chat'), ENT_QUOTES); ?>';
</script>
@endsection

@push('scripts')
<script src="/asset/js/web/study_plans.js?v={{ date('YmdHi') }}"></script>
@endpush
