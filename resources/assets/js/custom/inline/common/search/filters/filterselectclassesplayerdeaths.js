class SearchFilterClassesPlayerDeaths extends SearchFilterClasses {
    constructor(selector, onChange) {
        super(selector, onChange);
    }

    activate() {
        super.activate();

        let self = this;

        // Grouped affixes
        $(this.selector).off('change').on('change', function () {
            self.onChange();

            refreshSelectPickers();
        });
    }

    getFilterHeaderText() {

        return lang.get('messages.filter_input_select_classes_header')
            .replace(':classes', classNames.join(', '));
    }
}
