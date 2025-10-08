/**
 * Welcome Message Module
 * Handles the chatbot welcome message display
 */

const WELCOME_SUBTITLES = [
    { time: 0, text: "Hi there! I'm AI-mmi" },
    {
        time: 2,
        text: "your smart companion for migration and education planning.",
    },
    {
        time: 5,
        text: "I can help you explore study options, understand different migration pathways, and get ready for your move abroad",
    },
    { time: 12, text: "all in one easy-to-use platform." },
    {
        time: 14,
        text: "Whether you'd like to do it yourself with our personalized guided tools,",
    },
    {
        time: 17,
        text: "or connect with a trusted professional, I'm here to make your journey smoother, more affordable, and stress-free.",
    },
    {
        time: 24,
        text: "From choosing the right university or course, exploring visa options for your dream destination,",
    },
    {
        time: 29,
        text: "preparing documents, arranging English lessons, finding accommodation or jobs, to relocation services",
    },
    { time: 35, text: "even moving your pets!" },
    {
        time: 38,
        text: "I'll help you stay informed and organized every step of the way.",
    },
    {
        time: 42,
        text: "If you haven't signed up yet go ahead and create your free AI-mmi account to start planning your study or migration journey today!",
    },
];

function initWelcomeMessage() {
    // If user has chat mode set, remove welcome message only
    if (_current_chat_mode && _current_chat_mode !== "") {
        $(".welcome-message").remove();
        return;
    }

    // Check for chat history
    $.getJSON(_page_base_url + "/home/chat", function (data) {
        if (data && data.length > 0) {
            $(".welcome-message").remove();
        } else {
            displayWelcomeMessage();
        }
    }).fail(function () {
        displayWelcomeMessage();
    });
}

function removeWelcomeAndShowChat() {
    // Remove welcome message completely
    $(".welcome-message").remove();

    // Show chat UI
    $(".input-question").addClass("show");
    $("#ask_question").prop("disabled", false);
    $(".robot-container").show();
}

function setChatMode(mode) {
    // Set the hidden input value
    $("#chat_mode").val(mode);

    // Update UI based on mode
    if (typeof restoreChatModeUI === "function") {
        restoreChatModeUI(mode);
    }

    // Remove welcome message and show chat
    removeWelcomeAndShowChat();

    // Load chat history for this mode (this will also save mode to session via GET parameter)
    if (typeof loadChatMessage === "function") {
        loadChatMessage(1);
    }
}

function displayWelcomeMessage() {
    if ($(".welcome-message").length === 0) {
        return;
    }

    // Show welcome message - display only, no interactive buttons
    $(".robot-container").hide();
    $(".welcome-message").addClass("show").show();
    initializeWelcomeVideo();
    animateWelcomeTranscript();
}

function initializeWelcomeVideo() {
    setTimeout(function () {
        const video = document.getElementById("welcome-robot-video");
        if (video) {
            video.muted = true; // Must be muted for autoplay
            video.play().catch(function (error) {
                console.log("Video autoplay failed:", error);
            });
        }
    }, 100);

    // Welcome video sound control
    $(document).off("click", "#welcome-sound-control");
    $(document).on("click", "#welcome-sound-control", function () {
        const video = document.getElementById("welcome-robot-video");
        if ($(this).hasClass("opened")) {
            $(this).removeClass("opened");
            $(this).attr("title", "Click to unmute");
            $("#welcome-sound-control > i")
                .removeClass("fa-microphone-slash")
                .removeClass("fa-microphone")
                .addClass("fa-microphone");
            if (video) video.muted = true;
        } else {
            $(this).addClass("opened");
            $(this).attr("title", "Click to mute");
            $("#welcome-sound-control > i")
                .removeClass("fa-microphone-slash")
                .removeClass("fa-microphone")
                .addClass("fa-microphone-slash");
            if (video) video.muted = false;
        }
    });
}

function animateWelcomeTranscript() {
    const video = document.getElementById("welcome-robot-video");
    const transcriptElement = $(".welcome-message__transcript-line");
    let currentSubtitleIndex = -1;
    let typingTimeout = null;

    function typeText(text, charIndex = 0) {
        if (!transcriptElement.length) return;

        if (charIndex <= text.length) {
            transcriptElement.text(text.substring(0, charIndex));
            transcriptElement.addClass("show");
            typingTimeout = setTimeout(function () {
                typeText(text, charIndex + 1);
            }, 50); // Typing speed
        }
    }

    function updateSubtitle() {
        if (!transcriptElement.length || !video) return;

        const currentTime = video.currentTime;

        for (let i = WELCOME_SUBTITLES.length - 1; i >= 0; i--) {
            if (currentTime >= WELCOME_SUBTITLES[i].time) {
                if (currentSubtitleIndex !== i) {
                    currentSubtitleIndex = i;

                    if (typingTimeout) {
                        clearTimeout(typingTimeout);
                    }

                    typeText(WELCOME_SUBTITLES[currentSubtitleIndex].text);
                }
                break;
            }
        }
    }

    if (video) {
        video.addEventListener("timeupdate", updateSubtitle);

        video.addEventListener("ended", function () {
            if (typingTimeout) {
                clearTimeout(typingTimeout);
            }
        });
    }
}

// Auto-initialize when document is ready
$(document).ready(function () {
    // Remove welcome message immediately if user has chat mode
    if (_current_chat_mode && _current_chat_mode !== "") {
        $(".welcome-message").remove();
    }

    initWelcomeMessage();

    // Handle chat mode button clicks
    $(document).on("click", ".welcome-option-btn", function () {
        var mode = $(this).data("mode");
        setChatMode(mode);
    });
});
