@extends('web.common')
@push('css')
<style>
/* Upgrade: full-width layout */
main.page-body .page-content { margin-right: 450px !important; }
main.page-body .info-area {
    float: none !important;
    width: 100% !important;
    background-color: #f5f6f8 !important;
    background-image: none !important;
    min-height: 100vh !important;
}
main.page-body .info-area::before { display: none !important; }
</style>
@endpush
@section('content')
@php
    $plans           = $_page_data['plans_gate']          ?? [];
    $balance         = (int)($_page_data['token_balance'] ?? 0);
    $walletUrl       = $_page_data['wallet_url']           ?? '#';
    $currentPlanCode = $_page_data['current_plan_code']   ?? null;
    $currentPlanName = $_page_data['current_plan_name']   ?? null;
    $currentPlanExpiry = $_page_data['current_plan_expiry'] ?? null;
@endphp
<style>
/* ── Upgrade page ────────────────────────────────────────────── */
.up-wrap        { max-width: 880px; margin: 0 auto; padding: 28px 16px 60px; }
.up-header      { text-align: center; margin-bottom: 28px; }
.up-header h1   { margin: 0 0 6px; font-size: 30px; font-weight: 900; color: var(--primary-blue-dark, #1a2744); }
.up-header p    { margin: 0; font-size: 15px; color: #666; }

/* balance bar */
.up-balance-bar {
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
    background: #fffbea; border: 1px solid #f0c940; border-radius: 12px;
    padding: 12px 20px; margin-bottom: 22px; font-size: 14px; color: #6b4a00;
}
.up-balance-bar strong { font-size: 22px; font-weight: 900; color: #c97d00; margin: 0 4px; }
.up-balance-bar a { font-size: 13px; padding: 7px 16px; background: #f0c940; color: #3a2a00;
    border-radius: 8px; text-decoration: none; font-weight: 700; }
.up-balance-bar a:hover { background: #e0b800; }

/* current plan banner */
.up-cur-plan   { display: flex; align-items: center; gap: 10px; background: #eef4ff;
    border: 1px solid #bcd0ff; border-radius: 12px; padding: 12px 18px; margin-bottom: 20px;
    font-size: 14px; color: #1a2744; }
.up-cur-plan i { font-size: 18px; color: #4361ee; flex-shrink: 0; }

/* flash notices */
.up-flash       { display: flex; align-items: flex-start; gap: 10px; border-radius: 12px;
    padding: 14px 18px; margin-bottom: 20px; font-size: 14px; position: relative; }
.up-flash.success { background: #e6f9ee; border: 1px solid #76d899; color: #185a2e; }
.up-flash.warning { background: #fff8e1; border: 1px solid #ffe082; color: #5a4000; }
.up-flash.error   { background: #fdecea; border: 1px solid #f4a9a3; color: #6b0000; }
.up-flash-close   { position: absolute; top: 8px; right: 10px; background: none; border: none;
    font-size: 18px; color: #999; cursor: pointer; line-height: 1; }
.up-flash-close:hover { color: #333; }

/* plan grid */
.up-grid        { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
@media (max-width: 760px) { .up-grid { grid-template-columns: 1fr; } }

/* plan card */
.up-card        { background: #fff; border: 1px solid #dee2e6; border-radius: 16px;
    padding: 26px 22px; display: flex; flex-direction: column; gap: 0; position: relative; }
.up-card.popular{ border-color: #4361ee; box-shadow: 0 4px 24px rgba(67,97,238,.14); }
.up-card.current{ border-color: #76d899; background: #f6fdf9; }

.up-popular-badge { position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
    background: #4361ee; color: #fff; font-size: 11px; font-weight: 800;
    padding: 3px 14px; border-radius: 100px; white-space: nowrap; }

.up-plan-name   { margin: 0 0 4px; font-size: 20px; font-weight: 900; color: #1a2744; }
.up-plan-subtitle { margin: 0 0 16px; font-size: 13px; color: #666; line-height: 1.45; min-height: 50px; }

/* price row */
.up-price-row   { display: flex; align-items: baseline; gap: 8px; margin-bottom: 6px; }
.up-token-cost  { font-size: 32px; font-weight: 900; color: #c97d00; }
.up-token-label { font-size: 13px; color: #888; }
.up-usd-equiv   { font-size: 13px; color: #444; margin-bottom: 16px; }

/* deficit box */
.up-deficit     { background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px;
    padding: 10px 14px; margin-bottom: 14px; font-size: 13px; color: #664d03; }
.up-deficit strong { display: block; font-size: 15px; color: #856404; margin-bottom: 2px; }

/* CTA buttons */
.up-cta         { display: block; text-align: center; font-size: 15px; font-weight: 700;
    padding: 13px 20px; border-radius: 10px; text-decoration: none; cursor: pointer;
    border: none; width: 100%; transition: filter .15s; margin-bottom: 18px; }
.up-cta.direct  { background: #4361ee; color: #fff; }
.up-cta.direct:hover { filter: brightness(1.1); }
.up-cta.deficit { background: #f0c940; color: #3a2a00; }
.up-cta.deficit:hover { filter: brightness(1.08); }
.up-cta.current-btn { background: #e0f5e9; color: #2a6b3a; pointer-events: none; }

/* features */
.up-features-title { margin: 0 0 8px; font-size: 13px; font-weight: 800; color: #333; }
.up-features-list  { margin: 0; padding-left: 18px; font-size: 13px; color: #555; line-height: 1.5; }
.up-features-list li + li { margin-top: 6px; }

.up-access     { font-size: 12px; color: #888; margin-bottom: 16px; }
.up-plan-desc  { font-size: 13px; color: #555; line-height: 1.5; margin: 0 0 14px; }
.up-best-for   { font-size: 13px; color: #2a6b3a; margin: 10px 0 0; display: flex; align-items: flex-start; gap: 6px; }
</style>

<div class="up-wrap">
    <div class="up-header">
        <h1>Upgrade Your Plan</h1>
        <p>Pay with AI-mmi Credits &mdash; 1 credit = $0.10 USD</p>
    </div>

    {{-- Flash messages --}}
    @if(request()->query('payment') === 'success')
    <div class="up-flash success">
        <i class="fas fa-check-circle"></i>
        <span>Your plan has been activated successfully! Enjoy your new access.</span>
        <button class="up-flash-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    @elseif(request()->query('payment') === 'cancelled')
    <div class="up-flash warning">
        <i class="fas fa-exclamation-circle"></i>
        <span>Payment was cancelled. Your balance has not changed.</span>
        <button class="up-flash-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    @elseif(request()->query('notice') === 'already_active')
    <div class="up-flash warning">
        <i class="fas fa-info-circle"></i>
        <span>You already have this plan active. To renew, wait until closer to your expiry.</span>
        <button class="up-flash-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    @elseif(request()->query('error') === 'activation_failed' || request()->query('error') === 'stripe_failed')
    <div class="up-flash error">
        <i class="fas fa-times-circle"></i>
        <span>Something went wrong. Please try again or contact support.</span>
        <button class="up-flash-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    @endif

    {{-- Current plan banner --}}
    @if($currentPlanCode)
    <div class="up-cur-plan">
        <i class="fas fa-check-circle"></i>
        <span>Current plan: <strong>{{ $currentPlanName }}</strong>
            @if($currentPlanExpiry)
                &mdash; active until {{ \Carbon\Carbon::parse($currentPlanExpiry)->format('M j, Y') }}
            @endif
        </span>
    </div>
    @endif

    {{-- Balance bar --}}
    <div class="up-balance-bar">
        <span>Your credit balance: <strong>{{ number_format($balance) }}</strong> credits</span>
        <a href="{{ $walletUrl }}">&#43; Top Up Credits</a>
    </div>

    {{-- Plan cards --}}
    @if(!empty($plans))
    <div class="up-grid">
        @foreach($plans as $plan)
        @php
            $isCurrent  = !empty($plan['is_current']);
            $isDirect   = !empty($plan['can_pay_direct']);
            $isPopular  = !empty($plan['is_popular']);
            $deficit    = (int)($plan['deficit'] ?? 0);
        @endphp
        <article class="up-card{{ $isPopular ? ' popular' : '' }}{{ $isCurrent ? ' current' : '' }}">
            @if($isPopular)
                <span class="up-popular-badge">Most Popular</span>
            @endif

            <h2 class="up-plan-name">{{ $plan['name'] ?? '' }}</h2>
            <p class="up-plan-subtitle">{{ $plan['subtitle'] ?? '' }}</p>
            @if(!empty($plan['description']))
            <p class="up-plan-desc">{{ $plan['description'] }}</p>
            @endif

            <div class="up-price-row">
                <span class="up-token-cost">{{ number_format($plan['token_cost']) }}</span>
                <span class="up-token-label">credits</span>
            </div>
            <p class="up-usd-equiv">≈ {{ $plan['usd_equiv'] }} USD &bull; {{ $plan['access_months'] }} months access</p>

            @if(!$isCurrent && $deficit > 0)
            <div class="up-deficit">
                <strong>You need {{ number_format($deficit) }} more credits</strong>
                Top up {{ $plan['deficit_usd'] }} via card to complete activation, or add tokens to your wallet first.
            </div>
            @endif

            @if($isCurrent)
                <span class="up-cta current-btn">&#10003; Current Plan</span>
            @elseif($isDirect)
                <a class="up-cta direct" href="{{ $plan['checkout_url'] ?? '#' }}">
                    Activate Now &mdash; {{ number_format($plan['token_cost']) }} credits
                </a>
            @else
                <a class="up-cta deficit" href="{{ $plan['checkout_url'] ?? '#' }}">
                    Pay {{ $plan['deficit_usd'] }} &amp; Activate
                </a>
            @endif

            <p class="up-features-title">What&rsquo;s included:</p>
            <ul class="up-features-list">
                @foreach(($plan['features'] ?? []) as $f)
                <li>{{ $f }}</li>
                @endforeach
            </ul>
            @if(!empty($plan['best_for']))
            <p class="up-best-for">&#9989; {{ $plan['best_for'] }}</p>
            @endif
        </article>
        @endforeach
    </div>

    <p style="text-align:center;margin-top:18px;font-size:13px;color:#888;">
        Need more credits first? <a href="{{ $walletUrl }}" style="color:#4361ee;font-weight:700;">Go to Wallet &rarr;</a>
    </p>
    @else
    <p style="text-align:center;color:#888;margin-top:40px;">No plans are available right now. Please try again later.</p>
    @endif
</div>
@endsection
