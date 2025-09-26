var article_page = 1;
var article_loading = false;
var article_loading_enable = true;

function iweb_global_func() {
    $(document).on('click', 'header.page-header div.controls > div.menu > a.open-menu', function() {
        // reset all
        $('header.page-header div.controls > div.menu > a.open-menu').removeClass('show');
        $('header.page-header div.controls > div.menu > a.close-menu').removeClass('show');
        $('header.page-header div.controls > div.menu > a.hide-chat').removeClass('show');

        // target
        $('header.page-header div.controls > div.menu > a.close-menu').addClass('show');
        $('header.page-header div.controls > div.menu > ul').addClass('show');
    });
    
    $(document).on('click', 'header.page-header div.controls > div.menu > a.close-menu', function() {
        // reset all
        $('header.page-header div.controls > div.menu > a.open-menu').removeClass('show');
        $('header.page-header div.controls > div.menu > a.close-menu').removeClass('show');
        $('header.page-header div.controls > div.menu > a.hide-chat').removeClass('show');
        
        // target
        $('header.page-header div.controls > div.menu > a.open-menu').addClass('show');
        $('header.page-header div.controls > div.menu > ul').removeClass('show');
    });
    
    $(document).on('click', 'header.page-header div.controls > div.menu > a.hide-chat', function() {
        if($('main.page-body div.chat-area').hasClass('show-mobile')) {
            $('main.page-body div.chat-area').removeClass('show-mobile');
            var new_height = ($(window).height() - $('header.page-header').height() - $('div.chat-area div.top').height() - $('div.chat-area div.bottom').height());
            new_height = new_height - 210;
            $('main.page-body div.chat-area div.box > div.show-message').height(Math.max(0, parseInt(new_height)));
        }
        else {
        
            // reset all
            $('header.page-header div.controls > div.menu > a.open-menu').removeClass('show');
            $('header.page-header div.controls > div.menu > a.close-menu').removeClass('show');
            $('header.page-header div.controls > div.menu > a.hide-chat').removeClass('show');

            $('main.page-body div.info-area').removeClass('hide');
            $('main.page-body div.chat-area').removeClass('hide');
            $('main.page-body div.info-area').removeClass('show').removeClass('show-mobile');
            $('main.page-body div.chat-area').removeClass('show').removeClass('show-mobile');

            // target
            $('header.page-header div.controls > div.menu > a.open-menu').addClass('show');
            $('header.page-header div.controls > div.menu > ul').removeClass('show');

            var new_height = ($(window).height() - $('header.page-header').height() - $('div.chat-area div.top').height() - $('div.chat-area div.bottom').height());
            new_height = new_height - 210;
            $('main.page-body div.chat-area div.box > div.show-message').height(Math.max(0, parseInt(new_height)));
        }
    });
    
    $(document).on('click', 'a.floating-show-chat, main.page-body div.chat-area div.box > a.btn-expand-full', function() {
        // reset all
        $('header.page-header div.controls > div.menu > a.open-menu').removeClass('show');
        $('header.page-header div.controls > div.menu > a.close-menu').removeClass('show');
        $('header.page-header div.controls > div.menu > a.hide-chat').removeClass('show');
        
        $('main.page-body div.info-area').removeClass('hide');
        $('main.page-body div.chat-area').removeClass('hide');
        $('main.page-body div.info-area').removeClass('show').removeClass('show-mobile');
        $('main.page-body div.chat-area').removeClass('show').removeClass('show-mobile');
        
        // target
        $('header.page-header div.controls > div.menu > a.hide-chat').addClass('show');
        $('header.page-header div.controls > div.menu > ul').removeClass('show');
        $('main.page-body div.info-area').addClass('hide');
        $('main.page-body div.chat-area').addClass('show');
        
        var new_height = ($(window).height() - $('header.page-header').height() - $('div.chat-area div.top').height() - $('div.chat-area div.bottom').height());
        new_height = new_height - 210;
        $('main.page-body div.chat-area div.box > div.show-message').height(Math.max(0, parseInt(new_height)));
    });
    
    $(document).on('click', 'header.page-header div.controls > div.lang > a', function() {
        if($('header.page-header div.controls > div.lang > div.options').hasClass('show')) {
            $('header.page-header div.controls > div.lang > div.options').removeClass('show');
        }
        else {
            $('header.page-header div.controls > div.lang > div.options').addClass('show');
        }
    });
    
    $(document).on('click', 'main.page-body div.chat-area div.box > a.btn-expand-full-mobile', function() {
        $('main.page-body div.chat-area').addClass('show-mobile');
        var new_height = ($(window).height() - $('header.page-header').height() - $('div.chat-area div.top').height() - $('div.chat-area div.bottom').height());
        new_height = new_height - 110;
        $('main.page-body div.chat-area div.box > div.show-message').height(Math.max(0, parseInt(new_height)));
    });

    // edit publish post
    $(document).on('click', '#edit-publish-posts', function() {
        $.get(_page_base_url+'/account/posts_publish/'+$(this).data('id'), function(html) {
            iweb.dialog(html, function() {
                iweb.form('#account-publish-form', 'json', null ,function(response_data) {
                    if(iweb.isMatch(response_data.status, 200)) {
                        window.location.reload();
                    }
                    else {
                        iweb.alert(response_data.message);
                    }
                });
                
                $(document).off('click', '#account-publish-form a.remove-my-post');
                $(document).on('click', '#account-publish-form a.remove-my-post', function() {
                    var post_id = $(this).data('id');
                    iweb.confirm(_page_global_lang['confirm_delete_one'],function(result) {
                        if(result) {
                            $.getJSON(_page_base_url+'/account/delete_post/'+post_id, function() {
                                window.location.reload();
                            });
                        }
                    });
                });
                
                $(document).off('click', '#account-publish-form div.fullscreen > a');
                $(document).on('click', '#account-publish-form div.fullscreen > a', function() {
                    if($('div.iweb-info-dialog.publish').hasClass('fullscr')) {
                        $('div.iweb-info-dialog.publish').removeClass('fullscr');
                    }
                    else {
                        $('div.iweb-info-dialog.publish').addClass('fullscr');
                    }
                    setPostsFullScr();
                });
                
                $(document).off('focus', '#account-publish-form textarea');
                $(document).on('focus', '#account-publish-form textarea', function() {
                    $('div.upload-photo').slideUp();
                    $('div.upload-video').slideUp();
                });
                
                $(document).off('click', '#show-publish-photo');
                $(document).on('click', '#show-publish-photo', function() {
                    $('div.upload-video').hide();
                    if(!$('div.upload-photo').is(':visible')) {
                        $('div.upload-photo').slideDown();
                    }
                    else {
                        $('div.upload-photo').slideUp();
                    }
                });
                
                $(document).off('click', '#show-publish-video');
                $(document).on('click', '#show-publish-video', function() {
                    $('div.upload-photo').hide();
                    if(!$('div.upload-video').is(':visible')) {
                        $('div.upload-video').slideDown();
                    }
                    else {
                        $('div.upload-video').slideUp();
                    }
                });
                
                $(document).off('change', '#mypostsphoto');
                $(document).on('change', '#mypostsphoto', function() {
                    const regex = /^(.*)(.jpg|.jpeg|.gif|.png|.bmp)$/;
                    var file = (document.getElementById('mypostsphoto')).files;
                    if(iweb.isValue(file)) {
                        file = file[0];
                        if (regex.test(file.name.toLowerCase()) && (typeof(FileReader) !== 'undefined')) {
                            if (file.size <= 2 * 1024 * 1024) {
                                var reader = new FileReader();
                                reader.onload = function(e) {
                                    $('div.postsphoto-file > div.preview').html('<img src="' + e.target.result + '">');
                                }
                                reader.readAsDataURL(file);
                            }
                            else {
                                iweb.alert(iweb.language[iweb.current_language]['max_error'].replace('{num}',2));
                            }
                        }
                        else {
                            iweb.alert(iweb.language[iweb.current_language]['type_error']);
                            $('#mylogo').val('');
                            $('div.postsphoto-file > div.preview').html('');
                        }
                    }
                    else {
                        $('#mylogo').val('');
                        $('div.postsphoto-file > div.preview').html('');
                    }
                });
                
            }, null, 'publish');
        });
    });
    
    $(document).on('click', 'a.do-like', function() {
        var object = $(this);
        var posts_id = parseInt(object.data('id'));
        iweb.post({
            url: _page_base_url+'/account_article/ticklike',
            values: {
                posts_id: posts_id,
                _token: _token
            },
            showProcessing: false
        },function(response_data) {
            if(iweb.isMatch(response_data.status, 200)) {
                object.closest('div.post').find('div.total > div.like > span').html(response_data.total)
            }
            else if(iweb.isValue(response_data.url)) {
                window.location.href = response_data.url;
            }
        });
    });
    
    $(document).on('click', 'a.do-comment', function() {
        var object = $(this);
        var posts_id = parseInt(object.data('id'));
        if(object.closest('div.post').find('div.leavecomment').is(':visible')) {
            object.closest('div.post').find('div.leavecomment').slideUp();
        }
        else {
            $.get(_page_base_url+'/account_article/comment/'+posts_id, function(html) {
                object.closest('div.post').find('div.reply').html(html);
                object.closest('div.post').find('div.leavecomment').slideDown();
            });
        }
    });
    
    $(document).on('click','a.do-share', function() {
        var shareto = $(this).closest('div.actions').find('div.shareto');
        if(shareto.hasClass('show')) {
            shareto.removeClass('show');
        }
        else {
            shareto.addClass('show');
        }
    });

    $(document).on('click', 'button.btn-send-comment', function() {
        var object = $(this);
        var posts_id = parseInt(object.data('id'));
        var message = object.closest('div.leavecomment').find('textarea[name="message"]').val();
        if(iweb.isValue(message)) {
            iweb.post({
                url: _page_base_url+'/account_article/comment',
                values: {
                    posts_id: posts_id,
                    content: message,
                    _token: _token
                },
                showProcessing: false
            },function(response_data) {
                if(iweb.isMatch(response_data.status, 200)) {
                    object.closest('div.leavecomment').find('textarea[name="message"]').val('');
                    object.closest('div.post').find('div.total > div.comment > span').html(response_data.total);
                    $.get(_page_base_url+'/account_article/comment/'+posts_id, function(html) {
                        object.closest('div.post').find('div.reply').html(html);
                    });
                }
                else if(iweb.isValue(response_data.url)) {
                    window.location.href = response_data.url;
                }
            });
        }
    });
    
    if($('div.mypage').length > 0) {
        iweb.pagination('div.mypage');
    }
    
    loadArticle();
    
    iweb.form('#ask-form', 'json', function() {
        console.log('Form submission started');
        if(!iweb.isValue($('#ask_question').val())) {
            console.log('No question entered');
            return false;
        }
        $('main.page-body div.chat-area div.box').addClass('mask');
        return true;
    }, function(response_data) {
        console.log('Form response received:', response_data);
        $('main.page-body div.chat-area div.box').removeClass('mask');
        $('#ask_question').val('');
        if(iweb.isMatch(response_data.status, 200)) {
            if(iweb.isValue(response_data.content)) {
                var dialog_group = '<div class="dialog ask">';
                dialog_group += '<div class="avatar"><img src="asset/image/icon-member.png" alt="icon-member"><div style="background-image:url(\''+(response_data.member_owner_avatar)+'\')"></div></div>';
                dialog_group += '<div class="name">'+(response_data.member_owner_name)+'</div>';
                dialog_group += '<div class="clearboth"></div>';
                dialog_group += '<div class="txt">'+(response_data.content)+'</div>';
                dialog_group += '</div><div class="clearboth"></div>';

                $('main.page-body div.chat-area div.box > div.show-message').append(dialog_group);
                console.log('User message added, scrolling...');
                setTimeout(function() {
                    var element = $('main.page-body div.chat-area div.box > div.show-message')[0];
                    if (element) {
                        var scroll_value = element.scrollHeight;
                        console.log('User message - Element found, scrolling to:', scroll_value, 'Current scrollTop:', element.scrollTop);
                        element.scrollTop = scroll_value;
                        console.log('After scroll - scrollTop:', element.scrollTop);
                    } else {
                        console.log('User message - Element not found!');
                    }
                }, 100);
                // show reply if need
                if(iweb.isValue(response_data.reply)) {
                    var chat_timer = setTimeout(function() {
                        clearTimeout(chat_timer);

                        dialog_group = '<div class="dialog reply">';
                        dialog_group += '<div class="avatar"><img src="asset/image/icon-member.png" alt="icon-member"><div style="background-image:url(\''+(response_data.ai_owner_avatar)+'\')"></div></div>';
                        dialog_group += '<div class="name">'+(response_data.ai_owner_name)+'</div>';
                        dialog_group += '<div class="clearboth"></div>';
                        dialog_group += '<div class="txt">'+(response_data.reply)+'</div>';
                        dialog_group += '</div><div class="clearboth"></div>';

                        $('main.page-body div.chat-area div.box > div.show-message').append(dialog_group);
                        console.log('AI reply added, scrolling...');
                        setTimeout(function() {
                            var element = $('main.page-body div.chat-area div.box > div.show-message')[0];
                            if (element) {
                                var scroll_value = element.scrollHeight;
                                console.log('AI reply - Element found, scrolling to:', scroll_value, 'Current scrollTop:', element.scrollTop);
                                element.scrollTop = scroll_value;
                                console.log('After scroll - scrollTop:', element.scrollTop);
                            } else {
                                console.log('AI reply - Element not found!');
                            }
                        }, 100);
                    }, 1000);
                }
            }
        }
        else {
            iweb.alert(response_data.message, function() {
                if(iweb.isValue(response_data.url)) {
                    window.location.href = response_data.url;
                }
            });
        }
    });
}

