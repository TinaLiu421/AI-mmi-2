@extends('web.common')
@section('content')
<div class="banner">
    <div class="desktop">
        <?php if(!empty($_page_data['details']['media_files']['banner_'.$_current_lang_index])) { 
            foreach ($_page_data['details']['media_files']['banner_'.$_current_lang_index] as $banner) {
            ?>
        <img src="<?php echo $banner['url']; ?>" alt="<?php echo $banner['file_name']; ?>"/>
        <?php }} ?>
    </div>
    <div class="mobile">
        <?php if(!empty($_page_data['details']['media_files']['mobile_banner_'.$_current_lang_index])) { 
            foreach ($_page_data['details']['media_files']['mobile_banner_'.$_current_lang_index] as $banner) {
            ?>
        <img src="<?php echo $banner['url']; ?>" alt="<?php echo $banner['file_name']; ?>"/>
        <?php }} ?>
    </div>
    <div class="country">
        
    </div>
</div>

<div class="inner-panel">
    <?php if(!empty($_page_data['list_news'])) { ?>
    <div class="news-event">
        <div id="hslider-news" class="hslider">
            <?php foreach ($_page_data['list_news'] as $news_key => $news) { ?>
            <div>
                <a class="link" href="<?php echo $news['url']; ?>">
                    <div class="photo">
                        <img src="<?php echo $news['thumbnail']; ?>" alt=""/>
                        <?php if(empty($news['photo']) && !empty($news['youtube_url'])) { ?>
                        <iframe src="<?php echo $news['youtube_url']; ?>"></iframe>
                        <?php } ?>
                    </div>
                    <div class="title">
                        <?php echo $news['title']; ?>
                    </div>
                </a>
            </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
    
    <?php if(!empty($_page_data['list_events'])) { ?>
    <div class="news-event">
        <div id="hslider-events" class="hslider">
            <?php foreach ($_page_data['list_events'] as $events_key => $events) { ?>
            <div>
                <a class="link" href="<?php echo $events['url']; ?>">
                    <div class="photo">
                        <img src="<?php echo $events['thumbnail']; ?>" alt=""/>
                        <?php if(empty($events['photo']) && !empty($events['youtube_url'])) { ?>
                        <iframe src="<?php echo $events['youtube_url']; ?>"></iframe>
                        <?php } ?>
                    </div>
                    <div class="title">
                        <?php echo $events['title']; ?>
                    </div>
                </a>
            </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
    
    <div class="article-list" data-mid="0"></div>
</div>
@endsection