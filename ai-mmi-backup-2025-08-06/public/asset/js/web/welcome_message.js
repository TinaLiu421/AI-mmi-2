/**
 * Welcome Message Module
 * Handles the chatbot welcome message display
 * This file ONLY manages welcome message visibility - does NOT load chat history
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
    // Check if user has any chat history
    $.getJSON(_page_base_url + "/home/chat/1")
        .done(function (data) {
            var hasChatHistory = data && data.length > 0;

            if (hasChatHistory) {
                // Hide the entire welcome message when user has chat history
                $(".welcome-message").hide();
                
                // Show upload button when there's chat history
                $("#doc-upload-btn").show();
            } else {
                displayWelcomeMessage();
            }
        })
        .fail(function () {
            displayWelcomeMessage();
        });
}

function removeWelcomeAndShowChat() {
    // Remove welcome message and show chat UI
    $(".welcome-message").removeClass("show").hide();
    $(".input-question").addClass("show").show();
    // Show mode tabs + avatar panel
    $("#chat-mode-tabs").show();
    $(".chat-avatar-panel").show();
    $(".robot-container").show();
    $("#chat-robot-inner").show().css({ opacity: 1, transition: '' });
    $("#ask_question").prop("disabled", false);

    // Show chat action buttons
    $(".chat-action-buttons").show();

    // Show upload button
    $("#doc-upload-btn").show();

    // Stop the welcome video if it's playing
    const welcomeVideo = document.getElementById("welcome-robot-video");
    if (welcomeVideo) {
        welcomeVideo.pause();
        welcomeVideo.currentTime = 0;
        welcomeVideo.muted = true;
    }

    // Reset the welcome sound control button
    const soundControl = $("#welcome-sound-control");
    soundControl.removeClass("opened");
    soundControl.attr("title", "Click to unmute");
    $("#welcome-sound-control > i")
        .removeClass("fa-microphone")
        .addClass("fa-microphone-slash");
}

function startChat() {
    // Remove welcome message and show chat UI
    removeWelcomeAndShowChat();

    // Load chat history or show greeting
    if (typeof loadChatMessage === "function") {
        loadChatMessage(1); // Load initial chat history
    }

    console.log("Chat started");
}

function displayWelcomeMessage() {
    if ($(".welcome-message").length === 0) {
        return;
    }

    $(".robot-container").hide();
    $(".chat-avatar-panel").hide();
    $(".input-question").addClass("show").show();
    $("#ask_question").prop("disabled", false);
    $(".welcome-message").addClass("show").show();

    // Hide chat action buttons when welcome is showing
    $(".chat-action-buttons").hide();

    // Hide upload button when welcome message is showing
    $("#doc-upload-btn").hide();

    initializeWelcomeVideo();
    animateWelcomeTranscript();
}

function initializeWelcomeVideo() {
    setTimeout(function () {
        const video = document.getElementById("welcome-robot-video");
        if (video) {
            video.muted = true;
            video.play().catch(function (error) {
                console.log("Video autoplay failed:", error);
            });
        }
    }, 100);

    $(document).off("click", "#welcome-sound-control");
    $(document).on("click", "#welcome-sound-control", function () {
        const video = document.getElementById("welcome-robot-video");
        const icon = $("#welcome-sound-control > i");

        if ($(this).hasClass("opened")) {
            // Currently unmuted → mute
            $(this).removeClass("opened");
            $(this).attr("title", "Click to unmute");
            icon.removeClass("fa-microphone").addClass("fa-microphone-slash");
            if (video) video.muted = true;
        } else {
            // Currently muted → unmute
            $(this).addClass("opened");
            $(this).attr("title", "Click to mute");
            icon.removeClass("fa-microphone-slash").addClass("fa-microphone");
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
            }, 50);
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
    initWelcomeMessage();

    // Handle welcome message button clicks
    $(document).on("click", ".welcome-option-btn", function () {
        startChat();
    });
});
