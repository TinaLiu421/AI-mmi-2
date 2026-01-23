@extends('web.common')

@section('title', 'Migration Eligibility Check')

@push('css')
<link rel="stylesheet" href="/asset/css/web/migration_eligibility.css?v={{ date('Ymd') }}">
@endpush

@section('content')
<div class="eligibility-container">
    <div class="eligibility-header">
        <h1>Can I Migrate?</h1>
        <p>Complete this eligibility assessment to discover your migration opportunities</p>
    </div>

    <form id="eligibility-form" class="eligibility-form">
        @csrf
        
        <!-- Question 1: Destination Countries -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">1.</span>
                Where do you like to move or study? <span class="required">*</span>
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

        <!-- Question 2: Visa Types -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">2.</span>
                What types of visa you would like to explore? <span class="required">*</span>
            </label>
            <p class="form-help">You can select multiple visa types</p>
            <div class="checkbox-grid">
                @foreach($_page_data['visa_types'] as $visa)
                <label class="checkbox-label">
                    <input type="checkbox" name="visa_types[]" value="{{ $visa }}">
                    <span class="checkbox-text">{{ $visa }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <!-- Question 3: Nationality -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">3.</span>
                What's your nationality? <span class="required">*</span>
            </label>
            <input type="text" name="nationality" class="form-input" required placeholder="Enter your nationality">
        </div>

        <!-- Question 4: Current Country of Residency -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">4.</span>
                What's your current country of residency? <span class="required">*</span>
            </label>
            <input type="text" name="residency" class="form-input" required placeholder="Enter your current country of residency">
        </div>

        <!-- Question 5: Age -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">5.</span>
                What's your age? <span class="required">*</span>
            </label>
            <input type="number" name="age" class="form-input" required placeholder="Enter your age" min="1" max="120">
        </div>

        <!-- Question 6: Education Level -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">6.</span>
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

        <!-- Question 7: English Test Completed -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">7.</span>
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

        <!-- Question 8: Test Results (conditional) -->
        <div class="form-group" id="test-results-group" style="display: none;">
            <label class="form-label">
                <span class="question-number">8.</span>
                If yes, what are the test results?
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

        <!-- Question 9: Occupation -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">9.</span>
                What is your occupation? <span class="required">*</span>
            </label>
            <input type="text" name="occupation" class="form-input" required placeholder="Enter your occupation">
        </div>

        <!-- Question 10: Total Work Experience -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">10.</span>
                How many years full time work experience you got? <span class="required">*</span>
            </label>
            <select name="total_work_experience" class="form-input" required>
                <option value="">Select years</option>
                @for($i = 0; $i <= 30; $i++)
                <option value="{{ $i }}">{{ $i }} {{ $i == 1 ? 'year' : 'years' }}</option>
                @endfor
            </select>
        </div>

        <!-- Question 11: Occupation-Specific Work Experience -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">11.</span>
                How many years full time work experience in the occupation you indicated? <span class="required">*</span>
            </label>
            <select name="occupation_work_experience" class="form-input" required>
                <option value="">Select years</option>
                @for($i = 0; $i <= 30; $i++)
                <option value="{{ $i }}">{{ $i }} {{ $i == 1 ? 'year' : 'years' }}</option>
                @endfor
            </select>
        </div>

        <!-- Question 12: Work Experience in Destination -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">12.</span>
                Do you have full time work experience in the country of your destination? <span class="required">*</span>
            </label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="destination_work_experience" value="Yes" required>
                    <span class="radio-text">Yes</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="destination_work_experience" value="No" required>
                    <span class="radio-text">No</span>
                </label>
            </div>
            <div id="destination-years-group" style="display: none; margin-top: 15px;">
                <label class="form-label">How many years?</label>
                <select name="destination_work_years" class="form-input">
                    <option value="">Select years</option>
                    @for($i = 1; $i <= 30; $i++)
                    <option value="{{ $i }}">{{ $i }} {{ $i == 1 ? 'year' : 'years' }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <!-- Question 13: Job Offer -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">13.</span>
                Do you have a job offer from the country of your destination? <span class="required">*</span>
            </label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="job_offer" value="Yes" required>
                    <span class="radio-text">Yes</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="job_offer" value="No" required>
                    <span class="radio-text">No</span>
                </label>
            </div>
        </div>

        <!-- Question 14: Outstanding Achievements -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">14.</span>
                Do you have any outstanding achievements? <span class="required">*</span>
            </label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="outstanding_achievements" value="Yes" required>
                    <span class="radio-text">Yes</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="outstanding_achievements" value="No" required>
                    <span class="radio-text">No</span>
                </label>
            </div>
            <div id="achievements-details-group" style="display: none; margin-top: 15px;">
                <label class="form-label">Please give details</label>
                <textarea name="achievements_details" class="form-input" rows="4" placeholder="Describe your achievements..."></textarea>
            </div>
        </div>

        <!-- Question 15: CV Upload -->
        <div class="form-group">
            <label class="form-label">
                <span class="question-number">15.</span>
                Please attach your CV if possible
            </label>
            <div class="file-upload-area">
                <input type="file" name="cv_file" id="cv-file" class="file-input" accept=".pdf,.doc,.docx">
                <label for="cv-file" class="file-upload-label">
                    <i class="fa fa-cloud-upload"></i>
                    <div>Click to upload your CV</div>
                    <div class="file-upload-hint">PDF, DOC, DOCX (Max 10MB)</div>
                </label>
            </div>
            <div id="cv-file-list" class="file-list"></div>
        </div>

        <!-- Submit Button -->
        <div class="form-actions">
            <button type="submit" class="btn-submit">
                <i class="fa fa-paper-plane"></i>
                Submit Assessment
            </button>
        </div>
    </form>

    <!-- Results Section (hidden initially) -->
    <div id="assessment-results" class="assessment-results" style="display: none;">
        <div class="results-header">
            <h2>Your Assessment Results</h2>
        </div>
        <div class="results-content">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Analyzing your profile...</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="/asset/js/web/migration_eligibility.js?v={{ date('Ymd') }}"></script>
@endpush
