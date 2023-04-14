class CarouselHandler {
    constructor() {

    }

    refreshCarousel(prefix = '') {

        // Only perform this when the page is actually fully loaded - otherwise space calculations go wrong
        // and owl carousel completely flips and breaks pages.
        $(function () {
            let baseSettings = {
                item: 1,
                // autoWidth: true,
                // slideMove: 1, // slidemove will be 1 if loop is true
                slideMargin: 0,

                // addClass: '',
                // mode: 'slide',
                // useCSS: true,
                // cssEasing: 'ease', //'cubic-bezier(0.25, 0, 0.25, 1)',//
                // easing: 'linear', //'for jquery animation',////
                //
                // speed: 400, //ms'
                // auto: false,
                // pauseOnHover: false,
                loop: true,
                // slideEndAnimation: true,
                // pause: 2000,
                //
                // keyPress: false,
                controls: false,
                // prevHtml: '',
                // nextHtml: '',
                //
                // rtl: false,
                // adaptiveHeight: false,
                //
                // vertical: false,
                // verticalHeight: 500,
                // vThumbWidth: 100,
                //
                // thumbItem: 10,
                pager: false,
                gallery: false,
                // galleryMargin: 5,
                // thumbMargin: 5,
                // currentPagerPosition: 'middle',
                //
                // enableTouch: true,
                // enableDrag: true,
                // freeMove: true,
                // swipeThreshold: 40,
                //
                // responsive: [],
                //
                // onBeforeStart: function (el) {
                // },
                // onSliderLoad: function (el) {
                // },
                // onBeforeSlide: function (el) {
                // },
                // onAfterSlide: function (el) {
                // },
                // onBeforeNextSlide: function (el) {
                // },
                // onBeforePrevSlide: function (el) {
                // }

                //LAZY LOADING
                onBeforeStart: function (slider) {
                    for (var i = 1; i <= 2; i++) {
                        var img = slider.find('li:nth-child(' + i + ') img');
                        img.attr('src', img.attr('data-src'));
                    }
                },
                onSliderLoad: function (slider) {
                    $('.aboutSliderHidden').removeClass('aboutSliderHidden');
                    slider.parent().find('.lSPrev').hide();
                },
                onAfterSlide: function (slider, scene) {
                    for (var i = 0; i <= 1; i++) {
                        var img = slider.find('img').eq(slider.getCurrentSlideCount() + i);
                        img.attr('src', img.attr('data-src'));
                    }

                    var lsPrev = slider.parent().find('.lSPrev');
                    var lsNext = slider.parent().find('.lSNext');

                    if (slider.getCurrentSlideCount() === 1) {
                        lsPrev.hide();
                    }
                    else if (slider.getCurrentSlideCount() === slider.find('li').length) {
                        lsNext.hide();
                    }
                    else {
                        lsPrev.show();
                        lsNext.show();
                    }
                }
            };

            // $(`${prefix} .light-slider.single`).each(function(item){
            //     $(this).lightSlider(baseSettings);
            // });
            // $(`${prefix} .light-slider.multiple`).each(function(item){
            //     $(this).lightSlider($.extend({}, baseSettings, {loop: true}));
            // });
            $(`${prefix} .light-slider.single`).lightSlider(baseSettings);
            $(`${prefix} .light-slider.multiple`).lightSlider($.extend({}, baseSettings, {loop: true}));
        });
    }
}
