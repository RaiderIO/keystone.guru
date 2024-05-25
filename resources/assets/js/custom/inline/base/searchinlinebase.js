class SearchInlineBase extends InlineCode {

    constructor(searchHandler, options) {
        super(options);

        /** @type {SearchHandler} */
        this.searchHandler = searchHandler;
        // Previous search params are used to prevent searching for the same thing multiple times for no reason
        this._previousSearchParams = null;
        this.filters = {};
    }

    /**
     */
    activate() {
        super.activate();

        // Init all filters
        for (let index in this.filters) {
            if (this.filters.hasOwnProperty(index)) {
                this.filters[index].activate();
            }
        }

        // Set default values for the filters
        let queryParams = getQueryParams();

        // Restore URL -> filters values
        for (let key in queryParams) {
            let filtersKey = key.replace('[]', '');
            console.log(`Attempting to restore value for ${key} (${filtersKey})`);
            if (queryParams.hasOwnProperty(key) && this.filters.hasOwnProperty(filtersKey)) {
                console.log(`Restoring value for ${key}`);
                let value = queryParams[key];

                this.filters[filtersKey].setValue(value);
            }
        }
    }

    /**
     *
     * @protected
     */
    _updateFilters() {
        if (typeof this.options.currentFiltersSelector === 'undefined') {
            return;
        }

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


        $(this.options.currentFiltersSelector).html(
            `<span class="mr-2">${lang.get('messages.filters')}:</span>${html}`
        )
    }

    /**
     * Updates the URL according to the passed searchParams (so users can press F5 and be where they left off, ish)
     *
     * @param searchParams {SearchParams}
     * @protected
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

    /**
     * @param options {Object}
     * @param queryParameters {Object}
     * @protected
     */
    _search(options = {}, queryParameters = {}) {
        let searchParams = new SearchParams(this.filters, queryParameters);

        this._updateFilters();
        this._updateUrl(searchParams);

        // Only search if the search parameters have changed
        if (this._previousSearchParams === null || !this._previousSearchParams.equals(searchParams)) {
            this.searchHandler.search(searchParams, options);
        }

        this._previousSearchParams = searchParams;
    }
}
