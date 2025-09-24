function iweb_self_func() {
    $(document).on('click', 'div.page-content.faqs div.list > div.block > a', function() {
        if($(this).closest('div.block').find('div.content').is(':visible')) {
            $(this).closest('div.block').find('div.content').slideUp();
            $(this).closest('div.block').removeClass('show');
        }
        else {
            $(this).closest('div.block').find('div.content').slideDown();
            $(this).closest('div.block').addClass('show');
        }
    });
     $('div.page-content.faqs div.list > div.block').eq(0).addClass('show');
    $('div.page-content.faqs div.list > div.block').eq(0).find('div.content').css('display', 'block');
}