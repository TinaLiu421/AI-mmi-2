@extends('web.common')

@push('css')
<link href="/asset/css/web/job_applications.css?v=<?php echo date('YmdHi'); ?>" rel="stylesheet">
@endpush

@section('content')
<?php
$jobs           = $_page_data['jobs'] ?? [];
$jobCount       = (int)($_page_data['job_count'] ?? count($jobs));
$appliedIds     = $_page_data['applied_ids'] ?? [];
$myApplications = $_page_data['my_applications'] ?? [];
$profile        = $_page_data['profile'] ?? null;
$completeness   = (int)($_page_data['profile_completeness'] ?? 0);
$searchQ        = $_page_data['search_q'] ?? '';
$searchCountry  = $_page_data['search_country'] ?? '';
$searchType     = $_page_data['search_type'] ?? '';
$isJobAdmin     = !empty($_page_data['is_job_admin']);
$isGuest        = !empty($_page_data['is_guest']);
$canApply       = !empty($_page_data['can_apply']);
$memberType     = (int)($_page_data['member_type'] ?? 0);
$member         = $_current_member ?? null;
$csrf           = csrf_token();
$base           = $_page_base_url;

$displayName = 'Guest';
$initial     = 'G';
$headline    = '';
$location    = '';
$avatarUrl   = '';
if ($member) {
    $displayName = trim($member['alias_name'] ?? '') ?: trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')) ?: 'Member';
    $initial     = strtoupper(mb_substr($displayName, 0, 1));
    if (!empty($member['avatar']) && file_exists(public_path('upload/member_avatar/' . $member['avatar']))) {
        $avatarUrl = 'upload/member_avatar/' . $member['avatar'];
    }
    if ($profile) {
        $headline = $profile->headline ?? '';
        $location = trim(($profile->current_city ?? '') . ($profile->current_country ? (($profile->current_city ?? '') ? ', ' : '') . $profile->current_country : ''));
    }
}

$typeLabels = [
    'full_time'  => 'Full-time',
    'part_time'  => 'Part-time',
    'internship' => 'Internship',
    'contract'   => 'Contract',
    'remote'     => 'Remote',
];
$locLabels = [
    'on_site' => 'On-site',
    'remote'  => 'Remote',
    'hybrid'  => 'Hybrid',
];

function jp_time_ago($dt) {
    if (empty($dt)) return '';
    $ts = strtotime($dt);
    if (!$ts) return '';
    $diff = time() - $ts;
    if ($diff < 3600) return 'Just now';
    if ($diff < 86400) return 'Today';
    if ($diff < 604800) {
        $d = (int) floor($diff / 86400);
        return $d . ' day' . ($d > 1 ? 's' : '') . ' ago';
    }
    if ($diff < 2592000) {
        $w = (int) floor($diff / 604800);
        return $w . ' week' . ($w > 1 ? 's' : '') . ' ago';
    }
    return date('M j, Y', $ts);
}

function jp_status_label($status) {
    $map = [
        'submitted'   => 'Submitted',
        'reviewed'    => 'Under review',
        'shortlisted' => 'Shortlisted',
        'rejected'    => 'Not selected',
        'hired'       => 'Hired',
    ];
    return $map[$status] ?? ucfirst($status);
}

function jp_logo_url($job) {
    $logo = trim((string)($job['company_logo'] ?? ''));
    if ($logo === '') {
        return '';
    }
    $path = ltrim($logo, '/');
    if (strpos($path, '..') !== false) {
        return '';
    }
    if (file_exists(public_path($path))) {
        return '/' . $path;
    }
    return '';
}
?>

