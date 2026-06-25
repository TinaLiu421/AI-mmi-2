@extends('web.common')

@push('css')
<link href="/asset/css/web/institution_explore.css?v=<?php echo date('YmdHi'); ?>" rel="stylesheet">
@endpush

@section('title', 'Explore Colleges')

@section('content')
<?php
$institutions    = $_page_data['institutions'] ?? collect();
$search          = $_page_data['search']       ?? '';
$category        = $_page_data['category']     ?? '';
$total           = $_page_data['total']        ?? 0;
$lang            = $_current_lang_code ?? 'en';
$base            = rtrim($_page_base_url, '/');
$acctPostsBase   = '/' . $lang . '/account/posts?uid=';
$_is_institution = !empty($_current_member) && (int)($_current_member['type'] ?? 0) === 3;
$_category_labels = ['university' => 'College', 'vocational' => 'Vocational Education', 'highschool' => 'High School'];
$_category_icons  = ['university' => 'fa-university', 'vocational' => 'fa-wrench', 'highschool' => 'fa-book'];

$autoLang = !empty($_page_get_data['autolang']) ? $_page_get_data['autolang'] : session('autolang', '');
$appendAutoLang = function ($url) use ($autoLang) {
    if (empty($autoLang)) return $url;
    return $url . ((strpos($url, '?') !== false) ? '&' : '?') . 'autolang=' . urlencode($autoLang);
};
$wealthskeyUrl = 'https://wa.me/85298684187?text=' . rawurlencode('Hi, I am interested in studying or migrating abroad. Could you help me with my options?');
?>

