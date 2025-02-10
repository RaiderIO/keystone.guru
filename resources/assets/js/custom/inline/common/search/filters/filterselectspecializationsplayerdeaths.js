class SearchFilterSpecializationsPlayerDeaths extends SearchFilterSpecializations {
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
        return lang.get('messages.filter_input_select_specializations_player_deaths_header')
            .replace(':specializations', this._getSpecializationNames().join(', '));
    }
}
