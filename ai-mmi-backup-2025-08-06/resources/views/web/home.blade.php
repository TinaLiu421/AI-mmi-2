@extends('web.common')
@section('content')
<!-- Banner Section -->
<div class="banner banner-video-wrap">
    <video class="banner-video" autoplay muted loop playsinline preload="metadata">
        <source src="/asset/image/home-banner-video.mp4" type="video/mp4">
        <!-- fallback -->
        <img src="/asset/image/home-banner.svg" alt="AI-mmi Banner" class="banner-img"/>
    </video>
    <div class="banner-blur-overlay"></div>
    <div class="banner-content">
        <div class="banner-logo-row">
            <img src="/asset/image/logo.png" alt="AI-mmi" class="banner-logo"/>
        </div>
        <h1 class="banner-title">Your AI-Powered Migration &amp; Study Guide</h1>
        <p class="banner-sub">Get instant guidance in your language on visas, study, scholarships, and more — with access to qualified experts when you need them.</p>
        <div class="banner-cta-row">
            <a class="banner-cta-btn primary do-toapply" data-sector="migration" data-action-url="<?php echo $_page_base_url.'/agent_chat'; ?>" href="javascript:void(0);">Talk to AI-mmi</a>
            <a class="banner-cta-btn secondary" id="banner-talk-agent-btn" href="javascript:void(0);">Talk to Registered Migration Agent</a>
        </div>
    </div>
    <div class="country"></div>
</div>

<div class="inner-panel">
    @if(!empty($_current_member) && !empty($_page_data['show_agent_home_layout']))
    <div class="home-chat-notify" id="home-chat-notify" data-enabled="1">
        <div class="home-chat-notify-head">
            <div class="home-chat-notify-title">Chat Notifications</div>
            <a class="home-chat-notify-link" href="/{{ $_current_lang_code }}/agent_chat/chat">Open chat</a>
        </div>
        <div class="home-chat-notify-empty" id="home-chat-notify-empty">No unread chats.</div>
        <div class="home-chat-notify-list" id="home-chat-notify-list"></div>

        <div class="home-paid-customers" id="home-paid-customers" style="display:none;">
            <div class="home-paid-customers-title">Paid Customers</div>
            <div class="home-paid-customers-empty" id="home-paid-customers-empty">No paid customers found.</div>
            <div class="home-paid-customers-list" id="home-paid-customers-list"></div>
        </div>
    </div>
    @endif

    <?php if(!empty($_page_data['list_news'])) { ?>
    <div class="news-event">
        <div id="hslider-news" class="hslider">
            <?php foreach ($_page_data['list_news'] as $news_key => $news) { ?>
            <div>
                <a class="link" href="<?php echo $news['url']; ?>">
                    <div class="photo">
                        <img src="<?php echo $news['thumbnail']; ?>" alt=""/>
                        <?php if(empty($news['photo']) && !empty($news['youtube_url'])) { ?>
                        <iframe src="<?php echo $news['youtube_url']; ?>"></iframe>
                        <?php } ?>
                    </div>
                    <div class="title">
                        <?php echo $news['title']; ?>
                    </div>
                </a>
            </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
    
    <?php if(!empty($_page_data['list_events'])) { ?>
    <div class="news-event">
        <div id="hslider-events" class="hslider">
            <?php foreach ($_page_data['list_events'] as $events_key => $events) { ?>
            <div>
                <a class="link" href="<?php echo $events['url']; ?>">
                    <div class="photo">
                        <img src="<?php echo $events['thumbnail']; ?>" alt=""/>
                        <?php if(empty($events['photo']) && !empty($events['youtube_url'])) { ?>
                        <iframe src="<?php echo $events['youtube_url']; ?>"></iframe>
                        <?php } ?>
                    </div>
                    <div class="title">
                        <?php echo $events['title']; ?>
                    </div>
                </a>
            </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
    
    <div class="article-list" data-mid="0"></div>
</div>

<script>
window.homeChatNotifyConfig = {
    enabled: {{ (!empty($_current_member) && !empty($_page_data['show_agent_home_layout'])) ? 'true' : 'false' }},
    notificationsUrl: '/agent_chat/notifications'
};
</script>
@endsection