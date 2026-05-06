class SearchFilterRadioRegion extends SearchFilterRadio {
    getFilterHeaderText() {
        return lang.get('js.filter_input_region_header')
            .replace(':value', lang.get(`gameserverregions.${this.getValue()}`));
    }
}
