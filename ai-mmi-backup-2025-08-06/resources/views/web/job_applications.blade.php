@extends('web.common')

@push('css')
<link href="/asset/css/web/job_applications.css?v=<?php echo date('YmdHi'); ?>" rel="stylesheet">
@endpush

@section('content')
<?php
$jobs           = $_page_data['jobs'] ?? [];
$appliedIds     = $_page_data['applied_ids'] ?? [];
$myApplications = $_page_data['my_applications'] ?? [];
$profile        = $_page_data['profile'] ?? null;
$completeness   = (int)($_page_data['profile_completeness'] ?? 0);
$searchQ        = $_page_data['search_q'] ?? '';
$searchCountry  = $_page_data['search_country'] ?? '';
$searchType     = $_page_data['search_type'] ?? '';
$isJobAdmin     = !empty($_page_data['is_job_admin']);
$isGuest        = !empty($_page_data['is_guest']);
$member         = $_current_member ?? null;
$csrf           = csrf_token();
$base           = $_page_base_url;

$displayName = 'Guest';
$initial     = 'G';
$headline    = '';
$location    = '';
if ($member) {
    $displayName = trim($member['alias_name'] ?? '') ?: trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')) ?: 'Member';
    $initial     = strtoupper(mb_substr($displayName, 0, 1));
    if ($profile) {
        $headline = $profile->headline ?? '';
        $location = trim(($profile->current_city ?? '') . ($profile->current_country ? ', ' . $profile->current_country : ''));
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
    if ($diff < 86400) return 'Today';
    if ($diff < 604800) return floor($diff / 86400) . ' day' . (floor($diff / 86400) > 1 ? 's' : '') . ' ago';
    if ($diff < 2592000) return floor($diff / 604800) . ' week' . (floor($diff / 604800) > 1 ? 's' : '') . ' ago';
    return date('M j, Y', $ts);
}
?>

<div class="jp-page">
    <div class="jp-layout">

        {{-- Left sidebar (LinkedIn-style) --}}
        <aside class="jp-sidebar">
            <div class="jp-profile-card">
                <div class="jp-profile-cover"></div>
                <div class="jp-profile-body">
                    <div class="jp-profile-avatar"><?php echo htmlspecialchars($initial, ENT_QUOTES); ?></div>
                    <a href="<?php echo htmlspecialchars($base . '/job_profile', ENT_QUOTES); ?>" class="jp-profile-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES); ?></a>
                    <?php if ($headline): ?>
                    <p class="jp-profile-headline"><?php echo htmlspecialchars($headline, ENT_QUOTES); ?></p>
                    <?php else: ?>
                    <p class="jp-profile-headline jp-muted">Add a headline on your job profile</p>
                    <?php endif; ?>
                    <?php if ($location): ?>
                    <p class="jp-profile-loc"><?php echo htmlspecialchars($location, ENT_QUOTES); ?></p>
                    <?php endif; ?>
                    <?php if (!$isGuest): ?>
                    <div class="jp-profile-progress">
                        <div class="jp-progress-bar"><div class="jp-progress-fill" style="width:<?php echo $completeness; ?>%"></div></div>
                        <span><?php echo $completeness; ?>% profile complete</span>
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
        </aside>

        {{-- Main feed --}}
        <main class="jp-main">
            <div class="jp-search-card">
                <form method="get" action="<?php echo htmlspecialchars($base . '/job_applications', ENT_QUOTES); ?>" class="jp-search-form">
                    <div class="jp-search-row">
                        <i class="fa fa-search"></i>
                        <input type="text" name="q" value="<?php echo htmlspecialchars($searchQ, ENT_QUOTES); ?>" placeholder="Search jobs, companies, or keywords">
                    </div>
                    <div class="jp-filter-row">
                        <input type="text" name="country" value="<?php echo htmlspecialchars($searchCountry, ENT_QUOTES); ?>" placeholder="Country or region">
                        <select name="type">
                            <option value="">All types</option>
                            <?php foreach ($typeLabels as $k => $lbl): ?>
                            <option value="<?php echo $k; ?>" <?php echo $searchType === $k ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="jp-btn-primary">Search</button>
                    </div>
                </form>
            </div>

            <div class="jp-feed-header">
                <h1>Jobs that match your profile</h1>
                <p>Based on your job preferences and profile — find remote or overseas opportunities.</p>
            </div>

            <div class="jp-job-list" id="jp-job-list">
                <?php if (empty($jobs)): ?>
                <div class="jp-empty">
                    <i class="fa fa-briefcase"></i>
                    <h3>No jobs found</h3>
                    <p><?php echo $isJobAdmin ? 'Post the first job using the button on the left.' : 'Check back soon for new opportunities.'; ?></p>
                </div>
                <?php else: ?>
                <?php foreach ($jobs as $job):
                    $jid = (int)($job['id'] ?? 0);
                    $applied = in_array($jid, $appliedIds, true);
                    $empType = $typeLabels[$job['employment_type'] ?? ''] ?? ucfirst(str_replace('_', ' ', $job['employment_type'] ?? 'Job'));
                    $locType = $locLabels[$job['location_type'] ?? ''] ?? '';
                    $loc = trim(($job['city'] ?? '') . ($job['country'] ? (($job['city'] ?? '') ? ', ' : '') . $job['country'] : ''));
                    if ($locType) $loc .= ($loc ? ' · ' : '') . $locType;
                ?>
                <article class="jp-job-card" data-job-id="<?php echo $jid; ?>">
                    <div class="jp-job-logo"><?php echo strtoupper(mb_substr($job['company_name'] ?? 'CO', 0, 2)); ?></div>
                    <div class="jp-job-body">
                        <h2 class="jp-job-title">
                            <a href="javascript:void(0);" class="jp-job-detail-toggle"><?php echo htmlspecialchars($job['title'] ?? '', ENT_QUOTES); ?></a>
                        </h2>
                        <p class="jp-job-company"><?php echo htmlspecialchars($job['company_name'] ?? 'Company', ENT_QUOTES); ?><?php echo $loc ? ' · ' . htmlspecialchars($loc, ENT_QUOTES) : ''; ?></p>
                        <div class="jp-job-meta">
                            <span><?php echo htmlspecialchars($empType, ENT_QUOTES); ?></span>
                            <?php if (!empty($job['visa_sponsorship'])): ?><span class="jp-tag jp-tag-green">Visa sponsorship</span><?php endif; ?>
                            <span class="jp-time"><?php echo jp_time_ago($job['created_at'] ?? ''); ?></span>
                        </div>
                        <?php if (!empty($job['description'])): ?>
                        <p class="jp-job-snippet"><?php echo htmlspecialchars(mb_substr(strip_tags($job['description']), 0, 180), ENT_QUOTES); ?>…</p>
                        <?php endif; ?>
                        <div class="jp-job-detail" style="display:none;">
                            <?php if (!empty($job['description'])): ?>
                            <div class="jp-job-full-desc"><?php echo nl2br(htmlspecialchars($job['description'], ENT_QUOTES)); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($job['requirements'])): ?>
                            <h4>Requirements</h4>
                            <p><?php echo nl2br(htmlspecialchars($job['requirements'], ENT_QUOTES)); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($job['salary_min']) || !empty($job['salary_max'])): ?>
                            <p class="jp-salary"><strong>Salary:</strong>
                                <?php echo htmlspecialchars($job['salary_currency'] ?? 'USD', ENT_QUOTES); ?>
                                <?php echo number_format((int)($job['salary_min'] ?? 0)); ?>
                                <?php if (!empty($job['salary_max'])): ?> – <?php echo number_format((int)$job['salary_max']); ?><?php endif; ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="jp-job-actions">
                            <?php if ($applied): ?>
                            <span class="jp-applied"><i class="fa fa-check"></i> Applied</span>
                            <?php elseif ($isGuest): ?>
                            <a href="<?php echo htmlspecialchars($base . '/account_login?redirect=' . urlencode($base . '/job_applications'), ENT_QUOTES); ?>" class="jp-btn-primary jp-btn-sm">Sign in to apply</a>
                            <?php else: ?>
                            <button type="button" class="jp-btn-primary jp-btn-sm jp-apply-btn" data-job-id="<?php echo $jid; ?>">Easy Apply</button>
                            <?php endif; ?>
                            <button type="button" class="jp-btn-ghost jp-btn-sm jp-job-detail-toggle">View details</button>
                            <?php if ($isJobAdmin): ?>
                            <button type="button" class="jp-btn-danger jp-btn-sm jp-delete-job" data-job-id="<?php echo $jid; ?>">Remove</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($myApplications)): ?>
            <div class="jp-tracker" id="jp-tracker">
                <h2>Your applications</h2>
                <?php
                $jobMap = [];
                foreach ($jobs as $j) { $jobMap[(int)$j['id']] = $j; }
                foreach ($myApplications as $app):
                    $jid = (int)($app['job_posting_id'] ?? 0);
                    $j = $jobMap[$jid] ?? null;
                ?>
                <div class="jp-tracker-item">
                    <strong><?php echo htmlspecialchars($j['title'] ?? 'Job #' . $jid, ENT_QUOTES); ?></strong>
                    <span><?php echo htmlspecialchars($j['company_name'] ?? '', ENT_QUOTES); ?></span>
                    <span class="jp-status"><?php echo htmlspecialchars(ucfirst($app['status'] ?? 'submitted'), ENT_QUOTES); ?></span>
                    <span class="jp-time"><?php echo jp_time_ago($app['submitted_at'] ?? ''); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

