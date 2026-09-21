class SearchFilterTitle extends SearchFilterInputText {
    getFilterHeaderText() {
        return lang.get('js.filter_input_title_header').replace(':value', this.getValue());
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchFilterTitle};
}
