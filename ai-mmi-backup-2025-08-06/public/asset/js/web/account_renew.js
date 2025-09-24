function iweb_self_func() {
    iweb.form('#account-renew-payment-form', 'json', null, function(response_data) {
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
    var max_height_text_1 = 0;
    if($('div.page-content.account-renew div.list > div.block.plan').length > 0) {
        $('div.page-content.account-renew div.list > div.block div.txt-1').css('height', 'auto');
        $('div.page-content.account-renew div.list > div.block div.txt-1').each(function() {
            max_height_text_1 = Math.max(max_height_text_1, $(this).outerHeight());
        });
        if(iweb.win_width >= 1624) {
            $('div.page-content.account-renew div.list > div.block div.txt-1').css('height', max_height_text_1);
        }
        
        $('div.page-content.account-renew div.list > div.block.plan').css('height', 'auto');
        $('div.page-content.account-renew div.list > div.block.plan').each(function() {
            max_height = Math.max(max_height, $(this).outerHeight());
        });
        if(iweb.win_width >= 1624) {
            $('div.page-content.account-renew div.list > div.block.plan').css('height', max_height);
        }
    }
}