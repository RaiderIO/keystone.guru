class DungeonrouteDiscoverSearch extends InlineCode {

    constructor(options) {
        super(options);

        this.searchHandler = new SearchHandler();
        // Previous search params are used to prevent searching for the same thing multiple times for no reason
        this._previousSearchParams = null;
        // The current offset
        this.offset = 0;
        this.limit = this.options.limit;
        this.loading = false;
        this.hasMore = true;

        this.filters = [
            new SearchFilterDungeons('.grid_dungeon.selectable', this._search.bind(this)),
            new SearchFilterTitle('#title', this._search.bind(this)),
            new SearchFilterLevel('#level', this._search.bind(this), this.options.levelMin, this.options.levelMax),
            new SearchFilterAffixGroups('#filter_affixes', this._search.bind(this)),
            new SearchFilterAffixes('.select_icon.class_icon.selectable', this._search.bind(this)),
            new SearchFilterEnemyForces('#enemy_forces', this._search.bind(this)),
            new SearchFilterRating('#rating', this._search.bind(this)),
            new SearchFilterUser('#user', this._search.bind(this)),
        ];
    }

    /**
     */
    activate() {
        super.activate();

        let self = this;

        for (let index in this.filters) {
            if (this.filters.hasOwnProperty(index)) {
                this.filters[index].activate();
            }
        }

        this.$loadMore = $('#route_list_load_more');

        $(window).on('resize scroll', function () {
            let inViewport = self.$loadMore.isInViewport();

            if (!self.loading && inViewport && self.hasMore) {
                self._search(true);
            }
        });

        // Show some not very useful routes to get people to start using the filters
        this._search();
    }

    /**
     *
     * @private
     */
    _updateFilters() {
        let html = '';

        for (let index in this.filters) {
            if (this.filters.hasOwnProperty(index)) {
                let filter = this.filters[index];
                let value = filter.getValue();

                if (value !== null && value !== '' && (typeof value !== 'object' || value.length > 0)) {
                    html += filter.getFilterHeaderHtml();
                }
            }
        }

        $('#route_list_current_filters').html(
            `<span class="mr-2">Filters:</span>${html}`
        )
    }

    _search(searchMore = false) {
        let self = this;

        // If we're not searching for more, we have to start over with searching and replace the entire contents
        if (!searchMore) {
            this.offset = 0;
        }

        let searchParams = new SearchParams(this.filters, {offset: this.offset, limit: this.limit});

        this._updateFilters();

        // Only search if the search parameters have changed
        if (searchMore || this._previousSearchParams === null || !this._previousSearchParams.equals(searchParams)) {
            this.searchHandler.search($('#route_list'), searchParams, {
                beforeSend: function () {
                    self.loading = true;
                    $('#route_list_overlay').show();
                },
                success: function (html) {
                    self.hasMore = html.length > 0;
                    if (self.hasMore) {
                        // Increase the offset so that we load new rows whenever we fetch more
                        self.offset += self.limit;
                    }
                },
                complete: function () {
                    self.loading = false;
                    $('#route_list_overlay').hide();
                }
            });

            this._previousSearchParams = searchParams;
        }
    }

    cleanup() {
    }
}