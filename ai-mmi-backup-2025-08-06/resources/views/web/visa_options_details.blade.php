@extends('web.common')
@section('content')
<div class="banner">
    <div class="desktop">
        <?php if(!empty($_page_data['details']['media_files']['banner_'.$_current_lang_index])) { 
            foreach ($_page_data['details']['media_files']['banner_'.$_current_lang_index] as $banner) {
            ?>
        <img src="<?php echo $banner['url']; ?>" alt="<?php echo $banner['file_name']; ?>"/>
        <?php }} ?>
        <div class="countries"><!--
            --><div class="iweb-responsive" data-width="1300" data-height="245">
                <h2><?php echo (!empty($_page_data['target_country']))?$_page_data['target_country']['title']:''; ?></h2>
            </div><!--
        --></div>
    </div>
    <div class="mobile">
        <?php if(!empty($_page_data['details']['media_files']['mobile_banner_'.$_current_lang_index])) { 
            foreach ($_page_data['details']['media_files']['mobile_banner_'.$_current_lang_index] as $banner) {
            ?>
        <img src="<?php echo $banner['url']; ?>" alt="<?php echo $banner['file_name']; ?>"/>
        <?php }} ?>
        <div class="countries"><!--
            --><div class="iweb-responsive" data-width="800" data-height="400">
                <h2><?php echo (!empty($_page_data['target_country']))?$_page_data['target_country']['title']:''; ?></h2>
            </div><!--
        --></div>
    </div>
</div>
<div class="inner-panel">
    <h1 class="title"><?php echo (!empty($_page_data['sub_details']['sub_title']))?$_page_data['sub_details']['sub_title']:$_page_data['sub_details']['title']; ?></h1>
    <div class="underline"></div>
    <div class="clearboth"></div>

    <?php if(!empty($_page_data['sub_details']['content'])) { ?>
    <div>&nbsp;</div>
    <div>&nbsp;</div>
    <div class="iweb-editor">
        <?php echo $_page_data['sub_details']['content']; ?>
        <div class="clearboth"></div>
    </div>
    <?php } ?>
</div>
@endsection