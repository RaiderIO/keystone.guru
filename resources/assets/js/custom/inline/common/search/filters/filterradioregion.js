class SearchFilterRadioRegion extends SearchFilterRadio {
    getFilterHeaderText() {
        return lang.get('messages.filter_input_region_header')
            .replace(':value', lang.get(`gameserverregions.${this.getValue()}`));
    }
}
