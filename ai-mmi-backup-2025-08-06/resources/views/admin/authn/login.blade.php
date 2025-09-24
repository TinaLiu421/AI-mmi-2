@extends('admin.common')
@section('content')
<div class="widget">
    <div class="left">
        <img src="asset/image/logo-mmi.png" alt="logo">
    </div><!--
    --><div class="right">
        <form id="loginform" method="post">
            <div>@csrf</div>
            <div class="page-message iweb-tips-message"></div>
            <div class="row">
                <label for="user_id"><?php echo $_page_lang['user_id']; ?></label>
                <input type="text" id="user_id" name="user_id" data-validation="required">
            </div>
            <div class="row">
                <label for="user_password"><?php echo $_page_lang['user_password']; ?></label>
                <input type="password" id="user_password" name="user_password" data-validation="required">
            </div>
            <div class="row" style="text-align:right;">
                <a href="<?php echo url($_mapping_data['module'].'/authn/forgot');?>"><u><?php echo $_page_lang['forgot_password']; ?>?</u></a>
            </div>
            <button type="submit" class="btn"><?php echo $_page_lang['login']; ?></button>
        </form>
    </div>
    <div class="clearboth"></div>
</div>
<?php if(!empty($_page_data['myip'])) { ?>
<div class="ip_address">IP Address: <?php echo $_page_data['myip']; ?></div>
<?php } ?>
@endsection