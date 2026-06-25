@extends('web.common')

@push('css')
<link href="/asset/css/web/student_profile.css?v=<?php echo date('Ymd'); ?>" rel="stylesheet">
<style>
main.page-body .page-content { margin-right: 450px !important; max-width: 100% !important; }
main.page-body .info-area {
    width: 100% !important;
    float: none !important;
    background: #f3f2ef !important;
    background-image: none !important;
    background-color: #f3f2ef !important;
    min-height: 100vh !important;
}
main.page-body .info-area::before { display: none !important; }
body { background: #f3f2ef !important; }
/* Fix: info-area > div { position: relative } in common.css overrides position: fixed */
.sp-modal-bg,
.sp-modal { position: fixed !important; }
</style>
@endpush

@section('content')
<?php
$profile          = $_page_data['profile'] ?? null;
$alreadyInterested = $_page_data['already_interested'] ?? false;
$lang             = $_lang ?? 'en';
$interestUrl      = '/' . $lang . '/student_explore/interest';

if (!$profile) { echo '<p style="color:#fff;padding:40px;">Profile not found.</p>'; return; }

$displayName = trim($profile->alias_name ?? '') ?: trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')) ?: 'Student';
$initial     = strtoupper(mb_substr($displayName, 0, 1));

$eduHistory   = json_decode($profile->education_history ?? '[]', true) ?: [];
$langScores   = json_decode($profile->language_scores   ?? '[]', true) ?: [];
$achievements = json_decode($profile->achievements      ?? '[]', true) ?: [];
$workExp      = json_decode($profile->work_experience   ?? '[]', true) ?: [];
$targetFields = json_decode($profile->target_fields     ?? '[]', true) ?: [];

$avatarHtml = '';
if (!empty($profile->avatar)) {
    $avatarPath = public_path('upload/member_avatar/' . $profile->avatar);
    if (file_exists($avatarPath)) {
        $avatarHtml = '<div class="sp-avatar-img" style="background-image:url(\'/upload/member_avatar/' . htmlspecialchars($profile->avatar, ENT_QUOTES) . '\')"></div>';
    }
}
if (!$avatarHtml) {
    $avatarHtml = '<div class="sp-avatar-initial">' . htmlspecialchars($initial) . '</div>';
}

// Cover photo
$coverStyle = '';
$coverPhoto = $profile->coverphoto ?? '';
if (!empty($coverPhoto)) {
    $cpPath = 'upload/member_coverphoto/' . $coverPhoto;
    if (file_exists(public_path($cpPath))) {
        $coverStyle = 'background-image:url(\'/' . $cpPath . '\'); background-size:cover; background-position:center top;';
    }
}
?>

{{-- Toast --}}
<div class="sp-toast" id="sp-toast"></div>

<div class="sp-wrap">

    {{-- Back link --}}
    <a href="/<?php echo $lang; ?>/student_explore" class="sp-back-link">
        <i class="fa fa-arrow-left"></i> Back to Explore Students
    </a>

    {{-- Hero Card (read-only) --}}
    <div class="sp-hero-card sp-hero-readonly">
        <div class="sp-hero-cover" style="<?php echo $coverStyle; ?>"></div>
        <div class="sp-hero-body">
            <div class="sp-avatar-wrap"><?php echo $avatarHtml; ?></div>
            <div class="sp-hero-info">
                <h1 class="sp-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES); ?></h1>
                <?php if (!empty($profile->headline)): ?>
                <p class="sp-headline"><?php echo htmlspecialchars($profile->headline, ENT_QUOTES); ?></p>
                <?php endif; ?>
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
            <div class="sp-interest-area">
                <?php if ($alreadyInterested): ?>
                <div class="sp-interested-badge">
                    <i class="fa fa-check"></i> On Your Interest List
                </div>
                <?php else: ?>
                <button class="sp-interest-btn" id="sp-interest-btn"
                    onclick="spExpressInterest(<?php echo (int)$profile->member_id; ?>)">
                    ★ Express Interest
                </button>
                <?php endif; ?>
                <p class="sp-interest-note">AI-mmi will coordinate on your behalf</p>
            </div>
        </div>
    </div>

    <div class="sp-body-grid">

        {{-- LEFT COLUMN --}}
        <div class="sp-col-main">

            <?php if (!empty($profile->bio)): ?>
            <div class="sp-card">
                <div class="sp-card-header"><h2><i class="fa fa-user"></i> About</h2></div>
                <p class="sp-bio-text"><?php echo nl2br(htmlspecialchars($profile->bio, ENT_QUOTES)); ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($eduHistory)): ?>
            <div class="sp-card">
                <div class="sp-card-header"><h2><i class="fa fa-graduation-cap"></i> Education</h2></div>
                <?php foreach ($eduHistory as $edu): ?>
                <div class="sp-item">
                    <div class="sp-item-icon">🏛</div>
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
            </div>
            <?php endif; ?>

            <?php if (!empty($workExp)): ?>
            <div class="sp-card">
                <div class="sp-card-header"><h2><i class="fa fa-briefcase"></i> Work & Extracurricular</h2></div>
                <?php foreach ($workExp as $w): ?>
                <div class="sp-item">
                    <div class="sp-item-icon">💼</div>
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
            </div>
            <?php endif; ?>

            <?php if (!empty($achievements)): ?>
            <div class="sp-card">
                <div class="sp-card-header"><h2><i class="fa fa-trophy"></i> Achievements & Awards</h2></div>
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
            </div>
            <?php endif; ?>

        </div>

        {{-- RIGHT COLUMN --}}
        <div class="sp-col-side">

            <?php if (!empty($profile->target_degree) || !empty($targetFields)): ?>
            <div class="sp-card">
                <div class="sp-card-header"><h2><i class="fa fa-compass"></i> Target Study</h2></div>
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
            </div>
            <?php endif; ?>

            <?php if (!empty($langScores)): ?>
            <div class="sp-card">
                <div class="sp-card-header"><h2><i class="fa fa-language"></i> Language Scores</h2></div>
                <?php foreach ($langScores as $ls): ?>
                <div class="sp-lang-row">
                    <span class="sp-lang-test"><?php echo htmlspecialchars($ls['test'] ?? '', ENT_QUOTES); ?></span>
                    <span class="sp-lang-score"><?php echo htmlspecialchars($ls['score'] ?? '', ENT_QUOTES); ?></span>
                    <?php if (!empty($ls['date'])): ?><span class="sp-lang-date"><?php echo htmlspecialchars($ls['date'], ENT_QUOTES); ?></span><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            {{-- AI-mmi contact note --}}
            <div class="sp-card sp-card-cta">
                <div class="sp-card-header"><h2><i class="fa fa-shield"></i> Safe Connection</h2></div>
                <p class="sp-cta-text">All communications are handled through <strong>AI-mmi</strong>. We act as a trusted intermediary so you never contact students directly.</p>
                <?php if (!$alreadyInterested): ?>
                <button class="sp-interest-btn sp-interest-btn-block" id="sp-interest-btn2"
                    onclick="spExpressInterest(<?php echo (int)$profile->member_id; ?>)">
                    ★ Express Interest in This Student
                </button>
                <?php else: ?>
                <div class="sp-interested-badge sp-interested-badge-block">
                    <i class="fa fa-check"></i> You have expressed interest — AI-mmi will be in touch
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

</div>

<script>
const SP_INTEREST_URL = '<?php echo '/' . $lang . '/student_explore/interest'; ?>';
const SP_TOKEN        = '<?php echo csrf_token(); ?>';

function spExpressInterest(studentId) {
    const btn  = document.getElementById('sp-interest-btn');
    const btn2 = document.getElementById('sp-interest-btn2');
    if (btn)  { btn.disabled  = true; btn.textContent  = 'Sending…'; }
    if (btn2) { btn2.disabled = true; btn2.textContent = 'Sending…'; }

    fetch(SP_INTEREST_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': SP_TOKEN },
        body: JSON.stringify({ student_id: studentId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 200) {
            spToast('✓ ' + res.message, 'ok');
            setTimeout(() => location.reload(), 1200);
        } else {
            if (btn)  { btn.disabled  = false; btn.textContent  = '★ Express Interest'; }
            if (btn2) { btn2.disabled = false; btn2.textContent = '★ Express Interest in This Student'; }
            spToast('⚠ ' + (res.message || 'Error'), 'err');
        }
    })
    .catch(() => {
        if (btn)  { btn.disabled  = false; btn.textContent  = '★ Express Interest'; }
        if (btn2) { btn2.disabled = false; btn2.textContent = '★ Express Interest in This Student'; }
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
