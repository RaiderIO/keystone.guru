class SearchFilterAffixGroups extends SearchFilterInput {
    constructor(selector, onChange) {
        super({
            selector: selector,
            onChange: onChange
        });
    }

    activate() {
        super.activate();

        // Grouped affixes
        $(this.options.selector).change(this.options.onChange);
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_input_affix_group_header')
            .replace(':number', '' + this.getValue().length);
    }
}
