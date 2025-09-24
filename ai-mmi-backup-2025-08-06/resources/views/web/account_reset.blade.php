@extends('web.common')
@section('content')
<div class="inner-panel">
    <h1 class="title"><?php echo $_page_lang['forgot_password']; ?></h1>
    <div class="underline"></div>
    <div class="clearboth"></div>
    
    <div class="form">
        <form id="account-reset-form" method="post">
            <div>@csrf</div>
            <div><input type="hidden" name="reset_token" value="<?php echo $_page_get_data['token']; ?>"></div>
            <div class="required"><span style="color:red;">*</span> <?php echo $_page_lang['required']; ?></div>
            <div class="clearboth"></div>

            
            <div class="row">
                <label for="password"><?php echo $_page_lang['account.password']; ?> <span style="color:red;">*</span></label>
                <input type="password" id="password" name="password" placeholder="<?php echo $_page_lang['account.enter_password']; ?>" value="" data-validation="required|password">
            </div>
            <div class="clearboth"></div>
            
            <div class="row">
                <label for="repeat_password"><?php echo $_page_lang['account.re_password']; ?> <span style="color:red;">*</span></label>
                <input type="password" id="repeat_password" name="repeat_password" placeholder="<?php echo $_page_lang['account.enter_password']; ?>" value="" data-validation="required|password">
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