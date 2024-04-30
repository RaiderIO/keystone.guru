class SearchFilterInput extends SearchFilter {

    /**
     *
     * @returns {string}
     */
    getValue() {
        return $(this.selector).val();
    }

    /**
     *
     * @param value
     */
    setValue(value) {
        $(this.selector).val(value);
    }
}
