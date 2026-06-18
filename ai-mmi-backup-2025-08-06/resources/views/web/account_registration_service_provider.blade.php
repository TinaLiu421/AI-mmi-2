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
    <?php if (!empty($_current_member) && (int)($_current_member['type'] ?? 0) === 1): ?>
    <div style="margin:16px 0;padding:14px 16px;background:#fff8e1;border:1px solid #ffe082;border-radius:8px;font-size:0.92rem;line-height:1.5;">
        You are signed in as a personal account (<strong><?php echo htmlspecialchars($_current_member['email'] ?? '', ENT_QUOTES); ?></strong>).
        To register as a service provider, use a <strong>different email</strong> or
        <a href="<?php echo htmlspecialchars($_page_base_url.'/account/logout', ENT_QUOTES); ?>"><u>sign out</u></a> first.
    </div>
    <?php endif; ?>

    <div class="institution-type-selector">
        <p class="institution-type-label">Select Institution Type <span style="color:red;">*</span></p>
        <div class="institution-type-cards">
            <div class="institution-type-card selected" data-value="1" onclick="selectInstitutionType(1)">
                <div class="institution-type-icon">🏛️</div>
                <div class="institution-type-name">Migration Institution</div>
                <div class="institution-type-desc">Immigration agencies, law firms, migration consultants</div>
            </div>
            <div class="institution-type-card" data-value="2" onclick="selectInstitutionType(2)">
                <div class="institution-type-icon">🎓</div>
                <div class="institution-type-name">Education Institution</div>
                <div class="institution-type-desc">Universities, colleges, schools, training providers</div>
            </div>
        </div>
    </div>

    <div class="form">
        <form id="account-service-provider-form" method="post" enctype="multipart/form-data">
            <div>@csrf</div>
            <div><input type="hidden" id="method" name="method" value="1"></div>
            <div><input type="hidden" id="third_party_token" name="third_party_token" value=""></div>
            <div><input type="hidden" id="institution_type" name="institution_type" value="1"></div>
            
            <div class="required"><span style="color:red;">*</span> <?php echo $_page_lang['required']; ?></div>
            <div class="clearboth"></div>
            
            <div class="group-title"><u><?php echo $_page_lang['account.company_info']; ?></u></div>
            
            <div class="row sp-migration-only">
                <label for="logo"><?php echo $_page_lang['account.add_logo']; ?></label>
                <div class="logo-file">
                    <img src="asset/image/default_logo.jpg" alt="default_logo">
                    <div class="preview">
                        <?php if(!empty($_page_data['account']['logo'])) { ?>
                        <img src="<?php echo 'upload/member_logo/'.$_page_data['account']['logo']; ?>">
                        <?php } ?>
                    </div>
                    <div class="select">
                        <input type="file" id="mylogo" name="mylogo" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml,.svg">
                    </div>
                </div>
            </div>
            <div class="clearboth"></div>
            
            
            <div class="row left">
                <label for="company_name"><?php echo $_page_lang['account.company_name_2']; ?> <span style="color:red;">*</span> <small style="text-transform:none;color:#aaa;"><?php echo $_page_lang['max_company_name']; ?></small></label>
                <input type="text" id="company_name" name="company_name" placeholder="<?php echo $_page_lang['account.enter_company_name']; ?>" value="<?php echo $_page_data['account']['company_name']; ?>" data-validation="required">
            </div>
            <div class="row right">
                <label for="company_type"><?php echo $_page_lang['account.company_type']; ?> <span style="color:red;">*</span></label>
                <select id="company_type" name="company_type" data-validation="required">
                    <option value=""><?php echo $_page_lang['please_select']; ?></option>
                    <?php if(!empty($_page_options['organization_type'])) { foreach ($_page_options['organization_type'] as $organization_type_id => $organization_type) { ?>
                    <option value="<?php echo $organization_type_id; ?>"<?php echo ($_page_data['account']['company_type']==$organization_type_id)?' selected':'';?>><?php echo $organization_type; ?></option>
                    <?php }} ?>
                </select>
            </div>
            <div class="clearboth"></div>
            
            <div class="sp-migration-only">
            <div class="row left">
                <label for="country"><?php echo $_page_lang['account.country']; ?> <span style="color:red;">*</span></label>
                <select id="country" name="country" data-validation="required">
                    <option value=""><?php echo $_page_lang['please_select']; ?></option>
                    <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_code => $country_name) { ?>
                    <option value="<?php echo $country_code; ?>"<?php echo (!empty($_page_data['account']['country']) && $_page_data['account']['country']==$country_code)?' selected':'';?>><?php echo $country_name; ?></option>
                    <?php }} ?>
                </select>
            </div>
            <div class="row right">
                <label for="services_country"><?php echo $_page_lang['account.services_country']; ?> <span style="color:red;">*</span></label>
                <select id="services_country" name="services_country[]" multiple="multiple" data-validation="required">
                    <option value=""><?php echo $_page_lang['please_select']; ?></option>
                    <option value="all">All Countries</option>
                    <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_code => $country_name) { ?>
                    <option value="<?php echo $country_code; ?>"<?php echo (!empty($_page_data['account']['services_country']) && in_array($country_code, (array)$_page_data['account']['services_country']))?' selected':'';?>><?php echo $country_name; ?></option>
                    <?php }} ?>
                </select>
            </div>
            <div class="clearboth"></div>
            </div>

            <div class="row">
                <label for="company_website"><?php echo $_page_lang['account.company_website']; ?> <span style="color:red;">*</span></label>
                <input type="text" id="company_website" name="company_website" placeholder="<?php echo $_page_lang['account.enter_company_website']; ?>" value="<?php echo $_page_data['account']['company_website']; ?>" data-validation="required">
            </div>
            <div class="clearboth"></div>

            <div class="row sp-migration-only">
                <label for="company_address"><?php echo $_page_lang['account.company_address']; ?></label>
                <input type="text" id="company_address" name="company_address" rows="3" placeholder="<?php echo $_page_lang['account.enter_company_address']; ?>"><?php echo $_page_data['account']['company_address']; ?>
            </div>
            <div class="clearboth"></div>

            <div class="sp-migration-only">
            <div class="row">
                <label for="services"><?php echo $_page_lang['account.services']; ?></label>
                <textarea id="services" name="services" rows="3" placeholder="<?php echo $_page_lang['account.enter_services']; ?>"><?php echo (!empty($_page_data['account']['services']) ? $_page_data['account']['services'] : ''); ?></textarea>
            </div>
            <div class="clearboth"></div>

            <div>&nbsp;</div>

            <div class="group-title"><u><?php echo $_page_lang['account.business_registration']; ?></u> <span style="font-size: 0.8em; font-weight: normal;">(<?php echo $_page_lang['if_applicable']; ?>)</span></div>

            <div class="row left">
                <label for="registered_business_country"><?php echo $_page_lang['account.business_registration_country']; ?></label>
                <select id="registered_business_country" name="registered_business_country">
                    <option value=""><?php echo $_page_lang['please_select']; ?></option>
                    <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_code => $country_name) { ?>
                    <option value="<?php echo $country_code; ?>"<?php echo (!empty($_page_data['account']['registered_business_country']) && $_page_data['account']['registered_business_country']==$country_code)?' selected':'';?>><?php echo $country_name; ?></option>
                    <?php }} ?>
                </select>
            </div>
            <div class="row right">
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
            </div><!-- end sp-migration-only -->

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
            
            <div class="row left">
                <label for="telephone_code"><?php echo $_page_lang['account.telephone']; ?> <span style="color:red;">*</span></label>
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
            </div>
            <div class="row right">
                <label for="telephone" class="none">&nbsp;</label>
                <input type="text" id="telephone" name="telephone_num" placeholder="<?php echo $_page_lang['account.enter_telephone']; ?>" value="<?php echo $_page_data['account']['telephone_num']; ?>" data-validation="required">
            </div>
            <div class="clearboth"></div>
            
            <div class="row left">
                <label for="password"><?php echo $_page_lang['account.password']; ?> <span style="color:red;">*</span></label>
                <input type="password" id="password" name="password" placeholder="<?php echo $_page_lang['account.enter_password']; ?>" value="<?php echo $_page_data['account']['password']; ?>" data-validation="required|password">
            </div>
            <div class="row right">
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

    // Handle "All Countries" option using jQuery
    if (jQuery && jQuery('#services_country').length) {
        const $select = jQuery('#services_country');

        $select.on('change', function() {
            const $this = jQuery(this);
            const allCountryValues = [];

            // Get all country values (not empty, not "all")
            $this.find('option').each(function() {
                const val = jQuery(this).val();
                if (val !== '' && val !== 'all') {
                    allCountryValues.push(val);
                }
            });

            // Get currently selected values
            const selected = $this.val() || [];

            // Check if "all" was clicked
            const allIsSelected = selected.includes('all');

            if (allIsSelected) {
                // Check if all countries were already selected
                const allCountriesWereSelected = allCountryValues.length > 0 && allCountryValues.every(val => selected.includes(val));

                if (allCountriesWereSelected) {
                    // If all were already selected, deselect all
                    console.log('All clicked when all selected - deselecting all');
                    $this.find('option').prop('selected', false);
                } else {
                    // Otherwise select all individual countries
                    console.log('All clicked - selecting all countries');
                    $this.find('option').prop('selected', function() {
                        const val = jQuery(this).val();
                        return val !== '' && val !== 'all';
                    });
                }
            } else {
                // Check if all countries are selected
                const allCountriesSelected = allCountryValues.length > 0 && allCountryValues.every(val => selected.includes(val));

                if (allCountriesSelected) {
                    // Automatically check "all"
                    console.log('All countries selected - checking all option');
                    $this.find('option[value="all"]').prop('selected', true);
                }
            }
        });

        // Before form submission, remove "all" from the selected values
        jQuery('#account-service-provider-form').on('submit', function() {
            $select.find('option[value="all"]').prop('selected', false);
        });
    }

});
</script>

