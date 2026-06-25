@extends('web.common')
@section('title', 'NextGen AI & Talent Challenge')
@push('css')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800;900&family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/asset/css/web/nextgen_challenge.css?v={{ date('YmdHi') }}">
<style>
/* ── ng-guest-cta: always dark bg (#0a0a0a) — restore white text even inside ng-light-content ── */
.ng-guest-cta-inner h2 { color: #f2f5fa !important; }
.ng-guest-cta-inner p  { color: rgba(242,245,250,0.78) !important; }
</style>
@endpush
@section('content')
<link rel="stylesheet" href="/asset/css/web/nextgen_challenge.css?v={{ date('YmdHis') }}">
<?php
$_submission     = $_page_data['submission']      ?? null;
$_likes          = (int)($_page_data['likes']     ?? 0);
$_comments_count = (int)($_page_data['comments_count'] ?? 0);
$_comments       = $_page_data['comments']        ?? [];
$_user_liked     = (bool)($_page_data['user_liked'] ?? false);
$_public_feed    = $_page_data['public_feed']     ?? [];
$_viewer_is_education_institution = (bool)($_page_data['viewer_is_education_institution'] ?? false);
$_is_institution = !empty($_current_member) && (int)($_current_member['type'] ?? 0) === 3;
$_viewer_institution_name = $_page_data['viewer_institution_name'] ?? '';
$_interested_submission_ids = array_map('intval', $_page_data['interested_submission_ids'] ?? []);
$_save_url       = $_page_base_url . '/nextgen_challenge/save_submission';
$_like_url       = $_page_base_url . '/nextgen_challenge/toggle_like';
$_comment_url    = $_page_base_url . '/nextgen_challenge/add_comment';
$_yt_url         = $_page_base_url . '/nextgen_challenge/update_youtube_link';
$_interest_url   = $_page_base_url . '/nextgen_challenge/express_interest';
$_csrf           = csrf_token();

$autoLang = !empty($_page_get_data['autolang']) ? $_page_get_data['autolang'] : session('autolang', '');
$appendAutoLang = function ($url) use ($autoLang) {
    if (empty($autoLang)) return $url;
    return $url . ((strpos($url, '?') !== false) ? '&' : '?') . 'autolang=' . urlencode($autoLang);
};

$memberAvatar = function($avatar) {
    if (empty($avatar)) return '<div class="ng-av-initial"></div>';
    if (file_exists(public_path('upload/member_avatar/'.$avatar))) {
        return '<div class="ng-av" style="background-image:url(upload/member_avatar/' . htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') . ')"></div>';
    }
    if (file_exists(public_path('upload/member_logo/'.$avatar))) {
        return '<div class="ng-av ng-av--logo" style="background-image:url(upload/member_logo/' . htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') . ')"></div>';
    }
    return '<div class="ng-av-initial"></div>';
};

$submissionId = (int)($_submission['id'] ?? 0);
$submissionMediaUrl = $submissionId ? $appendAutoLang($_page_base_url . '/nextgen_challenge/media/' . $submissionId) : '';
?>

<!-- FULL BLEED HERO moved inside ng-main below -->

