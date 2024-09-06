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
}
