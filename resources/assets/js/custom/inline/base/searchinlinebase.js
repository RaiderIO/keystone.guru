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
        this._restoreFiltersFromQueryParams(queryParams);
    }

    /**
     *
     * @param queryParams
     * @protected
     */
    _restoreFiltersFromQueryParams(queryParams) {
        console.assert(this instanceof SearchInlineBase, 'this is not a SearchInlineBase!', this);

        for (let key in queryParams) {
            let filtersKey = key.replace('[]', '');
            let valueAssigned = false;

            // Check if we have a filter that claims this key by overriding it
            for (let filterKey in this.filters) {
                if (this.filters.hasOwnProperty(filterKey)) {
                    let filter = this.filters[filterKey];
                    let paramsOverride = filter.getParamsOverride();
                    // Check if this filter wants to claim this key
                    if (paramsOverride !== null && filter.getParamsOverride().hasOwnProperty(filtersKey)) {
                        // It does! Set the value
                        filter.setValueOverride(filtersKey, queryParams[key]);
                        valueAssigned = true;
                        break;
                    }
                }
            }

            if (!valueAssigned && queryParams.hasOwnProperty(key) && this.filters.hasOwnProperty(filtersKey)) {
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
        console.assert(this instanceof SearchInlineBase, 'this is not a SearchInlineBase!', this);

        if (typeof this.options.currentFiltersSelector === 'undefined') {
            return;
        }

        let html = '';

        for (let index in this.filters) {
            if (this.filters.hasOwnProperty(index)) {
                let filter = this.filters[index];
                if (!filter.isEnabled()) {
                    continue;
                }

                let value = filter.getValue();

                if (value !== null &&
                    value !== '' &&
                    (typeof value !== 'object' || value.length > 0) &&
                    value !== filter.getDefaultValue()
                ) {
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
     * @param blacklist {Array}
     * @protected
     */
    _updateUrl(searchParams, blacklist = []) {
        console.assert(this instanceof SearchInlineBase, 'this is not a SearchInlineBase!', this);

        let urlParams = [];

        blacklist.push('offset');
        blacklist.push('limit');

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
     * @param queryParametersUrlBlacklist
     * @protected
     */
    _search(options = {}, queryParameters = {}, queryParametersUrlBlacklist = []) {
        console.assert(this instanceof SearchInlineBase, 'this is not a SearchInlineBase!', this);

        let searchParams = new SearchParams(this.filters, queryParameters);

        this._updateFilters();
        this._updateUrl(searchParams, queryParametersUrlBlacklist);

        // Only search if the search parameters have changed
        if (this._previousSearchParams === null || !this._previousSearchParams.equals(searchParams)) {
            this.searchHandler.search(searchParams, options);
        }

        this._previousSearchParams = searchParams;
    }
}
