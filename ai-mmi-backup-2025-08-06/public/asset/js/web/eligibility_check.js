$(document).ready(function() {
    // Show/hide test results based on English test completion
    $('input[name="english_test_completed"]').on('change', function() {
        if ($(this).val() === 'Yes') {
            $('#test-results-group').slideDown();
        } else {
            $('#test-results-group').slideUp();
            // Clear test results when hiding
            $('#test-results-group input').val('');
        }
    });

    // Handle file upload area - make entire area clickable
    const fileUploadArea = $('.file-upload-area');
    const schoolResults = $('#school-results');
    const fileList = $('#file-list');

    // Make the entire upload area clickable
    fileUploadArea.on('click', function(e) {
        if ($(e.target).closest('.file-remove').length || $(e.target).is('input[type="file"]')) {
            return;
        }
        schoolResults.trigger('click');
    });

    // Handle drag and drop
    fileUploadArea.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).css('background', '#e0f2fe').css('border-color', '#0284c7');
    });

    fileUploadArea.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).css('background', '').css('border-color', '');
    });

    fileUploadArea.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).css('background', '').css('border-color', '');
        
        const files = e.originalEvent.dataTransfer.files;
        schoolResults[0].files = files;
        schoolResults.trigger('change');
    });

    // Handle file upload display
    schoolResults.on('change', function() {
        fileList.empty();
        
        const files = this.files;
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const fileItem = $('<div class="file-item"></div>');
            const fileName = $('<span class="file-name"></span>').text(file.name);
            const fileRemove = $('<span class="file-remove">Remove</span>');
            
            fileRemove.on('click', function(e) {
                e.stopPropagation();
                fileItem.remove();
                // Reset file input if no files left
                if ($('.file-item').length === 0) {
                    schoolResults.val('');
                }
            });
            
            fileItem.append(fileName).append(fileRemove);
            fileList.append(fileItem);
        }
    });

    // Handle form submission
    $('#eligibility-form').on('submit', function(e) {
        e.preventDefault();
        
        // Validate at least one country is selected
        const selectedCountries = $('input[name="countries[]"]:checked').length;
        if (selectedCountries === 0) {
            alert('Please select at least one country you would like to study in.');
            return;
        }
        
        // Validate all required fields
        const requiredFields = [
            { name: 'nationality', label: 'Nationality' },
            { name: 'residency', label: 'Current country of residency' },
            { name: 'age', label: 'Age' },
            { name: 'occupation', label: 'Occupation' }
        ];
        
        for (let field of requiredFields) {
            const value = $('[name="' + field.name + '"]').val();
            if (!value || value.trim() === '') {
                alert('Please fill in: ' + field.label);
                return;
            }
        }
        
        // Validate education level is selected
        if (!$('input[name="education_level"]:checked').val()) {
            alert('Please select your education level.');
            return;
        }
        
        // Validate English test option is selected
        if (!$('input[name="english_test_completed"]:checked').val()) {
            alert('Please indicate if you have completed an English test.');
            return;
        }
        
        // If English test is Yes, at least one test score should be filled
        if ($('input[name="english_test_completed"]:checked').val() === 'Yes') {
            const hasTestScore = $('#test-results-group input').filter(function() {
                return $(this).val() && $(this).val().trim() !== '';
            }).length > 0;
            
            if (!hasTestScore) {
                alert('Please enter at least one English test score.');
                return;
            }
        }
        
        // Submit form to backend
        this.submit();
    });

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
});
