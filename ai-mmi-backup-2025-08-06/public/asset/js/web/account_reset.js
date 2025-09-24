function iweb_self_func() {
    iweb.form('#account-reset-form', 'json', function() {
        if(iweb.isValue($('#account-reset-form input[name="password"]').val()) || iweb.isValue($('#account-reset-form input[name="repeat_password"]').val())) {
            if(!iweb.isMatch($('#account-reset-form input[name="password"]').val(), $('#account-reset-form input[name="repeat_password"]').val())) {
                $('#account-reset-form input[name="password"]').addClass('error');
                $('#account-reset-form input[name="repeat_password"]').addClass('error');
                return false;
            }
        }
        return true;
    } ,function(response_data) {
        if(iweb.isMatch(response_data.status, 200)) {
            iweb.alert(response_data.message, function() {
                window.location.href = response_data.url;
            });
        }
        else {
            iweb.alert(response_data.message);
        }
    });
}