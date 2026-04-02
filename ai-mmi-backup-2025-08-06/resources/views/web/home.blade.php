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
        <h1 class="banner-title">Your AI-Powered Study &amp; Migration Guide</h1>
        <p class="banner-sub">Get instant guidance in your language on visas, study, scholarships, and more — with access to qualified experts when you need them.</p>
        <div class="banner-cta-row">
            <a class="banner-cta-btn primary do-toapply" data-sector="migration" data-action-url="<?php echo $_page_base_url.'/agent_chat'; ?>" href="javascript:void(0);">Talk to AI-mmi</a>
            <?php if(empty($_current_member) || (int)($_current_member['type'] ?? 0) !== 3 || strpos(mb_strtolower(trim($_current_member['email'] ?? ''), 'UTF-8'), '@wealthskey.com') !== false): ?>
            <a class="banner-cta-btn secondary" id="banner-talk-agent-btn" href="javascript:void(0);">Talk to Registered Agent</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="country"></div>
</div>

<!-- Service Frames -->
<div class="home-service-frames">
    <a class="home-service-frame" href="<?php echo $_page_base_url.'/study'; ?>">
        <div class="home-service-frame-thumb">
            <img src="/asset/image/service-study.jpg" alt="Study Applications"/>
        </div>
        <div class="home-service-frame-text">
            <span class="home-service-frame-title">Study Applications</span>
            <span class="home-service-frame-divider"></span>
            <span class="home-service-frame-tagline">Don't search for universities &mdash; Let them find you</span>
        </div>
        <span class="home-service-frame-arrow">&#8594;</span>
    </a>
    <a class="home-service-frame" href="<?php echo $_page_base_url.'/migration'; ?>">
        <div class="home-service-frame-thumb">
            <img src="/asset/image/service-migration.jpg" alt="Migration Applications"/>
        </div>
        <div class="home-service-frame-text">
            <span class="home-service-frame-title">Migration Applications</span>
            <span class="home-service-frame-divider"></span>
            <span class="home-service-frame-tagline">Expert migration support, without the high fees</span>
        </div>
        <span class="home-service-frame-arrow">&#8594;</span>
    </a>
    <a class="home-service-frame" href="<?php echo $_page_base_url.'/service_provider_info'; ?>">
        <div class="home-service-frame-thumb">
            <img src="/asset/image/service-institution.jpg" alt="Institution Hub"/>
        </div>
        <div class="home-service-frame-text">
            <span class="home-service-frame-title">Institution Hub</span>
            <span class="home-service-frame-divider"></span>
            <span class="home-service-frame-tagline">Connect with global qualified applicants, faster!</span>
        </div>
        <span class="home-service-frame-arrow">&#8594;</span>
    </a>
</div>

<!-- Why Choose AI-mmi Section -->
<div class="home-why-section">
    <div class="home-why-inner">
        <div class="home-why-header">
            <h2 class="home-why-title">Why Choose <span>AI-mmi?</span></h2>
            <p class="home-why-subtitle">Unlike generic AI tools or traditional agents, we deliver personalized action plans, accurate answers on policies, verified pathways, and direct connections — all in one platform.</p>
        </div>
        <div class="home-why-cards">
            <div class="home-why-card">
                <div class="home-why-card-icon home-why-card-icon--logo">
                    <img src="/asset/image/logo-mmi.png" alt="AI-mmi" class="home-why-card-logo-img"/>
                </div>
                <h3 class="home-why-card-title">AI + Agent Model</h3>
                <p class="home-why-card-desc">AI automation supported by human experts when you need them most.</p>
            </div>
            <div class="home-why-card">
                <div class="home-why-card-icon">
                    <i class="fa fa-list-alt"></i>
                </div>
                <h3 class="home-why-card-title">Personalized Action Plans</h3>
                <p class="home-why-card-desc">Step-by-step plans tailored to your profile, goals, and timelines.</p>
            </div>
            <div class="home-why-card">
                <div class="home-why-card-icon">
                    <i class="fa fa-magic"></i>
                </div>
                <h3 class="home-why-card-title">Smart Matching</h3>
                <p class="home-why-card-desc">Match with suitable colleges or visa pathways using intelligent matching.</p>
            </div>
            <div class="home-why-card">
                <div class="home-why-card-icon">
                    <i class="fa fa-paper-plane"></i>
                </div>
                <h3 class="home-why-card-title">Opportunities Come to You</h3>
                <p class="home-why-card-desc">Matched universities and colleges can reach out to you directly.</p>
            </div>
        </div>
    </div>
</div>

<div class="inner-panel">
    @if(!empty($_current_member) && !empty($_page_data['show_agent_home_layout']))
    <div class="home-chat-notify" id="home-chat-notify" data-enabled="1">
        <div class="home-chat-notify-head">
            <div class="home-chat-notify-title">Chat Notifications</div>
            <a class="home-chat-notify-link" href="/{{ $_current_lang_code }}/agent_chat/chat">Open chat</a>
            <a class="home-chat-notify-link" href="/{{ $_current_lang_code }}/agent_verification" style="margin-left:10px;">Member Verification</a>
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