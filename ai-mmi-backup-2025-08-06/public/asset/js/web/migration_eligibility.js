$(function() {
    "use strict";

    // Initialize form state
    initializeFormHandlers();

    function initializeFormHandlers() {
        // Handle English test radio button changes
        $('input[name="english_test"]').on('change', function() {
            if ($(this).val() === 'yes') {
                $('#testResultsSection').slideDown(300);
            } else {
                $('#testResultsSection').slideUp(300);
                // Clear test results when hidden
                $('#testResultsSection input').val('');
            }
        });

        // Handle destination work experience radio button changes
        $('input[name="destination_work"]').on('change', function() {
            if ($(this).val() === 'yes') {
                $('#destinationWorkYears').slideDown(300);
            } else {
                $('#destinationWorkYears').slideUp(300);
                // Clear value when hidden
                $('#destination_years').val('');
            }
        });

        // Handle outstanding achievements radio button changes
        $('input[name="achievements"]').on('change', function() {
            if ($(this).val() === 'yes') {
                $('#achievementDetails').slideDown(300);
            } else {
                $('#achievementDetails').slideUp(300);
                // Clear text when hidden
                $('#achievement_details').val('');
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
        $('#migration_eligibility_form').on('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!validateForm()) {
                return false;
            }

            // Show loading state
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Assessing...');

            // Collect form data
            const formData = new FormData(this);
            
            // Send to backend
            $.ajax({
                url: page_base_url + '/migration_eligibility/submit',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Show results
                    displayResults(response);
                },
                error: function(xhr, status, error) {
                    alert('An error occurred while processing your request. Please try again.');
                    submitBtn.prop('disabled', false).html(originalText);
                },
                complete: function() {
                    // Re-enable button after processing
                    setTimeout(function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }, 1000);
                }
            });
        });
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
        if (!$('input[name="english_test"]:checked').val()) {
            alert('Please indicate if you have completed an English proficiency test.');
            isValid = false;
            firstError = firstError || $('input[name="english_test"]').first();
        }

        // If English test is Yes, validate test results
        if ($('input[name="english_test"]:checked').val() === 'yes') {
            const hasTestScore = $('#testResultsSection input').filter(function() {
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
        if (!$('input[name="achievements"]:checked').val()) {
            alert('Please indicate if you have outstanding achievements.');
            isValid = false;
            firstError = firstError || $('input[name="achievements"]').first();
        }

        // Check if destination work experience option is selected
        if (!$('input[name="destination_work"]:checked').val()) {
            alert('Please indicate if you have work experience in the destination country.');
            isValid = false;
            firstError = firstError || $('input[name="destination_work"]').first();
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
    $('#testResultsSection').hide();
    $('#destinationWorkYears').hide();
    $('#achievementDetails').hide();
});
