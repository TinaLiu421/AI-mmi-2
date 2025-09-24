@extends('admin.common')
@section('content')
<div class="widget">
    <div class="left">
        <img src="asset/image/logo-mmi.png" alt="logo">
    </div><!--
    --><div class="right">
        <h1><?php echo $_page_lang['forgot_password']; ?></h1>
        <form id="forgotform" method="post">
            <div>@csrf</div>
            <div><input type="hidden" id="page_action" name="page_action" value="forgot"></div>
            <div class="page-message iweb-tips-message"></div>
            <div class="row">
                <span style="line-height:1.5;"><?php echo $_page_lang['forgot_tips']; ?></span>
            </div>
            <div class="row">
                <label for="user_email"><?php echo $_page_lang['user_email']; ?></label>
                <input type="text" id="user_email" name="user_email" data-validation="required|email">
            </div>
            <div class="row" style="text-align:right;">
                <a href="<?php echo url($_mapping_data['module'].'/authn');?>">
                    <i class="fa fa-chevron-left"></i>
                    <u><?php echo $_page_lang['back']; ?></u>
                </a>
            </div>
            <button type="submit" class="btn"><?php echo $_page_lang['send']; ?></button>
        </form>
    </div>
    <div class="clearboth"></div>
</div>
@endsection