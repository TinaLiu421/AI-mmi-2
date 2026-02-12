$(document).ready(function() {
    // Handle study option button clicks (open form)
    $('.study-option-button').on('click', function(e) {
        const href = $(this).attr('href');
        if (href && href !== 'javascript:void(0);') {
            return true;
        }

        e.preventDefault();

        const $card = $(this).closest('.study-option-card');
        const $form = $card.find('.study-option-form');

        if ($form.length) {
            $form.toggleClass('is-open');
        }
    });

    // Handle form submissions
    $('.study-option-form').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const baseQuestion = $form.data('question') || '';
        const details = [];

        $form.find('[data-label]').each(function() {
            const value = ($(this).val() || '').trim();
            if (value) {
                details.push(`${$(this).data('label')}: ${value}`);
            }
        });

        const detailText = details.length ? `\n\nProfile details:\n- ${details.join('\n- ')}` : '';
        const fullPrompt = `${baseQuestion}${detailText}`;

        sendToChatbox(fullPrompt);
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
