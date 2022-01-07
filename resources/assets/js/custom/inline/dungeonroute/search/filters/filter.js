class SearchFilter {
    constructor(options) {
        console.assert(options.hasOwnProperty('onChange'), 'Filter options must have a onChange callback set', this);

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
