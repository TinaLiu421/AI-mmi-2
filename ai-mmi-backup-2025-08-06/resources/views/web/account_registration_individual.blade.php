@extends('web.common')
@section('content')
<div class="inner-panel">
    <?php if(empty($_page_data['parameter'])) {?>
    <?php
    $_page_data['account'] = array_merge([
        'first_name'        => '',
        'last_name'         => '',
        'email'             => '',
        'password'          => '',
        'repeat_password'   => ''
    ], ((!empty($_page_data['account']))?$_page_data['account']:[]))
    ?>
    <h1 class="title"><?php echo $_page_lang['create_your_account']; ?></h1>
    <div class="underline"></div>
    <div class="clearboth"></div>
    
    <div class="form">
        <form id="account-individual-form" method="post">
            <div>@csrf</div>
            <div><input type="hidden" id="method" name="method" value="1"></div>
            <div><input type="hidden" id="third_party_token" name="third_party_token" value=""></div>
            
            <div class="required"><span style="color:red;">*</span> <?php echo $_page_lang['required']; ?></div>
            <div class="clearboth"></div>
            
            <div class="row left">
                <label for="first_name"><?php echo $_page_lang['account.name']; ?> <span style="color:red;">*</span></label>
                <input type="text" id="first_name" name="first_name" placeholder="<?php echo $_page_lang['account.enter_first_name']; ?>" value="<?php echo $_page_data['account']['first_name']; ?>" data-validation="required">
            </div>
            <div class="row right">
                <label for="last_name" class="none">&nbsp;</label>
                <input type="text" id="last_name" name="last_name" placeholder="<?php echo $_page_lang['account.enter_last_name']; ?>" value="<?php echo $_page_data['account']['last_name']; ?>" data-validation="required">
            </div>
            <div class="clearboth"></div>
            
            <div class="row">
                <label for="email"><?php echo $_page_lang['account.email']; ?> <span style="color:red;">*</span></label>
                <input type="text" id="email" name="email" placeholder="<?php echo $_page_lang['account.enter_email']; ?>" value="<?php echo $_page_data['account']['email']; ?>" data-validation="required|email">
            </div>
            <div class="clearboth"></div>
            
            <div class="row">
                <label for="password"><?php echo $_page_lang['account.password']; ?> <span style="color:red;">*</span></label>
                <input type="password" id="password" name="password" placeholder="<?php echo $_page_lang['account.enter_password']; ?>" value="<?php echo $_page_data['account']['password']; ?>" data-validation="required|password">
            </div>
            <div class="clearboth"></div>
            
            <div class="row">
                <label for="repeat_password"><?php echo $_page_lang['account.re_password']; ?> <span style="color:red;">*</span></label>
                <input type="password" id="repeat_password" name="repeat_password" placeholder="<?php echo $_page_lang['account.enter_password']; ?>" value="<?php echo $_page_data['account']['repeat_password']; ?>" data-validation="required|password">
            </div>
            <div class="clearboth"></div>
            
            <?php /*
            <div class="or">
                <span><?php echo $_page_lang['account.or']; ?></span>
            </div>
            
            <div class="third-party"><!--
                --><a href="#" class="google" data-method="2">
                    <i class="fa fa-google"></i>
                    <span><?php echo $_page_lang['account.with_google_up']; ?></span>
                </a><!--
                --><a href="#" class="fb" data-method="3">
                    <i class="fa fa-facebook-square"></i>
                    <span><?php echo $_page_lang['account.with_fb_up']; ?></span>
                </a><!--
                --><a href="#" class="apple" data-method="4">
                    <i class="fa fa-apple"></i>
                    <span><?php echo $_page_lang['account.with_apple_up']; ?></span>
                </a><!--
            --></div>
             * 
             */
            ?>

            <div class="row agree">
                <div class="iweb-checkbox-set">
                    <input type="checkbox" id="agree_to" name="agree_to" value="1" data-validation="required">
                    <label for="agree_to"><?php echo str_replace('{link_1}', $_page_base_url.'/privacy_statement', $_page_lang['agree_to']); ?></label>
                </div>
            </div>
            
            <div class="action">
                <a class="btn btn-back" href="<?php echo $_page_base_url.'/account_registration' ;?>"><?php echo $_page_lang['btn.back']; ?></a>
                <button type="submit" class="btn btn-next"><?php echo $_page_lang['btn.continue']; ?></button>
                <div class="clearboth"></div>
            </div>
        </form>
    </div>
    
    <?php } else { ?>
    
    <?php
    $_page_data['preference'] = array_merge([
        'migration_destination'  => 0,
        'interested_visa'        => 0,
        'interested_topic'       => []
    ], ((!empty($_page_data['preference']))?$_page_data['preference']:[]))
    ?>
    
    <h1 class="title"><?php echo $_page_lang['choose_your_preference']; ?></h1>
    <div class="underline"></div>
    <div class="clearboth"></div>

    <div class="form">
        <form id="account-individual-preference-form" method="post">
            <div>@csrf</div>

            <div class="required"><span style="color:red;">*</span> <?php echo $_page_lang['required']; ?></div>
            <div class="clearboth"></div>
            
            <div class="row">
                <label for="migration_destination"><?php echo $_page_lang['account.migration_destination']; ?></label>
                <select id="migration_destination" name="migration_destination">
                    <option value=""><?php echo $_page_lang['please_select']; ?></option>
                    <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                    <option value="<?php echo $country_id; ?>"<?php echo ($_page_data['preference']['migration_destination']==$country_id)?' selected':'';?>><?php echo $country; ?></option>
                    <?php }} ?>
                </select>
            </div>
            <div class="clearboth"></div>
            
            <div class="row">
                <label for="interested_visa"><?php echo $_page_lang['account.interested_visa']; ?> <span style="color:red;">*</span></label>
                <select id="interested_visa" name="interested_visa" data-validation="required">
                    <option value=""><?php echo $_page_lang['please_select']; ?></option>
                    <?php if(!empty($_page_options['interest_visas'])) { foreach ($_page_options['interest_visas'] as $interest_visas_id => $interest_visas) { ?>
                    <option value="<?php echo $interest_visas_id; ?>"<?php echo ($_page_data['preference']['interested_visa']==$interest_visas_id)?' selected':'';?>><?php echo $interest_visas; ?></option>
                    <?php }} ?>
                </select>
            </div>
            <div class="clearboth"></div>
            
            <div class="row">
                <label for="interested_topic"><?php echo $_page_lang['account.interested_topic']; ?> <span style="color:red;">*</span></label>
                <div class="iweb-checkbox-set">
                    <?php if(!empty($_page_options['interest_topics'])) { foreach ($_page_options['interest_topics'] as $interest_topics_id => $interest_topics) { ?>
                    <input type="checkbox" id="interested_topic_<?php echo $interest_topics_id; ?>" name="interested_topic[]" value="<?php echo $interest_topics_id; ?>"<?php echo (in_array($interest_topics_id, $_page_data['preference']['interested_topic']))?' checked':'';?> data-validation="required">
                    <label for="interested_topic_<?php echo $interest_topics_id; ?>"><?php echo $interest_topics; ?></label>
                    <?php }} ?>
                </div>
            </div>
            <div class="clearboth"></div>
            
            <div class="action">
                <a class="btn btn-back" href="<?php echo $_page_base_url.'/account_registration/individual' ;?>"><?php echo $_page_lang['btn.back']; ?></a>
                <button type="submit" class="btn btn-next"><?php echo $_page_lang['btn.submit']; ?></button>
                <div class="clearboth"></div>
            </div>
        </form>
    </div>
    
    <?php } ?>
</div>
@endsection