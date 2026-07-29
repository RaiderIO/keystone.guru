class SearchFilterSelect extends SearchFilterInput {
    /**
     *
     * @returns {Array}
     */
    getPassThroughValue() {
        // Ensure that empty values are ignored
        if(typeof this.passThroughValue === 'object') {
            console.error(`Pass through value is not a string:`, this.passThroughValue, this.selector);
        }

        return this.passThroughValue.split(',').filter(item => item.length > 0).map(item => item.trim());
    }

    /**
     *
     * @param value {string}
     */
    setValue(value) {
        if (this.passThrough) {
            this.passThroughValue = value;
        } else {
            let split = value.split(',');

            $(this.selector).val(split);
        }
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchFilterSelect};
}
