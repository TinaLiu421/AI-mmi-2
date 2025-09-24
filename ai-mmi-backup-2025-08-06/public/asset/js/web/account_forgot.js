function iweb_self_func() {
    iweb.form('#account-forgot-form', 'json', null, function(response_data) {
        if(iweb.isMatch(response_data.status, 200)) {
            iweb.alert(response_data.message, function() {
                window.location.reload();
            });
        }
        else {
            iweb.alert(response_data.message);
        }
    });
}