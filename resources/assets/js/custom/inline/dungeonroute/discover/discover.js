class DungeonrouteDiscoverDiscover extends InlineCode {

    /**
     */
    activate() {
        super.activate();

        // let baseSettings = {
        //     nav: false,
        //     dots: false,
        //     lazyLoad: true,
        //     lazyLoadEager: 1,
        //     items: 1,
        //     autoWidth: true,
        // };
        //
        // $('.owl-carousel.multiple').owlCarousel($.extend({}, baseSettings, {loop: true}));
        // $('.owl-carousel.single').owlCarousel(baseSettings);

        $('[data-toggle="popover"]').popover();
    }

    cleanup() {
    }
}