@extends('admin.common')
@section('content')
<?php 
$parent_id = (isset($_page_get_data['parent_id']))?$_page_get_data['parent_id']:'';
$form_parent_data = (isset($_page_data['parent_data']))?$_page_data['parent_data']:[];
$form_data = (isset($_page_data['data']))?$_page_data['data']:[];

if(!empty($form_data)) {
    foreach ($form_data as $first) {
        $form_data_first = $first;
        break;
    }
}
else {
    $form_data_first = [];
}
if(empty($form_data_first['utoken'])) {
    $form_data_first['utoken'] = md5(uniqid(rand()));
}

$_page_setting['rows_share'] = [];
if($_page_setting['rows']) {
    foreach ($_page_setting['rows'] as $row_key => $row) { 
        if(!empty($row['share']) || empty($_page_setting['multi_language'])) {
            unset($_page_setting['rows'][$row_key]);
            $_page_setting['rows_share'][] = $row;
        }
    }
}
?>
<div class="t-form">
    <form id="myform" name="myform" method="post">
        <div>@csrf</div>
        <div><input type="hidden" id="page_action" name="page_action" value="save"></div>
        <div><input type="hidden" id="parent_id" name="parent_id" value="<?php echo $parent_id; ?>"></div>
        <div><input type="hidden" id="id" name="id" value="<?php echo (!empty($form_data_first['id']))?$form_data_first['id']:0; ?>"></div>
        <div><input type="hidden" id="utoken" name="utoken" value="<?php echo $form_data_first['utoken']; ?>"></div>
        
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
                <?php if(!empty($_page_setting['ts_translation']) && ((!empty($_page_setting['multi_language']) && count($_mapping_data['support_lang']) > 1))) { 
                    $ts_translation_tindex = 0;
                    $ts_translation_sindex = 0;
                    $ts_translation_eindex = 0;
                    foreach ($_mapping_data['support_lang'] as $lang_key => $lang) {
                        if(strtolower($lang['code']) == 'zh-hant') {
                            $ts_translation_tindex = $lang_key;
                        }
                        else if(strtolower($lang['code']) == 'zh-hans') {
                            $ts_translation_sindex = $lang_key;
                        }
                        else if(strtolower($lang['code']) == 'en') {
                            $ts_translation_eindex = $lang_key;
                        }
                    }
                    if(!empty($ts_translation_tindex) && !empty($ts_translation_sindex)) {
                    ?>
                    <div class="translation-tab translation-tab-<?php echo $ts_translation_tindex; ?> translation-tab-<?php echo $ts_translation_sindex; ?>">
                        <button type="button" class="btn btn-blue btn-ts-translation" data-tindex="<?php echo $ts_translation_tindex; ?>" data-sindex="<?php echo $ts_translation_sindex; ?>" data-type="t">
                            <i class="fa fa-language"></i>
                            <span>繁轉简</span>
                        </button>
                    </div>
                    <?php } ?>
                <?php } ?>
                
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
        
        <?php if(!empty($_page_setting['custom_link'])) { 
            $parent_path = []; 
            if(!empty($form_parent_data)) {
                foreach ($form_parent_data as $temp_parent) {
                    if($temp_parent['id'] !== ((!empty($form_data_first['id']))?$form_data_first['id']:0)) {
                        $parent_path[] = $temp_parent['path'];
                    }
                }
            }
            ?>
        <div class="widget">
            <table class="path">
                <tr>
                    <td><?php echo rtrim(preg_replace('/(admin\/|admin)$/i', '', $_page_base_url),'/'); ?>/<?php echo (!empty($_page_setting['multi_language']) && count($_mapping_data['support_lang']) > 1)?($_current_lang_code.'/'):''; ?><?php echo (!empty($parent_path))?(implode('/', $parent_path).'/'):''; ?></td>
                    <td><input type="text" class="custom_link_path" name="path" value="<?php echo (!empty($form_data_first['path']))?$form_data_first['path']:''; ?>" data-validation="required" data-error="<?php echo $_page_lang['custom_link_tips']; ?>"></td>
                </tr>
            </table>
        </div>
        <?php } ?>

        <?php if($_page_setting['rows']) { ?>
        <?php foreach ($_mapping_data['support_lang'] as $lang_key => $lang) { ?>
        <div class="tab-content tab-content-<?php echo $lang_key; ?>">
            <div class="widget">
                <?php foreach ($_page_setting['rows'] as $row) { ?>
                <div class="row">
                    <label for="<?php echo $row['name']; ?>_<?php echo $lang_key; ?>"><?php echo ucwords(strtolower((isset($row['alias']))?$row['alias']:$row['name'])); ?></label>
                    <?php if($row['type'] == 'select') { 
                        if(empty($form_data[$lang_key][$row['name']])) {
                            $form_data[$lang_key][$row['name']] = '';
                        }
                        ?>
                    <select id="<?php echo $row['name']; ?>_<?php echo $lang_key; ?>" name="<?php echo $row['name']; ?>[<?php echo $lang_key; ?>]"<?php echo (!empty($row['validation']))?' data-validation="required"':'';?> data-translation="<?php echo $lang_key; ?>">
                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                        <?php if(isset($row['options'])) { foreach ($row['options'] as $option_key => $option) { ?>
                        <option value="<?php echo $option_key; ?>"<?php echo (!empty($form_data[$lang_key][$row['name']]) && $form_data[$lang_key][$row['name']] == $option_key)?' selected':'' ;?>><?php echo $option; ?></option>
                        <?php }} ?>
                    </select>
                    
                    <?php } else if($row['type'] == 'select_multi') {
                        if(empty($form_data[$lang_key][$row['name']])) {
                            $form_data[$lang_key][$row['name']] = [];
                        }
                        ?>
                    <select id="<?php echo $row['name']; ?>_<?php echo $lang_key; ?>" name="<?php echo $row['name']; ?>[<?php echo $lang_key; ?>][]" multiple<?php echo (!empty($row['validation']))?' data-validation="required"':'';?> data-translation="<?php echo $lang_key; ?>">
                        <?php if(isset($row['options'])) { foreach ($row['options'] as $option_key => $option) { ?>
                        <option value="<?php echo $option_key; ?>"<?php echo (!empty($form_data[$lang_key][$row['name']]) && in_array($option_key,$form_data[$lang_key][$row['name']]))?' selected':'' ;?>><?php echo $option; ?></option>
                        <?php }} ?>
                    </select>
                    
                    <?php } else if($row['type'] == 'textarea') { ?>
                    <textarea id="<?php echo $row['name']; ?>_<?php echo $lang_key; ?>" name="<?php echo $row['name']; ?>[<?php echo $lang_key; ?>]"<?php echo (!empty($row['validation']))?' data-validation="'.$row['validation'].'"':'';?> data-translation="<?php echo $lang_key; ?>"><?php echo (isset($form_data[$lang_key][$row['name']]))?$form_data[$lang_key][$row['name']]:'' ;?></textarea>
                   
                    <?php } else if($row['type'] == 'editor') { ?>
                    <textarea class="editor" id="<?php echo $row['name']; ?>_<?php echo $lang_key; ?>" name="<?php echo $row['name']; ?>[<?php echo $lang_key; ?>]"<?php echo (!empty($row['validation']))?' data-validation="'.$row['validation'].'"':'';?> data-translation="<?php echo $lang_key; ?>"><?php echo (isset($form_data[$lang_key][$row['name']]))?$form_data[$lang_key][$row['name']]:'' ;?></textarea>
                    
                    <?php } else { ?>
                    <input type="text" class="<?php echo ($row['type'] == 'date')?'datepicker':(($row['type'] == 'color')?'colorpicker':'');?>" id="<?php echo $row['name']; ?>_<?php echo $lang_key; ?>" name="<?php echo $row['name']; ?>[<?php echo $lang_key; ?>]" value="<?php echo (isset($form_data[$lang_key][$row['name']]))?$form_data[$lang_key][$row['name']]:'' ;?>"<?php echo (!empty($row['validation']))?' data-validation="'.$row['validation'].'"':'';?> data-translation="<?php echo $lang_key; ?>">
                    <?php } ?>
                </div>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
        <?php } ?>
        
        <?php if($_page_setting['rows_share']) { ?>
        <div class="widget">
            <?php foreach ($_page_setting['rows_share'] as $row) { ?>
            <div class="row">
                <label for="<?php echo $row['name']; ?>"><?php echo ucwords(strtolower((isset($row['alias']))?$row['alias']:$row['name'])); ?></label>
                <?php if($row['type'] == 'select') { 
                    if(empty($form_data_first[$row['name']])) {
                        $form_data_first[$row['name']] = '';
                    }
                    ?>
                <select id="<?php echo $row['name']; ?>" name="<?php echo $row['name']; ?>"<?php echo (!empty($row['validation']))?' data-validation="'.$row['validation'].'"':'';?>>
                    <option value=""><?php echo $_page_lang['please_select']; ?></option>
                    <?php if(isset($row['options'])) { foreach ($row['options'] as $option_key => $option) { ?>
                    <option value="<?php echo $option_key; ?>"<?php echo (!empty($form_data_first[$row['name']]) && $form_data_first[$row['name']] == $option_key)?' selected':'' ;?>><?php echo $option; ?></option>
                    <?php }} ?>
                </select>
                
                <?php } else if($row['type'] == 'select_multi') { 
                    if(empty($form_data_first[$row['name']])) {
                        $form_data_first[$row['name']] = [];
                    }
                    ?>
                <select id="<?php echo $row['name']; ?>" name="<?php echo $row['name']; ?>[0][]" multiple<?php echo (!empty($row['validation']))?' data-validation="'.$row['validation'].'"':'';?>>
                    <?php if(isset($row['options'])) { foreach ($row['options'] as $option_key => $option) { ?>
                    <option value="<?php echo $option_key; ?>"<?php echo (!empty($form_data_first[$row['name']]) && in_array($option_key,  $form_data_first[$row['name']]))?' selected':'' ;?>><?php echo $option; ?></option>
                    <?php }} ?>
                </select>
                
                <?php } else if($row['type'] == 'textarea') { ?>
                <textarea id="<?php echo $row['name']; ?>" name="<?php echo $row['name']; ?>"<?php echo (!empty($row['validation']))?' data-validation="'.$row['validation'].'"':'';?>><?php echo (isset($form_data_first[$row['name']]))?$form_data_first[$row['name']]:'' ;?></textarea>
                
                <?php } else if($row['type'] == 'editor') { ?>
                <textarea id="<?php echo $row['name']; ?>" class="editor" name="<?php echo $row['name']; ?>"<?php echo (!empty($row['validation']))?' data-validation="'.$row['validation'].'"':'';?>><?php echo (isset($form_data_first[$row['name']]))?$form_data_first[$row['name']]:'' ;?></textarea>
                
                <?php } else { ?>
                <input type="text" class="<?php echo ($row['type'] == 'date')?'datepicker':(($row['type'] == 'color')?'colorpicker':'');?>" id="<?php echo $row['name']; ?>" name="<?php echo $row['name']; ?>" value="<?php echo (isset($form_data_first[$row['name']]))?$form_data_first[$row['name']]:'' ;?>"<?php echo (!empty($row['validation']))?' data-validation="'.$row['validation'].'"':'';?>>
                <?php } ?>
            </div>
            <?php } ?>
        </div>
        <?php } ?>
        
        <?php if(!empty($_page_setting['customize'])) { foreach ($_page_setting['customize'] as $customize) { ?>
        <div class="widget middle">
            <div><?php echo $customize; ?></div>
        </div>
        <?php }} ?>
        
        <?php if($_page_setting['seo']) { ?>
        <div class="expand-area">
            <div class="show">
                <a>
                    <span>SEO</span>
                    <i class="fa fa-plus"></i>
                    <i class="fa fa-minus"></i>
                </a>
            </div>
            <div class="hide">
                <?php if(!empty($_page_setting['multi_language']) && count($_mapping_data['support_lang']) > 1) { ?>
                <?php foreach ($_mapping_data['support_lang'] as $lang_key => $lang) { ?>
                <div class="tab-content tab-content-<?php echo $lang_key; ?>">
                    <div class="widget seo non">
                        <div class="row">
                            <label for="meta_title_<?php echo $lang_key; ?>"><?php echo $_page_lang['meta_title']; ?></label>
                            <input type="text" id="meta_title_<?php echo $lang_key; ?>" name="meta_title[<?php echo $lang_key; ?>]" value="<?php echo (isset($form_data[$lang_key]['meta_title']))?$form_data[$lang_key]['meta_title']:''; ?>" data-translation="<?php echo $lang_key; ?>">
                        </div>

                        <div class="row">
                            <label for="meta_description_<?php echo $lang_key; ?>"><?php echo $_page_lang['meta_description']; ?></label>
                            <textarea id="meta_description_<?php echo $lang_key; ?>" name="meta_description[<?php echo $lang_key; ?>]" data-translation="<?php echo $lang_key; ?>"><?php echo (isset($form_data[$lang_key]['meta_description']))?$form_data[$lang_key]['meta_description']:''; ?></textarea>
                        </div>

                        <div class="row">
                            <button type="button" class="btn btn-green upload" data-target="meta_image_<?php echo $lang_key; ?>"><i class="fa fa-cloud-upload"></i></button>
                            <label for="meta_image_<?php echo $lang_key; ?>"><?php echo $_page_lang['meta_image']; ?></label>
                            <input type="text" class="meta_image" id="meta_image_<?php echo $lang_key; ?>" name="meta_image[<?php echo $lang_key; ?>]" value="<?php echo (isset($form_data[$lang_key]['meta_image']))?$form_data[$lang_key]['meta_image']:''; ?>" data-translation="<?php echo $lang_key; ?>" style="padding-right:40px;">
                        </div>
                    </div>
                </div>
                <?php }} else { ?>
                <div class="widget seo non">
                    <div class="row">
                        <label for="meta_title"><?php echo $_page_lang['meta_title']; ?></label>
                        <input type="text" id="meta_title" name="meta_title" value="<?php echo (isset($form_data_first['meta_title']))?$form_data_first['meta_title']:''; ?>">
                    </div>

                    <div class="row">
                        <label for="meta_description"><?php echo $_page_lang['meta_description']; ?></label>
                        <textarea id="meta_description" name="meta_description"><?php echo (isset($form_data_first['meta_description']))?$form_data_first['meta_description']:''; ?></textarea>
                    </div>

                    <div class="row">
                        <button type="button" class="btn btn-green upload" data-target="meta_image"><i class="fa fa-cloud-upload"></i></button>
                        <label for="meta_image"><?php echo $_page_lang['meta_image']; ?></label>
                        <input type="text" class="meta_image" id="meta_image" name="meta_image" value="<?php echo (isset($form_data_first['meta_image']))?$form_data_first['meta_image']:''; ?>" style="padding-right:40px;">
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
        
        <?php if(isset($_page_setting['files']) && is_array($_page_setting['files'])) { ?>
        <?php foreach ($_page_setting['files'] as $file_key => $file) { ?>
        <div class="expand-area">
            <div class="show">
                <a>
                    <span><?php echo (isset($file['alias']))?$file['alias']: ucwords(str_replace('_', ' ', $file['name']));?> <?php if(isset($file['w']) && isset($file['h'])) { echo ' | '.$file['w'].'*'.$file['h']; } ?></span>
                    <i class="fa fa-plus"></i>
                    <i class="fa fa-minus"></i>
                </a>
            </div>
            <div class="hide">
                <div class="widget non selfile">
                    <div class="controls">
                        <button type="button" class="btn btn-red btn-delete-files">
                            <i class="fa fa-trash"></i>
                            <span><?php echo $_page_lang['delete']; ?></span>
                        </button>
                        
                        <button type="button" class="btn btn-yellow btn-select-files" 
                                data-url="<?php echo url(implode('/', array_filter([$_mapping_data['module'], 'media_files']))); ?>"
                                data-token="<?php echo $form_data_first['utoken']; ?>"
                                data-type="<?php echo (isset($file['type']))?$file['type']:'page'; ?>"
                                data-category="<?php echo $file['name']; ?>"
                                data-max="<?php echo (isset($file['max']))?$file['max']:64; ?>"
                                data-allowed="<?php echo (isset($file['allowed']))?$file['allowed']:''; ?>">
                            <i class="fa fa-cloud-upload"></i>
                            <span><?php echo $_page_lang['add'];?></span>
                        </button>
                    </div>
                    <div class="clearboth"></div>
                    <div class="list" 
                         data-token="<?php echo $form_data_first['utoken']; ?>"
                         data-type="<?php echo (isset($file['type']))?$file['type']:'page'; ?>"
                         data-category="<?php echo $file['name']; ?>">
                    </div>
                </div>
            </div>
        </div>
        <?php }} ?>
    </form>
</div>
@endsection