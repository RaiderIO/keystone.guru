class SearchFilterDungeons extends SearchFilterImageSelect {
    constructor(selector, onChange) {
        super({
            name: 'dungeons',
            selector: selector,
            onChange: onChange,
            array: true,
        });
    }

    getFilterHeaderText() {
        return `Dungeons: ${this.getValue().length} selected`;
    }
}