@extends('web.common')

@push('css')
<link href="/asset/css/web/student_profile.css?v=<?php echo date('Ymd'); ?>" rel="stylesheet">
<link href="/asset/css/web/job_profile.css?v=<?php echo date('Ymd'); ?>" rel="stylesheet">
@endpush

@section('content')
<?php
if (!empty($_page_data['is_guest'])):
?>
<div class="jp-guest-prompt">
    <div style="font-size:3rem;margin-bottom:16px;">&#128188;</div>
    <h2>My Job Profile</h2>
    <p>Log in to build your job seeker profile and apply to overseas opportunities.</p>
    <a href="<?php echo htmlspecialchars($_page_base_url . '/account_login?redirect=' . urlencode($_page_base_url . '/job_profile'), ENT_QUOTES); ?>" class="jp-guest-btn">Log In</a>
    <a href="<?php echo htmlspecialchars($_page_base_url . '/account_registration', ENT_QUOTES); ?>" class="jp-guest-link">Create an account</a>
</div>
<?php else:
$profile      = $_page_data['profile'] ?? null;
$completeness = (int)($_page_data['completeness'] ?? 0);
$member       = $_current_member;
$displayName  = trim($member['alias_name'] ?? '') ?: trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')) ?: 'Your Profile';
$initial      = strtoupper(mb_substr($displayName, 0, 1));
$eduHistory   = json_decode($profile ? ($profile->education_history ?? '[]') : '[]', true) ?: [];
$workExp      = json_decode($profile ? ($profile->work_experience ?? '[]') : '[]', true) ?: [];
$skills       = json_decode($profile ? ($profile->skills ?? '[]') : '[]', true) ?: [];
$langScores   = json_decode($profile ? ($profile->language_scores ?? '[]') : '[]', true) ?: [];
$targetRoles  = json_decode($profile ? ($profile->target_roles ?? '[]') : '[]', true) ?: [];
$targetLocs   = json_decode($profile ? ($profile->target_locations ?? '[]') : '[]', true) ?: [];
$csrf         = csrf_token();
$base         = $_page_base_url;

$avatarHtml = '<div class="sp-avatar-initial">' . htmlspecialchars($initial, ENT_QUOTES) . '</div>';
if (!empty($member['avatar']) && file_exists(public_path('upload/member_avatar/' . $member['avatar']))) {
    $avatarHtml = '<div class="sp-avatar-img" style="background-image:url(upload/member_avatar/' . htmlspecialchars($member['avatar'], ENT_QUOTES) . ')"></div>';
}
?>

