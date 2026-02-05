@extends('web.common')

@section('title', 'Study Options')

@push('css')
<link rel="stylesheet" href="/asset/css/web/study.css?v={{ date('Ymd') }}">
@endpush

@section('content')
<div class="study-container">
    <div class="study-header">
        <h1>Study Abroad Guidance</h1>
        <p>Choose a topic to get personalized assistance with your study abroad journey</p>
    </div>

    <div class="study-options">
        <div class="study-option-card">
            <div class="study-option-title">Can I go?</div>
            <div class="study-option-question">
                Am I eligible to apply based on my current qualifications and profile?
            </div>
            <a href="<?php echo $_page_base_url.'/eligibility_check'; ?>" class="study-option-button" data-action="eligibility-check">
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
        </div>

        <div class="study-option-card">
            <div class="study-option-title">What to study?</div>
            <div class="study-option-question">
                Which programs, fields of study, or courses would you recommend as the best match for my academic history, career aspirations, and long-term objectives?
            </div>
            <a href="javascript:void(0);" class="study-option-button" data-action="program-finder">
                Program Finder
            </a>
        </div>

        <div class="study-option-card">
            <div class="study-option-title">How much?</div>
            <div class="study-option-question">
                What are the estimated total costs (tuition, living expenses, visa/application fees, etc.)?
            </div>
            <a href="javascript:void(0);" class="study-option-button" data-action="cost-estimates">
                Cost Estimates
            </a>
        </div>

        <div class="study-option-card">
            <div class="study-option-title">How to get in?</div>
            <div class="study-option-question">
                The major outline of the key admission and visa requirements, along with the step-by-step application process.
            </div>
            <a href="javascript:void(0);" class="study-option-button" data-action="admission-plan">
                Admission Plan
            </a>
        </div>

        <div class="study-option-card">
            <div class="study-option-title">Scholarship?</div>
            <div class="study-option-question">
                What scholarships, bursaries, grants, or other forms of financial assistance are available for international students / applicants in my situation, and what are the eligibility requirements and application deadlines?
            </div>
            <a href="javascript:void(0);" class="study-option-button" data-action="scholarship-search">
                Scholarship Search
            </a>
        </div>

        <div class="study-option-card">
            <div class="study-option-title">When to start?</div>
            <div class="study-option-question">
                What are the available intake periods / commencement dates for the recommended programs, and what are the corresponding application submission deadlines?
            </div>
            <a href="javascript:void(0);" class="study-option-button" data-action="timeline-actions">
                Timeline Actions
            </a>
        </div>

        <div class="study-option-card">
            <div class="study-option-title">Talk to agent</div>
            <div class="study-option-question">
                Contact our study abroad advisors for personalized assistance and support throughout your application process.
            </div>
            <a href="javascript:void(0);" class="study-option-button" data-action="contact-agent">
                Contact An Agent
            </a>
        </div>
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
