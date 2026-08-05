class SearchFilter {
    constructor(selector, onChange, options = {}) {
        this.selector = selector;
        this.onChange = onChange;
        this.options = options;
        this.enabled = true;
        // Names of the params override keys that were explicitly restored from the URL - see markValueOverrideExplicit()
        this.explicitValueOverrides = {};
    }

    activate() {

    }

    toggle(enabled) {
        this.enabled = enabled;
    }

    isEnabled() {
        return this.enabled;
    }

    getFilterHeaderText() {

    }

    getFilterHeaderHtml() {
        let template = Handlebars.templates['search_filter_active_badge_template'];

        return template({
            text: this.getFilterHeaderText()
        });
    }

    getValue() {

    }

    getDefaultValue() {
        return '';
    }

    setValue(value) {

    }

    /**
     * Allows you to override the params that are sent to the server - instead of being hard coded
     * to name => value.
     */
    getParamsOverride() {
        return null;
    }

    /**
     * Called whenever we have a params override, and we want to restore a value based on the URL params that were overridden
     * @param name
     * @param value
     */
    setValueOverride(name, value) {

    }

    getDefaultValueOverride(name) {
        return null;
    }

    /**
     * Marks a params override key as having been set explicitly (rather than being left at its default).
     *
     * A value that happens to equal its own default is otherwise indistinguishable from "not set" and would be
     * dropped from the URL again on the next search - which silently truncates a shared/embedded link (#3837).
     *
     * @param name {String}
     */
    markValueOverrideExplicit(name) {
        this.explicitValueOverrides[name] = true;
    }

    /**
     * @param name {String}
     * @returns {boolean}
     */
    hasExplicitValueOverride(name) {
        return this.explicitValueOverrides.hasOwnProperty(name);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchFilter};
}
