class SearchFilterClassesPlayerDeaths extends SearchFilterClasses {
    constructor(selector, onChange, options = {}) {
        super(selector, onChange, options);
    }

    activate() {
        super.activate();

        let self = this;

        // Grouped affixes
        if (!this.passThrough) {
            $(this.selector).off('change').on('change', function () {
                self.onChange();

                refreshSelectPickers();
            });
        }
    }

    getFilterHeaderText() {
        return lang.get('js.filter_input_select_classes_header')
            .replace(':classes', this._getClassNames().join(', '));
    }
}
