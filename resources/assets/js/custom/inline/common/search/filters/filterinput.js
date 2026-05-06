class SearchFilterInput extends SearchFilter {

    constructor(selector, onChange, options = {}) {
        super(selector, onChange, options);

        // Passthrough disables reading/writing from the DOM element, and instead uses an internal variable
        this.passThrough = options.hasOwnProperty('passThrough') ? options.passThrough : false;
        this.passThroughValue = '';
    }

    /**
     *
     * @returns {string}
     */
    getValue() {
        return this.passThrough ? this.getPassThroughValue() : $(this.selector).val();
    }

    getPassThroughValue() {
        return this.passThroughValue;
    }

    /**
     *
     * @param value
     */
    setValue(value) {
        if (this.passThrough) {
            this.passThroughValue = value;
        } else {
            $(this.selector).val(value);
        }
    }

    setPassThrough(value) {
        this.passThrough = value;
    }
}
