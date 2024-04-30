class SearchFilterUser extends SearchFilterInputText {
    getFilterHeaderText() {
        return lang.get('messages.filter_input_user_header').replace(':number', this.getValue());
    }
}
