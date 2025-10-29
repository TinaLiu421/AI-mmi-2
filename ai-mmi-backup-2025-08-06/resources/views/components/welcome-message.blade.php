{{--
Welcome Message Component for Chat

This component displays a welcome message with options for new users.
Options are dynamically rendered from the welcome_message.js configuration.
--}}

<div class="welcome-message">
    <div class="welcome-message__video-container">
        <div class="welcome-message__video-wrapper">
            <video id="welcome-robot-video" autoplay loop muted playsinline>
                <source src="asset/image/ai-robot-video.mp4" type="video/mp4">
            </video>
            <a id="welcome-sound-control" href="javascript:void(0);" title="Click to unmute">
                <i class="fa fa-microphone-slash"></i>
            </a>
        </div>
    </div>

    <div class="welcome-message__transcript">
        <div class="welcome-message__transcript-line"></div>
    </div>

    <div class="welcome-message__footer">
        <?php echo $_page_lang['chat_robot.welcome_footer']; ?>
    </div>
</div>
