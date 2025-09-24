@extends('admin.common')
@section('content')
<?php
$privilege_user = 
[
    'id'            =>  0,
    'role_id'       =>  0,
    'name'          =>  '',
    'email'         =>  '',
    'password'      =>  '',
    'status'        =>  1,
    'signle_mode'   =>  0
];
if(!empty($_page_data['privilege_user'])) {
    $privilege_user = array_merge($privilege_user, $_page_data['privilege_user']);
}
?>
<form id="userform" name="userform" method="post">
    <div>@csrf</div>
    <div><input type="hidden" id="page_action" name="page_action" value="save"></div>
    <div><input type="hidden" id="user_id" name="user_id" value="<?php echo $privilege_user['id']; ?>"></div>
  
    <div class="widget thin fixed-top">
        <?php if(!empty($_page_setting['multi_language']) && count($_mapping_data['support_lang']) > 1) { ?>
        <div class="tab-lang">
            <?php foreach ($_mapping_data['support_lang'] as $lang_key => $lang) { ?>
            <a href="#" data-target="tab-content-<?php echo $lang_key; ?>"><?php echo $lang['short_name']; ?></a>
            <?php } ?>
            <div class="clearboth"></div>
        </div>
        <?php } ?>

        <?php if(empty($_page_readonly)) { ?>
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
        <?php } else if (!empty($privilege_user['id'])){ ?>
        <div class="controls right">
            <a class="btn btn-yellow" href="<?php echo url('admin/privilege/user/edit/'.$privilege_user['id']);?>">
                <i class="fa fa-pencil"></i>
                <span><?php echo $_page_lang['edit']; ?></span>
            </a>
        </div>
        <?php } ?>
        <div class="clearboth"></div>
    </div>
       
    <div class="widget">
        <?php if(!empty($_page_options['role'])) { ?>
        <div class="row">
            <label for="user_role"><?php echo $_page_lang['privilege_role']; ?></label>
            <?php if(empty($_page_readonly)) { ?>
            <select id="user_role" name="user_role">
                <option value="0"><?php echo $_page_lang['please_select']; ?></option>
                <?php foreach ($_page_options['role'] as $role_id => $role_name) { ?>
                <option value="<?php echo $role_id; ?>"<?php echo ($role_id == $privilege_user['role_id'])?' selected':''; ?>><?php echo $role_name; ?></option>
                <?php } ?>
            </select>
            <?php } else { ?>
            <span class="ivalue"><?php echo (!empty($_page_options['role'][$privilege_user['role_id']]))?$_page_options['role'][$privilege_user['role_id']]:''; ?></span>
            <?php } ?>
        </div>
        <?php } ?>

        <div class="row">
            <label for="user_name"><?php echo $_page_lang['user_name']; ?></label>
            <?php if(empty($_page_readonly)) { ?>
            <input type="text" id="user_name" name="user_name" value="<?php echo $privilege_user['name']; ?>" data-validation="required">
            <?php } else { ?>
            <span class="ivalue"><?php echo $privilege_user['name']; ?></span>
            <?php } ?>
        </div>

        <div class="row">
            <label for="user_email"><?php echo $_page_lang['user_email']; ?></label>
            <?php if(empty($_page_readonly)) { ?>
            <input type="text" id="user_email" name="user_email" value="<?php echo $privilege_user['email']; ?>" data-validation="required|email">
            <?php } else { ?>
            <span class="ivalue"><?php echo $privilege_user['email']; ?></span>
            <?php } ?>
        </div>
        
        <?php if(!empty($_page_options['role'])) { ?>
        <div class="row">
            <label for="user_status"><?php echo $_page_lang['status']; ?></label>
            <?php if(empty($_page_readonly)) { ?>
            <select id="user_status" name="user_status">
                <option value="1"<?php echo (1 == $privilege_user['status'])?' selected':''; ?>><?php echo $_page_lang['status_1']; ?></option>
                <option value="4"<?php echo (4 == $privilege_user['status'])?' selected':''; ?>><?php echo $_page_lang['status_4']; ?></option>
            </select>
            <?php } else { ?>
            <span class="ivalue"><?php echo (!empty($_page_lang['status_'.$privilege_user['status']]))?$_page_lang['status_'.$privilege_user['status']]:'-'; ?></span>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
    
    <?php if(empty($_page_readonly)) { ?>
    <?php if((int)$privilege_user['id'] == 0) { ?>
    <div class="widget">
        <div class="row">
            <label for="user_password"><?php echo $_page_lang['user_password']; ?></label>
            <input type="password" id="user_password" name="user_password" value="" data-validation="required|password">
        </div>

        <div class="row">
            <label for="user_repeat_password"><?php echo $_page_lang['user_repeat_password']; ?></label>
            <input type="password" id="user_repeat_password" name="user_repeat_password" value="" data-validation="required|password">
        </div>
    </div>
    <?php } else { ?>
    <div class="widget">
        <div class="row">
            <label><?php echo $_page_lang['user_password']; ?></label>
            <input type="password" id="user_password" name="user_password" value="" data-validation="password">
        </div>

        <div class="row">
            <label><?php echo $_page_lang['user_repeat_password']; ?></label>
            <input type="password" id="user_repeat_password" name="user_repeat_password" value="" data-validation="password">
        </div>
    </div>
    <?php } ?>
    <?php } ?>
</form>
@endsection