class CarouselHandler {
    constructor() {

    }

    refreshCarousel(prefix = '') {

        // Only perform this when the page is actually fully loaded - otherwise space calculations go wrong
        // and owl carousel completely flips and breaks pages.
        $(function(){
            let baseSettings = {
                nav: false,
                dots: false,
                lazyLoad: true,
                lazyLoadEager: 1,
                items: 1,
            };

            $(`${prefix} .owl-carousel.single`).owlCarousel(baseSettings);
            $(`${prefix} .owl-carousel.multiple`).owlCarousel($.extend({}, baseSettings, {loop: true}));
        });
    }
}