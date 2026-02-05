$(function() {
    "use strict";

    // Initialize form state
    initializeFormHandlers();

    function initializeFormHandlers() {
        // Handle English test radio button changes
        $('input[name="english_test_completed"]').on('change', function() {
            if ($(this).val() === 'Yes') {
                $('#test-results-group').slideDown(300);
            } else {
                $('#test-results-group').slideUp(300);
                // Clear test results when hidden
                $('#test-results-group input').val('');
            }
        });

        // Handle destination work experience radio button changes
        $('input[name="destination_work_experience"]').on('change', function() {
            if ($(this).val() === 'Yes') {
                $('#destination-years-group').slideDown(300);
            } else {
                $('#destination-years-group').slideUp(300);
                // Clear value when hidden
                $('select[name="destination_work_years"]').val('');
            }
        });

        // Handle outstanding achievements radio button changes
        $('input[name="outstanding_achievements"]').on('change', function() {
            if ($(this).val() === 'Yes') {
                $('#achievements-details-group').slideDown(300);
            } else {
                $('#achievements-details-group').slideUp(300);
                // Clear text when hidden
                $('textarea[name="achievements_details"]').val('');
            }
        });

        // Handle CV file upload
        $('#cv_upload').on('change', function() {
            const fileName = $(this).val().split('\\').pop();
            if (fileName) {
                $(this).siblings('.file-label').text(fileName);
            } else {
                $(this).siblings('.file-label').text('Choose file...');
            }
        });

        // Form submission handler
        $('#eligibility-form').on('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!validateForm()) {
                return false;
            }

            // Submit form to backend
            this.submit();
        });
    }

    // Check if we need to trigger AI assessment after redirect
    if (window.triggerAssessment && window.assessmentPrompt) {
        // Scroll to chat area if mobile
        if (window.innerWidth <= 768) {
            if (typeof toggleMobileChat === 'function') {
                toggleMobileChat();
            }
        } else {
            // Scroll to chat on desktop
            $('html, body').animate({
                scrollTop: $('#chat-messages').offset().top - 100
            }, 500);
        }
        
        // Wait a moment for the page to settle
        setTimeout(function() {
            // Set the message in the chat input
            $('#ask_question').val(window.assessmentPrompt);
            
            // Submit the chat form to get AI assessment
            $('#ask-form').submit();
        }, 500);
    }

    function validateForm() {
        let isValid = true;
        let firstError = null;

        // Check if at least one country is selected
        if ($('input[name="countries[]"]:checked').length === 0) {
            alert('Please select at least one destination country.');
            isValid = false;
            firstError = firstError || $('input[name="countries[]"]').first();
        }

        // Check if at least one visa type is selected
        if ($('input[name="visa_types[]"]:checked').length === 0) {
            alert('Please select at least one visa type.');
            isValid = false;
            firstError = firstError || $('input[name="visa_types[]"]').first();
        }

        // Check required text fields
        const requiredFields = ['nationality', 'residency', 'age', 'occupation'];
        requiredFields.forEach(function(fieldName) {
            const field = $('[name="' + fieldName + '"]');
            if (!field.val() || field.val().trim() === '') {
                alert('Please fill in all required fields.');
                isValid = false;
                firstError = firstError || field;
                return false; // break
            }
        });

        // Check age is valid
        const age = parseInt($('[name="age"]').val());
        if (age < 18 || age > 120) {
            alert('Please enter a valid age between 18 and 120.');
            isValid = false;
            firstError = firstError || $('[name="age"]');
        }

        // Check if education level is selected
        if (!$('input[name="education_level"]:checked').val()) {
            alert('Please select your highest education level.');
            isValid = false;
            firstError = firstError || $('input[name="education_level"]').first();
        }

        // Check if English test option is selected
        if (!$('input[name="english_test_completed"]:checked').val()) {
            alert('Please indicate if you have completed an English proficiency test.');
            isValid = false;
            firstError = firstError || $('input[name="english_test_completed"]').first();
        }

        // If English test is Yes, validate test results
        if ($('input[name="english_test_completed"]:checked').val() === 'Yes') {
            const hasTestScore = $('#test-results-group input').filter(function() {
                return $(this).val() && $(this).val().trim() !== '';
            }).length > 0;

            if (!hasTestScore) {
                alert('Please enter at least one English test score.');
                isValid = false;
                firstError = firstError || $('#testResultsSection input').first();
            }
        }

        // Check if job offer option is selected
        if (!$('input[name="job_offer"]:checked').val()) {
            alert('Please indicate if you have a job offer.');
            isValid = false;
            firstError = firstError || $('input[name="job_offer"]').first();
        }

        // Check if achievements option is selected
        if (!$('input[name="outstanding_achievements"]:checked').val()) {
            alert('Please indicate if you have outstanding achievements.');
            isValid = false;
            firstError = firstError || $('input[name="outstanding_achievements"]').first();
        }

        // Check if destination work experience option is selected
        if (!$('input[name="destination_work_experience"]:checked').val()) {
            alert('Please indicate if you have work experience in the destination country.');
            isValid = false;
            firstError = firstError || $('input[name="destination_work_experience"]').first();
        }

        // Scroll to first error if any
        if (firstError) {
            $('html, body').animate({
                scrollTop: firstError.offset().top - 100
            }, 500);
        }

        return isValid;
    }

    function displayResults(response) {
        // Show results section
        $('#resultsSection').slideDown(500);

        // Populate results
        if (response.assessment) {
            $('#assessmentResult').html(response.assessment);
        }

        if (response.recommendations) {
            $('#recommendations').html(response.recommendations);
        }

        // Scroll to results
        $('html, body').animate({
            scrollTop: $('#resultsSection').offset().top - 50
        }, 800);
    }

    // Add ripple effect to file upload
    $('.file-upload-wrapper').on('click', function(e) {
        const ripple = $('<span class="ripple"></span>');
        const x = e.pageX - $(this).offset().left;
        const y = e.pageY - $(this).offset().top;
        
        ripple.css({
            left: x + 'px',
            top: y + 'px'
        });
        
        $(this).append(ripple);
        
        setTimeout(function() {
            ripple.remove();
        }, 600);
    });

    // Initialize on page load - hide conditional sections
    $('#test-results-group').hide();
    $('#destination-years-group').hide();
    $('#achievements-details-group').hide();
});
