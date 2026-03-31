@extends('web.common')
@section('content')
<div class="container">
  @php
    $plans = $_page_data['plans_gate'] ?? [];
    $pricingTableId = $_page_data['pricing_table_id'] ?? env('STRIPE_PRICING_TABLE_ID_1');
    $stripePublishableKey = $_page_data['stripe_pk'] ?? env('STRIPE_KEY');
    $currentPlanCode  = $_page_data['current_plan_code'] ?? null;
    $currentPlanName  = $_page_data['current_plan_name'] ?? null;
    $currentPlanExpiry = $_page_data['current_plan_expiry'] ?? null;
  @endphp

  @if(!empty($plans))
  <style>
    .upgrade-gate-wrap {
      padding: 20px 0 8px;
    }

    .current-plan-banner {
      background: #eef4ff;
      border: 1px solid #bcd0ff;
      border-radius: 12px;
      padding: 12px 16px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      color: var(--primary-blue-dark);
    }

    .current-plan-banner strong { font-weight: 800; }

    .meeting-used-notice {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      background: #fff8e1;
      border: 1px solid #ffe082;
      border-radius: 12px;
      padding: 14px 16px;
      margin-bottom: 16px;
      font-size: 14px;
      color: #5a4000;
      line-height: 1.5;
      position: relative;
    }

    .meeting-used-notice__icon { font-size: 16px; flex-shrink: 0; margin-top: 1px; }

    .meeting-used-notice__close {
      position: absolute;
      top: 8px;
      right: 10px;
      background: none;
      border: none;
      font-size: 18px;
      line-height: 1;
      color: #999;
      cursor: pointer;
      padding: 0 4px;
    }

    .meeting-used-notice__close:hover { color: #333; }

    .upgrade-cta.is-current {
      background: var(--neutral-200);
      color: var(--neutral-600);
      pointer-events: none;
      cursor: default;
      box-shadow: none;
      filter: none;
    }

    .upgrade-gate-header {
      text-align: center;
      margin-bottom: 18px;
    }

    .upgrade-gate-header h1 {
      margin: 0 0 6px;
      font-size: 30px;
      line-height: 1.2;
      color: var(--primary-blue-dark);
      font-weight: 900;
    }

    .upgrade-gate-header p {
      margin: 0;
      color: var(--neutral-700);
      font-size: 15px;
    }

    .upgrade-plan-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 16px;
    }

    .upgrade-plan-card {
      position: relative;
      background: var(--white);
      border-radius: 16px;
      border: 1px solid var(--neutral-200);
      box-shadow: var(--shadow-md);
      padding: 18px;
      display: flex;
      flex-direction: column;
      min-height: 100%;
    }

    .upgrade-plan-card.is-popular {
      border-color: var(--primary-blue);
      box-shadow: var(--shadow-lg);
    }

    .upgrade-popular-badge {
      position: absolute;
      top: -10px;
      right: 14px;
      background: var(--primary-blue);
      color: var(--white);
      border-radius: 999px;
      font-size: 12px;
      font-weight: 800;
      padding: 4px 10px;
      letter-spacing: 0.2px;
    }

    .upgrade-plan-name {
      margin: 0;
      font-size: 22px;
      line-height: 1.25;
      color: var(--neutral-900);
      font-weight: 900;
    }

    .upgrade-plan-period {
      margin: 4px 0 2px;
      font-size: 13px;
      font-weight: 700;
      color: var(--primary-blue);
    }

    .upgrade-plan-renew {
      margin: 0 0 10px;
      font-size: 12px;
      color: var(--neutral-500);
    }

    .upgrade-plan-subtitle {
      margin: 8px 0 14px;
      color: var(--neutral-700);
      line-height: 1.5;
      font-size: 14px;
      min-height: 64px;
    }

    .upgrade-price-row {
      margin-bottom: 10px;
      display: flex;
      align-items: baseline;
      gap: 8px;
    }

    .upgrade-price {
      font-size: 40px;
      line-height: 1;
      font-weight: 900;
      color: var(--primary-blue-dark);
    }

    .upgrade-billing {
      font-size: 14px;
      color: var(--neutral-600);
      line-height: 1.3;
    }

    .upgrade-cta {
      display: inline-flex;
      justify-content: center;
      align-items: center;
      margin: 8px 0 14px;
      width: 100%;
      height: 46px;
      border-radius: 12px;
      text-decoration: none;
      font-size: 16px;
      font-weight: 900;
      color: var(--white);
      background: var(--gradient-primary);
      box-shadow: var(--shadow-md);
      transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
    }

    .upgrade-cta:hover {
      transform: translateY(-1px);
      box-shadow: var(--shadow-lg);
      filter: brightness(1.04);
      color: var(--white);
      text-decoration: none;
    }

    .upgrade-features-title {
      margin: 0 0 8px;
      color: var(--neutral-800);
      font-weight: 800;
      font-size: 14px;
    }

    .upgrade-features-list {
      margin: 0;
      padding-left: 18px;
      color: var(--neutral-700);
      line-height: 1.45;
      font-size: 14px;
    }

    .upgrade-features-list li + li {
      margin-top: 7px;
    }

    @media (max-width: 980px) {
      .upgrade-plan-grid {
        grid-template-columns: 1fr;
      }

      .upgrade-plan-subtitle {
        min-height: 0;
      }
    }
  </style>

  <div class="upgrade-gate-wrap">
    @if(request()->query('notice') === 'meeting_used')
    <div class="meeting-used-notice">
      <span class="meeting-used-notice__icon">✅</span>
      <span>You have completed your free consultation meeting with the agent. Upgrade your plan to enjoy more consultation arrangements with the agent.</span>
      <button class="meeting-used-notice__close" onclick="this.parentElement.style.display='none'" aria-label="Dismiss">&times;</button>
    </div>
    @endif
    @if($currentPlanCode)
    <div class="current-plan-banner">
      <i class="fas fa-check-circle" style="color:var(--primary-blue);font-size:18px;"></i>
      <span>Your current plan: <strong>{{ $currentPlanName }}</strong>
        @if($currentPlanExpiry)
          &mdash; active until {{ \Carbon\Carbon::parse($currentPlanExpiry)->format('M j, Y') }}
        @endif
      </span>
    </div>
    @endif
    <div class="upgrade-gate-header">
      <h1>Choose Your Plan</h1>
      <p>Secure checkout powered by Stripe</p>
    </div>

    <div class="upgrade-plan-grid">
      @foreach($plans as $plan)
        <article class="upgrade-plan-card{{ !empty($plan['is_popular']) ? ' is-popular' : '' }}">
          @if(!empty($plan['is_popular']))
            <span class="upgrade-popular-badge">Most popular</span>
          @endif

          <h2 class="upgrade-plan-name">{{ $plan['name'] ?? '' }}</h2>
          @if(!empty($plan['period_label']))
            <p class="upgrade-plan-period">{{ $plan['period_label'] }}</p>
          @endif
          @if(!empty($plan['renew_note']))
            <p class="upgrade-plan-renew">{{ $plan['renew_note'] }}</p>
          @endif
          <p class="upgrade-plan-subtitle">{{ $plan['subtitle'] ?? '' }}</p>

          <div class="upgrade-price-row">
            <div class="upgrade-price">{{ $plan['price'] ?? '' }}</div>
          </div>

          @php $isCurrent = ($currentPlanCode === ($plan['code'] ?? '')); @endphp
          <a class="upgrade-cta{{ $isCurrent ? ' is-current' : '' }}" href="{{ $isCurrent ? '#' : ($plan['checkout_url'] ?? '#') }}">
            {{ $isCurrent ? 'Current Plan' : ($plan['cta'] ?? 'Pay') }}
          </a>

          <p class="upgrade-features-title">This includes:</p>
          <ul class="upgrade-features-list">
            @foreach(($plan['features'] ?? []) as $feature)
              <li>{{ $feature }}</li>
            @endforeach
          </ul>
        </article>
      @endforeach
    </div>
  </div>
  @else
  <stripe-pricing-table
    pricing-table-id="{{ $pricingTableId }}"
    publishable-key="{{ $stripePublishableKey }}"
    client-reference-id="{{ $_current_member['id'] ?? '' }}"
    customer-email="{{ $_current_member['email'] ?? '' }}"
  ></stripe-pricing-table>
  @endif
</div>
@endsection
