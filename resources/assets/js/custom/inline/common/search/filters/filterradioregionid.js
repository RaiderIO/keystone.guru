class SearchFilterRadioRegionId extends SearchFilterRadio {
    getFilterHeaderText() {
        return lang.get('messages.filter_input_region_id_header')
            .replace(':value', lang.get(`gameserverregions.${this.getValue()}`));
    }
}
