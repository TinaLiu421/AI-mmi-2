@extends('web.common')
@section('content')
<?php $questions = $_page_data['questions']; ?>
<div class="inner-panel">
    <h1 class="title"><?php echo $_page_data['details']['title']; ?></h1>
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

    <div class="contact form">
        <div id="help-chat-panel">
            <?php foreach ($questions as $q_key => $q) { ?>
            <div class="dialog ask">
                <div class="avatar"><img src="asset/image/icon-member.png" alt="icon-member"><div style="background-image:url('asset/image/logo-mmi.png')"></div></div>
                <div class="name">AI-mmi</div>
                <div class="clearboth"></div>
                <div class="txt"><?php echo $q['title']; ?></div>
            </div>
            <div class="clearboth"></div>
            <?php break; } ?>
        </div>
        
        <form id="contact-form" method="post" data-showProcessing="0">
            <div>@csrf</div>
            <div class="row">
                <input type="text" id="inquiry" name="inquiry" value="" data-validation="required">
            </div>
            <div class="clearboth"></div>

            <div class="next" style="text-align:right;">
                <div class="row">
                    <button type="submit" class="btn"><?php echo $_page_lang['btn.send']; ?></button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection