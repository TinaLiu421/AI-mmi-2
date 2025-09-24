function iweb_self_func() {
    // individual
    iweb.form('#account-individual-form', 'json', function() {
        if(iweb.isValue($('#account-individual-form input[name="password"]').val()) || iweb.isValue($('#account-individual-form input[name="repeat_password"]').val())) {
            if(!iweb.isMatch($('#account-individual-form input[name="password"]').val(), $('#account-individual-form input[name="repeat_password"]').val())) {
                $('#account-individual-form input[name="password"]').addClass('error');
                $('#account-individual-form input[name="repeat_password"]').addClass('error');
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
    
    iweb.form('#account-individual-preference-form', 'json', null, function(response_data) {
        if(iweb.isMatch(response_data.status, 200)) {
            iweb.alert(response_data.message, function() {
                window.location.href = response_data.url;
            });
        }
        else {
            iweb.alert(response_data.message);
        }
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
    
    // agent
    iweb.form('#account-agent-form', 'json', function() {
        if(iweb.isValue($('#account-agent-form input[name="password"]').val()) || iweb.isValue($('#account-agent-form input[name="repeat_password"]').val())) {
            if(!iweb.isMatch($('#account-agent-form input[name="password"]').val(), $('#account-agent-form input[name="repeat_password"]').val())) {
                $('#account-agent-form input[name="password"]').addClass('error');
                $('#account-agent-form input[name="repeat_password"]').addClass('error');
                iweb.showTipsMessage(iweb.language[iweb.default_language]['required_error']);
                return false;
            }
        }
        return true;
    } ,function(response_data) {
        if(iweb.isMatch(response_data.status, 200)) {
            if(iweb.isValue(response_data.message)) {
                iweb.alert(response_data.message, function() {
                    window.location.href = response_data.url;
                });
            }
            else {
                window.location.href = response_data.url;
            }
        }
        else {
            iweb.alert(response_data.message);
        }
    });
    
    iweb.form('#account-agent-payment-form', 'json', null, function(response_data) {
        if(iweb.isMatch(response_data.status, 200)) {
            window.location.href = response_data.url;
        }
        else {
            iweb.alert(response_data.message);
        }
    });
    
    // service-provider
    iweb.form('#account-service-provider-form', 'json', function() {
        if(iweb.isValue($('#account-service-provider-form input[name="password"]').val()) || iweb.isValue($('#account-service-provider-form input[name="repeat_password"]').val())) {
            if(!iweb.isMatch($('#account-service-provider-form input[name="password"]').val(), $('#account-service-provider-form input[name="repeat_password"]').val())) {
                $('#account-service-provider-form input[name="password"]').addClass('error');
                $('#account-service-provider-form input[name="repeat_password"]').addClass('error');
                iweb.showTipsMessage(iweb.language[iweb.default_language]['required_error']);
                return false;
            }
        }
        return true;
    } ,function(response_data) {
        if(iweb.isMatch(response_data.status, 200)) {
            if(iweb.isValue(response_data.message)) {
                iweb.alert(response_data.message, function() {
                    window.location.href = response_data.url;
                });
            }
            else {
                window.location.href = response_data.url;
            }
        }
        else {
            iweb.alert(response_data.message);
        }
    });
    
    iweb.form('#account-service-provider-payment-form', 'json', null, function(response_data) {
        if(iweb.isMatch(response_data.status, 200)) {
            window.location.href = response_data.url;
        }
        else {
            iweb.alert(response_data.message);
        }
    });
}

function iweb_self_layout() {
    setPlanHeight();
}

function iweb_self_layout_done() {
    setPlanHeight();
}

function setPlanHeight() {
    var max_height = 0;
    if($('div.page-content.account-registration div.list > div.block.plan').length > 0) {
        $('div.page-content.account-registration div.list > div.block.plan').css('height', 'auto');
        $('div.page-content.account-registration div.list > div.block.plan').each(function() {
            max_height = Math.max(max_height, $(this).outerHeight());
        });
        if(iweb.win_width >= 1624) {
            $('div.page-content.account-registration div.list > div.block.plan').css('height', max_height);
        }
    }
}