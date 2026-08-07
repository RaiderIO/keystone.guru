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

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchFilterInput};
}
