@extends('web.common')
@section('content')
<div class="inner-panel">
    <h1 class="title"><?php echo $_page_lang['account.sign_in']; ?></h1>
    <div class="underline"></div>
    <div class="clearboth"></div>
    
    <div class="form">
        <form id="account-login-form" method="post">
            <div>@csrf</div>
            <div><input type="hidden" id="method" name="method" value="1"></div>
            <div><input type="hidden" id="third_party_token" name="third_party_token" value=""></div>
            
            <div class="required"><span style="color:red;">*</span> <?php echo $_page_lang['required']; ?></div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="email"><?php echo $_page_lang['account.email']; ?> <span style="color:red;">*</span></label>
                <input type="text" id="email" name="email" placeholder="<?php echo $_page_lang['account.enter_email']; ?>" value="" data-validation="required|email">
            </div>
            <div class="clearboth"></div>
            
            <div class="row">
                <label for="password"><?php echo $_page_lang['account.password']; ?> <span style="color:red;">*</span></label>
                <input type="password" id="password" name="password" placeholder="<?php echo $_page_lang['account.enter_password']; ?>" value="" data-validation="required">
            </div>
            <div class="clearboth"></div>
            
            <div class="row" style="text-align:right;">
                <a href="<?php echo $_page_base_url.'/account_forgot' ;?>"><u><?php echo $_page_lang['forgot_password']; ?>?</u></a>
            </div>
            
            
            <div class="clearboth"></div>

            <div class="action">
                <a class="btn btn-back" href="<?php echo $_page_base_url.'/account_registration' ;?>"><?php echo $_page_lang['btn.sign_up_now']; ?></a>
                <button type="submit" class="btn btn-next"><?php echo $_page_lang['btn.sign_in_now']; ?></button>
                <div class="clearboth"></div>
            </div>

        </form>

        <div class="or">
            <span><?php echo $_page_lang['account.or']; ?></span>
        </div>

        <div class="third-party">
            {{-- Google - Individual --}}
            <a href="<?php echo $_page_base_url.'/account_login/google?role=individual'; ?>" class="google">
                <i class="fa fa-google"></i>
                <span><?php echo $_page_lang['account.with_google_in']; ?> (Individual)</span>
            </a>

            {{-- Google - Service Provider --}}
            <a href="<?php echo $_page_base_url.'/account_login/google?role=provider'; ?>" class="google">
                <i class="fa fa-google"></i>
                <span><?php echo $_page_lang['account.with_google_in']; ?> (Service Provider)</span>
            </a>

            {{-- Facebook --}}
            <a class="btn-oauth fb"
                href="<?php echo $_page_base_url; ?>/account_login/facebook?role=individual">
                <i class="fa fa-facebook"></i><span>Sign in with Facebook (Individual)</span>
            </a>
            <a class="btn-oauth fb"
                href="<?php echo $_page_base_url; ?>/account_login/facebook?role=provider">
                <i class="fa fa-facebook"></i><span>Sign in with Facebook (Service Provider)</span>
            </a>
        </div>

        {{-- Mobile-only sign-up nudge (hidden on desktop via CSS) --}}
        <div class="mobile-signup-nudge">
            New here? <a href="<?php echo $_page_base_url.'/account_registration'; ?>"><?php echo $_page_lang['btn.sign_up_now']; ?></a>
        </div>
    </div>
</div>
@endsection