@extends('web.common')
@section('content')
<link href="asset/css/web/service_provider_info.css?v=<?php echo date('Ymd'); ?>" rel="stylesheet" type="text/css">

<div class="inner-panel service-provider-info">
    <h1 class="title"><?php echo $_page_lang['service_provider_info.title']; ?></h1>
    <div class="underline"></div>
    <div class="clearboth"></div>

    <div>&nbsp;</div>
    <div>&nbsp;</div>

    <div class="iweb-editor">
        <h2 class="main-headline">
            <?php echo $_page_lang['service_provider_info.headline']; ?>
        </h2>

        <p class="intro-text">
            <?php echo $_page_lang['service_provider_info.intro_1']; ?>
        </p>

        <p class="intro-text">
            <?php echo $_page_lang['service_provider_info.intro_2']; ?>
        </p>

        <p class="intro-text">
            <?php echo $_page_lang['service_provider_info.intro_3']; ?>
        </p>

        <p class="free-join-highlight">
            <span>👉</span>
            <span><?php echo $_page_lang['service_provider_info.free_join']; ?></span>
        </p>

        <p class="intro-text">
            <?php echo $_page_lang['service_provider_info.verified_only']; ?>
        </p>

        <div class="info-section why-join-section">
            <h3 class="section-title">
                <span>🌟</span>
                <span><?php echo $_page_lang['service_provider_info.why_join_title']; ?></span>
            </h3>
            <ul class="list">
                <li><?php echo $_page_lang['service_provider_info.benefit_1']; ?></li>
                <li><?php echo $_page_lang['service_provider_info.benefit_2']; ?></li>
                <li><?php echo $_page_lang['service_provider_info.benefit_3']; ?></li>
                <li><?php echo $_page_lang['service_provider_info.benefit_4']; ?></li>
                <li><?php echo $_page_lang['service_provider_info.benefit_5']; ?></li>
            </ul>
        </div>

        <div class="info-section why-join-section">
            <h3 class="section-title">
                <span>🧭</span>
                <span><?php echo $_page_lang['service_provider_info.who_can_join_title']; ?></span>
            </h3>
            <ul class="list">
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

        <div class="info-section why-join-section">
            <h3 class="section-title">
                <span>🚀</span>
                <span><?php echo $_page_lang['service_provider_info.how_it_works_title']; ?></span>
            </h3>
            <ol class="list">
                <li><strong><?php echo $_page_lang['service_provider_info.step_1']; ?></strong></li>
                <li><strong><?php echo $_page_lang['service_provider_info.step_2']; ?></strong></li>
                <li><strong><?php echo $_page_lang['service_provider_info.step_3']; ?></strong></li>
                <li><strong><?php echo $_page_lang['service_provider_info.step_4']; ?></strong></li>
            </ol>
        </div>

        <!-- Join Call to Action -->
        <div class="info-section cta-section">
            <h3 class="cta-title">
                <span>💬</span>
                <span><?php echo $_page_lang['service_provider_info.cta_title']; ?></span>
            </h3>
            <p class="cta-text">
                <?php echo $_page_lang['service_provider_info.cta_text']; ?>
            </p>
            <a href="<?php echo $_page_base_url.'/account_registration/service_provider'; ?>" class="cta-button">
                <?php echo $_page_lang['service_provider_info.join_button']; ?> →
            </a>
        </div>

        <div class="clearboth"></div>
    </div>
</div>
@endsection
