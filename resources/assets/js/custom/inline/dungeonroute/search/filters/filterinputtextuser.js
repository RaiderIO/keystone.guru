class SearchFilterUser extends SearchFilterInputText {
    constructor(selector, onChange) {
        super({
            name: 'user',
            selector: selector,
            onChange: onChange
        });
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_input_user_header').replace(':number', this.getValue());
    }
}