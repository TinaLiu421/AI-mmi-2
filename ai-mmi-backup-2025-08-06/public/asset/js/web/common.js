var article_page = 1;
var article_loading = false;
var article_loading_enable = true;

//var 仅调试推荐（救济），生产不推荐；最好使用const，但用其他方式确保声明只执行一次
//报错重复声明
var csrfToken = (typeof _token !== "undefined"&&_token)?_token: $('meta[name="csrf-token"]').attr('content');
/*function applyCsrfToken(newToken){
    if(!newToken) return;
    csrfToken = newToken;
    window._token = newToken;
    $('meta[name="csrf-token"]').attr('content',newToken);
    $.ajaxSetup({headers:{"X-CSRF-TOKEN": newToken}});
    if (window.iweb && typeof md5 === "function") {
    iweb.csrf_token = md5(md5("iweb@" + (location.hostname || "/")) + "@" + newToken);
}

}

window.csrfRefreshing=window.csrfRefreshing || null;
var csrfRefreshing = window.csrfRefreshing;

function refreshCsrfToken(){
    if(csrfRefreshing) return csrfRefreshing;
    csrfRefreshing = fetch(window.location.href,{method:"GET",credentials:"same-origin"})
        .then(res =>res.text())
        .then(html=>{
            const doc = new DOMParser().parseFromString(html,"text/html");
            const meta = doc.querySelector('meta[name="csrf-token"]');
            const token = meta && meta.getAttribute('content');
            if (token)applyCsrfToken(token);
            return token;
        })
        .catch(() => null)
        .finally(()=>{csrfRefreshing = null;});
    return csrfRefreshing;
}*/

$.ajaxSetup({
   headers: { "X-CSRF-TOKEN": csrfToken},
   /*statusCode:{
    408:handleCsrfExpiry,
    419:handleCsrfExpiry
   }*/
});

/*function handleCsrfExpiry(_, __, options) {
  if (options.__retriedCsrf) { 
    // failed at retry, directly refreshing the page
    location.reload();
    return;
  }
  refreshCsrfToken().then(token => {
    if (!token) { location.reload(); return; }
    // retry original requests, mark as retried, avoid cycle.
    const retryOpts = $.extend(true, {}, options, { __retriedCsrf: true });
    $.ajax(retryOpts);
  });
}

function safeJsonParse(text) {
  try { return JSON.parse(text); }
  catch (e) { return null; }
}

function isCsrfExpiredPayload(xhr) {
  if (!xhr) return false;
  // statusCode 408/419 will already be handled by jquery.statusCode hook
  if (xhr.status === 408 || xhr.status === 419) return false;
  const body = xhr.responseJSON || safeJsonParse(xhr.responseText);
  if (!body) return false;
  const code = body.status || body.code;
  if (code === 408 || code === 419) return true;
  const msg = String(body.message || body.error || "").toLowerCase();
  return msg.includes("csrf") && msg.includes("expire");
}

// Some endpoints return HTTP 200 but carry {status:408,message:"CSRF Token Expired"} in JSON.
// Detect that pattern globally and re-run the request after refreshing the token.
$(document).ajaxComplete(function (_, xhr, settings) {
  if (!isCsrfExpiredPayload(xhr)) return;
  handleCsrfExpiry(_, xhr, settings || {});
});*/


function mdToSafeHtml(md) {
  try {
    marked.setOptions({ gfm: true, breaks: true });
    const dirty = marked.parse(String(md || ""));
    return DOMPurify.sanitize(dirty);
  } catch (e) {
    // 解析失败就退回纯文本（转义）
    return escapeHtml(md || "");
  }
}

function decorateMarkdownWithEmojis(md) {
  const map = {
    'overview': '🧭',
    'eligibility': '✅',
    'english requirement': '🗣️',
    'health & character': '🩺',
    'fees': '💳',
    'processing time': '⏱️',
    'documents': '📄',
    'documents / evidence': '📄',
    'application steps': '🛠️',
    'conditions / notes': '📌',
    'conditions': '📌',
    'notes': '📝',
  };
  md = md.replace(/^###\s+([^\n#]+)$/gmi, (full, title) => {
    const key = title.trim().toLowerCase();
    let ico = map[key];
    if (!ico) {
      for (const k in map) { if (key.includes(k)) { ico = map[k]; break; } }
    }
    if (ico && !title.startsWith(ico)) return `### ${ico} ${title}`;
    return full;
  });

  md = md.replace(/^\s*(Note|Tip|Important)[:：]\s+/gmi, '💡 $1: ');
  md = md.replace(/\b(e\.g\.|for example)\b/gi, '🔎 $1');
  return md;
}


// var __ragBypassOnce = false;

// —— RAG 命中阈值（可调）——
// const RAG_MIN_MATCH = 3;
// const RAG_MIN_SCORE = 0.62;

/*
function callRAG(question, tag = "policy", lang = "en") {
  ...
}
*/

/*
function submitOnce() {
  ...
}
*/

function ensureHiddenFields() {
  // RAG 已停用，不再需要额外隐藏字段
}

