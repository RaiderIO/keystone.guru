class SearchFilterSpecializationsPlayerDeaths extends SearchFilterSpecializations {
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
        return lang.get('js.filter_input_select_specializations_player_deaths_header')
            .replace(':specializations', this._getSpecializationNames().join(', '));
    }
}
