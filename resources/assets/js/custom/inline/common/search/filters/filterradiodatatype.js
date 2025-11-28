class SearchFilterRadioDataType extends SearchFilterRadio {
    getFilterHeaderText() {
        return lang.get('js.filter_input_data_type_header')
            .replace(':value', lang.get(`combatlogdatatypes.${this.getValue()}`));
    }
}
