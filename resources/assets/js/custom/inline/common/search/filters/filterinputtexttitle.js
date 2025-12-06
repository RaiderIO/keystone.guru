class SearchFilterTitle extends SearchFilterInputText {
    getFilterHeaderText() {
        return lang.get('js.filter_input_title_header').replace(':number', this.getValue());
    }
}
