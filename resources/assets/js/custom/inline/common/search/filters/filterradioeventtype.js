class SearchFilterRadioEventType extends SearchFilterRadio {
    getFilterHeaderText() {
        return lang.get('js.filter_input_event_type_header')
            .replace(':value', lang.get(`combatlogeventtypes.${this.getValue()}`));
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchFilterRadioEventType};
}
