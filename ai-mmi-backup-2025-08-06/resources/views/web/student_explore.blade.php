@extends('web.common')

@push('css')
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
main.page-body .page-content { margin-right: 0 !important; }
body { background: #0c1445 !important; }
/* Fix: info-area > div { position: relative } in common.css overrides position: fixed */
.sp-modal-bg,
.sp-modal { position: fixed !important; }
/* Grid overflow fix — cards must not expand beyond their column track */
.se-layout { grid-template-columns: 220px minmax(0,1fr) !important; }
@media (max-width:1400px) { .se-layout { grid-template-columns: 1fr !important; } }
.se-grid { grid-template-columns: repeat(3, minmax(0,1fr)) !important; }
@media (max-width:1600px) { .se-grid { grid-template-columns: repeat(2, minmax(0,1fr)) !important; } }
@media (max-width:1100px) { .se-grid { grid-template-columns: minmax(0,1fr) !important; } }
.se-card { min-width: 0 !important; }
.se-card-footer { flex-wrap: wrap !important; }
.se-main { min-width: 0 !important; overflow: hidden !important; }
.se-content-area { min-width: 0 !important; overflow: hidden !important; }
/* Hide fixed chat panel — this page has its own nav + chat link */
main.page-body .chat-area { display: none !important; }
main.page-body .mobile-chat-button { display: none !important; }
main.page-body .page-content { margin-right: 0 !important; }
</style>
@endpush

@section('content')
<?php
$students       = $_page_data['students'] ?? collect();
$myInterestIds  = $_page_data['my_interest_ids'] ?? [];
$nationalities  = $_page_data['nationalities'] ?? [];
$filters        = $_page_data['filters'] ?? [];
$lang           = $_lang ?? 'en';
$base           = rtrim($_page_base_url, '/');
$_is_institution = !empty($_current_member) && (int)($_current_member['type'] ?? 0) === 3;
$_se_autoLang = !empty($_page_get_data['autolang']) ? $_page_get_data['autolang'] : session('autolang', '');
$appendAutoLang = function ($url) use ($_se_autoLang) {
    if (empty($_se_autoLang)) return $url;
    return $url . ((strpos($url, '?') !== false) ? '&' : '?') . 'autolang=' . urlencode($_se_autoLang);
};

$interestUrl    = '/' . $lang . '/student_explore/interest';
$viewBase       = '/' . $lang . '/student_explore/view/';
$myInterestsUrl = '/' . $lang . '/student_explore/my_interests';

// Filter query string for pagination links
$filterQuery = http_build_query(array_filter($filters));
?>

<div class="se-outer-wrap">

<!-- NAV SIDEBAR -->
<div class="se-nav-sidebar">
    <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/study_plans'), ENT_QUOTES); ?>">
        <span class="se-nav-icon"><i class="fa fa-star"></i></span>
        <span class="se-nav-label">Dreams</span>
    </a>
    <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/study_college_match'), ENT_QUOTES); ?>">
        <span class="se-nav-icon"><i class="fa fa-graduation-cap"></i></span>
        <span class="se-nav-label">Matches</span>
    </a>
    <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/nextgen_challenge'), ENT_QUOTES); ?>">
        <span class="se-nav-icon"><i class="fa fa-trophy"></i></span>
        <span class="se-nav-label">NextGen AI &amp;<br>Talent Challenge</span>
    </a>
    <?php if (!$_is_institution): ?>
    <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/institution_explore'), ENT_QUOTES); ?>">
        <span class="se-nav-icon"><i class="fa fa-building"></i></span>
        <span class="se-nav-label">Colleges</span>
    </a>
    <?php else: ?>
    <a href="<?php echo htmlspecialchars($appendAutoLang($base.'/student_explore'), ENT_QUOTES); ?>" class="active">
        <span class="se-nav-icon"><i class="fa fa-users"></i></span>
        <span class="se-nav-label">Explore Students</span>
    </a>
    <?php endif; ?>
    <?php if (!empty($_current_member)): ?>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_is_institution ? $base.'/institution_hub_profile' : ((int)($_current_member['type'] ?? 0) === 2 ? $base.'/account/posts' : $base.'/student_profile')), ENT_QUOTES); ?>">
        <span class="se-nav-icon"><i class="fa fa-id-card"></i></span>
        <span class="se-nav-label">My Profile</span>
    </a>
    <?php endif; ?>
    <a href="javascript:void(0);" class="do-toapply" data-sector="study" data-action-url="<?php echo htmlspecialchars($base.'/agent_chat', ENT_QUOTES); ?>">
        <?php if (!empty($_current_member) && !empty($_current_member['avatar'])): ?>
        <?php if (file_exists(public_path('upload/member_avatar/'.$_current_member['avatar']))): ?>
        <div class="se-chat-av" style="background-image:url(upload/member_avatar/<?php echo htmlspecialchars($_current_member['avatar'], ENT_QUOTES); ?>)"></div>
        <?php else: ?>
        <div class="se-chat-av se-chat-av--init"><?php echo htmlspecialchars(mb_substr($_current_member['alias_name'] ?? 'A', 0, 1), ENT_QUOTES); ?></div>
        <?php endif; ?>
        <?php else: ?>
        <div class="se-chat-av se-chat-av--blank"></div>
        <?php endif; ?>
        <span class="se-nav-label">Chat with<br>AI-mmi</span>
    </a>
