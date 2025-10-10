var article_page = 1;
var article_loading = false;
var article_loading_enable = true;

$.ajaxSetup({
  headers: {
    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
  }
});

function escapeHtml(s) {
  return String(s)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#039;');
}

function renderBubble({ role, avatar, name, text, createdAtIso }) {
  const timeLocal = formatUtcIsoToLocalTime(createdAtIso || new Date().toISOString());
  return `
    <div class="dialog ${role}">
      <div class="avatar">
        <img src="asset/image/icon-member.png" alt="icon-member">
        <div style="background-image:url('${avatar || ''}')"></div>
      </div>
      <div class="name">${escapeHtml(name || '')}</div>
      <div class="time">${timeLocal}</div>
      <div class="clearboth"></div>
      <div class="txt">${text}</div>
    </div>
    <div class="clearboth"></div>
  `;
}

function buildTopButtonsHintBubble() {
  return `
    <div class="dialog reply">
      <div class="avatar">
        <img src="asset/image/icon-member.png" alt="icon-member">
        <div style="background-image:url('asset/image/logo-mmi.png')"></div>
      </div>
      <div class="name">AI-mmi</div>
      <div class="clearboth"></div>
      <div class="txt">
        <p>Please select the buttons at the top of the chat window to access the full range of AI-mmi services.</p>
      </div>
    </div>
    <div class="clearboth"></div>
  `;
}

// --- Topic Determination (Multilingual Keywords, as Broadly as Possible) ---
function isStudyQuery(text) {
  if (!text) return false;
  const s = text.toLowerCase();
  const kw = [
    // EN
    'study','studies','student','school','college','university','course','degree',
    'admission','application','scholarship','tuition','ucas','commonapp',
    // ZH
    '留学','学校','大学','学院','课程','专业','申请','录取','奖学金','学费','入学','文书','sop','推荐信',
    // Others
    'ielts','toefl','pte','gpa','ranking'
  ];
  return kw.some(k => s.includes(k));
}

function isMigrationQuery(text) {
  if (!text) return false;
  const s = text.toLowerCase();
  const kw = [
    // EN
    'visa','migration','immigration','pr','permanent residence','skilled','482','485','189','190','491',
    'sponsor','sponsorship','work visa','h1b','eb','green card',
    // ZH
    '移民','签证','工签','学签','永居','绿卡','担保','打分','凑分','州担','雇主担保','技术移民',
    // Countries
    'australia','uk','united kingdom','canada','usa','united states','美国','英国','澳大利亚','加拿大'
  ];
  return kw.some(k => s.includes(k));
}

function formatUtcIsoToLocalTime(isoString) {
    try {
        const d = new Date(isoString);
        // timeStyle:‘short’ will automatically output times in formats like 09:05 / 9:05 AM based on the user's region.
        return new Intl.DateTimeFormat(undefined, { timeStyle: 'short' }).format(d);
    } catch (e) {
        // Bottom line: Current local time
        return new Intl.DateTimeFormat(undefined, { timeStyle: 'short' }).format(new Date());
    }
}

function scrollChatToBottom() {
  const el = $("main.page-body div.chat-area div.box > div.show-message")[0];
  if (el) el.scrollTop = el.scrollHeight;
}

