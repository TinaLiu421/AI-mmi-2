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
        
        // Show loading state
        const resultsDiv = $('#assessment-results');
        const resultsContent = $('#results-content');
        resultsDiv.show();
        resultsContent.html('<div class="loading-spinner"><div class="spinner"></div><p>Analyzing your eligibility...</p></div>');
        
        // Scroll to results
        $('html, body').animate({
            scrollTop: resultsDiv.offset().top - 100
        }, 500);
        
        // Collect form data
        const formData = new FormData(this);
        
        // Convert FormData to object for easier handling
        const data = {};
        for (let [key, value] of formData.entries()) {
            if (key.includes('[]')) {
                const cleanKey = key.replace('[]', '');
                if (!data[cleanKey]) {
                    data[cleanKey] = [];
                }
                data[cleanKey].push(value);
            } else {
                data[key] = value;
            }
        }
        
        // Call AI assessment API
        assessEligibility(data);
    });

    function assessEligibility(data) {
        // Prepare the prompt for AI assessment
        const prompt = buildAssessmentPrompt(data);
        
        // Make API call to chat endpoint for AI assessment
        $.ajax({
            url: '/chat',
            method: 'POST',
            data: {
                message: prompt,
                chat_mode: 'study',
                _token: $('input[name="_token"]').val()
            },
            success: function(response) {
                displayResults(response.reply || response.message);
            },
            error: function(xhr, status, error) {
                $('#results-content').html(
                    '<div class="error-message">' +
                    '<i class="fa fa-exclamation-circle"></i> ' +
                    'An error occurred while assessing your eligibility. Please try again.' +
                    '</div>'
                );
            }
        });
    }

    function buildAssessmentPrompt(data) {
        let prompt = "I need you to assess my eligibility for studying abroad based on the following information:\n\n";
        
        if (data.countries && data.countries.length > 0) {
            prompt += "Countries I'm interested in: " + data.countries.join(', ') + "\n";
        }
        
        if (data.nationality) {
            prompt += "My nationality: " + data.nationality + "\n";
        }
        
        if (data.residency) {
            prompt += "Current country of residency: " + data.residency + "\n";
        }
        
        if (data.age) {
            prompt += "My age: " + data.age + "\n";
        }
        
        if (data.education_level) {
            prompt += "My education level: " + data.education_level + "\n";
        }
        
        if (data.english_test_completed) {
            prompt += "English test completed: " + data.english_test_completed + "\n";
            
            if (data.english_test_completed === 'Yes' && data.test_results) {
                prompt += "Test results: ";
                const results = [];
                for (let [test, score] of Object.entries(data.test_results)) {
                    if (score) {
                        results.push(test.replace(/_/g, ' ') + ': ' + score);
                    }
                }
                prompt += results.join(', ') + "\n";
            }
        }
        
        if (data.occupation) {
            prompt += "My occupation: " + data.occupation + "\n";
        }
        
        prompt += "\nPlease provide a detailed eligibility assessment including:\n";
        prompt += "1. Overall eligibility status for each selected country\n";
        prompt += "2. Specific visa and admission requirements I need to meet\n";
        prompt += "3. Any potential challenges or areas of concern\n";
        prompt += "4. Recommendations for improving my eligibility\n";
        prompt += "5. Suggested next steps\n";
        
        return prompt;
    }

    function displayResults(aiResponse) {
        const resultsContent = $('#results-content');
        
        // Format the AI response with proper HTML
        const formattedResponse = aiResponse
            .replace(/\n\n/g, '</p><p>')
            .replace(/\n/g, '<br>');
        
        resultsContent.html(
            '<div class="ai-assessment">' +
            '<p>' + formattedResponse + '</p>' +
            '</div>' +
            '<div class="form-actions" style="margin-top: 30px;">' +
            '<button onclick="window.location.href=\'/study\'" class="btn-submit">' +
            '<i class="fa fa-arrow-left"></i> Back to Study Options' +
            '</button>' +
            '</div>'
        );
    }
});