<div class="ie-wrap">

    <!-- SIDEBAR -->
    <div class="ie-sidebar">
        <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/study_plans'), ENT_QUOTES); ?>">
            <span class="ie-sb-icon"><i class="fa fa-star"></i></span>
            <span class="ie-sb-label">Dreams</span>
        </a>
        <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/study_college_match'), ENT_QUOTES); ?>">
            <span class="ie-sb-icon"><i class="fa fa-graduation-cap"></i></span>
            <span class="ie-sb-label">Matches</span>
        </a>
        <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/nextgen_challenge'), ENT_QUOTES); ?>">
            <span class="ie-sb-icon"><i class="fa fa-trophy"></i></span>
            <span class="ie-sb-label">NextGen AI &amp;<br>Talent Challenge</span>
        </a>
        <?php if (!$_is_institution): ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/institution_explore'), ENT_QUOTES); ?>" class="active">
            <span class="ie-sb-icon"><i class="fa fa-building"></i></span>
            <span class="ie-sb-label">Colleges</span>
        </a>
        <?php else: ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/student_explore'), ENT_QUOTES); ?>">
            <span class="ie-sb-icon"><i class="fa fa-users"></i></span>
            <span class="ie-sb-label">Explore Students</span>
        </a>
        <?php endif; ?>
        <?php if (!empty($_current_member)): ?>
        <a href="<?php echo htmlspecialchars($appendAutoLang($_is_institution ? $base.'/institution_hub_profile' : ((int)($_current_member['type'] ?? 0) === 2 ? $base.'/account/posts' : $base.'/student_profile')), ENT_QUOTES); ?>">
            <span class="ie-sb-icon"><i class="fa fa-id-card"></i></span>
            <span class="ie-sb-label">My Profile</span>
        </a>
        <?php endif; ?>
        <a href="javascript:void(0);" class="do-toapply" data-sector="study" data-action-url="<?php echo htmlspecialchars($base.'/agent_chat', ENT_QUOTES); ?>">
            <?php if (!empty($_current_member) && !empty($_current_member['avatar'])): ?>
            <?php if (file_exists(public_path('upload/member_avatar/'.$_current_member['avatar']))): ?>
            <div class="ie-chat-av" style="background-image:url(upload/member_avatar/<?php echo htmlspecialchars($_current_member['avatar'], ENT_QUOTES); ?>)"></div>
            <?php else: ?>
            <div class="ie-chat-av ie-chat-av--init"><?php echo htmlspecialchars(mb_substr($_current_member['alias_name'] ?? 'A', 0, 1), ENT_QUOTES); ?></div>
            <?php endif; ?>
            <?php else: ?>
            <div class="ie-chat-av ie-chat-av--blank"></div>
            <?php endif; ?>
            <span class="ie-sb-label">Chat with<br>AI-mmi</span>
        </a>
    </div>

    <!-- MAIN -->
    <div class="ie-main">

        <!-- MOBILE PAGE TABS -->
        <div class="ie-page-tabs">
            <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/study_plans'), ENT_QUOTES); ?>" class="ie-page-tab">
                <i class="fa fa-star"></i>Dreams
            </a>
            <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/study_college_match'), ENT_QUOTES); ?>" class="ie-page-tab">
                <i class="fa fa-graduation-cap"></i>Match
            </a>
            <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/nextgen_challenge'), ENT_QUOTES); ?>" class="ie-page-tab">
                <i class="fa fa-trophy"></i>NextGen
            </a>
            <?php if (!$_is_institution): ?>
            <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/institution_explore'), ENT_QUOTES); ?>" class="ie-page-tab active">
                <i class="fa fa-building"></i>Colleges
            </a>
            <?php else: ?>
            <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/student_explore'), ENT_QUOTES); ?>" class="ie-page-tab">
                <i class="fa fa-users"></i>Students
            </a>
            <?php endif; ?>
            <?php if (!empty($_current_member)): ?>
            <a href="<?php echo htmlspecialchars($appendAutoLang($_is_institution ? $base.'/institution_hub_profile' : ((int)($_current_member['type'] ?? 0) === 2 ? $base.'/account/posts' : $base.'/student_profile')), ENT_QUOTES); ?>" class="ie-page-tab">
                <i class="fa fa-id-card"></i>My Profile
            </a>
            <?php endif; ?>
        </div>

        <!-- HERO BANNER -->
        <div class="ie-hero">
            <div class="ie-hero-particles">
                <span></span><span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span><span></span>
            </div>
            <div class="ie-hero-content">
                <div class="ie-hero-badge"><i class="fa fa-building"></i> College Explorer</div>
                <h1 class="ie-hero-title">Find Your Dream College</h1>
                <p class="ie-hero-sub">Browse partner institutions from around the world and explore their programs, courses, and opportunities.</p>
                <div class="ie-hero-stats">
                    <span class="ie-hero-stat"><i class="fa fa-university"></i> <?php echo number_format($total); ?> Institution<?php echo $total !== 1 ? 's' : ''; ?></span>
                    <span class="ie-hero-stat"><i class="fa fa-globe"></i> Global Partners</span>
                    <span class="ie-hero-stat"><i class="fa fa-graduation-cap"></i> Verified Profiles</span>
                </div>
            </div>
        </div>

        <!-- Light content area starts here (dark→light transition) -->
        <div class="ie-light-content">

        <!-- SEARCH BAR -->
        <div class="ie-search-bar">
            <form method="GET" action="" class="ie-search-form">
                <i class="fa fa-search ie-search-icon"></i>
                <input type="text" name="q" class="ie-search-input"
                    placeholder="Search by institution name, location, or program&hellip;"
                    value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>" />
                <?php if (!empty($category)): ?>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category, ENT_QUOTES); ?>">
                <?php endif; ?>
                <button type="submit" class="ie-search-btn">Search</button>
                <?php if (!empty($search) || !empty($category)): ?>
                <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/institution_explore'), ENT_QUOTES); ?>" class="ie-search-clear" title="Clear all filters"><i class="fa fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <!-- CATEGORY FILTER CHIPS -->
        <?php
        $allCats = ['university' => ['icon' => 'fa-university', 'label' => 'University'],
                    'vocational' => ['icon' => 'fa-wrench',     'label' => 'Vocational Education'],
                    'highschool' => ['icon' => 'fa-book',       'label' => 'High School']];
        ?>
        <div class="ie-cat-chips">
            <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/institution_explore'.(!empty($search) ? '?q='.urlencode($search) : '')), ENT_QUOTES); ?>"
               class="ie-cat-chip <?php echo empty($category) ? 'ie-cat-chip--active' : ''; ?>">
               <i class="fa fa-th-large"></i> All Types
            </a>
            <?php foreach ($allCats as $catVal => $catInfo): ?>
            <a href="<?php
                $catUrl = $base.'/institution_explore?category='.$catVal;
                if (!empty($search)) $catUrl .= '&q='.urlencode($search);
                echo htmlspecialchars($appendAutoLang($catUrl), ENT_QUOTES);
               ?>"
               class="ie-cat-chip <?php echo $category === $catVal ? 'ie-cat-chip--active' : ''; ?>">
               <i class="fa <?php echo $catInfo['icon']; ?>"></i> <?php echo $catInfo['label']; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- RESULT COUNT -->
        <div class="ie-result-bar">
            <span class="ie-result-count">
                <?php echo number_format($total); ?> institution<?php echo $total !== 1 ? 's' : ''; ?> found
                <?php if (!empty($category)): ?>
                &nbsp;&mdash;&nbsp;<i class="fa <?php echo $allCats[$category]['icon'] ?? 'fa-filter'; ?>"></i>
                <em><?php echo htmlspecialchars($allCats[$category]['label'] ?? $category, ENT_QUOTES); ?></em>
                <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/institution_explore'.(!empty($search) ? '?q='.urlencode($search) : '')), ENT_QUOTES); ?>" class="ie-filter-clear-link" title="Remove category filter"><i class="fa fa-times-circle"></i> Clear filter</a>
                <?php endif; ?>
                <?php if (!empty($search)): ?>
                for <em><?php echo htmlspecialchars($search, ENT_QUOTES); ?></em>
                <?php endif; ?>
            </span>
        </div>

        <?php if ($institutions->isEmpty()): ?>
        <!-- EMPTY STATE -->
        <div class="ie-empty">
            <?php
            $emptyBgMap = [
                'university' => 'https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=900&q=75',
                'vocational' => 'https://images.unsplash.com/photo-1504328345606-18bbc8c9d7d1?auto=format&fit=crop&w=900&q=75',
                'highschool' => 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=900&q=75',
            ];
            $emptyBg   = $emptyBgMap[$category] ?? 'https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=900&q=75';
            $emptyIcon = $_category_icons[$category] ?? 'fa-building-o';
            ?>
            <div class="ie-empty-hero" style="background-image:url('<?php echo $emptyBg; ?>')"><span class="ie-empty-hero-icon"><i class="fa <?php echo $emptyIcon; ?>"></i></span></div>
            <h3>No institutions found</h3>
            <?php if (!empty($category)): ?>
            <?php $catLabel = $_category_labels[$category] ?? ''; ?>
            <p>No <strong><?php echo htmlspecialchars($catLabel, ENT_QUOTES); ?></strong> institutions have joined the platform yet &mdash; check back soon!</p>
            <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/institution_explore'), ENT_QUOTES); ?>" class="ie-btn-clear">Browse All Types</a>
            <?php elseif (!empty($search)): ?>
            <p>No results match your search. Try a different keyword.</p>
            <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/institution_explore'), ENT_QUOTES); ?>" class="ie-btn-clear">Clear All Filters</a>
            <?php else: ?>
            <p>No institutions are available to browse at the moment.</p>
            <?php endif; ?>

            <!-- Migration CTA -->
            <div class="ibc-migration-cta" style="text-align:left;max-width:520px;margin:32px auto 0;">
                <div class="ibc-migration-divider"><span>Looking for more support?</span></div>
                <div class="ibc-migration-card">
                    <div class="ibc-migration-icon"><i class="fa fa-plane"></i></div>
                    <div class="ibc-migration-body">
                        <div class="ibc-migration-tag">MIGRATION SUPPORT</div>
                        <h3 class="ibc-migration-title">Interested in studying or migrating abroad?</h3>
                        <p class="ibc-migration-text">Our trusted partner <strong>Wealthskey Migration &amp; Education</strong> can guide you through the visa, migration, and study process. Connect with a qualified agent today.</p>
                        <a href="<?php echo htmlspecialchars($wealthskeyUrl, ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer" class="ibc-whatsapp-btn"><i class="fa fa-whatsapp"></i> Chat with Wealthskey on WhatsApp</a>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>

        <!-- INSTITUTION CARD GRID -->
        <div class="ie-grid">
            <?php foreach ($institutions as $inst):
                $profileId   = (int)($inst['id'] ?? 0);
                $instName    = htmlspecialchars(
                    trim($inst['institute_name'] ?? '') ?: trim($inst['alias_name'] ?? '') ?: 'Institution',
                    ENT_QUOTES, 'UTF-8'
                );
                $initial     = strtoupper(mb_substr(strip_tags($instName), 0, 1));
                $summary     = htmlspecialchars(mb_substr(strip_tags($inst['summary'] ?? ''), 0, 130), ENT_QUOTES, 'UTF-8');
                $location    = htmlspecialchars($inst['location'] ?? '', ENT_QUOTES, 'UTF-8');
                $coursesCount = (int)($inst['courses_count'] ?? 0);
                $profileUrl  = $profileId > 0
                    ? '/' . $lang . '/institution_hub_profile/pub_view/' . $profileId
                    : $acctPostsBase . ($inst['member_id'] ?? 0);

                // Logo
                $logo = null;
                if (!empty($inst['avatar'])) {
                    if (file_exists(public_path('upload/member_logo/'.$inst['avatar']))) {
                        $logo = '/upload/member_logo/'.htmlspecialchars($inst['avatar'], ENT_QUOTES);
                    } elseif (file_exists(public_path('upload/member_avatar/'.$inst['avatar']))) {
                        $logo = '/upload/member_avatar/'.htmlspecialchars($inst['avatar'], ENT_QUOTES);
                    }
                }
            ?>
            <a class="ie-card" href="<?php echo htmlspecialchars($appendAutoLang($profileUrl), ENT_QUOTES); ?>">
                <div class="ie-card-logo-wrap">
                    <?php if ($logo): ?>
                    <img src="<?php echo $logo; ?>" alt="<?php echo $instName; ?>" class="ie-card-logo" />
                    <?php else: ?>
                    <div class="ie-card-logo-placeholder" style="background:#f8fafc;color:#1d4ed8;border:2px solid rgba(29,78,216,0.15);"><?php echo htmlspecialchars($initial); ?></div>
                    <?php endif; ?>
                </div>
                <div class="ie-card-body">
                    <h3 class="ie-card-name"><?php echo $instName; ?></h3>
                    <?php if ($location): ?>
                    <div class="ie-card-location"><i class="fa fa-map-marker"></i> <?php echo $location; ?></div>
                    <?php endif; ?>
                    <?php if ($summary): ?>
                    <p class="ie-card-summary"><?php echo $summary; ?>&hellip;</p>
                    <?php endif; ?>
                    <div class="ie-card-footer">
                        <?php if ($coursesCount > 0): ?>
                        <span class="ie-card-badge"><i class="fa fa-book"></i> <?php echo $coursesCount; ?> program<?php echo $coursesCount !== 1 ? 's' : ''; ?></span>
                        <?php endif; ?>
                        <span class="ie-card-cta">View Profile &rarr;</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>

            <!-- ADVERTISE CARD -->
            <div class="ie-card ie-card-advertise">
                <div class="ie-card-logo-wrap">
                    <div class="ie-card-logo-placeholder ie-card-logo-advert"><i class="fa fa-bullhorn"></i></div>
                </div>
                <div class="ie-card-body">
                    <h3 class="ie-card-name">Feature Your College</h3>
                    <p class="ie-card-summary">Reach thousands of motivated international students seeking top programs worldwide.</p>
                    <div class="ie-card-footer">
                        <a href="https://wa.me/85298684187?text=Hi%2C%20I%27m%20interested%20in%20featuring%20my%20institution%20on%20AI-mmi.%20Could%20you%20tell%20me%20more%3F"
                            target="_blank" rel="noopener noreferrer"
                            class="ie-card-cta ie-card-cta-advert"
                            onclick="event.stopPropagation()">
                            Get Started &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- PAGINATION -->
        <?php if ($institutions->lastPage() > 1): ?>
        <div class="ie-pagination">
            <?php if ($institutions->currentPage() > 1): ?>
            <a href="<?php echo '?' . ($search ? 'q='.urlencode($search).'&' : '') . 'page=' . ($institutions->currentPage() - 1); ?>" class="ie-page-link"><i class="fa fa-chevron-left"></i> Prev</a>
            <?php endif; ?>
            <?php for ($p = max(1, $institutions->currentPage() - 2); $p <= min($institutions->lastPage(), $institutions->currentPage() + 2); $p++): ?>
            <a href="<?php echo '?' . ($search ? 'q='.urlencode($search).'&' : '') . 'page=' . $p; ?>"
                class="ie-page-link<?php echo $p === $institutions->currentPage() ? ' ie-page-active' : ''; ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
            <?php if ($institutions->currentPage() < $institutions->lastPage()): ?>
            <a href="<?php echo '?' . ($search ? 'q='.urlencode($search).'&' : '') . 'page=' . ($institutions->currentPage() + 1); ?>" class="ie-page-link">Next <i class="fa fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>

        </div><!-- /.ie-light-content -->

    </div><!-- /.ie-main -->

