class SearchFilterManualExpansion extends SearchFilterManual {
    constructor(onChange) {
        super({
            name: 'expansion',
            onChange: onChange
        });
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_input_expansion_header').replace(':value', this.getValue());
    }
}
