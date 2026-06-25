@extends('web.common')

@push('css')
<link href="/asset/css/web/student_explore.css?v=<?php echo date('Ymd'); ?>" rel="stylesheet">
<style>
main.page-body .page-content { margin-right: 0 !important; max-width: 100% !important; }
main.page-body .info-area {
    width: 100% !important;
    float: none !important;
    background: #0c1445 !important;
    background-image: none !important;
    background-color: #0c1445 !important;
    min-height: 100vh !important;
}
main.page-body .info-area::before { display: none !important; }
body { background: #0c1445 !important; }
</style>
@endpush

@section('content')
<?php
$interests = $_page_data['interests'] ?? collect();
$lang      = $_lang ?? 'en';
$viewBase  = '/' . $lang . '/student_explore/view/';
$exploreUrl = '/' . $lang . '/student_explore';
$_smi_base = rtrim($_page_base_url, '/');
$_smi_is_institution = !empty($_current_member) && (int)($_current_member['type'] ?? 0) === 3;
$_smi_autoLang = !empty($_page_get_data['autolang']) ? $_page_get_data['autolang'] : session('autolang', '');
$appendAutoLang = function ($url) use ($_smi_autoLang) {
    if (empty($_smi_autoLang)) return $url;
    return $url . ((strpos($url, '?') !== false) ? '&' : '?') . 'autolang=' . urlencode($_smi_autoLang);
};
?>

<div class="se-outer-wrap">

<!-- NAV SIDEBAR -->
<div class="se-nav-sidebar">
    <a href="<?php echo htmlspecialchars($appendAutoLang($_smi_base.'/study_plans'), ENT_QUOTES); ?>">
        <span class="se-nav-icon"><i class="fa fa-star"></i></span>
        <span class="se-nav-label">Dreams</span>
    </a>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_smi_base.'/study_college_match'), ENT_QUOTES); ?>">
        <span class="se-nav-icon"><i class="fa fa-graduation-cap"></i></span>
        <span class="se-nav-label">Matches</span>
    </a>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_smi_base.'/nextgen_challenge'), ENT_QUOTES); ?>">
        <span class="se-nav-icon"><i class="fa fa-trophy"></i></span>
        <span class="se-nav-label">NextGen AI &amp;<br>Talent Challenge</span>
    </a>
    <?php if (!$_smi_is_institution): ?>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_smi_base.'/institution_explore'), ENT_QUOTES); ?>">
        <span class="se-nav-icon"><i class="fa fa-building"></i></span>
        <span class="se-nav-label">Colleges</span>
    </a>
    <?php else: ?>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_smi_base.'/student_explore'), ENT_QUOTES); ?>" class="active">
        <span class="se-nav-icon"><i class="fa fa-users"></i></span>
        <span class="se-nav-label">Explore Students</span>
    </a>
    <?php endif; ?>
    <?php if (!empty($_current_member)): ?>
    <a href="<?php echo htmlspecialchars($appendAutoLang($_smi_is_institution ? $_smi_base.'/institution_hub_profile' : ((int)($_current_member['type'] ?? 0) === 2 ? $_smi_base.'/account/posts' : $_smi_base.'/student_profile')), ENT_QUOTES); ?>">
        <span class="se-nav-icon"><i class="fa fa-id-card"></i></span>
        <span class="se-nav-label">My Profile</span>
    </a>
    <?php endif; ?>
    <a href="javascript:void(0);" class="do-toapply" data-sector="study" data-action-url="<?php echo htmlspecialchars($_smi_base.'/agent_chat', ENT_QUOTES); ?>">
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

    <div class="se-page-header">
        <div class="se-page-title">
            <h1>My Interest List</h1>
            <p>Students you have expressed interest in. AI-mmi will coordinate on your behalf.</p>
        </div>
        <a href="<?php echo $exploreUrl; ?>" class="se-shortlist-btn">
            <i class="fa fa-search"></i> Explore More Students
        </a>
    </div>

    <?php if ($interests->isEmpty()): ?>
    <div class="se-empty" style="margin-top:60px;">
        <div class="se-empty-icon">⭐</div>
        <p>You haven't expressed interest in any students yet.</p>
        <a href="<?php echo $exploreUrl; ?>" class="se-view-btn" style="margin-top:16px;display:inline-block;">Explore Students</a>
    </div>
    <?php else: ?>

    <div class="smi-table-wrap">
        <table class="smi-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Target Degree</th>
                    <th>Language</th>
                    <th>AI-mmi Status</th>
                    <th>Date Added</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($interests as $item): ?>
                <?php
                    $name       = trim($item->alias_name ?? '') ?: trim(($item->stu_first ?? '') . ' ' . mb_substr($item->stu_last ?? '', 0, 1) . '.');
                    if (!$name) $name = 'Student';
                    $initial    = strtoupper(mb_substr($name, 0, 1));
                    $langScores = json_decode($item->language_scores ?? '[]', true) ?: [];
                    $topLang    = !empty($langScores[0]) ? ($langScores[0]['test'] . ' ' . $langScores[0]['score']) : '—';
                    $status     = $item->notification_status ?? 'pending';
                    $statusLabel = ['pending' => 'Pending AI-mmi', 'contacted' => '✓ Being Coordinated', 'closed' => 'Closed'];
                    $statusClass = ['pending' => 'smi-status-pending', 'contacted' => 'smi-status-contacted', 'closed' => 'smi-status-closed'];

                    // Avatar
                    $avatarHtml = '<div class="smi-avatar-initial">' . htmlspecialchars($initial) . '</div>';
                    if (!empty($item->avatar)) {
                        $aPath = public_path('upload/member_avatar/' . $item->avatar);
                        if (file_exists($aPath)) {
                            $avatarHtml = '<div class="smi-avatar-img" style="background-image:url(\'/upload/member_avatar/' . htmlspecialchars($item->avatar, ENT_QUOTES) . '\')"></div>';
                        }
                    }
                    $date = $item->interest_date ? date('d M Y', strtotime($item->interest_date)) : '—';
                ?>
                <tr>
                    <td>
                        <div class="smi-student-cell">
                            <div class="smi-avatar"><?php echo $avatarHtml; ?></div>
                            <div>
                                <div class="smi-student-name"><?php echo htmlspecialchars($name, ENT_QUOTES); ?></div>
                                <?php if (!empty($item->headline)): ?>
                                <div class="smi-student-headline"><?php echo htmlspecialchars($item->headline, ENT_QUOTES); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($item->nationality)): ?>
                                <div class="smi-student-nat">🌍 <?php echo htmlspecialchars($item->nationality, ENT_QUOTES); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><?php echo !empty($item->target_degree) ? htmlspecialchars(ucfirst($item->target_degree), ENT_QUOTES) : '—'; ?></td>
                    <td><?php echo htmlspecialchars($topLang, ENT_QUOTES); ?></td>
                    <td><span class="smi-status <?php echo $statusClass[$status] ?? 'smi-status-pending'; ?>"><?php echo $statusLabel[$status] ?? ucfirst($status); ?></span></td>
                    <td><?php echo $date; ?></td>
                    <td><a href="<?php echo $viewBase . (int)$item->member_id; ?>" class="smi-view-link">View Profile</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="smi-notice">
        <i class="fa fa-info-circle"></i>
        <strong>How this works:</strong> When you express interest in a student, our AI-mmi team reviews the match and coordinates communication on your behalf — no contact details are ever shared directly.
    </div>

    <?php endif; ?>

</div><!-- /.se-wrap -->
</div><!-- /.se-content-area -->
</div><!-- /.se-outer-wrap -->
@endsection
