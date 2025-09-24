@extends('admin.common')
@section('content')

<form id="generalform" name="generalform" method="post">
    <div>@csrf</div>
    <div><input type="hidden" id="page_action" name="page_action" value="save"></div>
    
    <div class="widget thin fixed-top">
        <?php if(count($_mapping_data['support_lang']) > 1) { ?>
        <div class="tab-lang">
            <?php foreach ($_mapping_data['support_lang'] as $lang_key => $lang) { ?>
            <a href="#" data-target="tab-content-<?php echo $lang_key; ?>"><?php echo $lang['short_name']; ?></a>
            <?php } ?>
            <div class="clearboth"></div>
        </div>
        <?php } ?>
        <div class="controls right">
             <?php if(count($_mapping_data['support_lang']) > 1) { 
                $ts_translation_tindex = 0;
                $ts_translation_sindex = 0;
                foreach ($_mapping_data['support_lang'] as $lang_key => $lang) {
                    if(strtolower($lang['code']) == 'zh-hant') {
                        $ts_translation_tindex = $lang_key;
                    }
                    else if(strtolower($lang['code']) == 'zh-hans') {
                        $ts_translation_sindex = $lang_key;
                    }
                }
                if(!empty($ts_translation_tindex) && !empty($ts_translation_sindex)) {
                ?>
            <button type="button" class="btn btn-blue btn-ts-translation" data-tindex="<?php echo $ts_translation_tindex; ?>" data-sindex="<?php echo $ts_translation_sindex; ?>">
                <i class="fa fa-language"></i>
                <span>繁轉简</span>
            </button>
            <?php }} ?>
            
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

    <?php foreach ($_mapping_data['support_lang'] as $lang_key => $lang) { ?>
    <div class="tab-content tab-content-<?php echo $lang_key; ?>">
        <div class="widget">
            <div class="row">
                <label for="meta_title_<?php echo $lang_key; ?>"><?php echo $_page_lang['meta_title']; ?></label>
                <input type="text" id="meta_title_<?php echo $lang_key; ?>" name="meta_title[<?php echo $lang_key; ?>]" value="<?php echo (!empty($_page_data['meta_title'][$lang_key]))?$_page_data['meta_title'][$lang_key]:''; ?>" data-validation="required" data-translation="<?php echo $lang_key; ?>">
            </div>

            <div class="row">
                <label for="meta_description_<?php echo $lang_key; ?>"><?php echo $_page_lang['meta_description']; ?></label>
                <textarea id="meta_description_<?php echo $lang_key; ?>" name="meta_description[<?php echo $lang_key; ?>]" data-translation="<?php echo $lang_key; ?>"><?php echo (!empty($_page_data['meta_description'][$lang_key]))?$_page_data['meta_description'][$lang_key]:''; ?></textarea>
            </div>

            <div class="row">
                <button type="button" class="btn btn-green upload" data-target="meta_image_<?php echo $lang_key; ?>"><i class="fa fa-cloud-upload"></i></button>
                <label for="meta_image_<?php echo $lang_key; ?>"><?php echo $_page_lang['meta_image']; ?></label>
                <input type="text" class="meta_image" id="meta_image_<?php echo $lang_key; ?>" name="meta_image[<?php echo $lang_key; ?>]" value="<?php echo (!empty($_page_data['meta_image'][$lang_key]))?$_page_data['meta_image'][$lang_key]:''; ?>" data-translation="<?php echo $lang_key; ?>" style="padding-right:40px;">
            </div>
            
            <div class="row">
                <label for="contact_telephone_<?php echo $lang_key; ?>"><?php echo $_page_lang['contact_telephone']; ?></label>
                <input type="text" id="contact_telephone_<?php echo $lang_key; ?>" name="contact_telephone[<?php echo $lang_key; ?>]" value="<?php echo (!empty($_page_data['contact_telephone'][$lang_key]))?$_page_data['contact_telephone'][$lang_key]:''; ?>" data-translation="<?php echo $lang_key; ?>">
            </div>
            
            <div class="row">
                <label for="contact_fax_<?php echo $lang_key; ?>"><?php echo $_page_lang['contact_fax']; ?></label>
                <input type="text" id="contact_fax_<?php echo $lang_key; ?>" name="contact_fax[<?php echo $lang_key; ?>]" value="<?php echo (!empty($_page_data['contact_fax'][$lang_key]))?$_page_data['contact_fax'][$lang_key]:''; ?>" data-translation="<?php echo $lang_key; ?>">
            </div>
            
            <div class="row">
                <label for="contact_email_<?php echo $lang_key; ?>"><?php echo $_page_lang['contact_email']; ?></label>
                <input type="text" id="contact_email_<?php echo $lang_key; ?>" name="contact_email[<?php echo $lang_key; ?>]" value="<?php echo (!empty($_page_data['contact_email'][$lang_key]))?$_page_data['contact_email'][$lang_key]:''; ?>" data-translation="<?php echo $lang_key; ?>">
            </div>
            
            <div class="row">
                <label for="contact_whatsapp_<?php echo $lang_key; ?>"><?php echo $_page_lang['contact_whatsapp']; ?></label>
                <input type="text" id="contact_whatsapp_<?php echo $lang_key; ?>" name="contact_whatsapp[<?php echo $lang_key; ?>]" value="<?php echo (!empty($_page_data['contact_whatsapp'][$lang_key]))?$_page_data['contact_whatsapp'][$lang_key]:''; ?>" data-translation="<?php echo $lang_key; ?>">
            </div>
            
            <div class="row">
                <label for="contact_facebook_<?php echo $lang_key; ?>"><?php echo $_page_lang['contact_facebook']; ?></label>
                <input type="text" id="contact_facebook_<?php echo $lang_key; ?>" name="contact_facebook[<?php echo $lang_key; ?>]" value="<?php echo (!empty($_page_data['contact_facebook'][$lang_key]))?$_page_data['contact_facebook'][$lang_key]:''; ?>" data-translation="<?php echo $lang_key; ?>">
            </div>
            
            <div class="row">
                <label for="contact_ig_<?php echo $lang_key; ?>"><?php echo $_page_lang['contact_ig']; ?></label>
                <input type="text" id="contact_ig_<?php echo $lang_key; ?>" name="contact_ig[<?php echo $lang_key; ?>]" value="<?php echo (!empty($_page_data['contact_ig'][$lang_key]))?$_page_data['contact_ig'][$lang_key]:''; ?>" data-translation="<?php echo $lang_key; ?>">
            </div>
            
            <div class="row">
                <label for="contact_address_<?php echo $lang_key; ?>"><?php echo $_page_lang['contact_address']; ?></label>
                <textarea id="contact_address_<?php echo $lang_key; ?>" name="contact_address[<?php echo $lang_key; ?>]" data-translation="<?php echo $lang_key; ?>"><?php echo (!empty($_page_data['contact_address'][$lang_key]))?$_page_data['contact_address'][$lang_key]:''; ?></textarea>
            </div>
            
            <div class="row">
                <label for="contact_map_<?php echo $lang_key; ?>"><?php echo $_page_lang['contact_map']; ?></label>
                <textarea id="contact_map_<?php echo $lang_key; ?>" name="contact_map[<?php echo $lang_key; ?>]" data-translation="<?php echo $lang_key; ?>"><?php echo (!empty($_page_data['contact_map'][$lang_key]))?$_page_data['contact_map'][$lang_key]:''; ?></textarea>
            </div>
        </div>
    </div>
    <?php } ?>
</form>
@endsection