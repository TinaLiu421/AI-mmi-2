@extends('web.common')
@section('content')
<div class="inner-panel">
    <?php
    $_page_data['account'] = array_merge([
        'logo'              =>  '',
        'company_type'      =>  0,
        'company_name'      =>  '',
        'country'           =>  '',
        'company_website'   =>  '',
        'company_address'   =>  '',
        'first_name'        =>  '',
        'last_name'         =>  '',
        'email'             =>  '',
        'telephone_code'    =>  '852',
        'telephone_num'     =>  '',
        'password'          =>  '',
        'repeat_password'   =>  '',
        'registered_business_country' => '',
        'registered_business_name' => '',
        'registered_business_number' => ''
    ], ((!empty($_page_data['account']))?$_page_data['account']:[]))
    ?>
    <?php
    $_business_licenses = (!empty($_page_data['business_licenses']))?$_page_data['business_licenses']:[];
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
                <label for="company_type"><?php echo $_page_lang['account.company_type']; ?> <span style="color:red;">*</span></label>
                <select id="company_type" name="company_type" data-validation="required">
                    <option value=""><?php echo $_page_lang['please_select']; ?></option>
                    <?php if(!empty($_page_options['organization_type'])) { foreach ($_page_options['organization_type'] as $organization_type_id => $organization_type) { ?>
                    <option value="<?php echo $organization_type_id; ?>"<?php echo ($_page_data['account']['company_type']==$organization_type_id)?' selected':'';?>><?php echo $organization_type; ?></option>
                    <?php }} ?>
                </select>
            </div>
            <div class="clearboth"></div>
            
            <div class="row">
                <label for="country"><?php echo $_page_lang['account.country']; ?> <span style="color:red;">*</span></label>
                <select id="country" name="country" data-validation="required">
                    <option value=""><?php echo $_page_lang['please_select']; ?></option>
                    <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_code => $country_name) { ?>
                    <option value="<?php echo $country_code; ?>"<?php echo (!empty($_page_data['account']['country']) && $_page_data['account']['country']==$country_code)?' selected':'';?>><?php echo $country_name; ?></option>
                    <?php }} ?>
                </select>
            </div>

            <div class="row">
                <label for="services_country"><?php echo $_page_lang['account.services_country']; ?> <span style="color:red;">*</span></label>
                <select id="services_country" name="services_country[]" multiple="multiple" data-validation="required">
                    <option value=""><?php echo $_page_lang['please_select']; ?></option>
                    <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_code => $country_name) { ?>
                    <option value="<?php echo $country_code; ?>"<?php echo (!empty($_page_data['account']['services_country']) && in_array($country_code, (array)$_page_data['account']['services_country']))?' selected':'';?>><?php echo $country_name; ?></option>
                    <?php }} ?>
                </select>
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="company_website"><?php echo $_page_lang['account.company_website']; ?></label>
                <input type="text" id="company_website" name="company_website" placeholder="<?php echo $_page_lang['account.enter_company_website']; ?>" value="<?php echo $_page_data['account']['company_website']; ?>">
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="company_address"><?php echo $_page_lang['account.company_address']; ?></label>
                <input type="text" id="company_address" name="company_address" rows="3" placeholder="<?php echo $_page_lang['account.enter_company_address']; ?>"><?php echo $_page_data['account']['company_address']; ?>
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="services"><?php echo $_page_lang['account.services']; ?></label>
                <textarea id="services" name="services" rows="3" placeholder="<?php echo $_page_lang['account.enter_services']; ?>"><?php echo (!empty($_page_data['account']['services']) ? $_page_data['account']['services'] : ''); ?></textarea>
            </div>
            <div class="clearboth"></div>

            <div>&nbsp;</div>

            <div class="group-title"><u><?php echo $_page_lang['account.business_registration']; ?></u> <span style="font-size: 0.8em; font-weight: normal;">(<?php echo $_page_lang['if_applicable']; ?>)</span></div>

            <div class="row">
                <label for="registered_business_country"><?php echo $_page_lang['account.business_registration_country']; ?></label>
                <select id="registered_business_country" name="registered_business_country">
                    <option value=""><?php echo $_page_lang['please_select']; ?></option>
                    <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_code => $country_name) { ?>
                    <option value="<?php echo $country_code; ?>"<?php echo (!empty($_page_data['account']['registered_business_country']) && $_page_data['account']['registered_business_country']==$country_code)?' selected':'';?>><?php echo $country_name; ?></option>
                    <?php }} ?>
                </select>
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="registered_business_name"><?php echo $_page_lang['account.business_registration_name']; ?></label>
                <input type="text" id="registered_business_name" name="registered_business_name" placeholder="<?php echo $_page_lang['account.enter_business_registration_name']; ?>" value="<?php echo $_page_data['account']['registered_business_name']; ?>">
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="registered_business_number"><?php echo $_page_lang['account.business_registration_number']; ?></label>
                <input type="text" id="registered_business_number" name="registered_business_number" placeholder="<?php echo $_page_lang['account.enter_business_registration_number']; ?>" value="<?php echo $_page_data['account']['registered_business_number']; ?>">
            </div>
            <div class="clearboth"></div>

            <div>&nbsp;</div>

            <div class="group-title"><u><?php echo $_page_lang['account.business_license']; ?></u> <span style="font-size: 0.8em; font-weight: normal;">(<?php echo $_page_lang['if_applicable']; ?>)</span></div>

            <div class="child-business-license">
                <div class="items">
                    <div class="block hidden" style="display:none;">
                        <a class="remove-license-block"><i class="fa fa-times"></i></a>

                        <input type="hidden" name="license_id[]" value="0" disabled>

                        <div class="num"><?php echo $_page_lang['account.business_license']; ?><span> - 0</span></div>

                        <div class="row left">
                            <label for="license_country"><?php echo $_page_lang['account.business_license_country']; ?></label>
                            <select name="license_country[]" disabled>
                                <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                <option value="<?php echo $country_id; ?>"><?php echo $country; ?></option>
                                <?php }} ?>
                            </select>
                        </div>
                        <div class="row right">
                            <label for="issuing_authority"><?php echo $_page_lang['account.issuing_authority']; ?></label>
                            <input type="text" name="issuing_authority[]" placeholder="<?php echo $_page_lang['account.enter_issuing_authority']; ?>" disabled>
                        </div>
                        <div class="clearboth"></div>

                        <div class="row left">
                            <label for="type_of_registration"><?php echo $_page_lang['account.type_of_registration']; ?></label>
                            <input type="text" name="type_of_registration[]" placeholder="<?php echo $_page_lang['account.enter_type_of_registration']; ?>" disabled>
                        </div>
                        <div class="row right">
                            <label for="registration_number"><?php echo $_page_lang['account.business_license_number']; ?></label>
                            <input type="text" name="registration_number[]" placeholder="<?php echo $_page_lang['account.enter_business_license_number']; ?>" disabled>
                        </div>
                        <div class="clearboth"></div>
                    </div>

                    <?php if(!empty($_business_licenses)) { foreach ($_business_licenses as $license_key => $license) { ?>
                    <div class="block">
                        <a class="remove-license-block"><i class="fa fa-times"></i></a>

                        <input type="hidden" name="license_id[]" value="<?php echo (int)$license['id']; ?>">

                        <div class="num"><?php echo $_page_lang['account.business_license']; ?><span> - <?php echo $license_key+1;?></span></div>

                        <div class="row left">
                            <label for="license_country"><?php echo $_page_lang['account.business_license_country']; ?></label>
                            <select name="license_country[]">
                                <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                <option value="<?php echo $country_id; ?>"<?php echo ($license['license_country']==$country_id)?' selected':'';?>><?php echo $country; ?></option>
                                <?php }} ?>
                            </select>
                        </div>
                        <div class="row right">
                            <label for="issuing_authority"><?php echo $_page_lang['account.issuing_authority']; ?></label>
                            <input type="text" name="issuing_authority[]" placeholder="<?php echo $_page_lang['account.enter_issuing_authority']; ?>" value="<?php echo $license['issuing_authority']; ?>">
                        </div>
                        <div class="clearboth"></div>

                        <div class="row left">
                            <label for="type_of_registration"><?php echo $_page_lang['account.type_of_registration']; ?></label>
                            <input type="text" name="type_of_registration[]" placeholder="<?php echo $_page_lang['account.enter_type_of_registration']; ?>" value="<?php echo $license['type_of_registration']; ?>">
                        </div>
                        <div class="row right">
                            <label for="registration_number"><?php echo $_page_lang['account.business_license_number']; ?></label>
                            <input type="text" name="registration_number[]" placeholder="<?php echo $_page_lang['account.enter_business_license_number']; ?>" value="<?php echo $license['registration_number']; ?>">
                        </div>
                        <div class="clearboth"></div>
                    </div>
                    <?php } } else { ?>
                    <div class="block">
                        <a class="remove-license-block"><i class="fa fa-times"></i></a>

                        <input type="hidden" name="license_id[]" value="0">

                        <div class="num"><?php echo $_page_lang['account.business_license']; ?><span> - 1</span></div>

                        <div class="row left">
                            <label for="license_country"><?php echo $_page_lang['account.business_license_country']; ?></label>
                            <select name="license_country[]">
                                <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                <option value="<?php echo $country_id; ?>"><?php echo $country; ?></option>
                                <?php }} ?>
                            </select>
                        </div>
                        <div class="row right">
                            <label for="issuing_authority"><?php echo $_page_lang['account.issuing_authority']; ?></label>
                            <input type="text" name="issuing_authority[]" placeholder="<?php echo $_page_lang['account.enter_issuing_authority']; ?>">
                        </div>
                        <div class="clearboth"></div>

                        <div class="row left">
                            <label for="type_of_registration"><?php echo $_page_lang['account.type_of_registration']; ?></label>
                            <input type="text" name="type_of_registration[]" placeholder="<?php echo $_page_lang['account.enter_type_of_registration']; ?>">
                        </div>
                        <div class="row right">
                            <label for="registration_number"><?php echo $_page_lang['account.business_license_number']; ?></label>
                            <input type="text" name="registration_number[]" placeholder="<?php echo $_page_lang['account.enter_business_license_number']; ?>">
                        </div>
                        <div class="clearboth"></div>
                    </div>
                    <?php } ?>
                </div>
                <div class="row">
                    <a class="add-license-block" href="#">
                        <span><?php echo $_page_lang['account.add_business_license']; ?></span>
                        <i class="fa fa-plus-circle"></i>
                    </a>
                </div>
            </div>

            <div>&nbsp;</div>

            <div class="group-title"><u><?php echo $_page_lang['account.contact_info']; ?></u></div>
            
            <div class="row">
                <label for="first_name"><?php echo $_page_lang['account.name']; ?> <span style="color:red;">*</span></label>
                <input type="text" id="first_name" name="first_name" placeholder="<?php echo $_page_lang['account.enter_first_name']; ?>" value="<?php echo $_page_data['account']['first_name']; ?>" data-validation="required">
            </div>
            <div class="row">
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
                        <td>
                            <select id="telephone_code" name="telephone_code" data-validation="required">
                                <option value="">Code</option>
                                <?php
                                $current_code = preg_replace('/^(\+)(.*)/i', '$2', $_page_data['account']['telephone_code']);
                                if(!empty($_page_options['phone_codes'])) {
                                    foreach ($_page_options['phone_codes'] as $code => $label) {
                                ?>
                                <option value="<?php echo $code; ?>"<?php echo ($current_code==$code)?' selected':'';?>><?php echo $label; ?></option>
                                <?php }} ?>
                            </select>
                        </td>
                        <td><input type="text" id="telephone" name="telephone_num" placeholder="<?php echo $_page_lang['account.enter_telephone']; ?>" value="<?php echo $_page_data['account']['telephone_num']; ?>" data-validation="required"></td>
                    </tr>
                </table>
            </div>
            <div class="clearboth"></div>
            
            <div class="row">
                <label for="password"><?php echo $_page_lang['account.password']; ?> <span style="color:red;">*</span></label>
                <input type="password" id="password" name="password" placeholder="<?php echo $_page_lang['account.enter_password']; ?>" value="<?php echo $_page_data['account']['password']; ?>" data-validation="required|password">
            </div>
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

<script>

const countryPhoneMap = <?php echo json_encode($_page_options['country_phone_map'] ?? []); ?>;

document.addEventListener('DOMContentLoaded', function() {
    const countrySelect = document.getElementById('country');
    const phoneCodeSelect = document.getElementById('telephone_code');
    const servicesCountrySelect = document.getElementById('services_country');
    const registeredBusinessCountrySelect = document.getElementById('registered_business_country');

    // Auto-fill phone code and business registration country when country of operation changes
    if (countrySelect && phoneCodeSelect) {
        countrySelect.addEventListener('change', function() {
            const selectedCountry = this.value;
            if (selectedCountry && countryPhoneMap[selectedCountry]) {
                const phoneCode = countryPhoneMap[selectedCountry];
                phoneCodeSelect.value = phoneCode;
            }
            // Auto-fill business registration country from country of operation
            if (selectedCountry && registeredBusinessCountrySelect) {
                registeredBusinessCountrySelect.value = selectedCountry;
            }
        });
    }

});
</script>
@endsection