<div class="ng-wrap">

    <!-- SIDEBAR -->
    <div class="ng-sidebar">
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_plans'), ENT_QUOTES); ?>">
            <span class="ng-sb-icon"><i class="fa fa-star"></i></span>
            <span class="ng-sb-label">Dreams</span>
        </a>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_college_match'), ENT_QUOTES); ?>">
            <span class="ng-sb-icon"><i class="fa fa-graduation-cap"></i></span>
            <span class="ng-sb-label">Matches</span>
        </a>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/nextgen_challenge'), ENT_QUOTES); ?>" class="active">
            <span class="ng-sb-icon"><i class="fa fa-trophy"></i></span>
            <span class="ng-sb-label">NextGen AI &amp;<br>Talent Challenge</span>
        </a>
        <?php if (!$_is_institution): ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_explore'), ENT_QUOTES); ?>">
            <span class="ng-sb-icon"><i class="fa fa-building"></i></span>
            <span class="ng-sb-label">Colleges</span>
        </a>
        <?php else: ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/student_explore'), ENT_QUOTES); ?>">
            <span class="ng-sb-icon"><i class="fa fa-users"></i></span>
            <span class="ng-sb-label">Explore Students</span>
        </a>
        <?php endif; ?>
        <?php if (!empty($_current_member)): ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_is_institution ? $_page_base_url.'/institution_hub_profile' : ((int)($_current_member['type'] ?? 0) === 2 ? $_page_base_url.'/account/posts' : $_page_base_url.'/student_profile')), ENT_QUOTES); ?>">
            <span class="ng-sb-icon"><i class="fa fa-id-card"></i></span>
            <span class="ng-sb-label">My Profile</span>
        </a>
        <?php endif; ?>
        <a href="javascript:void(0);" class="do-toapply" data-sector="migration" data-preset-msg="Hi, can you help me with education and migration queries?" data-action-url="<?php echo htmlspecialchars($_page_base_url.'/agent_chat', ENT_QUOTES); ?>">
            <?php if (!empty($_current_member) && !empty($_current_member['avatar'])): ?>
            <?php if (file_exists(public_path('upload/member_avatar/'.$_current_member['avatar']))): ?>
            <div class="ng-chat-av" style="background-image:url('upload/member_avatar/<?php echo htmlspecialchars($_current_member['avatar'], ENT_QUOTES); ?>')"></div>
            <?php else: ?>
            <div class="ng-chat-av ng-chat-av--init"><?php echo htmlspecialchars(mb_substr($_current_member['alias_name'] ?? 'A', 0, 1), ENT_QUOTES); ?></div>
            <?php endif; ?>
            <?php else: ?>
            <div class="ng-chat-av ng-chat-av--blank"></div>
            <?php endif; ?>
            <span class="ng-sb-label">Chat with<br>AI-mmi</span>
        </a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="ng-main">

        <!-- MOBILE PAGE TABS (shown only on mobile ≤640px, hidden on desktop) -->
        <div class="ng-page-tabs">
            <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_plans'), ENT_QUOTES); ?>" class="ng-page-tab">
                <i class="fa fa-star"></i>Dreams
            </a>
            <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_college_match'), ENT_QUOTES); ?>" class="ng-page-tab">
                <i class="fa fa-graduation-cap"></i>Match
            </a>
            <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/nextgen_challenge'), ENT_QUOTES); ?>" class="ng-page-tab active">
                <i class="fa fa-trophy"></i>NextGen
            </a>
            <?php if (!$_is_institution): ?>
            <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_explore'), ENT_QUOTES); ?>" class="ng-page-tab">
                <i class="fa fa-building"></i>Colleges
            </a>
            <?php else: ?>
            <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/student_explore'), ENT_QUOTES); ?>" class="ng-page-tab">
                <i class="fa fa-users"></i>Students
            </a>
            <?php endif; ?>
            <?php if (!empty($_current_member)): ?>
            <a href="<?php echo htmlspecialchars($appendAutoLang($_is_institution ? $_page_base_url.'/institution_hub_profile' : ((int)($_current_member['type'] ?? 0) === 2 ? $_page_base_url.'/account/posts' : $_page_base_url.'/student_profile')), ENT_QUOTES); ?>" class="ng-page-tab">
                <i class="fa fa-id-card"></i>My Profile
            </a>
            <?php endif; ?>
        </div>

        <!-- HERO (now inside ng-main as a full-width card) -->
        <div class="ng-hero">
            <div class="ng-hero-overlay"></div>
            <div class="ng-hero-inner">
                <h1 class="ng-hero-headline">
                    <span class="ng-hero-hl-main">NextGen AI &amp; Talent Challenge</span>
                    <span class="ng-hero-hl-sub">Win a Scholarship to Study Abroad</span>
                </h1>
                <?php if (!empty($_current_member)): ?>
                <button class="ng-hero-cta-btn" id="ng-edit-btn">
                    <?php echo empty($_submission) ? '<i class="fa fa-arrow-right"></i> Submit Your Entry Now' : '<i class="fa fa-pencil"></i> Edit My Submission'; ?>
                </button>
                <?php else: ?>
                <div class="ng-hero-cta-row">
                    <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/account_registration'), ENT_QUOTES); ?>" class="ng-hero-cta-btn"><i class="fa fa-arrow-right"></i> Enter the Challenge</a>
                    <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/account_login'), ENT_QUOTES); ?>" class="ng-hero-cta-btn ng-hero-cta-btn--outline">Log In</a>
                </div>
                <?php endif; ?>
                <a href="#ng-overview" class="ng-hero-scroll-down"><i class="fa fa-chevron-down"></i></a>
            </div>
        </div>

        <!-- OVERVIEW SECTION -->
        <section class="ng-section ng-overview-section" id="ng-overview">
            <div class="ng-overview-intro-box">
                <p><strong>Create. Perform. Get Discovered. Win a Global Scholarship.</strong></p>
                <p>The <strong>NextGen AI &amp; Talent Challenge</strong> is a global online competition hosted by <strong>AI-mmi</strong>, designed for students aged <strong>16 and above</strong> to showcase their creativity, innovation, and talents to the world.</p>
            </div>
            <p class="ng-overview-p">Whether you create with <strong>AI</strong> or perform with <strong>your unique talents</strong>, this is your chance to shine on a global stage and be noticed by <strong>universities and colleges worldwide</strong>.</p>
            <p class="ng-overview-p">One outstanding participant will win a <strong>full tuition scholarship</strong> to study at a university or college in destinations such as <strong>Australia, Canada, the United Kingdom, the United States</strong>, and more.</p>
            <p class="ng-overview-p">Beyond scholarships, this challenge gives students the opportunity to <strong>gain international exposure, build credibility, and attract attention from universities looking for talented students.</strong></p>
            <div class="ng-tl-block">
                <h4 class="ng-tl-title">Timelines of the challenge:</h4>
                <ul class="ng-tl-list">
                    <li><strong>Submission deadline:</strong> Oct 31, 2026 <em>(Submitting earlier will have more advantages on gaining more viewership)</em></li>
                    <li><strong>Presentation of the finalists:</strong> early Dec 2026</li>
                    <li><strong>Results announcement:</strong> Dec 18, 2026</li>
                </ul>
            </div>
            <div class="ng-highlights-grid">
                <div class="ng-hl-item"><strong>Global Stage</strong><p>Open to students worldwide aged 16+.</p></div>
                <div class="ng-hl-item"><strong>Two Competition Streams</strong><p>Choose the path that best represents you:<br>&bull; AI Stream &mdash; Create an AI-generated video project<br>&bull; Talent Stream &mdash; Showcase your personal talents and skills</p></div>
                <div class="ng-hl-item"><strong>Win a Full Scholarship</strong><p>One winner is selected to receive a full tuition scholarship to study abroad. We may decide to give more scholarships if we have more sponsorship received from the universities/colleges.</p></div>
                <div class="ng-hl-item"><strong>Global Exposure</strong><p>Approved entries will be published on the official AI-mmi YouTube channel, allowing contestants to gain global recognition.</p></div>
                <div class="ng-hl-item"><strong>University Recognition</strong><p>Universities worldwide watch submissions and may contact you with additional opportunities.</p></div>
                <div class="ng-hl-item"><strong>Online Competition</strong><p>Everything happens online &mdash; open to participants from anywhere in the world.</p></div>
            </div>
        </section>

        <!-- COMPETITION STREAMS -->
        <section class="ng-section" id="ng-streams">
            <div class="ng-section-header">
                <h2 class="ng-section-title"><i class="fa fa-cogs"></i> Competition Streams</h2>
            </div>
            <div class="ng-streams-compare">
                <div class="ng-stream-compare-card ng-stream-compare-ai">
                    <img class="ng-sc-header-img" src="https://images.unsplash.com/photo-1677442135703-1787eea5ce01?auto=format&fit=crop&w=700&q=80" alt="AI creation" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1620712943543-bcc4688e7485?auto=format&fit=crop&w=700&q=80'" />
                    <div class="ng-sc-icon"><i class="fa fa-cogs"></i></div>
                    <h4>AI Stream &mdash; Create with AI</h4>
                    <p>Participants create a <strong>video generated with AI tools</strong>.</p>
                    <ul>
                        <li>Length: 3 to 30 minutes</li>
                        <li>Content: Any topic or concept</li>
                        <li>AI tools: Participants are free to use any AI tools</li>
                    </ul>
                    <p>During the final presentation round, contestants will <strong>explain how the AI video was created</strong>, including the tools and creative process.</p>
                </div>
                <div class="ng-stream-compare-card ng-stream-compare-talent">
                    <img class="ng-sc-header-img" src="https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?auto=format&fit=crop&w=700&q=80" alt="Talent performance stage" loading="lazy" />
                    <div class="ng-sc-icon"><i class="fa fa-microphone"></i></div>
                    <h4>Talent Stream &mdash; Showcase Your Talent</h4>
                    <p>Participants submit a <strong>video recording of their talents or skills</strong>.</p>
                    <p>Examples include:</p>
                    <ul>
                        <li>Singing</li>
                        <li>Dancing</li>
                        <li>Acting</li>
                        <li>Music performance</li>
                        <li>Sports skills</li>
                        <li>Public speaking</li>
                        <li>Art, craft, or creative performance</li>
                    </ul>
                    <p>Video length: <strong>3 to 30 minutes</strong></p>
                </div>
            </div>
        </section>

        <!-- Light content area starts here (dark→light transition) -->
        <div class="ng-light-content">

        <!-- HOW IT WORKS -->
        <section class="ng-section" id="ng-how">
            <div class="ng-section-header">
                <h2 class="ng-section-title"><i class="fa fa-list-alt"></i> How It Works</h2>
            </div>
            <div class="ng-steps">
                <div class="ng-step"><div class="ng-step-num">1</div><div class="ng-step-body"><h4>Submit Your Entry</h4><p>Upload your video and select your stream: <strong>AI Stream</strong> or <strong>Talent Stream</strong>.</p></div></div>
                <div class="ng-step-line"></div>
                <div class="ng-step"><div class="ng-step-num">2</div><div class="ng-step-body"><h4>Review &amp; Approval</h4><p>AI-mmi reviews submissions to ensure they meet the rules.</p></div></div>
                <div class="ng-step-line"></div>
                <div class="ng-step"><div class="ng-step-num">3</div><div class="ng-step-body"><h4>Public Release</h4><p>Published on the <strong>AI-mmi platform</strong> &amp; <strong>YouTube channel</strong>.</p></div></div>
                <div class="ng-step-line"></div>
                <div class="ng-step"><div class="ng-step-num">4</div><div class="ng-step-body"><h4>Public Support</h4><p>Contestants share their video to gather views and support.</p></div></div>
                <div class="ng-step-line"></div>
                <div class="ng-step"><div class="ng-step-num">5</div><div class="ng-step-body"><h4>Shortlisting</h4><p><strong>10 entries shortlisted</strong> &mdash; 5 AI Stream &amp; 5 Talent Stream.</p></div></div>
                <div class="ng-step-line"></div>
                <div class="ng-step"><div class="ng-step-num">6</div><div class="ng-step-body"><h4>Online Showcase</h4><p>Present your work live to judges in a <strong>global online showcase</strong>.</p></div></div>
                <div class="ng-step-line"></div>
                <div class="ng-step"><div class="ng-step-num">7</div><div class="ng-step-body"><h4>Winner Selection</h4><p>One winner is selected. But we may decide to award more winners.</p></div></div>
            </div>
        </section>

        <!-- JUDGING CRITERIA -->
        <section class="ng-section" id="ng-judges">
            <div class="ng-section-header">
                <h2 class="ng-section-title"><i class="fa fa-gavel"></i> Judging Criteria</h2>
                <p class="ng-section-sub">Entries are evaluated using the following criteria.</p>
            </div>
            <div class="ng-judges-grid">
                <div class="ng-judge-card">
                    <div class="ng-judge-icon"><i class="fa fa-youtube-play"></i></div>
                    <div class="ng-judge-name">YouTube Views<span class="ng-judge-pct">&ndash; 30%</span></div>
                    <p class="ng-judge-desc">Number of views on the official AI-mmi YouTube video.</p>
                </div>
                <div class="ng-judge-card">
                    <div class="ng-judge-icon"><i class="fa fa-eye"></i></div>
                    <div class="ng-judge-name">AI-mmi Platform Views<span class="ng-judge-pct">&ndash; 30%</span></div>
                    <p class="ng-judge-desc">Engagement and views within the AI-mmi platform.</p>
                </div>
                <div class="ng-judge-card">
                    <div class="ng-judge-icon"><i class="fa fa-gavel"></i></div>
                    <div class="ng-judge-name">Judges&rsquo; Evaluation<span class="ng-judge-pct">&ndash; 40%</span></div>
                    <p class="ng-judge-desc">Based on creativity, presentation, talent or innovation.</p>
                </div>
            </div>
            <div class="ng-judge-footnotes">
                <p class="ng-judge-footnote"><i class="fa fa-info-circle"></i> Submitting earlier may have the advantages of gaining more views.</p>
                <p class="ng-judge-footnote"><i class="fa fa-info-circle"></i> Judges consist of representatives from sponsoring universities and colleges.</p>
            </div>
        </section>

        <!-- PRIZES -->
        <section class="ng-section" id="ng-awards">
            <div class="ng-section-header">
                <h2 class="ng-section-title"><i class="fa fa-trophy"></i> Prizes</h2>
            </div>
            <div class="ng-awards-grid">
                <div class="ng-award-card ng-award-gold">
                    <div class="ng-award-glow"></div>
                    <div class="ng-award-medal ng-medal-gold"><i class="fa fa-trophy"></i></div>
                    <h3 class="ng-award-name">Grand Prize</h3>
                    <div class="ng-award-prize">Full Tuition Scholarship</div>
                    <p class="ng-award-desc">Full Tuition Scholarship to study at a participating <strong>university or college abroad</strong>, including destinations such as Australia, Canada, United Kingdom, United States, and other participating institutions.</p>
                    <p class="ng-award-conditions-label">Important conditions:</p>
                    <ul class="ng-award-perks">
                        <li>Scholarship covers <strong>full tuition only</strong></li>
                        <li>Not exchangeable for cash</li>
                        <li>Not transferable</li>
                        <li>Only <strong>one scholarship per winning entry</strong> (individual or team)</li>
                        <li>If the winner declines the scholarship, it will be <strong>forfeited</strong></li>
                        <li>Scholarship may be <strong>reserved for up to 3 years</strong></li>
                        <li>Students <strong>cannot choose the university or program</strong></li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- BENEFITS FOR STUDENTS HIGHLIGHT -->
        <section class="ng-section ng-benefits-section" id="ng-benefits">
            <div class="ng-benefits-badge"><i class="fa fa-heart"></i>&nbsp; Student Highlights</div>
            <h2 class="ng-section-title" style="text-align:center;">Benefits for Students</h2>
            <div class="ng-benefits-body">
                <p class="ng-benefits-lead">Even beyond the scholarship, participants gain:</p>
                <ul class="ng-benefits-list">
                    <li><strong>Global Exposure</strong> &mdash; Your video is published and shared internationally.</li>
                    <li><strong>Recognition from Universities</strong> &mdash; Universities worldwide will watch the competition.</li>
                    <li><strong>Recruitment Opportunities</strong> &mdash; Other universities may contact you with <strong>additional offers or scholarships</strong>.</li>
                    <li><strong>Personal Branding</strong> &mdash; Build your reputation and showcase your talent or creativity.</li>
                </ul>
            </div>
        </section>

        <!-- RULES & TERMS -->
        <section class="ng-section" id="ng-rules">
            <div class="ng-section-header">
                <h2 class="ng-section-title"><i class="fa fa-file-text-o"></i> Rules &amp; Terms</h2>
                <p class="ng-section-sub">Please read carefully before submitting. By entering, you agree to all rules and terms below.</p>
            </div>
            <div class="ng-rules-accordion">
                <details class="ng-rule-group" open>
                    <summary class="ng-rule-summary"><i class="fa fa-check-circle-o"></i> Rules of Participation</summary>
                    <div class="ng-rule-body">
                        <ul>
                            <li>Participants must be <strong>16 years old or above</strong></li>
                            <li>Open to <strong>students worldwide</strong> (no upper age limit)</li>
                            <li>Entries must be <strong>3&ndash;30 minutes long</strong></li>
                            <li>If the video is not in English, <strong>English subtitles are required</strong></li>
                            <li>Only <strong>online submissions</strong> are accepted</li>
                            <li>AI tools may be freely used in the AI Stream</li>
                            <li>AI-mmi reserves the <strong>right to review and approve submissions</strong></li>
                            <li>Judges&rsquo; decisions are <strong>final and not subject to appeal</strong></li>
                        </ul>
                    </div>
                </details>
                <details class="ng-rule-group">
                    <summary class="ng-rule-summary"><i class="fa fa-gavel"></i> Intellectual Property &amp; Liability</summary>
                    <div class="ng-rule-body">
                        <p>By submitting an entry:</p>
                        <ul>
                            <li><strong>AI-mmi owns the copyrights and intellectual property</strong> of all submissions and videos.</li>
                            <li>AI-mmi has the right to <strong>publish, broadcast, distribute, and use the content publicly or commercially</strong>.</li>
                            <li>Participants agree that AI-mmi <strong>bears no liability</strong> for risks, damages, injuries, or incidents occurring during performances or production of the video.</li>
                        </ul>
                    </div>
                </details>
                <details class="ng-rule-group">
                    <summary class="ng-rule-summary"><i class="fa fa-film"></i> Entry Submission</summary>
                    <div class="ng-rule-body">
                        <p>Participants must:</p>
                        <ol>
                            <li>Register on the <strong>AI-mmi platform</strong></li>
                            <li>Select <strong>AI Stream</strong> or <strong>Talent Stream</strong></li>
                            <li>Upload the video submission</li>
                            <li>Provide basic profile information</li>
                            <li>Submit the entry for review</li>
                        </ol>
                        <p>Once approved, the video will be published on the <strong>AI-mmi platform</strong> and our official <strong>AI-mmi YouTube channel</strong>.</p>
                    </div>
                </details>
            </div>
        </section>

        <!-- UNIVERSITY & COLLEGE SPONSORSHIP HIGHLIGHT -->
        <section class="ng-section ng-uni-section" id="ng-university">
            <div class="ng-uni-badge"><i class="fa fa-university"></i>&nbsp; For Universities &amp; Colleges</div>
            <h2 class="ng-section-title" style="text-align:center;">University &amp; College<br>Sponsorship</h2>
            <p class="ng-uni-lead">Universities and colleges are invited to sponsor the <strong>NextGen AI &amp; Talent Challenge</strong> and participate as <strong>judges</strong>.</p>
            <div class="ng-uni-two-col">
                <div>
                    <h4 class="ng-uni-subhead">Why Sponsor This Initiative</h4>
                    <ul class="ng-uni-checklist">
                        <li><strong>Global Talent Discovery</strong> &mdash; Identify creative and high-potential students worldwide.</li>
                        <li><strong>Brand Visibility</strong> &mdash; Showcase your institution to a global audience of students.</li>
                        <li><strong>Direct Student Engagement</strong> &mdash; Engage with talented participants who are motivated to study abroad.</li>
                        <li><strong>Recruit Future Innovators</strong> &mdash; Discover students skilled in AI, creativity, and innovation.</li>
                        <li><strong>Thought Leadership</strong> &mdash; Participate as judges and help shape the next generation of global talent.</li>
                    </ul>
                </div>
                <div>
                    <h4 class="ng-uni-subhead">Sponsors Will</h4>
                    <ul class="ng-uni-checklist">
                        <li>Serve as <strong>competition judges</strong></li>
                        <li>Help select scholarship winners</li>
                        <li>Gain <strong>international exposure</strong> through the competition</li>
                    </ul>
                </div>
            </div>
            <div class="ng-uni-cta-block">
                <?php $sponsor_whatsapp_url = 'https://wa.me/85298684187?text=' . rawurlencode("Hi, I'm interested in becoming a sponsor for the NextGen AI & Talent Challenge. Could you please share the sponsorship details and next steps?"); ?>
                <h3 class="ng-uni-cta-title">Become a Sponsor</h3>
                <p class="ng-uni-cta-text">Universities and colleges interested in sponsoring scholarships or joining the judging panel can <strong>submit their sponsorship application through the AI-mmi platform</strong>. Contact us via <a href="<?php echo htmlspecialchars($sponsor_whatsapp_url, ENT_QUOTES); ?>" target="_blank" rel="noopener">Enquire on WhatsApp</a> to get started.</p>
                <a href="<?php echo htmlspecialchars($sponsor_whatsapp_url, ENT_QUOTES); ?>" class="ng-hero-cta-btn" style="font-size:15px;padding:16px 44px;" target="_blank" rel="noopener"><i class="fa fa-arrow-right"></i> Enquire</a>
            </div>
        </section>

        <!-- MY SUBMISSION -->
        <?php if (!empty($_current_member)): ?>
        <section class="ng-section ng-my-submission-section" id="ng-my-submission">
            <div class="ng-section-header">
                <h2 class="ng-section-title">My Submission</h2>
                <?php if (!empty($_submission)): ?>
                <button class="ng-btn-edit-inline" id="ng-edit-sub-btn"><i class="fa fa-pencil"></i> Edit Entry</button>
                <?php endif; ?>
            </div>

            <?php if (empty($_submission)): ?>
            <div class="ng-sub-empty-card">
                <div class="ng-sub-empty-icon"><i class="fa fa-film"></i></div>
                <h3>You haven&rsquo;t entered yet!</h3>
                <p>Submit your AI video or talent video to the NextGen Challenge and get discovered by universities worldwide.</p>
                <button class="ng-btn-primary" id="ng-submit-cta-btn"><i class="fa fa-arrow-right"></i> Submit My Entry</button>
            </div>
            <?php else: ?>
            <?php
            $as = (int)($_submission['admin_status'] ?? 0);
            $pub = (int)($_submission['published'] ?? 0);
            ?>
            <?php if ($as === 0): ?>
            <div class="ng-status-banner ng-status-banner--pending"><div class="ng-status-icon"><i class="fa fa-clock-o"></i></div><div><strong>Pending Review</strong><p>Your submission has been received and is awaiting review. We&rsquo;ll email you once a decision is made.</p></div></div>
            <?php elseif ($as === 2): ?>
            <div class="ng-status-banner ng-status-banner--rejected"><div class="ng-status-icon"><i class="fa fa-times-circle"></i></div><div><strong>Not Approved This Cycle</strong><p>Your submission was not approved. Please edit and resubmit, or try again next cycle.</p></div></div>
            <?php elseif ($as === 1 && $pub): ?>
            <div class="ng-status-banner ng-status-banner--live"><div class="ng-status-icon"><i class="fa fa-check-circle"></i></div><div><strong>Live &amp; Published!</strong><p>Your entry is featured on AI-mmi and live on YouTube. Universities and judges are watching!</p></div></div>
            <?php elseif ($as === 1): ?>
            <div class="ng-status-banner ng-status-banner--approved"><div class="ng-status-icon"><i class="fa fa-check-circle"></i></div><div><strong>Approved &mdash; Being Prepared for YouTube</strong><p>We are preparing to upload your video to YouTube. You&rsquo;ll receive an email with the link soon.</p></div></div>
            <?php endif; ?>

            <div class="ng-sub-card" id="ng-sub-preview">
                <div class="ng-sub-card-header">
                    <div>
                        <span class="ng-stream-badge ng-stream-badge--<?php echo htmlspecialchars(strtolower($_submission['stream'] ?? ''), ENT_QUOTES); ?>">
                            <?php echo $_submission['stream'] === 'AI' ? '<i class="fa fa-cogs"></i> AI Stream' : '<i class="fa fa-microphone"></i> Talent Stream'; ?>
                        </span>
                        <h3 class="ng-sub-title"><?php echo htmlspecialchars($_submission['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                    </div>
                </div>
                <?php if (!empty($_submission['description'])): ?>
                <p class="ng-sub-desc"><?php echo nl2br(htmlspecialchars($_submission['description'], ENT_QUOTES, 'UTF-8')); ?></p>
                <?php endif; ?>
                <?php if (!empty($_submission['video_path'])): ?>
                <div class="ng-sub-media">
                    <?php $vp = $_submission['video_path']; $vext = strtolower(pathinfo($vp, PATHINFO_EXTENSION)); ?>
                    <?php if (substr($vp, 0, 4) === 'http'): ?>
                    <a href="<?php echo htmlspecialchars($vp, ENT_QUOTES); ?>" target="_blank" rel="noopener" class="ng-gdrive-view-btn">
                        <i class="fa fa-google"></i> View My Video on Google Drive
                    </a>
                    <?php elseif (in_array($vext, ['jpg','jpeg','png','gif','webp'])): ?>
                    <img src="<?php echo htmlspecialchars($submissionMediaUrl, ENT_QUOTES); ?>" class="ng-sub-video-thumb" alt="Submission" />
                    <?php else: ?>
                    <video src="<?php echo htmlspecialchars($submissionMediaUrl, ENT_QUOTES); ?>" class="ng-sub-video-player" controls preload="metadata"></video>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($_submission['youtube_link'])): ?>
                <div class="ng-yt-display">
                    <a href="<?php echo htmlspecialchars($_submission['youtube_link'], ENT_QUOTES); ?>" target="_blank" rel="noopener" class="ng-yt-watch-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        Watch My Video on YouTube
                    </a>
                </div>
                <?php endif; ?>
                <div class="ng-sub-meta-grid">
                    <?php if (!empty($_submission['tags'])): ?>
                    <div class="ng-sub-meta-item"><span class="ng-sub-meta-label">Tags</span><span><?php echo htmlspecialchars($_submission['tags'], ENT_QUOTES, 'UTF-8'); ?></span></div>
                    <?php endif; ?>
                    <div class="ng-sub-meta-item"><span class="ng-sub-meta-label">YouTube Consent</span><span><?php echo (int)($_submission['youtube_consent'] ?? 0) ? '<span class="ng-check-yes"><i class="fa fa-check-circle-o"></i> Yes</span>' : '&#8212;'; ?></span></div>
                    <div class="ng-sub-meta-item"><span class="ng-sub-meta-label">IP Agreement</span><span><?php echo (int)($_submission['copyright_consent'] ?? 0) ? '<span class="ng-check-yes"><i class="fa fa-check-circle-o"></i> Yes</span>' : '&#8212;'; ?></span></div>
                    <div class="ng-sub-meta-item"><span class="ng-sub-meta-label">Submitted</span><span><?php echo htmlspecialchars(date('d M Y', strtotime($_submission['created_at'] ?? 'now')), ENT_QUOTES); ?></span></div>
                </div>
                <div class="ng-social-bar">
                    <button class="ng-social-btn ng-like-btn <?php echo $_user_liked ? 'liked' : ''; ?>" data-sub-id="<?php echo $submissionId; ?>">
                        <span class="ng-social-icon"><i class="fa fa-heart"></i></span>
                        <span class="ng-social-count" id="ng-like-count"><?php echo number_format($_likes); ?></span>
                        <span class="ng-social-label">Likes</span>
                    </button>
                    <button class="ng-social-btn ng-comment-focus-btn">
                        <span class="ng-social-icon"><i class="fa fa-comment"></i></span>
                        <span class="ng-social-count" id="ng-comment-count"><?php echo number_format($_comments_count); ?></span>
                        <span class="ng-social-label">Comments</span>
                    </button>
                    <button class="ng-social-btn ng-share-btn" data-sub-id="<?php echo $submissionId; ?>">
                        <span class="ng-social-icon"><i class="fa fa-share-alt"></i></span>
                        <span class="ng-social-label">Share</span>
                    </button>
                </div>
                <div class="ng-comments-section" id="ng-comments-section">
                    <?php foreach (array_slice($_comments, 0, 10) as $c): ?>
                    <div class="ng-comment-row">
                        <div class="ng-comment-avatar"><?php echo $memberAvatar($c['avatar'] ?? null); ?></div>
                        <div class="ng-comment-body">
                            <div class="ng-comment-author"><?php echo htmlspecialchars($c['alias_name'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?><span class="ng-comment-time"><?php echo htmlspecialchars(substr($c['created_at'] ?? '', 0, 10), ENT_QUOTES); ?></span></div>
                            <div class="ng-comment-text"><?php echo nl2br(htmlspecialchars($c['content'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="ng-add-comment-row">
                        <div class="ng-comment-avatar"><?php echo $memberAvatar($_current_member['avatar'] ?? null); ?></div>
                        <div class="ng-comment-input-wrap">
                            <input type="text" class="ng-comment-input" id="ng-comment-input" placeholder="Add a comment..." maxlength="500" />
                            <button class="ng-comment-submit" id="ng-comment-submit">Post</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- PUBLIC FEED -->
        <?php if (!empty($_public_feed)): ?>
        <section class="ng-section" id="ng-feed">
            <div class="ng-section-header">
                <h2 class="ng-section-title"><i class="fa fa-star"></i> Community Entries</h2>
                <p class="ng-section-sub">Approved submissions from participants worldwide &mdash; discover them here and watch on YouTube.</p>
            </div>
            <div class="ng-feed-grid">
                <?php foreach ($_public_feed as $entry): ?>
                <div class="ng-feed-card">
                    <?php $isInterested = in_array((int)($entry['id'] ?? 0), $_interested_submission_ids, true); ?>
                    <div class="ng-feed-card-top">
                        <div class="ng-feed-avatar-wrap"><?php echo $memberAvatar($entry['avatar'] ?? null); ?></div>
                        <div class="ng-feed-card-info">
                            <div class="ng-feed-card-name"><?php echo htmlspecialchars($entry['alias_name'] ?? 'Student', ENT_QUOTES, 'UTF-8'); ?></div>
                            <span class="ng-stream-badge ng-stream-badge--<?php echo htmlspecialchars(strtolower($entry['stream'] ?? ''), ENT_QUOTES); ?>"><?php echo $entry['stream'] === 'AI' ? '<i class="fa fa-cogs"></i> AI' : '<i class="fa fa-microphone"></i> Talent'; ?></span>
                        </div>
                    </div>
                    <h4 class="ng-feed-card-title"><?php echo htmlspecialchars($entry['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h4>
                    <?php if (!empty($entry['description'])): ?>
                    <p class="ng-feed-card-desc"><?php echo htmlspecialchars(substr($entry['description'], 0, 100), ENT_QUOTES, 'UTF-8'); ?><?php echo strlen($entry['description'] ?? '') > 100 ? '&hellip;' : ''; ?></p>
                    <?php endif; ?>
                    <?php if (!empty($entry['youtube_link'])): ?>
                    <a href="<?php echo htmlspecialchars($entry['youtube_link'], ENT_QUOTES); ?>" target="_blank" rel="noopener" class="ng-feed-yt-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        Watch on YouTube
                    </a>
                    <?php endif; ?>
                    <?php if ($_viewer_is_education_institution): ?>
                    <button type="button"
                            class="ng-feed-interest-btn<?php echo $isInterested ? ' is-sent' : ''; ?>"
                            data-submission-id="<?php echo (int)($entry['id'] ?? 0); ?>"
                            <?php echo $isInterested ? 'disabled' : ''; ?>>
                        <i class="fa fa-university"></i>
                        <?php echo $isInterested ? 'Interest Sent' : 'Interested In This Person'; ?>
                    </button>
                    <div class="ng-feed-interest-msg<?php echo $isInterested ? ' ok' : ''; ?>" id="ng-interest-msg-<?php echo (int)($entry['id'] ?? 0); ?>"><?php echo $isInterested ? 'AI-mmi has been notified of your institution\'s interest.' : ''; ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php elseif (empty($_current_member)): ?>
        <div class="ng-guest-cta">
            <div class="ng-guest-cta-inner">
                <h2>Join the Next Generation</h2>
                <p>The <strong>NextGen AI &amp; Talent Challenge</strong> is more than a competition &mdash; it is a <strong>global stage for creativity, innovation, and opportunity</strong>.</p>
                <p>Create something amazing. Show the world your talent. Get discovered by universities worldwide.</p>
                <p><strong>Submit your entry today.</strong></p>
                <div class="ng-hero-cta-group">
                    <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/account_registration'), ENT_QUOTES); ?>" class="ng-hero-cta"><i class="fa fa-arrow-right"></i> Create Free Account</a>
                    <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/account_login'), ENT_QUOTES); ?>" class="ng-hero-cta ng-hero-cta--outline">Log In</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.ng-light-content -->

    </div>
</div>
<div class="ng-modal-overlay" id="ng-modal" style="display:none;">
    <div class="ng-modal">
        <div class="ng-modal-header">
            <h3 id="ng-modal-title"><?php echo empty($_submission) ? '<i class="fa fa-arrow-right"></i> Submit to NextGen Challenge' : '<i class="fa fa-pencil"></i> Edit My Submission'; ?></h3>
            <button class="ng-modal-close" id="ng-modal-close" type="button"><i class="fa fa-times"></i></button>
        </div>
        <div class="ng-modal-body">
            <form id="ng-submission-form" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="<?php echo htmlspecialchars($_csrf, ENT_QUOTES); ?>">

                <div class="ng-form-group">
                    <label class="ng-form-label">Stream <span class="ng-required">*</span></label>
                    <div class="ng-stream-options">
                        <label class="ng-stream-opt">
                            <input type="radio" name="stream" value="AI" <?php echo ($_submission['stream'] ?? '') === 'AI' ? 'checked' : ''; ?> />
                            <span class="ng-stream-label ng-stream-ai"><span class="ng-sl-icon"><i class="fa fa-cogs"></i></span><span><strong>AI Stream</strong><br><small>Create with AI tools</small></span></span>
                        </label>
                        <label class="ng-stream-opt">
                            <input type="radio" name="stream" value="Talent" <?php echo ($_submission['stream'] ?? '') === 'Talent' ? 'checked' : ''; ?> />
                            <span class="ng-stream-label ng-stream-talent"><span class="ng-sl-icon"><i class="fa fa-microphone"></i></span><span><strong>Talent Stream</strong><br><small>Showcase your talent</small></span></span>
                        </label>
                    </div>
                </div>

                <div class="ng-form-group">
                    <label class="ng-form-label">Title <span class="ng-required">*</span></label>
                    <input type="text" name="title" class="ng-form-input" maxlength="300"
                           value="<?php echo htmlspecialchars($_submission['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="Give your entry a compelling title" />
                </div>

                <div class="ng-form-group">
                    <label class="ng-form-label">Brief description</label>
                    <textarea name="description" class="ng-form-textarea" rows="3" maxlength="600"
                              placeholder="Describe your submission..."><?php echo htmlspecialchars($_submission['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="ng-form-group">
                    <label class="ng-form-label">Social media tags</label>
                    <input type="text" name="tags" class="ng-form-input" maxlength="300"
                           value="<?php echo htmlspecialchars($_submission['tags'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="e.g. #AIchallenge #talent #aimmi" />
                </div>

                <div class="ng-form-row">
                    <div class="ng-form-group ng-form-half">
                        <label class="ng-form-label">Full name</label>
                        <input type="text" name="full_name" class="ng-form-input"
                               value="<?php echo htmlspecialchars($_submission['full_name'] ?? ($_current_member['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder="Your legal full name" />
                    </div>
                    <div class="ng-form-group ng-form-half">
                        <label class="ng-form-label">Country</label>
                        <input type="text" name="country" class="ng-form-input"
                               value="<?php echo htmlspecialchars($_submission['country'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder="e.g. Australia" />
                    </div>
                </div>

                <div class="ng-form-row">
                    <div class="ng-form-group ng-form-half">
                        <label class="ng-form-label">Age</label>
                        <input type="number" name="age" class="ng-form-input" min="16" max="120"
                               value="<?php echo htmlspecialchars((string)($_submission['age'] ?? ''), ENT_QUOTES); ?>"
                               placeholder="Must be 16+" />
                    </div>
                    <div class="ng-form-group ng-form-half">
                        <label class="ng-form-label">Email address <span class="ng-required">*</span></label>
                        <input type="email" name="email" class="ng-form-input"
                               value="<?php echo htmlspecialchars($_submission['email'] ?? ($_current_member['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder="We'll send your YouTube link here" />
                    </div>
                </div>

                <div class="ng-form-group">
                    <label class="ng-form-label">Phone number</label>
                    <input type="text" name="phone" class="ng-form-input" maxlength="50"
                           value="<?php echo htmlspecialchars($_submission['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="Optional &mdash; for prize notification" />
                </div>

                <div class="ng-form-group">
                    <label class="ng-form-label">Your Google Drive video link <span class="ng-required">*</span></label>

                    <?php if (!empty($_submission['video_path']) && substr($_submission['video_path'], 0, 4) === 'http'): ?>
                    <div class="ng-current-video-wrap">
                        <a href="<?php echo htmlspecialchars($_submission['video_path'], ENT_QUOTES); ?>" target="_blank" rel="noopener" class="ng-gdrive-current-link">
                            <i class="fa fa-google"></i> View current Google Drive video
                        </a>
                        <p class="ng-upload-replace-note">Click the button below to open Google Drive and pick a different video.</p>
                    </div>
                    <?php elseif (!empty($_submission['video_path'])): ?>
                    <div class="ng-current-video-wrap">
                        <video src="<?php echo htmlspecialchars($submissionMediaUrl, ENT_QUOTES); ?>" class="ng-sub-video-player" controls preload="metadata"></video>
                        <p class="ng-upload-replace-note">Click the button below to open Google Drive and pick a different video.</p>
                    </div>
                    <?php endif; ?>

                    <div class="ng-gdrive-picker-wrap">
                        <a href="https://drive.google.com" target="_blank" rel="noopener" class="ng-gdrive-open-btn">
                            <svg class="ng-gdrive-icon" viewBox="0 0 87.3 78" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8H0c0 1.55.4 3.1 1.2 4.5z" fill="#0066da"/>
                                <path d="M43.65 25 29.9 1.2C28.55 2 27.4 3.1 26.6 4.5L1.2 48.5C.4 49.9 0 51.45 0 53h27.5z" fill="#00ac47"/>
                                <path d="M73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5H59.8l5.85 11.5z" fill="#ea4335"/>
                                <path d="M43.65 25 57.4 1.2C56.05.4 54.5 0 52.95 0H34.35c-1.55 0-3.1.45-4.45 1.2z" fill="#00832d"/>
                                <path d="M59.8 53H27.5L13.75 76.8c1.35.8 2.9 1.2 4.45 1.2h50.9c1.55 0 3.1-.4 4.45-1.2z" fill="#2684fc"/>
                                <path d="M73.4 26.5c-.8-1.4-1.95-2.5-3.3-3.3L56.3 0H43.65L59.8 53l27.5.5c0-1.55-.4-3.1-1.2-4.5z" fill="#ffba00"/>
                            </svg>
                            Open Google Drive
                        </a>
                        <ol class="ng-gdrive-steps">
                            <li>Find &amp; right-click your video file</li>
                            <li>Click <strong>Share</strong> &rarr; set to <strong>&ldquo;Anyone with the link&rdquo;</strong></li>
                            <li>Click <strong>Copy link</strong> and paste it below</li>
                        </ol>
                    </div>

                    <div class="ng-gdrive-paste-wrap">
                        <span class="ng-gdrive-paste-icon"><i class="fa fa-link"></i></span>
                        <input type="url" name="google_drive_link" id="ng-gdrive-input" class="ng-form-input ng-gdrive-input"
                               value="<?php echo htmlspecialchars((isset($_submission['video_path']) && substr($_submission['video_path'] ?? '', 0, 4) === 'http') ? $_submission['video_path'] : '', ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder="Paste your Google Drive link here&hellip;" />
                    </div>
                </div>

                <div class="ng-upload-progress-wrap" id="ng-upload-progress" style="display:none;">
                    <div class="ng-upload-progress-bar" id="ng-upload-progress-bar"></div>
                    <span id="ng-upload-pct">0%</span>
                </div>

                <div class="ng-ip-agreement-box">
                    <div class="ng-ip-agreement-header">
                        <i class="fa fa-exclamation-triangle"></i>
                        <h4>Intellectual Property Agreement</h4>
                    </div>
                    <p>By submitting, you grant <strong>AI-mmi Pty Ltd</strong> the <strong>exclusive, perpetual, worldwide right</strong> to publish, broadcast and distribute your submission on YouTube and the AI-mmi website. You confirm this is your <strong>original work</strong>.</p>
                    <div class="ng-consent-checks">
                        <label class="ng-checkbox-label">
                            <input type="checkbox" name="youtube_consent" value="1" <?php echo (int)($_submission['youtube_consent'] ?? 0) ? 'checked' : ''; ?> id="ng-youtube-consent" />
                            <span>I consent to this video being <strong>uploaded to YouTube</strong> on the official AI-mmi channel</span>
                        </label>
                        <label class="ng-checkbox-label">
                            <input type="checkbox" name="copyright_consent" value="1" <?php echo (int)($_submission['copyright_consent'] ?? 0) ? 'checked' : ''; ?> id="ng-copyright-consent" />
                            <span>I agree that <strong>AI-mmi holds full intellectual property rights</strong> to this submission as described</span>
                        </label>
                    </div>
                </div>

                <div id="ng-form-msg" class="ng-form-msg" style="display:none;"></div>

                <div class="ng-modal-actions">
                    <button type="button" class="ng-btn-cancel" id="ng-cancel-btn">Cancel</button>
                    <button type="submit" class="ng-btn-primary" id="ng-save-btn">
                        <?php echo empty($_submission) ? '<i class="fa fa-arrow-right"></i> Submit Entry' : '<i class="fa fa-floppy-o"></i> Save Changes'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/* NextGen Countdown — results announcement: 18 December 2026 */
(function(){
    var target = new Date('2026-12-18T00:00:00');
    function tick(){
        var diff = target - new Date();
        if(diff <= 0){
            var el = document.getElementById('ng-countdown');
            if(el){ el.innerHTML = '<span style="color:var(--ng-accent);font-size:14px;letter-spacing:1px;">WINNERS BEING ANNOUNCED!</span>'; }
            return;
        }
        var d = Math.floor(diff/86400000),
            h = Math.floor((diff%86400000)/3600000),
            m = Math.floor((diff%3600000)/60000),
            s = Math.floor((diff%60000)/1000);
        function p(n){ return String(n).padStart(2,'0'); }
        var ed=document.getElementById('cd-days'),
            eh=document.getElementById('cd-hours'),
            em=document.getElementById('cd-mins'),
            es=document.getElementById('cd-secs');
        if(ed) ed.textContent=p(d);
        if(eh) eh.textContent=p(h);
        if(em) em.textContent=p(m);
        if(es) es.textContent=p(s);
    }
    tick();
    setInterval(tick,1000);
})();
</script>
<script>
const _ng_submission_id = <?php echo $submissionId ?: 'null'; ?>;
const _ng_save_url      = '<?php echo htmlspecialchars($appendAutoLang($_save_url), ENT_QUOTES); ?>';
const _ng_like_url      = '<?php echo htmlspecialchars($appendAutoLang($_like_url), ENT_QUOTES); ?>';
const _ng_comment_url   = '<?php echo htmlspecialchars($appendAutoLang($_comment_url), ENT_QUOTES); ?>';
const _ng_yt_url        = '<?php echo htmlspecialchars($appendAutoLang($_yt_url), ENT_QUOTES); ?>';
const _ng_interest_url  = '<?php echo htmlspecialchars($appendAutoLang($_interest_url), ENT_QUOTES); ?>';
const _ng_token         = '<?php echo htmlspecialchars($_csrf, ENT_QUOTES); ?>';
const _ng_is_logged_in  = <?php echo !empty($_current_member) ? 'true' : 'false'; ?>;
const _ng_base_url      = '<?php echo htmlspecialchars($_page_base_url, ENT_QUOTES); ?>';
const _ng_has_sub       = <?php echo !empty($_submission) ? 'true' : 'false'; ?>;
const _ng_is_education_institution = <?php echo $_viewer_is_education_institution ? 'true' : 'false'; ?>;
</script>

<!-- Mobile bottom nav (shown on ≤640px when sidebar is hidden) -->
<nav class="ng-mobile-nav" role="navigation" aria-label="Mobile navigation">
    <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_plans'), ENT_QUOTES); ?>" class="ng-mn-item">
        <span class="ng-mn-icon"><i class="fa fa-star"></i></span>
        <span class="ng-mn-label">Dreams</span>
    </a>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/study_college_match'), ENT_QUOTES); ?>" class="ng-mn-item">
        <span class="ng-mn-icon"><i class="fa fa-graduation-cap"></i></span>
        <span class="ng-mn-label">Matches</span>
    </a>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/nextgen_challenge'), ENT_QUOTES); ?>" class="ng-mn-item active">
        <span class="ng-mn-icon"><i class="fa fa-trophy"></i></span>
        <span class="ng-mn-label">NextGen</span>
    </a>
    <?php if (!$_is_institution): ?>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/institution_explore'), ENT_QUOTES); ?>" class="ng-mn-item">
        <span class="ng-mn-icon"><i class="fa fa-building"></i></span>
        <span class="ng-mn-label">Colleges</span>
    </a>
    <?php else: ?>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_page_base_url.'/student_explore'), ENT_QUOTES); ?>" class="ng-mn-item">
        <span class="ng-mn-icon"><i class="fa fa-users"></i></span>
        <span class="ng-mn-label">Students</span>
    </a>
    <?php endif; ?>
    <a href="javascript:void(0);" class="ng-mn-item do-toapply" data-sector="migration" data-preset-msg="Hi, can you help me with education and migration queries?" data-action-url="<?php echo htmlspecialchars($_page_base_url.'/agent_chat', ENT_QUOTES); ?>">
        <span class="ng-mn-icon"><i class="fa fa-comments"></i></span>
        <span class="ng-mn-label">Chat</span>
    </a>
</nav>
@endsection

@push('scripts')
<script src="/asset/js/web/nextgen_challenge.js?v={{ date('YmdHi') }}"></script>
@endpush
