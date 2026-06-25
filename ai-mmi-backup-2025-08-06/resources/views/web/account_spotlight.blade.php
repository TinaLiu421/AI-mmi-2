@extends('web.common')
@section('content')
<?php
$_show_current_member         = $_page_data['show_current_member'];
$_show_current_member_details = $_page_data['current_member_details'] ?? [];
$_show_current_member_agent   = $_page_data['current_member_agent']   ?? [];
$_institution_profile         = $_page_data['institution_profile']    ?? null;
$_is_edu_institution          = !empty($_institution_profile)
    || ((int)($_show_current_member['type'] ?? 0) === 3
        && (int)($_show_current_member_details['institution_type'] ?? 0) === 2);

$_my_queue        = $_page_data['my_queue']        ?? [];
$_available_posts = $_page_data['available_posts']  ?? [];
$_total_my_posts  = (int)($_page_data['total_my_posts'] ?? 0);
$_active_count    = (int)($_page_data['active_count']    ?? 0);
$_free_slots      = (int)($_page_data['free_slots']      ?? 0);
$_schedule_preview= $_page_data['schedule_preview'] ?? [];
$_price_cents     = (int)($_page_data['slot_price_cents'] ?? 10000);
$_payment_status  = $_page_data['payment_status']   ?? '';
$_is_readonly     = !empty($_page_data['is_readonly']);
$_directly_featured = $_page_data['directly_featured'] ?? [];
$_is_spotlight_mgr  = !empty($_page_data['is_spotlight_mgr']);

