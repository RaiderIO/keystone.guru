class DungeonrouteDiscoverSearchloadmore extends InlineCode {

    activate() {
        let self = this;

        this.searchHandler = new SearchHandlerDungeonRouteCategory(
            this.options.routeContainerListSelector,
            this.options.routeLoadMoreSelector,
            this.options.category, {
                routeLoaderSelector: this.options.routeLoaderSelector,
                // @TODO is this true??
                offset: 20,
                limit: this.options.loadMoreCount,
                expansion: this.options.expansion,
                dungeon: this.options.dungeon,
                beforeSend: function () {
                    $(self.options.routeLoaderSelector).show();
                },
                complete: function () {
                    $(self.options.routeLoaderSelector).hide();
                }
            });
    }
}
