class SearchParams {
    /**
     *
     * @param filters {SearchFilter[]}
     * @param queryParameters {{}}
     */
    constructor(filters, queryParameters) {
        this.filters = filters;

        this.params = $.extend({}, queryParameters);

        for (let index in this.filters) {
            if (this.filters.hasOwnProperty(index)) {
                let filter = this.filters[index];

                let value = filter.getValue();
                // Prevent sending empty strings
                if (value !== null && value !== '' && (typeof value !== 'object' || value.length > 0)) {
                    if (filter.options.array) {
                        this.params[`${filter.options.name}[]`] = value;
                    } else {
                        this.params[filter.options.name] = value;
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
