class DungeonrouteDiscoverDiscover extends InlineCode {

    /**
     */
    activate() {
        super.activate();
        (new CarouselHandler()).refreshCarousel();

        $('[data-toggle="popover"]').popover();
    }

    cleanup() {
    }
}