function iweb_self_func() {
    iweb.form('#memberform', 'json', function() {
        if(iweb.isValue($('#memberform input[name="password"]').val()) || iweb.isValue($('#memberform input[name="repeat_password"]').val())) {
            if(!iweb.isMatch($('#memberform input[name="password"]').val(), $('#memberform input[name="repeat_password"]').val())) {
                $('#memberform input[name="password"]').addClass('error');
                $('#memberform input[name="repeat_password"]').addClass('error');
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
}