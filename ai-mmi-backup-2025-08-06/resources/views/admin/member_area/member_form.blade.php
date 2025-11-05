@extends('admin.common')
@section('content')
<?php 
$target_member = 
[
    'id'                =>  0,
    'method'            =>  1,
    'type'              =>  1,
    'coverphoto'        =>  '',
    'avatar'            =>  '',
    'alias_name'        =>  '',
    'full_name'         =>  1,
    'first_name'        =>  1,
    'last_name'         =>  '',
    'email'             =>  '',
    'telephone_code'    =>  '',
    'telephone_num'     =>  '',
    'migration_destination' => 0,
    'interested_visa'   =>  0,
    'interested_topic'  =>  '',
    'expiration_date_account'   => '',
    'expiration_date_visa_submission_ai'    =>  '',
    'expiration_date_visa_submission_human' =>  '',
    'remark'            =>  '',
    'verified'          =>  0,
    'status'            =>  0,
    'details'           =>  
    [
        'company_type'  =>  0,
        'company_name'  =>  '',
        'company_website'   => '',
        'company_address'   =>  '',
        'services'      =>  '',
        'services_country'  =>  '',
        'registered_agent'  =>  0,
        'registered_lawfirm'=>  0
    ],
    'agent'             =>  [],
    'lawfirm'           =>  []
];
if(!empty($_page_data['target_member'])) {
    $target_member = array_merge($target_member, $_page_data['target_member']);
}
if(!empty($_page_data['target_member_details'])) {
    $target_member['details'] = array_merge($target_member['details'], $_page_data['target_member_details']);
}
if(!empty($_page_data['target_member_agent'])) {
    $target_member['agent'] = $_page_data['target_member_agent'];
}
if(!empty($_page_data['target_member_lawfirm'])) {
    $target_member['lawfirm'] = $_page_data['target_member_lawfirm'];
}
?>
<form id="memberform" name="memberform" method="post">
    <div>@csrf</div>
    <div><input type="hidden" id="page_action" name="page_action" value="save"></div>
    <div><input type="hidden" id="member_id" name="member_id" value="<?php echo $target_member['id']; ?>"></div>
    <div><input type="hidden" id="member_type" name="member_type" value="<?php echo $target_member['type']; ?>"></div>
  
    <div class="widget thin fixed-top">
        <div class="controls right">
            <button type="reset" class="btn btn-red">
                <i class="fa fa-undo"></i>
                <span><?php echo $_page_lang['reset']; ?></span>
            </button>
            <button type="submit" class="btn btn-green">
                <i class="fa fa-save"></i>
                <span><?php echo $_page_lang['save']; ?></span>
            </button>
        </div>
        <div class="clearboth"></div>
    </div>
  
    <?php if($target_member['type'] == 2) { ?>
    <div class="widget">
        <div class="title"><?php echo $_page_lang['member_account.company_info']; ?></div>
        
        <div class="row left">
            <label for="type"><?php echo $_page_lang['member_account.type']; ?></label>
            <span class="ivalue"><?php echo $_page_options['type'][$target_member['type']]; ?></span>
        </div>
        
        <div class="row right">
            <table width="100%">
                <tr>
                    <td>
                        <label for="verified">Verified</label>
                        <select id="verified" name="verified">
                            <option value="0"<?php echo ($target_member['verified']==0)?' selected':''; ?>>Failed</option>
                            <option value="1"<?php echo ($target_member['verified']==1)?' selected':''; ?>>Passed</option>
                        </select>
                    </td>
                    <td>
                        <label for="staus"><?php echo $_page_lang['status']; ?></label>
                        <select id="status" name="status">
                            <option value="1"<?php echo ($target_member['status']==1)?' selected':''; ?>>Enabled</option>
                            <option value="4"<?php echo ($target_member['status']==4)?' selected':''; ?>>Disabled</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="clearboth"></div>
        
        <div class="row left">
            <label for="company_name"><?php echo $_page_lang['member_account.company_name']; ?> <span style="color:red;">*</span></label>
            <input type="text" id="company_name" name="company_name" value="<?php echo $target_member['details']['company_name']; ?>" data-validation="required">
        </div>
        
        <div class="row right">
            <label for="company_website"><?php echo $_page_lang['member_account.company_website']; ?> <span style="color:red;">*</span></label>
            <input type="text" id="company_website" name="company_website" value="<?php echo $target_member['details']['company_website']; ?>" data-validation="required">
        </div>
        
        <div class="clearboth"></div>
        
        <div class="row">
            <label for="company_address"><?php echo $_page_lang['member_account.company_address']; ?> <span style="color:red;">*</span></label>
            <textarea id="company_address" name="company_address"><?php echo $target_member['details']['company_address']; ?></textarea>
        </div>
        
        <div class="row">
            <label for="countries_serving"><?php echo $_page_lang['member_account.countries_serving']; ?></label>
            <?php
            $destinationsServingList = !empty($_page_options['destinations_serving']) ? $_page_options['destinations_serving'] : [];
            $selectedDestinations = [];
            if(!empty($target_member['countries_serving']) && is_array($target_member['countries_serving'])) {
                $selectedDestinations = array_map('strval', $target_member['countries_serving']);
            }
            ?>
            <select id="countries_serving" name="countries_serving[]" multiple>
                <?php foreach ($destinationsServingList as $destId => $destLabel) {
                    $optionValue = (string)$destId;
                    $isSelected = in_array($optionValue, $selectedDestinations, true);
                ?>
                <option value="<?php echo htmlspecialchars($optionValue, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isSelected ? ' selected' : ''; ?>><?php echo htmlspecialchars($destLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="clearboth"></div>

        <div class="child-agent">
            <div class="row" style="margin-bottom:0px;">
                <label for="registered_agent"><?php echo $_page_lang['member_account.has_registered_agent']; ?> <span style="color:red;">*</span></label>
                <div class="iweb-radiobox-set"  data-showtips="false">
                    <input type="radio" id="registered_agent_yes" name="registered_agent" value="1" <?php echo ((int)$target_member['details']['registered_agent']==1)?' checked':'';?>>
                    <label for="registered_agent_yes"><?php echo $_page_lang['member_account.yes']; ?></label>

                    <input type="radio" id="registered_agent_no" name="registered_agent" value="0" <?php echo ((int)$target_member['details']['registered_agent']==0)?' checked':'';?>>
                    <label for="registered_agent_no"><?php echo $_page_lang['member_account.no']; ?></label>
                </div>
            </div>
            <div class="clearboth"></div>

            <div class="list<?php echo ((int)$target_member['details']['registered_agent']==0)?' disabled':'';?>">
                <div class="items">
                    <div class="block hidden">
                        <input type="hidden"  name="agent_id[]" value="0" disabled>

                        <div class="num"><?php echo $_page_lang['member_account.registered_agent']; ?><span> - 0</span></div>

                        <div class="row left">
                            <label for="first_name"><?php echo $_page_lang['member_account.first_name']; ?> <span style="color:red;">*</span></label>
                            <input type="text"  name="agent_first_name[]" placeholder="<?php echo $_page_lang['member_account.enter_first_name']; ?>" disabled>
                        </div>
                        <div class="row right">
                            <label for="last_name"><?php echo $_page_lang['member_account.last_name']; ?> <span style="color:red;">*</span></label>
                            <input type="text" name="agent_last_name[]" placeholder="<?php echo $_page_lang['member_account.enter_last_name']; ?>" disabled>
                        </div>
                        <div class="clearboth"></div>

                        <div class="row left">
                            <label for="registration_country"><?php echo $_page_lang['member_account.registration_country']; ?> <span style="color:red;">*</span></label>
                            <select name="agent_registration_country[]" disabled>
                                <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                <option value="<?php echo $country_id; ?>"><?php echo $country; ?></option>
                                <?php }} ?>
                            </select>
                        </div>
                        <div class="row right">
                            <label for="registration_num"><?php echo $_page_lang['member_account.registration_num']; ?> <span style="color:red;">*</span></label>
                            <input type="text" name="agent_registration_num[]" placeholder="<?php echo $_page_lang['member_account.enter_registration_num']; ?>" disabled>
                        </div>
                        <div class="clearboth"></div>
                    </div>

                    <?php if(!empty($target_member['agent'])) { foreach ($target_member['agent'] as $agent_key => $agent) { ?>
                    <div class="block">
                        <a class="remove-agent-block"><i class="fa fa-times"></i></a>

                        <input type="hidden"  name="agent_id[]" value="<?php echo (int)$agent['id']; ?>">

                        <div class="num"><?php echo $_page_lang['member_account.registered_agent']; ?><span> - <?php echo $agent_key+1;?></span></div>

                        <div class="row left">
                            <label for="first_name"><?php echo $_page_lang['member_account.first_name']; ?> <span style="color:red;">*</span></label>
                            <input type="text"  name="agent_first_name[]" placeholder="<?php echo $_page_lang['member_account.enter_first_name']; ?>" value="<?php echo $agent['first_name']; ?>">
                        </div>
                        <div class="row right">
                            <label for="last_name"><?php echo $_page_lang['member_account.last_name']; ?> <span style="color:red;">*</span></label>
                            <input type="text" name="agent_last_name[]" placeholder="<?php echo $_page_lang['member_account.enter_last_name']; ?>" value="<?php echo $agent['last_name']; ?>">
                        </div>
                        <div class="clearboth"></div>

                        <div class="row left">
                            <label for="registration_country"><?php echo $_page_lang['member_account.registration_country']; ?> <span style="color:red;">*</span></label>
                            <select name="agent_registration_country[]">
                                <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                <option value="<?php echo $country_id; ?>"<?php echo ($agent['registration_country']==$country_id)?' selected':'';?>><?php echo $country; ?></option>
                                <?php }} ?>
                            </select>
                        </div>
                        <div class="row right">
                            <label for="registration_num"><?php echo $_page_lang['member_account.registration_num']; ?> <span style="color:red;">*</span></label>
                            <input type="text" name="agent_registration_num[]" placeholder="<?php echo $_page_lang['member_account.enter_registration_num']; ?>" value="<?php echo $agent['registration_num']; ?>">
                        </div>
                        <div class="clearboth"></div>
                    </div>
                    <?php }} ?>
                </div>
                
                <div class="row" style="margin-top:10px;">
                    <a class="add-agent-block" href="#">
                        <span><?php echo $_page_lang['member_account.add_registered_agent']; ?></span>
                        <i class="fa fa-plus-circle"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="child-lawfirm">
            <div class="row" style="margin-bottom:0px;">
                <label for="registered_lawfirm"><?php echo $_page_lang['member_account.has_registered_lawfirm']; ?> <span style="color:red;">*</span></label>
                <div class="iweb-radiobox-set"  data-showtips="false">
                    <input type="radio" id="registered_lawfirm_yes" name="registered_lawfirm" value="1" <?php echo ((int)$target_member['details']['registered_lawfirm']==1)?' checked':'';?>>
                    <label for="registered_lawfirm_yes"><?php echo $_page_lang['member_account.yes']; ?></label>

                    <input type="radio" id="registered_lawfirm_no" name="registered_lawfirm" value="0" <?php echo ((int)$target_member['details']['registered_lawfirm']==0)?' checked':'';?>>
                    <label for="registered_lawfirm_no"><?php echo $_page_lang['member_account.no']; ?></label>
                </div>
            </div>
            <div class="clearboth"></div>

            <div class="list<?php echo ((int)$target_member['details']['registered_lawfirm']==0)?' disabled':'';?>">
                <div class="items">
                    <div class="block hidden">
                        <input type="hidden"  name="lawfirm_id[]" value="0" disabled>

                        <div class="num"><?php echo $_page_lang['member_account.registered_lawfirm']; ?><span> - 0</span></div>

                        <div class="row">
                            <label for="name"><?php echo $_page_lang['member_account.name']; ?> <span style="color:red;">*</span></label>
                            <input type="text" name="lawfirm_name[]" placeholder="<?php echo $_page_lang['member_account.enter_name']; ?>" disabled>
                        </div>
                        <div class="clearboth"></div>

                        <div class="row left">
                            <label for="registration_country"><?php echo $_page_lang['member_account.registration_country']; ?> <span style="color:red;">*</span></label>
                            <select name="lawfirm_registration_country[]" disabled>
                                <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                <option value="<?php echo $country_id; ?>"><?php echo $country; ?></option>
                                <?php }} ?>
                            </select>
                        </div>
                        <div class="row right">
                            <label for="registration_num"><?php echo $_page_lang['member_account.registration_num']; ?> <span style="color:red;">*</span></label>
                            <input type="text" name="lawfirm_registration_num[]" placeholder="<?php echo $_page_lang['member_account.enter_registration_num']; ?>" disabled>
                        </div>
                        <div class="clearboth"></div>
                    </div>

                    <?php if(!empty($target_member['lawfirm'])) { foreach ($target_member['lawfirm'] as $lawfirm_key => $lawfirm) { ?>
                    <div class="block">
                        <a class="remove-lawfirm-block"><i class="fa fa-times"></i></a>

                        <input type="hidden"  name="lawfirm_id[]" value="<?php echo (int)$lawfirm['id']; ?>">

                        <div class="num"><?php echo $_page_lang['member_account.registered_lawfirm']; ?><span> - <?php echo $lawfirm_key+1; ?></span></div>

                        <div class="row">
                            <label for="name"><?php echo $_page_lang['member_account.name']; ?> <span style="color:red;">*</span></label>
                            <input type="text" name="lawfirm_name[]" placeholder="<?php echo $_page_lang['member_account.enter_name']; ?>" value="<?php echo $lawfirm['full_name']; ?>" data-validation="required">
                        </div>
                        <div class="clearboth"></div>

                        <div class="row left">
                            <label for="registration_country"><?php echo $_page_lang['member_account.registration_country']; ?> <span style="color:red;">*</span></label>
                            <select name="lawfirm_registration_country[]">
                                <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                <option value="<?php echo $country_id; ?>"<?php echo ($lawfirm['registration_country']==$country_id)?' selected':'';?>><?php echo $country; ?></option>
                                <?php }} ?>
                            </select>
                        </div>
                        <div class="row right">
                            <label for="registration_num"><?php echo $_page_lang['member_account.registration_num']; ?> <span style="color:red;">*</span></label>
                            <input type="text" name="lawfirm_registration_num[]" placeholder="<?php echo $_page_lang['member_account.enter_registration_num']; ?>" value="<?php echo $lawfirm['registration_num']; ?>">
                        </div>
                        <div class="clearboth"></div>
                    </div>
                    <?php }} ?>
                </div>
                <div class="row" style="margin-top:10px;">
                    <a class="add-lawfirm-block">
                        <span><?php echo $_page_lang['member_account.add_registered_lawfirm']; ?></span>
                        <i class="fa fa-plus-circle"></i>
                    </a>
                </div>
            </div>
        </div>
        
    </div>
    <?php } else if($target_member['type'] == 3) { ?>
    <div class="widget">
        <div class="title"><?php echo $_page_lang['member_account.company_info']; ?></div>
        
        <div class="row left">
            <label for="type"><?php echo $_page_lang['member_account.type']; ?></label>
            <span class="ivalue"><?php echo $_page_options['type'][$target_member['type']]; ?></span>
        </div>
        
        <div class="row right">
            <table width="100%">
                <tr>
                    <td>
                        <label for="verified">Verified</label>
                        <select id="verified" name="verified">
                            <option value="0"<?php echo ($target_member['verified']==0)?' selected':''; ?>>Failed</option>
                            <option value="1"<?php echo ($target_member['verified']==1)?' selected':''; ?>>Passed</option>
                        </select>
                    </td>
                    <td>
                        <label for="staus"><?php echo $_page_lang['status']; ?></label>
                        <select id="status" name="status">
                            <option value="1"<?php echo ($target_member['status']==1)?' selected':''; ?>>Enabled</option>
                            <option value="4"<?php echo ($target_member['status']==4)?' selected':''; ?>>Disabled</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="clearboth"></div>
        
        <div class="row left">
            <label for="company_name"><?php echo $_page_lang['member_account.company_name_2']; ?> <span style="color:red;">*</span></label>
            <input type="text" id="company_name" name="company_name" value="<?php echo $target_member['details']['company_name']; ?>" data-validation="required">
        </div>
        
        <div class="row right">
            <label for="company_type"><?php echo $_page_lang['member_account.company_type']; ?> <span style="color:red;">*</span></label>
            <select id="company_type" name="company_type" data-validation="required">
                <option value=""><?php echo $_page_lang['please_select']; ?></option>
                <?php if(!empty($_page_options['organization_type'])) { foreach ($_page_options['organization_type'] as $organization_type_id => $organization_type) { ?>
                <option value="<?php echo $organization_type_id; ?>"<?php echo ($target_member['details']['company_type']==$organization_type_id)?' selected':'';?>><?php echo $organization_type; ?></option>
                <?php }} ?>
            </select>
        </div>
        <div class="clearboth"></div>
        
        <div class="row left">
            <label for="company_website"><?php echo $_page_lang['member_account.company_website']; ?> <span style="color:red;">*</span></label>
            <input type="text" id="company_website" name="company_website" value="<?php echo $target_member['details']['company_website']; ?>" data-validation="required">
        </div>
        
        <div class="clearboth"></div>
        
        <div class="row left">
            <label for="services"><?php echo $_page_lang['member_account.services']; ?></label>
            <input type="text" id="services" name="services" placeholder="<?php echo $_page_lang['member_account.enter_services']; ?>" value="<?php echo $target_member['details']['services']; ?>">
        </div>
        
        <div class="row right">
            <label for="services_country"><?php echo $_page_lang['member_account.services_country']; ?></label>
            <select id="services_country" name="services_country">
                <option value=""><?php echo $_page_lang['please_select']; ?></option>
                <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                <option value="<?php echo $country_id; ?>"<?php echo ($target_member['details']['services_country']==$country_id)?' selected':'';?>><?php echo $country; ?></option>
                <?php }} ?>
            </select>
        </div>
        <div class="clearboth"></div>
        
        <div class="row">
            <label for="company_address"><?php echo $_page_lang['member_account.company_address']; ?> <span style="color:red;">*</span></label>
            <textarea id="company_address" name="company_address"><?php echo $target_member['details']['company_address']; ?></textarea>
        </div>
    </div>
    <?php } ?>
    
    <div class="widget">
        <div class="title"><?php echo $_page_lang['member_account.contact_info']; ?></div>
        
        <?php if($target_member['type'] == 1) { ?>
        <div class="row left">
            <label for="type"><?php echo $_page_lang['member_account.type']; ?></label>
            <span class="ivalue"><?php echo $_page_options['type'][$target_member['type']]; ?></span>
        </div>
        
        <div class="row right">
            <table width="100%">
                <tr>
                    <td>
                        <label for="verified">Verified</label>
                        <select id="verified" name="verified">
                            <option value="0"<?php echo ($target_member['verified']==0)?' selected':''; ?>>Failed</option>
                            <option value="1"<?php echo ($target_member['verified']==1)?' selected':''; ?>>Passed</option>
                        </select>
                    </td>
                    <td>
                        <label for="staus"><?php echo $_page_lang['status']; ?></label>
                        <select id="status" name="status">
                            <option value="1"<?php echo ($target_member['status']==1)?' selected':''; ?>>Enabled</option>
                            <option value="4"<?php echo ($target_member['status']==4)?' selected':''; ?>>Disabled</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="clearboth"></div>
        <?php } ?>
        
        <div class="row left">
            <label for="first_name"><?php echo $_page_lang['member_account.first_name']; ?> <span style="color:red;">*</span></label>
            <input type="text" id="first_name" name="first_name" value="<?php echo $target_member['first_name']; ?>" data-validation="required">
        </div>
        
        <div class="row right">
            <label for="last_name"><?php echo $_page_lang['member_account.last_name']; ?> <span style="color:red;">*</span></label>
            <input type="text" id="last_name" name="last_name" value="<?php echo $target_member['last_name']; ?>" data-validation="required">
        </div>
        
        <div class="clearboth"></div>

        <div class="row left">
            <label for="member_email"><?php echo $_page_lang['member_account.email']; ?> <span style="color:red;">*</span></label>
            <input type="text" id="member_email" name="email" value="<?php echo $target_member['email']; ?>" data-validation="required|email">
        </div>
        
        
        <div class="row right">
            <label for="member_telephone"><?php echo $_page_lang['member_account.telephone']; ?> <span style="color:red;"><?php echo ($target_member['type']!=1)?'*':''; ?></span></label>
            <table width="100%">
                <tr>
                    <td style="width:100px;padding:0px;">
                        <input type="text" id="member_telephone_code" name="telephone_code" value="<?php echo preg_replace('/^(\+)(.*)/i', '$2', $target_member['telephone_code']); ?>" data-validation="<?php echo ($target_member['type']!=1)?'required':'none'; ?>">
                    </td>
                    <td style="width:10px;padding:0px;">&nbsp;</td>
                    <td style="padding:0px;">
                        <input type="text" id="member_telephone_num" name="telephone_num" value="<?php echo $target_member['telephone_num']; ?>" data-validation="<?php echo ($target_member['type']!=1)?'required':'none'; ?>">
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="clearboth"></div>
        
        <div class="row left">
            <label for="member_password"><?php echo $_page_lang['member_account.password']; ?></label>
            <input type="password" id="member_password" name="password" value="" data-validation="password">
        </div>

        <div class="row right">
            <label for="member_repeat_password"><?php echo $_page_lang['member_account.re_password']; ?></label>
            <input type="password" id="member_repeat_password" name="repeat_password" value="" data-validation="password">
        </div>
        
        <div class="clearboth"></div>
        
        <div class="row">
            <label for="member_remark"><?php echo $_page_lang['member_account.remark']; ?></label>
            <textarea id="member_remark" name="remark"><?php echo $target_member['remark']; ?></textarea>
        </div>

    </div>
    
    <?php if($target_member['type'] == 1) { ?>
    <div class="widget">
        <div class="title"><?php echo $_page_lang['member_account.choose_your_preference']; ?></div>
        
        <div class="row">
            <label for="migration_destination"><?php echo $_page_lang['member_account.migration_destination']; ?></label>
            <select id="migration_destination" name="migration_destination">
                <option value=""><?php echo $_page_lang['please_select']; ?></option>
                <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                <option value="<?php echo $country_id; ?>"<?php echo ($target_member['migration_destination']==$country_id)?' selected':'';?>><?php echo $country; ?></option>
                <?php }} ?>
            </select>
        </div>
        <div class="clearboth"></div>

        <div class="row">
            <label for="interested_visa"><?php echo $_page_lang['member_account.interested_visa']; ?> <span style="color:red;">*</span></label>
            <select id="interested_visa" name="interested_visa" data-validation="required">
                <option value=""><?php echo $_page_lang['please_select']; ?></option>
                <?php if(!empty($_page_options['interest_visas'])) { foreach ($_page_options['interest_visas'] as $interest_visas_id => $interest_visas) { ?>
                <option value="<?php echo $interest_visas_id; ?>"<?php echo ($target_member['interested_visa']==$interest_visas_id)?' selected':'';?>><?php echo $interest_visas; ?></option>
                <?php }} ?>
            </select>
        </div>
        <div class="clearboth"></div>

        <div class="row">
            <label for="interested_topic"><?php echo $_page_lang['member_account.interested_topic']; ?> <span style="color:red;">*</span></label>
            <div class="iweb-checkbox-set">
                <?php if(!empty($_page_options['interest_topics'])) { foreach ($_page_options['interest_topics'] as $interest_topics_id => $interest_topics) { ?>
                <input type="checkbox" id="interested_topic_<?php echo $interest_topics_id; ?>" name="interested_topic[]" value="<?php echo $interest_topics_id; ?>"<?php echo (is_array($target_member['interested_topic']) && in_array($interest_topics_id, $target_member['interested_topic']))?' checked':'';?> data-validation="required">
                <label for="interested_topic_<?php echo $interest_topics_id; ?>"><?php echo $interest_topics; ?></label>
                <?php }} ?>
            </div>
        </div>
        <div class="clearboth"></div>
    </div>
    <?php } ?>
    
    <div class="widget">
        <div class="title"><?php echo $_page_lang['member_account.expiration_date']; ?></div>
        
        <?php if($target_member['type'] != 1) { ?>
        <div class="row">
            <label for="expiration_date_account">Account</label>
            <input type="text" class="datepicker" id="expiration_date_account" name="expiration_date_account" value="<?php echo $target_member['expiration_date_account']; ?>" data-validation="date">
        </div>
        <?php } ?>
        
        <div class="row left">
            <label for="expiration_date_visa_submission_ai">AI Migration Consultant</label>
            <input type="text" class="datepicker" id="expiration_date_visa_submission_ai" name="expiration_date_visa_submission_ai" value="<?php echo $target_member['expiration_date_visa_submission_ai']; ?>" data-validation="date">
        </div>

        <div class="row right">
            <label for="expiration_date_visa_submission_human">Human Migration Consultant</label>
            <input type="text" class="datepicker" id="expiration_date_visa_submission_human" name="expiration_date_visa_submission_human" value="<?php echo $target_member['expiration_date_visa_submission_human']; ?>" data-validation="date">
        </div>
        
        <div class="clearboth"></div>

     </div>
