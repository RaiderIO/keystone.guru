class DungeonrouteDiscoverDiscover extends InlineCode {

    activate() {
        super.activate();
        (new CarouselHandler()).refreshCarousel();
        (new ThumbnailRefresh()).refreshHandlers();

        $('[data-bs-toggle="popover"]').popover();
    }

    cleanup() {
    }
}
