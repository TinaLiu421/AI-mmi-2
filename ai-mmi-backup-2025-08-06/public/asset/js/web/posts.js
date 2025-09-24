function iweb_self_func_done() {
    setTimeout(function() {
        var object = $('a.do-comment');
        var posts_id = parseInt(object.data('id'));
        $.get(_page_base_url+'/account_article/comment/'+posts_id, function(html) {
            object.closest('div.post').find('div.leavecomment').show();
            object.closest('div.post').find('div.reply').html(html);
        });
    }, 500);
}