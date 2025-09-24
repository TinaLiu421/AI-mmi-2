function iweb_self_func() {
    iweb.form('#account-login-form', 'json', null, function(response_data) {
        if(iweb.isMatch(response_data.status, 200)) {
            window.location.href = response_data.url;
        }
        else {
            iweb.alert(response_data.message);
        }
    });
}