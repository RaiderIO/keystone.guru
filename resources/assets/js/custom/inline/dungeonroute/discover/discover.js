class DungeonrouteDiscoverDiscover extends InlineCode {

    /**
     */
    activate() {
        super.activate();

        $('.owl-carousel').owlCarousel({
            // True to enable overlayed buttons (custom styled, wasted time :( )
            nav: false,
            loop: true,
            dots: false,
            lazyLoad: true,
            lazyLoadEager: 1,
            items: 1
        });
    }

    cleanup() {
    }
}