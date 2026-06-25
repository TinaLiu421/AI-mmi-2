@extends('web.common')
@section('title', 'How Credits Work')

@push('css')
<style>
/* ── Credit Guide Page ── */
body.page-token_guide main.page-body { min-height: 0 !important; }
body.page-token_guide main.page-body .page-content { margin-right: 450px !important; min-height: 0 !important; }
body.page-token_guide main.page-body .info-area {
    float: none !important;
    width: 100% !important;
    background: #f0f4ff !important;
    background-image: none !important;
    min-height: auto !important;
    align-self: flex-start !important;
}
body.page-token_guide main.page-body .info-area::before { display: none !important; }

.tg-page {
    font-family: inherit;
}

/* ── Hero ── */
.tg-hero {
    background: linear-gradient(135deg, #001540 0%, #002b8a 40%, #1a3fcc 75%, #4f46e5 100%);
    padding: 72px 24px 68px;
    text-align: center;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.tg-hero::before {
    content: '';
    position: absolute; top: -80px; right: -80px;
    width: 340px; height: 340px; border-radius: 50%;
    background: rgba(255,255,255,0.05);
    pointer-events: none;
}
.tg-hero::after {
    content: '';
    position: absolute; bottom: -60px; left: -60px;
    width: 260px; height: 260px; border-radius: 50%;
    background: rgba(99,102,241,0.18);
    pointer-events: none;
}
.tg-hero-badge {
    display: inline-flex; align-items: center; gap: 7px;
    background: rgba(255,255,255,0.13);
    border: 1px solid rgba(255,255,255,0.28);
    color: #c7d8ff;
    font-size: 0.8rem; font-weight: 600; letter-spacing: 0.6px;
    text-transform: uppercase;
    padding: 5px 14px;
    border-radius: 20px;
    margin-bottom: 22px;
}
.tg-hero-icon-wrap {
    width: 90px; height: 90px;
    background: rgba(255,255,255,0.14);
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 24px;
    font-size: 2.4rem;
    color: #fff;
    position: relative; z-index: 1;
}
.tg-hero-title {
    font-size: 3.6rem; font-weight: 800;
    letter-spacing: -1.2px; line-height: 1.1;
    margin: 0 0 18px;
    position: relative; z-index: 1;
}
.tg-hero-tagline {
    font-size: 1.25rem; opacity: 0.88;
    line-height: 1.7; margin: 0 auto 40px;
    max-width: 620px;
    position: relative; z-index: 1;
}
.tg-hero-actions {
    display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;
    position: relative; z-index: 1;
}
.tg-hero-cta {
    display: inline-flex; align-items: center; gap: 8px;
    background: #fff; color: #002065;
    font-weight: 700; font-size: 0.97rem;
    padding: 13px 30px; border-radius: 10px;
    text-decoration: none;
    box-shadow: 0 6px 24px rgba(0,0,0,0.22);
    transition: transform 0.15s, box-shadow 0.15s;
}
.tg-hero-cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 32px rgba(0,0,0,0.28);
    text-decoration: none; color: #002065;
}
.tg-hero-wallet {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,0.15);
    border: 1.5px solid rgba(255,255,255,0.45);
    color: #fff; font-weight: 600; font-size: 0.97rem;
    padding: 13px 28px; border-radius: 10px;
    text-decoration: none; transition: background 0.15s;
}
.tg-hero-wallet:hover { background: rgba(255,255,255,0.24); text-decoration: none; color: #fff; }
/* Hero stats strip */
.tg-stats-strip {
    display: flex; justify-content: center; gap: 0;
    background: rgba(255,255,255,0.09);
    border-top: 1px solid rgba(255,255,255,0.1);
    margin-top: 48px; padding: 0;
    position: relative; z-index: 1;
}
.tg-stat {
    flex: 1; max-width: 180px;
    padding: 18px 12px;
    text-align: center;
    border-right: 1px solid rgba(255,255,255,0.1);
}
.tg-stat:last-child { border-right: none; }
.tg-stat-num { font-size: 2.2rem; font-weight: 800; color: #fff; line-height: 1; }
.tg-stat-lbl { font-size: 0.82rem; color: rgba(255,255,255,0.7); margin-top: 5px; letter-spacing: 0.3px; }

/* ── Body ── */
.tg-body {
    max-width: 1100px;
    margin: 0 auto;
    padding: 48px 24px 72px;
}

/* ── Section card ── */
.tg-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #dde6f8;
    box-shadow: 0 2px 16px rgba(0,32,101,0.06);
    padding: 32px 36px;
    margin-bottom: 28px;
}
.tg-card-head {
    display: flex; align-items: center; gap: 14px;
    margin-bottom: 20px;
}
.tg-card-icon {
    width: 52px; height: 52px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0;
}
.tg-card-title {
    font-size: 1.45rem; font-weight: 800; color: #001e5e;
    margin: 0; letter-spacing: -0.3px;
}
.tg-card-sub {
    font-size: 0.95rem; color: #7a8aac; margin-top: 3px;
}

/* ── What section ── */
.tg-what-text {
    color: #334; font-size: 1.07rem; line-height: 1.85; margin: 0;
}
/* ── Subsection heading inside cards ── */
.tg-sub-heading {
    font-size: 1.08rem; font-weight: 800; color: #001e5e;
    margin: 0 0 10px;
    padding-left: 12px;
    border-left: 3px solid #0055d4;
    line-height: 1.2;
}
/* ── Subsection list ── */
.tg-sub-list {
    margin: 0 0 22px; padding-left: 24px;
    color: #334; font-size: 1.02rem; line-height: 2;
}

/* ── Earn section ── */
.tg-earn-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-top: 4px;
}
.tg-earn-card {
    background: linear-gradient(160deg, #eef5ff 0%, #dde8ff 100%);
    border: 1.5px solid #b8d0ff;
    border-radius: 16px;
    padding: 28px 12px 22px;
    text-align: center;
    transition: transform 0.18s, box-shadow 0.18s;
}
.tg-earn-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 28px rgba(0,86,212,0.17);
}
.tg-earn-num {
    font-size: 2.6rem; font-weight: 900; color: #0055d4; line-height: 1;
    margin-bottom: 10px; letter-spacing: -1px;
}
.tg-earn-icon {
    width: 44px; height: 44px; border-radius: 50%;
    background: #0055d4; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem; margin: 0 auto 12px;
}
.tg-earn-lbl {
    font-size: 1rem; color: #223; font-weight: 700; line-height: 1.3;
}

