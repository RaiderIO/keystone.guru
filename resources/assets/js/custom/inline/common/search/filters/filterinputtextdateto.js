class SearchFilterInputDateTo extends SearchFilterInputDate {
    constructor(selector, onChange) {
        super(selector, onChange);
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_input_date_to_header').replace(':value', this.getValue());
    }
}
