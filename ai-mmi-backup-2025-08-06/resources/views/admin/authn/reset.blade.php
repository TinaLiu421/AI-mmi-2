@extends('admin.common')
@section('content')
<div class="widget">
    <div class="left">
        <img src="asset/image/logo-mmi.png" alt="logo">
    </div><!--
    --><div class="right">
        <h1><?php echo $_page_lang['reset_password']; ?></h1>
        <form id="resetform" method="post">
            <div>@csrf</div>
            <div class="page-message iweb-tips-message"></div>
            <div class="row">
                <label for="user_password"><?php echo $_page_lang['user_password']; ?></label>
                <input type="password" id="user_password" name="user_password" data-validation="required|password">
            </div>
            <div class="row">
                <label for="user_repeat_password"><?php echo $_page_lang['user_repeat_password']; ?></label>
                <input type="password" id="user_repeat_password" name="user_repeat_password" data-validation="required|password">
            </div>
            <div class="row" style="text-align:right;">
                <a href="<?php echo url($_mapping_data['module'].'/authn');?>">
                    <i class="fa fa-chevron-left"></i>
                    <u><?php echo $_page_lang['back']; ?></u>
                </a>
            </div>
            <button type="submit" class="btn"><?php echo $_page_lang['submit']; ?></button>
        </form>
    </div>
    <div class="clearboth"></div>
</div>
@endsection