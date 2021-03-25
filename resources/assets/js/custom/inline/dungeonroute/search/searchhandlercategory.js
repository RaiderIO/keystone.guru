class SearchHandlerCategory extends SearchHandler {
    constructor(category, offset, limit, options) {
        super();

        this.category = category;
        this.offset = offset;
        this.limit = limit;
        this.options = options;
    }


    /**
     *
     * @protected
     */
    getSearchUrl() {
        return `/ajax/search/${this.category}`
    }

    /**
     *
     * @param $targetContainer
     */
    searchMore($targetContainer) {
        let self = this;

        this.search($targetContainer, new SearchParams([], this.offset, this.limit), $.extend({}, {
            success: function (html) {
                // Only if we actually got any results back
                if (html.length > 0) {
                    // Increase the offset so that we load new rows whenever we fetch more
                    self.offset += self.limit;
                }
            }
        }, this.options));
    }
}