<div class="jp-page">
    <div class="jp-layout">

        <aside class="jp-sidebar">
            <div class="jp-profile-card">
                <div class="jp-profile-cover"></div>
                <div class="jp-profile-body">
                    <?php if ($avatarUrl): ?>
                    <div class="jp-profile-avatar jp-profile-avatar-img" style="background-image:url('<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES); ?>')"></div>
                    <?php else: ?>
                    <div class="jp-profile-avatar"><?php echo htmlspecialchars($initial, ENT_QUOTES); ?></div>
                    <?php endif; ?>
                    <?php if ($isGuest): ?>
                    <span class="jp-profile-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES); ?></span>
                    <?php else: ?>
                    <a href="<?php echo htmlspecialchars($base . '/job_profile', ENT_QUOTES); ?>" class="jp-profile-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES); ?></a>
                    <?php endif; ?>
                    <?php if ($headline): ?>
                    <p class="jp-profile-headline"><?php echo htmlspecialchars($headline, ENT_QUOTES); ?></p>
                    <?php else: ?>
                    <p class="jp-profile-headline jp-muted"><?php echo $isGuest ? 'Sign in to build your job profile' : 'Add a headline on your job profile'; ?></p>
                    <?php endif; ?>
                    <?php if ($location): ?><p class="jp-profile-loc"><?php echo htmlspecialchars($location, ENT_QUOTES); ?></p><?php endif; ?>
                    <?php if (!$isGuest && $canApply): ?>
                    <div class="jp-profile-progress">
                        <div class="jp-progress-bar"><div class="jp-progress-fill" style="width:<?php echo $completeness; ?>%"></div></div>
                        <span><?php echo $completeness; ?>% profile complete</span>
                        <?php if ($completeness < 70): ?>
                        <a href="<?php echo htmlspecialchars($base . '/job_profile', ENT_QUOTES); ?>" class="jp-profile-cta">Complete profile</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <nav class="jp-side-nav">
                <a href="<?php echo htmlspecialchars($base . '/job_profile', ENT_QUOTES); ?>" class="jp-side-link"><i class="fa fa-id-card"></i> My job profile</a>
                <a href="#jp-tracker" class="jp-side-link"><i class="fa fa-bookmark"></i> Job tracker <span class="jp-badge"><?php echo count($myApplications); ?></span></a>
                <a href="<?php echo htmlspecialchars($base . '/job_applications', ENT_QUOTES); ?>" class="jp-side-link active"><i class="fa fa-briefcase"></i> Job search</a>
            </nav>

            <?php if ($isJobAdmin): ?>
            <button type="button" class="jp-post-job-btn" id="jp-open-post-modal"><i class="fa fa-plus"></i> Post a job</button>
            <?php endif; ?>

            <?php if ($isGuest): ?>
            <a href="<?php echo htmlspecialchars($base . '/account_login?redirect=' . urlencode($base . '/job_applications'), ENT_QUOTES); ?>" class="jp-signin-card">
                <strong>Sign in for the full experience</strong>
                <span>Apply to jobs and track your applications</span>
            </a>
            <?php endif; ?>
        </aside>

        <div class="jp-content-area">
            <main class="jp-main">
                <div class="jp-search-card">
                    <form method="get" action="<?php echo htmlspecialchars($base . '/job_applications', ENT_QUOTES); ?>" class="jp-search-form" id="jp-search-form">
                        <div class="jp-search-row">
                            <i class="fa fa-search"></i>
                            <input type="text" name="q" value="<?php echo htmlspecialchars($searchQ, ENT_QUOTES); ?>" placeholder="Search jobs, companies, or keywords" aria-label="Search jobs">
                        </div>
                        <div class="jp-filter-row">
                            <input type="text" name="country" value="<?php echo htmlspecialchars($searchCountry, ENT_QUOTES); ?>" placeholder="Country or region" aria-label="Country">
                            <select name="type" aria-label="Job type">
                                <option value="">All types</option>
                                <?php foreach ($typeLabels as $k => $lbl): ?>
                                <option value="<?php echo $k; ?>" <?php echo $searchType === $k ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="jp-btn-primary">Search</button>
                            <?php if ($searchQ !== '' || $searchCountry !== '' || $searchType !== ''): ?>
                            <a href="<?php echo htmlspecialchars($base . '/job_applications', ENT_QUOTES); ?>" class="jp-btn-ghost jp-btn-sm">Clear</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="jp-feed-header">
                    <div>
                        <h1>Top job picks for you</h1>
                        <p>Based on your profile and preferences — remote and overseas roles.</p>
                    </div>
                    <span class="jp-result-count"><?php echo $jobCount; ?> result<?php echo $jobCount === 1 ? '' : 's'; ?></span>
                </div>

                <div class="jp-job-list" id="jp-job-list">
                    <?php if (empty($jobs)): ?>
                    <div class="jp-empty">
                        <div class="jp-empty-icon"><i class="fa fa-briefcase"></i></div>
                        <h3>No jobs found</h3>
                        <p><?php echo $isJobAdmin ? 'Post the first job using the button in the sidebar.' : 'Try different search filters or check back soon.'; ?></p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($jobs as $job):
                        $jid = (int)($job['id'] ?? 0);
                        $applied = in_array($jid, $appliedIds, true);
                        $empType = $typeLabels[$job['employment_type'] ?? ''] ?? ucfirst(str_replace('_', ' ', $job['employment_type'] ?? 'Job'));
                        $locType = $locLabels[$job['location_type'] ?? ''] ?? '';
                        $loc = trim(($job['city'] ?? '') . ($job['country'] ? (($job['city'] ?? '') ? ', ' : '') . $job['country'] : ''));
                        if ($locType) $loc .= ($loc ? ' · ' : '') . $locType;
                        $hasExternal = !empty($job['application_url']);
                        $logoUrl = jp_logo_url($job);
                    ?>
                    <article class="jp-job-card" data-job-id="<?php echo $jid; ?>" data-job-json="<?php echo htmlspecialchars(json_encode([
                        'id' => $jid,
                        'title' => $job['title'] ?? '',
                        'company' => $job['company_name'] ?? '',
                        'company_logo' => $logoUrl,
                        'location' => $loc,
                        'employment_type' => $empType,
                        'description' => $job['description'] ?? '',
                        'requirements' => $job['requirements'] ?? '',
                        'salary_min' => $job['salary_min'] ?? null,
                        'salary_max' => $job['salary_max'] ?? null,
                        'salary_currency' => $job['salary_currency'] ?? 'USD',
                        'visa_sponsorship' => !empty($job['visa_sponsorship']),
                        'application_url' => $job['application_url'] ?? '',
                        'posted' => jp_time_ago($job['created_at'] ?? ''),
                        'applied' => $applied,
                        'has_external' => $hasExternal,
                    ]), ENT_QUOTES); ?>">
                        <button type="button" class="jp-job-select" aria-label="View job details">
                            <?php if ($logoUrl): ?>
                            <div class="jp-job-logo jp-job-logo-img"><img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES); ?>" alt="" loading="lazy"></div>
                            <?php else: ?>
                            <div class="jp-job-logo jp-job-logo-empty" aria-hidden="true"></div>
                            <?php endif; ?>
                            <div class="jp-job-body">
                                <h2 class="jp-job-title"><?php echo htmlspecialchars($job['title'] ?? '', ENT_QUOTES); ?></h2>
                                <p class="jp-job-company"><?php echo htmlspecialchars($job['company_name'] ?? 'Company', ENT_QUOTES); ?><?php echo $loc ? ' · ' . htmlspecialchars($loc, ENT_QUOTES) : ''; ?></p>
                                <div class="jp-job-meta">
                                    <?php if ($completeness >= 50 && $canApply && !$applied): ?>
                                    <span class="jp-tag jp-tag-gold"><i class="fa fa-star"></i> Good match</span>
                                    <?php endif; ?>
                                    <span class="jp-meta-pill"><?php echo htmlspecialchars($empType, ENT_QUOTES); ?></span>
                                    <?php if (!empty($job['visa_sponsorship'])): ?><span class="jp-tag jp-tag-green">Visa sponsorship</span><?php endif; ?>
                                    <span class="jp-time"><?php echo jp_time_ago($job['created_at'] ?? ''); ?></span>
                                </div>
                                <?php if (!empty($job['description'])): ?>
                                <p class="jp-job-snippet"><?php echo htmlspecialchars(mb_substr(strip_tags($job['description']), 0, 140), ENT_QUOTES); ?>…</p>
                                <?php endif; ?>
                            </div>
                        </button>
                        <div class="jp-job-actions">
                            <?php if ($applied): ?>
                            <span class="jp-applied"><i class="fa fa-check-circle"></i> Applied</span>
                            <?php elseif ($isGuest): ?>
                            <a href="<?php echo htmlspecialchars($base . '/account_login?redirect=' . urlencode($base . '/job_applications'), ENT_QUOTES); ?>" class="jp-btn-primary jp-btn-sm">Sign in to apply</a>
                            <?php elseif (!$canApply): ?>
                            <span class="jp-muted-action">Individual accounts only</span>
                            <?php elseif ($hasExternal): ?>
                            <a href="<?php echo htmlspecialchars($job['application_url'], ENT_QUOTES); ?>" target="_blank" rel="noopener" class="jp-btn-primary jp-btn-sm jp-external-apply">Apply <i class="fa fa-external-link"></i></a>
                            <?php else: ?>
                            <button type="button" class="jp-btn-primary jp-btn-sm jp-apply-btn" data-job-id="<?php echo $jid; ?>" data-job-title="<?php echo htmlspecialchars($job['title'] ?? '', ENT_QUOTES); ?>">Easy Apply</button>
                            <?php endif; ?>
                            <button type="button" class="jp-btn-ghost jp-btn-sm jp-view-detail" data-job-id="<?php echo $jid; ?>">View job</button>
                            <?php if ($isJobAdmin): ?>
                            <button type="button" class="jp-btn-danger jp-btn-sm jp-delete-job" data-job-id="<?php echo $jid; ?>">Remove</button>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (!empty($myApplications)): ?>
                <div class="jp-tracker" id="jp-tracker">
                    <div class="jp-tracker-head">
                        <h2><i class="fa fa-bookmark"></i> Your applications</h2>
                        <span><?php echo count($myApplications); ?> total</span>
                    </div>
                    <?php foreach ($myApplications as $app):
                        $status = $app['status'] ?? 'submitted';
                        $locApp = trim(($app['job_city'] ?? '') . (($app['job_city'] && $app['job_country']) ? ', ' : '') . ($app['job_country'] ?? ''));
                    ?>
                    <div class="jp-tracker-item">
                        <div class="jp-tracker-main">
                            <strong><?php echo htmlspecialchars($app['job_title'] ?? ('Job #' . ($app['job_posting_id'] ?? '')), ENT_QUOTES); ?></strong>
                            <span><?php echo htmlspecialchars($app['job_company'] ?? '', ENT_QUOTES); ?><?php echo $locApp ? ' · ' . htmlspecialchars($locApp, ENT_QUOTES) : ''; ?></span>
                        </div>
                        <span class="jp-status jp-status-<?php echo htmlspecialchars($status, ENT_QUOTES); ?>"><?php echo htmlspecialchars(jp_status_label($status), ENT_QUOTES); ?></span>
                        <span class="jp-time"><?php echo jp_time_ago($app['submitted_at'] ?? ''); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </main>

            <aside class="jp-detail-panel" id="jp-detail-panel" aria-hidden="true">
                <div class="jp-detail-inner" id="jp-detail-inner">
                    <button type="button" class="jp-detail-close" id="jp-detail-close" aria-label="Close">&times;</button>
                    <div class="jp-detail-placeholder">
                        <i class="fa fa-hand-pointer-o"></i>
                        <p>Select a job to view details</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<div class="jp-modal-bg" id="jp-apply-modal" style="display:none;" role="dialog" aria-modal="true">
    <div class="jp-modal jp-modal-simple">
        <button type="button" class="jp-modal-close" data-close-modal aria-label="Close">&times;</button>
        <h3>Easy Apply</h3>
        <p class="jp-modal-job-title" id="jp-apply-job-title"></p>
        <p class="jp-modal-sub">Your job profile and resume will be shared with the employer. A confirmation email will be sent to you.</p>
        <label class="jp-modal-label">Cover letter <span>(optional)</span>
            <textarea id="jp-cover-letter" rows="5" placeholder="Why are you a great fit for this role?"></textarea>
        </label>
        <div class="jp-modal-actions">
            <button type="button" class="jp-btn-ghost" data-close-modal>Cancel</button>
            <button type="button" class="jp-btn-primary" id="jp-confirm-apply">Submit application</button>
        </div>
    </div>
