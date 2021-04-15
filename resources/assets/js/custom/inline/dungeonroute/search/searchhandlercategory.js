class SearchHandlerCategory extends SearchHandler {
    constructor(category, offset, limit, options) {
        super();

        this.category = category;
        this.offset = offset;
        this.limit = limit;
        this.options = options;
        this.hasMore = true;
    }


    /**
     *
     * @protected
     */
    getSearchUrl() {
        return `/ajax/search/${this.category}`;
    }

    /**
     *
     * @param $targetContainer
     */
    searchMore($targetContainer) {
        let self = this;

        this.search($targetContainer, new SearchParams([], {
            offset: this.offset,
            limit: this.limit,
            dungeon: typeof this.options.dungeon !== 'undefined' && this.options.dungeon !== null ? this.options.dungeon.id : null
        }), $.extend({}, {
            success: function (html) {
                // Only if we actually got any results back
                self.hasMore = html.length > 0;
                if (self.hasMore) {
                    // Increase the offset so that we load new rows whenever we fetch more
                    self.offset += self.limit;
                }
            }
        }, this.options));
    }
}