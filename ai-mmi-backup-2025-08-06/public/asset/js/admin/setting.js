function iweb_self_func() {
    $(document).on('click','div.page-content.setting-general button.btn.upload', function() {
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
    
    iweb.form('#generalform','json', null, function(response_data) {
        if(iweb.isValue(response_data.status) && iweb.isMatch(response_data.status, 200)) {
            window.location.href = response_data.url;
        }
        else {
            iweb.showTipsMessage(response_data.message);
        }
    });
    
    iweb.form('#whitelistform','json', null, function(response_data) {
        if(iweb.isValue(response_data.status) && iweb.isMatch(response_data.status, 200)) {
            window.location.href = response_data.url;
        }
        else {
            iweb.showTipsMessage(response_data.message);
        }
    });
}