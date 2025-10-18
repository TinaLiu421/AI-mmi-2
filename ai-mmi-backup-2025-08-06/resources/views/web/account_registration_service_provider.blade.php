@extends('web.common')
@section('content')
<div class="inner-panel">
    <?php
    $_page_data['account'] = array_merge([
        'logo'              =>  '',
        'company_type'      =>  0,
        'company_name'      =>  '',
        'company_website'   =>  '',
        'company_address'   =>  '',
        'first_name'        =>  '',
        'last_name'         =>  '',
        'email'             =>  '',
        'telephone_code'    =>  '852',
        'telephone_num'     =>  '',
        'password'          =>  '',
        'repeat_password'   =>  ''
    ], ((!empty($_page_data['account']))?$_page_data['account']:[]))
    ?>
    <h1 class="title-small"><?php echo $_page_data['plan']['title']; ?></h1>
    <h1 class="title"><?php echo $_page_lang['create_your_account']; ?></h1>
    <div class="underline"></div>
    <div class="clearboth"></div>
    
    <div class="form">
        <form id="account-service-provider-form" method="post" enctype="multipart/form-data">
            <div>@csrf</div>
            <div><input type="hidden" id="method" name="method" value="1"></div>
            <div><input type="hidden" id="third_party_token" name="third_party_token" value=""></div>
            
            <div class="required"><span style="color:red;">*</span> <?php echo $_page_lang['required']; ?></div>
            <div class="clearboth"></div>
            
            <div class="group-title"><u><?php echo $_page_lang['account.company_info']; ?></u></div>
            
            <div class="row">
                <label for="logo"><?php echo $_page_lang['account.add_logo']; ?></label>
                <div class="logo-file">
                    <img src="asset/image/default_logo.jpg" alt="default_logo">
                    <div class="preview">
                        <?php if(!empty($_page_data['account']['logo'])) { ?>
                        <img src="<?php echo 'upload/member_logo/'.$_page_data['account']['logo']; ?>">
                        <?php } ?>
                    </div>
                    <div class="select">
                        <input type="file" id="mylogo" name="mylogo" accept="image/*">
                    </div>
                </div>
            </div>
            <div class="clearboth"></div>
            
            
            <div class="row">
                <label for="company_name"><?php echo $_page_lang['account.company_name_2']; ?> <span style="color:red;">*</span> <small style="text-transform:none;color:#aaa;"><?php echo $_page_lang['max_company_name']; ?></small></label>
                <input type="text" id="company_name" name="company_name" placeholder="<?php echo $_page_lang['account.enter_company_name']; ?>" value="<?php echo $_page_data['account']['company_name']; ?>" data-validation="required">
            </div>
            <div class="clearboth"></div>
            
            <div class="row">
                <label for="company_type"><?php echo $_page_lang['account.company_type']; ?> <span style="color:red;">*</span> <small style="text-transform:none;color:#aaa;"><?php echo $_page_lang['max_company_name']; ?></small></label>
                <select id="company_type" name="company_type" data-validation="required">
                    <option value=""><?php echo $_page_lang['please_select']; ?></option>
                    <?php if(!empty($_page_options['organization_type'])) { foreach ($_page_options['organization_type'] as $organization_type_id => $organization_type) { ?>
                    <option value="<?php echo $organization_type_id; ?>"<?php echo ($_page_data['account']['company_type']==$organization_type_id)?' selected':'';?>><?php echo $organization_type; ?></option>
                    <?php }} ?>
                </select>
            </div>
            <div class="clearboth"></div>
            
            <div class="row">
                <label for="company_website"><?php echo $_page_lang['account.company_website']; ?> <span style="color:red;">*</span></label>
                <input type="text" id="company_website" name="company_website" placeholder="<?php echo $_page_lang['account.enter_company_website']; ?>" value="<?php echo $_page_data['account']['company_website']; ?>" data-validation="required">
            </div>
            <div class="clearboth"></div>
            
            <div class="row">
                <label for="company_address"><?php echo $_page_lang['account.company_address']; ?> <span style="color:red;">*</span></label>
                <textarea id="company_address" name="company_address" rows="3" placeholder="<?php echo $_page_lang['account.enter_company_address']; ?>" data-validation="required"><?php echo $_page_data['account']['company_address']; ?></textarea>
            </div>
            <div class="clearboth"></div>
            
            <div>&nbsp;</div>
            
            <div class="group-title"><u><?php echo $_page_lang['account.contact_info']; ?></u></div>
            
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
                <label for="telephone"><?php echo $_page_lang['account.telephone']; ?> <span style="color:red;">*</span></label>
                <table class="telephone">
                    <tr>
                        <td><input type="text" id="telephone" name="telephone_code" placeholder="+852" value="+<?php echo preg_replace('/^(\+)(.*)/i', '$2', $_page_data['account']['telephone_code']); ?>" data-validation="required"></td>
                        <td><input type="text" id="telephone" name="telephone_num" placeholder="<?php echo $_page_lang['account.enter_telephone']; ?>" value="<?php echo $_page_data['account']['telephone_num']; ?>" data-validation="required"></td>
                    </tr>
                </table>
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

            <div class="row agree">
                <div class="iweb-checkbox-set">
                    <input type="checkbox" id="agree_to" name="agree_to" value="1" data-validation="required">
                    <label for="agree_to"><?php echo str_replace('{link_1}', $_page_base_url.'/privacy_statement', $_page_lang['agree_to']); ?></label>
                </div>
            </div>
            
            <div class="action">
                <a class="btn btn-back" href="<?php echo $_page_base_url.'/account_registration' ;?>"><?php echo $_page_lang['btn.back']; ?></a>
                <button type="submit" class="btn btn-next"><?php echo $_page_lang['btn.submit']; ?></button>
                <div class="clearboth"></div>
            </div>
        </form>
    </div>
</div>
@endsection