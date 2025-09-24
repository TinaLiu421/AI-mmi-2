@extends('web.common')
@section('content')
<div class="inner-panel">
    <h1 class="title"><?php echo $_page_lang['choose_a_plan']; ?></h1>
    <?php if(!empty($_page_data['details']['content'])) { ?>
    <div class="top-brief iweb-editor">
        <?php echo $_page_data['details']['content']; ?>
        <div class="clearboth"></div>
    </div>
    <?php } ?>
    <div class="underline"></div>
    <div class="clearboth"></div>
   
    <?php if(!empty($_page_data['list_plan_visa_submission'])) { ?>
    <div class="list"><!--
        <?php foreach ($_page_data['list_plan_visa_submission'] as $key => $plan) { ?>
        --><div class="block plan">
            <div class="txt-1"><?php echo $plan['brief']; ?></div>

            <div class="txt-2"><?php echo $plan['title'];?></div>

            <?php if((int)$plan['price'] > 0) { ?>
            <div class="txt-4"><?php echo str_replace('{price}', number_format($plan['price']), $_page_lang['price_2']); ?></div>
            <?php } else { ?>
            <div class="txt-4">&nbsp;</div>
            <?php } ?>

            <div class="link-1">
                <a href="<?php echo $_page_base_url.'/account_submission/payment/'.$plan['id'] ;?>"><?php echo $_page_lang['btn.pay_now']; ?></a>
            </div>

            <?php if(!empty($plan['content'])) { ?>
            <div class="benefits">
                <span><i class="fa fa-plus"></i> <?php echo $_page_lang['benefits']; ?></span>
                <div class="iweb-editor">
                    <?php echo $plan['content']; ?>
                    <div class="clearboth"></div>
                </div>
            </div>
            <?php } ?>
        </div><!--
        <?php } ?>
    --></div>
    <?php } ?>
</div>
@endsection