<div class="mp-layout jp-profile-layout">
    <div class="mp-sidebar jp-mp-sidebar">
        <a href="<?php echo htmlspecialchars($base.'/job_applications', ENT_QUOTES); ?>">
            <span class="mp-sb-icon"><i class="fa fa-search"></i></span>
            <span class="mp-sb-label">Job Search</span>
        </a>
        <a href="<?php echo htmlspecialchars($base.'/job_profile', ENT_QUOTES); ?>" class="active">
            <span class="mp-sb-icon"><i class="fa fa-id-card"></i></span>
            <span class="mp-sb-label">My Profile</span>
        </a>
        <a href="<?php echo htmlspecialchars($base.'/job_applications#jp-tracker', ENT_QUOTES); ?>">
            <span class="mp-sb-icon"><i class="fa fa-bookmark"></i></span>
            <span class="mp-sb-label">Applications</span>
        </a>
    </div>

    <div class="mp-main">
        <div class="sp-wrap">
            <div class="sp-toast" id="jp-prof-toast"></div>

            <div class="sp-hero-card">
                <div class="sp-hero-cover jp-hero-cover"></div>
                <div class="sp-hero-body">
                    <div class="sp-avatar-col">
                        <div class="sp-avatar-wrap"><?php echo $avatarHtml; ?></div>
                    </div>
                    <div class="sp-hero-info">
                        <h1 class="sp-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES); ?></h1>
                        <p class="sp-headline" id="jp-headline-text">
                            <?php echo ($profile && !empty($profile->headline))
                                ? htmlspecialchars($profile->headline, ENT_QUOTES)
                                : '<span class="sp-placeholder">Add a headline — e.g. "Software Engineer open to roles in Australia"</span>'; ?>
                        </p>
                        <div class="sp-hero-badges">
                            <?php if (!empty($profile->open_to_work)): ?>
                            <span class="sp-badge sp-badge-green">&#128994; Open to work</span>
                            <?php endif; ?>
                            <?php if (!empty($profile->current_country)): ?>
                            <span class="sp-badge">&#128205; <?php echo htmlspecialchars($profile->current_country, ENT_QUOTES); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button class="sp-edit-btn" onclick="jpOpenModal('hero')"><i class="fa fa-pencil"></i> Edit</button>
                </div>
                <div class="sp-completeness">
                    <div class="sp-comp-label"><span>Profile completeness</span><span class="sp-comp-pct" id="jp-comp-pct"><?php echo $completeness; ?>%</span></div>
                    <div class="sp-comp-bar"><div class="sp-comp-fill" id="jp-comp-fill" style="width:<?php echo $completeness; ?>%"></div></div>
                </div>
            </div>

            <div class="sp-light-content">
                <div class="sp-body-grid">
                    <div class="sp-col-main">
                        <div class="sp-card">
                            <div class="sp-card-header"><h2><i class="fa fa-user"></i> About</h2>
                                <button class="sp-edit-btn" onclick="jpOpenModal('bio')"><i class="fa fa-pencil"></i> Edit</button></div>
                            <?php if (!empty($profile->bio)): ?>
                            <p class="sp-bio-text"><?php echo nl2br(htmlspecialchars($profile->bio, ENT_QUOTES)); ?></p>
                            <?php else: ?>
                            <p class="sp-empty-hint">Tell employers about your experience and career goals.</p>
                            <?php endif; ?>
                        </div>

                        <div class="sp-card">
                            <div class="sp-card-header"><h2><i class="fa fa-briefcase"></i> Experience</h2>
                                <button class="sp-edit-btn" onclick="jpOpenModal('work')"><i class="fa fa-pencil"></i> Edit</button></div>
                            <?php if (!empty($workExp)): foreach ($workExp as $w): ?>
                            <div class="sp-item">
                                <div class="sp-item-body">
                                    <div class="sp-item-title"><?php echo htmlspecialchars($w['title'] ?? '', ENT_QUOTES); ?></div>
                                    <div class="sp-item-sub"><?php echo htmlspecialchars($w['company'] ?? '', ENT_QUOTES); ?></div>
                                    <div class="sp-item-meta"><?php echo htmlspecialchars(($w['from'] ?? '') . ' – ' . (($w['current'] ?? false) ? 'Present' : ($w['to'] ?? '')), ENT_QUOTES); ?></div>
                                </div>
                            </div>
                            <?php endforeach; else: ?>
                            <p class="sp-empty-hint">Add your work experience.</p>
                            <?php endif; ?>
                        </div>

                        <div class="sp-card">
                            <div class="sp-card-header"><h2><i class="fa fa-graduation-cap"></i> Education</h2>
                                <button class="sp-edit-btn" onclick="jpOpenModal('education')"><i class="fa fa-pencil"></i> Edit</button></div>
                            <?php if (!empty($eduHistory)): foreach ($eduHistory as $edu): ?>
                            <div class="sp-item">
                                <div class="sp-item-body">
                                    <div class="sp-item-title"><?php echo htmlspecialchars(($edu['degree'] ?? '') . ' in ' . ($edu['field'] ?? ''), ENT_QUOTES); ?></div>
                                    <div class="sp-item-sub"><?php echo htmlspecialchars($edu['institution'] ?? '', ENT_QUOTES); ?></div>
                                </div>
                            </div>
                            <?php endforeach; else: ?>
                            <p class="sp-empty-hint">Add your education history.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="sp-col-side">
                        <div class="sp-card">
                            <div class="sp-card-header"><h2><i class="fa fa-file-pdf-o"></i> Resume</h2></div>
                            <?php if (!empty($profile->resume_path)): ?>
                            <p><a href="/<?php echo htmlspecialchars($profile->resume_path, ENT_QUOTES); ?>" target="_blank" rel="noopener">View uploaded resume</a></p>
                            <?php endif; ?>
                            <label class="jp-upload-btn">
                                <i class="fa fa-upload"></i> Upload resume (PDF/DOC)
                                <input type="file" id="jp-resume-input" accept=".pdf,.doc,.docx" style="display:none">
                            </label>
                        </div>

                        <div class="sp-card">
                            <div class="sp-card-header"><h2><i class="fa fa-star"></i> Skills</h2>
                                <button class="sp-edit-btn" onclick="jpOpenModal('skills')"><i class="fa fa-pencil"></i> Edit</button></div>
                            <div class="sp-tags">
                                <?php if (!empty($skills)): foreach ($skills as $s): ?>
                                <span class="sp-tag"><?php echo htmlspecialchars($s, ENT_QUOTES); ?></span>
                                <?php endforeach; else: ?>
                                <p class="sp-empty-hint">Add skills to stand out.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="sp-card">
                            <div class="sp-card-header"><h2><i class="fa fa-sliders"></i> Preferences</h2>
                                <button class="sp-edit-btn" onclick="jpOpenModal('preferences')"><i class="fa fa-pencil"></i> Edit</button></div>
                            <?php if (!empty($targetRoles)): ?>
                            <p><strong>Target roles:</strong> <?php echo htmlspecialchars(implode(', ', $targetRoles), ENT_QUOTES); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($targetLocs)): ?>
                            <p><strong>Locations:</strong> <?php echo htmlspecialchars(implode(', ', $targetLocs), ENT_QUOTES); ?></p>
                            <?php endif; ?>
                            <?php if (empty($targetRoles) && empty($targetLocs)): ?>
                            <p class="sp-empty-hint">Set your job preferences.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modals --}}
