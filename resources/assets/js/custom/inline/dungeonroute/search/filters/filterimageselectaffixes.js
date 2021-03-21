class SearchFilterAffixes extends SearchFilterImageSelect {
    constructor(selector, onChange) {
        super({
            name: 'affixes',
            default: [],
            selector: selector,
            onChange: onChange,
            array: true,
        });
    }
}