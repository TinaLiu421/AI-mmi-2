@extends('web.common')
@section('content')


<div class="container">
  <!-- <h1>Choose Your Plan</h1> -->

  @if(!empty($current_plan))
  <div class="alert alert-info">
    <h4>Your Current Plan: {{ ucfirst($current_plan['plan_slug']) }}</h4>
    @if($current_plan['plan_slug'] !== 'free')
      <p>Expires: {{ $current_plan['expires_at'] ?? 'Never' }}</p>
      <p>Migration questions used: {{ $current_plan['migration_questions_used'] }} / {{ $current_plan['migration_questions_limit'] === -1 ? 'Unlimited' : $current_plan['migration_questions_limit'] }}</p>
      <p>Education questions used: {{ $current_plan['education_questions_used'] }} / {{ $current_plan['education_questions_limit'] === -1 ? 'Unlimited' : $current_plan['education_questions_limit'] }}</p>
    @else
      <p>Migration questions used: {{ $current_plan['migration_questions_used'] }} / 5</p>
      <p>Education questions: Unlimited</p>
    @endif
  </div>
  @endif

  <stripe-pricing-table
    pricing-table-id="{{ $pricing_table_id ?? env('STRIPE_PRICING_TABLE_ID_1') }}"
    publishable-key="{{ $stripe_pk ?? env('STRIPE_KEY') }}"
    customer-email="{{ $customer_email ?? '' }}"
    @if(!empty($member_id))
    client-reference-id="{{ $member_id }}"
    @endif
  >
  </stripe-pricing-table>

  <script>
    // Pass member_id to Stripe session metadata
    @if(!empty($member_id))
    document.addEventListener('DOMContentLoaded', function() {
      const pricingTable = document.querySelector('stripe-pricing-table');
      if (pricingTable) {
        // Add member_id to session metadata
        pricingTable.setAttribute('data-metadata', JSON.stringify({
          member_id: '{{ $member_id }}'
        }));
      }
    });
    @endif
  </script>
</div>
@endsection

