class SearchFilter {
    constructor(selector, onChange, options = {}) {
        this.selector = selector;
        this.onChange = onChange;
        this.options = options;
        this.enabled = true;
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
}
