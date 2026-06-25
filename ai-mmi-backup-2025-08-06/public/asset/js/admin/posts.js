function iweb_self_func() {
    $(document).on('click', 'a.remove-posts-post', function() {
        var find_ids = $(this).data('id');
        iweb.confirm(_page_global_lang['confirm_delete_all'],function(result) {
            if(result) {
                iweb.post({
                    url: window.location.href,
                    values: {
                        _token: _token,
                        page_action: 'delete_sub',
                        id: find_ids
                    }
                },function() {
                    window.location.reload();
                });
            }
        });
    });
    
    
    $(document).on('click', 'a.set-highlight', function() {
        var find_ids = $(this).data('id');
        iweb.post({
            url: window.location.href,
            values: {
                _token: _token,
                page_action: 'highlight',
                id: find_ids
            }
        },function() {
            window.location.reload();
        });
    });


    $(document).on('click', 'a.set-feature', function() {
        var find_ids = $(this).data('id');
        var is_featured = $(this).find('i').hasClass('fa-star') && !$(this).find('i').hasClass('fa-star-o');
        if (is_featured) {
            iweb.confirm('Remove this post from featured spotlight?', function(result) {
                if (result) {
                    iweb.post({
                        url: window.location.href,
                        values: { _token: _token, page_action: 'feature', id: find_ids }
                    }, function() { window.location.reload(); });
                }
            });
        } else {
            iweb.confirm('Feature this post for 7 days on the home page spotlight?', function(result) {
                if (result) {
                    iweb.post({
                        url: window.location.href,
                        values: { _token: _token, page_action: 'feature', id: find_ids }
                    }, function() { window.location.reload(); });
                }
            });
        }
    });
    
    
    $(document).on('click', '#delete-my-posts', function() {
        var find_ids = $(this).data('id');
        iweb.confirm(_page_global_lang['confirm_delete_one'],function(result) {
            if(result) {
                iweb.post({
                    url: _page_base_url+'/posts',
                    values: {
                        _token: _token,
                        page_action: 'delete',
                        id: find_ids
                    }
                },function() {
                    window.location.href = _page_base_url+'/posts';
                });
            }
        });
    });
    
    
    $(document).on('click', '#edit-my-posts', function() {
        $.get(_page_base_url+'/posts/renew/'+$(this).data('id'), function(html) {
            iweb.dialog(html, function() {
                iweb.form('#account-publish-form', 'json', null ,function(response_data) {
                    if(iweb.isMatch(response_data.status, 200)) {
                        window.location.reload();
                    }
                    else {
                        iweb.alert(response_data.message);
                    }
                });
                
                $('#created_at').datetimepicker({
                    timepicker: true,
                    format: 'Y-m-d H:s',
                    scrollMonth : false
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
            $('#account-publish-form div.row div.iweb-input > div > textarea').css('height', parseInt(new_height-300));
        }
    }
}