class SearchFilterManualExpansion extends SearchFilterManual {
    getFilterHeaderText() {
        return lang.get('messages.filter_input_expansion_header').replace(':value', this.getValue());
    }
}
