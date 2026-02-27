class HomeLayout extends InlineCode {
    /**
     *
     */
    activate() {
        super.activate();

        console.log('HomeLayout activated');


        (new CarouselHandler()).refreshCarousel();
        (new ThumbnailRefresh()).refreshHandlers();

        $('[data-toggle="popover"]').popover();
    }
}
