/**
 * @typedef {Object} DungeonrouteDiscoverSearchloadmoreOptions
 * @property {string} routeContainerListSelector
 * @property {string} routeLoadMoreSelector
 * @property {string} loaderSelector
 * @property {string} category
 * @property {number} loadMoreOffset
 * @property {number} loadMoreCount
 * @property {Object} gameVersion
 * @property {Object} dungeon
 */

/**
 * @property {DungeonrouteDiscoverSearchloadmoreOptions} options
 */
class DungeonrouteDiscoverSearchloadmore extends InlineCode {

    activate() {
        let self = this;

        this.searchHandler = new SearchHandlerDungeonRouteCategory(
            this.options.routeContainerListSelector,
            this.options.routeLoadMoreSelector,
            this.options.category, {
                loaderSelector: this.options.loaderSelector,
                offset: this.options.loadMoreOffset,
                limit: this.options.loadMoreCount,
                data: {
                    test: 'test',
                    gameVersion: this.options.gameVersion.id,
                    dungeon: this.options.dungeon.id,
                },
                beforeSend: function () {
                    $(self.options.loaderSelector).show();
                },
                complete: function () {
                    $(self.options.loaderSelector).hide();
                }
            });
    }
}
