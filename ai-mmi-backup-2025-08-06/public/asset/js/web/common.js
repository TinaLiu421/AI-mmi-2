var article_page = 1;
var article_loading = false;
var article_loading_enable = true;

$.ajaxSetup({
    headers: {
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
    },
});

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


var __ragBypassOnce = false;

// —— RAG 命中阈值（可调）——
const RAG_MIN_MATCH = 3;
const RAG_MIN_SCORE = 0.62;

// 调用 RAG 接口
function callRAG(question, tag = "policy") {
  return $.ajax({
    url: "/api/rag/ask",
    method: "POST",
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    headers: { Accept: "application/json" },
    timeout: 20000, 
    data: JSON.stringify({ q: question, tag })
  });
}

function submitOnce() {
  __ragBypassOnce = true;                 
  $("#ask-form").trigger("submit");       
  setTimeout(() => { __ragBypassOnce = false; }, 0); 
}

function ensureHiddenFields() {
  const $form = $("#ask-form");
  if ($("#hidden_question").length === 0) {
    $form.append('<input type="hidden" id="hidden_question" name="question">');
  }
  if ($("#use_rag").length === 0) {
    $form.append('<input type="hidden" id="use_rag" name="use_rag" value="0">');
  }
  if ($("#override_reply").length === 0) {
    $form.append('<input type="hidden" id="override_reply" name="override_reply" value="">');
  }
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


// --- 简易主题判定：移民/签证关键词 ---
function isMigrationQuery(text) {
  if (!text) return false;
  const s = String(text).toLowerCase();
  const kw = [
    // EN
    "visa","migration","immigration","pr","permanent residence","skilled",
    "482","485","189","190","491","sponsor","sponsorship","work visa",
    "h1b","eb","green card",
    // ZH
    "移民","签证","工签","学签","永居","绿卡","担保","打分","凑分",
    "州担","雇主担保","技术移民",
    // Countries
    "australia","uk","united kingdom","canada","usa","united states",
    "美国","英国","澳大利亚","加拿大"
  ];
  return kw.some(k => s.includes(k));
}


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
        function () {
            if (
                $(
                    "header.page-header div.controls > div.lang > div.options"
                ).hasClass("show")
            ) {
                $(
                    "header.page-header div.controls > div.lang > div.options"
                ).removeClass("show");
            } else {
                $(
                    "header.page-header div.controls > div.lang > div.options"
                ).addClass("show");
            }
        }
    );

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

    // edit publish post
    $(document).on("click", "#edit-publish-posts", function () {
        $.get(
            _page_base_url + "/account/posts_publish/" + $(this).data("id"),
            function (html) {
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
            }
        );
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

    $(document).on("click", "a.do-comment", function () {
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
    });

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
            iweb.post(
                {
                    url: _page_base_url + "/account_article/comment",
                    values: {
                        posts_id: posts_id,
                        content: message,
                        _token: _token,
                    },
                    showProcessing: false,
                },
                function (response_data) {
                    if (iweb.isMatch(response_data.status, 200)) {
                        object
                            .closest("div.leavecomment")
                            .find('textarea[name="message"]')
                            .val("");
                        object
                            .closest("div.post")
                            .find("div.total > div.comment > span")
                            .html(response_data.total);
                        $.get(
                            _page_base_url +
                                "/account_article/comment/" +
                                posts_id,
                            function (html) {
                                object
                                    .closest("div.post")
                                    .find("div.reply")
                                    .html(html);
                            }
                        );
                    } else if (iweb.isValue(response_data.url)) {
                        window.location.href = response_data.url;
                    }
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

    iweb.form(
        "#ask-form",
        "json",
        // ---------- beforeSubmit ----------
        function () {
            ensureHiddenFields();
            // —— ❶：这几行很关键：如果是“绕过一次”，直接放行给原流程（让 iweb.form 自己提交，带上原来的 CSRF 等）
            if (__ragBypassOnce === true) {
                __ragBypassOnce = false; // 用完立即复位
                return true;             
            }

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

            // ---- 统一：复制到隐藏域，并暂时移除 textarea 的 name（避免重名冲突）----
            $("#hidden_question").val(userQuestion);
            const oldName = $ta.attr("name");
            if (oldName) $ta.attr("data-old-name", oldName).removeAttr("name");

            // —— 显示用户气泡、thinking 动画（保持你原来的写法）——
            window.__lastUserQuestion = userQuestion;
            var userHtml = renderBubble({ /* 你的原参数：role/avatar/name/text/time */ 
                role: "ask",
                avatar: _current_member && _current_member.avatar
                    ? (_current_member.type == 1 ? "upload/member_avatar/" : "upload/member_logo/") + _current_member.avatar
                    : "asset/image/icon-member.png",
                name: (_current_member && _current_member.name) ? _current_member.name : "You",
                text: escapeHtml(userQuestion),
                createdAtIso: new Date().toISOString(),
            });
            $("main.page-body div.chat-area div.box > div.show-message").append(userHtml);

            var thinkingIndicator =
                '<div class="dialog reply thinking-indicator">' +
                '<div class="avatar"><div style="background-image:url(\'/asset/image/logo-mmi.png\')"></div></div>' +
                '<div class="txt">Thinking<span class="dot"></span><span class="dot"></span><span class="dot"></span></div>' +
                '</div><div class="clearboth"></div>';
            $("main.page-body div.chat-area div.box > div.show-message").append(thinkingIndicator);
            scrollChatToBottom();

            // UI：清空输入框&占位
            $ta.val("").attr("placeholder","Thinking...");

            // —— ❷：只在“签证/移民类问题”时拦截，尝试 RAG；否则直接 return true 走原流程（含 CSRF）
            const isVisa = isMigrationQuery(userQuestion); 
            if (!isVisa) {
                $("#use_rag").val("0");
                $("#override_reply").val("");
                submitOnce();
                return false;
            }

            // —— ❸：签证问题 → 先试 RAG；命中就自己渲染；不命中则“交还控制权”给原流程
            callRAG(userQuestion, "policy")
                .then(function (rag) {

                    function isNonAnswer(s = "") {
                        const t = String(s).trim();
                        if (t.length < 50) return true; // 太短，多半没料
                        // 常见否定/缺料短语（含直引号/弯引号）
                        const deny = [
                            /i do(?:n['’]t)\s+know/i,
                            /not (?:found|available) in (?:the )?context/i,
                            /no (?:specific|sufficient)?\s*details? (?:provided|found)/i,
                            /insufficient (?:context|information)/i,
                            /i (?:can['’]?t|cannot) answer/i,
                            /context (?:missing|lacks)/i,
                            /无(?:法|足够) (?:确定|回答|提供)/,
                            /不知道|不确定/
                        ];
                        return deny.some(rx => rx.test(t));
                    }

                    // 命中判定（与你之前一致）
                    const matchCount = rag.match_count ?? ((rag.snippets && rag.snippets.length) || 0);
                    const hasHighScore = (rag.snippets || []).some(s => (s.score || 0) >= 0.62);

                    // 有“像样的正文”才算有效：长度、不是否定句、且匹配数/分数满足至少一个门槛
                    const ans = (rag.answer || "").trim();
                    const looksSubstantive = ans.length >= 120 && !isNonAnswer(ans);
                    const ragOk = looksSubstantive && (matchCount >= 3 || hasHighScore || hasConcrete);

                    if (ragOk) {
                        $("#use_rag").val("1");
                        $("#override_reply").val((rag.answer||"").trim());
                        } else {
                        $("#use_rag").val("0");
                        $("#override_reply").val("");
                        }
                        __ragBypassOnce = true;
                        $("#ask-form").trigger("submit");   // 现在真正提交
                    }).fail(function(){
                        // RAG失败 → 普通AI
                        $("#use_rag").val("0");
                        $("#override_reply").val("");
                        __ragBypassOnce = true;
                        $("#ask-form").trigger("submit");
                    });

                    return false; // 这里先不让 iweb.form 提交，等上面的 then/fail 里触发
                    },

            // —— ❹：successFn（第二个回调）保持你原来的实现不变 —— 
            function (response_data) {
                // 这部分用你原来的渲染逻辑：移除 thinking、显示回复、FA 引导、顶部按钮等
                $(".thinking-indicator").remove();

                // 提交完成后，务必把 textarea 的 name 还原（为下一轮做准备）
                const $ta = $("#ask_question");
                const n = $ta.attr("data-old-name");
                if (n) $ta.attr("name", n).removeAttr("data-old-name");

                if (iweb.isMatch(response_data.status, 200)) {
                    if (iweb.isValue(response_data.reply) || iweb.isValue(response_data.answer_html) || iweb.isValue(response_data.answer_markdown)) {
                        // 优先后端提供的 HTML；否则把 Markdown/纯文本转为安全 HTML
                        const md = response_data.answer_markdown || response_data.reply || "";
                        const mdDecorated = decorateMarkdownWithEmojis(md);
                        const safeHtml = response_data.answer_html
                        ? DOMPurify.sanitize(String(response_data.answer_html))
                        : mdToSafeHtml(mdDecorated);

                        const replyHtml = renderBubble({
                        role: "reply",
                        avatar: response_data.ai_owner_avatar || "asset/image/logo-mmi.png",
                        name: response_data.ai_owner_name || "AI-mmi",
                        text: safeHtml,
                        createdAtIso: response_data.reply_created_at || new Date().toISOString(),
                        isHtml: true,
                    });
                    $(
                        "main.page-body div.chat-area div.box > div.show-message"
                    ).append(replyHtml);

                       // Show flow prompt if available
                    if (response_data.flow_prompt) {
                        $("main.page-body div.chat-area div.box > div.show-message").append(
                            `<div class="dialog reply"><div class="txt chat-markdown">${
                            mdToSafeHtml(response_data.flow_prompt)
                            }</div></div><div class="clearboth"></div>`
                        );
                    }
                  
                        scrollChatToBottom();
                    }
                    $("#ask_question").attr("placeholder", "Ask about immigrations, visas, or migration...");
                    $("#hidden_question").val("");
                    $("#use_rag").val("0");
                    $("#override_reply").val("");
                    
                } else {
                    iweb.alert(response_data.message, function () {
                        if (iweb.isValue(response_data.url)) {
                            window.location.href = response_data.url;
                    }
                });
                }
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
                // 历史数据也可能是 markdown/纯文本；AI 的消息转为安全 HTML，用户消息保留纯文本
                let textForBubble;
                if (isAi) {
                if (value.content_html) {
                    textForBubble = DOMPurify.sanitize(String(value.content_html));
                } else {
                    const md = value.content || "";
                    const mdDecorated = decorateMarkdownWithEmojis(md);
                    textForBubble = mdToSafeHtml(mdDecorated);
                }
                } else {
                textForBubble = value.content || "";
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