</div>
<!-- END NAV SIDEBAR -->

<div class="se-content-area">
<div class="se-wrap">

    {{-- Toast --}}
    <div class="sp-toast" id="sp-toast"></div>

    {{-- Page header --}}
    <div class="se-page-header">
        <div class="se-page-title">
            <h1>Explore Students</h1>
            <p>Discover talented students seeking international education. Express interest and our team will coordinate.</p>
        </div>
        <?php if ($_is_institution): ?>
        <a href="<?php echo $myInterestsUrl; ?>" class="se-shortlist-btn">
            <i class="fa fa-star"></i> My Interest List
        </a>
        <?php endif; // institution only ?>
    </div>

    <div class="se-layout">

        {{-- ── Filter sidebar ── --}}
        <aside class="se-sidebar">
            <div class="se-filter-card">
                <h3><i class="fa fa-filter"></i> Filters</h3>
                <form method="GET" id="se-filter-form">
                    <div class="se-filter-group">
                        <label>Degree Level</label>
                        <select name="degree" onchange="document.getElementById('se-filter-form').submit()">
                            <option value="">All Levels</option>
                            <?php foreach (['certificate','diploma','bachelor','master','phd'] as $d): ?>
                            <option value="<?php echo $d; ?>" <?php echo ($filters['degree'] ?? '') === $d ? 'selected' : ''; ?>><?php echo ucfirst($d); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="se-filter-group">
                        <label>Nationality</label>
                        <select name="nationality" onchange="document.getElementById('se-filter-form').submit()">
                            <option value="">All Nationalities</option>
                            <?php foreach ($nationalities as $n): ?>
                            <option value="<?php echo htmlspecialchars($n, ENT_QUOTES); ?>" <?php echo ($filters['nationality'] ?? '') === $n ? 'selected' : ''; ?>><?php echo htmlspecialchars($n, ENT_QUOTES); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!empty($filters['degree']) || !empty($filters['nationality'])): ?>
                    <a href="/<?php echo $lang; ?>/student_explore" class="se-clear-link">✕ Clear Filters</a>
                    <?php endif; ?>
                </form>

                <div class="se-sidebar-notice">
                    <i class="fa fa-shield"></i>
                    <span>AI-mmi protects all student privacy. No contact details are shared.</span>
                </div>
            </div>
        </aside>

        {{-- ── Main content ── --}}
        <div class="se-main">

            {{-- Result count --}}
            <div class="se-result-bar">
                <span><?php echo $students->total(); ?> student<?php echo $students->total() !== 1 ? 's' : ''; ?> found</span>
                <?php if (!empty($filters['degree']) || !empty($filters['nationality'])): ?>
                <span class="se-active-filters">
                    <?php if (!empty($filters['degree'])): ?><span class="se-filter-tag"><?php echo ucfirst($filters['degree']); ?></span><?php endif; ?>
                    <?php if (!empty($filters['nationality'])): ?><span class="se-filter-tag"><?php echo htmlspecialchars($filters['nationality'], ENT_QUOTES); ?></span><?php endif; ?>
                </span>
                <?php endif; ?>
            </div>

            <?php if ($students->isEmpty()): ?>
            <div class="se-empty">
                <div class="se-empty-icon">🎓</div>
                <p>No students found matching your filters.</p>
                <a href="/<?php echo $lang; ?>/student_explore" class="se-clear-link">Clear Filters</a>
            </div>
            <?php else: ?>

            {{-- Student card grid --}}
            <div class="se-grid">
                <?php foreach ($students as $s): ?>
                <?php
                    $sId         = (int)$s->member_id;
                    $sName       = trim($s->alias_name ?? '') ?: trim(($s->first_name ?? '') . ' ' . mb_substr($s->last_name ?? '', 0, 1) . '.');
                    if (!$sName) $sName = 'Student';
                    $sInitial    = strtoupper(mb_substr($sName, 0, 1));
                    $isInterested = in_array($sId, $myInterestIds);
                    $sFields     = json_decode($s->target_fields ?? '[]', true) ?: [];
                    $sLang       = json_decode($s->language_scores ?? '[]', true) ?: [];
                    $topLang     = !empty($sLang[0]) ? ($sLang[0]['test'] . ' ' . $sLang[0]['score']) : '';

                    // Avatar
                    $avatarContent = '';
                    if (!empty($s->avatar)) {
                        $aPath = public_path('upload/member_avatar/' . $s->avatar);
                        if (file_exists($aPath)) {
                            $avatarContent = '<div class="se-card-avatar-img" style="background-image:url(\'/upload/member_avatar/' . htmlspecialchars($s->avatar, ENT_QUOTES) . '\')"></div>';
                        }
                    }
                    if (!$avatarContent) {
                        $avatarContent = '<div class="se-card-avatar-initial">' . htmlspecialchars($sInitial) . '</div>';
                    }
                ?>
                <div class="se-card<?php echo $isInterested ? ' se-card-interested' : ''; ?>" data-id="<?php echo $sId; ?>">
                    <div class="se-card-top">
                        <div class="se-card-avatar"><?php echo $avatarContent; ?></div>
                        <?php if ($isInterested): ?>
                        <span class="se-interested-chip"><i class="fa fa-check"></i> Interested</span>
                        <?php endif; ?>
                    </div>
                    <div class="se-card-body">
                        <h3 class="se-card-name"><?php echo htmlspecialchars($sName, ENT_QUOTES); ?></h3>
                        <?php if (!empty($s->headline)): ?>
                        <p class="se-card-headline"><?php echo htmlspecialchars($s->headline, ENT_QUOTES); ?></p>
                        <?php endif; ?>
                        <div class="se-card-badges">
                            <?php if (!empty($s->nationality)): ?>
                            <span class="se-badge">🌍 <?php echo htmlspecialchars($s->nationality, ENT_QUOTES); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($s->target_degree)): ?>
                            <span class="se-badge se-badge-blue">🎓 <?php echo htmlspecialchars(ucfirst($s->target_degree), ENT_QUOTES); ?></span>
                            <?php endif; ?>
                            <?php if ($topLang): ?>
                            <span class="se-badge se-badge-green">📋 <?php echo htmlspecialchars($topLang, ENT_QUOTES); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($sFields)): ?>
                        <div class="se-card-fields">
                            <?php foreach (array_slice($sFields, 0, 3) as $f): ?>
                            <span class="se-field-tag"><?php echo htmlspecialchars($f, ENT_QUOTES); ?></span>
                            <?php endforeach; ?>
                            <?php if (count($sFields) > 3): ?>
                            <span class="se-field-tag se-field-more">+<?php echo count($sFields) - 3; ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="se-card-footer">
                        <a href="<?php echo $viewBase . $sId; ?>" class="se-view-btn">View Profile</a>
                        <?php if ($isInterested): ?>
                        <button class="se-interest-btn se-interest-btn-done" disabled>✓ Interested</button>
                        <?php elseif ($_is_institution): ?>
                        <button class="se-interest-btn" onclick="seExpressInterest(<?php echo $sId; ?>, this)">★ Express Interest</button>
                        <?php else: ?>
                        <a href="<?php echo htmlspecialchars($base . '/account_login', ENT_QUOTES); ?>" class="se-interest-btn" style="text-decoration:none;">★ Express Interest</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            {{-- Pagination --}}
            <?php if ($students->lastPage() > 1): ?>
            <div class="se-pagination">
                <?php if ($students->currentPage() > 1): ?>
                <a href="<?php echo '?' . ($filterQuery ? $filterQuery . '&' : '') . 'page=' . ($students->currentPage() - 1); ?>" class="se-page-link se-page-prev"><i class="fa fa-chevron-left"></i> Prev</a>
                <?php endif; ?>
                <?php for ($p = max(1, $students->currentPage() - 2); $p <= min($students->lastPage(), $students->currentPage() + 2); $p++): ?>
                <a href="<?php echo '?' . ($filterQuery ? $filterQuery . '&' : '') . 'page=' . $p; ?>"
                    class="se-page-link<?php echo $p === $students->currentPage() ? ' se-page-active' : ''; ?>"><?php echo $p; ?></a>
                <?php endfor; ?>
                <?php if ($students->currentPage() < $students->lastPage()): ?>
                <a href="<?php echo '?' . ($filterQuery ? $filterQuery . '&' : '') . 'page=' . ($students->currentPage() + 1); ?>" class="se-page-link se-page-next">Next <i class="fa fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>

    </div>
