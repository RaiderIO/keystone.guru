class SearchParams {
    /**
     *
     * @param filters {SearchFilter[]}
     * @param queryParameters {{}}
     */
    constructor(filters, queryParameters = {}) {
        this.filters = filters;

        this.params = {};
        this.addQueryParameters(queryParameters);

        for (let name in this.filters) {
            if (this.filters.hasOwnProperty(name)) {
                let filter = this.filters[name];

                if (!filter.isEnabled()) {
                    continue;
                }

                let paramsOverride = filter.getParamsOverride();
                if (paramsOverride !== null && paramsOverride.length !== null) {
                    for (let key in paramsOverride) {
                        // A value equal to its own default is only dropped when it wasn't explicitly asked for -
                        // otherwise an explicit ?minMythicLevel=2 vanishes from the URL just because 2 also
                        // happens to be that filter's default (#3837).
                        if (paramsOverride.hasOwnProperty(key) &&
                            (filter.hasExplicitValueOverride(key) || paramsOverride[key] !== filter.getDefaultValueOverride(key))) {
                            this.params[key] = paramsOverride[key];
                        }
                    }
                } else {
                    let value = filter.getValue();
                    // Prevent sending empty strings
                    if (value !== null && value !== '' && (typeof value !== 'object' || value.length > 0)) {
                        if (filter.options.array) {
                            this.params[`${name}[]`] = value;
                        } else {
                            this.params[name] = filter.options.csv && typeof value === 'object' ? value.join(',') : value;
                        }
                    }
                }
            }
        }
    }

    addQueryParameters(queryParameters = {}) {
        this.params = $.extend(this.params, queryParameters);
    }

    /**
     *
     * @param searchParams
     * @returns {boolean}
     */
    equals(searchParams) {
        return searchParams instanceof SearchParams &&
            (JSON.stringify(searchParams.params) === JSON.stringify(this.params));
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchParams};
}
