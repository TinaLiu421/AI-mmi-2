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
   
    <?php if(!empty($_page_data['list_account_plans'])) { ?>
    <div class="list"><!--
        <?php foreach ($_page_data['list_account_plans'] as $key => $plan) { ?>
        <?php if($plan['type'] == 'migration_agent') continue; // Hide migration agent for now ?>
        --><div class="block plan">
            <div class="txt-1"><?php echo $_page_lang['sign_up_as']; ?></div>

            <div class="txt-2"><?php echo $plan['title'];?></div>

            <?php if($plan['type'] == 'service_provider') { ?>
            <!-- Service Provider: Always Free -->
            <div class="txt-3"><?php echo $_page_lang['free'];?></div>
            <div class="txt-4">&nbsp;</div>
            <div class="link-1">
                <a href="<?php echo $_page_base_url.'/account_registration/'.$plan['type'] ;?>"><?php echo $_page_lang['sign_up_now']; ?></a>
            </div>
            <div class="link-2">
                <a href="#">&nbsp;</a>
            </div>
            <?php } else { ?>
            <!-- Other account types: Show trial/pricing -->
            <?php if((int)$plan['valid_days_trial'] > 0) { ?>
            <div class="txt-3"><?php echo $_page_lang['free_trial'];?><span> /<?php echo str_replace('{num}', $plan['valid_days_trial'], $_page_lang['num_days']);?></span></div>
            <?php } else { ?>
            <div class="txt-3"><?php echo $_page_lang['free'];?></div>
            <?php } ?>

            <?php if((int)$plan['price'] > 0) { ?>
            <div class="txt-4"><?php echo str_replace('{price}', number_format($plan['price']), $_page_lang['price']); ?></div>
            <?php } else { ?>
            <div class="txt-4">&nbsp;</div>
            <?php } ?>

            <div class="link-1">
                <a href="<?php echo $_page_base_url.'/account_registration/'.$plan['type'] ;?>"><?php echo $_page_lang['sign_up_now']; ?></a>
            </div>

            <?php if((int)$plan['valid_days_trial'] > 0) { ?>
            <div class="link-2">
                <a href="<?php echo $_page_base_url.'/account_registration/'.$plan['type'] ;?>?trial=1"><?php echo $_page_lang['or_start_free_trial']; ?></a>
            </div>
            <?php } else { ?>
            <div class="link-2">
                <a href="#">&nbsp;</a>
            </div>
            <?php } ?>
            <?php } ?>

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