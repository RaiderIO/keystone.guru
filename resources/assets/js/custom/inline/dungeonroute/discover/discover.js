class DungeonrouteDiscoverDiscover extends InlineCode {

    activate() {
        super.activate();
        (new CarouselHandler()).refreshCarousel();
        (new ThumbnailRefresh()).refreshHandlers();

        document.querySelectorAll('[data-bs-toggle="popover"]').forEach((el) => bootstrap.Popover.getOrCreateInstance(el));
    }

    cleanup() {
    }
}
