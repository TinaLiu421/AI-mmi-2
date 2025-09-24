function iweb_self_func() {
    // profile
    iweb.form('#account-profile-form', 'json', function() {
        if(iweb.isValue($('#account-profile-form input[name="password"]').val()) || iweb.isValue($('#account-profile-form input[name="repeat_password"]').val())) {
            if(!iweb.isMatch($('#account-profile-form input[name="password"]').val(), $('#account-profile-form input[name="repeat_password"]').val())) {
                $('#account-profile-form input[name="password"]').addClass('error');
                $('#account-profile-form input[name="repeat_password"]').addClass('error');
                iweb.showTipsMessage(iweb.language[iweb.default_language]['required_error']);
                return false;
            }
        }
        return true;
    } ,function(response_data) {
        if(iweb.isMatch(response_data.status, 200)) {
            window.location.href = response_data.url;
        }
        else {
            iweb.alert(response_data.message);
        }
    });
    
    // avatar
    $(document).on('click', '#myavatar', function() {
        const regex = /^(.*)(.jpg|.jpeg|.gif|.png|.bmp)$/;
        var files_input = document.createElement('input');
		files_input.type = 'file';
        files_input.accept = 'image/*';
		files_input.onchange = function() {
            var file = files_input.files;
            if(iweb.isValue(file)) {
                file = file[0];
                if (regex.test(file.name.toLowerCase())) {
                    if (file.size <= 2 * 1024 * 1024) {
						var local_time = iweb.getDateTime(null, 'time');
						var formData = new FormData();
						formData.append('page_action', 'file_upload');
						formData.append('itoken', window.btoa(md5(iweb.csrf_token + '#dt' + local_time) + '%' + local_time));
                        formData.append('_token', _token);
						formData.append('myavatar', file, file.name);
                        
                        $.ajax({
                            url: _page_base_url+'/account/myavatar',
                            type: 'post',
                            data: formData,
                            dataType: 'text',
                            processData: false,
                            contentType: false,
                            cache: false,
                            enctype: 'multipart/form-data',
                            success: function(response_data) {
                                window.location.reload();
                            },
                            error: function(xhr, status, thrownError) {
                                alert(thrownError);
                                return false;
                            }
                        });
                    }
                    else {
                        iweb.alert(iweb.language[iweb.current_language]['max_error'].replace('{num}',2));
                    }
                }
                else {
                    iweb.alert(iweb.language[iweb.current_language]['type_error']);
                }
            }
		};
		files_input.click();
    });
    
    // logo
    $(document).on('change', '#mylogo', function() {
        const regex = /^(.*)(.jpg|.jpeg|.gif|.png|.bmp)$/;
        var file = (document.getElementById('mylogo')).files;
        if(iweb.isValue(file)) {
            file = file[0];
            if (regex.test(file.name.toLowerCase()) && (typeof(FileReader) !== 'undefined')) {
                if (file.size <= 2 * 1024 * 1024) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('div.logo-file > div.preview').html('<img src="' + e.target.result + '">');
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
                $('div.logo-file > div.preview').html('');
            }
        }
        else {
            $('#mylogo').val('');
            $('div.logo-file > div.preview').html('');
        }
    });
    
    // coverphoto
    $(document).on('change', '#mycoverphoto', function() {
        const regex = /^(.*)(.jpg|.jpeg|.gif|.png|.bmp)$/;
        var file = (document.getElementById('mycoverphoto')).files;
        if(iweb.isValue(file)) {
            file = file[0];
            if (regex.test(file.name.toLowerCase()) && (typeof(FileReader) !== 'undefined')) {
                if (file.size <= 2 * 1024 * 1024) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('div.coverphoto-file > div.preview').html('<img src="' + e.target.result + '">');
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
                $('div.coverphoto-file > div.preview').html('');
            }
        }
        else {
            $('#mylogo').val('');
            $('div.coverphoto-file > div.preview').html('');
        }
    });
    
    // alias
    $(document).on('click', 'div.page-content.account div.basic > div.name > div > a', function() {
        iweb.dialog($('#hide-extra-form').html(), function() {
            iweb.form('#account-alias-form', 'json', null ,function(response_data) {
                if(iweb.isMatch(response_data.status, 200)) {
                    window.location.reload();
                }
                else {
                    iweb.alert(response_data.message);
                }
            });
        });
    });
    
    // agent
    $(document).on('click', '#registered_agent_yes', function() {
        $('div.page-content.account div.child-agent div.list').removeClass('disabled');
        $('div.page-content.account div.child-agent div.list').find('div.block').each(function() {
            if(!$(this).hasClass('hidden')) {
                $(this).find('input[type="text"]').attr('data-validation', 'required');
                $(this).find('select').attr('data-validation', 'required');
            }
        });
    });
    
    $(document).on('click', '#registered_agent_no', function() {
        $('div.page-content.account div.child-agent div.list').addClass('disabled');
        $('div.page-content.account div.child-agent div.list').find('div.block').each(function() {
            $(this).find('input[type="text"]').attr('data-validation', '');
            $(this).find('select').attr('data-validation', '');
        });
    });
    
    $(document).on('click', 'a.add-agent-block', function() {
        var clone = $('div.page-content.account div.child-agent div.list').find('div.block').first().clone();
        clone.find('input[type="hidden"]').val(0).prop('disabled', false);
        clone.find('input[type="text"]').val('').prop('disabled', false);
        clone.find('select').val('').prop('disabled', false);
        clone.removeClass('hidden');
        clone.prepend('<a class="remove-agent-block"><i class="fa fa-times"></i></a>');
        $('div.page-content.account div.child-agent div.list > div.items').append(clone).each(function() {
            $('div.page-content.account div.child-agent div.num').each(function(key,value) {
                $(this).find('span').html(' - '+(key));
            });
        });
    });
    
    $(document).on('click', 'a.remove-agent-block', function() {
        $(this).closest('div.block').remove();
        $('div.page-content.account div.child-agent div.num').each(function(key,value) {
            $(this).find('span').html(' - '+(key));
        });
    });

    // lawfirm
    $(document).on('click', '#registered_lawfirm_yes', function() {
        $('div.page-content.account div.child-lawfirm div.list').removeClass('disabled');
        $('div.page-content.account div.child-lawfirm div.list').find('div.block').each(function() {
            if(!$(this).hasClass('hidden')) {
                $(this).find('input[type="text"]').attr('data-validation', 'required');
                $(this).find('select').attr('data-validation', 'required');
            }
        });
    });
    
    $(document).on('click', '#registered_lawfirm_no', function() {
        $('div.page-content.account div.child-lawfirm div.list').addClass('disabled');
        $('div.page-content.account div.child-lawfirm div.list').find('div.block').each(function() {
            $(this).find('input[type="text"]').attr('data-validation', '');
            $(this).find('select').attr('data-validation', '');
        });
    });
    
    $(document).on('click', 'a.add-lawfirm-block', function() {
        var clone = $('div.page-content.account div.child-lawfirm div.list').find('div.block').first().clone();
        clone.find('input[type="hidden"]').val(0).prop('disabled', false);
        clone.find('input[type="text"]').val('').prop('disabled', false);
        clone.find('select').val('').prop('disabled', false);
        clone.removeClass('hidden');
        clone.prepend('<a class="remove-lawfirm-block"><i class="fa fa-times"></i></a>');
        $('div.page-content.account div.child-lawfirm div.list > div.items').append(clone).each(function() {
            $('div.page-content.account div.child-lawfirm div.num').each(function(key,value) {
                $(this).find('span').html(' - '+(key));
            });
        });
    });
    
    $(document).on('click', 'a.remove-lawfirm-block', function() {
        $(this).closest('div.block').remove();
        $('div.page-content.account div.child-lawfirm div.num').each(function(key,value) {
            $(this).find('span').html(' - '+(key));
        });
    });
    
    // publish post
    $(document).on('click', '#publish-photo, #publish-video', function() {
        $.get(_page_base_url+'/account/posts_publish', function(html) {
            iweb.dialog(html, function() {
                iweb.form('#account-publish-form', 'json', null ,function(response_data) {
                    if(iweb.isMatch(response_data.status, 200)) {
                        window.location.reload();
                    }
                    else {
                        iweb.alert(response_data.message, function() {
                            if(iweb.isValue(response_data.url)) {
                                window.location.href = response_data.url;
                            }
                        });
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
}
