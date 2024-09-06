class SearchFilterRadioEventType extends SearchFilterRadio {
    getFilterHeaderText() {
        return lang.get('messages.filter_input_event_type_header')
            .replace(':value', lang.get(`combatlogeventtypes.${this.getValue()}`));
    }
}
