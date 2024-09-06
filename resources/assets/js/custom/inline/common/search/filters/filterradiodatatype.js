class SearchFilterRadioDataType extends SearchFilterRadio {
    getFilterHeaderText() {
        return lang.get('messages.filter_input_data_type_header')
            .replace(':value', lang.get(`combatlogdatatypes.${this.getValue()}`));
    }
}
