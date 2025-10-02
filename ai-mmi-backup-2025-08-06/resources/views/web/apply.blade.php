@extends('web.common')
@section('content')
<div class="container">



<stripe-pricing-table
  pricing-table-id="{{ env('STRIPE_PRICING_TABLE_ID_2') }}"
  publishable-key="{{ env('STRIPE_KEY') }}"
  
  client-reference-id="{{ $_current_member['id'] ?? '' }}"
  customer-email="{{ $_current_member['email'] ?? '' }}"


></stripe-pricing-table>
</div>
@endsection
