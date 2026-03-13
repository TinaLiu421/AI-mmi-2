<!DOCTYPE html>
<html lang="<?php echo $_current_lang_code; ?>">
    <head>
        <base href="{{ request()->getSchemeAndHttpHost() }}/">
        <title><?php echo (!empty($_page_meta_data['title']))?$_page_meta_data['title']:''; ?></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
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
        </script>
        <?php if(!empty($_page_js_files)) { foreach ($_page_js_files as $js_file) { ?>
        <script src="<?php echo $js_file; ?>?v=<?php echo date('Ymd'); ?>" type="text/javascript"></script>
        <?php }} ?>
        <!-- Welcome message module (must load before common.js) -->
        <link href="asset/css/web/welcome_message.css?v=<?php echo date('Ymd'); ?>" rel="stylesheet" type="text/css">
        <script src="asset/js/web/welcome_message.js?v=<?php echo date('Ymd'); ?>" type="text/javascript"></script>
        <!-- Conversation flow styles -->
        <link href="asset/css/web/conversation_flow.css?v=<?php echo date('Ymd'); ?>" rel="stylesheet" type="text/css">
        <!-- Document Upload styles -->
        <link href="asset/css/web/document-upload.css?v=<?php echo date('Ymd'); ?>" rel="stylesheet" type="text/css">

        <script src="https://cdn.jsdelivr.net/npm/marked@12/marked.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/dompurify@3/dist/purify.min.js"></script>
        <script src="asset/js/web/common.js?v=<?php echo date('Ymd'); ?>" type="text/javascript"></script>

        <?php
            $googleTranslatePageLang = 'en';
            if ($_current_lang_code == 'zh-hant') {
                $googleTranslatePageLang = 'zh-TW';
            }
            else if ($_current_lang_code == 'zh-hans') {
                $googleTranslatePageLang = 'zh-CN';
            }
        ?>
        <script type="text/javascript">
        window.__pageTranslateSourceLang = '<?php echo $googleTranslatePageLang; ?>';
        window.__autoTranslateDispatched = false;

        window.normalizeAutoTranslateLang = function (targetLang) {
            var sourceLang = window.__pageTranslateSourceLang || 'en';
            if (!targetLang || targetLang === '__reset__' || targetLang === sourceLang) {
                return '';
            }

            return targetLang;
        };

        window.getUrlAutoTranslateLang = function () {
            try {
                var currentUrl = new URL(window.location.href);
                return currentUrl.searchParams.get('autolang') || '';
            } catch (e) {
                return '';
            }
        };

        window.buildAutoTranslateUrl = function (urlValue, targetLang) {
            try {
                var url = new URL(urlValue, window.location.href);
                if (url.origin !== window.location.origin) {
                    return url.toString();
                }

                if (!targetLang || targetLang === '__reset__') {
                    url.searchParams.delete('autolang');
                } else {
                    url.searchParams.set('autolang', targetLang);
                }

                return url.toString();
            } catch (e) {
                return urlValue;
            }
        };

        window.clearAutoTranslateCookie = function () {
            var hostname = window.location.hostname || '';
            var domains = ['', hostname];

            if (hostname) {
                domains.push('.' + hostname);

                var hostParts = hostname.split('.');
                if (hostParts.length > 2) {
                    for (var i = 1; i < hostParts.length - 1; i++) {
                        domains.push('.' + hostParts.slice(i).join('.'));
                    }
                }
            }

            var seen = {};
            for (var j = 0; j < domains.length; j++) {
                var domain = domains[j];
                if (seen[domain]) {
                    continue;
                }

                seen[domain] = true;
                document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
                if (domain) {
                    document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/;domain=' + domain;
                }
            }
        };

        window.setAutoTranslateCookie = function (targetLang) {
            var normalizedTargetLang = window.normalizeAutoTranslateLang(targetLang);
            window.clearAutoTranslateCookie();

            if (!normalizedTargetLang) {
                return;
            }

            var sourceLang = window.__pageTranslateSourceLang || 'en';
            var googtransValue = '/' + sourceLang + '/' + normalizedTargetLang;
            document.cookie = 'googtrans=' + googtransValue + ';path=/';
            document.cookie = 'googtrans=' + googtransValue + ';path=/;domain=' + window.location.hostname;
            document.cookie = 'googtrans=' + googtransValue + ';path=/;domain=.' + window.location.hostname;
        };

        window.getStoredAutoTranslateLang = function () {
            var urlLang = window.getUrlAutoTranslateLang();
            if (urlLang) {
                return window.normalizeAutoTranslateLang(urlLang);
            }

            try {
                var storedLang = window.localStorage.getItem('autoTranslateLang');
                if (storedLang) {
                    return window.normalizeAutoTranslateLang(storedLang);
                }
            } catch (e) {}

            var cookieMatch = document.cookie.match(/(?:^|; )googtrans=([^;]+)/);
            if (!cookieMatch || !cookieMatch[1]) {
                return '';
            }

            var cookieValue = decodeURIComponent(cookieMatch[1]);
            var parts = cookieValue.split('/');
            return parts.length >= 3 ? window.normalizeAutoTranslateLang(parts[2]) : '';
        };

        window.persistAutoTranslateLang = function (targetLang) {
            var normalizedTargetLang = window.normalizeAutoTranslateLang(targetLang);
            try {
                if (!normalizedTargetLang) {
                    window.localStorage.removeItem('autoTranslateLang');
                } else {
                    window.localStorage.setItem('autoTranslateLang', normalizedTargetLang);
                }
            } catch (e) {}
        };

        window.getAutoTranslateTriggerLabel = function (targetLang) {
            var normalizedTargetLang = window.normalizeAutoTranslateLang(targetLang);
            if (!normalizedTargetLang) {
                return '';
            }

            var option = document.querySelector('.auto-translate-option[data-translate-lang="' + normalizedTargetLang.replace(/"/g, '\\"') + '"] span');
            if (!option) {
                return normalizedTargetLang.toUpperCase();
            }

            return (option.textContent || '').trim();
        };

        window.updateLanguageTriggerLabel = function (targetLang) {
            var triggerLabel = document.querySelector('.page-header .lang > a .current-lang-label');
            if (!triggerLabel) {
                return;
            }

            var defaultLabel = triggerLabel.getAttribute('data-default-label') || triggerLabel.textContent || '';
            var autoTranslateLabel = window.getAutoTranslateTriggerLabel(targetLang);

            triggerLabel.textContent = autoTranslateLabel || defaultLabel;
        };

        window.decorateInternalLinksForAutoTranslate = function () {
            var targetLang = window.getStoredAutoTranslateLang();
            var links = document.querySelectorAll('a[href]');

            for (var i = 0; i < links.length; i++) {
                var link = links[i];
                var rawHref = link.getAttribute('href') || '';
                if (!rawHref || rawHref.indexOf('javascript:') === 0 || rawHref.indexOf('#') === 0 || rawHref.indexOf('mailto:') === 0 || rawHref.indexOf('tel:') === 0) {
                    continue;
                }

                link.setAttribute('href', window.buildAutoTranslateUrl(link.href, targetLang));
            }
        };

        window.syncCurrentAutoTranslateUrl = function (targetLang) {
            try {
                var nextUrl = window.buildAutoTranslateUrl(window.location.href, targetLang);
                window.history.replaceState({}, '', nextUrl);
            } catch (e) {}
        };

        window.bootstrapStoredAutoTranslate = function () {
            var targetLang = window.getStoredAutoTranslateLang();
            if (!targetLang) {
                window.updateLanguageTriggerLabel('');
                return '';
            }

            window.setAutoTranslateCookie(targetLang);
            window.updateLanguageTriggerLabel(targetLang);
            return targetLang;
        };

        window.syncAutoTranslateCombo = function () {
            var targetLang = window.getStoredAutoTranslateLang();
            if (!targetLang || targetLang === '__reset__') {
                return;
            }

            var combo = document.querySelector('.goog-te-combo');
            if (!combo || !combo.options || combo.options.length <= 1) {
                return;
            }

            if (combo.value !== targetLang || window.__autoTranslateDispatched === false) {
                combo.value = targetLang;
                combo.dispatchEvent(new Event('change'));
                window.__autoTranslateDispatched = true;
            }
        };

        window.reapplyStoredAutoTranslate = function () {
            var attempts = 0;
            var maxAttempts = 80;
            var timer = window.setInterval(function () {
                attempts++;
                var combo = document.querySelector('.goog-te-combo');
                if (combo && combo.options && combo.options.length > 1) {
                    window.syncAutoTranslateCombo();
                    if (combo.value === window.getStoredAutoTranslateLang()) {
                        window.clearInterval(timer);
                    }
                } else if (attempts >= maxAttempts) {
                    window.clearInterval(timer);
                }
            }, 300);
        };

        window.__pendingAutoTranslateLang = window.bootstrapStoredAutoTranslate();

        window.ensureGoogleTranslateContainer = function () {
            var container = document.getElementById('google_translate_element_hidden');
            if (container) {
                return container;
            }

            if (!document.body) {
                return null;
            }

            container = document.createElement('div');
            container.id = 'google_translate_element_hidden';
            container.style.position = 'absolute';
            container.style.left = '-9999px';
            container.style.top = '-9999px';
            container.style.opacity = '0';
            container.style.pointerEvents = 'none';
            document.body.appendChild(container);

            return container;
        };

        window.googleTranslateElementInit = function () {
            if (typeof google === 'undefined' || !google.translate || !google.translate.TranslateElement) {
                return;
            }

            var container = window.ensureGoogleTranslateContainer();
            if (!container) {
                window.setTimeout(window.googleTranslateElementInit, 200);
                return;
            }

            new google.translate.TranslateElement({
                pageLanguage: '<?php echo $googleTranslatePageLang; ?>',
                autoDisplay: false,
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE
            }, 'google_translate_element_hidden');

            window.reapplyStoredAutoTranslate();
        };

        window.applyAutoTranslate = function (targetLang) {
            if (!targetLang) {
                return;
            }

            var normalizedTargetLang = window.normalizeAutoTranslateLang(targetLang);

            // Always persist and sync cookie/URL state first so any subsequent
            // page load (navigation or reload) immediately picks up the right language.
            window.persistAutoTranslateLang(normalizedTargetLang);
            window.setAutoTranslateCookie(normalizedTargetLang);
            window.syncCurrentAutoTranslateUrl(normalizedTargetLang);
            window.updateLanguageTriggerLabel(normalizedTargetLang);

            // === RESET path ===
            // Must navigate: we need a fresh page load so GT starts without
            // any previously translated DOM or stale widget state.
            if (!normalizedTargetLang) {
                window.location.href = window.buildAutoTranslateUrl(window.location.href, '__reset__');
                return;
            }

            // === TRANSLATE path ===
            // Prefer the already-loaded Google Translate combo when available.
            // This reuses the existing GT widget, avoids an extra network request
            // to translate.google.com, and is the fastest / most reliable path.
            var combo = document.querySelector('.goog-te-combo');
            if (combo && combo.options && combo.options.length > 1) {
                combo.value = normalizedTargetLang;
                combo.dispatchEvent(new Event('change'));
                window.decorateInternalLinksForAutoTranslate();
                return;
            }

            // Combo not ready yet (GT still loading). Navigate so the fresh page
            // load picks up the cookie we just set and GT translates on init.
            var nextUrl = window.buildAutoTranslateUrl(window.location.href, normalizedTargetLang);
            if (nextUrl !== window.location.href) {
                window.location.href = nextUrl;
            } else {
                window.location.reload();
            }
        };

        document.addEventListener('DOMContentLoaded', function () {
            window.updateLanguageTriggerLabel(window.getStoredAutoTranslateLang());
            window.syncCurrentAutoTranslateUrl(window.getStoredAutoTranslateLang());
            window.decorateInternalLinksForAutoTranslate();
            window.reapplyStoredAutoTranslate();

            document.addEventListener('click', function (event) {
                var link = event.target.closest('a[href]');
                if (!link) {
                    return;
                }

                var href = link.getAttribute('href') || '';
                if (!href || href.indexOf('javascript:') === 0 || href.indexOf('#') === 0) {
                    return;
                }

                try {
                    var url = new URL(link.href, window.location.href);
                    if (url.origin === window.location.origin) {
                        var targetLang = window.getStoredAutoTranslateLang();
                        window.setAutoTranslateCookie(targetLang);
                        link.href = window.buildAutoTranslateUrl(link.href, targetLang);
                    }
                } catch (e) {}
            });

            document.addEventListener('submit', function () {
                var targetLang = window.getStoredAutoTranslateLang();
                if (targetLang && targetLang !== '__reset__') {
                    window.setAutoTranslateCookie(targetLang);
                }
            });
        });

        window.addEventListener('load', function () {
            window.reapplyStoredAutoTranslate();
        });
        </script>
        <script type="text/javascript" src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

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
        <?php
            $autoLang = !empty($_page_get_data['autolang']) ? $_page_get_data['autolang'] : session('autolang', '');
            $appendAutoLang = function ($url) use ($autoLang) {
                if(empty($autoLang)) {
                    return $url;
                }

                return $url.((strpos($url, '?') !== false) ? '&' : '?').'autolang='.urlencode($autoLang);
            };
        ?>
        <?php if(!empty($_included_header_footer)) { ?>
        <header class="page-header">
            <div>
                <a class="logo" href="<?php echo $appendAutoLang($_page_base_url); ?>" onclick="window.applyAutoTranslate('en'); return false;">
                    <img src="asset/image/logo.png" alt="logo">
                </a>

                <div class="controls">
                     <div class="upgrade_link">
                        <a href="/upgrade">
                            <i class="fa fa-star"></i>
                            <span>Upgrade</span>
                        </a>
                    </div>
                     <div class="service_provider_info">
                        <a href="<?php echo $appendAutoLang($_page_base_url.'/service_provider_info'); ?>">
                            <img src="asset/image/service_provider.png" alt="icon-service-provider"/>
                            <span><?php echo $_page_lang['service_provider']; ?></span>
                        </a>
                    </div>
                    <?php if(!empty($_mapping_data['support_lang']) && count($_mapping_data['support_lang']) > 1) { ?>
                    <?php
                        $currentLangLabel = strtoupper($_current_lang_code);
                        foreach ($_mapping_data['support_lang'] as $lang) {
                            if (!empty($lang['code']) && $lang['code'] == $_current_lang_code) {
                                $currentLangLabel = !empty($lang['name']) ? $lang['name'] : strtoupper($lang['code']);
                                break;
                            }
                        }
                    ?>
                    <div class="lang notranslate" translate="no">
                        <a href="javascript:void(0);" title="Switch language">
                            <img src="asset/image/icon-lang.png" alt="icon-lang"/>
                            <span class="current-lang-label" data-default-label="<?php echo htmlspecialchars($currentLangLabel, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $currentLangLabel; ?></span>
                        </a>
                        <div class="options header-dropdown notranslate" translate="no">
                            <div class="lang-group-title">Website language</div>
                            <?php foreach ($_mapping_data['support_lang'] as $lang) { ?>
                            <?php if(!empty($lang['code']) && !empty($_mapping_data['multi_url'][$lang['code']]) && !in_array($lang['code'], ['en', 'zh-hant', 'zh-hans'])) { ?>
                            <a href="<?php echo $appendAutoLang($_mapping_data['multi_url'][$lang['code']]); ?>">
                                <span><?php echo !empty($lang['name']) ? $lang['name'] : strtoupper($lang['code']); ?></span>
                            </a>
                            <?php } ?>
                            <?php } ?>
                            <div class="lang-group-title">Auto translate</div>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="__reset__"><span>Original language</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="en"><span>English</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="zh-CN"><span>中文（简体）</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="zh-TW"><span>中文（繁體）</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="ms"><span>Bahasa Melayu</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="id"><span>Bahasa Indonesia</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="ja"><span>日本語</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="ko"><span>한국어</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="th"><span>ไทย</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="vi"><span>Tiếng Việt</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="hi"><span>हिन्दी</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="ar"><span>العربية</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="bn"><span>বাংলা</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="ur"><span>اردو</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="ta"><span>தமிழ்</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="tl"><span>Filipino</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="fa"><span>فارسی</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="fr"><span>Français</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="de"><span>Deutsch</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="it"><span>Italiano</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="es"><span>Español</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="pt"><span>Português</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="tr"><span>Türkçe</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="nl"><span>Nederlands</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="pl"><span>Polski</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="uk"><span>Українська</span></a>
                            <a href="javascript:void(0);" class="auto-translate-option" data-translate-lang="ru"><span>Русский</span></a>
                        </div>
                    </div>
                    <?php } ?>
                    <?php if(!empty($_current_member)) { ?>
                    <div class="member large">
                        <a href="<?php echo $appendAutoLang(($_current_member['type'] == 1)?($_page_base_url.'/account/profile'):($_page_base_url.'/account/posts')); ?>">
                            <?php if(!empty($_current_member['avatar'])) { ?>
                            <?php if(file_exists('upload/member_avatar/'.$_current_member['avatar'])) { ?>
                            <div class="avatar" style="background-image:url('<?php echo 'upload/member_avatar/'.$_current_member['avatar']; ?>')"></div>
                            <?php } else { ?>
                            <div class="avatar" style="background-image:url('<?php echo 'upload/member_logo/'.$_current_member['avatar']; ?>')"></div>
                            <?php } ?>
                            <?php } else { ?>
                            <?php
                                $displayName = (!empty($_current_member['alias_name'])) ? $_current_member['alias_name'] : (!empty($_current_member['full_name']) ? $_current_member['full_name'] : '');
                                $initial = strtoupper(mb_substr(trim($displayName), 0, 1));
                            ?>
                            <?php if(!empty($initial)) { ?>
                            <div class="avatar" style="background-color:#0b2d6f;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;">
                                <?php echo htmlspecialchars($initial, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <?php } else { ?>
                            <img src="asset/image/icon-member1.png" alt="icon-member"/>
                            <?php } ?>
                            <?php } ?>
                        </a>
                    </div>
                    <?php } else { ?>
                    <div class="member">
                        <a href="<?php echo $appendAutoLang($_page_base_url.'/account_login'); ?>">
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
                                <a href="<?php echo $appendAutoLang($_page_base_url.'/forum'); ?>"><?php echo $_page_lang['forum']; ?></a>
                            </li>
                            <li>
                                <a href="<?php echo $appendAutoLang($_page_base_url.'/about_us'); ?>"><?php echo $_page_lang['about_us']; ?></a>
                            </li>
                            <?php if(!empty($_current_member)) { ?>
                            <li>
                                <a href="<?php echo $appendAutoLang($_page_base_url.'/account_logout'); ?>"><?php echo $_page_lang['sign_out']; ?></a>
                            </li>
                            <?php } else { ?>
                            <li>
                                <a href="<?php echo $appendAutoLang($_page_base_url.'/account_login'); ?>"><?php echo $_page_lang['sign_in']; ?></a>
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

                            <!-- Chat Action Buttons -->
                            <div class="chat-action-buttons" style="display: none">
                                <a href="<?php echo $appendAutoLang($_page_base_url.'/study'); ?>" class="chat-action-btn chat-action-btn--study" title="Study">
                                    <span class="chat-action-btn-text">Study</span>
                                </a>
                                <a href="<?php echo $appendAutoLang($_page_base_url.'/migration'); ?>" class="chat-action-btn chat-action-btn--migration" title="Migration">
                                    <span class="chat-action-btn-text">Migration</span>
                                </a>
                            </div>

                            <div class="show-message">
                                <!-- Welcome Message Component -->
                                @include('components.welcome-message')
                            </div>

                            <form id="ask-form" method="post" action="<?php echo $appendAutoLang($_page_base_url.'/home/chat'); ?>" data-showProcessing="0">
                                <div>@csrf</div>
                                <?php if(!empty($autoLang)) { ?>
                                <input type="hidden" name="autolang" value="<?php echo htmlspecialchars($autoLang, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php } ?>
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
                                        <input type="text" id="ask_question" name="question" placeholder="Ask about study, migration or life overseas..."/>
                                        <div class="input-buttons-group">
                                            <button type="button" id="doc-upload-btn" class="btn-document-upload" onclick="document.getElementById('doc-file-input').click();" title="Upload documents">
                                                <img src="asset/image/upload.png" alt="upload">
                                            </button>
                                            <input type="file" id="doc-file-input" accept=".pdf,.doc,.docx,.txt" style="display: none;">
                                            <button type="submit" title="Send">
                                                <img src="asset/image/icon-send.png" alt="icon-send"/>
                                            </button>
                                        </div>
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
                <a href="<?php echo $appendAutoLang($_page_base_url.'/privacy_statement'); ?>">
                    <?php echo $_page_lang['privacy_statement'];?>
                </a>
                 |
                <a href="<?php echo $appendAutoLang($_page_base_url.'/data_deletion'); ?>">
                    <?php echo $_page_lang['data_deletion'];?>
                </a>
            </div>
            <div>Copyright @ <?php echo date('Y');?>. AI-mmi. All Rights Reserved</div>
        </footer>
        <?php } ?>

        <!-- Mobile Chat Button -->
        <button class="mobile-chat-button">Chat with AI-mmi</button>
        <div id="google_translate_element_hidden" style="position:absolute;left:-9999px;top:-9999px;opacity:0;pointer-events:none;"></div>
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

    <!-- Document Upload Handler -->
    <script src="asset/js/web/document-upload.js?v=<?php echo date('Ymd'); ?>" type="text/javascript"></script>
    </body>
</html>