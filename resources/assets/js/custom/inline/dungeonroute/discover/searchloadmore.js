class DungeonrouteDiscoverSearchloadmore extends InlineCode {

    activate() {
        let self = this;

        this.searchHandler = new SearchHandlerDungeonRouteCategory(
            this.options.routeContainerListSelector,
            this.options.routeLoadMoreSelector,
            this.options.category, {
                loaderSelector: this.options.loaderSelector,
                // @TODO is this true??
                offset: 20,
                limit: this.options.loadMoreCount,
                // @TODO This is not used anywhere?
                gameVersion: this.options.gameVersion,
                dungeon: this.options.dungeon,
                beforeSend: function () {
                    $(self.options.loaderSelector).show();
                },
                complete: function () {
                    $(self.options.loaderSelector).hide();
                }
            });
    }
}