</div>

<?php if ($isJobAdmin): ?>
<div class="jp-modal-bg" id="jp-post-modal" style="display:none;" role="dialog" aria-modal="true">
    <div class="jp-modal jp-modal-lg jp-modal-post">
        <div class="jp-modal-head">
            <button type="button" class="jp-modal-close" data-close-modal aria-label="Close">&times;</button>
            <h3>Post a new job</h3>
            <p class="jp-modal-sub">Jobs appear immediately in the job search feed.</p>
        </div>
        <form id="jp-post-form" class="jp-post-form" novalidate>
            <div class="jp-modal-scroll">
            <label>Job title *<input type="text" name="title" required maxlength="300" placeholder="e.g. Software Engineer (Graduate)"></label>
            <label>Company *<input type="text" name="company_name" id="jp-company-name" required maxlength="200" placeholder="Company name"></label>
            <label>Company website <span class="jp-label-hint">(helps auto-fetch the correct logo)</span>
                <input type="url" name="company_website" id="jp-company-website" placeholder="https://company.com">
            </label>
            <div class="jp-logo-field">
                <span class="jp-logo-field-label">Company logo <span class="jp-label-hint">(optional)</span></span>
                <div class="jp-logo-preview-wrap" id="jp-logo-preview-wrap" hidden>
                    <img id="jp-logo-preview" src="" alt="Company logo preview" class="jp-logo-preview-img">
                    <button type="button" class="jp-logo-remove" id="jp-logo-remove" aria-label="Remove logo">&times;</button>
                </div>
                <input type="hidden" name="company_logo" id="jp-company-logo" value="">
                <div class="jp-logo-actions">
                    <button type="button" class="jp-btn-ghost jp-btn-sm" id="jp-fetch-logo">Auto-fetch logo</button>
                    <button type="button" class="jp-btn-ghost jp-btn-sm" id="jp-upload-logo-btn">Upload logo</button>
                    <input type="file" id="jp-logo-file" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml" hidden>
                </div>
                <p class="jp-logo-hint" id="jp-logo-hint">We look up the official logo from the company website or name. Upload your own if needed, or leave blank.</p>
            </div>
            <label class="jp-check"><input type="checkbox" name="auto_fetch_logo" id="jp-auto-fetch-logo" value="1" checked> Auto-fetch logo on publish if none selected</label>
            <div class="jp-form-row">
                <label>Country<input type="text" name="country" maxlength="100" placeholder="Australia"></label>
                <label>City<input type="text" name="city" maxlength="100" placeholder="Sydney"></label>
            </div>
            <div class="jp-form-row">
                <label>Type
                    <select name="employment_type">
                        <?php foreach ($typeLabels as $k => $lbl): ?>
                        <option value="<?php echo $k; ?>"><?php echo $lbl; ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Location
                    <select name="location_type">
                        <?php foreach ($locLabels as $k => $lbl): ?>
                        <option value="<?php echo $k; ?>"><?php echo $lbl; ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label>Description<textarea name="description" rows="5" maxlength="8000" placeholder="Role overview, responsibilities…"></textarea></label>
            <label>Requirements<textarea name="requirements" rows="3" maxlength="4000" placeholder="Skills, experience, education…"></textarea></label>
            <div class="jp-form-row">
                <label>Salary min<input type="number" name="salary_min" min="0" placeholder="50000"></label>
                <label>Salary max<input type="number" name="salary_max" min="0" placeholder="80000"></label>
                <label>Currency<input type="text" name="salary_currency" value="USD" maxlength="10"></label>
            </div>
            <label class="jp-check"><input type="checkbox" name="visa_sponsorship" value="1"> Visa sponsorship available</label>
            <label>External apply URL <span class="jp-label-hint">(optional — opens company site instead of Easy Apply)</span>
                <input type="url" name="application_url" placeholder="https://company.com/careers/apply">
            </label>
            </div>
            <div class="jp-modal-foot">
                <div class="jp-modal-actions">
                    <button type="button" class="jp-btn-ghost" data-close-modal>Cancel</button>
                    <button type="submit" class="jp-btn-primary" id="jp-post-submit">Publish job</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="jp-toast" id="jp-toast" role="status"></div>

<script>
window.jpConfig = {
    csrf: <?php echo json_encode($csrf); ?>,
    baseUrl: <?php echo json_encode($base); ?>,
    isGuest: <?php echo $isGuest ? 'true' : 'false'; ?>,
    isAdmin: <?php echo $isJobAdmin ? 'true' : 'false'; ?>,
    canApply: <?php echo $canApply ? 'true' : 'false'; ?>
};
</script>
@endsection

@push('scripts')
<script src="/asset/js/web/job_applications.js?v=<?php echo date('YmdHi'); ?>"></script>
@endpush
