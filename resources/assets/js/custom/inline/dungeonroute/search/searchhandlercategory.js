class SearchHandlerCategory extends SearchHandler {
    constructor(category, offset, options) {
        super();

        this.category = category;
        this.offset = offset;
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
        this.search($targetContainer, new SearchParams([], this.offset), $.extend({}, {
            success: function () {
                // Increase the offset so that we load new rows whenever we fetch more
                this.offset += this.offset;
            }
        }, this.options));
    }
}