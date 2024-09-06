class SearchHandlerDungeonRouteCategory extends SearchHandlerDungeonRoute {
    constructor(targetContainerSelector, loadMoreSelector, category, options) {
        super(targetContainerSelector, loadMoreSelector, options);

        this.category = category;
    }

    /**
     *
     * @protected
     */
    getSearchUrl() {
        return `/ajax/search/${this.category}`;
    }
}
