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
        return lang.get('messages.filter_image_select_dungeons_header')
            .replace(':number', '' + this.getValue().length);
    }
}