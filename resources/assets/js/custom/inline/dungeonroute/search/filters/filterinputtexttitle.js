class SearchFilterTitle extends SearchFilterInputText {
    constructor(selector, onChange) {
        super({
            name: 'title',
            selector: selector,
            onChange: onChange
        });
    }

    getFilterHeaderText() {
        return `Title: ${this.getValue()}`;
    }
}