$_uid_qs = (!empty($_page_get_data['uid'])) ? '?uid='.$_page_get_data['uid'] : '';
?>
<div class="inner-panel full">

    {{-- ── Banner / avatar ──────────────────────────────────────────────── --}}
    <?php if(!empty($_show_current_member['coverphoto']) && file_exists('upload/member_coverphoto/'.$_show_current_member['coverphoto'])): ?>
    <div class="banner" style="background-image:url('<?php echo 'upload/member_coverphoto/'.$_show_current_member['coverphoto']; ?>')"></div>
    <?php else: ?>
    <div class="banner" style="display:none;"></div>
    <?php endif; ?>

    <div class="basic">
        <div class="photo">
            <img src="asset/image/icon-member.png" alt="icon-member"/>
            <?php if(file_exists('upload/member_avatar/'.$_show_current_member['avatar'])): ?>
            <div class="avatar" style="background-image:url('<?php echo 'upload/member_avatar/'.$_show_current_member['avatar']; ?>')"></div>
            <?php else: ?>
            <div class="avatar" style="background-image:url('<?php echo 'upload/member_logo/'.$_show_current_member['avatar']; ?>');background-size:contain;background-color:#fff;<?php if($_is_edu_institution) echo 'border-radius:8px;'; ?>"></div>
            <?php endif; ?>
        </div>
        <div class="name">
            <div class="alias">
                <div class="readonly"><span><?php echo $_show_current_member['alias_name']; ?></span></div>
            </div>
            {{-- followers hidden --}}
        </div>
        <div class="clearboth"></div>

        {{-- ── Tab nav ───────────────────────────────────────────────────── --}}
        <div class="tab">
            <a class="posts" href="<?php echo $_page_base_url.'/account/posts'.$_uid_qs; ?>"><?php echo $_page_lang['tab_posts']; ?></a>
            <a class="about" href="<?php echo $_page_base_url.'/account/profile'.$_uid_qs; ?>"><?php echo $_page_lang['tab_about']; ?></a>
            <?php if($_is_edu_institution): ?>
            <a class="edu-tab" href="<?php echo $_page_base_url.'/account/students_matched'.$_uid_qs; ?>">Students Matched</a>
            <a class="edu-tab" href="<?php echo $_page_base_url.'/account/students_applied'.$_uid_qs; ?>">Students Applied</a>
            <a class="edu-tab" href="<?php echo $_page_base_url.'/account/students_accepted'.$_uid_qs; ?>">Students Accepted</a>
            <?php endif; ?>
            <?php if(empty($_is_readonly)): ?>
            <a class="spotlight selected"><?php echo '⭐ Spotlight'; ?></a>
            <?php endif; ?>
        </div>
    </div>

    {{-- ── Main content ──────────────────────────────────────────────────── --}}
    <div class="tab-details blank spotlight-page">

        {{-- Back button --}}
        <a class="spotlight-back-btn" href="<?php echo $_page_base_url.'/account/posts'.$_uid_qs; ?>">← Back to Posts</a>

        {{-- Payment flash banner --}}
        <?php if($_payment_status === 'success'): ?>
        <div class="spotlight-flash spotlight-flash--success">
            ✅ Payment received! Your post(s) are now live in the Spotlight section.
        </div>
        <?php elseif($_payment_status === 'cancel'): ?>
        <div class="spotlight-flash spotlight-flash--cancel">
            ⚠️ Payment was cancelled. Your posts have not been spotlighted.
        </div>
        <?php elseif(session('error')): ?>
        <div class="spotlight-flash spotlight-flash--cancel">
            ⚠️ <?php echo session('error'); ?>
        </div>
        <?php elseif(session('info')): ?>
        <div class="spotlight-flash spotlight-flash--info">
            ℹ️ <?php echo session('info'); ?>
        </div>
        <?php endif; ?>

        {{-- ── Admin Recovery: directly-featured posts (no queue entry) ────── --}}
        <?php if ($_is_spotlight_mgr && !empty($_directly_featured)): ?>
        <div class="spotlight-section" style="background:#fff8e1;border:1px solid #f5c842;border-radius:10px;padding:18px 22px;margin-bottom:16px;">
            <h3 class="spotlight-section-title" style="color:#7a5200;">⚠️ Live in Spotlight (Direct)</h3>
            <p class="spotlight-section-desc" style="color:#7a5200;">These posts are live in the spotlight carousel. Click <strong>Remove</strong> to unfeature them.</p>
            <div class="spotlight-queue-list">
                <?php foreach ($_directly_featured as $_df): ?>
                <div class="spotlight-queue-item" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 14px;background:#fff;border-radius:8px;margin-bottom:8px;border:1px solid #e0c85a;">
                    <div>
                        <div style="font-weight:600;font-size:0.92rem;"><?php echo htmlspecialchars(mb_substr($_df['title'], 0, 70), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div style="font-size:0.8rem;color:#888;margin-top:2px;">Live until: <?php echo date('d M Y H:i', strtotime($_df['featured_until'])); ?></div>
                    </div>
                    <button class="spotlight-direct-remove-btn"
                            data-posts-id="<?php echo (int)$_df['id']; ?>"
                            style="flex-shrink:0;padding:6px 14px;background:#ef5350;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:0.82rem;font-weight:700;">
                        ✕ Remove
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        {{-- ── Section 1: My current spotlight entries ────────────────────── --}}
        <div class="spotlight-section">
            <h3 class="spotlight-section-title">⭐ My Spotlight</h3>
            <p class="spotlight-section-desc">
                Each spotlight costs <strong>$100 / week</strong> per post. Your post goes <strong>live immediately</strong> after payment.
            </p>

            <?php if(empty($_my_queue)): ?>
            <p class="spotlight-empty">You have no posts in spotlight or queue yet.</p>
            <?php else: ?>
            <div class="spotlight-queue-list">
                <?php foreach($_my_queue as $_sq_item): ?>
                <?php
                $sq_status = $_sq_item['status'] ?? 'pending_payment';
                $sq_title  = $_sq_item['title'] ?? 'Untitled';
                $sq_end    = $_sq_item['scheduled_end'] ?? null;
                $sq_start  = $_sq_item['scheduled_start'] ?? null;
                ?>
                <div class="spotlight-queue-card spotlight-queue-card--<?php echo htmlspecialchars($sq_status); ?>">
                    <div class="spotlight-queue-card__title"><?php echo htmlspecialchars($sq_title); ?></div>
                    <div class="spotlight-queue-card__meta">
                        <?php if($sq_status === 'active'): ?>
                        <span class="sq-badge sq-badge--active">🟢 Live</span>
                        <?php if($sq_end): ?>
                        <span class="sq-expires">Expires <?php echo date('d M Y H:i', strtotime($sq_end)); ?></span>
                        <?php endif; ?>
                        <?php elseif($sq_status === 'queued'): ?>
                        <span class="sq-badge sq-badge--queued">🕐 Queued</span>
                        <?php if($sq_start): ?>
                        <span class="sq-expires">Predicted start: <?php echo date('d M Y', strtotime($sq_start)); ?></span>
                        <?php else: ?>
                        <span class="sq-expires">Goes live immediately after payment is confirmed</span>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="sq-badge sq-badge--pending">💳 Awaiting payment confirmation</span>
                        <?php endif; ?>
                    </div>
                    <div class="spotlight-queue-card__actions">
                        <a class="spotlight-queue-card__link"
                           href="<?php echo $_page_base_url.'/posts/details/'.(int)$_sq_item['posts_id']; ?>">
                            View post →
                        </a>
                        <?php if($sq_status === 'pending_payment'): ?>
                        {{-- Retry payment --}}
                        <form method="POST"
                              action="<?php echo $_page_base_url.'/account/spotlight_retry'; ?>"
                              style="display:inline;">
                            @csrf
                            <input type="hidden" name="sq_id" value="<?php echo (int)$_sq_item['id']; ?>">
                            <button type="submit" class="sq-action-btn sq-action-btn--pay">💳 Pay Now</button>
                        </form>
                        {{-- Cancel entry --}}
                        <form method="POST"
                              action="<?php echo $_page_base_url.'/account/spotlight_cancel'; ?>"
                              style="display:inline;"
                              onsubmit="return confirm('Cancel this spotlight entry? The post will be available to select again.');">
                            @csrf
                            <input type="hidden" name="sq_id" value="<?php echo (int)$_sq_item['id']; ?>">
                            <button type="submit" class="sq-action-btn sq-action-btn--cancel">✕ Cancel</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>



        <?php if(!$_is_readonly && !empty($_available_posts)): ?>
        {{-- ── Section 3: Purchase basket ──────────────────────────────────── --}}
        <div class="spotlight-section spotlight-section--basket">
            <h3 class="spotlight-section-title">🛒 Add to Spotlight</h3>
            <p class="spotlight-section-desc">Select the posts you want spotlighted. Each costs <strong>$100 / week</strong>. All activate <strong>immediately</strong> after payment.</p>

            <form id="spotlight-checkout-form"
                  method="POST"
                  action="<?php echo $_page_base_url.'/account/spotlight_checkout'; ?>">
                @csrf

                <div class="spotlight-post-picker">
                    <?php foreach($_available_posts as $_ap): ?>
                    <?php $ap_id = (int)$_ap['id']; ?>
                    <label class="spotlight-post-item" data-id="<?php echo $ap_id; ?>">
                        <input type="checkbox"
                               name="post_ids[]"
                               value="<?php echo $ap_id; ?>"
                               class="spotlight-post-checkbox">
                        <div class="spotlight-post-item__body">
                            <span class="spotlight-post-item__title">
                                <?php echo htmlspecialchars($_ap['title'] ?? 'Untitled'); ?>
                            </span>
                            <span class="spotlight-post-item__type">
                                <?php echo (int)($_ap['category_type'] ?? 0) === 1 ? 'News' : 'Event'; ?>
                            </span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                {{-- Schedule preview table --}}
                <div class="spotlight-preview" id="spotlight-preview" style="display:none;">
                    <h4>📅 Predicted Schedule</h4>
                    <table class="spotlight-preview-table">
                        <thead>
                            <tr><th>#</th><th>Goes Live</th><th>Expires</th></tr>
                        </thead>
                        <tbody id="spotlight-preview-body"></tbody>
                    </table>
                </div>

                {{-- Running total --}}
                <div class="spotlight-total">
                    <span id="spotlight-total-label">Select posts above</span>
                </div>

                <div class="spotlight-submit-wrap">
                    <button type="submit" id="spotlight-pay-btn" class="spotlight-pay-btn" disabled>
                        💳 Pay with Stripe
                    </button>
                </div>
            </form>
        </div>

        {{-- ── Schedule preview data (JSON) for JS ──────────────────────── --}}
        <script>
        var _spotlightSchedule = <?php echo json_encode($_schedule_preview, JSON_UNESCAPED_UNICODE); ?>;
        var _spotlightPriceCents = <?php echo (int)$_price_cents; ?>;
        </script>

        <?php elseif(!$_is_readonly && empty($_available_posts) && $_total_my_posts === 0): ?>
        {{-- No posts published at all --}}
        <div class="spotlight-section">
            <div class="spotlight-no-posts-cta">
                <div class="spotlight-no-posts-icon">📝</div>
                <h4>No published posts yet</h4>
                <p>To spotlight a post on the home page, you need to publish one first.</p>
                <a class="spotlight-pay-btn" href="<?php echo $_page_base_url.'/account/posts'; ?>">Write Your First Post →</a>
            </div>
        </div>
        <?php elseif(!$_is_readonly && empty($_available_posts)): ?>
        {{-- Posts exist but all are already in spotlight/queue --}}
        <div class="spotlight-section">
            <p class="spotlight-empty">All your published posts are already in spotlight or queue.
                <a href="<?php echo $_page_base_url.'/account/posts'; ?>">Publish a new post</a> to add more.
            </p>
        </div>
        <?php endif; ?>

    </div><!-- .tab-details -->
</div><!-- .inner-panel -->

<script>
(function () {
    var checkboxes  = document.querySelectorAll('.spotlight-post-checkbox');
    var preview     = document.getElementById('spotlight-preview');
    var previewBody = document.getElementById('spotlight-preview-body');
    var totalLabel  = document.getElementById('spotlight-total-label');
    var payBtn      = document.getElementById('spotlight-pay-btn');
    var form        = document.getElementById('spotlight-checkout-form');
    var schedule    = (typeof _spotlightSchedule !== 'undefined') ? _spotlightSchedule : [];
    var priceCents  = (typeof _spotlightPriceCents !== 'undefined') ? _spotlightPriceCents : 10000;

    function fmtDate(ts) {
        if (!ts) return '--';
        var d = new Date(ts * 1000);
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function update() {
        var checked = [];
        checkboxes.forEach(function (cb) {
            if (cb.checked) checked.push(cb);
        });

        var qty = checked.length;

        if (qty === 0) {
            if (preview)     preview.style.display = 'none';
            if (totalLabel)  totalLabel.textContent = 'Select posts above';
            if (payBtn)      payBtn.disabled = true;
            return;
        }

        // Update total label
        var total = (qty * priceCents / 100).toFixed(0);
        if (totalLabel) totalLabel.textContent = qty + ' post' + (qty > 1 ? 's' : '') + ' × $100 = $' + total;
        if (payBtn)     payBtn.disabled = false;

        // Show schedule preview
        if (preview && previewBody && schedule.length > 0) {
            preview.style.display = '';
            var html = '';
            for (var i = 0; i < qty; i++) {
                var slot = schedule[i] || null;
                html += '<tr>'
                    + '<td>' + (i + 1) + '</td>'
                    + '<td>' + (slot ? fmtDate(slot.start) : 'TBD') + '</td>'
                    + '<td>' + (slot ? fmtDate(slot.end)   : 'TBD') + '</td>'
                    + '</tr>';
            }
            previewBody.innerHTML = html;
        }
    }

    if (checkboxes.length > 0) {
        checkboxes.forEach(function (cb) {
            cb.addEventListener('change', update);
        });
        // Also style the label wrappers
        document.querySelectorAll('.spotlight-post-item').forEach(function (lbl) {
            lbl.addEventListener('click', function () {
                setTimeout(function () {
                    document.querySelectorAll('.spotlight-post-item').forEach(function (l) {
                        var cb = l.querySelector('.spotlight-post-checkbox');
                        l.classList.toggle('spotlight-post-item--selected', cb && cb.checked);
                    });
                }, 0);
            });
        });
    }

    // Confirm on submit
    if (form) {
        form.addEventListener('submit', function (e) {
            var checked = document.querySelectorAll('.spotlight-post-checkbox:checked').length;
            if (checked === 0) { e.preventDefault(); return; }
            if (!confirm('Spotlight ' + checked + ' post' + (checked > 1 ? 's' : '') + ' for $' + checked * 100 + '?\n\nYou will be taken to a secure Stripe payment page.')) {
                e.preventDefault();
            }
        });
    }
})();
</script>

<?php if ($_is_spotlight_mgr): ?>
<script>
(function() {
    // Handle direct-remove buttons (admin recovery section)
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.spotlight-direct-remove-btn');
        if (!btn) return;
        e.preventDefault();
        var postsId = parseInt(btn.dataset.postsId, 10);
        if (!confirm('Remove this post from the Spotlight section?')) return;
        btn.disabled = true;
        btn.textContent = '…';
        fetch('/spotlight/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (typeof _token !== 'undefined') ? _token
                    : (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
            },
            body: JSON.stringify({ posts_id: postsId })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 200) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Something went wrong.'));
                btn.disabled = false;
                btn.textContent = '✕ Remove';
            }
        })
        .catch(function(err) {
            alert('Request failed: ' + err.message);
            btn.disabled = false;
            btn.textContent = '✕ Remove';
        });
    });
})();
</script>
<?php endif; ?>

@endsection
