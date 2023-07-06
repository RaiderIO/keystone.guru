class SearchFilterInput extends SearchFilter {
    constructor(options) {
        super(options);
        console.assert(options.hasOwnProperty('selector'), 'Filter options must have a selector set', this);
    }

    /**
     *
     * @returns {string}
     */
    getValue() {
        return $(this.options.selector).val();
    }

    /**
     *
     * @param value
     */
    setValue(value) {
        $(this.options.selector).val(value);
    }
}
