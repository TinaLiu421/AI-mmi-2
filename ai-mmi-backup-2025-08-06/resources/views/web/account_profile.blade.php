@extends('web.common')
@section('content')
<?php $_show_current_member = $_page_data['show_current_member']; ?>
<?php $_show_current_member_details = (!empty($_page_data['current_member_details']))?$_page_data['current_member_details']:[]; ?>
<?php $_show_current_member_agent = (!empty($_page_data['current_member_agent']))?$_page_data['current_member_agent']:[]; ?>
<?php $_show_current_member_lawfirm = (!empty($_page_data['current_member_lawfirm']))?$_page_data['current_member_lawfirm']:[]; ?>
<?php $_show_current_member_business_license = (!empty($_page_data['current_member_business_license']))?$_page_data['current_member_business_license']:[]; ?>
<div class="inner-panel full">
    <?php if(!empty($_show_current_member['coverphoto']) && file_exists('upload/member_coverphoto/'.$_show_current_member['coverphoto'])) { ?>
    <div class="banner" style="background-image:url('<?php echo 'upload/member_coverphoto/'.$_show_current_member['coverphoto']; ?>')"></div>
    <?php } else { ?>
    <div class="banner" style="display:none;"></div>
    <?php } ?>
    <div class="basic">
        <div class="photo">
            <img src="asset/image/icon-member.png" alt="icon-member"/>
            <?php if(file_exists('upload/member_avatar/'.$_show_current_member['avatar'])) { ?>
            <div class="avatar" style="background-image:url('<?php echo 'upload/member_avatar/'.$_show_current_member['avatar']; ?>')"></div>
            <?php } else { ?>
            <div class="avatar" style="background-image:url('<?php echo 'upload/member_logo/'.$_show_current_member['avatar']; ?>')"></div>
            <?php } ?>
            <?php if(empty($_page_data['is_readonly'])) { ?>
            <a id="myavatar" class="camera"><i class="fa fa-camera"></i></a>
            <?php } ?>
        </div>
        <div class="name">
            <div class="alias">
                <div class="readonly">
                    <span><?php echo $_show_current_member['alias_name']; ?></span>
                </div>
                <?php if(empty($_page_data['is_readonly'])) { ?>
                <a><img src="asset/image/icon-edit.png"></a>
                <?php } ?>
            </div>
            <div class="total-followers">0 followers</div>
        </div>
        <div class="clearboth"></div>
        <div class="tab">
            <?php if((int)$_show_current_member['type'] > 1) { ?>
            <a class="posts" href="<?php echo $_page_base_url.'/account/posts'.((!empty($_page_get_data['uid']))?'?uid='.$_page_get_data['uid']:''); ?>"><?php echo $_page_lang['tab_posts']; ?></a>
            <?php } ?>
            <a class="about selected"><?php echo $_page_lang['tab_about']; ?></a>
        </div>
    </div>
    
    <?php if(empty($_page_data['is_readonly'])) { ?>
    <div class="tab-details">
        <div class="form">
            <form id="account-profile-form" method="post" enctype="multipart/form-data">
                <div>@csrf</div>
                
                <div class="ac-type">
                    <?php echo $_page_lang['account.ac_type_'.$_show_current_member['type']]; ?>
                </div>

                <div class="subscription-info">
                    <div class="subscription-name">
                        <strong>Subscription:</strong> <?php echo !empty($_show_current_member['subscription_name']) ? $_show_current_member['subscription_name'] : 'Free Plan'; ?>
                    </div>
                    <div class="subscription-expiry">
                        <strong>Expires:</strong> <?php echo !empty($_show_current_member['subscription_expiry']) ? date('M d, Y', strtotime($_show_current_member['subscription_expiry'])) : 'N/A'; ?>
                    </div>
                </div>

                <?php if(empty($_show_current_member['remark'])) { ?>
                <div class="further-tips">
                    <i class="fa fa-info-circle"></i>
                    <?php echo $_page_lang['account.further_info_1']; ?>
                </div>
                <?php } ?>
                
                <div class="clearboth"></div>
                
                <div class="further-title">
                    <h1><?php echo $_page_lang['tab_about']; ?></h1>
                    <textarea id="further-content" name="remark"><?php echo $_show_current_member['remark']; ?></textarea>
                </div>
                <div class="clearboth"></div>
  
                <div class="required"><span style="color:red;">*</span> <?php echo $_page_lang['required']; ?></div>
                <div class="clearboth"></div>

                <?php if(in_array((int)$_show_current_member['type'], [2, 3])) { ?>
                <div class="group-title"><u><?php echo $_page_lang['account.company_info']; ?></u></div>

                <div class="row">
                    <label for="logo"><?php echo $_page_lang['account.logo']; ?></label>
                    <div class="logo-file">
                        <img src="asset/image/default_logo.jpg" alt="default_logo">
                        <div class="preview"><!--
                            <?php if(!empty($_show_current_member_details['logo'])) { ?>
                            --><img src="<?php echo 'upload/member_logo/'.$_show_current_member_details['logo']; ?>"><!--
                            <?php } ?>
                        --></div>
                        <div class="select">
                            <input type="file" id="mylogo" name="mylogo" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="company_name"><?php echo ((int)$_show_current_member['type'] == 2)?$_page_lang['account.company_name']:$_page_lang['account.company_name_2']; ?> <span style="color:red;">*</span></label>
                    <input type="text" id="company_name" name="company_name" placeholder="<?php echo $_page_lang['account.enter_company_name']; ?>" value="<?php echo $_show_current_member_details['company_name']; ?>" data-validation="required">
                </div>
                <div class="clearboth"></div>

                <?php if((int)$_show_current_member['type'] == 3) { ?>
                <div class="row">
                    <label for="company_type"><?php echo $_page_lang['account.company_type']; ?> <span style="color:red;">*</span></label>
                    <select id="company_type" name="company_type" data-validation="required">
                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                        <?php if(!empty($_page_options['organization_type'])) { foreach ($_page_options['organization_type'] as $organization_type_id => $organization_type) { ?>
                        <option value="<?php echo $organization_type_id; ?>"<?php echo ($_show_current_member_details['company_type']==$organization_type_id)?' selected':'';?>><?php echo $organization_type; ?></option>
                        <?php }} ?>
                    </select>
                </div>
                <div class="clearboth"></div>
                <?php } ?>

                <div class="row">
                    <label for="company_website"><?php echo $_page_lang['account.company_website']; ?> <span style="color:red;">*</span></label>
                    <input type="text" id="company_website" name="company_website" placeholder="<?php echo $_page_lang['account.enter_company_website']; ?>" value="<?php echo $_show_current_member_details['company_website']; ?>" data-validation="required">
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="company_address"><?php echo $_page_lang['account.company_address']; ?> <span style="color:red;">*</span></label>
                    <textarea id="company_address" name="company_address" rows="3" placeholder="<?php echo $_page_lang['account.enter_company_address']; ?>" data-validation="required"><?php echo $_show_current_member_details['company_address']; ?></textarea>
                </div>
                <div class="clearboth"></div>

                <?php if((int)$_show_current_member['type'] == 2) { ?>
                
                <div class="row">
                    <label for="countries_serving"><?php echo $_page_lang['account.countries_serving']; ?></label>
                    <select id="countries_serving" name="countries_serving[]" multiple>
                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                        <option value="<?php echo $country_id; ?>"<?php echo (!empty($_show_current_member['countries_serving']) && is_array($_show_current_member['countries_serving']) && in_array($country_id, $_show_current_member['countries_serving']))?' selected':'';?>><?php echo $country; ?></option>
                        <?php }} ?>
                    </select>
                </div>
                <div class="clearboth"></div>
                
                <div class="child-agent">
                    <div class="row">
                        <label for="registered_agent"><?php echo $_page_lang['account.has_registered_agent']; ?> <span style="color:red;">*</span></label>
                        <div class="iweb-radiobox-set"  data-showtips="false">
                            <input type="radio" id="registered_agent_yes" name="registered_agent" value="1" <?php echo ((int)$_show_current_member_details['registered_agent']==1)?' checked':'';?>>
                            <label for="registered_agent_yes"><?php echo $_page_lang['account.yes']; ?></label>

                            <input type="radio" id="registered_agent_no" name="registered_agent" value="0" <?php echo ((int)$_show_current_member_details['registered_agent']==0)?' checked':'';?>>
                            <label for="registered_agent_no"><?php echo $_page_lang['account.no']; ?></label>
                        </div>
                    </div>
                    <div class="clearboth"></div>

                    <div class="list<?php echo ((int)$_show_current_member_details['registered_agent']==0)?' disabled':'';?>">
                        <div class="items">
                            <div class="block hidden">
                                <input type="hidden"  name="agent_id[]" value="0" disabled>

                                <div class="num"><?php echo $_page_lang['account.registered_agent']; ?><span> - 0</span></div>

                                <div class="row left">
                                    <label for="first_name"><?php echo $_page_lang['account.first_name']; ?> <span style="color:red;">*</span></label>
                                    <input type="text"  name="agent_first_name[]" placeholder="<?php echo $_page_lang['account.enter_first_name']; ?>" disabled>
                                </div>
                                <div class="row right">
                                    <label for="last_name"><?php echo $_page_lang['account.last_name']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="agent_last_name[]" placeholder="<?php echo $_page_lang['account.enter_last_name']; ?>" disabled>
                                </div>
                                <div class="clearboth"></div>

                                <div class="row left">
                                    <label for="registration_country"><?php echo $_page_lang['account.registration_country']; ?> <span style="color:red;">*</span></label>
                                    <select name="agent_registration_country[]" disabled>
                                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                        <option value="<?php echo $country_id; ?>"><?php echo $country; ?></option>
                                        <?php }} ?>
                                    </select>
                                </div>
                                <div class="row right">
                                    <label for="registration_num"><?php echo $_page_lang['account.registration_num']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="agent_registration_num[]" placeholder="<?php echo $_page_lang['account.enter_registration_num']; ?>" disabled>
                                </div>
                                <div class="clearboth"></div>
                            </div>

                            <?php if(!empty($_show_current_member_agent)) { foreach ($_show_current_member_agent as $agent_key => $agent) { ?>
                            <div class="block">
                                <a class="remove-agent-block"><i class="fa fa-times"></i></a>

                                <input type="hidden"  name="agent_id[]" value="<?php echo (int)$agent['id']; ?>">

                                <div class="num"><?php echo $_page_lang['account.registered_agent']; ?><span> - <?php echo $agent_key+1;?></span></div>

                                <div class="row left">
                                    <label for="first_name"><?php echo $_page_lang['account.first_name']; ?> <span style="color:red;">*</span></label>
                                    <input type="text"  name="agent_first_name[]" placeholder="<?php echo $_page_lang['account.enter_first_name']; ?>" value="<?php echo $agent['first_name']; ?>">
                                </div>
                                <div class="row right">
                                    <label for="last_name"><?php echo $_page_lang['account.last_name']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="agent_last_name[]" placeholder="<?php echo $_page_lang['account.enter_last_name']; ?>" value="<?php echo $agent['last_name']; ?>">
                                </div>
                                <div class="clearboth"></div>

                                <div class="row left">
                                    <label for="registration_country"><?php echo $_page_lang['account.registration_country']; ?> <span style="color:red;">*</span></label>
                                    <select name="agent_registration_country[]">
                                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                        <option value="<?php echo $country_id; ?>"<?php echo ($agent['registration_country']==$country_id)?' selected':'';?>><?php echo $country; ?></option>
                                        <?php }} ?>
                                    </select>
                                </div>
                                <div class="row right">
                                    <label for="registration_num"><?php echo $_page_lang['account.registration_num']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="agent_registration_num[]" placeholder="<?php echo $_page_lang['account.enter_registration_num']; ?>" value="<?php echo $agent['registration_num']; ?>">
                                </div>
                                <div class="clearboth"></div>
                            </div>
                            <?php }} else { ?>
                            <div class="block">
                                <a class="remove-agent-block"><i class="fa fa-times"></i></a>

                                <input type="hidden"  name="agent_id[]" value="0">

                                <div class="num"><?php echo $_page_lang['account.registered_agent']; ?><span> - 1</span></div>

                                <div class="row left">
                                    <label for="first_name"><?php echo $_page_lang['account.first_name']; ?> <span style="color:red;">*</span></label>
                                    <input type="text"  name="agent_first_name[]" placeholder="<?php echo $_page_lang['account.enter_first_name']; ?>">
                                </div>
                                <div class="row right">
                                    <label for="last_name"><?php echo $_page_lang['account.last_name']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="agent_last_name[]" placeholder="<?php echo $_page_lang['account.enter_last_name']; ?>">
                                </div>
                                <div class="clearboth"></div>

                                <div class="row left">
                                    <label for="registration_country"><?php echo $_page_lang['account.registration_country']; ?> <span style="color:red;">*</span></label>
                                    <select name="agent_registration_country[]">
                                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                        <option value="<?php echo $country_id; ?>"><?php echo $country; ?></option>
                                        <?php }} ?>
                                    </select>
                                </div>
                                <div class="row right">
                                    <label for="registration_num"><?php echo $_page_lang['account.registration_num']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="agent_registration_num[]" placeholder="<?php echo $_page_lang['account.enter_registration_num']; ?>">
                                </div>
                                <div class="clearboth"></div>
                            </div>
                            <?php } ?>
                        </div>
                        <div class="row">
                            <a class="add-agent-block" href="#">
                                <span><?php echo $_page_lang['account.add_registered_agent']; ?></span>
                                <i class="fa fa-plus-circle"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="child-lawfirm">
                    <div class="row">
                        <label for="registered_lawfirm"><?php echo $_page_lang['account.has_registered_lawfirm']; ?> <span style="color:red;">*</span></label>
                        <div class="iweb-radiobox-set"  data-showtips="false">
                            <input type="radio" id="registered_lawfirm_yes" name="registered_lawfirm" value="1" <?php echo ((int)$_show_current_member_details['registered_lawfirm']==1)?' checked':'';?>>
                            <label for="registered_lawfirm_yes"><?php echo $_page_lang['account.yes']; ?></label>

                            <input type="radio" id="registered_lawfirm_no" name="registered_lawfirm" value="0" <?php echo ((int)$_show_current_member_details['registered_lawfirm']==0)?' checked':'';?>>
                            <label for="registered_lawfirm_no"><?php echo $_page_lang['account.no']; ?></label>
                        </div>
                    </div>
                    <div class="clearboth"></div>

                    <div class="list<?php echo ((int)$_show_current_member_details['registered_lawfirm']==0)?' disabled':'';?>">
                        <div class="items">
                            <div class="block hidden">
                                <input type="hidden"  name="lawfirm_id[]" value="0" disabled>

                                <div class="num"><?php echo $_page_lang['account.registered_lawfirm']; ?><span> - 0</span></div>

                                <div class="row">
                                    <label for="name"><?php echo $_page_lang['account.name']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="lawfirm_name[]" placeholder="<?php echo $_page_lang['account.enter_name']; ?>" disabled>
                                </div>
                                <div class="clearboth"></div>

                                <div class="row left">
                                    <label for="registration_country"><?php echo $_page_lang['account.registration_country']; ?> <span style="color:red;">*</span></label>
                                    <select name="lawfirm_registration_country[]" disabled>
                                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                        <option value="<?php echo $country_id; ?>"><?php echo $country; ?></option>
                                        <?php }} ?>
                                    </select>
                                </div>
                                <div class="row right">
                                    <label for="registration_num"><?php echo $_page_lang['account.registration_num']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="lawfirm_registration_num[]" placeholder="<?php echo $_page_lang['account.enter_registration_num']; ?>" disabled>
                                </div>
                                <div class="clearboth"></div>
                            </div>

                            <?php if(!empty($_show_current_member_lawfirm)) { foreach ($_show_current_member_lawfirm as $lawfirm_key => $lawfirm) { ?>
                            <div class="block">
                                <a class="remove-lawfirm-block"><i class="fa fa-times"></i></a>

                                <input type="hidden"  name="lawfirm_id[]" value="<?php echo (int)$lawfirm['id']; ?>">

                                <div class="num"><?php echo $_page_lang['account.registered_lawfirm']; ?><span> - <?php echo $lawfirm_key+1; ?></span></div>

                                <div class="row">
                                    <label for="name"><?php echo $_page_lang['account.name']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="lawfirm_name[]" placeholder="<?php echo $_page_lang['account.enter_name']; ?>" value="<?php echo $lawfirm['full_name']; ?>" data-validation="required">
                                </div>
                                <div class="clearboth"></div>

                                <div class="row left">
                                    <label for="registration_country"><?php echo $_page_lang['account.registration_country']; ?> <span style="color:red;">*</span></label>
                                    <select name="lawfirm_registration_country[]">
                                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                        <option value="<?php echo $country_id; ?>"<?php echo ($lawfirm['registration_country']==$country_id)?' selected':'';?>><?php echo $country; ?></option>
                                        <?php }} ?>
                                    </select>
                                </div>
                                <div class="row right">
                                    <label for="registration_num"><?php echo $_page_lang['account.registration_num']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="lawfirm_registration_num[]" placeholder="<?php echo $_page_lang['account.enter_registration_num']; ?>" value="<?php echo $lawfirm['registration_num']; ?>">
                                </div>
                                <div class="clearboth"></div>
                            </div>
                            <?php }} else { ?>
                            <div class="block">
                                <a class="remove-lawfirm-block"><i class="fa fa-times"></i></a>

                                <input type="hidden"  name="lawfirm_id[]" value="0">

                                <div class="num"><?php echo $_page_lang['account.registered_lawfirm']; ?><span> - 1</span></div>

                                <div class="row">
                                    <label for="name"><?php echo $_page_lang['account.name']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="lawfirm_name[]" placeholder="<?php echo $_page_lang['account.enter_name']; ?>">
                                </div>
                                <div class="clearboth"></div>

                                <div class="row left">
                                    <label for="registration_country"><?php echo $_page_lang['account.registration_country']; ?> <span style="color:red;">*</span></label>
                                    <select name="lawfirm_registration_country[]">
                                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                        <option value="<?php echo $country_id; ?>"><?php echo $country; ?></option>
                                        <?php }} ?>
                                    </select>
                                </div>
                                <div class="row right">
                                    <label for="registration_num"><?php echo $_page_lang['account.registration_num']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="lawfirm_registration_num[]" placeholder="<?php echo $_page_lang['account.enter_registration_num']; ?>">
                                </div>
                                <div class="clearboth"></div>
                            </div>
                            <?php } ?>
                        </div>
                        <div class="row">
                            <a class="add-lawfirm-block">
                                <span><?php echo $_page_lang['account.add_registered_lawfirm']; ?></span>
                                <i class="fa fa-plus-circle"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <?php } else { ?>
                <div class="row">
                    <label for="services"><?php echo $_page_lang['account.services']; ?></label>
                    <input type="text" id="services" name="services" placeholder="<?php echo $_page_lang['account.enter_services']; ?>" value="<?php echo $_show_current_member_details['services']; ?>">
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="services_country"><?php echo $_page_lang['account.services_country']; ?> <span style="color:red;">*</span></label>
                    <?php
                    $selected_countries = [];
                    if(!empty($_show_current_member_details['services_country'])) {
                        $decoded = json_decode($_show_current_member_details['services_country'], true);
                        $selected_countries = is_array($decoded) ? $decoded : [];
                    }
                    ?>
                    <select id="services_country" name="services_country[]" multiple="multiple" data-validation="required">
                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                        <option value="all">All Countries</option>
                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                        <option value="<?php echo $country_id; ?>"<?php echo (in_array($country_id, $selected_countries))?' selected':'';?>><?php echo $country; ?></option>
                        <?php }} ?>
                    </select>
                </div>
                <div class="clearboth"></div>

                <div>&nbsp;</div>

                <div class="group-title"><u><?php echo $_page_lang['account.business_registration']; ?></u> <span style="font-size: 0.8em; font-weight: normal;">(<?php echo $_page_lang['if_applicable']; ?>)</span></div>

                <div class="row">
                    <label for="registered_business_country"><?php echo $_page_lang['account.business_registration_country']; ?></label>
                    <select id="registered_business_country" name="registered_business_country">
                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                        <option value="<?php echo $country_id; ?>"<?php echo ($_show_current_member_details['registered_business_country']==$country_id)?' selected':'';?>><?php echo $country; ?></option>
                        <?php }} ?>
                    </select>
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="registered_business_name"><?php echo $_page_lang['account.business_registration_name']; ?></label>
                    <input type="text" id="registered_business_name" name="registered_business_name" placeholder="<?php echo $_page_lang['account.enter_business_registration_name']; ?>" value="<?php echo $_show_current_member_details['registered_business_name']; ?>">
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="registered_business_number"><?php echo $_page_lang['account.business_registration_number']; ?></label>
                    <input type="text" id="registered_business_number" name="registered_business_number" placeholder="<?php echo $_page_lang['account.enter_business_registration_number']; ?>" value="<?php echo $_show_current_member_details['registered_business_number']; ?>">
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

                        <?php if(!empty($_show_current_member_business_license)) { foreach ($_show_current_member_business_license as $license_key => $license) { ?>
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

                <?php } ?>

                <div>&nbsp;</div>
                <?php } ?>

                <div class="group-title"><u><?php echo $_page_lang['account.contact_info']; ?></u></div>

                <div class="row left">
                    <label for="first_name"><?php echo $_page_lang['account.first_name']; ?> <span style="color:red;">*</span></label>
                    <input type="text" id="first_name" name="first_name" placeholder="<?php echo $_page_lang['account.enter_first_name']; ?>" value="<?php echo $_show_current_member['first_name']; ?>" data-validation="required">
                </div>
                <div class="row right">
                    <label for="last_name"><?php echo $_page_lang['account.last_name']; ?> <span style="color:red;">*</span></label>
                    <input type="text" id="last_name" name="last_name" placeholder="<?php echo $_page_lang['account.enter_last_name']; ?>" value="<?php echo $_show_current_member['last_name']; ?>" data-validation="required">
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="email"><?php echo $_page_lang['account.email']; ?> <span style="color:red;">*</span></label>
                    <input type="text" id="email" name="email" placeholder="<?php echo $_page_lang['account.enter_email']; ?>" value="<?php echo $_show_current_member['email']; ?>" data-validation="required|email">
                </div>
                <div class="clearboth"></div>

                <?php if(in_array((int)$_show_current_member['type'], [2, 3])) { ?>
                <div class="row">
                    <label for="telephone"><?php echo $_page_lang['account.telephone']; ?> <span style="color:red;">*</span></label>
                    <table class="telephone">
                        <tr>
                            <td><input type="text" id="telephone_code" name="telephone_code" placeholder="+852" value="+<?php echo preg_replace('/^(\+)(.*)/i', '$2', $_show_current_member['telephone_code']); ?>" data-validation="required"></td>
                            <td><input type="text" id="telephone_num" name="telephone_num" placeholder="<?php echo $_page_lang['account.enter_telephone']; ?>" value="<?php echo $_show_current_member['telephone_num']; ?>" data-validation="required"></td>
                        </tr>
                    </table>
                </div>
                <div class="clearboth"></div>
                <?php } ?>

                <div class="row">
                    <label for="password"><?php echo $_page_lang['account.password']; ?> <small style="color:red;">(<?php echo $_page_lang['password_blank']; ?>)</small></label>
                    <input type="password" id="password" name="password" placeholder="<?php echo $_page_lang['account.enter_password']; ?>" value="" data-validation="password">
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="repeat_password"><?php echo $_page_lang['account.re_password']; ?></label>
                    <input type="password" id="repeat_password" name="repeat_password" placeholder="<?php echo $_page_lang['account.enter_password']; ?>" value="" data-validation="password">
                </div>
                <div class="clearboth"></div>

                <?php if(in_array((int)$_show_current_member['type'], [1])) { ?>
                <div>&nbsp;</div>

                <div class="group-title"><u><?php echo $_page_lang['choose_your_preference']; ?></u></div>

                <div class="row">
                    <label for="migration_destination"><?php echo $_page_lang['account.migration_destination']; ?></label>
                    <select id="migration_destination" name="migration_destination">
                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                        <option value="<?php echo $country_id; ?>"<?php echo ($_show_current_member['migration_destination']==$country_id)?' selected':'';?>><?php echo $country; ?></option>
                        <?php }} ?>
                    </select>
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="interested_visa"><?php echo $_page_lang['account.interested_visa']; ?> <span style="color:red;">*</span></label>
                    <select id="interested_visa" name="interested_visa" data-validation="required">
                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                       <?php if(!empty($_page_options['interest_visas'])) { foreach ($_page_options['interest_visas'] as $interest_visas_id => $interest_visas) { ?>
                        <option value="<?php echo $interest_visas_id; ?>"<?php echo ($_show_current_member['interested_visa']==$interest_visas_id)?' selected':'';?>><?php echo $interest_visas; ?></option>
                        <?php }} ?>
                    </select>
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="interested_topic"><?php echo $_page_lang['account.interested_topic']; ?> <span style="color:red;">*</span></label>
                    <div class="iweb-checkbox-set">
                        <?php if(!empty($_page_options['interest_topics'])) { foreach ($_page_options['interest_topics'] as $interest_topics_id => $interest_topics) { ?>
                        <input type="checkbox" id="interested_topic_<?php echo $interest_topics_id; ?>" name="interested_topic[]" value="<?php echo $interest_topics_id; ?>"<?php echo (is_array($_show_current_member['interested_topic']) && in_array($interest_topics_id, $_show_current_member['interested_topic']))?' checked':'';?> data-validation="required">
                        <label for="interested_topic_<?php echo $interest_topics_id; ?>"><?php echo $interest_topics; ?></label>
                        <?php }} ?>
                    </div>
                </div>
                <div class="clearboth"></div>
                <?php } ?>

                <div class="action">
                    <button type="submit" class="btn btn-save"><?php echo $_page_lang['btn.save']; ?></button>
                    <div class="clearboth"></div>
                </div>
            </form>
        </div>
    </div>
    <?php } else { ?>
    <div class="tab-details">
        <div class="form">
            
            <div class="ac-type">
                <?php echo $_page_lang['account.ac_type_'.$_show_current_member['type']]; ?>
            </div>

            <div class="subscription-info">
                <div class="subscription-name">
                    <strong>Subscription:</strong> <?php echo !empty($_show_current_member['subscription_name']) ? $_show_current_member['subscription_name'] : 'Free Plan'; ?>
                </div>
                <div class="subscription-expiry">
                    <strong>Expires:</strong> <?php echo !empty($_show_current_member['subscription_expiry']) ? date('M d, Y', strtotime($_show_current_member['subscription_expiry'])) : 'N/A'; ?>
                </div>
            </div>

            <div class="clearboth"></div>

            <div class="further-title">
                <h1><?php echo $_page_lang['tab_about']; ?></h1>
                <div class="input-value"><?php echo nl2br($_show_current_member['remark']); ?></div>
            </div>
            <div class="clearboth"></div>

            <?php if(in_array((int)$_show_current_member['type'], [2, 3])) { ?>
            <div class="group-title"><u><?php echo $_page_lang['account.company_info']; ?></u></div>

            <div class="row">
                <label for="logo"><?php echo $_page_lang['account.logo']; ?></label>
                <div class="logo-file">
                    <img src="asset/image/default_logo.jpg" alt="default_logo">
                    <div class="preview"><!--
                        <?php if(!empty($_show_current_member_details['logo'])) { ?>
                        --><img src="<?php echo 'upload/member_logo/'.$_show_current_member_details['logo']; ?>"><!--
                        <?php } ?>
                    --></div>
                </div>
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="company_name"><?php echo ((int)$_show_current_member['type'] == 2)?$_page_lang['account.company_name']:$_page_lang['account.company_name_2']; ?></label>
                <div class="input-value"><?php echo $_show_current_member_details['company_name']; ?></div>
            </div>
            <div class="clearboth"></div>

            <?php if((int)$_show_current_member['type'] == 3) { ?>
            <div class="row">
                <label for="company_type"><?php echo $_page_lang['account.company_type']; ?></label>
                <div class="input-value">
                    <?php echo (!empty($_page_options['organization_type'][$_show_current_member_details['company_type']]))?$_page_options['organization_type'][$_show_current_member_details['company_type']]:''; ?>
                </div>
            </div>
            <div class="clearboth"></div>
            <?php } ?>

            <div class="row">
                <label for="company_website"><?php echo $_page_lang['account.company_website']; ?></label>
                <div class="input-value"><?php echo $_show_current_member_details['company_website']; ?></div>
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="company_address"><?php echo $_page_lang['account.company_address']; ?></label>
                <div class="input-value"><?php echo nl2br($_show_current_member_details['company_address']); ?></div>
            </div>
            <div class="clearboth"></div>

            <?php if((int)$_show_current_member['type'] == 2) { ?>
            <div class="child-agent">
                <div class="row">
                    <label for="registered_agent"><?php echo $_page_lang['account.has_registered_agent']; ?></label>
                    <div class="iweb-radiobox-set"  data-showtips="false">
                        <input type="radio" id="registered_agent_yes" name="registered_agent" value="1" <?php echo ((int)$_show_current_member_details['registered_agent']==1)?' checked':'';?> disabled>
                        <label for="registered_agent_yes"><?php echo $_page_lang['account.yes']; ?></label>

                        <input type="radio" id="registered_agent_no" name="registered_agent" value="0" <?php echo ((int)$_show_current_member_details['registered_agent']==0)?' checked':'';?> disabled>
                        <label for="registered_agent_no"><?php echo $_page_lang['account.no']; ?></label>
                    </div>
                </div>
                <div class="clearboth"></div>

                <div class="list<?php echo ((int)$_show_current_member_details['registered_agent']==0)?' disabled':'';?>">
                    <div class="items">
                        <?php if(!empty($_show_current_member_agent)) { foreach ($_show_current_member_agent as $agent_key => $agent) { ?>
                        <div class="block">
                            <div class="num"><?php echo $_page_lang['account.registered_agent']; ?><span> - <?php echo $agent_key+1;?></span></div>

                            <div class="row left">
                                <label for="first_name"><?php echo $_page_lang['account.first_name']; ?></label>
                                <div class="input-value"><?php echo $agent['first_name']; ?></div>
                            </div>
                            <div class="row right">
                                <label for="last_name"><?php echo $_page_lang['account.last_name']; ?></label>
                                <div class="input-value"><?php echo $agent['last_name']; ?></div>
                            </div>
                            <div class="clearboth"></div>

                            <div class="row left">
                                <label for="registration_country"><?php echo $_page_lang['account.registration_country']; ?></label>
                                <div class="input-value">
                                    <?php echo (!empty($_page_options['countries'][$agent['registration_country']]))?$_page_options['countries'][$agent['registration_country']]:''; ?>
                                </div>
                            </div>
                            <div class="row right">
                                <label for="registration_num"><?php echo $_page_lang['account.registration_num']; ?></label>
                                <div class="input-value"><?php echo $agent['registration_num']; ?></div>
                            </div>
                            <div class="clearboth"></div>
                        </div>
                        <?php }} ?>
                    </div>
                </div>
            </div>

            <div class="child-lawfirm">
                <div class="row">
                    <label for="registered_lawfirm"><?php echo $_page_lang['account.has_registered_lawfirm']; ?></label>
                    <div class="iweb-radiobox-set"  data-showtips="false">
                        <input type="radio" id="registered_lawfirm_yes" name="registered_lawfirm" value="1" <?php echo ((int)$_show_current_member_details['registered_lawfirm']==1)?' checked':'';?> disabled>
                        <label for="registered_lawfirm_yes"><?php echo $_page_lang['account.yes']; ?></label>

                        <input type="radio" id="registered_lawfirm_no" name="registered_lawfirm" value="0" <?php echo ((int)$_show_current_member_details['registered_lawfirm']==0)?' checked':'';?> disabled>
                        <label for="registered_lawfirm_no"><?php echo $_page_lang['account.no']; ?></label>
                    </div>
                </div>
                <div class="clearboth"></div>

                <div class="list<?php echo ((int)$_show_current_member_details['registered_lawfirm']==0)?' disabled':'';?>">
                    <div class="items">
                        <?php if(!empty($_show_current_member_lawfirm)) { foreach ($_show_current_member_lawfirm as $lawfirm_key => $lawfirm) { ?>
                        <div class="block">
                            <div class="num"><?php echo $_page_lang['account.registered_lawfirm']; ?><span> - <?php echo $lawfirm_key+1; ?></span></div>

                            <div class="row">
                                <label for="name"><?php echo $_page_lang['account.name']; ?></label>
                                <div class="input-value"><?php echo $lawfirm['full_name']; ?></div>
                            </div>
                            <div class="clearboth"></div>

                            <div class="row left">
                                <label for="registration_country"><?php echo $_page_lang['account.registration_country']; ?></label>
                                <div class="input-value">
                                    <?php echo (!empty($_page_options['countries'][$lawfirm['registration_country']]))?$_page_options['countries'][$lawfirm['registration_country']]:''; ?>
                                </div>
                            </div>
                            <div class="row right">
                                <label for="registration_num"><?php echo $_page_lang['account.registration_num']; ?></label>
                                <div class="input-value"><?php echo $lawfirm['registration_num']; ?></div>
                            </div>
                            <div class="clearboth"></div>
                        </div>
                        <?php }} ?>
                    </div>
                </div>
            </div>

            <?php } else { ?>
            <div class="row">
                <label for="services"><?php echo $_page_lang['account.services']; ?></label>
                <div class="input-value"><?php echo $_show_current_member_details['services']; ?></div>
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="services_country"><?php echo $_page_lang['account.services_country']; ?></label>
                <div class="input-value">
                    <?php
                    $services_countries = [];
                    if(!empty($_show_current_member_details['services_country'])) {
                        $decoded = json_decode($_show_current_member_details['services_country'], true);
                        if(is_array($decoded)) {
                            foreach($decoded as $country_code) {
                                if(!empty($_page_options['countries'][$country_code])) {
                                    $services_countries[] = $_page_options['countries'][$country_code];
                                }
                            }
                        }
                    }
                    echo !empty($services_countries) ? implode(', ', $services_countries) : '';
                    ?>
                </div>
            </div>
            <div class="clearboth"></div>

            <div>&nbsp;</div>

            <div class="group-title"><u><?php echo $_page_lang['account.business_registration']; ?></u></div>

            <div class="row">
                <label for="registered_business_country"><?php echo $_page_lang['account.business_registration_country']; ?></label>
                <div class="input-value">
                    <?php echo (!empty($_page_options['countries'][$_show_current_member_details['registered_business_country']]))?$_page_options['countries'][$_show_current_member_details['registered_business_country']]:''; ?>
                </div>
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="registered_business_name"><?php echo $_page_lang['account.business_registration_name']; ?></label>
                <div class="input-value"><?php echo $_show_current_member_details['registered_business_name']; ?></div>
            </div>
            <div class="clearboth"></div>

            <div>&nbsp;</div>

            <div class="group-title"><u><?php echo $_page_lang['account.business_license']; ?></u> <span style="font-size: 0.8em; font-weight: normal;">(<?php echo $_page_lang['if_applicable']; ?>)</span></div>

            <div class="child-business-license">
                <div class="items">
                    <?php if(!empty($_show_current_member_business_license)) { foreach ($_show_current_member_business_license as $license_key => $license) { ?>
                    <div class="block">
                        <div class="num"><?php echo $_page_lang['account.business_license']; ?><span> - <?php echo $license_key+1;?></span></div>

                        <div class="row left">
                            <label for="license_country"><?php echo $_page_lang['account.business_license_country']; ?></label>
                            <div class="input-value">
                                <?php echo (!empty($_page_options['countries'][$license['license_country']]))?$_page_options['countries'][$license['license_country']]:''; ?>
                            </div>
                        </div>
                        <div class="row right">
                            <label for="issuing_authority"><?php echo $_page_lang['account.issuing_authority']; ?></label>
                            <div class="input-value"><?php echo $license['issuing_authority']; ?></div>
                        </div>
                        <div class="clearboth"></div>
                    </div>
                    <?php }} ?>
                </div>
            </div>
            <?php } ?>

            <div>&nbsp;</div>
            <?php } ?>

            <div class="group-title"><u><?php echo $_page_lang['account.contact_info']; ?></u></div>

            <div class="row left">
                <label for="first_name"><?php echo $_page_lang['account.first_name']; ?></label>
                <div class="input-value"><?php echo $_show_current_member['first_name']; ?></div>
             </div>
            <div class="row right">
                <label for="last_name"><?php echo $_page_lang['account.last_name']; ?></label>
                <div class="input-value"><?php echo $_show_current_member['last_name']; ?></div>
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="email"><?php echo $_page_lang['account.email']; ?></label>
                <div class="input-value"><?php echo $_show_current_member['email']; ?></div>
            </div>
            <div class="clearboth"></div>

            <?php if(in_array((int)$_show_current_member['type'], [2, 3])) { ?>
            <div class="row">
                <label for="telephone"><?php echo $_page_lang['account.telephone']; ?></label>
                <div class="input-value">
                    (<?php echo preg_replace('/^(\+)(.*)/i', '$2', $_show_current_member['telephone_code']); ?>)<?php echo $_show_current_member['telephone_num']; ?>
                    <?php
                    $phone_code = preg_replace('/^(\+)(.*)/i', '$2', $_show_current_member['telephone_code']);
                    $whatsapp_number = str_replace([' ', '-', '(', ')'], '', $phone_code . $_show_current_member['telephone_num']);
                    ?>
                    <a href="https://wa.me/<?php echo $whatsapp_number; ?>" target="_blank" title="Contact via WhatsApp" style="margin-left: 10px; display: inline-block;">
                        <i class="fa fa-whatsapp" style="color: #25D366; font-size: 24px;"></i>
                    </a>
                </div>
            </div>
            <div class="clearboth"></div>
            <?php } ?>


            <?php if(in_array((int)$_show_current_member['type'], [1])) { ?>
            <div>&nbsp;</div>

            <div class="group-title"><u><?php echo $_page_lang['choose_your_preference']; ?></u></div>

            <div class="row">
                <label for="migration_destination"><?php echo $_page_lang['account.migration_destination']; ?></label>
                <div class="input-value">
                    <?php echo (!empty($_page_options['countries'][$_show_current_member['migration_destination']]))?$_page_options['countries'][$_show_current_member['migration_destination']]:''; ?>
                </div>
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="interested_visa"><?php echo $_page_lang['account.interested_visa']; ?></label>
                <div class="input-value">
                    <?php echo (!empty($_page_options['interest_visas'][$_show_current_member['interested_visa']]))?$_page_options['interest_visas'][$_show_current_member['interested_visa']]:''; ?>
                </div>
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="interested_topic"><?php echo $_page_lang['account.interested_topic']; ?></label>
                <div class="iweb-checkbox-set">
                    <?php if(!empty($_page_options['interest_topics'])) { foreach ($_page_options['interest_topics'] as $interest_topics_id => $interest_topics) { ?>
                    <input type="checkbox" id="interested_topic_<?php echo $interest_topics_id; ?>" name="interested_topic[]" value="<?php echo $interest_topics_id; ?>"<?php echo (is_array($_show_current_member['interested_topic']) && in_array($interest_topics_id, $_show_current_member['interested_topic']))?' checked':'';?> disabled>
                    <label for="interested_topic_<?php echo $interest_topics_id; ?>"><?php echo $interest_topics; ?></label>
                    <?php }} ?>
                </div>
            </div>
            <div class="clearboth"></div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
</div>
<?php if(empty($_page_data['is_readonly'])) { ?>
<div id="hide-extra-form" style="display:none;">
    <div class="form" style="margin-top:0px;">
        <form id="account-alias-form" method="post" action="<?php echo $_page_base_url.'/account/myalias'; ?> " enctype="multipart/form-data">
            <div>@csrf</div>
            
            <div class="required"><span style="color:red;">*</span> <?php echo $_page_lang['required']; ?></div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="mycoverphoto"><?php echo $_page_lang['account.add_cover_photo']; ?></label>
                <div class="coverphoto-file">
                    <div class="upload">
                        <i class="fa fa-camera"></i>
                    </div>
                    <div class="preview">
                        <?php if(!empty($_show_current_member_details['coverphoto'])) { ?>
                        <img src="<?php echo 'upload/member_coverphoto/'.$_show_current_member_details['coverphoto']; ?>">
                        <?php } ?>
                    </div>
                    <div class="select">
                        <input type="file" id="mycoverphoto" name="mycoverphoto" accept="image/*">
                    </div>
                </div>
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="alias_name"><?php echo $_page_lang['account.alias_name']; ?> <span style="color:red;">*</span></label>
                <input type="text" id="alias_name" name="alias_name" placeholder="<?php echo $_page_lang['account.enter_alias_name']; ?>" value="<?php echo $_show_current_member['alias_name']; ?>" data-validation="required">
            </div>
            <div class="clearboth"></div>

            <div class="action">
                <button type="submit" class="btn btn-save"><?php echo $_page_lang['btn.save']; ?></button>
                <div class="clearboth"></div>
            </div>
        </form>
    </div>
</div>
<?php } ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-fill business registration country when country of operation changes
    const countrySelect = document.getElementById('country');
    const registeredBusinessCountrySelect = document.getElementById('registered_business_country');

    if (countrySelect && registeredBusinessCountrySelect) {
        countrySelect.addEventListener('change', function() {
            const selectedCountry = this.value;
            if (selectedCountry) {
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
        jQuery('#account-profile-form').on('submit', function() {
            $select.find('option[value="all"]').prop('selected', false);
        });
    }

});
</script>

@endsection