@extends('web.common')

@section('title', 'Eligibility Check')

@push('css')
<link rel="stylesheet" href="/asset/css/web/eligibility_check.css?v={{ date('Ymd') }}">
@endpush

@section('content')
<div class="eligibility-container">
    <div class="eligibility-header">
        <h1>Can I go?</h1>
        <p>Complete this eligibility assessment to discover your study abroad opportunities</p>
    </div>

    <form id="eligibility-form" class="eligibility-form" method="POST" action="/{{ $_current_lang_code }}/eligibility_check/assess" enctype="multipart/form-data">
        @csrf
        
        <!-- Question 1: Destination Countries -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">1.</span>
                Where would you like to study? <span class="required">*</span>
            </label>
            <p class="form-help">You can select multiple countries</p>
            <div class="checkbox-grid">
                @foreach($_page_data['countries'] as $country)
                <label class="checkbox-label">
                    <input type="checkbox" name="countries[]" value="{{ $country }}">
                    <span class="checkbox-text">{{ $country }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <!-- Question 2: Nationality -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">2.</span>
                What is your nationality? <span class="required">*</span>
            </label>
            <input type="text" name="nationality" class="form-input" required placeholder="Enter your nationality">
        </div>

        <!-- Question 3: Current Country of Residency -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">3.</span>
                What is your current country of residency? <span class="required">*</span>
            </label>
            <input type="text" name="residency" class="form-input" required placeholder="Enter your current country of residency">
        </div>

        <!-- Question 4: Age -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">4.</span>
                What is your age? <span class="required">*</span>
            </label>
            <input type="number" name="age" class="form-input" required placeholder="Enter your age" min="1" max="120">
        </div>

        <!-- Question 5: Education Level -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">5.</span>
                What is your education level? <span class="required">*</span>
            </label>
            <div class="radio-group">
                @foreach($_page_data['education_levels'] as $level)
                <label class="radio-label">
                    <input type="radio" name="education_level" value="{{ $level }}" required>
                    <span class="radio-text">{{ $level }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <!-- Question 6: English Test Completed -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">6.</span>
                Have you completed an English test? <span class="required">*</span>
            </label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="english_test_completed" value="Yes" required>
                    <span class="radio-text">Yes</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="english_test_completed" value="No" required>
                    <span class="radio-text">No</span>
                </label>
            </div>
        </div>

        <!-- Question 7: Test Results (conditional) -->
        <div class="form-group" id="test-results-group" style="display: none;">
            <label class="form-label">
                <span class="question-number">7.</span>
                What are your test results?
            </label>
            <div class="test-results-container">
                @foreach($_page_data['english_tests'] as $test)
                <div class="test-item">
                    <label class="test-label">{{ $test }}</label>
                    <input type="text" name="test_results[{{ strtolower(str_replace(' ', '_', $test)) }}]" class="form-input-small" placeholder="Score">
                </div>
                @endforeach
            </div>
        </div>

        <!-- Question 8: Occupation -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">8.</span>
                What is your occupation? <span class="required">*</span>
            </label>
            <input type="text" name="occupation" class="form-input" required placeholder="Enter your occupation">
        </div>

        <!-- Question 9: School Results Upload -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">9.</span>
                Please attach your school results if possible
            </label>
            <p class="form-help">Upload your academic transcripts or certificates (PDF, JPG, PNG)</p>
            <div class="file-upload-area">
                <input type="file" name="school_results[]" id="school-results" multiple accept=".pdf,.jpg,.jpeg,.png" class="file-input">
                <label for="school-results" class="file-upload-label">
                    <i class="fa fa-cloud-upload"></i>
                    <span>Click to upload or drag and drop</span>
                    <span class="file-upload-hint">Supported formats: PDF, JPG, PNG (Max 10MB each)</span>
                </label>
                <div id="file-list" class="file-list"></div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="form-actions">
            <button type="submit" class="btn-submit">
                <i class="fa fa-check-circle"></i>
                Assess My Eligibility
            </button>
        </div>
    </form>

    <!-- Results Section (hidden initially) -->
    <div id="assessment-results" class="assessment-results" style="display: none;">
        <div class="results-header">
            <h2>Your Eligibility Assessment</h2>
        </div>
        <div id="results-content" class="results-content">
            <!-- AI assessment will be displayed here -->
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="/asset/js/web/eligibility_check.js?v={{ date('Ymd') }}"></script>
@if(session('trigger_assessment'))
<script>
    window.triggerAssessment = true;
    window.assessmentPrompt = {!! json_encode(session('eligibility_assessment')['prompt'] ?? '') !!};
</script>
@endif
@endpush