</div><!-- /.ie-wrap -->

<!-- MOBILE BOTTOM NAV -->
<nav class="ie-mobile-nav" aria-label="Page navigation">
    <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/study_plans'), ENT_QUOTES); ?>" class="ie-mn-item">
        <span class="ie-mn-icon"><i class="fa fa-star"></i></span>
        <span class="ie-mn-label">Dreams</span>
    </a>
    <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/study_college_match'), ENT_QUOTES); ?>" class="ie-mn-item">
        <span class="ie-mn-icon"><i class="fa fa-graduation-cap"></i></span>
        <span class="ie-mn-label">Match</span>
    </a>
    <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/nextgen_challenge'), ENT_QUOTES); ?>" class="ie-mn-item">
        <span class="ie-mn-icon"><i class="fa fa-trophy"></i></span>
        <span class="ie-mn-label">NextGen</span>
    </a>
    <?php if (!$_is_institution): ?>
    <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/institution_explore'), ENT_QUOTES); ?>" class="ie-mn-item active">
        <span class="ie-mn-icon"><i class="fa fa-building"></i></span>
        <span class="ie-mn-label">Colleges</span>
    </a>
    <?php else: ?>
    <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/student_explore'), ENT_QUOTES); ?>" class="ie-mn-item">
        <span class="ie-mn-icon"><i class="fa fa-users"></i></span>
        <span class="ie-mn-label">Students</span>
    </a>
    <?php endif; ?>
    <a href="javascript:void(0);" class="ie-mn-item do-toapply" data-sector="study" data-action-url="<?php echo htmlspecialchars($base.'/agent_chat', ENT_QUOTES); ?>">
        <span class="ie-mn-icon"><i class="fa fa-comments"></i></span>
        <span class="ie-mn-label">Chat</span>
    </a>
</nav>



@endsection