function escapeHtml(s) {
    return String(s)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function renderBubble({ role, avatar, name, text, createdAtIso, isHtml }) {
  const timeLocal = formatUtcIsoToLocalTime(createdAtIso || new Date().toISOString());

  // 纯文本模式（旧逻辑）
  const txtBlock = isHtml
    ? `<div class="txt chat-markdown">${text}</div>`  // 已是安全 HTML
    : `<div class="txt">${escapeHtml(text || "")}</div>`;

  return `
    <div class="dialog ${role}">
      <div class="avatar">
        <div style="background-image:url('${avatar || ""}')"></div>
      </div>
      <div class="name">${escapeHtml(name || "")}</div>
      <div class="time">${timeLocal}</div>
      <div class="clearboth"></div>
      ${txtBlock}
    </div>
    <div class="clearboth"></div>
  `;
}

/*
function detectLangClient(text) { ... }
function isTraditionalChinese(text) { ... }
function isMigrationQuery(text) { ... }
*/

function formatUtcIsoToLocalTime(isoString) {
    try {
        const d = new Date(isoString);
        // timeStyle:‘short’ will automatically output times in formats like 09:05 / 9:05 AM based on the user's region.
        return new Intl.DateTimeFormat(undefined, {
            timeStyle: "short",
        }).format(d);
    } catch (e) {
        // Bottom line: Current local time
        return new Intl.DateTimeFormat(undefined, {
            timeStyle: "short",
        }).format(new Date());
    }
}

function scrollChatToBottom() {
    const el = $("main.page-body div.chat-area div.box > div.show-message")[0];
    if (el) el.scrollTop = el.scrollHeight;
}

function scrollSafely() {
    try { scrollChatToBottom(); } catch(e){}
    requestAnimationFrame(() => { try { scrollChatToBottom(); } catch(e){} });
    setTimeout(() => { try { scrollChatToBottom(); } catch(e){} }, 50);
}

// Generate Timestamp HTML
function buildTimeLineHtml(text) {
    return '<div class="time">' + text + "</div>";
}

// Streaming function
// Streaming function
function streamResponse(question, bubbleId) {
    const $bubble = $('#' + bubbleId);
    const $text = $bubble.find('.txt');
    let fullText = '';
    let streamMeta = {};
    let renderMode = 'plain';
    let renderScheduled = false;
    let upgradePopupOpened = false;

    function openUpgradePopup() {
        if (!(streamMeta && streamMeta.show_upgrade)) return false;
        if (upgradePopupOpened) return true;

        const upgradeUrl = streamMeta.upgrade_url || (_page_base_url + '/upgrade');
        const popup = window.open(
            upgradeUrl,
            'aimmi_upgrade_plans',
            'width=980,height=760,menubar=no,toolbar=no,status=no,scrollbars=yes,resizable=yes'
        );
        upgradePopupOpened = true;

        if (!popup) {
            return false;
        }

        return true;
    }

    function renderUpgradeButton() {
        if (!(streamMeta && streamMeta.show_upgrade)) return;

        const upgradeUrl = streamMeta.upgrade_url || (_page_base_url + '/upgrade');
        const label = streamMeta.upgrade_label || '✨ See the Full Plan → Upgrade';
        const isOverLimit = !!(streamMeta.reason && streamMeta.reason === 'free-plan-limit-reached');

        const wrapStyle = [
            'margin-top:14px',
            'padding:12px 14px',
            'background:linear-gradient(135deg,#0f766e 0%,#0e9488 100%)',
            'border-radius:12px',
            'display:flex',
            'align-items:center',
            'justify-content:space-between',
            'gap:10px',
            'box-shadow:0 2px 8px rgba(15,118,110,0.25)',
        ].join(';');

        const subtext = isOverLimit
            ? 'Get unlimited deep-dive answers + certified specialist access'
            : 'Deeper answers, full step-by-step plans & specialist access';

        const btnHtml = '<div class="chat-upgrade-wrap" style="' + wrapStyle + '">'
            + '<div style="flex:1;min-width:0;">'
            + '<div style="color:#fff;font-weight:700;font-size:14px;margin-bottom:2px;">' + label + '</div>'
            + '<div style="color:rgba(255,255,255,0.8);font-size:12px;">' + subtext + '</div>'
            + '</div>'
            + '<button type="button" class="chat-upgrade-btn" '
            + 'style="flex-shrink:0;padding:9px 18px;border:2px solid #fff;border-radius:8px;'
            + 'background:transparent;color:#fff;cursor:pointer;font-weight:700;font-size:13px;'
            + 'white-space:nowrap;transition:background 0.15s;"'
            + ' onmouseover="this.style.background=\'rgba(255,255,255,0.15)\'"'
            + ' onmouseout="this.style.background=\'transparent\'">'
            + 'Upgrade Now</button>'
            + '</div>';

        $text.append(btnHtml);
        $text.find('.chat-upgrade-btn').off('click').on('click', function () {
            const popup = window.open(
                upgradeUrl,
                'aimmi_upgrade_plans',
                'width=980,height=760,menubar=no,toolbar=no,status=no,scrollbars=yes,resizable=yes'
            );

            if (!popup) {
                window.location.href = upgradeUrl;
            }
        });
    }

    function handleActionMeta(meta) {
        if (!(meta && typeof meta === 'object')) return false;
        if (meta.action !== 'redirect') return false;

        const redirectUrl = meta.redirect_url || (_page_base_url + '/agent_chat');
        setTimeout(() => {
            window.location.href = redirectUrl;
        }, 700);

        return true;
    }

    function renderPlain() {
        const safe = escapeHtml(fullText || '').replace(/\n/g, '<br>');
        $text.html(safe);
    }

    function renderMarkdown() {
        const decorated = decorateMarkdownWithEmojis(fullText);
        const safeHtml = mdToSafeHtml(decorated);
        $text.html(safeHtml);
    }

    function scheduleRender() {
        if (renderScheduled) return;
        renderScheduled = true;
        requestAnimationFrame(() => {
            renderScheduled = false;
            if (renderMode === 'markdown') renderMarkdown();
            else renderPlain();
            scrollChatToBottom();
        });
    }
    
    // === ADD THINKING INDICATOR ===
    $text.html('<span class="thinking">Thinking<span class="dot">.</span><span class="dot">.</span><span class="dot">.</span></span>');
    $bubble.addClass('streaming');
    
    // === USE YOUR EXISTING itoken GENERATION ===
    const local_time = iweb.getDateTime(null, "time");
    const itoken = window.btoa(md5(iweb.csrf_token + "#dt" + local_time) + "%" + local_time);
    
    // === SAME FORM DATA AS REGULAR CHAT ===
    const formData = new FormData();
    formData.append('question', question);
    formData.append('_token', csrfToken);
    formData.append('itoken', itoken); // Your custom CSRF
    
    fetch('/chat/stream', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                let payload = null;
                try {
                    payload = JSON.parse(text);
                } catch (e) {
                    payload = null;
                }

                const message = (payload && (payload.message || payload.error))
                    ? (payload.message || payload.error)
                    : 'Request failed. Please try again.';

                throw {
                    isHttpError: true,
                    status: response.status,
                    message: message,
                    redirect: payload && payload.redirect ? payload.redirect : null,
                };
            });
        }

        if (!response.body) {
            throw {
                isHttpError: true,
                status: response.status,
                message: 'No stream body returned from server.',
            };
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        
        function read() {
            reader.read().then(({done, value}) => {
                if (done) {
                    $bubble.removeClass('streaming');
                    if (!fullText.trim()) {
                        let fallback = 'No response received. Please try again.';
                        const maybeJson = (buffer || '').trim();
                        if (maybeJson) {
                            try {
                                const data = JSON.parse(maybeJson);
                                if (data && (data.message || data.error)) {
                                    fallback = data.message || data.error;
                                }
                            } catch (e) {
                                // keep fallback
                            }
                        }
                        $text.html(`<span style="color: orange;">${escapeHtml(fallback)}</span>`);
                    }
                    return;
                }
                
                buffer += decoder.decode(value, { stream: true });
                
                // Process SSE lines
                let lastNewline = buffer.lastIndexOf('\n');
                if (lastNewline !== -1) {
                    const chunk = buffer.slice(0, lastNewline + 1);
                    buffer = buffer.slice(lastNewline + 1);
                    
                    const lines = chunk.split('\n');
                    lines.forEach(line => {
                        line = line.trim();
                        if (!line) return;
                        
                        if (line === 'data: [DONE]') {
                            renderMode = 'markdown';
                            renderMarkdown();
                            renderUpgradeButton();
                            $bubble.removeClass('streaming');
                            return;
                        }
                        
                        if (line.startsWith('data:')) {
                            const dataStr = line.substring(5).trim();
                            try {
                                const data = JSON.parse(dataStr);
                                
                                // Handle error from PHP
                                if (data.error) {
                                    $text.html(`<span style="color: red;">${data.error}</span>`);
                                    return;
                                }

                                if (data.meta && typeof data.meta === 'object') {
                                    streamMeta = data.meta;
                                    if (handleActionMeta(streamMeta)) {
                                        return;
                                    }
                                    openUpgradePopup();
                                    return;
                                }
                                
                                // Handle OpenAI-compatible format
                                if (data.choices && data.choices[0] && data.choices[0].delta && data.choices[0].delta.content) {
                                    // === REMOVE THINKING INDICATOR WHEN FIRST TEXT ARRIVES ===
                                    if ($text.find('.thinking').length) {
                                        $text.empty();
                                    }
                                    
                                    fullText += data.choices[0].delta.content;
                                    scheduleRender();
                                }
                                
                            } catch(e) {
                                // Ignore parse errors
                            }
                        }
                    });
                }
                
                read();
            }).catch(err => {
                console.error('Stream error:', err);
                $bubble.removeClass('streaming');
                $text.html('<span style="color: orange;">Stream interrupted</span>');
            });
        }
        
        read();
    })
    .catch(error => {
        console.error('Fetch error:', error);
        $bubble.removeClass('streaming');

        if (error && error.isHttpError) {
            $text.html(`<span style="color: orange;">${escapeHtml(error.message || 'Request failed.')}</span>`);
            if (error.status === 401 && error.redirect) {
                setTimeout(() => {
                    window.location.href = error.redirect;
                }, 800);
            }
            return;
        }

        $text.html('<span style="color: orange;">Connection failed. Please try again.</span>');
    });
}