</div>
</div><!-- /.se-content-area -->
</div><!-- /.se-outer-wrap -->

<script>
const SE_INTEREST_URL = '<?php echo $interestUrl; ?>';
const SE_TOKEN        = '<?php echo csrf_token(); ?>';

function seExpressInterest(studentId, btn) {
    btn.disabled = true; btn.textContent = 'Sending…';
    fetch(SE_INTEREST_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': SE_TOKEN },
        body: JSON.stringify({ student_id: studentId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 200) {
            btn.textContent = '✓ Interested';
            btn.classList.add('se-interest-btn-done');
            const card = btn.closest('.se-card');
            if (card) card.classList.add('se-card-interested');
            spToast('✓ ' + res.message, 'ok');
        } else {
            btn.disabled = false; btn.textContent = '★ Express Interest';
            spToast('⚠ ' + (res.message || 'Error'), 'err');
        }
    })
    .catch(() => {
        btn.disabled = false; btn.textContent = '★ Express Interest';
        spToast('⚠ Network error, please try again', 'err');
    });
}

function spToast(msg, type) {
    const t = document.getElementById('sp-toast');
    t.textContent = msg;
    t.className = 'sp-toast sp-toast-' + type + ' sp-toast-show';
    setTimeout(() => t.classList.remove('sp-toast-show'), 3500);
}
</script>
@endsection
