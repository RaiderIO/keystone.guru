class SearchFilter {
    constructor(selector, onChange, options = {}) {
        this.selector = selector;
        this.onChange = onChange;
        this.options = options;
    }

    activate() {

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
}
