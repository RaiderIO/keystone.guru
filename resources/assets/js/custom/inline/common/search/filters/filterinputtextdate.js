class SearchFilterInputDate extends SearchFilterInputText {
    getFilterHeaderText() {
        return lang.get('messages.filter_input_date_header').replace(':value', this.getValue());
    }
}
