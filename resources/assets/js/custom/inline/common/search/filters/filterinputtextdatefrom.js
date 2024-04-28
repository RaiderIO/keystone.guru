class SearchFilterInputDateFrom extends SearchFilterInputDate {
    constructor(selector, onChange) {
        super(selector, onChange);
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_input_date_from_header').replace(':value', this.getValue());
    }
}
