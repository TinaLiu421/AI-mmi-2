@extends('web.common')
@section('content')
<link href="asset/css/web/service_provider_info.css?v=<?php echo date('Ymd'); ?>" rel="stylesheet" type="text/css">

<div class="inner-panel">
    <h1 class="title"><?php echo $_page_lang['service_provider_info.title']; ?></h1>
    <div class="underline"></div>
    <div class="clearboth"></div>

    <div>&nbsp;</div>
    <div>&nbsp;</div>

    <div class="iweb-editor">
        <h2><?php echo $_page_lang['service_provider_info.headline']; ?></h2>

        <p><?php echo $_page_lang['service_provider_info.intro_1']; ?></p>

        <p><?php echo $_page_lang['service_provider_info.intro_2']; ?></p>

        <p><?php echo $_page_lang['service_provider_info.intro_3']; ?></p>

        <p style="font-size: larger; padding: 20px 0; "><strong><?php echo $_page_lang['service_provider_info.free_join']; ?></strong></p>

        <p><?php echo $_page_lang['service_provider_info.verified_only']; ?></p>

        <div class="clearboth"></div>
    </div>

    <div class="card-section">
        <h3 class="card-title"><?php echo $_page_lang['service_provider_info.why_join_title']; ?></h3>
        <div class="iweb-editor">
            <ul>
                <li><?php echo $_page_lang['service_provider_info.benefit_1']; ?></li>
                <li><?php echo $_page_lang['service_provider_info.benefit_2']; ?></li>
                <li><?php echo $_page_lang['service_provider_info.benefit_3']; ?></li>
                <li><?php echo $_page_lang['service_provider_info.benefit_4']; ?></li>
                <li><?php echo $_page_lang['service_provider_info.benefit_5']; ?></li>
            </ul>
        </div>
    </div>

    <div class="card-section">
        <h3 class="card-title"><?php echo $_page_lang['service_provider_info.who_can_join_title']; ?></h3>
        <div class="iweb-editor">
            <ul>
                <li><?php echo $_page_lang['service_provider_info.who_1']; ?></li>
                <li><?php echo $_page_lang['service_provider_info.who_2']; ?></li>
                <li><?php echo $_page_lang['service_provider_info.who_3']; ?></li>
                <li><?php echo $_page_lang['service_provider_info.who_4']; ?></li>
                <li><?php echo $_page_lang['service_provider_info.who_5']; ?></li>
                <li><?php echo $_page_lang['service_provider_info.who_6']; ?></li>
                <li><?php echo $_page_lang['service_provider_info.who_7']; ?></li>
                <li><?php echo $_page_lang['service_provider_info.who_8']; ?></li>
            </ul>
        </div>
    </div>

    <div class="card-section">
        <h3 class="card-title"><?php echo $_page_lang['service_provider_info.how_it_works_title']; ?></h3>
        <div class="iweb-editor">
            <ol>
                <li><strong><?php echo $_page_lang['service_provider_info.step_1']; ?></strong></li>
                <li><strong><?php echo $_page_lang['service_provider_info.step_2']; ?></strong></li>
                <li><strong><?php echo $_page_lang['service_provider_info.step_3']; ?></strong></li>
                <li><strong><?php echo $_page_lang['service_provider_info.step_4']; ?></strong></li>
            </ol>
        </div>
    </div>

    <h2 class="top-title"><?php echo $_page_lang['service_provider_info.cta_title']; ?></h2>
    <div class="form">
        <p><?php echo $_page_lang['service_provider_info.cta_text']; ?></p>
        <div class="shorcut">
            <a href="<?php echo $_page_base_url.'/account_registration/service_provider'; ?>"><?php echo $_page_lang['service_provider_info.join_button']; ?></a>
        </div>
    </div>
</div>
@endsection