function iweb_global_func() {
    // Welcome message is now handled by welcome_message.js
    $(document).on(
        "click",
        "header.page-header div.controls > div.menu > a.open-menu",
        function () {
            // reset all
            $(
                "header.page-header div.controls > div.menu > a.open-menu"
            ).removeClass("show");
            $(
                "header.page-header div.controls > div.menu > a.close-menu"
            ).removeClass("show");
            $(
                "header.page-header div.controls > div.menu > a.hide-chat"
            ).removeClass("show");

            // target
            $(
                "header.page-header div.controls > div.menu > a.close-menu"
            ).addClass("show");
            $("header.page-header div.controls > div.menu > ul").addClass(
                "show"
            );
        }
    );

    $(document).on(
        "click",
        "header.page-header div.controls > div.menu > a.close-menu",
        function () {
            // reset all
            $(
                "header.page-header div.controls > div.menu > a.open-menu"
            ).removeClass("show");
            $(
                "header.page-header div.controls > div.menu > a.close-menu"
            ).removeClass("show");
            $(
                "header.page-header div.controls > div.menu > a.hide-chat"
            ).removeClass("show");

            // target
            $(
                "header.page-header div.controls > div.menu > a.open-menu"
            ).addClass("show");
            $("header.page-header div.controls > div.menu > ul").removeClass(
                "show"
            );
        }
    );

    $(document).on(
        "click",
        "header.page-header div.controls > div.menu > a.hide-chat",
        function () {
            if ($("main.page-body div.chat-area").hasClass("show-mobile")) {
                $("main.page-body div.chat-area").removeClass("show-mobile");
                var new_height =
                    $(window).height() -
                    $("header.page-header").height() -
                    $("div.chat-area div.top").height() -
                    $("div.chat-area div.bottom").height();
                new_height = new_height - 210;
                $(
                    "main.page-body div.chat-area div.box > div.show-message"
                ).height(Math.max(0, parseInt(new_height)));
            } else {
                // reset all
                $(
                    "header.page-header div.controls > div.menu > a.open-menu"
                ).removeClass("show");
                $(
                    "header.page-header div.controls > div.menu > a.close-menu"
                ).removeClass("show");
                $(
                    "header.page-header div.controls > div.menu > a.hide-chat"
                ).removeClass("show");

                $("main.page-body div.info-area").removeClass("hide");
                $("main.page-body div.chat-area").removeClass("hide");
                $("main.page-body div.info-area")
                    .removeClass("show")
                    .removeClass("show-mobile");
                $("main.page-body div.chat-area")
                    .removeClass("show")
                    .removeClass("show-mobile");

                // target
                $(
                    "header.page-header div.controls > div.menu > a.open-menu"
                ).addClass("show");
                $(
                    "header.page-header div.controls > div.menu > ul"
                ).removeClass("show");

                var new_height =
                    $(window).height() -
                    $("header.page-header").height() -
                    $("div.chat-area div.top").height() -
                    $("div.chat-area div.bottom").height();
                new_height = new_height - 210;
                $(
                    "main.page-body div.chat-area div.box > div.show-message"
                ).height(Math.max(0, parseInt(new_height)));
            }
        }
    );

    $(document).on(
        "click",
        "header.page-header div.controls > div.lang > a",
        function (e) {
            e.preventDefault();
            e.stopPropagation();

            const $options = $(this)
                .closest("div.lang")
                .find("div.options");

            $("header.page-header div.controls > div.lang > div.options")
                .not($options)
                .removeClass("show");

            $options.toggleClass("show");
        }
    );

    $(document).on("click", "a.auto-translate-option", function (e) {
        e.preventDefault();
        e.stopPropagation();

        const targetLang = $(this).data("translateLang");
        if (typeof window.applyAutoTranslate === "function") {
            window.applyAutoTranslate(targetLang);
        }
    });

    $(document).on("click", function (e) {
        if ($(e.target).closest("header.page-header div.controls > div.lang").length === 0) {
            $("header.page-header div.controls > div.lang > div.options").removeClass("show");
        }
    });

    $(document).on(
        "click",
        "main.page-body div.chat-area div.box > a.btn-close-mobile",
        function () {
            $("main.page-body div.chat-area").removeClass("show-mobile");
            $(".mobile-chat-button").removeClass("hidden");

            // Stop the welcome video if it's playing
            const welcomeVideo = document.getElementById("welcome-robot-video");
            if (welcomeVideo) {
                welcomeVideo.pause();
                welcomeVideo.currentTime = 0;
                welcomeVideo.muted = true;
            }

            // Reset the welcome sound control button
            const soundControl = $("#welcome-sound-control");
            soundControl.removeClass("opened");
            soundControl.attr("title", "Click to unmute");
            $("#welcome-sound-control > i")
                .removeClass("fa-microphone")
                .addClass("fa-microphone-slash");
        }
    );

    $(document).on("click", ".mobile-chat-button", function () {
        toggleMobileChat();
    });

    function openPostPublishDialog(postId) {
        var targetId = parseInt(postId, 10);
        if (isNaN(targetId) || targetId < 0) {
            targetId = 0;
        }

        var requestUrl = _page_base_url + "/account/posts_publish";
        if (targetId > 0) {
            requestUrl += "/" + targetId;
        }

        $.get(requestUrl, function (html) {
                iweb.dialog(
                    html,
                    function () {
                        iweb.form(
                            "#account-publish-form",
                            "json",
                            null,
                            function (response_data) {
                                if (iweb.isMatch(response_data.status, 200)) {
                                    window.location.reload();
                                } else {
                                    iweb.alert(response_data.message);
                                }
                            }
                        );

                        $(document).off(
                            "click",
                            "#account-publish-form a.remove-my-post"
                        );
                        $(document).on(
                            "click",
                            "#account-publish-form a.remove-my-post",
                            function () {
                                var post_id = $(this).data("id");
                                iweb.confirm(
                                    _page_global_lang["confirm_delete_one"],
                                    function (result) {
                                        if (result) {
                                            $.getJSON(
                                                _page_base_url +
                                                    "/account/delete_post/" +
                                                    post_id,
                                                function () {
                                                    window.location.reload();
                                                }
                                            );
                                        }
                                    }
                                );
                            }
                        );

                        $(document).off(
                            "click",
                            "#account-publish-form div.fullscreen > a"
                        );
                        $(document).on(
                            "click",
                            "#account-publish-form div.fullscreen > a",
                            function () {
                                if (
                                    $("div.iweb-info-dialog.publish").hasClass(
                                        "fullscr"
                                    )
                                ) {
                                    $(
                                        "div.iweb-info-dialog.publish"
                                    ).removeClass("fullscr");
                                } else {
                                    $("div.iweb-info-dialog.publish").addClass(
                                        "fullscr"
                                    );
                                }
                                setPostsFullScr();
                            }
                        );

                        $(document).off(
                            "focus",
                            "#account-publish-form textarea"
                        );
                        $(document).on(
                            "focus",
                            "#account-publish-form textarea",
                            function () {
                                $("div.upload-photo").slideUp();
                                $("div.upload-video").slideUp();
                            }
                        );

                        $(document).off("click", "#show-publish-photo");
                        $(document).on(
                            "click",
                            "#show-publish-photo",
                            function () {
                                $("div.upload-video").hide();
                                if (!$("div.upload-photo").is(":visible")) {
                                    $("div.upload-photo").slideDown();
                                } else {
                                    $("div.upload-photo").slideUp();
                                }
                            }
                        );

                        $(document).off("click", "#show-publish-video");
                        $(document).on(
                            "click",
                            "#show-publish-video",
                            function () {
                                $("div.upload-photo").hide();
                                if (!$("div.upload-video").is(":visible")) {
                                    $("div.upload-video").slideDown();
                                } else {
                                    $("div.upload-video").slideUp();
                                }
                            }
                        );

                        $(document).off("change", "#mypostsphoto");
                        $(document).on("change", "#mypostsphoto", function () {
                            const regex = /^(.*)(.jpg|.jpeg|.gif|.png|.bmp)$/;
                            var file =
                                document.getElementById("mypostsphoto").files;
                            if (iweb.isValue(file)) {
                                file = file[0];
                                if (
                                    regex.test(file.name.toLowerCase()) &&
                                    typeof FileReader !== "undefined"
                                ) {
                                    if (file.size <= 2 * 1024 * 1024) {
                                        var reader = new FileReader();
                                        reader.onload = function (e) {
                                            $(
                                                "div.postsphoto-file > div.preview"
                                            ).html(
                                                '<img src="' +
                                                    e.target.result +
                                                    '">'
                                            );
                                        };
                                        reader.readAsDataURL(file);
                                    } else {
                                        iweb.alert(
                                            iweb.language[
                                                iweb.current_language
                                            ]["max_error"].replace("{num}", 2)
                                        );
                                    }
                                } else {
                                    iweb.alert(
                                        iweb.language[iweb.current_language][
                                            "type_error"
                                        ]
                                    );
                                    $("#mylogo").val("");
                                    $("div.postsphoto-file > div.preview").html(
                                        ""
                                    );
                                }
                            } else {
                                $("#mylogo").val("");
                                $("div.postsphoto-file > div.preview").html("");
                            }
                        });
                    },
                    null,
                    "publish"
                );
            }).fail(function (xhr) {
                var message = "Unable to open the post form right now.";
                if (xhr && xhr.responseText) {
                    message = xhr.responseText;
                }
                iweb.alert(message);
            });
    }

    $(document).on("click", "#edit-publish-posts", function (e) {
        e.preventDefault();
        e.stopPropagation();
        openPostPublishDialog($(this).data("id"));
    });

    $(document).on("click", "#publish-photo, #publish-video", function (e) {
        e.preventDefault();
        e.stopPropagation();
        openPostPublishDialog(0);
    });

    $(document).on("click", "a.do-like", function () {
        var object = $(this);
        var posts_id = parseInt(object.data("id"));
        iweb.post(
            {
                url: _page_base_url + "/account_article/ticklike",
                values: {
                    posts_id: posts_id,
                    _token: _token,
                },
                showProcessing: false,
            },
            function (response_data) {
                if (iweb.isMatch(response_data.status, 200)) {
                    object
                        .closest("div.post")
                        .find("div.total > div.like > span")
                        .html(response_data.total);
                } else if (iweb.isValue(response_data.url)) {
                    window.location.href = response_data.url;
                }
            }
        );
    });

    $(document).on("click", "a.do-toapply", function () {
        var object = $(this);
        var sector = object.data("sector");
        var actionUrl = object.data("action-url");

        if (sector === "migration") {
            // Auto-send a migration greeting to the embedded AI chatbot
            var presetMsg = "Hi, I would like to ask about migration. Could you help?";
            var $input = $("#ask_question");
            var $form  = $("#ask-form");

            if ($input.length && $form.length) {
                // Scroll the chat input into view
                $("html, body").animate({
                    scrollTop: $input.offset().top - 120
                }, 400, function () {
                    $input.val(presetMsg).focus();
                    // Give the UI a moment to render, then submit
                    setTimeout(function () {
                        $form.submit();
                    }, 150);
                });
            } else {
                // Fallback: navigate to the chat page
                window.location.href = iweb.isValue(actionUrl)
                    ? actionUrl
                    : (_page_base_url + "/agent_chat");
            }
            return;
        }

        window.location.href = iweb.isValue(actionUrl)
            ? actionUrl
            : (_page_base_url + "/apply");
    });

    $(document).on("click", "a.do-qanda", function () {
        var object = $(this);
        var posts_id = parseInt(object.data("id"));
        
        //Is the comment component is open or close.
        if (object.closest('div.post').find("div.leavecomment").is(":visible")) 
        {
            object.closest('div.post').find("div.leavecomment").slideUp();
        } 
        else {
            //Open the comment component
            $.get(
                _page_base_url + "/account_article/comment/" + posts_id,
                function (html) {
                    object.closest('div.post').find("div.reply").html(html);
                    object
                        .closest('div.post')
                        .find("div.leavecomment")
                        .slideDown();
                }
            );
        }
    });

    /*$(document).on("click", "a.do-comment", function () {
        var object = $(this);
        var posts_id = parseInt(object.data("id"));
        if (
            object.closest("div.post").find("div.leavecomment").is(":visible")
        ) {
            object.closest("div.post").find("div.leavecomment").slideUp();
        } else {
            $.get(
                _page_base_url + "/account_article/comment/" + posts_id,
                function (html) {
                    object.closest("div.post").find("div.reply").html(html);
                    object
                        .closest("div.post")
                        .find("div.leavecomment")
                        .slideDown();
                }
            );
        }
    });*/

    $(document).on("click", "a.do-share", function () {
        var shareto = $(this).closest("div.actions").find("div.shareto");
        if (shareto.hasClass("show")) {
            shareto.removeClass("show");
        } else {
            shareto.addClass("show");
        }
    });

    $(document).on("click", "button.btn-send-comment", function () {
        var object = $(this);
        var posts_id = parseInt(object.data("id"));
        var message = object
            .closest("div.leavecomment")
            .find('textarea[name="message"]')
            .val();
        if (iweb.isValue(message)) {
            //1. submit comment to database
            iweb.post(
                {
                    url: _page_base_url + "/account_article/comment",
                    values: {
                        posts_id: posts_id,
                        content: message,
                        _token: csrfToken,
                    },
                    showProcessing: false,
                },
                function (response_data) {
                    //2. Refresh the textbox
                    if (iweb.isMatch(response_data.status, 200)) {
                        object
                            .closest("div.leavecomment")
                            .find('textarea[name="message"]')
                            .val("");
                        object
                            .closest("div.post")
                            .find("div.total > div.comment > span")
                            .html(response_data.total);
                        //3. refresh the comment list
                        $.get(
                            _page_base_url +
                                "/account_article/comment/" +
                                posts_id,
                            function (html) {
                                const $reply = object.closest("div.post").find("div.reply");
                                $reply.html(html);
                                applyAiReplyClass($reply);
                                //4. active AI responding
                                simulateAIReply(message,$reply,posts_id);
                            }
                        ); 
                    }else if (iweb.isValue(response_data.url)) {
                        window.location.href = response_data.url;
                    }
                }
            );
        }
    });

    function simulateAIReply(userMessage,$replyContainer,posts_id){
        if(!iweb.isValue(userMessage)) return false;
        var thinkingIndicator =
                '<div class="dialog reply thinking-indicator">' +
                '<div class="avatar"><div style="background-image:url(\'/asset/image/logo-mmi.png\')"></div></div>' +
                '<div class="txt">Thinking<span class="dot"></span><span class="dot"></span><span class="dot"></span></div>' +
                '</div><div class="clearboth"></div>';
        var $anchor = $replyContainer.find(".replier").first();
        if($anchor.length){
            $anchor.after(thinkingIndicator);
        }else{
            $replyContainer.append(thinkingIndicator);
        }
        var local_time = iweb.getDateTime(null,"time");
        var itoken = window.btoa(md5(iweb.csrf_token + "#dt" + local_time) + "%" + local_time);
        $.post(
            _page_base_url+"/home/chat",
            {question:userMessage, _token:_token, itoken: itoken, from_qa: true},
            function(response_data){
                if(response_data.status===200){
                    console.log(response_data);
                    $replyContainer.find(".thinking-indicator").remove();
                    var mdRaw = response_data.answer_markdown || response_data.reply || "";
                    var md = (mdRaw === null || mdRaw === undefined) ? "" : String(mdRaw).trim();

                    // If AI returned no content, skip saving/rendering any reply
                    if (!md) {
                        return;
                    }

                    var safeHtml = response_data.answer_html
                        ? DOMPurify.sanitize(String(response_data.answer_html))
                        : mdToSafeHtml(md);

                    iweb.post(
                        {
                            url: _page_base_url + "/account_article/comment",
                            values: {
                                posts_id: posts_id,
                                content: md,
                                _token: csrfToken,
                                status: 2,
                            },
                            showProcessing: false,
                        },
                        function(saveRes){
                            const replierHtml = `
                            <div class="replier ai-reply" data-comment-id="${(saveRes && saveRes.id) ? saveRes.id : 'ai-temp'}" data-is-ai="1" data-status="2">
                                <div class="avatar">
                                    <a href="#">
                                        <img src="asset/image/icon-member.png" alt="icon-member"/>
                                        <div style="background-image:url('asset/image/logo-mmi.png')"></div>
                                    </a>
                                </div>
                                <div class="name">
                                    <div><a href="#">AI-mmi</a></div>
                                    <div class="hours">${new Date().toLocaleTimeString()} &#x2022; <img src="asset/image/icon-earth.png" alt="icon-earth" width="16"/></div>
                                    <div class="message">${safeHtml}</div>
                                    <div class="comment-action">
                                        <!-- <a href="javascript:void(0);" class="do-reply" data-id="ai-temp">Reply</a> -->
                                        <!-- <a href="javascript:void(0);" class="do-delete" data-id="ai-temp">Delete</a> -->
                                    </div>
                                </div>
                            </div>
                            `;
                            if($anchor.length){
                                $anchor.after(replierHtml);
                            }else{
                                $replyContainer.append(replierHtml);
                            }
                            applyAiReplyClass($replyContainer);
                        }
                    );
                } else {
                    $replyContainer.find(".thinking-indicator").remove();
                    return;
                }
            },
            "json"
        );
    };

    function applyAiReplyClass($container){
        if(!$container || !$container.length) return;
        $container.find(".replier").each(function(){
            var $r = $(this);
            var isAi = $r.data("is-ai") === 1 || $r.data("status") === 2 || ($r.find(".badge").length && $r.find(".badge").text().toLowerCase().indexOf("assistant")>-1);
            if(isAi){
                $r.addClass("ai-reply");
            }
        });
    }

    /*$(document).on("click", "a.do-reply", function() {
        var object = $(this);
        var commentId = object.data("id");
        //var username = object.data("user");

        // find the closet comment textbox
        var leaveCommentDiv = object.closest('div.post').find('div.leavecomment');
        var textarea = leaveCommentDiv.find('textarea[name="message"]');
        var sendButton = leaveCommentDiv.find('button.btn-send-comment');

        // fill text with '@username:'
        //textarea.val("@" + username + " ").focus();
        textarea.val("reply test").focus();
        //update sendButton data-id to comment id
        sendButton.data('data-id', commentId);
        sendButton.data('status',2);

    });*/

    //delete comment
    $(document).on("click", "a.do-delete", function () {
        var object = $(this);
        var posts_id = parseInt(object.data("id"));

        //Is the comment component is open or close.
        if (object.closest("div.post").find("div.leavecomment").is(":visible")) 
        {
            object.closest("div.post").find("div.leavecomment").slideUp();
        } 
        else {
            //Open the comment component
            $.get(
                _page_base_url + "/account_article/comment/" + posts_id,
                function (html) {
                    object.closest("div.post").find("div.reply").html(html);
                    object
                        .closest("div.post")
                        .find("div.leavecomment")
                        .slideDown();
                }
            );
        }
    });

    if ($("div.mypage").length > 0) {
        iweb.pagination("div.mypage");
    }

    loadArticle();

    // Handle Enter key to submit form (Shift+Enter for new line)
    $(document).on("keydown", "#ask_question", function (e) {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            $("#ask-form").submit();
        }
    });
    
    // AI responding in chatbox
    iweb.form(
        "#ask-form",
        "json",
        // ---------- beforeSubmit ----------
        function () {
            ensureHiddenFields();

            // if (!_current_member) {
            // iweb.alert("Sign in to get full chat features.", function () {
            //         window.location.href = "/account_login";
            //     });
            //     return false;
            // }

            const $ta = $("#ask_question");
            const userQuestion = $ta.val().trim();
            if (!iweb.isValue(userQuestion)) return false;

            // Hide welcome message when user starts chatting
            if (typeof removeWelcomeAndShowChat === 'function') {
                removeWelcomeAndShowChat();
            }

            // ---- 保留 textarea 的 name 即可，无需隐藏字段 ----

            // Show user bubble
            const userHtml = renderBubble({ 
                role: "ask",
                avatar: _current_member && _current_member.avatar
                    ? (_current_member.type == 1 ? "upload/member_avatar/" : "upload/member_logo/") + _current_member.avatar
                    : "asset/image/icon-member.png",
                name: (_current_member && _current_member.name) ? _current_member.name : "You",
                text: userQuestion,
                createdAtIso: new Date().toISOString(),
            });
            $("main.page-body div.chat-area div.box > div.show-message").append(userHtml);

            // Create AI bubble
            const bubbleId = 'ai-' + Date.now();
            const aiHtml = `
                <div class="dialog reply" id="${bubbleId}">
                    <div class="avatar"><div style="background-image:url('/asset/image/logo-mmi.png')"></div></div>
                    <div class="name">AI-mmi</div>
                    <div class="time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                    <div class="clearboth"></div>
                    <div class="txt chat-markdown"></div>
                </div>
                <div class="clearboth"></div>
            `;
            $("main.page-body div.chat-area div.box > div.show-message").append(aiHtml);
            
            // Scroll to bottom when new bubbles are added
            scrollChatToBottom();

            // Start streaming
            streamResponse(userQuestion, bubbleId);
            
            // Clear input
            $ta.val("").attr("placeholder", "AI is responding...");
            
            return false; // Prevent normal form submission
        },
        // successFn (fallback)
        function (response_data) {
            $("#ask_question").attr("placeholder", "Ask about study, visa...");
        }
    );
}

