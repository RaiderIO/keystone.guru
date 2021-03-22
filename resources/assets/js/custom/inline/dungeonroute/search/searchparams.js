class SearchParams {
    /**
     *
     * @param filters {SearchFilter[]}
     * @param offset {Number}
     */
    constructor(filters, offset) {
        this.filters = filters;
        this.offset = offset;


        this.params = {offset: this.offset};

        for (let i = 0; i < this.filters.length; i++) {
            let filter = this.filters[i];

            let value = filter.getValue();
            // Prevent sending empty strings
            if (value !== null && value !== '' && value.length > 0) {
                if (filter.options.array) {
                    this.params[`${filter.options.name}[]`] = value;
                } else {
                    this.params[filter.options.name] = value;
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