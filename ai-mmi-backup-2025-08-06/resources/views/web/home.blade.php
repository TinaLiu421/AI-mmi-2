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

<?php
$_featured_posts   = $_page_data['featured_posts']   ?? [];
$_member_has_featured = $_page_data['member_has_featured'] ?? false;
$_is_spotlight_manager = !empty($_page_data['is_spotlight_manager']);
$_spotlight_queue_overview = $_page_data['spotlight_queue_overview'] ?? [];
$_member_type      = (int)($_current_member['type'] ?? 0);
// Show upgrade CTA to logged-in agents/institutions who haven't featured a post yet
$_show_upgrade_cta = !empty($_current_member) && in_array($_member_type, [2, 3]) && !$_member_has_featured && !$_is_spotlight_manager;
?>

<?php if (!empty($_featured_posts)): ?>
<!-- Featured Posts Section -->
<div class="home-featured-section">
    <div class="home-featured-inner">
        <div class="home-featured-header">
            <div class="home-featured-badge"><i class="fa fa-star"></i> Featured</div>
            <h2 class="home-featured-title">Spotlight from Our Agents</h2>
            <p class="home-featured-subtitle">Expert insights and updates from verified education &amp; migration professionals.</p>
        </div>
        <div class="home-featured-cards">
            <?php foreach ($_featured_posts as $_fp): ?>
            <?php if ($_is_spotlight_manager): ?>
            <div class="home-featured-card home-featured-card--manage">
            <?php else: ?>
            <a class="home-featured-card" href="<?php echo htmlspecialchars($_fp['url'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>
                <div class="home-featured-card-thumb">
                    <?php if (!empty($_fp['thumbnail'])): ?>
                    <img src="<?php echo htmlspecialchars($_fp['thumbnail'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($_fp['title'], ENT_QUOTES, 'UTF-8'); ?>"/>
                    <?php else: ?>
                    <div class="home-featured-card-thumb-placeholder"></div>
                    <?php endif; ?>
                    <span class="home-featured-card-badge"><i class="fa fa-star"></i> Featured</span>
                    <?php if ($_is_spotlight_manager): ?>
                    <button class="spotlight-remove-btn" data-posts-id="<?php echo (int)$_fp['id']; ?>" title="Remove from Spotlight"><i class="fa fa-times"></i> Remove</button>
                    <?php endif; ?>
                </div>
                <div class="home-featured-card-body">
                    <?php if ($_is_spotlight_manager): ?>
                    <a href="<?php echo htmlspecialchars($_fp['url'], ENT_QUOTES, 'UTF-8'); ?>" class="home-featured-card-title-link">
                    <h3 class="home-featured-card-title"><?php echo htmlspecialchars($_fp['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    </a>
                    <?php else: ?>
                    <h3 class="home-featured-card-title"><?php echo htmlspecialchars($_fp['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <?php endif; ?>
                    <p class="home-featured-card-excerpt"><?php echo htmlspecialchars($_fp['excerpt'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="home-featured-card-meta">
                        <span class="home-featured-card-author"><i class="fa fa-user-circle"></i> <?php echo htmlspecialchars($_fp['alias_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="home-featured-card-date"><?php echo date('d M Y', strtotime($_fp['created_at'])); ?></span>
                    </div>
                </div>
            <?php if ($_is_spotlight_manager): ?>
            </div>
            <?php else: ?>
            </a>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?php if ($_show_upgrade_cta): ?>
        <div class="home-featured-upgrade-cta">
            <div class="home-featured-upgrade-cta-text">
                <i class="fa fa-bolt"></i>
                <span>Want your post featured here? <strong>$100 / week per post</strong> — up to 3 spotlight slots available. Join the waitlist if full.</span>
            </div>
            <a class="home-featured-upgrade-btn" href="<?php echo $_page_base_url; ?>/account/spotlight">Spotlight My Post &rarr;</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($_is_spotlight_manager && !empty($_spotlight_queue_overview)): ?>
<!-- Admin: Spotlight Queue Overview -->
<div class="home-featured-section" style="background:#fffdf4;border-top:2px solid #e2c96a;">
    <div class="home-featured-inner">
        <div class="home-featured-header">
            <div class="home-featured-badge" style="background:#c9a227;">⚙️ Admin</div>
            <h2 class="home-featured-title" style="font-size:18px;">Spotlight Queue — All Active &amp; Pending</h2>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f5f0e8;text-align:left;">
                    <th style="padding:8px 10px;">ID</th>
                    <th style="padding:8px 10px;">Member</th>
                    <th style="padding:8px 10px;">Post</th>
                    <th style="padding:8px 10px;">Status</th>
                    <th style="padding:8px 10px;">Paid At</th>
                    <th style="padding:8px 10px;">Goes Live</th>
                    <th style="padding:8px 10px;">Expires</th>
                    <th style="padding:8px 10px;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($_spotlight_queue_overview as $_sq): ?>
            <?php
            $adminBg = $_sq['status'] === 'active' ? '#e8f5e9' : ($_sq['status'] === 'queued' ? '#fff8e1' : '#fce4ec');
            $adminBadge = $_sq['status'] === 'active' ? '🟢 Active' : ($_sq['status'] === 'queued' ? '🕐 Queued' : '💳 Pending');
            ?>
            <tr style="background:<?php echo $adminBg; ?>;border-bottom:1px solid #ddd;">
                <td style="padding:7px 10px;"><?php echo (int)$_sq['id']; ?></td>
                <td style="padding:7px 10px;"><?php echo htmlspecialchars($_sq['member_name'].' ('.$_sq['member_email'].')'); ?></td>
                <td style="padding:7px 10px;"><?php echo htmlspecialchars(mb_substr($_sq['post_title'] ?? 'Untitled', 0, 40)); ?></td>
                <td style="padding:7px 10px;"><?php echo $adminBadge; ?></td>
                <td style="padding:7px 10px;"><?php echo $_sq['paid_at'] ? date('d M Y H:i', strtotime($_sq['paid_at'])) : '—'; ?></td>
                <td style="padding:7px 10px;"><?php echo $_sq['scheduled_start'] ? date('d M Y', strtotime($_sq['scheduled_start'])) : '—'; ?></td>
                <td style="padding:7px 10px;"><?php echo $_sq['scheduled_end'] ? date('d M Y H:i', strtotime($_sq['scheduled_end'])) : '—'; ?></td>
                <td style="padding:7px 10px;">
                    <button class="sq-admin-cancel-btn" data-sq-id="<?php echo (int)$_sq['id']; ?>" style="font-size:11px;padding:3px 8px;border:1px solid #ef9a9a;color:#c62828;background:transparent;border-radius:4px;cursor:pointer;">✕ Cancel</button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php elseif ($_is_spotlight_manager): ?>
<!-- Admin: no active queue -->
<div class="home-featured-section" style="background:#fffdf4;border-top:2px solid #e2c96a;display:none;">
</div>
<?php endif; ?>

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
                <?php if ($_is_spotlight_manager): ?>
                <button class="spotlight-add-btn" data-posts-id="<?php echo (int)$news['id']; ?>" title="Add to Spotlight"><i class="fa fa-star"></i> Spotlight</button>
                <?php endif; ?>
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
                <?php if ($_is_spotlight_manager): ?>
                <button class="spotlight-add-btn" data-posts-id="<?php echo (int)$events['id']; ?>" title="Add to Spotlight"><i class="fa fa-star"></i> Spotlight</button>
                <?php endif; ?>
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

<?php if ($_is_spotlight_manager): ?>
<script>
(function() {
    function spotlightToggle(postsId, btn) {
        btn.disabled = true;
        btn.style.opacity = '0.6';
        fetch('/spotlight/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (typeof _token !== 'undefined') ? _token : (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
            },
            body: JSON.stringify({ posts_id: postsId })
        })
        .then(function(r) {
            if (!r.ok && r.status !== 200) {
                return r.text().then(function(txt) {
                    throw new Error('Server error ' + r.status);
                });
            }
            return r.json();
        })
        .then(function(data) {
            if (data.status === 200) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Something went wrong.'));
                btn.disabled = false;
                btn.style.opacity = '1';
            }
        })
        .catch(function(err) {
            alert('Request failed: ' + (err.message || 'Please try again.'));
            btn.disabled = false;
            btn.style.opacity = '1';
        });
    }

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.spotlight-add-btn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            var postsId = parseInt(btn.dataset.postsId, 10);
            if (confirm('Add this post to the Spotlight section on the home page?')) {
                spotlightToggle(postsId, btn);
            }
        }
        var removeBtn = e.target.closest('.spotlight-remove-btn');
        if (removeBtn) {
            e.preventDefault();
            e.stopPropagation();
            var postsId = parseInt(removeBtn.dataset.postsId, 10);
            if (confirm('Remove this post from the Spotlight section?')) {
                spotlightToggle(postsId, removeBtn);
            }
        }
        var adminCancelBtn = e.target.closest('.sq-admin-cancel-btn');
        if (adminCancelBtn) {
            e.preventDefault();
            e.stopPropagation();
            var sqId = parseInt(adminCancelBtn.dataset.sqId, 10);
            if (confirm('Cancel this spotlight queue entry? If active, the post will be removed from spotlight and the next in queue will activate.')) {
                adminCancelBtn.disabled = true;
                fetch('/spotlight/admin_cancel', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': (typeof _token !== 'undefined') ? _token : (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
                    },
                    body: JSON.stringify({ sq_id: sqId })
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.status === 200) {
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Something went wrong.'));
                        adminCancelBtn.disabled = false;
                    }
                })
                .catch(function(err) {
                    alert('Request failed: ' + err.message);
                    adminCancelBtn.disabled = false;
                });
            }
        }
    });
})();
</script>
<?php endif; ?>
@endsection