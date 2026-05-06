class SearchFilterManualExpansion extends SearchFilterManual {
    getFilterHeaderText() {
        return lang.get('js.filter_input_expansion_header').replace(':value', this.getValue());
    }
}
