class SearchFilterTitle extends SearchFilterInputText {
    constructor(selector, onChange) {
        super({
            name: 'title',
            default: '',
            selector: selector,
            onChange: onChange
        });
    }

    getFilterHeaderText() {
        return `Title: ${this.getValue()}`;
    }
}