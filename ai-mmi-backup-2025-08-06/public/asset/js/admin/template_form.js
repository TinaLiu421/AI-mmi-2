function iweb_extra_func() {
    $(document).on('click', 'div.t-form div.expand-area div.show > a', function() {
        $(this).closest('div.expand-area').find('div.hide').toggle();
        if($(this).closest('div.expand-area').find('div.hide').is(':visible')) {
            $(this).closest('div.expand-area').find('i.fa-plus').css('display','none');
            $(this).closest('div.expand-area').find('i.fa-minus').css('display','inline-block');
        }
        else {
            $(this).closest('div.expand-area').find('i.fa-plus').css('display','inline-block');
            $(this).closest('div.expand-area').find('i.fa-minus').css('display','none');
        }
    });
    
    // seo
    $(document).on('click','div.widget.seo button.btn.upload', function() {
        var object = $(this);
        var files_input = document.createElement('input');
        files_input.type = 'file';
        files_input.onchange = function(){
            var file = files_input.files[0];
            var extension = file.name.slice((file.name.lastIndexOf('.') - 1 >>> 0) + 2);
            if($.inArray(extension.toLowerCase(),['jpg','jpeg','png']) < 0) {
                iweb.alert(iweb.language[iweb.default_language]['type_error']);
            }
            else if(file.size > 2*1024*1024) {
                iweb.alert(iweb.language[iweb.default_language]['max_error'].replace('{num}',2));
            }
            else {
                var local_time = iweb.getDateTime(null,'time');
                var formData = new FormData();
                formData.append('page_action', 'file_upload_meta');
                formData.append('itoken', window.btoa(md5(iweb.csrf_token+'#dt'+local_time)+'%'+local_time));
                formData.append('_token', _token);
                formData.append('myfile', file, file.name);

                iweb.showProcessing(true, 70);
                $.ajax({
                    url: _page_base_url+'/media_files',
                    type: 'post',
                    data: formData,
                    dataType: 'json',
                    processData: false,
                    contentType: false,
                    cache: false,
                    enctype: 'multipart/form-data',
                    success: function(response_data){
                        $('#'+object.data('target')).val(response_data.file_url).trigger('input');
                    },
                    error: function(xhr, status, thrownError){
                        alert(thrownError);
                        return false;
                    },
                    complete: function() {
                        iweb.showProcessing(false);
                    }
                });
            }
        };
        files_input.click();
    });
    
    iweb.form('#myform', 'json', function() {
        var check_result = true;
        if($('input.custom_link_path').length > 0) {
            $.each($('input.custom_link_path'), function() {
                if(iweb.isValue($(this).val())) {
                    var reg = /^[a-zA-Z0-9\-\_\s]+$/; // or /^\w+$/ as mentioned
                    if (!reg.test($(this).val()) || iweb.isMatch($(this).val(), 'admin') || iweb.isMatch($(this).val(), 'web')) {
                        $(this).addClass('error');
                        check_result = false;
                    }
                }
            });
            if(!check_result) {
                iweb.alert($('input.custom_link_path').first().data('error'));
            }
        }
        
        if (typeof myform_extra_checkfunc == 'function') {
            check_result = myform_extra_checkfunc();
        }
        
        return check_result;
    } ,function(response_data) {
        if(iweb.isMatch(response_data.status, 200)) {
            if(iweb.isValue(response_data.url)) {
                window.location.href = response_data.url;
            }
            else {
                window.location.reload();
            }
        }
        else {
            $('div.page-message').html('<div class="error"><a class="close">×</a><span>'+response_data.message+'</span></div>').each(function() {
                iweb.scrollto();
            });
        }
    });
    
    if($('div.t-form div.selfile div.list').length > 0) {
        $(document).on('click','div.t-form button.btn-delete-files',function() {
            var object = $(this);
            var find_id = '';
            $.each($(this).closest('div.widget.selfile').find('input[name="media_file_id[]"]:checked'),function() {
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
                        },function() {
                            loadFiles(object.closest('div.selfile').find('div.list'));
                        });
                    }
                });
            }
        });

        $(document).on('click','div.t-form button.btn-select-files',function() {
            var object = $(this);
            iweb.uploader({
                url: object.data('url'),
                values: {
                    _token: _token,
                    related_token: object.data('token'),
                    related_type: object.data('type'),
                    related_category: object.data('category')
                },
                max_filesize: object.data('max'),
                allowed_types: object.data('allowed')
            }, function() {
                loadFiles(object.closest('div.selfile').find('div.list'));
            });
        });
        
        // edit
        $(document).on('click','div.t-form div.selfile button.edit',function() {
            iweb.post({
                url: _page_base_url+'/media_files/attribute',
                values: {
                    _token: _token,
                    media_file_id: $(this).data('id')
                },
                showProcessing: false
            }, function(response_data) {
                var label = {
                    title: _page_global_lang.title,
                    sub_title: _page_global_lang.sub_title,
                    content: _page_global_lang.content,
                    use_crop: _page_global_lang.use_crop,
                    use_crop_no: _page_global_lang.use_crop_no,
                    use_crop_yes: _page_global_lang.use_crop_yes,
                    url: _page_global_lang.url,
                    extra_1: _page_global_lang.extra_1,
                    extra_2: _page_global_lang.extra_2,
                    btn_change: _page_global_lang.revised_coordinates,
                    btn_save: _page_global_lang.save,
                };

                var file_is_image = parseInt(response_data.data.is_image);

                var coordinate_start_x = 0;
                var coordinate_start_y = 0;
                var coordinate_end_x = 0;
                var coordinate_end_y = 0;

                var input_use_crop = 0;
                var input_title = '';
                var input_sub_title = '';
                var input_content = '';
                var input_url = '';
                var input_extra_1 = '';
                var input_extra_2 = '';

                if(iweb.isValue(response_data.data.file_attribute)) {
                    if(file_is_image == 1) {
                        coordinate_start_x = parseInt(response_data.data.file_attribute.x);
                        coordinate_start_y = parseInt(response_data.data.file_attribute.y);
                        coordinate_end_x = parseInt(response_data.data.file_attribute.width);
                        coordinate_end_y = parseInt(response_data.data.file_attribute.height);
                        input_use_crop = parseInt(response_data.data.file_attribute.use_crop);
                    }

                    input_title = response_data.data.file_attribute.title;
                    input_sub_title = response_data.data.file_attribute.sub_title;
                    input_content = response_data.data.file_attribute.content;
                    input_url = response_data.data.file_attribute.url;
                    input_extra_1 = response_data.data.file_attribute.extra_1;
                    input_extra_2 = response_data.data.file_attribute.extra_2;
                }
                else {
                    if(file_is_image == 1) {
                        coordinate_end_x = response_data.data.image_width;
                        coordinate_end_y = response_data.data.image_height;
                    }
                }

                var imageinfo_html = '<div>';
                    imageinfo_html += '<form id="imageinfo-form" name="imageinfo-form" method="post" action="'+_page_base_url+'/media_files/attribute'+'">';
                        imageinfo_html += '<div><input type="hidden" name="_token" value="'+(_token)+'"/></div>';
                        imageinfo_html += '<div><input type="hidden" name="page_action" value="save_info"/></div>';
                        imageinfo_html += '<div><input type="hidden" name="media_file_id" value="'+response_data.data.id+'"/></div>';

                        if(file_is_image == 1) {
                            imageinfo_html += '<div class="photo">';
                                imageinfo_html += '<image src="'+(response_data.data.file_path)+'" id="croper"/>';
                            imageinfo_html += '</div>';

                            imageinfo_html += '<div class="coordinate">';
                                imageinfo_html += '<div class="row"><span>X:</span><input type="text" id="coordinate_start_x" name="x" value="'+coordinate_start_x+'"/></div>';
                                imageinfo_html += '<div class="row"><span>Y:</span><input type="text" id="coordinate_start_y" name="y"  value="'+coordinate_start_y+'"/></div>';
                                imageinfo_html += '<div class="row"><span>W:</span><input type="text" id="coordinate_end_x" name="width" value="'+coordinate_end_x+'"/></div>';
                                imageinfo_html += '<div class="row"><span>H:</span><input type="text" id="coordinate_end_y" name="height" value="'+coordinate_end_y+'"/></div>';
                                imageinfo_html += '<div class="row"><button type="button" class="btn" id="btn_revised_coordinates"><i class="fa fa-crop"></i>&nbsp;<span>'+label.btn_change+'</span></button></div>';
                            imageinfo_html += '</div>';
                        }
                        else {
                            //imageinfo_html += '<div style="text-align:center;text-transform:uppercase;"><strong>'+response_data.data.title+'</strong></div>';
                        }

                        if(file_is_image == 1) {
                            imageinfo_html += '<div class="attribute">';
                        }
                        else {
                            imageinfo_html += '<div class="attribute" style="margin-top:0px;padding-top:0px;border-top:0px;">';
                        }
                            if(file_is_image == 1) {
                                imageinfo_html += '<div class="row"><label for="use_crop">'+label.use_crop+':</label><select id="use_crop" name="use_crop">';
                                imageinfo_html += '<option value="0"'+((input_use_crop == 0)?' selected':'')+'>'+label.use_crop_no+'</option>';
                                imageinfo_html += '<option value="1"'+((input_use_crop == 1)?' selected':'')+'>'+label.use_crop_yes+'</option>';
                                imageinfo_html += '</select></div>';
                            }

                            imageinfo_html += '<div class="row"><label for="title">'+label.title+':</label><input type="text" id="title" name="title" value="'+input_title+'"/></div>';
                            imageinfo_html += '<div class="row"><label for="title">'+label.sub_title+':</label><input type="text" id="sub_title" name="sub_title" value="'+input_sub_title+'"/></div>';
                            imageinfo_html += '<div class="row"><label for="content">'+label.content+':</label><textarea id="content" name="content" rows="3">'+input_content+'</textarea></div>';
                            imageinfo_html += '<div class="row"><label for="url">'+label.url+':</label><input type="text" id="url" name="url" value="'+input_url+'"/></div>';
                            imageinfo_html += '<div class="row"><label for="extra_1">'+label.extra_1+':</label><input type="text" id="extra_1" name="extra_1" value="'+input_extra_1+'"/></div>';
                            imageinfo_html += '<div class="row"><label for="extra_2">'+label.extra_2+':</label><input type="text" id="extra_2" name="extra_2" value="'+input_extra_2+'"/></div>';
                            imageinfo_html += '<div class="row" style="text-align:right;"><button type="submit" class="btn btn-green" id="btn_save_imageinfo"><i class="fa fa-save"></i>&nbsp;<span>'+label.btn_save+'</span></button></div>';
                        imageinfo_html += '</div>';
                    imageinfo_html += '</form>';
                imageinfo_html += '</div>';

                iweb.dialog(imageinfo_html, function() {
                    $('#croper').rcrop({
                        full : true,
                        grid : true
                    }).on('rcrop-ready', function(){
                        $(this).rcrop(
                            'resize', 
                            Math.max(0, parseInt($('#coordinate_end_x').val())),
                            Math.max(0, parseInt($('#coordinate_end_y').val())),
                            Math.max(0, parseInt($('#coordinate_start_x').val())),
                            Math.max(0, parseInt($('#coordinate_start_y').val()))
                        );
                    }).on('rcrop-changed', function(){
                        var coordinate = $(this).rcrop('getValues');
                        $('#coordinate_end_x').val(coordinate.width);
                        $('#coordinate_end_y').val(coordinate.height);
                        $('#coordinate_start_x').val(coordinate.x);
                        $('#coordinate_start_y').val(coordinate.y);
                    });

                    $(document).off('click', '#btn_revised_coordinates');
                    $(document).on('click', '#btn_revised_coordinates', function() {
                        $('#croper').rcrop(
                            'resize', 
                            Math.max(0, parseInt($('#coordinate_end_x').val())),
                            Math.max(0, parseInt($('#coordinate_end_y').val())),
                            Math.max(0, parseInt($('#coordinate_start_x').val())),
                            Math.max(0, parseInt($('#coordinate_start_y').val()))
                        );
                    });

                    iweb.form('#imageinfo-form','text', null, function() {
                        $('div.iweb-info-dialog > div > div.dialog-content > a.btn-close').trigger('click');
                    });

                }, null, 'imageinfo');
            });
        });

        // delete
        $(document).on('click','div.t-form div.selfile button.delete', function() {
            var object = $(this);
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
                        if(iweb.isMatch(response_data.status, 200)) {
                            loadFiles(object.closest('div.list'));
                        }
                        else {
                            iweb.alert(response_data.message);
                        }
                    });
                }
            });
        });

        // download
        $(document).on('click','div.t-form div.selfile button.download', function() {
            window.open($(this).data('url'), '_blank');
        });

        // switch seq
        $(document).on('change','div.widget.selfile div.list select.seq_number', function() {
            var object = $(this);
            var media_file_id = $(this).data('id');
            var from_seq = parseInt($(this).data('offset'));
            var to_seq = parseInt($(this).val());
            
            if(from_seq != to_seq) {
                iweb.post({
                    url: _page_base_url+'/media_files',
                    values: {
                        _token: _token,
                        page_action: 'seq',
                        id: media_file_id,
                        from_seq: from_seq,
                        to_seq: to_seq
                    }
                },function() {
                    loadFiles(object.closest('div.list'));
                });
            }
        });
        
        // rotate
        $(document).on('click', 'div.t-form div.widget.selfile a.rotate', function() {
            var object = $(this);
            var find_id = $(this).data('id');
            iweb.post({
                url: _page_base_url+'/media_files',
                values: {
                    _token: _token,
                    page_action: 'rotate',
                    id: find_id
                }
            },function() {
                loadFiles(object.closest('div.list'));
            });
        });

        loadFiles();
    }
}

function loadFiles(list_object) {
    if(iweb.isValue(list_object)) {
        iweb.post({
            url: _page_base_url+'/media_files/ajaxlist',
            values: {
                _token: _token,
                related_token: list_object.data('token'),
                related_type: list_object.data('type'),
                related_category: list_object.data('category')
            },
            dataType: 'html',
            showProcessing: false
        },function(response_data) {
            list_object.html(response_data);
        });
    }
    else {
        $('div.t-form div.selfile div.list').each(function() {
            var object = $(this);
            iweb.post({
                url: _page_base_url+'/media_files/ajaxlist',
                values: {
                    _token: _token,
                    related_token: object.data('token'),
                    related_type: object.data('type'),
                    related_category: object.data('category')
                },
                dataType: 'html',
                showProcessing: false
            },function(response_data) {
                object.html(response_data);
            });
        });
    }
}