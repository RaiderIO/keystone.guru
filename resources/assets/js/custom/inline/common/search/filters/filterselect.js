class SearchFilterSelect extends SearchFilter {
    /**
     *
     * @returns {Array}
     */
    getValue() {
        return $(`${this.selector}`).val();
    }

    /**
     *
     * @param value {string}
     */
    setValue(value) {
        let split = value.split(',');

        $(`${this.selector}`).val(split);
    }
}
