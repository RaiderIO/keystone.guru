class SearchFilterRadioDataType extends SearchFilterRadio {
    getFilterHeaderText() {
        return lang.get('js.filter_input_data_type_header')
            .replace(':value', lang.get(`combatlogdatatypes.${this.getValue()}`));
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchFilterRadioDataType};
}
