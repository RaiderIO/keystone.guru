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

                let paramsOverride = filter.getParamsOverride();
                if (paramsOverride !== null && paramsOverride.length !== null) {
                    for (let key in paramsOverride) {
                        if (paramsOverride.hasOwnProperty(key) && paramsOverride[key] !== filter.getDefaultValueOverride(key)) {
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
