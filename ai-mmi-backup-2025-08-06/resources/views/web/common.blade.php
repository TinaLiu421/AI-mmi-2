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
        <link href="<?php echo $css_file; ?>?v=<?php echo date('YmdHis'); ?>" rel="stylesheet" type="text/css">
        <?php }} ?>
        @stack('css')
        <style>
        /* ── Navbar Token Balance Badge ── */
        .nav-token-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(245,158,11,0.14);
            border: 1.5px solid rgba(245,158,11,0.55);
            color: #f59e0b;
            border-radius: 100px;
            padding: 5px 12px 5px 10px;
            font-size: 13px; font-weight: 700;
            text-decoration: none;
            transition: background .2s, border-color .2s;
            white-space: nowrap;
            line-height: 1;
        }
        .nav-token-badge:hover {
            background: rgba(245,158,11,0.26);
            border-color: rgba(245,158,11,0.8);
            color: #f59e0b;
            text-decoration: none;
        }
        .nav-token-icon { font-size: 12px; line-height: 1; }
        .nav-token-count { font-size: 13px; font-weight: 800; }
        .nav-token-label { font-size: 12px; font-weight: 600; opacity: 0.85; letter-spacing: 0.2px; }
        @media (max-width: 700px) {
            .nav-token-badge { padding: 4px 9px; }
            .nav-token-label { display: none; }
        }
        </style>

        <script src="asset/lib/base/jquery.min.js" type="text/javascript"></script>
        <script src="asset/lib/base/iweb.min.js" type="text/javascript"></script>
        <script type="text/javascript">
        const _page_global_lang = JSON.parse('<?php echo json_encode($_page_global_lang); ?>');
        const _page_base_url = '<?php echo $_page_base_url; ?>';
        const _token = '<?php echo $_token; ?>';
        const _current_member = <?php echo (!empty($_current_member)) ? json_encode($_current_member) : 'null'; ?>;
        <?php
            /* Detect whether logged-in member has an agent-enabled plan (hybrid / vip). */
            $_page_has_agent_access = false;
            if (!empty($_current_member)) {
                $_mid = (int)($_current_member['id'] ?? 0);
                if ($_mid > 0) {
                    try {
                        $_page_has_agent_access = \DB::table('subscriptions as sub')
                            ->join('plans as pl', 'sub.plan_id', '=', 'pl.id')
                            ->where('sub.member_id', $_mid)
                            ->whereIn('pl.code', ['hybrid', 'vip'])
                            ->where(function ($q) {
                                $q->whereNull('sub.ends_at')
                                  ->orWhere('sub.ends_at', '>', \Carbon\Carbon::now());
                            })
                            ->exists();
                    } catch (\Throwable $e) {
                        $_page_has_agent_access = false;
                    }
                }
            }
            /* Build the URL now without $appendAutoLang (not yet defined).
               autolang query param, if needed, is appended by JS on click instead. */
            $_page_agent_cta_path = '/agent_chat';
        ?>
        const _page_has_agent_access = <?php echo $_page_has_agent_access ? 'true' : 'false'; ?>;
        const _page_agent_cta_url = '<?php echo htmlspecialchars($_page_base_url . $_page_agent_cta_path, ENT_QUOTES, 'UTF-8'); ?>';
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

        window.resetAutoTranslateAndGoTo = function (targetUrl) {
            var destination = targetUrl || window.location.href;
            window.persistAutoTranslateLang('');
            window.setAutoTranslateCookie('');
            window.updateLanguageTriggerLabel('');
            try {
                window.sessionStorage.setItem('forceAutoTranslateReset', '1');
            } catch (e) {}
            window.location.href = window.buildAutoTranslateUrl(destination, '__reset__');
        };

        document.addEventListener('DOMContentLoaded', function () {
            try {
                if (window.sessionStorage.getItem('forceAutoTranslateReset') === '1') {
                    window.sessionStorage.removeItem('forceAutoTranslateReset');
                    window.persistAutoTranslateLang('');
                    window.setAutoTranslateCookie('');
                }
            } catch (e) {}

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
    <body class="page-<?php echo $_mapping_data['class']; ?>">
        <?php
            $autoLang = !empty($_page_get_data['autolang']) ? $_page_get_data['autolang'] : session('autolang', '');
            $_adminToolbarEmails = ['admin@wealthskey.com', 'info@ai-mmi.com'];
            $_currentMemberEmailLower2 = mb_strtolower(trim((string)($_current_member['email'] ?? '')), 'UTF-8');
            $_showAdminToolbar = !empty($_current_member) && in_array($_currentMemberEmailLower2, $_adminToolbarEmails, true);
        ?>
        <?php if($_showAdminToolbar): ?>
        <style>
        #frontend-admin-toolbar{position:fixed;top:0;left:0;right:0;z-index:99999;background:#1a1a2e;color:#fff;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:13px;height:40px;display:flex;align-items:center;padding:0 12px;gap:4px;box-shadow:0 2px 8px rgba(0,0,0,.35);}
        #frontend-admin-toolbar .fat-label{font-weight:700;color:#a78bfa;margin-right:8px;font-size:12px;white-space:nowrap;letter-spacing:.5px;}
        #frontend-admin-toolbar .fat-label i{margin-right:4px;}
        #frontend-admin-toolbar .fat-sep{width:1px;height:22px;background:rgba(255,255,255,.15);margin:0 4px;}
        .fat-item{position:relative;}
        .fat-item > a{display:flex;align-items:center;gap:5px;color:#d1d5db;text-decoration:none;padding:0 9px;height:40px;font-size:12.5px;white-space:nowrap;transition:background .15s,color .15s;border-radius:0;}
        .fat-item > a:hover,.fat-item:hover > a{background:rgba(255,255,255,.1);color:#fff;}
        .fat-item > a i{font-size:12px;opacity:.8;}
        .fat-item > a .fat-caret{font-size:9px;margin-left:2px;opacity:.6;}
        .fat-dropdown{display:none;position:absolute;top:40px;left:0;background:#1e1e3a;border:1px solid rgba(255,255,255,.12);border-radius:6px;min-width:180px;box-shadow:0 8px 24px rgba(0,0,0,.4);padding:4px 0;z-index:100000;}
        .fat-dropdown.right{left:auto;right:0;}
        .fat-item:hover .fat-dropdown{display:block;}
        .fat-dropdown a{display:flex;align-items:center;gap:8px;padding:8px 14px;color:#c4c9d4;text-decoration:none;font-size:12px;transition:background .12s,color .12s;}
        .fat-dropdown a:hover{background:rgba(167,139,250,.15);color:#a78bfa;}
        .fat-dropdown a i{width:14px;text-align:center;opacity:.75;}
        .fat-dropdown .fat-dd-sep{height:1px;background:rgba(255,255,255,.08);margin:4px 0;}
        .fat-item.fat-highlight > a{color:#fbbf24;}
        .fat-item.fat-highlight > a:hover{background:rgba(251,191,36,.1);color:#fcd34d;}
        body.page-header-offset{padding-top:40px;}
        </style>
        <div id="frontend-admin-toolbar">
            <span class="fat-label"><i class="fa fa-shield"></i> Admin</span>
            <div class="fat-sep"></div>

            <div class="fat-item">
                <a href="/admin/home"><i class="fa fa-tachometer"></i> Dashboard</a>
            </div>

            <div class="fat-item fat-highlight">
                <a href="/admin/nextgen_challenge"><i class="fa fa-trophy"></i> NextGen <i class="fa fa-caret-down fat-caret"></i></a>
                <div class="fat-dropdown">
                    <a href="/admin/nextgen_challenge"><i class="fa fa-list"></i> All Submissions</a>
                </div>
            </div>

            <div class="fat-item fat-highlight">
                <a href="/admin/institution_partner"><i class="fa fa-university"></i> Institution Enquiries <i class="fa fa-caret-down fat-caret"></i></a>
                <div class="fat-dropdown">
                    <a href="/admin/institution_partner"><i class="fa fa-list"></i> All Enquiries</a>
                </div>
            </div>

            <div class="fat-item">
                <a href="/admin/members"><i class="fa fa-users"></i> Members <i class="fa fa-caret-down fat-caret"></i></a>
                <div class="fat-dropdown">
                    <a href="/admin/members"><i class="fa fa-user"></i> All Members</a>
                    <a href="/admin/posts"><i class="fa fa-rss"></i> Member Posts</a>
                    <a href="/admin/forum"><i class="fa fa-podcast"></i> Forum</a>
                </div>
            </div>

            <div class="fat-item">
                <a href="/admin/plans/account"><i class="fa fa-leaf"></i> Plans <i class="fa fa-caret-down fat-caret"></i></a>
                <div class="fat-dropdown">
                    <a href="/admin/plans/account"><i class="fa fa-user"></i> Account Plans</a>
                    <a href="/admin/plans/visa_submission"><i class="fa fa-gavel"></i> Visa Submission Plans</a>
                </div>
            </div>

            <div class="fat-item">
                <a href="/admin/pages"><i class="fa fa-file-text-o"></i> Content <i class="fa fa-caret-down fat-caret"></i></a>
                <div class="fat-dropdown">
                    <a href="/admin/pages"><i class="fa fa-book"></i> Pages</a>
                    <a href="/admin/visa"><i class="fa fa-ticket"></i> Visa Options</a>
                    <a href="/admin/faqs"><i class="fa fa-commenting"></i> FAQs</a>
                    <a href="/admin/events"><i class="fa fa-calendar-o"></i> Events</a>
                </div>
            </div>

            <div class="fat-item">
                <a href="/admin/options/countries"><i class="fa fa-filter"></i> Options <i class="fa fa-caret-down fat-caret"></i></a>
                <div class="fat-dropdown">
                    <a href="/admin/options/countries"><i class="fa fa-globe"></i> Countries</a>
                    <a href="/admin/options/organization_type"><i class="fa fa-tag"></i> Organization Types</a>
                    <a href="/admin/options/interest_visas"><i class="fa fa-address-card-o"></i> Interest Visas</a>
                    <a href="/admin/options/interest_topics"><i class="fa fa-coffee"></i> Interest Topics</a>
                </div>
            </div>

            <div class="fat-item">
                <a href="/admin/media_files"><i class="fa fa-cloud-upload"></i> Media</a>
            </div>

            <div class="fat-item">
                <a href="/admin/profile"><i class="fa fa-info-circle"></i> Settings <i class="fa fa-caret-down fat-caret"></i></a>
                <div class="fat-dropdown right">
                    <a href="/admin/profile"><i class="fa fa-user-circle-o"></i> Admin Profile</a>
                    <a href="/admin/setting/general"><i class="fa fa-gears"></i> Site Settings</a>
                    <a href="/admin/setting/email"><i class="fa fa-envelope-o"></i> Email Settings</a>
                    <div class="fat-dd-sep"></div>
                    <a href="/admin/authn/logout"><i class="fa fa-sign-out"></i> Admin Logout</a>
                </div>
            </div>
        </div>
        <script>document.addEventListener('DOMContentLoaded',function(){document.body.classList.add('page-header-offset');});</script>
        <?php endif; ?>
        <?php
            $logoHomeUrl = url('/en');
            $appendAutoLang = function ($url) use ($autoLang) {
                if(empty($autoLang)) {
                    return $url;
                }

                return $url.((strpos($url, '?') !== false) ? '&' : '?').'autolang='.urlencode($autoLang);
            };

            $_adminEmails = ['admin@wealthskey.com', 'info@ai-mmi.com'];
            $_currentEmailLower = mb_strtolower(trim((string)($_current_member['email'] ?? '')), 'UTF-8');
            $_isProxyMode = !empty($_page_data['admin_proxy_mode']);
            $_realAdminEmailLower = mb_strtolower(trim((string)($_page_data['admin_real_member']['email'] ?? '')), 'UTF-8');
            $_isAdminHeader = in_array($_currentEmailLower, $_adminEmails, true)
                || ($_isProxyMode && in_array($_realAdminEmailLower, $_adminEmails, true));
            $_memberTypeHeader = (int)($_current_member['type'] ?? 0);
            $_memberStatusHeader = (int)($_current_member['status'] ?? 0);
            $_listProgramsJobsUrl = $_page_base_url.'/service_provider_info';
            if (!empty($_current_member) && in_array($_memberTypeHeader, [2, 3], true) && $_memberStatusHeader === 1) {
                $_listProgramsJobsUrl = $_page_base_url.'/job_applications';
            }
        ?>
        <?php if(!empty($_included_header_footer)) { ?>
        <header class="page-header">
            <div>
                <a class="logo" href="<?php echo $appendAutoLang($logoHomeUrl); ?>" onclick="window.resetAutoTranslateAndGoTo('<?php echo $logoHomeUrl; ?>'); return false;">
                    <img src="asset/image/logo.png" alt="logo">
                </a>

                <div class="controls">
                    <?php if($_isProxyMode && !empty($_page_data['admin_proxy_target'])) { ?>
                    <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:8px 10px;margin-right:10px;max-width:360px;">
                        <div style="font-size:0.8em;color:#92400e;font-weight:600;">Admin Full-Access Mode</div>
                        <div style="font-size:0.75em;color:#92400e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?php echo htmlspecialchars($_page_data['admin_proxy_target']['alias_name'] ?? $_page_data['admin_proxy_target']['full_name'] ?? ('Member #' . ($_page_data['admin_proxy_target']['id'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>
                    <?php } ?>
                    <!-- Upgrade button moved to token guide page -->
                    <?php if($_isAdminHeader) { ?>
                    <div class="upgrade_link" style="background:#e0f2fe;">
                        <a href="<?php echo $appendAutoLang($_page_base_url.'/Admin_Edu_Agents'); ?>" style="color:#075985;">
                            <i class="fa fa-users"></i>
                            <span>Edu Agent Mgmt</span>
                        </a>
                    </div>
                    <?php if($_isProxyMode) { ?>
                    <div class="upgrade_link" style="background:#fef3c7;">
                        <a href="<?php echo $appendAutoLang($_page_base_url.'/Admin_Edu_Agents/stop_access'); ?>" style="color:#92400e;">
                            <i class="fa fa-sign-out"></i>
                            <span>Exit Full Access</span>
                        </a>
                    </div>
                    <?php } ?>
                    <?php } ?>
                     <div class="service_provider_info">
                        <a href="<?php echo $appendAutoLang($_listProgramsJobsUrl); ?>">
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
                    <?php
                        // Token balance navbar widget
                        $_nav_token_balance = (int) \DB::table('member')->where('id', (int)$_current_member['id'])->value('token_balance');
                    ?>
                    <a href="<?php echo $appendAutoLang($_page_base_url.'/token_guide'); ?>" class="nav-token-badge" title="AI-mmi Tokens — click to manage">
                        <span class="nav-token-icon"><i class="fa fa-bolt"></i></span>
                        <span class="nav-token-count" id="nav-token-count"><?php echo number_format($_nav_token_balance); ?></span>
                        <span class="nav-token-label">Tokens</span>
                    </a>
                    <div class="member large">
                        <a href="<?php echo $appendAutoLang(($_current_member['type'] == 1)?($_page_base_url.'/account/profile'):($_page_base_url.'/account/posts')); ?>">
                            <?php if(!empty($_current_member['avatar'])) { ?>
                            <?php if(file_exists(public_path('upload/member_avatar/'.$_current_member['avatar']))) { ?>
                            <div class="avatar" style="background-image:url('<?php echo 'upload/member_avatar/'.$_current_member['avatar']; ?>')"></div>
                            <?php } elseif(file_exists(public_path('upload/member_logo/'.$_current_member['avatar']))) { ?>
                            <div class="avatar" style="background-image:url('<?php echo 'upload/member_logo/'.$_current_member['avatar']; ?>');background-size:contain;background-repeat:no-repeat;background-position:center;background-color:#fff;padding:3px;"></div>
                            <?php } else { ?>
                            <div class="avatar" style="background-image:url('asset/image/icon-member.png')"></div>
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
                        <ul class="header-dropdown<?php if(empty($_current_member)): ?> hd-no-auth<?php endif; ?>">
                            <!-- STUDY -->
                            <li class="hd-section">
                                <div class="hd-section-label hd-section-label--study">
                                    <i class="fa fa-graduation-cap"></i><span>Study</span>
                                </div>
                                <div class="hd-section-items">
                                    <?php if(empty($_current_member) || (int)($_current_member['type'] ?? 0) === 1): ?>
                                    <a href="<?php echo $appendAutoLang($_page_base_url.'/student_profile'); ?>" class="hd-item">
                                        <i class="fa fa-id-card hd-item-icon"></i><span>My Academic Profile</span>
                                    </a>
                                    <?php endif; ?>
                                    <a href="<?php echo $appendAutoLang($_page_base_url.'/study_plans'); ?>" class="hd-item">
                                        <i class="fa fa-star hd-item-icon"></i><span>Dreams</span>
                                    </a>
                                    <?php if(empty($_current_member) || in_array((int)($_current_member['type'] ?? 0), [1, 2])): ?>
                                    <a href="<?php echo $appendAutoLang($_page_base_url.'/study_college_match'); ?>" class="hd-item hd-item--gated">
                                        <i class="fa fa-graduation-cap hd-item-icon"></i><span>Matches</span>
                                    </a>
                                    <?php endif; ?>
                                    <a href="<?php echo $appendAutoLang($_page_base_url.'/nextgen_challenge'); ?>" class="hd-item">
                                        <i class="fa fa-trophy hd-item-icon"></i><span>NextGen AI &amp; Talent Challenge</span>
                                    </a>
                                    <?php if(empty($_current_member) || in_array((int)($_current_member['type'] ?? 0), [1, 2])): ?>
                                    <a href="<?php echo $appendAutoLang($_page_base_url.'/institution_explore'); ?>" class="hd-item">
                                        <i class="fa fa-building hd-item-icon"></i><span>Explore Colleges</span>
                                    </a>
                                    <?php endif; ?>
                                    <?php if(!empty($_current_member) && (int)($_current_member['type'] ?? 0) === 3): ?>
                                    <a href="<?php echo $appendAutoLang($_page_base_url.'/student_explore'); ?>" class="hd-item">
                                        <i class="fa fa-users hd-item-icon"></i><span>Explore Students</span>
                                    </a>
                                    <a href="<?php echo $appendAutoLang($_page_base_url.'/student_explore/my_interests'); ?>" class="hd-item">
                                        <i class="fa fa-heart hd-item-icon"></i><span>My Interest List</span>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <!-- MIGRATE -->
                            <li class="hd-section">
                                <div class="hd-section-label hd-section-label--migrate">
                                    <i class="fa fa-plane"></i><span>Migrate</span>
                                </div>
                                <div class="hd-section-items">
                                    <a href="<?php echo $appendAutoLang($_page_base_url.'/migration'); ?>" class="hd-item">
                                        <i class="fa fa-compass hd-item-icon"></i><span>Migration Applications</span>
                                    </a>
                                    <a href="<?php echo $appendAutoLang($_listProgramsJobsUrl); ?>" class="hd-item">
                                        <i class="fa fa-briefcase hd-item-icon"></i><span>Find a Migration Agent</span>
                                    </a>
                                </div>
                            </li>
                            <!-- COUNTRIES -->
                            <?php if(!empty($_page_data['visa_countries'])): ?>
                            <li class="hd-section">
                                <div class="hd-section-label hd-section-label--countries">
                                    <i class="fa fa-globe"></i><span>Countries</span>
                                </div>
                                <div class="hd-section-items">
                                    <?php foreach($_page_data['visa_countries'] as $vc): ?>
                                    <a href="<?php echo $vc['url']; ?>" class="hd-item hd-item--flag">
                                        <img src="<?php echo $vc['photo_flag']; ?>" class="hd-flag-img"><span><?php echo $vc['title']; ?></span>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </li>
                            <?php endif; ?>
                            <!-- ABOUT US -->
                            <li class="hd-flat-li">
                                <a href="<?php echo $appendAutoLang($_page_base_url.'/about_us'); ?>" class="hd-item hd-item--about">
                                    <i class="fa fa-info-circle hd-item-icon"></i><span><?php echo $_page_lang['about_us']; ?></span>
                                </a>
                            </li>
                            <!-- TOKENS (always free, no guest gate) -->
                            <li class="hd-flat-li">
                                <a href="<?php echo $appendAutoLang($_page_base_url.'/token_guide'); ?>" class="hd-item hd-item--tokens">
                                    <i class="fa fa-bolt hd-item-icon"></i><span>Tokens</span>
                                </a>
                            </li>
                            <!-- ADMIN -->
                            <?php if($_isAdminHeader): ?>
                            <li class="hd-flat-li">
                                <a href="<?php echo $appendAutoLang($_page_base_url.'/Admin_Edu_Agents'); ?>" class="hd-item hd-item--admin">
                                    <i class="fa fa-cog hd-item-icon"></i><span>Education Agent Management</span>
                                </a>
                            </li>
                            <?php if($_isProxyMode): ?>
                            <li class="hd-flat-li">
                                <a href="<?php echo $appendAutoLang($_page_base_url.'/Admin_Edu_Agents/stop_access'); ?>" class="hd-item hd-item--admin">
                                    <i class="fa fa-times-circle hd-item-icon"></i><span>Exit Full Access Mode</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php endif; ?>
                            <!-- ACCOUNT -->
                            <li class="hd-divider-line"></li>
                            <?php if(!empty($_current_member)): ?>
                            <li class="hd-flat-li">
                                <a href="<?php echo $appendAutoLang(((int)($_current_member['type'] ?? 0) === 1) ? $_page_base_url.'/account/profile' : $_page_base_url.'/account/posts'); ?>" class="hd-item hd-item--profile">
                                    <i class="fa fa-user-circle hd-item-icon"></i><span>My Account</span>
                                </a>
                            </li>
                            <li class="hd-flat-li">
                                <a href="<?php echo $appendAutoLang($_page_base_url.'/account_logout'); ?>" class="hd-item hd-item--signout">
                                    <i class="fa fa-sign-out hd-item-icon"></i><span><?php echo $_page_lang['sign_out']; ?></span>
                                </a>
                            </li>
                            <?php else: ?>
                            <li class="hd-flat-li">
                                <a href="<?php echo $appendAutoLang($_page_base_url.'/account_login'); ?>" class="hd-item hd-item--signin">
                                    <i class="fa fa-sign-in hd-item-icon"></i><span><?php echo $_page_lang['sign_in']; ?></span>
                                </a>
                            </li>
                            <?php endif; ?>
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
                                <a href="<?php echo $appendAutoLang($_page_base_url.'/study_plans'); ?>" class="chat-action-btn chat-action-btn--study" title="Study">
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
                                        <div class="robot" id="chat-robot-inner" style="display:none;">
                                            {{-- D-ID live avatar video (hidden until WebRTC connects) --}}
                                            <video id="did-avatar-video" autoplay playsinline style="display:none;"></video>
                                            {{-- Fallback looping robot video (shown while avatar loads or not configured) --}}
                                            <video id="chat-robot-video" autoplay loop muted playsinline>
                                                <source src="asset/image/ai-robot-video.mp4" type="video/mp4">
                                            </video>
                                            <a id="sound-control" href="javascript:void(0);" title="Avatar sound">
                                                <i class="fa fa-microphone"></i>
                                            </a>
                                        </div>
                                        <?php if(!empty($_current_member) && ((int)($_current_member['type'] ?? 0) !== 3 || strpos(mb_strtolower(trim($_current_member['email'] ?? ''), 'UTF-8'), '@wealthskey.com') !== false)): ?>
                                        <div id="talk-agent-cta" class="visible">
                                            <a id="talk-agent-cta-link" href="<?php echo htmlspecialchars($_page_base_url.'/agent_chat', ENT_QUOTES, 'UTF-8'); ?>">
                                                <span class="tac-icon"><i class="fa fa-user-tie"></i></span>
                                                <span class="tac-label"><span class="tac-line1">Talk to</span><span class="tac-line2">Registered Agent</span></span>
                                            </a>
                                        </div>
                                        <?php endif; ?>
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
    @stack('scripts')
    <?php if(empty($_current_member)): ?>
    <!-- Guest gate modal -->
    <div id="gg-modal" class="gg-overlay" role="dialog" aria-modal="true">
        <div class="gg-box">
            <div class="gg-icon"><i class="fa fa-lock"></i></div>
            <h3 class="gg-title">Sign in to continue</h3>
            <p class="gg-desc">Create a free account or sign in to access this feature.</p>
            <div class="gg-token-bonus"><i class="fa fa-bolt"></i> Sign up &amp; earn <strong>20 tokens</strong> instantly — free!</div>
            <div class="gg-actions">
                <a href="<?php echo $appendAutoLang($_page_base_url.'/account_login'); ?>" class="gg-btn gg-btn--signin">Sign In</a>
                <a href="<?php echo $_page_base_url.'/account_registration'; ?>" class="gg-btn gg-btn--signup">Sign Up Free</a>
            </div>
            <button class="gg-later" id="gg-close">Maybe later</button>
        </div>
    </div>
    <script>
    (function(){
        var modal = document.getElementById('gg-modal');
        if (!modal) return;
        document.addEventListener('click', function(e){
            var link = e.target.closest('ul.hd-no-auth .hd-item');
            if (!link) return;
            // Only gate items explicitly marked as requiring login
            if (!link.classList.contains('hd-item--gated')) return;
            e.preventDefault();
            modal.style.display = 'flex';
        });
        modal.addEventListener('click', function(e){
            if (e.target === modal) modal.style.display = 'none';
        });
        document.getElementById('gg-close').addEventListener('click', function(){
            modal.style.display = 'none';
        });
    })();
    </script>
    <?php endif; ?>
    </body>
</html>