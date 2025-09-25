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
        </script>
        <?php if(!empty($_page_js_files)) { foreach ($_page_js_files as $js_file) { ?>
        <script src="<?php echo $js_file; ?>?v=<?php echo date('Ymd'); ?>" type="text/javascript"></script>
        <?php }} ?>

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
                        <div class="options">
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
                            <img src="asset/image/icon-member.png" alt="icon-member"/>
                            
                            <?php if(file_exists('upload/member_avatar/'.$_current_member['avatar'])) { ?>
                            <div class="avatar" style="background-image:url('<?php echo 'upload/member_avatar/'.$_current_member['avatar']; ?>')"></div>
                            <?php } else { ?>
                            <div class="avatar" style="background-image:url('<?php echo 'upload/member_logo/'.$_current_member['avatar']; ?>')"></div>
                            <?php } ?>
                            
                            <?php } else { ?>
                            <img src="asset/image/icon-member.png" alt="icon-member"/>
                            <?php } ?>
                        </a>
                    </div>
                    <?php } else { ?>
                    <div class="member">
                        <a href="<?php echo $_page_base_url.'/account_login' ;?>">
                            <img src="asset/image/icon-member.png" alt="icon-member"/>
                            <span><?php echo $_page_lang['sign_in']; ?></span>
                        </a>
                    </div>
                    <?php } ?>
                    
                    <div class="menu">
                        <a class="open-menu show"><img src="asset/image/icon-menu.png" alt="icon-menu"/></a>
                        <a class="close-menu"><img src="asset/image/icon-close.png" alt="icon-close"/></a>
                        <a class="hide-chat"><img src="asset/image/icon-arrow-red.png" alt="icon-arrow-red"/></a>
                        <ul>
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
                
                <div class="clearboth"></div>
            </div>
        </header>
        <?php } ?>
 
        <main class="page-body<?php echo (empty($_included_header_footer))?' full':''; ?>">
            <div class="page-content <?php echo implode(' ', array_unique([str_replace('_', '-', $_mapping_data['class']),str_replace('_', '-', $_mapping_data['class'].(($_mapping_data['function']!='index')?('_'.$_mapping_data['function']):''))])); ?>">
                <div class="info-area">
                    @yield('content')
                </div>
                <div class="chat-area"
                    style="background:url('asset/image/chat-bg.jpg') no-repeat center center; background-size:cover;">

                    <div>
                        <div class="top">
                            <div class="slogn">
                                <img src="asset/image/get-help-<?php echo $_current_lang_index;?>.png" alt="get-help"/>
                            </div>
                            <div class="robot">
                                <img src="asset/image/ai-robot.png" alt="ai-robot"/>
                                <video id="ai-robot-video" muted autoplay loop playsinline>
                                    <source type="video/mp4" src="asset/image/ai-robot-video.mp4"></source>
                                </video>
                                <a id="sound-control"><i class="fa fa-microphone-slash"></i></a>
                            </div>
                            <div class="clearboth"></div>
                        </div>
                        
                        <div class="box">
                            <a class="btn-expand-full">
                                <img src="asset/image/icon-expand-full.png" alt="icon-expand-full"/>
                            </a>
                            <a class="btn-expand-full-mobile">
                                <img src="asset/image/icon-expand-full.png" alt="icon-expand-full"/>
                            </a>
                            <?php if(!empty($_current_member['expiration_ai_level']) && (int)$_current_member['expiration_ai_level'] == 2) { ?>
                            <div class="limit-warning"><?php echo $_page_lang['chat_robot.limited'];?></div>
                            <?php } ?>
                            <div class="show-message">
                            </div>
                            <form id="ask-form" method="post" action="<?php echo $_page_base_url.'/home/chat'; ?>" data-showProcessing="0">
                                <div>@csrf</div>
                                <div class="input-question">
                                    <input type="text" id="ask_question" name="question" placeholder="<?php echo $_page_lang['enter_question'];?>">
                                    <button type="submit">
                                        <img src="asset/image/icon-send.png" alt="icon-send"/>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="clearboth"></div>

                        <div class="bottom">
                            <div class="controls">
                                <table>
                                    <!-- <tr>
                                        <td><div class="not-sure"><?php echo $_page_lang['chat_robot.not_sure'];?></div></td>
                                        <td><a class="start-free" href="<?php echo $_page_base_url.'/free_assessment'; ?>"><?php echo $_page_lang['chat_robot.start_free'];?></a></td>
                                    </tr> -->
                                </table>

                                <div>&nbsp;</div>

                                <table>
                                    <tr>
                                        <?php if(empty($_current_member) || !empty($_current_member['expiration_ai_level'])) { ?>
                                        <td>
                                            <a class="with-ai" href="<?php echo $_page_base_url.'/account_submission'; ?>">
                                                <div><?php echo $_page_lang['chat_robot.get_help_with'];?></div>
                                                <div><?php echo $_page_lang['chat_robot.ai_agent'];?><small><?php echo $_page_lang['chat_robot.ai_consultant'];?></small></div>
                                            </a>
                                        </td>
                                        <td>
                                            <a class="with-human" href="<?php echo $_page_base_url.'/agents'; ?>">
                                                <div><?php echo $_page_lang['chat_robot.get_help_with'];?></div>
                                                <div><?php echo $_page_lang['chat_robot.human_agent'];?><small><?php echo $_page_lang['chat_robot.human_consultant'];?></small></div>
                                            </a>
                                        </td>
                                        <?php } else { ?>
                                        <td>
                                            <a class="with-ai" href="<?php echo $_page_base_url.'/upgrade'; ?>">
                                                <div><?php echo $_page_lang['chat_robot.get_help_with'];?></div>
                                                <div style="display:flex; justify-content:center; align-items:center; text-align:center; height:70px;">
                                                    <?php echo $_page_lang['chat_robot.autofill'];?>
                                                <!-- <small><?php echo $_page_lang['chat_robot.ai_consultant'];?></small> -->
                                                </div>
                                            </a>
                                        </td>
                                        <td>
                                            <a class="with-human" href="<?php echo $_page_base_url.'/apply'; ?>" style="width:100%;">
                                                <div><?php echo $_page_lang['chat_robot.get_help_with'];?></div>
                                                <div style="display:flex; justify-content:center; align-items:center; text-align:center; height:70px;">
                                                    <?php echo $_page_lang['chat_robot.human_agent'];?>
                                                <!-- <small><?php echo $_page_lang['chat_robot.human_consultant'];?></small> -->
                                                </div>
                                            </a>
                                        </td>
                                        <?php } ?>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="clearboth"></div>
            </div>
        </main>
        
        <?php if(!empty($_included_header_footer)) { ?>
        <footer class="page-footer">
            <div>
                <a href="<?php echo $_page_base_url.'/terms'; ?>">
                    <?php echo $_page_lang['our_terms'];?>
                </a> 
                | 
                <a href="<?php echo $_page_base_url.'/privacy_statement'; ?>">
                    <?php echo $_page_lang['privacy_statement'];?>
                </a>
            </div>
            <div>Copyright @ <?php echo date('Y');?>. AI-mmi. All Rights Reserved</div>
        </footer>
        <?php } ?>
        
        <a class="floating-show-chat"><img src="asset/image/icon-floating-chat.jpg" alt="icon-floating-chat"/></a>
        
        <div id="bottom-white-space" style="height:0px;"></div>

    <!-- {{-- Stripe Pricing Table script --}} -->
    <script async src="https://js.stripe.com/v3/pricing-table.js"></script>

    </body>
</html>