class SearchFilterTitle extends SearchFilterInputText {
    constructor(selector, onChange) {
        super({
            selector: selector,
            onChange: onChange
        });
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_input_title_header').replace(':number', this.getValue());
    }
}
