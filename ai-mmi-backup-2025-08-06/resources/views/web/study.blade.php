@extends('web.common')

@section('title', 'Study Options')

@push('css')
<link rel="stylesheet" href="/asset/css/web/study.css?v={{ date('Ymd') }}">
@endpush

@section('content')
<?php
    $autoLang = !empty($_page_get_data['autolang']) ? $_page_get_data['autolang'] : session('autolang', '');
    $appendAutoLang = function ($url) use ($autoLang) {
        if(empty($autoLang)) {
            return $url;
        }
        return $url.((strpos($url, '?') !== false) ? '&' : '?').'autolang='.urlencode($autoLang);
    };
?>
<div class="study-container">
    <div class="study-header">
        <h1>Study Abroad Guidance</h1>
        <p>Choose a topic to get personalized assistance with your study abroad journey</p>
<!--  -->        <p class="study-chat-notice">Answers will appear in the AI-mmi chatbox.</p>
    </div>

    <div class="study-options">
        <div class="study-option-card" style="border:2px solid #012169;">
            <div class="study-option-title" style="color:#012169;">College Match</div>
            <div class="study-option-question">
                See your personalised institution matches and manage your study preferences across your top 3 choices.
            </div>
            <a href="<?php echo $appendAutoLang($_page_base_url.'/study_college_match'); ?>" class="study-option-button" style="background:#012169;">
                College Match
            </a>
        </div>

        <div class="study-option-card">
            <div class="study-option-title">Can I go?</div>
            <div class="study-option-question">
                Am I eligible to apply based on my current qualifications and profile?
            </div>
            <a href="<?php echo $appendAutoLang($_page_base_url.'/eligibility_check'); ?>" class="study-option-button" data-action="eligibility-check">
                Eligibility Check
            </a>
        </div>

        <div class="study-option-card">
            <div class="study-option-title">Where to go?</div>
            <div class="study-option-question">
                Which countries and institutions would you recommend as the strongest options for someone with my background?
            </div>
            <a href="javascript:void(0);" class="study-option-button" data-action="country-comparison">
                Country Comparison
            </a>
            <form class="study-option-form" data-question="Which countries and institutions would you recommend as the strongest options for someone with my background?">
                <div class="study-form-row">
                    <input type="text" data-label="Nationality" placeholder="Nationality">
                    <input type="text" data-label="Current country" placeholder="Current country">
                </div>
                <div class="study-form-row">
                    <input type="text" data-label="Preferred countries" placeholder="Preferred countries (optional)">
                    <input type="text" data-label="Study level" placeholder="Study level">
                </div>
                <div class="study-form-row">
                    <input type="text" data-label="Field of study" placeholder="Field of study">
                    <input type="text" data-label="Budget range" placeholder="Budget range">
                </div>
                <textarea data-label="Academic background" placeholder="Academic background"></textarea>
                <button type="submit" class="study-form-submit">Submit to AI</button>
            </form>
        </div>

        <div class="study-option-card">
            <div class="study-option-title">How much?</div>
            <div class="study-option-question">
                What are the estimated total costs (tuition, living expenses, visa/application fees, etc.)?
            </div>
            <a href="javascript:void(0);" class="study-option-button" data-action="cost-estimates">
                Cost Estimates
            </a>
            <form class="study-option-form" data-question="What are the estimated total costs (tuition, living expenses, visa/application fees, etc.)?">
                <div class="study-form-row">
                    <input type="text" data-label="Destination countries" placeholder="Destination countries">
                    <input type="text" data-label="Study level" placeholder="Study level">
                </div>
                <div class="study-form-row">
                    <input type="text" data-label="Program length" placeholder="Program length">
                    <input type="text" data-label="Accommodation preference" placeholder="Accommodation preference">
                </div>
                <div class="study-form-row">
                    <input type="text" data-label="Budget range" placeholder="Budget range">
                    <input type="text" data-label="Dependents" placeholder="Dependents (if any)">
                </div>
                <textarea data-label="Additional notes" placeholder="Additional notes"></textarea>
                <button type="submit" class="study-form-submit">Submit to AI</button>
            </form>
        </div>

        <?php if(empty($_current_member) || (int)($_current_member['type'] ?? 0) !== 3 || strpos(mb_strtolower(trim($_current_member['email'] ?? ''), 'UTF-8'), '@wealthskey.com') !== false): ?>
        <div class="study-option-card">
            <div class="study-option-title">Talk to agent</div>
            <div class="study-option-question">
                Contact our study abroad advisors for personalized assistance and support throughout your application process.
            </div>
            <a href="<?php echo $appendAutoLang($_page_base_url.'/agent_chat'); ?>" class="study-option-button">
                Contact An Agent
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// ── Course Listings (ApplyBoard-style) ────────────────────────────────────
$_institutions = $_page_data['institutions'] ?? [];
$_all_courses = [];
foreach ($_institutions as $inst) {
    $decoded = @json_decode($inst['courses_json'] ?? '', true);
    if (!is_array($decoded)) continue;
    foreach ($decoded as $course) {
        if (empty($course['name'])) continue;
        $course['_inst_id']        = $inst['id'];
        $course['_inst_member_id'] = $inst['member_id'];
        $course['_inst_name']      = $inst['institute_name'] ?: ($inst['alias_name'] ?: 'Institution');
        $course['_inst_avatar']    = $inst['avatar'] ?? '';
        $course['_inst_summary']   = $inst['summary'] ?? '';
        $_all_courses[] = $course;
    }
}

