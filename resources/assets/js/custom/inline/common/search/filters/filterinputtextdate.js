class SearchFilterInputDate extends SearchFilterInputText {
    constructor(selector, onChange) {
        super({
            selector: selector,
            onChange: onChange
        });
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_input_date_header').replace(':value', this.getValue());
    }
}