<div class="sp-modal-bg" id="jp-modal-bg" style="display:none;">
    <div class="sp-modal" id="jp-modal">
        <button class="sp-modal-close" onclick="jpCloseModal()">&times;</button>
        <h3 id="jp-modal-title">Edit</h3>
        <div id="jp-modal-body"></div>
        <div class="sp-modal-footer">
            <button class="sp-btn-cancel" onclick="jpCloseModal()">Cancel</button>
            <button class="sp-btn-save" id="jp-modal-save">Save</button>
        </div>
    </div>
</div>

<script>
window.jpProfConfig = {
    csrf: <?php echo json_encode($csrf); ?>,
    baseUrl: <?php echo json_encode($base); ?>,
    profile: <?php echo json_encode([
        'headline' => $profile ? ($profile->headline ?? '') : '',
        'bio' => $profile ? ($profile->bio ?? '') : '',
        'nationality' => $profile ? ($profile->nationality ?? '') : '',
        'current_country' => $profile ? ($profile->current_country ?? '') : '',
        'current_city' => $profile ? ($profile->current_city ?? '') : '',
        'open_to_work' => $profile ? ($profile->open_to_work ?? '') : '',
        'employment_preference' => $profile ? ($profile->employment_preference ?? '') : '',
        'target_roles' => $targetRoles,
        'target_locations' => $targetLocs,
        'work_experience' => $workExp,
        'education_history' => $eduHistory,
        'skills' => $skills,
        'language_scores' => $langScores,
    ]); ?>
};
</script>
<?php endif; ?>
@endsection

@push('scripts')
<script src="/asset/js/web/job_profile.js?v=<?php echo date('YmdHi'); ?>"></script>
@endpush
