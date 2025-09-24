function iweb_self_func() {
    $('div.page-content.media-files #searchform').submit(function() {
        var queryParameters = $(this).serialize().replace(/(&?)(([\w\-\d_]+)=)(&|$)/gi, '$1').replace(/(.*)(&)$/gi,'$1').split('&');
        if(iweb.isValue(queryParameters)) {
            queryParameters = queryParameters.join('&');
        }
        window.location.href = iweb.getUrl()+((iweb.isValue(queryParameters))?('?'+queryParameters):'');
        return false;
    });
    
    $(document).on('click','div.page-content.media-files #btn-select-files',function() {
        iweb.uploader({
            url: _page_base_url+'/media_files',
            values: {
                _token: _token
            },
            max_filesize: 16
        }, function() {
             window.location.reload();
        });
    });
    
    $(document).on('click','div.page-content.media-files #btn-delete-files',function() {
        var find_id = '';
        $.each($('div.page-content.media-files input[name="media_file_id[]"]:checked'),function() {
            if(find_id!='') {
                find_id += ',';
            }
            find_id += $(this).val()
        });

        if(iweb.isValue(find_id)) {
            iweb.confirm(_page_global_lang['confirm_delete_all'],function(result) {
                if(result) {
                    iweb.post({
                        url: _page_base_url+'/media_files',
                        values: {
                            _token: _token,
                            page_action: 'delete',
                            id: find_id
                        }
                    },function(response_data) {
                        if(iweb.isValue(response_data.status) && iweb.isMatch(response_data.status, 200)) {
                            if(iweb.isValue(response_data.url)) {
                                window.location.href = response_data.url;
                            }
                            else {
                                window.location.reload();
                            }
                        }
                        else {
                            iweb.showTipsMessage(response_data.message);
                        }
                    });
                }
            });
        }
    });
    
    // pick
    $(document).on('click', 'div.page-content.media-files button.pick', function() {
        parent.tinymce.activeEditor.windowManager.getParams().oninsert($(this).data('url'));
        parent.tinymce.activeEditor.windowManager.close();
    });
    
    // delete
    $(document).on('click', 'div.page-content.media-files button.delete', function() {
        var find_id = $(this).data('id');
        iweb.confirm(_page_global_lang['confirm_delete_one'],function(result) {
            if(result) {
                iweb.post({
                    url: _page_base_url+'/media_files',
                    values: {
                        _token: _token,
                        page_action: 'delete',
                        id: find_id
                    }
                },function(response_data) {
                    if(iweb.isValue(response_data.status) && iweb.isMatch(response_data.status, 200)) {
                        if(iweb.isValue(response_data.url)) {
                            window.location.href = response_data.url;
                        }
                        else {
                            window.location.reload();
                        }
                    }
                    else {
                        iweb.showTipsMessage(response_data.message);
                    }
                });
            }
        });
    });
    
    // rotate
    $(document).on('click', 'div.page-content.media-files a.rotate', function() {
        var find_id = $(this).data('id');
        iweb.post({
            url: _page_base_url+'/media_files',
            values: {
                _token: _token,
                page_action: 'rotate',
                id: find_id
            }
        },function() {
            window.location.reload();
        });
    });
}