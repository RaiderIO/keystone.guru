class DungeonrouteDiscoverDiscover extends InlineCode {

    /**
     */
    activate() {
        super.activate();

        let baseSettings = {
            nav: false,
            dots: true,
            lazyLoad: true,
            lazyLoadEager: 1,
            items: 1,
        };

        $('.owl-carousel.single').owlCarousel(baseSettings);
        $('.owl-carousel.multiple').owlCarousel($.extend({}, baseSettings, {loop: true}));

        $('[data-toggle="popover"]').popover();
    }

    cleanup() {
    }
}