class DungeonrouteDiscoverDiscover extends InlineCode {

    /**
     */
    activate() {
        super.activate();


        (new CarouselHandler()).refreshCarousel();

        $('[data-toggle="popover"]').popover();

        if (this.options.hasOwnProperty('loadMore') && this.options.loadMore) {
            let self = this;

            this.loading = false;
            this.searchHandler = new SearchHandlerCategory('popular', 20, {
                beforeSend: function () {
                    self.loading = true;
                    $('#category_route_load_more_loader').show();
                },
                complete: function () {
                    self.loading = false;
                    $('#category_route_load_more_loader').hide();
                }
            });
            this.$loadMore = $('#category_route_load_more');

            $(window).on('resize scroll', function () {
                let inViewport = self.$loadMore.isInViewport();
                if (!self.loading && inViewport) {
                    self.searchHandler.searchMore($('#category_route_list'));
                }
            });
        }
    }

    cleanup() {
    }
}