var article_page = 1;
var article_loading = false;
var article_loading_enable = true;

function formatUtcIsoToLocalTime(isoString) {
    try {
        const d = new Date(isoString);
        // timeStyle:'short' 会自动按用户地区输出 09:05 / 9:05 AM 等格式
        return new Intl.DateTimeFormat(undefined, { timeStyle: 'short' }).format(d);
    } catch (e) {
        // 兜底：当前本地时间
        return new Intl.DateTimeFormat(undefined, { timeStyle: 'short' }).format(new Date());
    }
}

// 生成时间标签 HTML（你之前时间是在气泡上方单独一行）
function buildTimeLineHtml(text) {
    return '<div class="time">' + text + '</div>';
}

function iweb_global_func() {
    // show welcome message
    showWelcomeMessage();
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
            var userAvatar =
                _current_member && _current_member.avatar
                    ? (_current_member.type == 1
                          ? "upload/member_avatar/"
                          : "upload/member_logo/") + _current_member.avatar
                    : "asset/image/icon-member.png";
            var userName =
                _current_member && _current_member.name
                    ? _current_member.name
                    : "You";

            // ==== 新增：立刻用本地时间显示（以浏览器时间为准）====
            var userLocalTime = new Intl.DateTimeFormat(undefined, { timeStyle: 'short' }).format(new Date());
            var timeLine = buildTimeLineHtml(userLocalTime);
            $("main.page-body div.chat-area div.box > div.show-message").append(timeLine);

            var dialog_group = '<div class="dialog ask">';
            dialog_group +=
                '<div class="avatar"><img src="asset/image/icon-member.png" alt="icon-member">';
            if (_current_member && _current_member.avatar) {
                dialog_group +=
                    "<div style=\"background-image:url('" +
                    userAvatar +
                    "')\"></div>";
            }
            dialog_group += "</div>";
            dialog_group += '<div class="name">' + userName + "</div>";
            dialog_group += '<div class="clearboth"></div>';
            dialog_group += '<div class="txt">' + userQuestion + "</div>";
            dialog_group += '</div><div class="clearboth"></div>';

            $("main.page-body div.chat-area div.box > div.show-message").append(
                dialog_group
            );

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

                    // ==== 新增：把后端 UTC 时间转为本地时间并插入 ====
                    if (response_data.reply_created_at) {
                        var local = formatUtcIsoToLocalTime(response_data.reply_created_at);
                        var timeLine = buildTimeLineHtml(local);
                        $("main.page-body div.chat-area div.box > div.show-message").append(timeLine);
                    }

                    var dialog_group = '<div class="dialog reply">';
                    dialog_group +=
                        '<div class="avatar"><img src="asset/image/icon-member.png" alt="icon-member"><div style="background-image:url(\'' +
                        response_data.ai_owner_avatar +
                        "')\"></div></div>";
                    dialog_group +=
                        '<div class="name">' +
                        response_data.ai_owner_name +
                        "</div>";
                    dialog_group += '<div class="clearboth"></div>';
                    dialog_group +=
                        '<div class="txt">' + response_data.reply + "</div>";
                    dialog_group += '</div><div class="clearboth"></div>';

                    $(
                        "main.page-body div.chat-area div.box > div.show-message"
                    ).append(dialog_group);
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

    // Only load chat if no specific mode is being restored
    setTimeout(function () {
        if (
            !$("#chat_mode").val() &&
            (!_current_chat_mode || _current_chat_mode === "")
        ) {
            loadChatMessage(1);
        }
    }, 300); // Wait for immigration-chat.js to potentially set the mode
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
                dialog_group += '<div class="dialog ' + value.type + '">';
                dialog_group += '<div class="avatar"><img src="asset/image/icon-member.png" alt="icon-member"><div style="background-image:url(\'' + value.owner_avatar + "')\"></div></div>";
                dialog_group += '<div class="name">' + value.owner_name + '</div>';

                
                if (value.created_time) {
                
                    const dateObj = new Date(value.created_time);
                    

                    const formatted = dateObj.toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false, 
                    });

                    dialog_group += '<div class="time">' + formatted + '</div>';
                }

                dialog_group += '<div class="clearboth"></div>';
                dialog_group += '<div class="txt">' + value.content + '</div>';
                dialog_group += '</div><div class="clearboth"></div>';

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
    const chatArea = $("main.page-body div.chat-area");
    const mobileButton = $(".mobile-chat-button");

    if (chatArea.hasClass("show-mobile")) {
        // Hide mobile chat
        chatArea.removeClass("show-mobile");
        mobileButton.removeClass("hidden");
    } else {
        // Show mobile chat
        chatArea.addClass("show-mobile");
        mobileButton.addClass("hidden");

        // Calculate and set proper height for message area
        var new_height =
            $(window).height() - $("header.page-header").height() - 80;
        $("main.page-body div.chat-area div.box > div.show-message").height(
            Math.max(300, parseInt(new_height))
        );
    }
}

