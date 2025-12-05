@extends('web.common')

@section('content')
@php
    $member = $_current_member ?? [];
    $defaultEmail = $member['email'] ?? '';
    $defaultMobile = $member['phone'] ?? ($member['mobile'] ?? ($member['contact_number'] ?? ''));
    $yearStart = (int) date('Y');
    $yearOptions = [];
    for ($i = 0; $i < 5; $i++) {
        $yearOptions[] = (string) ($yearStart + $i);
    }
    $educationOptions = [
        'High school / Year 12',
        'Certificate / Diploma',
        'Advanced Diploma',
        'Bachelor degree',
        'Graduate Diploma',
        'Master degree',
        'Doctorate / PhD',
        'Other'
    ];
@endphp

<div class="apply-page" id="apply-page-top" data-member-id="{{ $member['id'] ?? '' }}">
    <section class="card apply-hero">
        <div>
            <p class="eyebrow">Education · Course Application</p>
            <h1>Tell AI-mmi about your study plan</h1>
            <p>Complete the form, attach the required documents, and NO application fee to secure priority support from AI-mmi counsellors.</p>
        </div>
            <ul class="hero-highlights">
                <li>
                    <strong>Complete Support</strong>
                    <p>AI-mmi will review your goals, budget, scholarship interest, and documents in one place.</p>
                </li>
                <li>
                    <strong>Documents &amp; Payment</strong>
                    <p>Upload your passport, academic certificates, English results, and proof of funds, then pay AUD $0 securely via Stripe.</p>
                </li>
                <li>
                    <strong>Scholarship Ready</strong>
                    <p>Let AI-mmi know the colleges you want so AI-mmi can match the best AUD$1,000 scholarships.</p>
                </li>
            </ul>
    </section>

    <div class="apply-layout">
        <div class="apply-main">
            <div id="application-status" class="apply-status" role="alert" aria-live="polite"></div>

            <form id="course-application-form" class="card apply-card" enctype="multipart/form-data">
                <input type="hidden" name="application_id" id="application-id">
                <input type="hidden" name="intent" id="application-intent" value="submit">

                <div class="section-heading">
                    <div>
                        <h2>Applicant profile</h2>
                        <p>Everything we need to assess your eligibility.</p>
                    </div>
                    <span class="required-hint">All fields are required unless stated otherwise.</span>
                </div>

                <div class="form-grid two-column">
                    <label class="input-field">
                        <span>Family Name</span>
                        <input type="text" name="family_name" id="family_name" placeholder="e.g. Newton" required>
                    </label>
                    <label class="input-field">
                        <span>Given Name</span>
                        <input type="text" name="given_name" id="given_name" placeholder="e.g. Isaac" required>
                    </label>
                    <label class="input-field">
                        <span>Email address</span>
                        <input type="email" name="email_address" id="email_address" value="{{ $defaultEmail }}" placeholder="name@email.com" required>
                    </label>
                    <label class="input-field">
                        <span>Mobile number (include country code)</span>
                        <input type="text" name="mobile_number" id="mobile_number" value="{{ $defaultMobile }}" placeholder="+61 400 123 888" required>
                    </label>
                    <label class="input-field">
                        <span>Date of Birth</span>
                        <input type="text" name="date_of_birth" id="date_of_birth" placeholder="dd/mm/yyyy" lang="en-AU" required>
                    </label>
                    <label class="input-field">
                        <span>Nationality</span>
                        <input type="text" name="nationality" id="nationality" placeholder="e.g. Malaysia" required>
                    </label>
                </div>

                <label class="input-field full-width address-field">
                    <span>Residential address</span>
                    <textarea name="residential_address" id="residential_address" rows="3" placeholder="Street, city, state, country" required></textarea>
                </label>

                <label class="input-field full-width">
                    <span>Highest education completed</span>
                    <select name="highest_education" id="highest_education" required>
                        <option value="">Please select</option>
                        @foreach ($educationOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="section-heading compact">
                    <div>
                        <h3>English proficiency</h3>
                        <p>Tell us whether you have taken any recognised test.</p>
                    </div>
                </div>

                <div class="chooser">
                    <label>
                        <input type="radio" name="has_english_test" value="yes">
                        <span>I have completed an English test</span>
                    </label>
                    <label>
                        <input type="radio" name="has_english_test" value="no" checked>
                        <span>I have not taken one</span>
                    </label>
                </div>

                <label class="input-field full-width">
                    <span>Select English Test</span>
                    <select name="english_test[selected]" id="english-test-select">
                        <option value="">Choose a test</option>
                        <option value="IELTS">IELTS</option>
                        <option value="TOEFL iBT">TOEFL iBT</option>
                        <option value="Cambridge">Cambridge</option>
                        <option value="PTE">PTE</option>
                        <option value="OET">OET</option>
                    </select>
                </label>
                <div class="english-test-scores" id="english-test-score-fields" style="display:none;">
                    <div>
                        <label>Overall Score</label>
                        <input type="text" inputmode="decimal" name="english_test[scores][overall]" placeholder="Overall score">
                    </div>
                    <div>
                        <label>Listening</label>
                        <input type="text" inputmode="decimal" name="english_test[scores][listening]" placeholder="Listening score">
                    </div>
                    <div>
                        <label>Speaking</label>
                        <input type="text" inputmode="decimal" name="english_test[scores][speaking]" placeholder="Speaking score">
                    </div>
                    <div>
                        <label>Reading</label>
                        <input type="text" inputmode="decimal" name="english_test[scores][reading]" placeholder="Reading score">
                    </div>
                    <div>
                        <label>Writing</label>
                        <input type="text" inputmode="decimal" name="english_test[scores][writing]" placeholder="Writing score">
                    </div>
                </div>

                <div class="section-heading compact">
                    <div>
                        <h3>Study preferences</h3>
                        <p>Share the course and scholarship information.</p>
                    </div>
                </div>

                <label class="input-field full-width">
                    <span>What university/college are you applying for?</span>
                    <input type="text" name="target_institution" id="target_institution" placeholder="College or university name" required>
                </label>
                <label class="input-field full-width">
                    <span>What program/course are you applying for?</span>
                    <input type="text" name="target_program" id="target_program" placeholder="e.g. Diploma of IT" required>
                </label>
                <label class="input-field full-width">
                    <span>What year do you want to start?</span>
                    <select name="start_year" id="start_year" required>
                        <option value="">Select year</option>
                        @foreach ($yearOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="input-field full-width">
                    <span>Financial capacity</span>
                    <p class="field-hint">
                        Do you have sufficient money in the bank to support 12-month study and living expenses? (Example: AUD$10,000 tuition + AUD$30,000 living = minimum AUD$40,000).
                    </p>
                    <div class="chooser">
                        <label>
                            <input type="radio" name="has_financial_support" value="yes">
                            <span>Yes</span>
                        </label>
                        <label>
                            <input type="radio" name="has_financial_support" value="no" checked>
                            <span>No / Not yet</span>
                        </label>
                    </div>
                    <textarea name="financial_notes" id="financial_notes" rows="3" placeholder="Optional comments about the available funds or sponsor."></textarea>
                </div>

                <div class="input-field full-width">
                    <span>Scholarship preference</span>
                    <p class="field-hint">
                        Scholarships (AUD$1,000 one-off to offset tuition, minimum 2-year study) are available for these colleges only.
                    </p>
                    <div class="chooser stacked">
                        <label>
                            <input type="radio" name="wants_scholarship" value="yes">
                            <span>I want to be considered</span>
                        </label>
                        <label>
                            <input type="radio" name="wants_scholarship" value="no" checked>
                            <span>No thanks</span>
                        </label>
                    </div>

                    <div class="scholarship-options">
                        @php
                            $scholarshipColleges = [
                                'SBTA-SELA',
                                'Queensland Academy of Technology',
                                'Australia College of Tourism & Information Technology',
                                'Queensland International Institute',
                                'Rosehill College'
                            ];
                        @endphp
                        @foreach ($scholarshipColleges as $college)
                            <label class="checkbox-chip">
                                <input type="checkbox" name="scholarship_colleges[]" value="{{ $college }}">
                                <span>{{ $college }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

            <section class="card apply-card attachments-card" id="application-documents">
                <div class="section-heading">
                    <div>
                        <h2>Supporting documents</h2>
                        <p>Upload clear PDF/JPG/PNG files (max 10&nbsp;MB each).</p>
                    </div>
                </div>
                <div class="document-grid">
                    <div class="document-field" data-doc-key="passport_copy">
                        <div class="doc-label">Copy of your passport</div>
                        <p>Preferably the bio-data page.</p>
                        <div class="doc-action">
                            <label class="doc-upload">
                                <input type="file" name="passport_copy" id="passport_copy" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                                <span>Select file</span>
                            </label>
                            <div class="doc-status" id="passport_copy_status">No file uploaded yet.</div>
                        </div>
                    </div>
                    <div class="document-field" data-doc-key="education_certificate">
                        <div class="doc-label">Copy of your education certificate</div>
                        <p>Latest academic transcripts or certificates.</p>
                        <div class="doc-action">
                            <label class="doc-upload">
                                <input type="file" name="education_certificate" id="education_certificate" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                                <span>Select file</span>
                            </label>
                            <div class="doc-status" id="education_certificate_status">No file uploaded yet.</div>
                        </div>
                    </div>
                    <div class="document-field" data-doc-key="english_test_result">
                        <div class="doc-label">Copy of your English test result</div>
                        <p>If applicable.</p>
                        <div class="doc-action">
                            <label class="doc-upload">
                                <input type="file" name="english_test_result" id="english_test_result" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                                <span>Select file</span>
                            </label>
                            <div class="doc-status" id="english_test_result_status">No file uploaded yet.</div>
                        </div>
                    </div>
                    <div class="document-field" data-doc-key="financial_statement">
                        <div class="doc-label">Copy of your or sponsor&rsquo;s bank statement</div>
                        <p>Proof of sufficient funds.</p>
                        <div class="doc-action">
                            <label class="doc-upload">
                                <input type="file" name="financial_statement" id="financial_statement" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                                <span>Select file</span>
                            </label>
                            <div class="doc-status" id="financial_statement_status">No file uploaded yet.</div>
                        </div>
                    </div>
                </div>
            </section>

                <!-- reCAPTCHA v2 Verification -->
                <div class="recaptcha-container" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 4px; border: 1px solid #e0e0e0;">
                    <p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">
                        <strong>Security verification:</strong> Please verify that you're human before submitting.
                    </p>
                    <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}" data-callback="onRecaptchaSuccess" style="display: flex; justify-content: center;"></div>
                    <input type="hidden" id="recaptcha_token" name="g-recaptcha-response" value="">
                </div>

                <div class="form-actions">
                    <button type="button" class="btn ghost" id="save-application">Save draft</button>
                    <button type="button" class="btn primary" id="submit-application" disabled style="opacity: 0.6; cursor: not-allowed;" title="Please verify with reCAPTCHA first">Submit</button>
                </div>
            </form>
        </div>

        <aside class="apply-sidebar">
            <section class="card payment-card lean" id="payment-card"
                data-pricing-table-id="{{ $pricing_table_id ?? env('STRIPE_PRICING_TABLE_ID_2') }}"
                data-stripe-key="{{ $stripe_pk ?? env('STRIPE_KEY') }}"
                data-default-email="{{ $defaultEmail }}"
                data-member-id="{{ $member['id'] ?? '' }}">
                <div class="payment-card__body">
                    <div class="payment-widget-wrapper">
                        <div id="payment-widget" class="apply-payment-widget is-locked">
                            <div class="payment-placeholder">
                                <strong>Submit your application details first.</strong>
                                <p>The payment button appears once the form and documents are saved.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </aside>
    </div>
</div>

<!-- reCAPTCHA Script & Verification Handler -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
    // Called when reCAPTCHA is successfully verified
    function onRecaptchaSuccess() {
        const submitBtn = document.getElementById('submit-application');
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
        submitBtn.title = '';
    }

    // Called when reCAPTCHA expires
    function onRecaptchaExpire() {
        const submitBtn = document.getElementById('submit-application');
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.6';
        submitBtn.style.cursor = 'not-allowed';
        submitBtn.title = 'Please verify with reCAPTCHA first';
    }
</script>
@endsection