{{-- Apply modal --}}
<div class="jp-modal-bg" id="jp-apply-modal" style="display:none;">
    <div class="jp-modal">
        <button type="button" class="jp-modal-close" data-close-modal>&times;</button>
        <h3>Easy Apply</h3>
        <p class="jp-modal-sub">Your job profile and resume will be shared with the employer.</p>
        <textarea id="jp-cover-letter" rows="5" placeholder="Optional cover letter…"></textarea>
        <div class="jp-modal-actions">
            <button type="button" class="jp-btn-ghost" data-close-modal>Cancel</button>
            <button type="button" class="jp-btn-primary" id="jp-confirm-apply">Submit application</button>
        </div>
    </div>
</div>

<?php if ($isJobAdmin): ?>
<div class="jp-modal-bg" id="jp-post-modal" style="display:none;">
    <div class="jp-modal jp-modal-lg">
        <button type="button" class="jp-modal-close" data-close-modal>&times;</button>
        <h3>Post a new job</h3>
        <form id="jp-post-form" class="jp-post-form">
            <label>Job title *<input type="text" name="title" required maxlength="300"></label>
            <label>Company<input type="text" name="company_name" maxlength="200"></label>
            <div class="jp-form-row">
                <label>Country<input type="text" name="country" maxlength="100"></label>
                <label>City<input type="text" name="city" maxlength="100"></label>
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
            <label>Description<textarea name="description" rows="5" maxlength="8000"></textarea></label>
            <label>Requirements<textarea name="requirements" rows="3" maxlength="4000"></textarea></label>
            <div class="jp-form-row">
                <label>Salary min<input type="number" name="salary_min" min="0"></label>
                <label>Salary max<input type="number" name="salary_max" min="0"></label>
                <label>Currency<input type="text" name="salary_currency" value="USD" maxlength="10"></label>
            </div>
            <label class="jp-check"><input type="checkbox" name="visa_sponsorship" value="1"> Visa sponsorship available</label>
            <label>External apply URL<input type="url" name="application_url" placeholder="https://"></label>
            <div class="jp-modal-actions">
                <button type="button" class="jp-btn-ghost" data-close-modal>Cancel</button>
                <button type="submit" class="jp-btn-primary">Publish job</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="jp-toast" id="jp-toast"></div>

<script>
window.jpConfig = {
    csrf: <?php echo json_encode($csrf); ?>,
    baseUrl: <?php echo json_encode($base); ?>,
    isGuest: <?php echo $isGuest ? 'true' : 'false'; ?>,
    isAdmin: <?php echo $isJobAdmin ? 'true' : 'false'; ?>
};
</script>
@endsection

@push('scripts')
<script src="/asset/js/web/job_applications.js?v=<?php echo date('YmdHi'); ?>"></script>
@endpush
