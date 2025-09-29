// Immigration Chat Functionality
$(document).ready(function() {

    // Function to restore chat mode UI
    function restoreChatModeUI(mode) {
        if (!mode) return;

        // Set the chat mode
        $('#chat_mode').val(mode);
        $('#ask-form').attr('data-chat-mode', mode);

        // Show/hide appropriate mode indicators
        if (mode === 'immigration') {
            $('#chat-mode-indicator').show();
            $('#study-mode-indicator').hide();
            $('#ask_question').attr('placeholder', 'Ask about immigration, visas, or migration...');
        } else if (mode === 'study') {
            $('#study-mode-indicator').show();
            $('#chat-mode-indicator').hide();
            $('#ask_question').attr('placeholder', 'Ask about study options, education, or courses...');
        }

        // Enable the input field and show the form
        $('#ask_question').prop('disabled', false);
        $('#ask-form').removeClass('hidden');
        $('.input-question').addClass('show');

        // Show expand buttons
        $("main.page-body div.chat-area div.box > a.btn-expand-full").show();
        $("main.page-body div.chat-area div.box > a.btn-expand-full-mobile").show();

        // Add visual feedback to selected button
        $('.chat-mode-btn').removeClass('active');
        $('.chat-mode-btn[data-mode="' + mode + '"]').addClass('active');

        console.log('Restored chat mode UI for:', mode);
    }

    // Check for existing chat mode on page load and restore UI if needed
    function checkExistingChatMode() {
        // Check if we have a saved chat mode from session
        if (typeof _current_chat_mode !== 'undefined' && _current_chat_mode && _current_chat_mode !== '') {
            console.log('Restoring chat mode from session:', _current_chat_mode);
            restoreChatModeUI(_current_chat_mode);
            // Load chat history for this mode
            loadChatMessage(1);
            return;
        }

        // If no session mode, check if user has any chat history and restore appropriate mode
        $.getJSON(_page_base_url + '/home/chat', function(data) {
            if (data && data.length > 0) {
                var firstMessage = data[0];
                var mode = firstMessage.chat_mode || 'immigration';
                console.log('Restoring chat mode from history:', mode);
                restoreChatModeUI(mode);
                // Load chat history for this mode
                loadChatMessage(1);
            }
        }).fail(function() {
            console.log('No chat history or user not logged in');
        });
    }

    // Initialize on page load
    setTimeout(function() {
        checkExistingChatMode();
    }, 500); // Small delay to ensure other page elements are loaded

    // Handle chat mode button clicks
    $(document).on('click', '.chat-mode-btn', function(e) {
        e.preventDefault();

        var mode = $(this).data('mode');
        var currentMode = $('#ask-form').attr('data-chat-mode');

        // Set the chat mode FIRST
        $('#chat_mode').val(mode);
        $('#ask-form').attr('data-chat-mode', mode);

        // If switching modes, clear the chat history and load new mode's history
        if (currentMode && currentMode !== mode) {
            $("main.page-body div.chat-area div.box > div.show-message").html('');
            console.log('Mode switch from', currentMode, 'to', mode, '- loading new chat history');

            // Load chat history for the new mode after a short delay
            setTimeout(function() {
                loadChatMessage(1);
            }, 100);
        }

        // Show/hide appropriate mode indicators
        if (mode === 'immigration') {
            $('#chat-mode-indicator').show();
            $('#study-mode-indicator').hide();
            $('#ask_question').attr('placeholder', 'Ask about immigration, visas, or migration...');
        } else if (mode === 'study') {
            $('#study-mode-indicator').show();
            $('#chat-mode-indicator').hide();
            $('#ask_question').attr('placeholder', 'Ask about study options, education, or courses...');
        }

        // Enable the input field and show the form
        $('#ask_question').prop('disabled', false);
        $('#ask-form').removeClass('hidden');
        $('.input-question').addClass('show');

        // Clear welcome message and show expand button
        if ($("main.page-body div.chat-area div.box > div.show-message").html().includes('Click one of the buttons above')) {
            $("main.page-body div.chat-area div.box > div.show-message").html('');
        }

        // Show expand buttons
        $("main.page-body div.chat-area div.box > a.btn-expand-full").show();
        $("main.page-body div.chat-area div.box > a.btn-expand-full-mobile").show();

        // Add visual feedback to selected button
        $('.chat-mode-btn').removeClass('active');
        $(this).addClass('active');

        // Load chat history for the selected mode
        loadChatMessage(1);

        // Focus on input field
        $('#ask_question').focus();

        console.log('Chat mode set to:', mode);
    });

    // Handle direct clicks on the chat-button component inside chat-mode-btn
    $(document).on('click', '.chat-mode-btn .chat-button', function(e) {
        e.preventDefault();
        e.stopPropagation();

        // Trigger the parent chat-mode-btn click
        $(this).closest('.chat-mode-btn').trigger('click');
    });

});