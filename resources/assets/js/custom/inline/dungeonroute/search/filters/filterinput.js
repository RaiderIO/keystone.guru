class SearchFilterInput extends SearchFilter {
    constructor(options) {
        super(options);
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