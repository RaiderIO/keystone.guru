class CarouselHandler {
    constructor() {

    }

    refreshCarousel(prefix = '') {
        let baseSettings = {
            nav: false,
            dots: true,
            lazyLoad: true,
            lazyLoadEager: 1,
            items: 1,
        };

        $(`${prefix} .owl-carousel.single`).owlCarousel(baseSettings);
        $(`${prefix} .owl-carousel.multiple`).owlCarousel($.extend({}, baseSettings, {loop: true}));
    }
}