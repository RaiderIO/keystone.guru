class SearchFilterManual extends SearchFilter {
    constructor(options) {
        super(options);

        this.value = '';
    }

    /**
     *
     * @returns {string}
     */
    getValue() {
        return this.value;
    }

    /**
     *
     * @param value {String}
     */
    setValue(value) {
        if (this.value !== value) {
            this.value = value;

            // Call on change
            this.options.onChange();
        }
    }
}
