@extends('web.common')
@section('title', 'College Match')
@push('css')
<link rel="stylesheet" href="/asset/css/web/study_college_match.css?v={{ date('YmdHi') }}">
@endpush
@section('content')
<?php
$_prefs        = $_page_data['prefs'] ?? null;
$_is_logged_in  = !empty($_current_member);
$_is_institution = !empty($_current_member) && (int)($_current_member['type'] ?? 0) === 3;
$_save_url     = $_page_base_url . '/study_college_match/save_preferences';
$_match_url    = $_page_base_url . '/study_college_match/find_matches';
$_csrf         = csrf_token();

$autoLang = !empty($_page_get_data['autolang']) ? $_page_get_data['autolang'] : session('autolang', '');
$appendAutoLang = function ($url) use ($autoLang) {
    if (empty($autoLang)) return $url;
    return $url . ((strpos($url, '?') !== false) ? '&' : '?') . 'autolang=' . urlencode($autoLang);
};

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

<div class="cm-wrap">

    <!-- SIDEBAR (matches study_plans style) -->
    <div class="cm-sidebar">
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_plans'), ENT_QUOTES); ?>">
            <span class="cm-sb-icon"><i class="fa fa-star"></i></span>
            <span class="cm-sb-label">Dreams</span>
        </a>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_college_match'), ENT_QUOTES); ?>" class="active">
            <span class="cm-sb-icon"><i class="fa fa-university"></i></span>
            <span class="cm-sb-label">Matches</span>
        </a>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/nextgen_challenge'), ENT_QUOTES); ?>">
            <span class="cm-sb-icon"><i class="fa fa-trophy"></i></span>
            <span class="cm-sb-label">NextGen AI &amp;<br>Talent Challenge</span>
        </a>
        <?php if (!$_is_institution): ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_explore'), ENT_QUOTES); ?>">
            <span class="cm-sb-icon"><i class="fa fa-building"></i></span>
            <span class="cm-sb-label">Colleges</span>
        </a>
        <?php else: ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/student_explore'), ENT_QUOTES); ?>">
            <span class="cm-sb-icon"><i class="fa fa-users"></i></span>
            <span class="cm-sb-label">Explore Students</span>
        </a>
        <?php endif; ?>
        <?php if (!empty($_current_member)): ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_is_institution ? $_page_base_url.'/institution_hub_profile' : ((int)($_current_member['type'] ?? 0) === 2 ? $_page_base_url.'/account/posts' : $_page_base_url.'/student_profile')), ENT_QUOTES); ?>">
            <span class="cm-sb-icon"><i class="fa fa-id-card"></i></span>
            <span class="cm-sb-label">My Profile</span>
        </a>
        <?php endif; ?>
        <a href="javascript:void(0);" class="do-toapply" data-sector="study" data-action-url="<?php echo htmlspecialchars($_page_base_url.'/agent_chat', ENT_QUOTES); ?>">
            <?php if (!empty($_current_member) && !empty($_current_member['avatar'])): ?>
            <?php if (file_exists(public_path('upload/member_avatar/'.$_current_member['avatar']))): ?>
            <div class="cm-chat-av" style="background-image:url(upload/member_avatar/<?php echo htmlspecialchars($_current_member['avatar'], ENT_QUOTES); ?>)"></div>
            <?php else: ?>
            <div class="cm-chat-av cm-chat-av--init"><?php echo htmlspecialchars(mb_substr($_current_member['alias_name'] ?? 'A', 0, 1), ENT_QUOTES); ?></div>
            <?php endif; ?>
            <?php else: ?>
            <div class="cm-chat-av cm-chat-av--blank"></div>
            <?php endif; ?>
            <span class="cm-sb-label">Chat with<br>AI-mmi</span>
        </a>
    </div>

    <!-- MAIN -->
    <div class="cm-main">

        <!-- MOBILE PAGE TABS (shown only on mobile ≤900px, hidden on desktop) -->
        <div class="cm-page-tabs">
            <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_plans'), ENT_QUOTES); ?>" class="cm-page-tab">
                <i class="fa fa-star"></i>Dreams
            </a>
            <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_college_match'), ENT_QUOTES); ?>" class="cm-page-tab active">
                <i class="fa fa-graduation-cap"></i>Match
            </a>
            <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/nextgen_challenge'), ENT_QUOTES); ?>" class="cm-page-tab">
                <i class="fa fa-trophy"></i>NextGen
            </a>
            <?php if (!$_is_institution): ?>
            <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_explore'), ENT_QUOTES); ?>" class="cm-page-tab">
                <i class="fa fa-building"></i>Colleges
            </a>
            <?php else: ?>
            <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/student_explore'), ENT_QUOTES); ?>" class="cm-page-tab">
                <i class="fa fa-users"></i>Students
            </a>
            <?php endif; ?>
            <?php if (!empty($_current_member)): ?>
            <a href="<?php echo htmlspecialchars($appendAutoLang($_is_institution ? $_page_base_url.'/institution_hub_profile' : ((int)($_current_member['type'] ?? 0) === 2 ? $_page_base_url.'/account/posts' : $_page_base_url.'/student_profile')), ENT_QUOTES); ?>" class="cm-page-tab">
                <i class="fa fa-id-card"></i>My Profile
            </a>
            <?php endif; ?>
        </div>

        <!-- HERO BANNER -->
        <div class="cm-hero">
            <div class="cm-hero-particles">
                <span></span><span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span>
            </div>
            <div class="cm-hero-content">
                <div class="cm-hero-badge"><i class="fa fa-cogs"></i> AI-Powered Matching</div>
                <h1 class="cm-hero-title">Find Your Perfect<br>University Match</h1>
                <p class="cm-hero-sub">Tell us your study preferences and our AI will recommend the best universities and programs worldwide &mdash; tailored just for you.</p>
                <div class="cm-hero-features">
                    <span><i class="fa fa-globe"></i> 180+ Countries</span>
                    <span><i class="fa fa-university"></i> 500+ Universities</span>
                    <span><i class="fa fa-bolt"></i> AI-Powered</span>
                </div>
            </div>
        </div>

        <!-- Light content area starts here (dark→light transition) -->
        <div class="cm-light-content">

        <!-- PREFERENCES FORM -->

        <!-- ── AI MATCHING VISUAL STRIP ─────────────────────────── -->
        <div class="cm-inspire-strip">
            <div class="cm-inspire-card">
                <img src="https://images.unsplash.com/photo-1488590528505-98d2b5aba04b?auto=format&fit=crop&w=800&q=70"
                     alt="AI technology for university matching" loading="lazy">
                <div class="cm-inspire-overlay">
                    <div class="cm-inspire-icon"><i class="fa fa-bolt"></i></div>
                    <div class="cm-inspire-tag">AI-Powered</div>
                    <div class="cm-inspire-caption">Smart matching algorithm</div>
                    <div class="cm-inspire-stat">Analyzes 50+ data points</div>
                </div>
            </div>
            <div class="cm-inspire-card">
                <img src="https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=800&q=70"
                     alt="Student finding the right university" loading="lazy">
                <div class="cm-inspire-overlay">
                    <div class="cm-inspire-icon"><i class="fa fa-graduation-cap"></i></div>
                    <div class="cm-inspire-tag">Personalized</div>
                    <div class="cm-inspire-caption">Tailored to your profile</div>
                    <div class="cm-inspire-stat">500+ universities matched</div>
                </div>
            </div>
            <div class="cm-inspire-card">
                <img src="https://images.unsplash.com/photo-1434030216411-0b793f4b4173?auto=format&fit=crop&w=800&q=70"
                     alt="Student succeeding in studies abroad" loading="lazy">
                <div class="cm-inspire-overlay">
                    <div class="cm-inspire-icon"><i class="fa fa-globe"></i></div>
                    <div class="cm-inspire-tag">Global Reach</div>
                    <div class="cm-inspire-caption">Study in 180+ countries</div>
                    <div class="cm-inspire-stat">Find your perfect fit</div>
                </div>
            </div>
        </div>

        <div class="cm-prefs-card">
            <div class="cm-prefs-card-header">
                <h2><i class="fa fa-bullseye"></i> My Study Preferences</h2>
                <p>Fill in up to 3 choices for each field. Leave blank if you&rsquo;re flexible &mdash; our AI will find the best options.</p>
            </div>

            <form id="cm-prefs-form">
                <input type="hidden" name="_token" value="<?php echo htmlspecialchars($_csrf, ENT_QUOTES); ?>">

                <div class="cm-prefs-choices-header">
                    <div></div>
                    <div class="cm-choice-label">1st Choice</div>
                    <div class="cm-choice-label">2nd Choice</div>
                    <div class="cm-choice-label">3rd Choice</div>
                </div>

                <?php foreach($_pref_labels as $key => $label): ?>
                <div class="cm-pref-row">
                    <div class="cm-pref-row-label">
                        <span class="cm-pref-icon">
                            <?php
                            $icons = ['country'=>'<i class="fa fa-globe"></i>','city'=>'<i class="fa fa-building"></i>','university'=>'<i class="fa fa-university"></i>','level'=>'<i class="fa fa-graduation-cap"></i>','fields'=>'<i class="fa fa-book"></i>','budget'=>'<i class="fa fa-usd"></i>','year'=>'<i class="fa fa-calendar"></i>'];
                            echo $icons[$key] ?? '<i class="fa fa-bullseye"></i>';
                            ?>
                        </span>
                        <?php echo htmlspecialchars($label, ENT_QUOTES); ?>
                    </div>
                    <?php for($c = 1; $c <= 3; $c++):
                        $fkey = 'choice_'.$c.'_'.$key;
                        $val  = htmlspecialchars($_prefs[$fkey] ?? '', ENT_QUOTES);
                    ?>
                    <div class="cm-pref-cell">
                        <?php if($key === 'level'): ?>
                        <select name="<?php echo $fkey; ?>" class="cm-pref-select">
                            <?php foreach($_level_opts as $opt):
                                $optLabel = ($opt === '') ? 'Select...' : htmlspecialchars($opt, ENT_QUOTES);
                                $optVal   = htmlspecialchars($opt, ENT_QUOTES);
                                $sel      = ($val === $optVal) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $optVal; ?>" <?php echo $sel; ?>><?php echo $optLabel; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <input type="text" name="<?php echo $fkey; ?>" value="<?php echo $val; ?>"
                               class="cm-pref-input"
                               placeholder="<?php echo htmlspecialchars($key === 'budget' ? 'e.g. 30000' : ($key === 'year' ? 'e.g. 2025' : 'Enter...'), ENT_QUOTES); ?>">
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
                <?php endforeach; ?>

                <?php if($_is_logged_in): ?>
                <div class="cm-action-bar">
                    <button type="button" class="cm-save-btn" id="cm-save-btn">
                        <i class="fa fa-floppy-o"></i> Save Preferences
                    </button>
                    <button type="button" class="cm-match-btn" id="cm-match-btn">
                        <i class="fa fa-bolt"></i> Find My Match
                    </button>
                    <span class="cm-action-msg" id="cm-action-msg" style="display:none;"></span>
                    <span class="cm-error-msg" id="cm-error-msg" style="display:none;"></span>
                </div>
                <?php else: ?>
                <div class="cm-login-notice">
                    <span><i class="fa fa-lock"></i></span>
                    Please <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/account_login'), ENT_QUOTES); ?>">log in</a> to save preferences and find matches.
                </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- LOADING INDICATOR -->
        <div class="cm-loading" id="cm-loading">
            <div class="cm-loading-inner">
                <div class="cm-spinner-wrap">
                    <div class="cm-spinner"></div>
                    <span class="cm-spinner-icon"><i class="fa fa-cogs"></i></span>
                </div>
                <div class="cm-loading-title">Finding your perfect matches...</div>
                <p class="cm-loading-sub">Our AI is analysing 500+ universities worldwide based on your preferences</p>
            </div>
        </div>

        <!-- RESULTS -->
        <div id="cm-results-wrap" style="display:none;">
            <div class="cm-results-header">
                <h2 class="cm-results-title"><i class="fa fa-trophy"></i> Your Top Matches</h2>
                <p class="cm-results-sub">Universities and programs matched to your preferences by AI</p>
            </div>
            <div id="cm-top-matches-block"></div>

            <div class="cm-results-header" id="cm-also-title" style="display:none; margin-top:24px;">
                <h2 class="cm-results-title"><i class="fa fa-lightbulb-o"></i> Also Consider</h2>
                <p class="cm-results-sub">Additional universities you may want to explore</p>
            </div>
            <div id="cm-also-block"></div>
        </div>

        </div><!-- /.cm-light-content -->

    </div><!-- /.cm-main -->