function iweb_global_func_done() {
    // load full article content
    $(document).on("click", "a.load-fullcontent", function () {
        var object = $(this);
        $.get(
            _page_base_url +
                "/account_article/fullcontent/" +
                object.data("id"),
            function (html) {
                if (iweb.isValue(html)) {
                    object.closest("div.article-short-content").html(html);
                }
            }
        );
    });

    // video sound
    $(document).on("click", "a#sound-control", function () {
        if ($(this).hasClass("opened")) {
            $(this).removeClass("opened");
            $("a#sound-control > i")
                .removeClass("fa-microphone-slash")
                .removeClass("fa-microphone")
                .addClass("fa-microphone-slash");
            $("#chat-robot-video").prop("muted", true);
        } else {
            $(this).addClass("opened");
            $("a#sound-control > i")
                .removeClass("fa-microphone-slash")
                .removeClass("fa-microphone")
                .addClass("fa-microphone");
            $("#chat-robot-video").prop("muted", false);
        }
    });

    $("main.page-body div.chat-area div.box > div.show-message").scroll(
        function () {
            var pos = $(
                "main.page-body div.chat-area div.box > div.show-message"
            ).scrollTop();
            if (pos == 0) {
                loadChatMessage();
            }
        }
    );
    $("main.page-body div.chat-area div.box > div.show-message").click(
        function () {
            var pos = $(
                "main.page-body div.chat-area div.box > div.show-message"
            ).scrollTop();
            if (pos == 0) {
                loadChatMessage();
            }
        }
    );
}

