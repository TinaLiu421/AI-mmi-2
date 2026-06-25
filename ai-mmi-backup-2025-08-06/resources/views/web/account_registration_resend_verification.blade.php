@extends('web.common')
@section('content')
<div class="inner-panel">
    <h1 class="title"><?php echo $_page_lang['account_verification']; ?></h1>
    <div class="underline"></div>
    <div class="clearboth"></div>

    <div class="form">
        <p style="margin-bottom:20px;">If you did not receive your account verification email, enter your registered email address below and we will send you a new one.</p>
        <form id="resend-verification-form" method="post">
            <div>@csrf</div>
            <div class="row">
                <label for="email"><?php echo $_page_lang['account.email']; ?> <span style="color:red;">*</span></label>
                <input type="text" id="email" name="email" placeholder="<?php echo $_page_lang['account.enter_email']; ?>" value="" data-validation="required|email">
            </div>
            <div class="clearboth"></div>
            <div class="action">
                <a class="btn btn-back" href="<?php echo $_page_base_url.'/account_login'; ?>"><?php echo $_page_lang['btn.back']; ?></a>
                <button type="submit" class="btn btn-next">Resend Verification Email</button>
                <div class="clearboth"></div>
            </div>
        </form>
    </div>
</div>
@endsection
