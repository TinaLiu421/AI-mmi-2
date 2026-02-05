$(document).ready(function() {
    // Predefined messages for each migration option
    const migrationMessages = {
        'country-comparison': 'Which countries and institutions would you recommend as the strongest options for someone with my background?',
        'cost-estimates': 'What are the estimated total costs (visa fees, document processing, legal fees, relocation expenses, etc.)?',
        'timeline-actions': 'What are the processing timeframes for different visa types, and what are the corresponding application submission deadlines?',
        'contact-agent': 'I would like to contact a migration advisor for personalized assistance and support throughout my application process.'
    };

    // Handle migration option button clicks
    $('.migration-option-button').on('click', function(e) {
        const action = $(this).data('action');
        const href = $(this).attr('href');
        
        // If the button has a valid href (not javascript:void), follow it
        if (href && href !== 'javascript:void(0);') {
            // Let the browser follow the link naturally
            return true;
        }
        
        // Prevent default action
        e.preventDefault();
        
        // Check if this action has a predefined message
        if (migrationMessages[action]) {
            // Send message to chatbox
            sendToChatbox(migrationMessages[action]);
        } else {
            console.log('Migration action:', action, '- No message defined');
        }
    });

    /**
     * Send a predefined message to the chatbox
     */
    function sendToChatbox(message) {
        // Get the chat input field
        const $chatInput = $('#ask_question');
        
        if ($chatInput.length === 0) {
            console.error('Chat input not found');
            return;
        }

        // Set the message in the input field
        $chatInput.val(message);

        // Show mobile chat if on mobile
        if ($(window).width() <= 768) {
            if (!$('main.page-body div.chat-area').hasClass('show-mobile')) {
                toggleMobileChat();
            }
        }

        // Scroll to chat area smoothly
        $('html, body').animate({
            scrollTop: $('.chat-area').offset().top - 100
        }, 500, function() {
            // Auto-submit the form after scrolling
            $('#ask-form').submit();
        });
    }

    // Check if we need to trigger AI assessment after redirect from eligibility form
    if (window.triggerAssessment && window.assessmentPrompt) {
        // Scroll to chat area if mobile
        if (window.innerWidth <= 768) {
            if (typeof toggleMobileChat === 'function') {
                toggleMobileChat();
            }
        } else {
            // Scroll to chat on desktop
            $('html, body').animate({
                scrollTop: $('.chat-area').offset().top - 100
            }, 500);
        }
        
        // Wait a moment for the page to settle
        setTimeout(function() {
            // Set the message in the chat input
            $('#ask_question').val(window.assessmentPrompt);
            
            // Submit the chat form to get AI assessment
            $('#ask-form').submit();
        }, 800);
    }
});
