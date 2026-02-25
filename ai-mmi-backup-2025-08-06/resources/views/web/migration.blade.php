@extends('web.common')

@section('title', 'Migration Services')

@push('css')
<link rel="stylesheet" href="/asset/css/web/migration.css?v={{ date('Ymd') }}">
@endpush

@section('content')
<div class="migration-container">
    <div class="migration-header">
        <h1>Migration Services</h1>
        <p>Choose a topic to get personalized assistance with your migration journey</p>
        <p class="migration-chat-notice">Answers will appear in the chatbox on the right.</p>
    </div>

    <div class="migration-options">
        <div class="migration-option-card">
            <div class="migration-option-title">Can I go?</div>
            <div class="migration-option-question">
                Am I eligible to migrate based on my current qualifications and profile?
            </div>
            <a href="<?php echo $_page_base_url.'/migration_eligibility'; ?>" class="migration-option-button" data-action="eligibility-check">
                Eligibility Assessment
            </a>
        </div>

        <div class="migration-option-card">
            <div class="migration-option-title">Where to go?</div>
            <div class="migration-option-question">
                Which countries and visa pathways would you recommend as the strongest options for someone with my background?
            </div>
            <a href="javascript:void(0);" class="migration-option-button" data-action="country-comparison">
                Country Comparison
            </a>
        </div>

        <div class="migration-option-card">
            <div class="migration-option-title">How much?</div>
            <div class="migration-option-question">
                What are the estimated total costs (visa fees, document processing, legal fees, relocation expenses, etc.)?
            </div>
            <a href="javascript:void(0);" class="migration-option-button" data-action="cost-estimates">
                Cost Estimates
            </a>
        </div>

        <div class="migration-option-card">
            <div class="migration-option-title">When to start?</div>
            <div class="migration-option-question">
                What are the processing timeframes for different visa types, and what are the corresponding application submission deadlines?
            </div>
            <a href="javascript:void(0);" class="migration-option-button" data-action="timeline-actions">
                Timeline & Actions
            </a>
        </div>

        <div class="migration-option-card">
            <div class="migration-option-title">Talk to agent</div>
            <div class="migration-option-question">
                Contact our migration advisors for personalized assistance and support throughout your application process.
            </div>
            <a href="<?php echo $_page_base_url.'/agent_chat'; ?>" class="migration-option-button">
                Contact An Agent
            </a>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="/asset/js/web/migration.js?v={{ date('Ymd') }}"></script>
@if(session('trigger_assessment'))
<script>
    window.triggerAssessment = true;
    window.assessmentPrompt = {!! json_encode(session('eligibility_assessment')['prompt'] ?? '') !!};
</script>
@endif
@endpush
