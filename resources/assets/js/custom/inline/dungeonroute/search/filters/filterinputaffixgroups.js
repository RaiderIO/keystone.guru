class SearchFilterAffixGroups extends SearchFilterInput {
    constructor(selector, onChange) {
        super({
            name: 'affixgroups',
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
        return `Affix groups: ${this.getValue().length} selected`;
    }
}