function iweb_global_func_done() {
    // load full article content
    $(document).on('click', 'a.load-fullcontent', function() {
        var object = $(this);
        $.get(_page_base_url+'/account_article/fullcontent/'+(object.data('id')), function(html) {
            if(iweb.isValue(html)) {
                object.closest('div.article-short-content').html(html);
            }
        });
    });
    
    // video sound
    $(document).on('click', 'a#sound-control', function() {
        if($(this).hasClass('opened')) {
            $(this).removeClass('opened');
            $('a#sound-control > i').removeClass('fa-microphone-slash').removeClass('fa-microphone').addClass('fa-microphone-slash');
            $('#ai-robot-video').prop('muted', true);
        }
        else {
            $(this).addClass('opened');
            $('a#sound-control > i').removeClass('fa-microphone-slash').removeClass('fa-microphone').addClass('fa-microphone');
            $('#ai-robot-video').prop('muted', false);
        }
    });
    
    $('main.page-body div.chat-area div.box > div.show-message').scroll(function() {
        var pos = $('main.page-body div.chat-area div.box > div.show-message').scrollTop();
        if (pos == 0) {
            loadChatMessage();
        }
    });
    $('main.page-body div.chat-area div.box > div.show-message').click(function() {
        var pos = $('main.page-body div.chat-area div.box > div.show-message').scrollTop();
        if (pos == 0) {
            loadChatMessage();
        }
    });
    
    loadChatMessage(1);
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
    if($(window).scrollTop() + $(window).height() > $(document).height() - 200) {
        loadArticle();
    }
}

