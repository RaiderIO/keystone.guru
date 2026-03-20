class SearchFilterManual extends SearchFilter {
    constructor(onChange) {
        super('', onChange);

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
     * @param triggerOnChange {boolean}
     */
    setValue(value, triggerOnChange = true) {
        if (this.value !== value) {
            this.value = value;

            // Call on change
            if (triggerOnChange) {
                this.onChange();
            }
        }
    }
}
