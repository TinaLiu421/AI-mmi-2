<!DOCTYPE html>
<html lang="<?php echo $_current_lang_code; ?>">
    <head>
        <base href="{{ url('/') }}/">
        <title><?php echo (!empty($_page_meta_data['title']))?$_page_meta_data['title']:''; ?></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="asset/image/logo-mmi.png" rel="icon" type="image/x-icon">
    
        <?php if(!empty($_page_csrf_token)) { ?>
        <meta name="csrf-token" content="<?php echo $_page_csrf_token; ?>">
        <?php } ?>
        <meta name="description" content="<?php echo (!empty($_page_meta_data['description']))?$_page_meta_data['description']:''; ?>">
        
        <meta property="og:title" content="<?php echo (!empty($_page_meta_data['title']))?$_page_meta_data['title']:''; ?>">
        <meta property="og:description" content="<?php echo (!empty($_page_meta_data['description']))?$_page_meta_data['description']:''; ?>">
        <?php if(!empty($_page_meta_data['image'])) { ?>
        <meta property="og:image" content="<?php echo $_page_meta_data['image']; ?>">
        <?php } ?>
        <meta property="og:url" content="<?php echo (!empty($_page_meta_data['url']))?$_page_meta_data['url']:''; ?>">
        <meta property="og:type" content="website">
        
        <?php if(!empty($_mapping_data['multi_url'])) { foreach ($_mapping_data['multi_url'] as $url_key => $url) { ?>
        <link href="<?php echo $url; ?>" rel="alternate" hreflang="<?php echo $url_key; ?>">
        <?php }} ?>
        
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <link href="/asset/lib/icon/css/font-awesome.min.css" rel="stylesheet" type="text/css"/>
        <link href="asset/lib/base/iweb.min.css" rel="stylesheet" type="text/css">
        <?php if(!empty($_page_css_files)) { foreach ($_page_css_files as $css_file) { ?>
        <link href="<?php echo $css_file; ?>?v=<?php echo date('Ymd'); ?>" rel="stylesheet" type="text/css">
        <?php }} ?>

        <script src="asset/lib/base/jquery.min.js" type="text/javascript"></script>
        <script src="asset/lib/base/iweb.min.js" type="text/javascript"></script>
        <script type="text/javascript">
        const _page_global_lang = JSON.parse('<?php echo json_encode($_page_global_lang); ?>');
        const _page_base_url = '<?php echo $_page_base_url; ?>';
        const _token = '<?php echo $_token; ?>';
        const _current_member = <?php echo (!empty($_current_member)) ? json_encode($_current_member) : 'null'; ?>;
        const _current_chat_mode = '<?php echo session('current_chat_mode', ''); ?>';
        </script>
        <?php if(!empty($_page_js_files)) { foreach ($_page_js_files as $js_file) { ?>
        <script src="<?php echo $js_file; ?>?v=<?php echo date('Ymd'); ?>" type="text/javascript"></script>
        <?php }} ?>
        <!-- Welcome message module (must load before common.js) -->
        <link href="asset/css/web/welcome_message.css?v=<?php echo date('Ymd'); ?>" rel="stylesheet" type="text/css">
        <script src="asset/js/web/welcome_message.js?v=<?php echo date('Ymd'); ?>" type="text/javascript"></script>
        <!-- Chat modules -->
        <script src="asset/js/web/immigration-chat.js?v=<?php echo date('Ymd'); ?>" type="text/javascript"></script>
        <script src="asset/js/web/study-chat.js?v=<?php echo date('Ymd'); ?>" type="text/javascript"></script>
        <script src="asset/js/web/common.js?v=<?php echo date('Ymd'); ?>" type="text/javascript"></script>

        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=AW-16657487633"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'AW-16657487633');
        </script>
    </head>
    <body>
        <?php if(!empty($_included_header_footer)) { ?>
        <header class="page-header">
            <div>
                <a class="logo" href="<?php echo ($_page_base_url); ?>">
                    <img src="asset/image/logo.png" alt="logo">
                </a>

                <div class="controls">
                    <div class="lang">
                        <a>
                            <img src="asset/image/icon-lang.png" alt="icon-lang"/>
                            <span><?php echo $_page_lang['lang_'.str_replace('-', '_', $_current_lang_code)]; ?></span>
                        </a>
                        <div class="options header-dropdown">
                            <?php if(!empty($_mapping_data['multi_url']['en'])) { ?>
                            <a href="<?php echo $_mapping_data['multi_url']['en']; ?>">
                                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAALCAMAAABBPP0LAAAAt1BMVEWSmb66z+18msdig8La3u+tYX9IaLc7W7BagbmcUW+kqMr/q6n+//+hsNv/lIr/jIGMnNLJyOP9/fyQttT/wb3/////aWn+YWF5kNT0oqz0i4ueqtIZNJjhvt/8gn//WVr/6+rN1+o9RKZwgcMPJpX/VFT9UEn+RUX8Ozv2Ly+FGzdYZrfU1e/8LS/lQkG/mbVUX60AE231hHtcdMb0mp3qYFTFwNu3w9prcqSURGNDaaIUMX5FNW5wYt7AAAAAjklEQVR4AR3HNUJEMQCGwf+L8RR36ajR+1+CEuvRdd8kK9MNAiRQNgJmVDAt1yM6kSzYVJUsPNssAk5N7ZFKjVNFAY4co6TAOI+kyQm+LFUEBEKKzuWUNB7rSH/rSnvOulOGk+QlXTBqMIrfYX4tSe2nP3iRa/KNK7uTmWJ5a9+erZ3d+18od4ytiZdvZyuKWy8o3UpTVAAAAABJRU5ErkJggg==" alt="English" width="16" height="11" style="width: 16px; height: 11px;">
                                <span>English</span>
                            </a>
                            <?php } ?>
                            
                            <?php if(!empty($_mapping_data['multi_url']['zh-hant'])) { ?>
                            <a href="<?php echo $_mapping_data['multi_url']['zh-hant']; ?>">
                                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAALCAMAAABBPP0LAAAAS1BMVEXqAADjAADZAAD2e3v0dXXzbGzyYWHxWFjyUlLuSkrQAAD5jY76m5zsPz/qNTXnKir56en6w8PmGxvKAAD5z9D6qqvjDw/32dnCAADqlhvuAAAAYElEQVR4AQXBUU6DUBRAwTm8SwqJ+9+m8cva0uBMgBBkkUiqYSWrkpeNxL6/76w5R/L1WZ9jXoONqvs5fWeyrGWux9i3Na5ji3Ftfv/uIZtG+8/78TxA7Skj4qxwcNrBPxiAEpMq30QZAAAAAElFTkSuQmCC" alt="繁體中文" width="16" height="11" style="width: 16px; height: 11px;">
                                <span>繁體中文</span>
                            </a>
                            <?php } ?>

                            <?php if(!empty($_mapping_data['multi_url']['zh-hans'])) { ?>
                            <a href="<?php echo $_mapping_data['multi_url']['zh-hans']; ?>">
                                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAALCAMAAABBPP0LAAAAXVBMVEXUAADlQgDLAADBAADtgXn63Xjypnf1wHHpcG/oZmbmXVzlU1PjS0q1AAD981775VvwnVD2zkvhPz/fNzfdMjHcKyvaJyfsi0baISHYGhqqAADWExPTDQ2jAACfAAApGpDBAAAAWklEQVR4ATXIhQHDQBTDUMll2n/RMiU5/vQsAE4EsPbaKVOU+pXNwc/WKQXeDZMKu+psCXw/Z7efarmENd6GIwGpXhUvM4spxoiEbouRNT7Fmtaq+RG4wAqZZvceD8DeIelqAAAAAElFTkSuQmCC" alt="简体中文" width="16" height="11" style="width: 16px; height: 11px;">
                                <span>简体中文</span>
                            </a>
                            <?php } ?>
                        </div>
                    </div>
                    
                    <?php if(!empty($_current_member)) { ?>
                    <div class="member large">
                        <a href="<?php echo ($_current_member['type'] == 1)?($_page_base_url.'/account/profile'):($_page_base_url.'/account/posts') ;?>">
                            <?php if(!empty($_current_member['avatar'])) { ?>
                            <?php if(file_exists('upload/member_avatar/'.$_current_member['avatar'])) { ?>
                            <div class="avatar" style="background-image:url('<?php echo 'upload/member_avatar/'.$_current_member['avatar']; ?>')"></div>
                            <?php } else { ?>
                            <div class="avatar" style="background-image:url('<?php echo 'upload/member_logo/'.$_current_member['avatar']; ?>')"></div>
                            <?php } ?>
                            <?php } else { ?>
                            <img src="asset/image/icon-member1.png" alt="icon-member"/>
                            <?php } ?>
                        </a>
                    </div>
                    <?php } else { ?>
                    <div class="member">
                        <a href="<?php echo $_page_base_url.'/account_login' ;?>">
                            <img src="asset/image/icon-member1.png" alt="icon-member"/>
                            <span><?php echo $_page_lang['sign_in']; ?></span>
                        </a>
                    </div>
                    <?php } ?>
                    
                    <div class="menu">
                        <a class="open-menu show"><img src="asset/image/icon-menu.png" alt="icon-menu"/></a>
                        <a class="close-menu"><img src="asset/image/icon-close.png" alt="icon-close"/></a>
                        <ul class="header-dropdown">
                            <?php if(!empty($_page_data['visa_countries'])) { ?>
                            <li>
                                <a href="#" class="parent-node"><?php echo $_page_lang['countries']; ?></a>
                                <ol>
                                <?php foreach ($_page_data['visa_countries'] as $vc) { ?>
                                <li>
                                    <a href="<?php echo $vc['url']; ?>">
                                        <img src="<?php echo $vc['photo_flag']; ?>">
                                        <span><?php echo $vc['title']; ?></span>
                                    </a>
                                </li>
                                <?php } ?>
                                </ol>
                            </li>
                            <?php } ?>
                            <li>
                                <a href="<?php echo ($_page_base_url.'/forum'); ?>"><?php echo $_page_lang['forum']; ?></a>
                            </li>
                            <li>
                                <a href="<?php echo ($_page_base_url.'/about_us'); ?>"><?php echo $_page_lang['about_us']; ?></a>
                            </li>
                            <?php if(!empty($_current_member)) { ?>
                            <li>
                                <a href="<?php echo $_page_base_url.'/account_logout' ;?>"><?php echo $_page_lang['sign_out']; ?></a>
                            </li>
                            <?php } else { ?>
                            <li>
                                <a href="<?php echo $_page_base_url.'/account_login' ;?>"><?php echo $_page_lang['sign_in']; ?></a>
                            </li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>
        </header>
        <?php } ?>
 
        <main class="page-body<?php echo (empty($_included_header_footer))?' full':''; ?>">
            <div class="page-content <?php echo implode(' ', array_unique([str_replace('_', '-', $_mapping_data['class']),str_replace('_', '-', $_mapping_data['class'].(($_mapping_data['function']!='index')?('_'.$_mapping_data['function']):''))])); ?>">
                <div class="info-area">
                    @yield('content')
                </div>
                <div class="chat-area">

                    <div>
                        <div class="box">
                            <a class="btn-close-mobile">
                                <img src="asset/image/icon-close.png" alt="icon-close"/>
                            </a>
                            <?php
                            // Only show warning if user has NO active subscriptions AND expiration_ai_level is 2
                            $has_any_subscription = !empty($_current_member['has_migration_subscription']) || !empty($_current_member['has_education_subscription']);
                            if(!$has_any_subscription && !empty($_current_member['expiration_ai_level']) && (int)$_current_member['expiration_ai_level'] == 2) {
                            ?>
                            <div class="limit-warning"><?php echo $_page_lang['chat_robot.limited'];?></div>
                            <?php } ?>
                            <div class="show-message">
                                <!-- Welcome Message Component -->
                                <x-welcome-message />
                            </div>

                            <form id="ask-form" method="post" action="<?php echo $_page_base_url.'/home/chat'; ?>" data-showProcessing="0" data-chat-mode="">
                                <div>@csrf</div>
                                <input type="hidden" id="chat_mode" name="chat_mode" value="">
                                <input type="hidden" id="question_number" name="question_number" value="1">
                                <div class="input-question show">
                                    <div class="robot-container">
                                        <div class="robot">
                                            <video id="chat-robot-video" autoplay loop muted playsinline>
                                                <source src="asset/image/ai-robot-video.mp4" type="video/mp4">
                                            </video>
                                            <a id="sound-control" href="javascript:void(0);">
                                                <i class="fa fa-microphone"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="input-wrapper">
                                        <textarea type="text" id="ask_question" name="question" placeholder="Ask me about migration and study..."></textarea>
                                        <button type="submit">
                                            <img src="asset/image/icon-send.png" alt="icon-send"/>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="clearboth"></div>
                    </div>
                </div>
                <div class="clearboth"></div>
            </div>
        </main>
        
        <?php if(!empty($_included_header_footer)) { ?>
        <footer class="page-footer">
            <div>
                <a href="<?php echo $_page_base_url.'/privacy_statement'; ?>">
                    <?php echo $_page_lang['privacy_statement'];?>
                </a>
            </div>
            <div>Copyright @ <?php echo date('Y');?>. AI-mmi. All Rights Reserved</div>
        </footer>
        <?php } ?>

        <!-- Mobile Chat Button -->
        <button class="mobile-chat-button">Chat with AI-MMI</button>
        <div id="bottom-white-space" style="height:0px;"></div>
        <!-- {{-- Stripe Pricing Table script --}} -->
    <script async src="https://js.stripe.com/v3/pricing-table.js"></script>

    <script>
    // If the page is restored via “backward cache (bfcache)”, force a refresh once.
    window.addEventListener('pageshow', function (e) {
    const nav = performance.getEntriesByType('navigation')[0];
    const isBFCache = e.persisted || (nav && nav.type === 'back_forward');
    if (isBFCache) {
        // Avoid infinite loops: Only refresh during the first recovery.
        if (!window.__reloaded_after_bfcache__) {
        window.__reloaded_after_bfcache__ = true;
        location.reload(); // Hard refresh (fetch new page from server)
        }
    }
    });
    </script>


    </body>
</html>