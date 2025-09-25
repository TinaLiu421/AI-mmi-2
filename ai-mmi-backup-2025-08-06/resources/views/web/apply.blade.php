@extends('web.common')
@section('content')


<div class="container">
  <!-- <h1>Choose Your Plan</h1> -->

  <stripe-pricing-table
    pricing-table-id="{{ env('STRIPE_PRICING_TABLE_ID_2') }}"
    publishable-key="{{ env('STRIPE_KEY') }}"
    customer-email="{{ auth()->user()->email ?? '' }}">
  </stripe-pricing-table>
</div>
@endsection