function iweb_global_layout() {
    resetPageView();

    setPostsFullScr();
}

function iweb_global_layout_done() {
    resetPageView();
}

function iweb_global_scroll() {
    if (
        $(window).scrollTop() + $(window).height() >
        $(document).height() - 200
    ) {
        loadArticle();
    }
}

function resetPageView() {
    // reset all
    $("header.page-header div.controls > div.lang > div.options").removeClass(
        "show"
    );

    $("header.page-header div.controls > div.menu > a.open-menu").removeClass(
        "show"
    );
    $("header.page-header div.controls > div.menu > a.close-menu").removeClass(
        "show"
    );
    $("header.page-header div.controls > div.menu > a.hide-chat").removeClass(
        "show"
    );

    // target
    $("header.page-header div.controls > div.menu > a.open-menu").addClass(
        "show"
    );
    $("header.page-header div.controls > div.menu > ul").removeClass("show");

    $("main.page-body div.info-area").removeClass("hide");
    $("main.page-body div.chat-area").removeClass("hide");
    $("main.page-body div.info-area").removeClass("show");
    $("main.page-body div.chat-area").removeClass("show");

    // resize chat message height
    if ($("a.floating-show-chat").is(":visible")) {
        var new_height =
            $(window).height() -
            $("header.page-header").height() -
            $("div.chat-area div.top").height() -
            $("div.chat-area div.bottom").height();
        new_height = new_height - 210;
        $("main.page-body div.chat-area div.box > div.show-message").height(
            Math.max(0, parseInt(new_height))
        );
        $("#bottom-white-space").css(
            "height",
            $("a.floating-show-chat").outerHeight()
        );
    } else {
        var new_height =
            $(window).height() -
            $("header.page-header").height() -
            $("footer.page-footer").height() -
            $("div.chat-area div.top").height() -
            $("div.chat-area div.bottom").height();
        new_height = new_height - 210;
        $("main.page-body div.chat-area div.box > div.show-message").height(
            Math.max(0, parseInt(new_height))
        );
        $("#bottom-white-space").css("height", "auto");
    }

    $("a#sound-control").removeClass("opened");
    $("a#sound-control > i")
        .removeClass("fa-microphone-slash")
        .removeClass("fa-microphone")
        .addClass("fa-microphone-slash");
    $("#chat-robot-video").prop("muted", true);
}