function showWelcomeMessage() {
    // Check if user has an active chat mode or existing chat history
    if (
        typeof _current_chat_mode !== "undefined" &&
        _current_chat_mode &&
        _current_chat_mode !== ""
    ) {
        console.log(
            "Skipping welcome message - user has active chat mode:",
            _current_chat_mode
        );
        // User has active chat mode, ensure input is visible
        $(".input-question").addClass("show");
        $("#ask_question").prop("disabled", false);
        $(".robot-container").show(); // Show robot when not showing welcome
        return;
    }

    // Check if there's any chat history by making a quick AJAX call
    $.getJSON(_page_base_url + "/home/chat", function (data) {
        if (data && data.length > 0) {
            console.log("Skipping welcome message - user has chat history");
            // User has chat history, ensure input is visible
            $(".input-question").addClass("show");
            $("#ask_question").prop("disabled", false);
            $(".robot-container").show(); // Show robot when not showing welcome
            return;
        } else {
            // No chat history, show welcome message
            displayWelcomeMessage();
        }
    }).fail(function () {
        // If AJAX fails (user not logged in), show welcome message
        displayWelcomeMessage();
    });
}

function displayWelcomeMessage() {
    const welcomeMessage = `
        <div class="welcome-message">
            <div class="welcome-message__video-container">
                <div class="welcome-message__video-wrapper">
                    <video id="welcome-robot-video" autoplay loop muted playsinline>
                        <source src="asset/image/ai-robot-video.mp4" type="video/mp4">
                    </video>
                    <a id="welcome-sound-control" href="javascript:void(0);" title="Click to unmute">
                        <i class="fa fa-microphone-slash"></i>
                    </a>
                </div>
            </div>

            <div class="welcome-message__transcript">
                <div class="welcome-message__transcript-line"></div>
            </div>

            <div class="welcome-message__footer">
                AI-powered Migration & Study Support - With Instant Access to Human Expert
            </div>
        </div>
    `;

    // Prepend welcome message before the button (button stays in place from blade)
    $("main.page-body div.chat-area div.box > div.show-message").prepend(
        welcomeMessage
    );

    // Show Get Started button
    $(".get-started-container").addClass("show");

    // Hide chat robot video during welcome message
    $(".robot-container").hide();

    // Ensure video plays (some browsers need this)
    setTimeout(function () {
        var video = document.getElementById("welcome-robot-video");
        if (video) {
            video.muted = true; // Must be muted for autoplay
            video.play().catch(function (error) {
                console.log("Video autoplay failed:", error);
            });
        }
    }, 100);

    // Welcome video sound control
    $(document).off("click", "#welcome-sound-control");
    $(document).on("click", "#welcome-sound-control", function () {
        var video = document.getElementById("welcome-robot-video");
        if ($(this).hasClass("opened")) {
            $(this).removeClass("opened");
            $(this).attr("title", "Click to unmute");
            $("#welcome-sound-control > i")
                .removeClass("fa-microphone-slash")
                .removeClass("fa-microphone")
                .addClass("fa-microphone-slash");
            if (video) video.muted = true;
        } else {
            $(this).addClass("opened");
            $(this).attr("title", "Click to mute");
            $("#welcome-sound-control > i")
                .removeClass("fa-microphone-slash")
                .removeClass("fa-microphone")
                .addClass("fa-microphone");
            if (video) video.muted = false;
        }
    });

    // Animate transcript
    animateWelcomeTranscript();
}

function animateWelcomeTranscript() {
    // Sync subtitles with video timestamps (in seconds) - typewriter style
    const subtitles = [
        { time: 0, text: "Hi there! I'm AI-mmi" },
        {
            time: 2,
            text: "your smart companion for migration and education planning.",
        },
        {
            time: 5,
            text: "I can help you explore study options, understand different migration pathways, and get ready for your move abroad",
        },
        { time: 12, text: "all in one easy-to-use platform." },
        {
            time: 14,
            text: "Whether you'd like to do it yourself with our personalized guided tools,",
        },
        {
            time: 17,
            text: "or connect with a trusted professional, I'm here to make your journey smoother, more affordable, and stress-free.",
        },
        {
            time: 24,
            text: "From choosing the right university or course, exploring visa options for your dream destination,",
        },
        {
            time: 29,
            text: "preparing documents, arranging English lessons, finding accommodation or jobs, to relocation services",
        },
        { time: 35, text: "even moving your pets!" },
        {
            time: 38,
            text: "I'll help you stay informed and organized every step of the way.",
        },
        {
            time: 42,
            text: "If you haven't signed up yet go ahead and create your free AI-mmi account to start planning your study or migration journey today!",
        },
    ];

    const video = document.getElementById("welcome-robot-video");
    const transcriptElement = $(".welcome-message__transcript-line");
    let currentSubtitleIndex = -1;
    let typingTimeout = null;

    function typeText(text, charIndex = 0) {
        if (!transcriptElement.length) return;

        if (charIndex <= text.length) {
            transcriptElement.text(text.substring(0, charIndex));
            transcriptElement.addClass("show");
            typingTimeout = setTimeout(function () {
                typeText(text, charIndex + 1);
            }, 50); // Typing speed
        }
    }

    function updateSubtitle() {
        if (!transcriptElement.length || !video) return;

        const currentTime = video.currentTime;

        // Find the current subtitle based on video time
        for (let i = subtitles.length - 1; i >= 0; i--) {
            if (currentTime >= subtitles[i].time) {
                if (currentSubtitleIndex !== i) {
                    currentSubtitleIndex = i;

                    // Clear any ongoing typing
                    if (typingTimeout) {
                        clearTimeout(typingTimeout);
                    }

                    // Start typing new text
                    typeText(subtitles[currentSubtitleIndex].text);
                }
                break;
            }
        }
    }

    // Update subtitle based on video time
    if (video) {
        video.addEventListener("timeupdate", updateSubtitle);
        video.addEventListener("seeked", updateSubtitle);

        // Initial subtitle
        typeText(subtitles[0].text);
    }

}
