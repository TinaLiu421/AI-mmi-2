@extends('admin.common')
@section('content')
<?php
$privilege_role = 
[
    'id'        =>  0,
    'name'      =>  '',
    'allowed'   =>  []
];

if(!empty($_page_data['privilege_role'])) {
    $privilege_role= array_merge($privilege_role, $_page_data['privilege_role']);
}

$section_allowed = [
    'home' => [
        'alias'     =>  $_page_lang['home'],
        'allowed'   =>  
        [
            '101'   =>  $_page_lang['allowed.access']
        ]
    ],
    'pages' => [
        'alias'     =>  $_page_lang['pages'],
        'allowed'   =>  
        [
            '101'   =>  $_page_lang['allowed.access'],
            '102'   =>  $_page_lang['allowed.add'],
            '103'   =>  $_page_lang['allowed.edit'],
            '104'   =>  $_page_lang['allowed.delete']
        ]
    ],
    'media_files' => [
        'alias'     =>  $_page_lang['media_files'],
        'allowed'   =>  
        [
            '101'   =>  $_page_lang['allowed.access'],
            '102'   =>  $_page_lang['allowed.add'],
            '103'   =>  $_page_lang['allowed.edit'],
            '104'   =>  $_page_lang['allowed.delete']
        ]
    ]
]
?>
<form id="roleform" name="roleform" method="post">
    <div>@csrf</div>
    <div><input type="hidden" id="page_action" name="page_action" value="save"></div>
    <div><input type="hidden" id="role_id" name="role_id" value="<?php echo $privilege_role['id']; ?>"></div>

    <div class="widget thin fixed-top">
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
        <div class="row">
            <label for="role_name"><?php echo $_page_lang['name']; ?></label>
            <?php if(empty($_page_readonly)) { ?>
            <input type="text" id="role_name" name="role_name" value="<?php echo $privilege_role['name']; ?>" data-validation="required">
            <?php } else { ?>
            <span class="ivalue"><?php echo $privilege_role['name']; ?></span>
            <?php } ?>
        </div>
    </div>
    
    <div class="widget">
        <table class="list">
            <thead>
                <tr>
                    <th width="50%"><?php echo $_page_lang['privilege_section']; ?></th>
                    <th width="50%"><?php echo $_page_lang['privilege_allowed']; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($section_allowed)) { foreach ($section_allowed as $key => $value) { ?>
                <tr>
                    <td><?php echo (!empty($value['alias']))?$value['alias']: ucwords(str_replace('_', ' ', strtolower($key))); ?></td>
                    <td>
                        <?php if(!empty($value['allowed'])) { foreach ($value['allowed'] as $child_key => $child_value) { ?>
                        <div style="margin-bottom:5px;">
                            <input type="checkbox" class="role_allowed" id="role_allowed_<?php echo $key;?>_<?php echo $child_key;?>" name="role_allowed[<?php echo $key;?>][]" 
                               value="<?php echo $child_key;?>"<?php echo ((!empty($privilege_role['allowed'][$key])) && in_array($child_key, $privilege_role['allowed'][$key]))?' checked':'';?>
                               <?php echo (empty($_page_readonly))?'':' disabled'; ?>>
                            <label for="role_allowed_<?php echo $key;?>_<?php echo $child_key;?>"><?php echo $child_value; ?></label>
                        </div>
                        <?php }} ?>
                    </td>
                </tr>
                <?php }} ?>
            </tbody>
        </table>
    </div>
</form>
@endsection