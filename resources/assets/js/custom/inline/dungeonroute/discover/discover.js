class DungeonrouteDiscoverDiscover extends InlineCode {

    /**
     */
    activate() {
        super.activate();
        (new CarouselHandler()).refreshCarousel();
        (new ThumbnailRefresh()).refreshHandlers();

        $('[data-toggle="popover"]').popover();
    }

    cleanup() {
    }
}