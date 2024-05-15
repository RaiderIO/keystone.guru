class SearchFilterInputDateFrom extends SearchFilterInputDate {
    getFilterHeaderText() {
        return lang.get('messages.filter_input_date_from_header').replace(':value', this.getValue());
    }
}
