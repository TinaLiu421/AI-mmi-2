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

    // Handle file upload display
    $('#school-results').on('change', function() {
        const fileList = $('#file-list');
        fileList.empty();
        
        const files = this.files;
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const fileItem = $('<div class="file-item"></div>');
            const fileName = $('<span class="file-name"></span>').text(file.name);
            const fileRemove = $('<span class="file-remove">Remove</span>');
            
            fileRemove.on('click', function() {
                fileItem.remove();
                // Reset file input if no files left
                if ($('.file-item').length === 0) {
                    $('#school-results').val('');
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