/* ── Spend section ── */
.tg-spend-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-top: 4px;
}
.tg-spend-card {
    background: #fff8f0;
    border: 1.5px solid #fddbb8;
    border-radius: 14px;
    padding: 22px 20px;
    display: flex; flex-direction: column; gap: 12px;
}
.tg-spend-top {
    display: flex; align-items: center; gap: 12px;
}
.tg-spend-ico {
    width: 42px; height: 42px; border-radius: 11px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; color: #fff; flex-shrink: 0;
}
.tg-spend-feature { font-weight: 700; color: #1a1a2e; font-size: 0.97rem; }
.tg-spend-desc { color: #666; font-size: 0.84rem; line-height: 1.4; margin-top: 2px; }
.tg-spend-pill {
    display: inline-flex; align-items: center; gap: 6px;
    background: #fff; border: 1.5px solid #f0bb80;
    border-radius: 20px; padding: 5px 12px;
    font-size: 0.82rem; font-weight: 700; color: #c45c00;
    align-self: flex-start;
}

/* ── Guest CTA ── */
.tg-guest-cta {
    background: linear-gradient(135deg, #047857 0%, #065f46 55%, #064e3b 100%);
    border-radius: 16px;
    padding: 44px 36px;
    text-align: center;
    color: #fff;
    margin-bottom: 28px;
    position: relative; overflow: hidden;
}
.tg-guest-cta::before {
    content: ''; position: absolute;
    top: -50px; right: -50px;
    width: 220px; height: 220px; border-radius: 50%;
    background: rgba(255,255,255,0.06);
}
.tg-guest-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);
    padding: 4px 14px; border-radius: 20px;
    font-size: 0.78rem; font-weight: 600; letter-spacing: 0.5px;
    color: #a7f3d0; text-transform: uppercase;
    margin-bottom: 16px; position: relative; z-index: 1;
}
.tg-guest-cta h3 {
    font-size: 1.7rem; font-weight: 800;
    margin: 0 0 10px; position: relative; z-index: 1;
}
.tg-guest-cta p {
    opacity: 0.88; margin: 0 0 28px; font-size: 1rem; line-height: 1.55;
    position: relative; z-index: 1;
}
.tg-guest-btns {
    display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;
    position: relative; z-index: 1;
}
.tg-signup-btn {
    display: inline-flex; align-items: center; gap: 8px;
    background: #fff; color: #065f46;
    font-weight: 700; padding: 13px 30px;
    border-radius: 10px; text-decoration: none; font-size: 0.97rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    transition: transform 0.15s, box-shadow 0.15s;
}
.tg-signup-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.25); text-decoration: none; color: #065f46; }
.tg-login-btn {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,0.16); color: #fff;
    font-weight: 600; padding: 13px 26px;
    border-radius: 10px; text-decoration: none; font-size: 0.97rem;
    border: 1.5px solid rgba(255,255,255,0.4);
    transition: background 0.15s;
}
.tg-login-btn:hover { background: rgba(255,255,255,0.26); text-decoration: none; color: #fff; }

/* ── Buy / dark card ── */
.tg-card--dark {
    background: linear-gradient(135deg, #0c1a3e 0%, #0d2470 60%, #1a3fcc 100%);
    border: none; color: #fff;
    text-align: center;
}
.tg-card--dark .tg-card-head { justify-content: center; }
.tg-card--dark .tg-card-title { color: #fff; }
.tg-card--dark .tg-card-sub { color: rgba(255,255,255,0.6); }
.tg-buy-text {
    color: rgba(255,255,255,0.85); font-size: 1.07rem;
    line-height: 1.7; margin: 0 0 24px;
}
.tg-buy-btn {
    display: inline-flex; align-items: center; gap: 8px;
    background: #fff; color: #0022a0;
    font-weight: 700; font-size: 0.97rem;
    padding: 13px 32px; border-radius: 10px;
    text-decoration: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.25);
    transition: transform 0.15s, box-shadow 0.15s;
}
.tg-buy-btn:hover {
    transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,0.3);
    text-decoration: none; color: #0022a0;
}

/* ── Responsive ── */
@media (max-width: 1024px) {
    .tg-hero-title { font-size: 2.9rem; }
    .tg-hero-tagline { font-size: 1.15rem; max-width: 560px; }
}
@media (max-width: 860px) {
    .tg-earn-grid { grid-template-columns: repeat(2, 1fr); }
    .tg-hero-title { font-size: 2.5rem; }
    .tg-hero-tagline { font-size: 1.1rem; }
    .tg-stat-num { font-size: 1.9rem; }
    .tg-stats-strip { flex-wrap: wrap; }
    .tg-stat { max-width: none; flex: 0 0 50%; }
}
/* ── Upgrade Plan card ── */
.tg-upgrade {
    background: #fff;
    border-radius: 16px;
    border: 2px solid #6366f1;
    box-shadow: 0 4px 24px rgba(99,102,241,0.12);
    padding: 32px 36px;
    margin: 0 auto 40px;
    max-width: 1100px;
    width: calc(100% - 80px);
}
.tg-upgrade-head {
    display: flex; align-items: center; gap: 14px;
    margin-bottom: 20px;
}
.tg-upgrade-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: linear-gradient(135deg,#6366f1,#4f46e5);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.15rem; flex-shrink: 0;
}
.tg-upgrade-title { font-size: 1.18rem; font-weight: 700; color: #001e5e; margin: 0; }
.tg-upgrade-sub { font-size: 0.82rem; color: #7a8aac; margin-top: 2px; }
.tg-upgrade-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 24px;
}
.tg-upgrade-plan {
    border: 1.5px solid #e0e4f5;
    border-radius: 12px;
    padding: 18px 16px;
    position: relative;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.tg-upgrade-plan:hover { border-color: #6366f1; box-shadow: 0 4px 16px rgba(99,102,241,0.12); }
.tg-upgrade-plan.featured {
    border-color: #6366f1;
    background: linear-gradient(160deg,#f5f3ff,#ede9fe);
}
.tg-plan-badge {
    display: inline-block;
    background: #6366f1; color: #fff;
    font-size: 0.68rem; font-weight: 700; letter-spacing: 0.5px;
    text-transform: uppercase;
    padding: 2px 8px; border-radius: 20px;
    margin-bottom: 8px;
}
.tg-plan-name { font-size: 1rem; font-weight: 700; color: #1a1a2e; margin-bottom: 4px; }
.tg-plan-desc { font-size: 0.8rem; color: #556; line-height: 1.4; }
.tg-plan-perk {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.78rem; color: #334; margin-top: 8px;
}
.tg-plan-perk i { color: #6366f1; font-size: 0.75rem; }
.tg-upgrade-btn {
    display: inline-flex; align-items: center; gap: 8px;
    background: linear-gradient(135deg,#6366f1,#4f46e5);
    color: #fff; font-weight: 700; font-size: 0.97rem;
    padding: 13px 32px; border-radius: 10px;
    text-decoration: none;
    box-shadow: 0 4px 16px rgba(99,102,241,0.3);
    transition: transform 0.15s, box-shadow 0.15s;
}
.tg-upgrade-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(99,102,241,0.4);
    text-decoration: none; color: #fff;
}
@media (max-width: 640px) {
    .tg-hero { padding: 48px 20px 44px; }
    .tg-hero-title { font-size: 2.1rem; letter-spacing: -0.5px; }
    .tg-hero-tagline { font-size: 1.05rem; margin-bottom: 28px; max-width: 100%; }
    .tg-hero-icon-wrap { width: 72px; height: 72px; font-size: 1.9rem; margin-bottom: 18px; }
    .tg-hero-badge { margin-bottom: 14px; }
    .tg-stat-num { font-size: 1.7rem; }
    .tg-stat { flex: 0 0 50%; max-width: none; padding: 14px 8px; }
    .tg-body { padding: 32px 16px 60px; }
    .tg-card { padding: 24px 20px; }
    .tg-earn-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .tg-spend-grid { grid-template-columns: 1fr; }
    .tg-guest-cta { padding: 32px 20px; }
    .tg-guest-cta h3 { font-size: 1.35rem; }
    .tg-stats-strip { display: none; }
    .tg-upgrade-grid { grid-template-columns: 1fr; }
    .tg-upgrade { padding: 24px 20px; margin: 0 16px 28px; }
}
</style>
@endpush

@section('content')
<?php
    $autoLang = !empty($_page_get_data['autolang']) ? $_page_get_data['autolang'] : session('autolang', '');
    $appendAutoLang = function ($url) use ($autoLang) {
        if (empty($autoLang)) return $url;
        return $url . ((strpos($url, '?') !== false) ? '&' : '?') . 'autolang=' . urlencode($autoLang);
    };
    $isLoggedIn = !empty($_current_member);
?>
<div class="tg-page">

    <!-- ── Hero ── -->
    <div class="tg-hero">
        <div class="tg-hero-badge"><i class="fa fa-star"></i> AI-mmi Credit System</div>
        <div class="tg-hero-icon-wrap"><i class="fa fa-star"></i></div>
        <h1 class="tg-hero-title">AI-mmi Credits</h1>
        <p class="tg-hero-tagline">AI-mmi Credit is the currency used across AI-mmi for AI guidance, school and visa applications, employment &amp; migration support, tuition payments, tutoring, and more.</p>
        <div class="tg-hero-actions">
            <?php if($isLoggedIn): ?>
            <a href="<?php echo $appendAutoLang($_page_base_url.'/wallet'); ?>" class="tg-hero-wallet">
                <i class="fa fa-credit-card-alt"></i> View My Wallet
            </a>
            <?php else: ?>
            <a href="<?php echo $_page_base_url.'/account_registration'; ?>" class="tg-hero-cta">
                <i class="fa fa-star"></i> Get 20 Credits Free — Sign Up
            </a>
            <a href="<?php echo $appendAutoLang($_page_base_url.'/account_login'); ?>" class="tg-hero-wallet">
                <i class="fa fa-sign-in"></i> Sign In
            </a>
            <?php endif; ?>
        </div>
        <div class="tg-stats-strip">
            <div class="tg-stat">
                <div class="tg-stat-num">20</div>
                <div class="tg-stat-lbl">Free credits on sign up</div>
            </div>
            <div class="tg-stat">
                <div class="tg-stat-num">5</div>
                <div class="tg-stat-lbl">AI chats per credit</div>
            </div>
            <div class="tg-stat">
                <div class="tg-stat-num">5</div>
                <div class="tg-stat-lbl">College matches per credit</div>
            </div>
            <div class="tg-stat">
                <div class="tg-stat-num">$0.10</div>
                <div class="tg-stat-lbl">Per credit if you top up</div>
            </div>
        </div>
    </div>

    <div class="tg-body">

        <!-- ── What are Credits ── -->
        <div class="tg-card">
            <div class="tg-card-head">
                <div class="tg-card-icon" style="background:#eff6ff;color:#0055d4;"><i class="fa fa-info-circle"></i></div>
                <div>
                    <div class="tg-card-title">What are AI-mmi Credits?</div>
                    <div class="tg-card-sub">Your credits for AI-powered features</div>
                </div>
            </div>
            <p class="tg-what-text">
                AI-mmi Credit is the currency used across AI-mmi for AI guidance, school and visa applications, employment &amp; migration support, tuition payments, tutoring, and more.
                <br><br>
                <strong>1 AI-mmi Credit = USD $0.10</strong>
            </p>
        </div>

        <!-- ── Earn ── -->
        <div class="tg-card">
            <div class="tg-card-head">
                <div class="tg-card-icon" style="background:#eff6ff;color:#0055d4;"><i class="fa fa-plus-circle"></i></div>
                <div>
                    <div class="tg-card-title">How to Earn Credits</div>
                    <div class="tg-card-sub">Free rewards for everyday actions</div>
                </div>
            </div>
            <div class="tg-earn-grid">
                <div class="tg-earn-card">
                    <div class="tg-earn-icon"><i class="fa fa-user-plus"></i></div>
                    <div class="tg-earn-num">+20</div>
                    <div class="tg-earn-lbl">Sign Up</div>
                </div>
                <div class="tg-earn-card">
                    <div class="tg-earn-icon"><i class="fa fa-id-card-o"></i></div>
                    <div class="tg-earn-num">+3</div>
                    <div class="tg-earn-lbl">Complete Profile</div>
                </div>
                <div class="tg-earn-card">
                    <div class="tg-earn-icon"><i class="fa fa-share-alt"></i></div>
                    <div class="tg-earn-num">+2</div>
                    <div class="tg-earn-lbl">Share Results</div>
                </div>
                <div class="tg-earn-card">
                    <div class="tg-earn-icon"><i class="fa fa-users"></i></div>
                    <div class="tg-earn-num">+5</div>
                    <div class="tg-earn-lbl">Invite Friends</div>
                </div>
            </div>            <p style="margin:22px 0 0;color:#0055d4;font-size:1.05rem;font-weight:600;font-style:italic;">The more you participate, the more rewards you unlock.</p>        </div>

        <!-- ── Guest CTA (guests only) ── -->
        <?php if(!$isLoggedIn): ?>
        <div class="tg-guest-cta">
            <div class="tg-guest-badge"><i class="fa fa-gift"></i> Limited Time Offer</div>
            <h3>Start with 20 Credits — Completely Free</h3>
            <p>Create your free account and instantly unlock AI-powered college matching and migration guidance.<br>No credit card. No commitment.</p>
            <div class="tg-guest-btns">
                <a href="<?php echo $_page_base_url.'/account_registration'; ?>" class="tg-signup-btn">
                    <i class="fa fa-user-plus"></i> Create Free Account
                </a>
                <a href="<?php echo $appendAutoLang($_page_base_url.'/account_login'); ?>" class="tg-login-btn">
                    <i class="fa fa-sign-in"></i> Already have an account
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Transfer Credits ── -->
        <div class="tg-card">
            <div class="tg-card-head">
                <div class="tg-card-icon" style="background:#f0fdf4;color:#16a34a;"><i class="fa fa-exchange"></i></div>
                <div>
                    <div class="tg-card-title">Transfer Credits</div>
                    <div class="tg-card-sub">Share credits with others</div>
                </div>
            </div>
            <p class="tg-what-text" style="margin-bottom:14px;">Send Credits to:</p>
            <ul class="tg-sub-list">
                <li>another AI-mmi user as a gift</li>
                <li>family or friends pursuing their own international move as a gesture to support</li>
            </ul>
            <p class="tg-what-text">Universities, colleges, and partners may also reward you with promotional Credits and scholarships.</p>
            <?php if($isLoggedIn): ?>
            <div style="margin-top:18px;">
                <a href="<?php echo $appendAutoLang($_page_base_url.'/wallet#transfer'); ?>" class="tg-hero-cta" style="font-size:0.9rem;padding:10px 22px;">
                    <i class="fa fa-exchange"></i> Transfer Credits
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Use Credits ── -->
        <div class="tg-card">
            <div class="tg-card-head">
                <div class="tg-card-icon" style="background:#fff5eb;color:#c45c00;"><i class="fa fa-magic"></i></div>
                <div>
                    <div class="tg-card-title">Use Credits</div>
                    <div class="tg-card-sub">Spend credits on AI-powered features and services</div>
                </div>
            </div>
            <!-- Upgrade -->
            <p class="tg-sub-heading">Upgrade</p>
            <ul class="tg-sub-list">
                <li>AI + Agent Plan</li>
                <li>DIY + Expert Review Plan</li>
                <li>Full Agent Service Plan</li>
            </ul>
            <!-- Pay for Services -->
            <p class="tg-sub-heading">Pay for Services</p>
            <ul class="tg-sub-list">
                <li>School or agency fees</li>
                <li>Fees from other service providers</li>
                <li>Other online services</li>
            </ul>
            <!-- Unlock Platform Features -->
            <p class="tg-sub-heading">Unlock Platform Features</p>
            <ul class="tg-sub-list" style="margin-bottom:0;">
                <li>5 AI chats &mdash; 1 Credit</li>
                <li>5 smart matches &mdash; 1 Credit</li>
                <li>10 profile views by employers &ndash; 25 Credits</li>
            </ul>
        </div>

        <!-- ── Buy more ── -->
        <div class="tg-card tg-card--dark">
            <div class="tg-card-head">
                <div class="tg-card-icon" style="background:rgba(255,255,255,0.14);color:#fff;"><i class="fa fa-shopping-cart"></i></div>
                <div>
                    <?php if($isLoggedIn): ?>
                    <div class="tg-card-title">Credit Wallet</div>
                    <div class="tg-card-sub">Top up your balance anytime</div>
                    <?php else: ?>
                    <div class="tg-card-title">Credit Wallet</div>
                    <div class="tg-card-sub">1 AI-mmi Credit = USD $0.10</div>
                    <?php endif; ?>
                </div>
            </div>
            <p class="tg-buy-text" style="margin-bottom:10px;"><strong style="color:#fff;font-size:1.25rem;">1 AI-mmi Credit = USD $0.10</strong></p>
            <p class="tg-buy-text" style="margin-bottom:18px;font-size:1rem;font-weight:600;letter-spacing:0.3px;">Top Up Credits</p>
            <ul style="list-style:disc;padding-left:20px;color:rgba(255,255,255,0.9);font-size:1.05rem;line-height:2.1;margin-bottom:28px;">
                <li>100 Credits &mdash; $10</li>
                <li>500 Credits &mdash; $50</li>
                <li>1,000 Credits &mdash; $100</li>
                <li>2,000 Credits &mdash; $200</li>
                <li>5,000 Credits &mdash; $500</li>
                <li>10,000 Credits &mdash; $1,000</li>
            </ul>
            <?php if($isLoggedIn): ?>
            <a href="<?php echo $appendAutoLang($_page_base_url.'/wallet'); ?>" class="tg-buy-btn">
                <i class="fa fa-plus"></i> Top Up
            </a>
            <?php else: ?>
            <a href="<?php echo $_page_base_url.'/account_registration'; ?>" class="tg-buy-btn">
                <i class="fa fa-user-plus"></i> Top Up
            </a>
            <?php endif; ?>
        </div>

        <!-- ── Why AI-mmi Credit? ── -->
        <div class="tg-card">
            <div class="tg-card-head">
                <div class="tg-card-icon" style="background:#eff6ff;color:#0055d4;"><i class="fa fa-star"></i></div>
                <div>
                    <div class="tg-card-title">Why AI-mmi Credit?</div>
                </div>
            </div>
            <ul style="list-style:none;padding:0;margin:0 0 20px;font-size:1.08rem;color:#223;line-height:2.1;">
                <li>&#9989; Affordable AI + expert guidance</li>
                <li>&#9989; Earn while learning and sharing</li>
                <li>&#9989; Support friends and family globally</li>
                <li>&#9989; One currency for study, work, migration, and global opportunities</li>
            </ul>
            <p style="font-weight:900;color:#001e5e;font-size:1.3rem;margin:0;letter-spacing:-0.3px;">Learn. Earn. Apply. Succeed.</p>
        </div>

    </div><!-- /.tg-body -->

        <!-- ── Upgrade Your Plan ── -->
        <div class="tg-upgrade">
            <div class="tg-upgrade-head">
                <div class="tg-upgrade-icon"><i class="fa fa-level-up"></i></div>
                <div>
                    <div class="tg-upgrade-title">Upgrade Your Plan</div>
                    <div class="tg-upgrade-sub">Unlock premium AI features &amp; priority access</div>
                </div>
            </div>
            <div class="tg-upgrade-grid">
                <div class="tg-upgrade-plan">
                    <div class="tg-plan-name">AI + Agent Plan</div>
                    <div class="tg-plan-desc">Smart AI + Human Expert Support. Get instant guidance with access to qualified agents when you need personalized help.</div>
                    <div class="tg-plan-perk"><i class="fa fa-check-circle"></i> AI-powered guidance</div>
                    <div class="tg-plan-perk"><i class="fa fa-check-circle"></i> Consultation call with expert agents</div>
                </div>
                <div class="tg-upgrade-plan featured">
                    <div class="tg-plan-badge">Popular</div>
                    <div class="tg-plan-name">DIY + Expert Review Plan</div>
                    <div class="tg-plan-desc">Do-It-Yourself for Visa + Expert&rsquo;s Review before Submission. Perfect for applicants who want to prepare and submit visa applications without agents.</div>
                    <div class="tg-plan-perk"><i class="fa fa-check-circle"></i> AI visa eligibility assessment</div>
                    <div class="tg-plan-perk"><i class="fa fa-check-circle"></i> Expert review before submission</div>
                </div>
                <div class="tg-upgrade-plan">
                    <div class="tg-plan-name">Full Agent Service Plan</div>
                    <div class="tg-plan-desc">End-to-End Professional Assistance. A complete AI+Agent services with dedicated agent support throughout your application journey.</div>
                    <div class="tg-plan-perk"><i class="fa fa-check-circle"></i> Full visa consultation</div>
                    <div class="tg-plan-perk"><i class="fa fa-check-circle"></i> Dedicated agent support</div>
                </div>
            </div>
            <a href="<?php echo $appendAutoLang($_page_base_url.'/upgrade'); ?>" class="tg-upgrade-btn">
                <i class="fa fa-level-up"></i> View All Plans &amp; Pricing
            </a>
        </div>

</div><!-- /.tg-page -->
@endsection
