function iweb_self_func() {
    $(document).on('click', 'a.remove-forum-post', function() {
        var find_ids = $(this).data('id');
        iweb.confirm(_page_global_lang['confirm_delete_all'],function(result) {
            if(result) {
                 iweb.post({
                    url: window.location.href,
                    values: {
                        _token: _token,
                        page_action: 'delete_sub',
                        id: find_ids
                    }
                },function() {
                    window.location.reload();
                });
            }
        });
    });
    
    
}