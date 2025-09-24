@extends('web.common')
@section('content')
<div class="inner-panel">
    <div class="form">
        <div class="thanks">
            <div class="ttitle"><?php echo $_page_lang['thank_you']; ?></div>
            <div class="tmsg">
                <span>✔</span>
                <?php echo $_page_lang['payment_successful']; ?>
            </div>
            <div class="action center">
                <a class="btn btn-back-2 btn-back-3" href="<?php echo $_page_base_url.'/account_login' ;?>"><?php echo $_page_lang['btn.to_profile']; ?></a>
            </div>
        </div>
    </div>
</div>
@endsection