function loadArticle() {
    if ($("div.article-list").length > 0) {
        if (!article_loading && article_loading_enable) {
            article_loading = true;
            $.get(
                _page_base_url +
                    "/account_article?mid=" +
                    parseInt($("div.article-list").data("mid")) +
                    "&page=" +
                    article_page,
                function (html) {
                    if (iweb.isValue(html)) {
                        $("div.article-list")
                            .append(html)
                            .each(function () {
                                iweb.responsive();
                                article_page = article_page + 1;
                                setTimeout(function () {
                                    article_loading = false;
                                }, 500);
                            });
                    } else {
                        article_loading_enable = false;
                    }
                }
            );
        }
    }
}

function loadChatMessage(init) {
    var url = _page_base_url + "/home/chat" + (iweb.isValue(init) ? "/1" : "");

    $.getJSON(url, function (data) {
        if (iweb.isValue(data)) {
            var dialog_group = "";
            var dialog_date_int = 0;
            $.each(data, function (key, value) {
                const role =
                    String(value.type || "").toLowerCase() === "ask" ||
                    String(value.type || "").toLowerCase() === "member"
                        ? "ask"
                        : "reply";

                const isAi = (role === "reply");
                // 历史数据也可能是 markdown/纯文本；AI 消息统一走 markdown 渲染链，避免显示 ** 或 []() 原始符号
                let textForBubble;
                const contentRaw = value.content_raw || value.content || "";
                if (isAi) {
                    const md = String(contentRaw || "");
                    const mdDecorated = decorateMarkdownWithEmojis(md);
                    textForBubble = mdToSafeHtml(mdDecorated);
                } else {
                    textForBubble = contentRaw;
                }

                const bubbleHtml = renderBubble({
                role,
                avatar: value.owner_avatar || (isAi ? "asset/image/logo-mmi.png" : "asset/image/icon-member.png"),
                name: value.owner_name || (isAi ? "AI-mmi" : "You"),
                text: textForBubble,
                createdAtIso: value.created_time,
                isHtml: isAi,                 // 只有 AI 的内容当 HTML 插入
                });

                dialog_group += bubbleHtml;
                dialog_date_int = value.target_date;
            });

            dialog_group =
                '<div id="chat-' +
                dialog_date_int +
                '"><div class="chat-date"><span>' +
                dialog_date_int +
                '</span></div><div class="clearboth"></div>' +
                dialog_group +
                "</div>";
            if ($("#chat-" + dialog_date_int).length == 0) {
                if (iweb.isValue(init)) {
                    $(
                        "main.page-body div.chat-area div.box > div.show-message"
                    ).append(dialog_group);
                    $(
                        "main.page-body div.chat-area div.box > div.show-message"
                    ).scrollTop(
                        $(
                            "main.page-body div.chat-area div.box > div.show-message"
                        )[0].scrollHeight
                    );
                } else {
                    $(
                        "main.page-body div.chat-area div.box > div.show-message"
                    ).prepend(dialog_group);
                }
            }
        }
    });
}

