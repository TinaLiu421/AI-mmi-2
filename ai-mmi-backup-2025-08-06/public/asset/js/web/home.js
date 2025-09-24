function iweb_self_func() {
    if($('#hslider-news').length > 0) {
        $('#hslider-news').slick({
            dots: false,
            arrows: true,
            infinite: true,
            autoplay: false,
            slidesToShow: 5,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 1200,
                    settings: {
                        slidesToShow: 4
                    }
                },
                {
                    breakpoint: 900,
                    settings: {
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 600,
                    settings: {
                        slidesToShow: 2
                    }
                }
            ]
        });
    }
    
    if($('#hslider-events').length > 0) {
        $('#hslider-events').slick({
            dots: false,
            arrows: true,
            infinite: true,
            autoplay: false,
            slidesToShow: 5,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 1200,
                    settings: {
                        slidesToShow: 4
                    }
                },
                {
                    breakpoint: 900,
                    settings: {
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 600,
                    settings: {
                        slidesToShow: 2
                    }
                }
            ]
        });
    }
}