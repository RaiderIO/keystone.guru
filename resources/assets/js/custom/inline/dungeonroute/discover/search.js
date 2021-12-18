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

        this.filters = {
            'expansion': new SearchFilterManualExpansion(this._search.bind(this)),
            'dungeon': new SearchFilterDungeons('.grid_dungeon.selectable', this._search.bind(this)),
            'title': new SearchFilterTitle('#title', this._search.bind(this)),
            'level': new SearchFilterLevel('#level', this._search.bind(this), this.options.levelMin, this.options.levelMax),
            'filter_affixes': new SearchFilterAffixGroups('#filter_affixes', this._search.bind(this)),
            'affixes': new SearchFilterAffixes('.select_icon.class_icon.selectable', this._search.bind(this)),
            'enemy_forces': new SearchFilterEnemyForces('#enemy_forces', this._search.bind(this)),
            'rating': new SearchFilterRating('#rating', this._search.bind(this)),
            'user': new SearchFilterUser('#user', this._search.bind(this)),
        };
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

        // Set default values for the filters
        let queryParams = getQueryParams();

        // @TODO enemy_forces=0 gets flipped back to 1?

        // Find the query parameters
        for (let key in queryParams) {
            if (queryParams.hasOwnProperty(key)) {
                let value = queryParams[key];

                // Find the appropriate filter
                for (let filterIndex in this.filters) {
                    if (this.filters.hasOwnProperty(filterIndex)) {
                        let filter = this.filters[filterIndex];
                        // Find the filter and apply the value to the filter (use startsWith to catch array values)
                        if (key.startsWith(filter.options.name)) {
                            filter.setValue(value);
                            break;
                        }
                    }
                }
            }
        }

        // Restore selected expansion tab
        if (this.filters.expansion.getValue() !== '') {
            $(`#${this.filters.expansion.getValue()}-search-tab`).tab('show');
        }

        // Whenever the tab is changed, apply the new filter
        $('#search_expansion_select_tabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            let expansion = $(e.target).data('expansion');

            $(`#search_expansion_dungeon .grid_dungeon`).removeClass('selectable').filter(`.${expansion}`).addClass('selectable');
            self.filters.expansion.setValue(expansion);
        });

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
            `<span class="mr-2">${lang.get('messages.filters')}:</span>${html}`
        )
    }

    /**
     * Updates the URL according to the passed searchParams (so users can press F5 and be where they left off, ish)
     * @param searchParams
     * @private
     */
    _updateUrl(searchParams) {
        let urlParams = [];
        let blacklist = ['offset', 'limit'];
        for (let index in searchParams.params) {
            if (searchParams.params.hasOwnProperty(index) && !blacklist.includes(index)) {
                urlParams.push(`${index}=${encodeURIComponent(searchParams.params[index])}`);
            }
        }

        let newUrl = `?${urlParams.join('&')}`;

        // If it not just contains the question mark..
        if (newUrl.length > 1) {
            history.pushState({page: 1},
                newUrl,
                newUrl);
        }
    }

    _search(searchMore = false) {
        let self = this;

        // If we're not searching for more, we have to start over with searching and replace the entire contents
        if (!searchMore) {
            this.offset = 0;
        }

        let searchParams = new SearchParams(this.filters, {offset: this.offset, limit: this.limit});

        this._updateFilters();
        this._updateUrl(searchParams);

        // Only search if the search parameters have changed
        if (searchMore || this._previousSearchParams === null || !this._previousSearchParams.equals(searchParams)) {
            this.searchHandler.search($('#route_list'), searchParams, {
                beforeSend: function () {
                    self.loading = true;
                    $('#route_list_overlay').show();
                },
                success: function (html, textStatus, xhr) {
                    self.hasMore = xhr.status !== 204;
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