</form>
<style>
div.iweb-checkbox {
    width: 45%;
}

@media only screen and (max-width: 1040px) {
    div.iweb-checkbox {
        width: 100%;
    }
}

div.child-agent div.list.disabled {
    position: relative;
    opacity: 0.7;
}

div.child-agent div.list.disabled:after {
    position: absolute;
    content: '';
    top: 0px;
    left: 0px;
    width: 100%;
    height: 100%;
    z-index: 1;
}

div.child-agent div.block {
    position: relative;
    padding: 10px;
    border: 1px solid #e6e6e6;
    border-radius: 4px;
    margin-top: 10px;
}

div.child-agent div.block.hidden {
    display: none!important;
}

div.child-agent div.block a.remove-agent-block {
    position: absolute;
    display: inline-block;
    top: 10px;
    right: 10px;
    color: #bc002d;
    z-index: 1;
}

div.child-agent div.num {
    font-size: 16px;
    font-weight: bold;
    text-decoration: underline;
    padding-bottom: 10px;
}

div.child-agent a.add-agent-block {
    font-size: 16px;
}

div.child-agent a.add-agent-block > span {
    display: inline-block;
    vertical-align: middle;
}

div.child-agent a.add-agent-block > i {
    display: inline-block;
    vertical-align: middle;
}

