@extends('web.common')
@section('content')
<div class="inner-panel">
    <h1 class="title"><?php echo $_page_lang['account_verification']; ?></h1>
    <div class="underline"></div>
    <div class="clearboth"></div>
   
    <div class="form">
        <?php if(!empty($_page_data['verification_result'])) { ?>
        <div class="vmsg"><?php echo $_page_lang['activated_successfully']; ?></div>
        <div class="action center">
            <a class="btn btn-back-2" href="<?php echo $_page_base_url.'/account_login' ;?>"><?php echo $_page_lang['btn.sign_in_now']; ?></a>
        </div>
        <?php } else { ?>
        <div class="vmsg"><?php echo $_page_lang['activated_failed']; ?></div>
        <?php } ?>
    </div>
</div>
@endsection