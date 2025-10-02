<!DOCTYPE html>
<html lang="<?php echo $_current_lang_code; ?>" class="small-font">
    <head>
        <title><?php echo (!empty($_page_meta_data['title']))?$_page_meta_data['title']:''; ?></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="/asset/image/logo-mmi.png" rel="icon" type="image/x-icon">

        <?php if(!empty($_page_csrf_token)) { ?>
        <meta name="csrf-token" content="<?php echo $_page_csrf_token; ?>">
        <?php } ?>
        
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
        
        <link href="/asset/lib/icon/css/font-awesome.min.css" rel="stylesheet" type="text/css"/>
        <link href="/asset/lib/base/iweb.min.css" rel="stylesheet" type="text/css">
        <link href="/asset/lib/picker/datetime.min.css" rel="stylesheet" type="text/css">
        <link href="/asset/lib/picker/minicolors.min.css" rel="stylesheet" type="text/css">
        <link href="/asset/lib/3rd/rcrop.min.css" rel="stylesheet" type="text/css">
        <link href="/asset/lib/3rd/rtable.min.css" rel="stylesheet" type="text/css">
        <?php if(!empty($_page_css_files)) { foreach ($_page_css_files as $css_file) { ?>
        <link href="/<?php echo $css_file; ?>" rel="stylesheet" type="text/css">
        <?php }} ?>
        
        <script src="/asset/lib/base/jquery.min.js" type="text/javascript"></script>
        <script src="/asset/lib/base/iweb.min.js" type="text/javascript"></script>
        <script src="/asset/lib/picker/datetime.min.js" type="text/javascript"></script>
        <script src="/asset/lib/picker/minicolors.min.js" type="text/javascript"></script>
        <script src="/asset/lib/3rd/rcrop.min.js" type="text/javascript"></script>
        <script src="/asset/lib/3rd/rtable.min.js" type="text/javascript"></script>
        <script src="/asset/lib/3rd/jquery.s2t.js" type="text/javascript"></script>
        <?php if(!empty($_page_js_files)) { foreach ($_page_js_files as $js_file) { ?>
        <script src="/<?php echo $js_file; ?>" type="text/javascript"></script>
        <?php }} ?>
        <script type="text/javascript">
        const _page_global_lang = JSON.parse('<?php echo json_encode($_page_global_lang); ?>');
        const _page_base_url = '<?php echo $_page_base_url; ?>';
        const _token = '<?php echo $_token; ?>';
        </script>
    </head>
    <body>
        <?php if(!empty($_included_header_footer)) { ?>
        <header class="page-header">
            <div class="logo">
                <a href="<?php echo url($_mapping_data['module'].'/home');?>"><img src="/asset/image/logo-mmi.png" alt="logo"></a>
            </div>
            <div class="open">
                <a href="#"><i class="fa fa-indent"></i></a>
            </div>
        </header>

        <?php if(!empty($_page_data['left_menu'])) { ?>
        <aside class="left-menu">
            <ul>
            <?php foreach ($_page_data['left_menu'] as $left_menu) { ?>
                <?php if(!empty($left_menu['child'])) { ?> 
                <li>
                    <a href="#" class="parent<?php echo ($_page_index==$left_menu['index'])?' current':''; ?>">
                        <i class="fa fa-<?php echo $left_menu['icon']; ?>"></i>
                        <span><?php echo $left_menu['title']; ?></span>
                    </a>
                    <ol style="display:<?php echo ($_page_index==$left_menu['index'])?'block':'none'; ?>">
                    <?php foreach ($left_menu['child'] as $left_menu_child) { ?>
                       <li>
                            <a href="<?php echo $left_menu_child['url']; ?>" target="<?php echo (!empty($left_menu_child['target']))?$left_menu_child['target']:'_self'; ?>">
                                <i class="fa fa-<?php echo $left_menu_child['icon']; ?>"></i>
                                <span><?php echo $left_menu_child['title']; ?></span>
                            </a>
                        </li> 
                    <?php } ?>
                    </ol>
                </li>
                
                <?php } else { ?>
                <li>
                    <a href="<?php echo $left_menu['url']; ?>" 
                       target="<?php echo (!empty($left_menu['target']))?$left_menu['target']:'_self'; ?>" 
                       class="<?php echo ($_page_index==$left_menu['index'])?' current':''; ?>">
                        <i class="fa fa-<?php echo $left_menu['icon']; ?>"></i>
                        <span><?php echo $left_menu['title']; ?></span>
                    </a>
                </li>
                <?php } ?>
            <?php } ?>
            <ul>
        </aside>
        <?php } ?>
        
        <?php if(!empty($_page_navigator_data)) { ?>
        <nav class="path">
            <div>
                <ul>
                <?php $k = 0; foreach ($_page_navigator_data as $navigator_key => $navigator) { ?>
                    <?php if($k > 0) { ?>
                    <li><i class="fa fa-angle-right"></i></li>
                    <?php } ?>
                    <li><a href="<?php echo (!empty($navigator['url']))?$navigator['url']:''; ?>"><?php echo $navigator['name']; ?></a></li>
                <?php $k++; } ?>
                </ul>
                <?php if(!empty($_page_back_url)) { ?>
                <a class="back" href="<?php echo $_page_back_url; ?>">
                    <i class="fa fa-angle-left"></i>
                    <span><?php echo $_page_lang['back']; ?></span>
                </a>
                <?php } ?>
            </div>
        </nav>
        <?php } ?>
        
        <?php } ?>
        
        <main class="page-body<?php echo (empty($_included_header_footer))?' full':''; ?>">
            <?php if(!empty($_included_header_footer)) { ?>
            <div class="page-message iweb-tips-message">
                <?php if(!empty($_page_error_message)) { ?>
                <div class="error"><a class="close">×</a><span><?php echo $_page_error_message; ?></span></div>
                <?php } else if(!empty($_page_success_message)) { ?>
                <div class="success"><a class="close">×</a><span><?php echo $_page_success_message; ?></span></div>
                <?php } ?>
            </div>
            <?php } ?>
            <div class="page-content <?php echo implode(' ', array_unique([str_replace('_', '-', $_mapping_data['class']),str_replace('_', '-', $_mapping_data['class'].(($_mapping_data['function']!='index')?('_'.$_mapping_data['function']):''))])); ?>">
                @yield('content')
            </div>
        </main>
    </body>
</html>