function resetPageView() {
    // reset all
    $('header.page-header div.controls > div.lang > div.options').removeClass('show');
    
    $('header.page-header div.controls > div.menu > a.open-menu').removeClass('show');
    $('header.page-header div.controls > div.menu > a.close-menu').removeClass('show');
    $('header.page-header div.controls > div.menu > a.hide-chat').removeClass('show');
    
    // target
    $('header.page-header div.controls > div.menu > a.open-menu').addClass('show');
    $('header.page-header div.controls > div.menu > ul').removeClass('show');
    
    $('main.page-body div.info-area').removeClass('hide');
    $('main.page-body div.chat-area').removeClass('hide');
    $('main.page-body div.info-area').removeClass('show');
    $('main.page-body div.chat-area').removeClass('show');
    
    // resize chat message height
    if($('a.floating-show-chat').is(':visible')) {
        var new_height = ($(window).height() - $('header.page-header').height() - $('div.chat-area div.top').height() - $('div.chat-area div.bottom').height());
        new_height = new_height - 210;
        $('main.page-body div.chat-area div.box > div.show-message').height(Math.max(0, parseInt(new_height)));
        $('#bottom-white-space').css('height', $('a.floating-show-chat').outerHeight());
    }
    else {
        var new_height = ($(window).height() - $('header.page-header').height() - $('footer.page-footer').height() - $('div.chat-area div.top').height() - $('div.chat-area div.bottom').height());
        new_height = new_height - 210;
        $('main.page-body div.chat-area div.box > div.show-message').height(Math.max(0, parseInt(new_height)));
        $('#bottom-white-space').css('height', 'auto');
    }
    
    $('a#sound-control').removeClass('opened');
    $('a#sound-control > i').removeClass('fa-microphone-slash').removeClass('fa-microphone').addClass('fa-microphone-slash');
    $('#ai-robot-video').prop('muted', true);
    
}

