class SearchFilterInputDateTo extends SearchFilterInputDate {
    getFilterHeaderText() {
        return lang.get('messages.filter_input_date_to_header').replace(':value', this.getValue());
    }
}
