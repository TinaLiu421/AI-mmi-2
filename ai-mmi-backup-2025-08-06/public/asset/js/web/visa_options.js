var item_slider = [];
function iweb_self_func() {
    $(document).on('change', 'select.switch-country', function() {
        window.location.href = _page_base_url+'/visa_options?country='+$(this).val();
    });
    
    if($('div.item-slider').length > 0) {
        $.each($('div.item-slider'), function(key, value) {
            item_slider[key+1] = $(this).slick({
                dots: false,
                arrows: false,
                infinite: true,
                autoplay: false
            });
        });
 
        $(document).on('click', 'a.prev-item-set', function() {
            item_slider[$(this).data('index')].slick('slickPrev');
        });
        
        $(document).on('click', 'a.next-item-set', function() {
            item_slider[$(this).data('index')].slick('slickNext');
        });
    }
    
    setBlockHeight();
}

function iweb_self_layout() {
    setBlockHeight();
}

function iweb_self_layout_done() {
    setBlockHeight();
}

function setBlockHeight() {
    var max_height = 0;
    if($('div.page-content.visa-options div.list div.block > div').length > 0) {
        $('div.page-content.visa-options div.list div.block > div').css('height', 'auto');
        $('div.page-content.visa-options div.list div.block > div').each(function() {
            max_height = Math.max(max_height, $(this).outerHeight());
        });
        if(iweb.win_width >= 683) {
            $('div.page-content.visa-options div.list div.block > div').css('height', max_height);
        }
    }
}
