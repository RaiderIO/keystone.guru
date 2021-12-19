class SearchParams {
    /**
     *
     * @param filters {SearchFilter[]}
     * @param queryParameters {{}}
     */
    constructor(filters, queryParameters) {
        this.filters = filters;

        this.params = $.extend({}, queryParameters);

        for (let name in this.filters) {
            if (this.filters.hasOwnProperty(name)) {
                let filter = this.filters[name];

                let value = filter.getValue();
                // Prevent sending empty strings
                if (value !== null && value !== '' && (typeof value !== 'object' || value.length > 0)) {
                    if (filter.options.array) {
                        this.params[`${name}[]`] = value;
                    } else {
                        this.params[name] = value;
                    }
                }
            }
        }
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
