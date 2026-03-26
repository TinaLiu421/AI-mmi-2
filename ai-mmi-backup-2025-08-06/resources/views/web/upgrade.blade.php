@extends('web.common')
@section('content')
<div class="container">
    <stripe-pricing-table
      pricing-table-id="{{ $pricing_table_id ?? env('STRIPE_PRICING_TABLE_ID_1') }}"
      publishable-key="{{ $stripe_pk ?? env('STRIPE_KEY') }}"
      client-reference-id="{{ $_current_member['id'] ?? '' }}"
      customer-email="{{ $_current_member['email'] ?? '' }}"
    ></stripe-pricing-table>

    @php
        $primaryTableId = $pricing_table_id ?? env('STRIPE_PRICING_TABLE_ID_1');
        $oneTimeTableId = $one_time_pricing_table_id ?? env('STRIPE_PRICING_TABLE_ID_ONE_TIME', env('STRIPE_PRICING_TABLE_ID_2'));
    @endphp

    @if(!empty($oneTimeTableId) && $oneTimeTableId !== $primaryTableId)
        <div style="margin-top: 28px;"></div>
        <stripe-pricing-table
          pricing-table-id="{{ $oneTimeTableId }}"
          publishable-key="{{ $stripe_pk ?? env('STRIPE_KEY') }}"
          client-reference-id="{{ $_current_member['id'] ?? '' }}"
          customer-email="{{ $_current_member['email'] ?? '' }}"
        ></stripe-pricing-table>
    @endif
</div>
@endsection