</div><!-- /.cm-wrap -->

<div class="cm-bottom-bar">
    <a class="cm-btn-chat do-toapply" data-sector="migration" data-action-url="<?php echo htmlspecialchars($_page_base_url.'/agent_chat', ENT_QUOTES); ?>" href="javascript:void(0);"><i class="fa fa-comments"></i> Chat with AI-mmi</a>
    <a class="cm-btn-apply" id="cm-apply-btn" href="<?php echo htmlspecialchars($_page_base_url.'/apply', ENT_QUOTES); ?>"><i class="fa fa-arrow-right"></i> Apply Now</a>
</div>

<!-- MOBILE BOTTOM NAV (visible only on small screens) -->
<nav class="cm-mobile-nav" aria-label="Page navigation">
    <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_plans'), ENT_QUOTES); ?>" class="cm-mn-item">
        <span class="cm-mn-icon"><i class="fa fa-star"></i></span>
        <span class="cm-mn-label">Dreams</span>
    </a>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_college_match'), ENT_QUOTES); ?>" class="cm-mn-item active">
        <span class="cm-mn-icon"><i class="fa fa-graduation-cap"></i></span>
        <span class="cm-mn-label">Match</span>
    </a>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/nextgen_challenge'), ENT_QUOTES); ?>" class="cm-mn-item">
        <span class="cm-mn-icon"><i class="fa fa-trophy"></i></span>
        <span class="cm-mn-label">NextGen</span>
    </a>
    <?php if (!$_is_institution): ?>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_explore'), ENT_QUOTES); ?>" class="cm-mn-item">
        <span class="cm-mn-icon"><i class="fa fa-building"></i></span>
        <span class="cm-mn-label">Colleges</span>
    </a>
    <?php else: ?>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/student_explore'), ENT_QUOTES); ?>" class="cm-mn-item">
        <span class="cm-mn-icon"><i class="fa fa-users"></i></span>
        <span class="cm-mn-label">Students</span>
    </a>
    <?php endif; ?>
    <a href="javascript:void(0);" class="cm-mn-item do-toapply" data-sector="study" data-action-url="<?php echo htmlspecialchars($_page_base_url.'/agent_chat', ENT_QUOTES); ?>">
        <span class="cm-mn-icon"><i class="fa fa-comments"></i></span>
        <span class="cm-mn-label">Chat</span>
    </a>
</nav>

<script>
var _cm_csrf    = <?php echo json_encode(csrf_token()); ?>;
var _cm_saveUrl = <?php echo json_encode($_save_url); ?>;
var _cm_matchUrl = <?php echo json_encode($_match_url); ?>;
var _cm_baseUrl = <?php echo json_encode(rtrim($_page_base_url, '/')); ?>;
</script>
@endsection

@push('scripts')
<script src="/asset/js/web/study_college_match.js?v={{ date('YmdHi') }}"></script>
@endpush