div.child-lawfirm div.list.disabled {
    position: relative;
    opacity: 0.7;
}

div.child-lawfirm div.list.disabled:after {
    position: absolute;
    content: '';
    top: 0px;
    left: 0px;
    width: 100%;
    height: 100%;
    z-index: 1;
}

div.child-lawfirm div.block {
    position: relative;
    padding: 10px;
    border: 1px solid #e6e6e6;
    border-radius: 4px;
    margin-top: 10px;
}

div.child-lawfirm div.block.hidden {
    display: none!important;
}

div.child-lawfirm div.block a.remove-lawfirm-block {
    position: absolute;
    display: inline-block;
    top: 10px;
    right: 10px;
    color: #bc002d;
    z-index: 1;
}

div.child-lawfirm div.num {
    font-size: 16px;
    font-weight: bold;
    text-decoration: underline;
    padding-bottom: 10px;
}

div.child-lawfirm a.add-lawfirm-block {
    font-size: 16px;
}

div.child-lawfirm a.add-lawfirm-block > span {
    display: inline-block;
    vertical-align: middle;
}

div.child-lawfirm a.add-lawfirm-block > i {
    display: inline-block;
    vertical-align: middle;
}    
</style>
<script>
$(document).ready(function() {
    // agent
    $(document).on('click', '#registered_agent_yes', function() {
        $('div.child-agent div.list').removeClass('disabled');
        $('div.child-agent div.list').find('div.block').each(function() {
            if(!$(this).hasClass('hidden')) {
                $(this).find('input[type="text"]').attr('data-validation', 'required');
                $(this).find('select').attr('data-validation', 'required');
            }
        });
    });
    
    $(document).on('click', '#registered_agent_no', function() {
        $('div.child-agent div.list').addClass('disabled');
        $('div.child-agent div.list').find('div.block').each(function() {
            $(this).find('input[type="text"]').attr('data-validation', '');
            $(this).find('select').attr('data-validation', '');
        });
    });
    
    $(document).on('click', 'a.add-agent-block', function() {
        var clone = $('div.child-agent div.list').find('div.block').first().clone();
        clone.find('input[type="hidden"]').val(0).prop('disabled', false);
        clone.find('input[type="text"]').val('').prop('disabled', false);
        clone.find('select').val('').prop('disabled', false);
        clone.removeClass('hidden');
        clone.prepend('<a class="remove-agent-block"><i class="fa fa-times"></i></a>');
        $('div.child-agent div.list > div.items').append(clone).each(function() {
            $('div.child-agent div.num').each(function(key,value) {
                $(this).find('span').html(' - '+(key));
            });
        });
    });
    
    $(document).on('click', 'a.remove-agent-block', function() {
        $(this).closest('div.block').remove();
        $('div.child-agent div.num').each(function(key,value) {
            $(this).find('span').html(' - '+(key));
        });
    });

    // lawfirm
    $(document).on('click', '#registered_lawfirm_yes', function() {
        $('div.child-lawfirm div.list').removeClass('disabled');
        $('div.child-lawfirm div.list').find('div.block').each(function() {
            if(!$(this).hasClass('hidden')) {
                $(this).find('input[type="text"]').attr('data-validation', 'required');
                $(this).find('select').attr('data-validation', 'required');
            }
        });
    });
    
    $(document).on('click', '#registered_lawfirm_no', function() {
        $('div.child-lawfirm div.list').addClass('disabled');
        $('div.child-lawfirm div.list').find('div.block').each(function() {
            $(this).find('input[type="text"]').attr('data-validation', '');
            $(this).find('select').attr('data-validation', '');
        });
    });
    
    $(document).on('click', 'a.add-lawfirm-block', function() {
        var clone = $('div.child-lawfirm div.list').find('div.block').first().clone();
        clone.find('input[type="hidden"]').val(0).prop('disabled', false);
        clone.find('input[type="text"]').val('').prop('disabled', false);
        clone.find('select').val('').prop('disabled', false);
        clone.removeClass('hidden');
        clone.prepend('<a class="remove-lawfirm-block"><i class="fa fa-times"></i></a>');
        $('div.child-lawfirm div.list > div.items').append(clone).each(function() {
            $('div.child-lawfirm div.num').each(function(key,value) {
                $(this).find('span').html(' - '+(key));
            });
        });
    });
    
    $(document).on('click', 'a.remove-lawfirm-block', function() {
        $(this).closest('div.block').remove();
        $('div.child-lawfirm div.num').each(function(key,value) {
            $(this).find('span').html(' - '+(key));
        });
    });
});  
</script>
@endsection
