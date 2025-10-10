// This file handles ALL chat history loading and mode management
$(document).ready(function () {
    function restoreChatModeUI(mode) {
        if (!mode) return;

        // Set chat mode
        $("#chat_mode").val(mode);
        $("#ask-form").attr("data-chat-mode", mode);

        // Show the switcher
        $(".chat-mode-switcher").show();

        // Hide all buttons first
        $(".chat-mode-switcher .chat-mode-btn").hide();

        if (mode === "immigration") {
            $('.chat-mode-btn[data-group="immigration"]').show();
        } else if (mode === "study") {
            $('.chat-mode-btn[data-group="study"]').show();
        }

        // Update placeholders & indicators
        if (mode === "immigration") {
            $("#chat-mode-indicator").show();
            $("#study-mode-indicator").hide();
            $("#ask_question").attr(
                "placeholder",
                "Ask about immigration, visas, or migration..."
            );
        } else if (mode === "study") {
            $("#study-mode-indicator").show();
            $("#chat-mode-indicator").hide();
            $("#ask_question").attr(
                "placeholder",
                "Ask about study options, education, or courses..."
            );
        }

        // Enable input & show UI
        $("#ask_question").prop("disabled", false);
        $("#ask-form").removeClass("hidden");
        $(".input-question").addClass("show");
        $(".robot-container").show();

        console.log("Restored chat mode UI for:", mode);
    }

    // Make restoreChatModeUI available globally for welcome_message.js
    window.restoreChatModeUI = restoreChatModeUI;

    // Shared function to show greeting or load history based on chat history
    function showGreetingOrLoadHistory(mode) {
        $.getJSON(_page_base_url + "/home/chat/1", { chat_mode: mode }, function(data) {
            const hasHistory = data && data.length > 0;

            if (!hasHistory) {
                // Show initial greeting only if no chat history
                let greetingText = "";
                if (mode === "immigration") {
                    greetingText = "Hi! I am your professional immigration assistant. Ask me anything about immigration, visas, or migration pathways, and we can start from there!";
                } else if (mode === "study") {
                    greetingText = "Hi! I am your professional study assistant. Ask me anything about studying abroad, universities, or courses, and we can start from there!";
                }

                const initialGreetingHtml = `
                    <div class="dialog reply initial-greeting">
                        <div class="avatar">
                            <div style="background-image:url('/asset/image/logo-mmi.png')"></div>
                        </div>
                        <div class="name">AI-mmi</div>
                        <div class="time">${new Intl.DateTimeFormat(undefined, {timeStyle: "short"}).format(new Date())}</div>
                        <div class="clearboth"></div>
                        <div class="txt">${greetingText}</div>
                    </div>
                    <div class="clearboth"></div>
                `;
                $("main.page-body div.chat-area div.box > div.show-message").append(initialGreetingHtml);

                // Scroll to show the greeting
                setTimeout(function() {
                    const element = $("main.page-body div.chat-area div.box > div.show-message")[0];
                    if (element) {
                        element.scrollTop = element.scrollHeight;
                    }
                }, 100);
            } else {
                // Load chat history
                if (typeof loadChatMessage === "function") {
                    loadChatMessage(1);
                }
            }
        });
    }

    // Make it globally available for welcome_message.js
    window.showGreetingOrLoadHistory = showGreetingOrLoadHistory;

    // Check for existing chat mode on page load and restore for returning users
    function initializeChatOnLoad() {
        // Check if we have a saved chat mode from session
        if (
            typeof _current_chat_mode !== "undefined" &&
            _current_chat_mode &&
            _current_chat_mode !== ""
        ) {
            console.log(
                "Restoring chat mode from session:",
                _current_chat_mode
            );
            restoreChatModeUI(_current_chat_mode);
            if (typeof loadChatMessage === "function") {
                loadChatMessage(1);
            }
            return;
        }

        // If no session mode, check both immigration and study for any history
        var immigrationCheck = $.getJSON(_page_base_url + "/home/chat/1", {
            chat_mode: "immigration",
        });
        var studyCheck = $.getJSON(_page_base_url + "/home/chat/1", {
            chat_mode: "study",
        });

        $.when(immigrationCheck, studyCheck).then(
            function (immigrationData, studyData) {
                var hasImmigrationHistory =
                    immigrationData[0] && immigrationData[0].length > 0;
                var hasStudyHistory = studyData[0] && studyData[0].length > 0;

                if (hasImmigrationHistory) {
                    restoreChatModeUI("immigration");
                    if (typeof loadChatMessage === "function") {
                        loadChatMessage(1);
                    }
                } else if (hasStudyHistory) {
                    restoreChatModeUI("study");
                    if (typeof loadChatMessage === "function") {
                        loadChatMessage(1);
                    }
                } else {
                    console.log("No chat history found - new user");
                }
            },
            function () {
                console.log("Error checking chat history");
            }
        );
    }

    // Initialize on page load with a delay to ensure welcome_message.js runs first
    setTimeout(function () {
        initializeChatOnLoad();
    }, 500);

    // Unified handler for chat mode buttons (switch or navigate)
    $(document).on("click", ".chat-mode-btn", function (e) {
        const link = $(this).data("link");
        const mode = $(this).data("mode");

        if (link) {
            window.location.href = link;
            return;
        }

        e.preventDefault();
        if (!mode) return;

        const currentMode = $("#ask-form").attr("data-chat-mode");

        // Update chat mode
        $("#chat_mode").val(mode);
        $("#ask-form").attr("data-chat-mode", mode);

        // If switching between modes, clear and reload chat
        if (currentMode && currentMode !== mode) {
            $("main.page-body div.chat-area div.box > div.show-message").html(
                ""
            );
            console.log(
                "Mode switch from",
                currentMode,
                "to",
                mode,
                "- loading new chat history"
            );
        }

        restoreChatModeUI(mode);

        // Use shared function to show greeting or load history
        showGreetingOrLoadHistory(mode);

        $(".chat-mode-btn").removeClass("active");
        $(this).addClass("active");

        $("#ask_question").focus();
    });

    $(document).on("click", ".chat-mode-btn .chat-button", function (e) {
        const parent = $(this).closest(".chat-mode-btn");
        const link = parent.data("link");

        if (link) {
            window.location.href = link;
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        parent.trigger("click");
    });
});
