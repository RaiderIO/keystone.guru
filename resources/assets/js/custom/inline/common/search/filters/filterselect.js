class SearchFilterSelect extends SearchFilterInput {
    /**
     *
     * @returns {Array}
     */
    getPassThroughValue() {
        // Ensure that empty values are ignored
        return this.passThroughValue.split(',').filter(item => item.length > 0).map(item => item.trim());
    }

    /**
     *
     * @param value {string}
     */
    setValue(value) {
        if (this.passThrough) {
            this.passThroughValue = value.split(',');
        } else {
            let split = value.split(',');

            $(this.selector).val(split);
        }
    }
}
