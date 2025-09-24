@extends('web.common')
@section('content')
<div class="inner-panel">
    <h1 class="title"><?php echo $_page_lang['payment']; ?></h1>
    <div class="top-brief iweb-editor">
        <p><?php echo $_page_lang['choose_your_payment']; ?></p>
    </div>
    <div class="underline"></div>
    <div class="clearboth"></div>

    <div class="form">
        <form id="account-submission-payment-form" method="post">
            <div>@csrf</div>

            <div class="method">
                <input type="radio" id="payment_method_1" name="payment_method" value="1" checked="">
                <label for="payment_method_1">
                    <img src="asset/image/method-paypal.png" alt="method-paypal">
                </label>
            </div>
            
            <div class="action">
                <a class="btn btn-back" href="<?php echo $_page_base_url.'/account_submission' ;?>"><?php echo $_page_lang['btn.back']; ?></a>
                <button type="submit" class="btn btn-next"><?php echo $_page_lang['btn.pay_now']; ?></button>
                <div class="clearboth"></div>
            </div>
        </form>
    </div>
</div>
@endsection