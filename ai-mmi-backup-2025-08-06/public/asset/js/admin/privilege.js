function iweb_self_func() {
    $(document).on('change', '#roleform input[type="checkbox"][value!="101"].role_allowed', function() {
        if($(this).is(':checked')) {
            $(this).closest('td').find('input[type="checkbox"][value="101"].role_allowed').prop('checked', false).trigger('click');
        }
    });
    
    $(document).on('change', '#roleform input[type="checkbox"][value="101"].role_allowed', function() {
        if(!$(this).is(':checked')) {
            $(this).closest('td').find('input[type="checkbox"][value!="101"].role_allowed').prop('checked', true).trigger('click');
        }
    });
    
    iweb.form('#userform', 'json', function() {
        if(iweb.isValue($('#userform input[name="user_password"]').val()) || iweb.isValue($('#userform input[name="user_repeat_password"]').val())) {
            if(!iweb.isMatch($('#userform input[name="user_password"]').val(), $('#userform input[name="user_repeat_password"]').val())) {
                $('#userform input[name="user_password"]').addClass('error');
                $('#userform input[name="user_repeat_password"]').addClass('error');
                iweb.showTipsMessage(iweb.language[iweb.default_language]['required_error']);
                return false;
            }
        }
        return true;
    }, function(response_data) {
        if(iweb.isValue(response_data.status) && iweb.isMatch(response_data.status, 200)) {
            window.location.href = response_data.url;
        }
        else {
            iweb.showTipsMessage(response_data.message);
        }
    });

    iweb.form('#roleform','json', null ,function(response_data) {
        if(iweb.isValue(response_data.status) && iweb.isMatch(response_data.status, 200)) {
            window.location.href = response_data.url;
        }
        else {
            iweb.showTipsMessage(response_data.message);
        }
    });
}