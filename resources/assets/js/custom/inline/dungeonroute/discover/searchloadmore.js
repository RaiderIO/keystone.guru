class DungeonrouteDiscoverSearchloadmore extends InlineCode {

    activate() {
        let self = this;

        this.loading = false;

        this.searchHandler = new SearchHandlerCategory(this.options.category, 20, this.options.loadMoreCount, {
            beforeSend: function () {
                self.loading = true;
                $(self.options.routeLoaderSelector).show();
            },
            complete: function () {
                self.loading = false;
                $(self.options.routeLoaderSelector).hide();
            }
        });

        this.$loadMore = $(this.options.routeLoadMoreSelector);

        $(window).on('resize scroll', function () {
            let inViewport = self.$loadMore.isInViewport();
            if (!self.loading && inViewport) {
                self.searchHandler.searchMore($(self.options.routeContainerListSelector));
            }
        });
    }
}