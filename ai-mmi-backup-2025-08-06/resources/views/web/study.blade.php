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
            <div class="study-option-title">What to study?</div>
            <div class="study-option-question">
                Which programs, fields of study, or courses would you recommend as the best match for my academic history, career aspirations, and long-term objectives?
            </div>
            <a href="javascript:void(0);" class="study-option-button" data-action="program-finder">
                Program Finder
            </a>
            <form class="study-option-form" data-question="Which programs, fields of study, or courses would you recommend as the best match for my academic history, career aspirations, and long-term objectives?">
                <div class="study-form-row">
                    <input type="text" data-label="Academic background" placeholder="Academic background">
                    <input type="text" data-label="Career goal" placeholder="Career goal">
                </div>
                <div class="study-form-row">
                    <input type="text" data-label="Interests" placeholder="Interests">
                    <input type="text" data-label="Study level" placeholder="Study level">
                </div>
                <div class="study-form-row">
                    <input type="text" data-label="Preferred countries" placeholder="Preferred countries">
                    <input type="text" data-label="Budget range" placeholder="Budget range">
                </div>
                <textarea data-label="Long-term objectives" placeholder="Long-term objectives"></textarea>
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

        <div class="study-option-card">
            <div class="study-option-title">How to get in?</div>
            <div class="study-option-question">
                The major outline of the key admission and visa requirements, along with the step-by-step application process.
            </div>
            <a href="javascript:void(0);" class="study-option-button" data-action="admission-plan">
                Admission Plan
            </a>
            <form class="study-option-form" data-question="Could you please outline the key admission and visa requirements, along with the step-by-step application process?">
                <div class="study-form-row">
                    <input type="text" data-label="Target country" placeholder="Target country">
                    <input type="text" data-label="Study level" placeholder="Study level">
                </div>
                <div class="study-form-row">
                    <input type="text" data-label="English test status" placeholder="English test status">
                    <input type="text" data-label="GPA/Grades" placeholder="GPA/Grades">
                </div>
                <div class="study-form-row">
                    <input type="text" data-label="Intake" placeholder="Intake (e.g. Feb/Jul)">
                    <input type="text" data-label="Timeline" placeholder="Target application timeline">
                </div>
                <textarea data-label="Academic background" placeholder="Academic background"></textarea>
                <button type="submit" class="study-form-submit">Submit to AI</button>
            </form>
        </div>

        <div class="study-option-card">
            <div class="study-option-title">Scholarship?</div>
            <div class="study-option-question">
                What scholarships, bursaries, grants, or other forms of financial assistance are available for international students / applicants in my situation, and what are the eligibility requirements and application deadlines?
            </div>
            <a href="javascript:void(0);" class="study-option-button" data-action="scholarship-search">
                Scholarship Search
            </a>
            <form class="study-option-form" data-question="What scholarships, bursaries, grants, or other forms of financial assistance are available for international students / applicants in my situation, and what are the eligibility requirements and application deadlines?">
                <div class="study-form-row">
                    <input type="text" data-label="Nationality" placeholder="Nationality">
                    <input type="text" data-label="Target country" placeholder="Target country">
                </div>
                <div class="study-form-row">
                    <input type="text" data-label="Study level" placeholder="Study level">
                    <input type="text" data-label="GPA/Grades" placeholder="GPA/Grades">
                </div>
                <div class="study-form-row">
                    <input type="text" data-label="Field of study" placeholder="Field of study">
                    <input type="text" data-label="Financial need" placeholder="Financial need (yes/no)">
                </div>
                <textarea data-label="Achievements" placeholder="Achievements / awards / extracurricular"></textarea>
                <button type="submit" class="study-form-submit">Submit to AI</button>
            </form>
        </div>

        <div class="study-option-card">
            <div class="study-option-title">When to start?</div>
            <div class="study-option-question">
                What are the available intake periods / commencement dates for the recommended programs, and what are the corresponding application submission deadlines?
            </div>
            <a href="javascript:void(0);" class="study-option-button" data-action="timeline-actions">
                Timeline Actions
            </a>
            <form class="study-option-form" data-question="What are the available intake periods / commencement dates for the recommended programs, and what are the corresponding application submission deadlines? Please make a timeline for me.">
                <div class="study-form-row">
                    <input type="text" data-label="Target country" placeholder="Target country">
                    <input type="text" data-label="Preferred intake" placeholder="Preferred intake">
                </div>
                <div class="study-form-row">
                    <input type="text" data-label="Study level" placeholder="Study level">
                    <input type="text" data-label="Program length" placeholder="Program length">
                </div>
                <div class="study-form-row">
                    <input type="text" data-label="English test date" placeholder="English test date">
                    <input type="text" data-label="Current stage" placeholder="Current stage">
                </div>
                <textarea data-label="Notes" placeholder="Notes / constraints"></textarea>
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
@endsection

@push('js')
<script src="/asset/js/web/study.js?v={{ date('Ymd') }}"></script>
@if(session('trigger_assessment'))
<script>
    window.triggerAssessment = true;
    window.assessmentPrompt = {!! json_encode(session('eligibility_assessment')['prompt'] ?? '') !!};
</script>
@endif
@endpush