// Generate Timestamp HTML
function buildTimeLineHtml(text) {
    return '<div class="time">' + text + '</div>';
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
        function () {
            console.log("Form submission started");
            if (!iweb.isValue($("#ask_question").val())) {
                console.log("No question entered");
                return false;
            }

            // Show user's question immediately before sending
            var userQuestion = $("#ask_question").val();

            // Record the current round's question (for topic identification in successful callback)
            window.__lastUserQuestion = userQuestion;
            
            var userAvatar =
                _current_member && _current_member.avatar
                    ? (_current_member.type == 1
                          ? "upload/member_avatar/"
                          : "upload/member_logo/") + _current_member.avatar
                    : "asset/image/icon-member2.png";
            var userName =
                _current_member && _current_member.name
                    ? _current_member.name
                    : "You";

            const nowIso = new Date().toISOString(); // First use the browser's local timestamp (ISO)
            const userHtml = renderBubble({
            role: 'ask',
            avatar: (_current_member && _current_member.avatar)
                    ? ((_current_member.type == 1 ? "upload/member_avatar/" : "upload/member_logo/") + _current_member.avatar)
                    : 'asset/image/icon-member.png',
            name:  (_current_member && _current_member.name) ? _current_member.name : 'You',
            text:  escapeHtml(userQuestion),
            createdAtIso: nowIso
            });

            $("main.page-body div.chat-area div.box > div.show-message").append(userHtml);


            // Clear the textarea immediately
            $("#ask_question").val("");

            // Add thinking indicator with animated dots
            var thinkingIndicator =
                '<div class="dialog reply thinking-indicator">';
            thinkingIndicator +=
                '<div class="avatar"><img src="/asset/image/icon-member.png" alt="icon-member"><div style="background-image:url(\'/asset/image/logo-mmi.png\')"></div></div>';
            thinkingIndicator +=
                '<div class="txt">Thinking<span class="dot"></span><span class="dot"></span><span class="dot"></span></div>';
            thinkingIndicator += '</div><div class="clearboth"></div>';
            $("main.page-body div.chat-area div.box > div.show-message").append(
                thinkingIndicator
            );

            // Scroll to bottom
            var element = $(
                "main.page-body div.chat-area div.box > div.show-message"
            )[0];
            if (element) {
                element.scrollTop = element.scrollHeight;
            }

            return true;
        },
        function (response_data) {
            console.log("Form response received:", response_data);

            // Remove thinking indicator
            $(".thinking-indicator").remove();

            if (iweb.isMatch(response_data.status, 200)) {
                // User question is already shown immediately, just show AI reply
                if (iweb.isValue(response_data.reply)) {

                    const replyHtml = renderBubble({
                        role: 'reply',
                        avatar: response_data.ai_owner_avatar || 'asset/image/logo-mmi.png',
                        name:  response_data.ai_owner_name  || 'AI-mmi',
                        text:  response_data.reply, 
                        createdAtIso: response_data.reply_created_at || new Date().toISOString()
                    });
                    $("main.page-body div.chat-area div.box > div.show-message").append(replyHtml);

                    const userQ = (window.__lastUserQuestion || '').trim();

                    // Hit study abroad/immigration topic → Pull profile → Branch rendering
                    if (isStudyQuery(userQ) || isMigrationQuery(userQ)) {
                    const fetchFA = () => fetch(`${_page_base_url}/home/fa_me`, { credentials: 'include' })
                                            .then(r => r.json()).catch(() => ({ has_profile:false }));
                    (window.__fa_cache__ ? Promise.resolve(window.__fa_cache__) : fetchFA().then(d => (window.__fa_cache__ = d)))
                    .then(fa => {
                        if (!fa || !fa.has_profile) {
                        const cta = `
                            <div class="dialog reply">
                            <div class="avatar">
                                <img src="asset/image/icon-member.png" alt="icon-member">
                                <div style="background-image:url('asset/image/logo-mmi.png')"></div>
                            </div>
                            <div class="name">AI-mmi</div>
                            <div class="clearboth"></div>
                            <div class="txt">
                                <p>To provide you with more precise recommendations, we suggest completing a Free Assessment first.</p>
                                <div class="ai-actions">
                                <a class="ai-btn" href="${_page_base_url}/free_assessment">Go fill out the free assessment</a>
                                </div>
                            </div>
                            </div>
                            <div class="clearboth"></div>`;
                        $("main.page-body div.chat-area div.box > div.show-message").append(cta);
                        scrollChatToBottom();
                        }
                        
                        const hint = buildTopButtonsHintBubble();
                        $("main.page-body div.chat-area div.box > div.show-message").append(hint);
                        scrollChatToBottom(); 
                        
                    });
                    }

                    console.log("AI reply added, scrolling...");

                    var element = $(
                        "main.page-body div.chat-area div.box > div.show-message"
                    )[0];
                    if (element) {
                        element.scrollTop = element.scrollHeight;
                    }
                }
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

    // Chat history loading is now handled by immigration-chat.js
    // Only loads when user selects a mode from welcome message
    //$('main.page-body div.chat-area div.box > div.show-message').scrollTop($('main.page-body div.chat-area div.box > div.show-message')[0].scrollHeight);
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

    // Get current chat mode
    var currentMode =
        $("#chat_mode").val() || _current_chat_mode || "immigration";

    // Send current chat mode to ensure we get the right history
    var requestData = {};
    if (currentMode) {
        requestData.chat_mode = currentMode;
    }

    console.log("Loading chat for mode:", currentMode, "URL:", url);

    $.getJSON(url, requestData, function (data) {
        if (iweb.isValue(data)) {
            var dialog_group = "";
            var dialog_date_int = 0;
            $.each(data, function (key, value) {
                const role = (String(value.type || '').toLowerCase() === 'ask' || String(value.type || '').toLowerCase() === 'member')
                            ? 'ask'
                            : 'reply';

                const bubbleHtml = renderBubble({
                    role,
                    avatar: value.owner_avatar || (role === 'reply' ? 'asset/image/logo-mmi.png' : 'asset/image/icon-member.png'),
                    name:   value.owner_name   || (role === 'reply' ? 'AI-mmi' : 'You'),
                    text:   value.content,  
                    createdAtIso: value.created_time 
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