<style>
.institution-type-selector {
    margin: 20px 0 30px 0;
}
.institution-type-label {
    font-weight: 600;
    font-size: 1em;
    margin-bottom: 12px;
    color: #333;
}
.institution-type-cards {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}
.institution-type-card {
    flex: 1;
    min-width: 200px;
    max-width: 280px;
    border: 2px solid #ddd;
    border-radius: 10px;
    padding: 20px 18px;
    cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    background: #fff;
    text-align: center;
}
.institution-type-card:hover {
    border-color: #4a90d9;
    box-shadow: 0 2px 10px rgba(74,144,217,0.15);
}
.institution-type-card.selected {
    border-color: #1a73e8;
    background: #f0f6ff;
    box-shadow: 0 2px 12px rgba(26,115,232,0.2);
}
.institution-type-icon {
    font-size: 2em;
    margin-bottom: 8px;
}
.institution-type-name {
    font-weight: 700;
    font-size: 1em;
    color: #1a1a2e;
    margin-bottom: 6px;
}
.institution-type-desc {
    font-size: 0.82em;
    color: #666;
    line-height: 1.4;
}
</style>

<script>
function selectInstitutionType(value) {
    document.querySelectorAll('.institution-type-card').forEach(function(card) {
        card.classList.remove('selected');
    });
    document.querySelector('.institution-type-card[data-value="' + value + '"]').classList.add('selected');
    document.getElementById('institution_type').value = value;

    var isEdu = parseInt(value) === 2;
    document.querySelectorAll('.sp-migration-only').forEach(function(el) {
        if (isEdu) {
            el.style.display = 'none';
            el.querySelectorAll('input, select, textarea').forEach(function(inp) {
                inp.disabled = true;
                // Remove data-validation so the iweb client validator does not
                // block submission for hidden/disabled fields
                var dv = inp.getAttribute('data-validation');
                if (dv) {
                    inp.setAttribute('data-orig-validation', dv);
                    inp.removeAttribute('data-validation');
                }
            });
        } else {
            el.style.display = '';
            el.querySelectorAll('input, select, textarea').forEach(function(inp) {
                inp.disabled = false;
                // Restore data-validation when fields become visible again
                var dv = inp.getAttribute('data-orig-validation');
                if (dv) {
                    inp.setAttribute('data-validation', dv);
                    inp.removeAttribute('data-orig-validation');
                }
            });
        }
    });
}
</script>
@endsection