function loadArticle() {
    if($('div.article-list').length > 0) {
        if(!article_loading && article_loading_enable) {
            article_loading = true;
            $.get(_page_base_url+'/account_article?mid='+(parseInt($('div.article-list').data('mid')))+'&page='+article_page, function(html) {
                if(iweb.isValue(html)) {
                    $('div.article-list').append(html).each(function() {
                        iweb.responsive();
                        article_page = article_page + 1;
                        setTimeout(function() {
                            article_loading = false;
                        }, 500);
                    });
                }
                else {
                    article_loading_enable = false;
                }
            });
        }
    }
}

function loadChatMessage(init) {
    var url = _page_base_url+'/home/chat'+((iweb.isValue(init))?'/1':'');
    console.log(url);
    $.getJSON(url, function(data) {
        if(iweb.isValue(data)) {
            var dialog_group = '';
            var dialog_date_int = 0;
            $.each(data, function(key, value) {
                dialog_group += '<div class="dialog '+(value.type)+'">';
                dialog_group += '<div class="avatar"><img src="asset/image/icon-member.png" alt="icon-member"><div style="background-image:url(\''+(value.owner_avatar)+'\')"></div></div>';
                dialog_group += '<div class="name">'+(value.owner_name)+'</div>';
                dialog_group += '<div class="clearboth"></div>';
                dialog_group += '<div class="txt">'+(value.content)+'</div>';
                dialog_group += '</div><div class="clearboth"></div>';
                dialog_date_int = value.target_date;
            });
            dialog_group = '<div id="chat-'+dialog_date_int+'"><div class="chat-date"><span>'+dialog_date_int+'</span></div><div class="clearboth"></div>'+dialog_group+'</div>';
            if($('#chat-'+dialog_date_int).length == 0) {
                if(iweb.isValue(init)) { 
                    $('main.page-body div.chat-area div.box > div.show-message').append(dialog_group);
                    $('main.page-body div.chat-area div.box > div.show-message').scrollTop($('main.page-body div.chat-area div.box > div.show-message')[0].scrollHeight);
                }
                else {
                    $('main.page-body div.chat-area div.box > div.show-message').prepend(dialog_group);
                }
            }
        }
    });
}

