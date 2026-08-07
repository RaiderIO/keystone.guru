class SearchFilterPassThrough extends SearchFilter
{
    constructor(options = {}) {
        super(null, null, options);

        this.value = '';
    }

    getValue() {
        return this.value;
    }

    setValue(value) {
        this.value = value;
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchFilterPassThrough};
}
