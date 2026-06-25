@extends('web.common')
@section('title', 'Partner with AI-mmi')
@section('content')
<?php $_page_base_url_safe = htmlspecialchars($_page_base_url, ENT_QUOTES); ?>
<div class="ip-page">

    <div class="ip-hero">
        <div class="ip-hero-badge"><i class="fa fa-university"></i>&nbsp; Institution Partnership</div>
        <h1 class="ip-hero-title">Partner with AI-mmi</h1>
        <p class="ip-hero-sub">Connect with talented global students and grow your international enrolment. Fill in the form below and our team will be in touch.</p>
    </div>

    <div class="ip-card">

        <div id="ip-form-section">
            <div class="ip-alert error" id="ip-error-box"></div>

            <form id="ip-form" method="POST">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                <div class="ip-section-title"><i class="fa fa-building-o"></i>&nbsp; Institution Details</div>

                <div class="ip-row-2">
                    <div class="ip-field">
                        <label>Institution Name <span class="req">*</span></label>
                        <input type="text" name="institution_name" placeholder="e.g. University of Melbourne" maxlength="200">
                    </div>
                    <div class="ip-field">
                        <label>Institution Type</label>
                        <select name="institution_type">
                            <option value="">-- Select --</option>
                            <option value="University">University</option>
                            <option value="College">College / TAFE</option>
                            <option value="High School">High School / Secondary</option>
                            <option value="Language School">Language School</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="ip-row-2">
                    <div class="ip-field">
                        <label>Country / Region</label>
                        <input type="text" name="country" placeholder="e.g. Australia" maxlength="100">
                    </div>
                    <div class="ip-field">
                        <label>No. of International Students (approx.)</label>
                        <input type="text" name="intl_students" placeholder="e.g. 500" maxlength="20">
                    </div>
                </div>

                <div class="ip-field">
                    <label>Institution Website</label>
                    <input type="text" name="website" placeholder="https://" maxlength="300">
                </div>

                <div class="ip-section-title"><i class="fa fa-user-o"></i>&nbsp; Contact Person</div>

                <div class="ip-field">
                    <label>Full Name <span class="req">*</span></label>
                    <input type="text" name="contact_person" placeholder="Full name" maxlength="200">
                </div>

                <div class="ip-row-2">
                    <div class="ip-field">
                        <label>Email Address <span class="req">*</span></label>
                        <input type="email" name="email" placeholder="contact@institution.edu" maxlength="200">
                    </div>
                    <div class="ip-field">
                        <label>Phone Number <span class="req">*</span></label>
                        <input type="text" name="phone" placeholder="+61 4..." maxlength="50">
                    </div>
                </div>

                <div class="ip-section-title"><i class="fa fa-comments-o"></i>&nbsp; Partnership Message</div>

                <div class="ip-field">
                    <label>Tell us about your goals <span class="req">*</span></label>
                    <textarea name="message" placeholder="Tell us about your institution, your goals for international student recruitment, and how you'd like to partner with AI-mmi..."></textarea>
                </div>

                <button type="submit" class="ip-submit-btn" id="ip-submit-btn">
                    <i class="fa fa-paper-plane"></i>&nbsp; Send Partnership Enquiry
                </button>
            </form>
        </div>

        <div class="ip-success-panel" id="ip-success-section">
            <div class="ip-success-icon">🎉</div>
            <h2>Enquiry Sent!</h2>
            <p>
                Thank you for your interest in partnering with AI-mmi.<br>
                Our team will review your enquiry and be in touch shortly.<br>
                A confirmation has been sent to your email address.
            </p>
            <a href="<?php echo $_page_base_url_safe; ?>/study_plans" class="ip-back-link">
                <i class="fa fa-arrow-left"></i> Back to Institution Hub
            </a>
        </div>

    </div>
</div>
@endsection