function setPostsFullScr() {
    if($('#account-publish-form div.row div.iweb-input > div > textarea').length > 0) {
        $('#account-publish-form div.row div.iweb-input > div > textarea').css('height', 'auto');
        if($('div.iweb-info-dialog.fullscr').length > 0) {
            var new_height = $(window).outerHeight();
            new_height -= $('#account-publish-form div.title').outerHeight();
            new_height -= $('#account-publish-form div.details > div.category').outerHeight();
            new_height -= $('#account-publish-form div.details > div.row.border').outerHeight();
            new_height -= $('#account-publish-form div.details > div.action').outerHeight();
            $('#account-publish-form div.row div.iweb-input > div > textarea').css('height', parseInt(new_height-100));
        }
    }
}

// Mobile Chat Toggle Function
function toggleMobileChat() {
    const chatArea = $('main.page-body div.chat-area');
    const mobileButton = $('.mobile-chat-button');

    if (chatArea.hasClass('show-mobile')) {
        // Hide mobile chat
        chatArea.removeClass('show-mobile');
        mobileButton.removeClass('hidden');
    } else {
        // Show mobile chat
        chatArea.addClass('show-mobile');
        mobileButton.addClass('hidden');

        // Calculate and set proper height for message area
        var new_height = ($(window).height() - $('header.page-header').height() - 80);
        $('main.page-body div.chat-area div.box > div.show-message').height(Math.max(300, parseInt(new_height)));
    }
}