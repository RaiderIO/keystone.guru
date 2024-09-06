class SearchFilterWeeklyAffixGroups extends SearchFilterInput {
    activate() {
        super.activate();

        let self = this;

        // Grouped affixes
        $(this.selector).off('change').change(function() {
            refreshSelectPickers();

            self.onChange();
        });
    }

    getFilterHeaderText() {
        let value = this.getValue();
        let option = $(`${this.selector} option`).eq(value);

        return lang.get('messages.filter_image_select_weekly_affix_groups_header')
            .replace(':week', '' + value)
            .replace(':date', option.data('date'));
    }
}
