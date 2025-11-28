class SearchFilterUser extends SearchFilterInputText {
    getFilterHeaderText() {
        return lang.get('js.filter_input_user_header').replace(':number', this.getValue());
    }
}
