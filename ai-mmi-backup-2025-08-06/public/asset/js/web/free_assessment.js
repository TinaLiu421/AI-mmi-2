function iweb_self_func() {
    iweb.form('#questions-form', 'json', null, function(response_data) {
        if(iweb.isMatch(response_data.status, 200)) {
            iweb.alert(response_data.message, function() {
                window.location.reload();
            });
        }
        else {
            iweb.alert(response_data.message);
        }
    });
    
    $(document).on('click', '#answers_1_1', function() {
        if($(this).is(':checked')) {
            $('div.questions div.next').addClass('show');
        }
        else {
            $('div.questions div.next').removeClass('show');
        }
    });
    
    $(document).on('click', '#answers_1_2', function() {
        if(!$(this).is(':checked')) {
            $('div.questions div.next').addClass('show');
        }
        else {
            $('div.questions div.next').removeClass('show');
        }
    });
}