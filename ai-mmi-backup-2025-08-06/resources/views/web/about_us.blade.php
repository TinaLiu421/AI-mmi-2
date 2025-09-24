@extends('web.common')
@section('content')
<div class="inner-panel">
    <h1 class="title"><?php echo $_page_lang['about_us']; ?></h1>
    <div class="underline"></div>
    <div class="clearboth"></div>

    <?php if(!empty($_page_data['details']['content'])) { ?>
    <div>&nbsp;</div>
    <div>&nbsp;</div>
    <div class="iweb-editor">
        <?php echo $_page_data['details']['content']; ?>
        <div class="clearboth"></div>
    </div>
    <?php } ?>
    
    <?php /*
    <div class="shorcut">
        <a href="<?php echo ($_page_base_url.'/contact_us'); ?>"><?php echo $_page_lang['contact_us']; ?></a>
    </div>
     * 
     */
    ?>
    <div><h2 class="top-title"><?php echo $_page_lang['contact_us']; ?></h2></div>
    <div class="form">
        <form id="contact-form" name="contact-form" method="post" action="<?php echo $_page_base_url.'/about_us/contact';?>">
            <div>@csrf</div>
            
            <div class="row">
                <label for="name"><?php echo $_page_lang['contact_us_form.name']; ?> <span style="color:red;">*</span></label>
                <input type="text" id="name" name="name" value="" data-validation="required">
            </div>
            <div class="clearboth"></div>
            
            <div class="row">
                <label for="email"><?php echo $_page_lang['contact_us_form.email']; ?> <span style="color:red;">*</span></label>
                <input type="text" id="email" name="email" value="" data-validation="required|email">
            </div>
            <div class="clearboth"></div>
            
            <div class="row">
                <label for="subject"><?php echo $_page_lang['contact_us_form.subject']; ?> <span style="color:red;">*</span></label>
                <input type="text" id="subject" name="subject" value="" data-validation="required">
            </div>
            <div class="clearboth"></div>
            
            <div class="row">
                <label for="content"><?php echo $_page_lang['contact_us_form.content']; ?> <span style="color:red;">*</span></label>
                <textarea id="content" name="content" value="" data-validation="required"></textarea>
            </div>
            <div class="clearboth"></div>
            
            <div class="row">
                <button type="submit" class="btn btn-next"><?php echo $_page_lang['btn.submit']; ?></button>
            </div>
            <div class="clearboth"></div>
        </form>
    </div>
    
    
    <?php if(!empty($_page_data['list'])) { ?>
    <div class="list">
        <?php foreach ($_page_data['list'] as $key => $data) { ?>
        <div class="block">
            <a href="#">
                <h2 class="title"><?php echo $data['title']; ?></h2>
                <i class="fa fa-chevron-right"></i>
            </a>
            <div class="content">
                <div class="iweb-editor">
                    <?php echo $data['content']; ?>
                    <div class="clearboth"></div>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
    <?php } ?>
</div>
@endsection