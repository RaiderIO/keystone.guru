class SearchFilterAffixGroups extends SearchFilterInput {
    activate() {
        super.activate();

        // Grouped affixes
        $(this.selector).off('change').change(this.onChange);
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_input_affix_group_header')
            .replace(':number', '' + this.getValue().length);
    }
}
