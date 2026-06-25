@extends('web.common')

@push('css')
<link href="/asset/css/web/institution_by_country.css?v=<?php echo date('YmdHi'); ?>" rel="stylesheet">
<style>
main.page-body .page-content { margin-right: 450px !important; max-width: 100% !important; }
main.page-body .info-area {
    width: 100% !important;
    float: none !important;
    background: #0b2d6f !important;
    background-image: none !important;
    background-color: #0b2d6f !important;
    min-height: 100vh !important;
}
main.page-body .info-area::before { display: none !important; }
body { background: #0b2d6f !important; }
</style>
@endpush

@section('title', ($country_name ?? 'Institutions') . ' — Partner Institutions')

@section('content')
<?php
$institutions  = $_page_data['institutions']  ?? [];
$countrySlug   = $_page_data['country_slug']  ?? '';
$countryName   = $_page_data['country_name']  ?? 'Unknown';
$countryFlag   = $_page_data['country_flag']  ?? '';
$hasResults    = $_page_data['has_results']   ?? false;
$lang          = $_current_lang_code ?? 'en';
$base          = rtrim($_page_base_url, '/');
$_is_institution = !empty($_current_member) && (int)($_current_member['type'] ?? 0) === 3;

$autoLang = !empty($_page_get_data['autolang']) ? $_page_get_data['autolang'] : session('autolang', '');
$appendAutoLang = function ($url) use ($autoLang) {
    if (empty($autoLang)) return $url;
    return $url . ((strpos($url, '?') !== false) ? '&' : '?') . 'autolang=' . urlencode($autoLang);
};

$wealthskeyUrl = 'https://wa.me/85298684187?text=' . rawurlencode(
    'Hi, I am interested in migrating to ' . $countryName . '. Could you help me with the migration process?'
);
?>

<div class="ibc-wrap">

    <!-- SIDEBAR -->
    <div class="ibc-sidebar">
        <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/study_plans'), ENT_QUOTES); ?>">
            <span class="ibc-sb-icon"><i class="fa fa-star"></i></span>
            <span class="ibc-sb-label">Dreams</span>
        </a>
        <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/study_college_match'), ENT_QUOTES); ?>">
            <span class="ibc-sb-icon"><i class="fa fa-graduation-cap"></i></span>
            <span class="ibc-sb-label">Matches</span>
        </a>
        <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/nextgen_challenge'), ENT_QUOTES); ?>">
            <span class="ibc-sb-icon"><i class="fa fa-trophy"></i></span>
            <span class="ibc-sb-label">NextGen AI &amp;<br>Talent Challenge</span>
        </a>
        <?php if (!$_is_institution): ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/institution_explore'), ENT_QUOTES); ?>" class="active">
            <span class="ibc-sb-icon"><i class="fa fa-building"></i></span>
            <span class="ibc-sb-label">Colleges</span>
        </a>
        <?php else: ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/student_explore'), ENT_QUOTES); ?>">
            <span class="ibc-sb-icon"><i class="fa fa-users"></i></span>
            <span class="ibc-sb-label">Explore Students</span>
        </a>
        <?php endif; ?>
        <a href="javascript:void(0);" class="do-toapply" data-sector="study" data-action-url="<?php echo htmlspecialchars($base.'/agent_chat', ENT_QUOTES); ?>">
            <?php if (!empty($_current_member) && !empty($_current_member['avatar'])): ?>
            <?php if (file_exists(public_path('upload/member_avatar/'.$_current_member['avatar']))): ?>
            <div class="ibc-chat-av" style="background-image:url(upload/member_avatar/<?php echo htmlspecialchars($_current_member['avatar'], ENT_QUOTES); ?>)"></div>
            <?php else: ?>
            <div class="ibc-chat-av ibc-chat-av--init"><?php echo htmlspecialchars(mb_substr($_current_member['alias_name'] ?? 'A', 0, 1), ENT_QUOTES); ?></div>
            <?php endif; ?>
            <?php else: ?>
            <div class="ibc-chat-av ibc-chat-av--blank"></div>
            <?php endif; ?>
            <span class="ibc-sb-label">Chat with<br>AI-mmi</span>
        </a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="ibc-main">

        <!-- MOBILE TABS -->
        <div class="ibc-page-tabs">
            <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/study_plans'), ENT_QUOTES); ?>" class="ibc-page-tab">
                <i class="fa fa-star"></i>Dreams
            </a>
            <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/study_college_match'), ENT_QUOTES); ?>" class="ibc-page-tab">
                <i class="fa fa-graduation-cap"></i>Match
            </a>
            <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/nextgen_challenge'), ENT_QUOTES); ?>" class="ibc-page-tab">
                <i class="fa fa-trophy"></i>NextGen
            </a>
            <?php if (!$_is_institution): ?>
            <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/institution_explore'), ENT_QUOTES); ?>" class="ibc-page-tab active">
                <i class="fa fa-building"></i>Colleges
            </a>
            <?php else: ?>
            <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/student_explore'), ENT_QUOTES); ?>" class="ibc-page-tab">
                <i class="fa fa-users"></i>Students
            </a>
            <?php endif; ?>
        </div>

        <!-- BACK BREADCRUMB -->
        <div class="ibc-breadcrumb">
            <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/study_plans'), ENT_QUOTES); ?>" class="ibc-back-link">
                <i class="fa fa-chevron-left"></i> Back to Study Plans
            </a>
        </div>

        <!-- COMPACT COUNTRY HEADER -->
        <div class="ibc-compact-header">
            <span class="ibc-compact-flag"><?php echo $countryFlag; ?></span>
            <div class="ibc-compact-info">
                <h1 class="ibc-compact-title"><?php echo htmlspecialchars($countryName, ENT_QUOTES); ?></h1>
                <span class="ibc-compact-sub"><?php if ($hasResults): ?><?php echo count($institutions); ?> partner institution<?php echo count($institutions) !== 1 ? 's' : ''; ?><?php else: ?>Browse study opportunities<?php endif; ?></span>
            </div>
        </div>

        <?php if ($hasResults): ?>
        <!-- ═══════════════════════════════════════
             INSTITUTION GRID
        ═══════════════════════════════════════════ -->
        <div class="ibc-grid">
            <?php foreach ($institutions as $inst):
                $profileId    = (int)($inst['id'] ?? 0);
                $memberId     = (int)($inst['member_id'] ?? 0);
                $instName     = htmlspecialchars(
                    trim($inst['institute_name'] ?? '') ?: trim($inst['alias_name'] ?? '') ?: 'Institution',
                    ENT_QUOTES, 'UTF-8'
                );
                $initial      = strtoupper(mb_substr(strip_tags($instName), 0, 1));
                $summary      = htmlspecialchars(mb_substr(strip_tags($inst['summary'] ?? ''), 0, 140), ENT_QUOTES, 'UTF-8');
                $coursesCount = (int)($inst['courses_count'] ?? 0);
                $websiteUrl   = htmlspecialchars($inst['website_url'] ?? '', ENT_QUOTES, 'UTF-8');

                // Use institution hub profile page if exists (has id), else member posts page
                $profileUrl = $profileId > 0
                    ? $appendAutoLang($base . '/institution_hub_profile/pub_view/' . $profileId)
                    : '/' . $lang . '/account/posts?uid=' . $memberId;

                // Logo
                $logo = null;
                if (!empty($inst['avatar'])) {
                    if (file_exists(public_path('upload/member_logo/' . $inst['avatar']))) {
                        $logo = '/upload/member_logo/' . htmlspecialchars($inst['avatar'], ENT_QUOTES);
                    } elseif (file_exists(public_path('upload/member_avatar/' . $inst['avatar']))) {
                        $logo = '/upload/member_avatar/' . htmlspecialchars($inst['avatar'], ENT_QUOTES);
                    }
                }
            ?>
            <a class="ibc-card" href="<?php echo htmlspecialchars($profileUrl, ENT_QUOTES); ?>">
                <div class="ibc-card-logo-wrap">
                    <?php if ($logo): ?>
                    <img src="<?php echo $logo; ?>" alt="<?php echo $instName; ?>" class="ibc-card-logo" />
                    <?php else: ?>
                    <div class="ibc-card-logo-placeholder"><?php echo htmlspecialchars($initial); ?></div>
                    <?php endif; ?>
                </div>
                <div class="ibc-card-body">
                    <h3 class="ibc-card-name"><?php echo $instName; ?></h3>
                    <div class="ibc-card-country">
                        <span class="ibc-card-flag"><?php echo $countryFlag; ?></span>
                        <?php echo htmlspecialchars($countryName, ENT_QUOTES); ?>
                    </div>
                    <?php if ($summary): ?>
                    <p class="ibc-card-summary"><?php echo $summary; ?>&hellip;</p>
                    <?php endif; ?>
                    <?php if ($coursesCount > 0): ?>
                    <div class="ibc-card-courses">
                        <i class="fa fa-graduation-cap"></i>
                        <?php echo $coursesCount; ?> program<?php echo $coursesCount !== 1 ? 's' : ''; ?> available
                    </div>
                    <?php endif; ?>
                </div>
                <div class="ibc-card-footer">
                    <span class="ibc-card-view-btn">View Profile <i class="fa fa-chevron-right"></i></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <!-- ═══════════════════════════════════════
             NO INSTITUTIONS — EMPTY STATE
        ═══════════════════════════════════════════ -->
        <div class="ibc-empty">
            <div class="ibc-empty-hero" style="background-image:url('https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=900&q=75')">
                <span class="ibc-empty-hero-flag"><?php echo $countryFlag; ?></span>
            </div>
            <h2 class="ibc-empty-title">No partner institutions in <?php echo htmlspecialchars($countryName, ENT_QUOTES); ?> yet</h2>
            <p class="ibc-empty-text">
                We are actively expanding our network of partner institutions in <?php echo htmlspecialchars($countryName, ENT_QUOTES); ?>.
                Check back soon, or browse institutions from other countries.
            </p>
            <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/study_plans'), ENT_QUOTES); ?>" class="ibc-btn-outline">
                <i class="fa fa-globe"></i> Browse All Countries
            </a>

            <!-- Migration CTA — WealthsKey -->
            <div class="ibc-migration-cta">
                <div class="ibc-migration-divider">
                    <span>Are you looking to migrate?</span>
                </div>
                <div class="ibc-migration-card">
                    <div class="ibc-migration-icon">
                        <i class="fa fa-plane"></i>
                    </div>
                    <div class="ibc-migration-body">
                        <div class="ibc-migration-tag">MIGRATION SUPPORT</div>
                        <h3 class="ibc-migration-title">Interested in migrating to <?php echo htmlspecialchars($countryName, ENT_QUOTES); ?>?</h3>
                        <p class="ibc-migration-text">
                            Our trusted migration partner <strong>Wealthskey Migration &amp; Education</strong> can guide you through the visa and migration process for <?php echo htmlspecialchars($countryName, ENT_QUOTES); ?>.
                            Connect with a qualified migration agent today.
                        </p>
                        <a href="<?php echo htmlspecialchars($wealthskeyUrl, ENT_QUOTES); ?>"
                           target="_blank" rel="noopener noreferrer"
                           class="ibc-whatsapp-btn">
                            <i class="fa fa-whatsapp"></i>
                            Chat with Wealthskey on WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.ibc-main -->
</div><!-- /.ibc-wrap -->
@endsection
