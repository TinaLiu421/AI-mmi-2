function iweb_self_func() {
    iweb.form('#loginform', 'json', null ,function(response_data) {
        if(iweb.isMatch(response_data.status, 200)) {
            window.location.href = response_data.url;
        }
        else {
            iweb.showTipsMessage(response_data.message);
        }
    });
    
    iweb.form('#forgotform', 'json', null ,function(response_data) {
        if(iweb.isMatch(response_data.status, 200)) {
            $('#forgotform input[name="user_email"]').val('');
        }
        iweb.showTipsMessage(response_data.message, iweb.isMatch(response_data.status, 200));
    });
    
    iweb.form('#resetform', 'json', function() {
        if(!iweb.isMatch($('#resetform input[name="user_password"]').val(), $('#resetform input[name="user_repeat_password"]').val())) {
            $('#resetform input[name="user_password"]').addClass('error');
            $('#resetform input[name="user_repeat_password"]').addClass('error');
            iweb.showTipsMessage(iweb.language[iweb.default_language]['required_error']);
            return false;
        }
        return true;
    }, function(response_data) {
        if(iweb.isMatch(response_data.status, 200)) {
            $('#resetform input[name="user_password"]').val('');
            $('#resetform input[name="user_repeat_password"]').val('');
        }
        iweb.showTipsMessage(response_data.message, iweb.isMatch(response_data.status, 200));
    });
}
