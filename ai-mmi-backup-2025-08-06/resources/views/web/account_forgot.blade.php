@extends('web.common')
@section('content')
<div class="inner-panel">
    <h1 class="title"><?php echo $_page_lang['forgot_password']; ?></h1>
    <div class="underline"></div>
    <div class="clearboth"></div>
    
    <div class="form">
        <form id="account-forgot-form" method="post">
            <div>@csrf</div>
            <div class="required"><span style="color:red;">*</span> <?php echo $_page_lang['required']; ?></div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="email"><?php echo $_page_lang['forgot_tips']; ?> <span style="color:red;">*</span></label>
                <input type="text" id="email" name="email" placeholder="<?php echo $_page_lang['account.enter_email']; ?>" value="" data-validation="required|email">
            </div>
            <div class="clearboth"></div>
            
            
            <div class="action">
                <button type="submit" class="btn btn-next"><?php echo $_page_lang['btn.submit']; ?></button>
                <div class="clearboth"></div>
            </div>
        </form>
    </div>
</div>
@endsection