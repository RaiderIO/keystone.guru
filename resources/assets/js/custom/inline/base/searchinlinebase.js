class SearchInlineBase extends InlineCode {

    constructor(searchHandler, id, bladePath, options) {
        super(id, bladePath, options);

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
                        // The URL asked for this value explicitly, so it must survive a round trip even when it
                        // happens to equal the filter's default (#3837)
                        filter.markValueOverrideExplicit(filtersKey);
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

        if (html.length === 0) {
            html = lang.get('js.filter_no_filters_active');
        }

        $(this.options.currentFiltersSelector).html(
            `<span class="me-2">${lang.get('js.filters')}:</span>${html}`
        )
    }

    /**
     * All query parameter names the filters of this search own - anything else in the URL belongs to someone else
     * and must be left alone by _updateUrl().
     *
     * Disabled filters are included on purpose: their params must be dropped from the URL, not preserved.
     *
     * @returns {String[]}
     * @protected
     */
    _getFilterParamNames() {
        console.assert(this instanceof SearchInlineBase, 'this is not a SearchInlineBase!', this);

        let names = [];

        for (let name in this.filters) {
            if (!this.filters.hasOwnProperty(name)) {
                continue;
            }

            let paramsOverride = this.filters[name].getParamsOverride();
            if (paramsOverride !== null) {
                names = names.concat(Object.keys(paramsOverride));
            } else {
                names.push(name);
                names.push(`${name}[]`);
            }
        }

        return names;
    }

    /**
     * Updates the URL according to the passed searchParams (so users can press F5 and be where they left off, ish)
     *
     * Query parameters that none of the filters own (embed display options such as defaultZoom or showHeader) are
     * carried over untouched - rebuilding the query string from the filters alone silently truncated shared embed
     * URLs (#3837).
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

        // Everything we're not responsible for stays exactly as it was
        let ownedParams = this._getFilterParamNames().concat(blacklist);
        let existingParams = getQueryParams();
        for (let index in existingParams) {
            if (existingParams.hasOwnProperty(index) && !ownedParams.includes(index) &&
                !searchParams.params.hasOwnProperty(index)) {
                urlParams.push(`${index}=${encodeURIComponent(existingParams[index])}`);
            }
        }

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

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchInlineBase};
}
