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
            
            <div class="recaptcha-container" style="margin: 20px 0; text-align: center;">
                <p style="margin-bottom:8px;">Security verification: Please verify you're human before submitting.</p>
                <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}" data-callback="onRecaptchaSuccess" data-expired-callback="onRecaptchaExpire"></div>
                <input type="hidden" id="recaptcha_token" name="g-recaptcha-response" value="">
            </div>
            <div class="row">
                <button type="submit" class="btn btn-next" id="submit-contact" disabled style="opacity:0.6;cursor:not-allowed;" title="Please verify with reCAPTCHA first"><?php echo $_page_lang['btn.submit']; ?></button>
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
<<<<<<< Updated upstream
=======

>>>>>>> Stashed changes
<!-- reCAPTCHA script & client handlers -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
    function onRecaptchaSuccess() {
        var btn = document.getElementById('submit-contact');
        if (btn) {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
            btn.title = '';
        }
        var tokenInput = document.getElementById('recaptcha_token');
        if (tokenInput && typeof grecaptcha !== 'undefined') {
            tokenInput.value = grecaptcha.getResponse();
        }
    }

    function onRecaptchaExpire() {
        var btn = document.getElementById('submit-contact');
        if (btn) {
            btn.disabled = true;
            btn.style.opacity = '0.6';
            btn.style.cursor = 'not-allowed';
            btn.title = 'Please verify with reCAPTCHA first';
        }
        var tokenInput = document.getElementById('recaptcha_token');
        if (tokenInput) {
            tokenInput.value = '';
        }
    }
</script>
@endsection