// Helper: derive a human-readable study level from course name
if (!function_exists('studyLevelFromName')) {
    function studyLevelFromName(string $name): string {
        $n = strtolower($name);
        if (strpos($n, 'doctor') !== false || strpos($n, 'phd') !== false) return 'Doctoral Degree';
        if (strpos($n, 'master') !== false || strpos($n, 'graduate diploma') !== false || strpos($n, 'graduate cert') !== false) return 'Postgraduate';
        if (strpos($n, 'bachelor') !== false || strpos($n, 'b.sc') !== false || strpos($n, 'b.eng') !== false) return 'Bachelor\'s Degree';
        if (strpos($n, 'advanced diploma') !== false) return 'Advanced Diploma';
        if (strpos($n, 'diploma') !== false) return 'Diploma';
        if (strpos($n, 'certificate iv') !== false || strpos($n, 'cert iv') !== false) return 'Certificate IV';
        if (strpos($n, 'certificate iii') !== false || strpos($n, 'cert iii') !== false) return 'Certificate III';
        if (strpos($n, 'certificate ii') !== false) return 'Certificate II';
        if (strpos($n, 'certificate') !== false) return 'Certificate';
        return 'Program';
    }
}
?>

@if(!empty($_all_courses))
<section class="programs-section">
    <div class="programs-section-header">
        <div>
            <h2 class="programs-title">Programs in Our Platform</h2>
            <p class="programs-subtitle"><?php echo count($_all_courses); ?> program<?php echo count($_all_courses) !== 1 ? 's' : ''; ?> from <?php echo count($_institutions); ?> institution<?php echo count($_institutions) !== 1 ? 's' : ''; ?></p>
        </div>
        <div class="programs-search-wrap">
            <input type="text" id="program-search" class="programs-search" placeholder="Search programs or institutions…">
            <button type="button" id="program-search-clear" class="programs-search-clear" aria-label="Clear search">Clear</button>
        </div>
    </div>

    <div class="programs-slider-controls" id="programs-slider-controls" style="display:none;">
        <button type="button" id="programs-slide-prev" class="programs-slide-btn" aria-label="Previous programs">&larr; Prev</button>
        <span id="programs-slide-indicator" class="programs-slide-indicator">1 / 1</span>
        <button type="button" id="programs-slide-next" class="programs-slide-btn" aria-label="Next programs">Next &rarr;</button>
    </div>

    <div class="programs-grid-wrap" id="programs-grid-wrap">
    <div class="programs-grid" id="programs-grid">
        <?php foreach ($_all_courses as $ci => $course): ?>
        <?php
            $instId    = (int)($course['_inst_id'] ?? 0);
            $instMemberId = (int)($course['_inst_member_id'] ?? 0);
            $instName  = htmlspecialchars($course['_inst_name'] ?? '', ENT_QUOTES);
            $avatar    = htmlspecialchars($course['_inst_avatar'] ?? '', ENT_QUOTES);
            $courseName = htmlspecialchars($course['name'] ?? '', ENT_QUOTES);
            $level     = studyLevelFromName($course['name'] ?? '');
            $delivery  = htmlspecialchars($course['delivery'] ?? '', ENT_QUOTES);
            $duration  = htmlspecialchars($course['duration'] ?? '', ENT_QUOTES);
            $entry     = htmlspecialchars($course['entry'] ?? '', ENT_QUOTES);
            $feeTuition   = htmlspecialchars($course['fee_tuition'] ?? '', ENT_QUOTES);
            $feeApp       = htmlspecialchars($course['fee_application'] ?? '', ENT_QUOTES);
            $hasScholarship = !empty($course['scholarships']);
            $viewUrl   = !empty($instId) ? $appendAutoLang($_page_base_url.'/institution_hub_profile/pub_view/'.$instId) : $appendAutoLang($_page_base_url.'/account/posts?uid='.$instMemberId);

            // Logo URL logic with existence check to avoid broken image renders.
            if (!empty($avatar)) {
                $logoRelativePath = 'upload/member_logo/' . ltrim($avatar, '/');
                $logoSrc = file_exists(public_path($logoRelativePath)) ? ('/' . $logoRelativePath) : '';
            } else {
                $logoSrc = '';
            }
        ?>
        <div class="program-card" data-search="<?php echo strtolower($instName . ' ' . $courseName . ' ' . $delivery); ?>">
            <div class="program-card-header">
                <div class="program-inst-logo">
                    <?php if (!empty($logoSrc)): ?>
                    <img src="<?php echo $logoSrc; ?>" alt="<?php echo $instName; ?>" loading="lazy">
                    <?php else: ?>
                    <span class="program-logo-placeholder"><?php echo mb_substr(strip_tags($instName), 0, 1, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </div>
                <a href="<?php echo $viewUrl; ?>" class="program-inst-name"><?php echo $instName; ?></a>
            </div>

            <div class="program-level-badge"><?php echo htmlspecialchars($level, ENT_QUOTES); ?></div>
            <a href="<?php echo $viewUrl; ?>" class="program-name"><?php echo $courseName; ?></a>

            <?php if ($hasScholarship): ?>
            <div class="program-tags">
                <span class="program-tag tag-scholarship"><i class="fa fa-dollar"></i> Scholarship Available</span>
            </div>
            <?php endif; ?>

            <div class="program-meta">
                <?php if (!empty($delivery)): ?>
                <div class="program-meta-row">
                    <span class="program-meta-label">Delivery</span>
                    <span class="program-meta-value"><?php echo $delivery; ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($feeTuition)): ?>
                <div class="program-meta-row">
                    <span class="program-meta-label">Gross tuition</span>
                    <span class="program-meta-value program-fee"><?php echo $feeTuition; ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($feeApp)): ?>
                <div class="program-meta-row">
                    <span class="program-meta-label">Application fee</span>
                    <span class="program-meta-value"><?php echo $feeApp; ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($duration)): ?>
                <div class="program-meta-row">
                    <span class="program-meta-label">Duration</span>
                    <span class="program-meta-value"><?php echo $duration; ?></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($entry)): ?>
            <div class="program-intake">
                <span class="program-intake-label">Earliest intake</span>
                <span class="program-intake-value"><?php echo $entry; ?></span>
            </div>
            <?php endif; ?>

            <div class="program-card-footer">
                <a href="<?php echo $viewUrl; ?>" class="program-btn-view">View Program Details ▾</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    </div>

    <div id="programs-no-results" class="programs-no-results" style="display:none;">
        <i class="fa fa-search"></i>
        <p>No programs match your search.</p>
    </div>
</section>
@endif

@if(session('trigger_assessment'))
<script>
    window.triggerAssessment = true;
    window.assessmentPrompt = {!! json_encode(session('eligibility_assessment')['prompt'] ?? '') !!};
</script>
@endif
@endsection
