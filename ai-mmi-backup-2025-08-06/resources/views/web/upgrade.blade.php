@extends('web.common')
@section('content')
<div class="container">
  <style>
    .upgrade-gate-wrap {
      padding: 20px 0 8px;
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

    .upgrade-plan-subtitle {
      margin: 10px 0 14px;
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
    <div class="upgrade-gate-header">
      <h1>Choose Your Plan</h1>
      <p>Secure checkout powered by Stripe</p>
    </div>

    <div class="upgrade-plan-grid">
      @foreach(($plans_gate ?? []) as $plan)
        <article class="upgrade-plan-card{{ !empty($plan['is_popular']) ? ' is-popular' : '' }}">
          @if(!empty($plan['is_popular']))
            <span class="upgrade-popular-badge">Most popular</span>
          @endif

          <h2 class="upgrade-plan-name">{{ $plan['name'] ?? '' }}</h2>
          <p class="upgrade-plan-subtitle">{{ $plan['subtitle'] ?? '' }}</p>

          <div class="upgrade-price-row">
            <div class="upgrade-price">{{ $plan['price'] ?? '' }}</div>
            <div class="upgrade-billing">{{ $plan['billing'] ?? '' }}</div>
          </div>

          <a class="upgrade-cta" href="{{ $plan['checkout_url'] ?? '#' }}">{{ $plan['cta'] ?? 'Pay' }}</a>

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
</div>
@endsection
