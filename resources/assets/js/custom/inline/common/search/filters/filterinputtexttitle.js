class SearchFilterTitle extends SearchFilterInputText {
    getFilterHeaderText() {
        return lang.get('messages.filter_input_title_header').replace(':number', this.getValue());
    }
}