function setPostsFullScr() {
    if (
        $("#account-publish-form div.row div.iweb-input > div > textarea")
            .length > 0
    ) {
        $("#account-publish-form div.row div.iweb-input > div > textarea").css(
            "height",
            "auto"
        );
        if ($("div.iweb-info-dialog.fullscr").length > 0) {
            var new_height = $(window).outerHeight();
            new_height -= $("#account-publish-form div.title").outerHeight();
            new_height -= $(
                "#account-publish-form div.details > div.category"
            ).outerHeight();
            new_height -= $(
                "#account-publish-form div.details > div.row.border"
            ).outerHeight();
            new_height -= $(
                "#account-publish-form div.details > div.action"
            ).outerHeight();
            $(
                "#account-publish-form div.row div.iweb-input > div > textarea"
            ).css("height", parseInt(new_height - 100));
        }
    }
}

// Mobile Chat Toggle Function
function toggleMobileChat() {
    if ($("main.page-body div.chat-area").hasClass("show-mobile")) {
        // Hide mobile chat
        $("main.page-body div.chat-area").removeClass("show-mobile");
        $(".mobile-chat-button").removeClass("hidden");
    } else {
        // Show mobile chat
        $("main.page-body div.chat-area").addClass("show-mobile");
        $(".mobile-chat-button").addClass("hidden");

        // Calculate and set proper height for message area
        var new_height =
            $(window).height() - $("header.page-header").height() - 80;
        $("main.page-body div.chat-area div.box > div.show-message").height(
            Math.max(300, parseInt(new_height))
        );

        // Scroll to bottom to show most recent messages
        var element = $(
            "main.page-body div.chat-area div.box > div.show-message"
        )[0];
        if (element) {
            element.scrollTop = element.scrollHeight;
        }

        // Reset sound control to muted
        $("a#sound-control").removeClass("opened");
        $("a#sound-control > i")
            .removeClass("fa-microphone-slash")
            .removeClass("fa-microphone")
            .addClass("fa-microphone-slash");
        $("#chat-robot-video").prop("muted", true);
    }
}

// Initialize chat on page load
$(document).ready(function () {
    // Only load chat history if welcome message is not showing
    // (welcome_message.js handles initialization if welcome is shown)
    setTimeout(function () {
        if (
            !$(".welcome-message").hasClass("show") &&
            !$(".welcome-message").is(":visible")
        ) {
            // Welcome message is hidden, so load chat history
            loadChatMessage(1);
            // Show chat action buttons
            $(".chat-action-buttons").show();
        }
    }, 500);
});