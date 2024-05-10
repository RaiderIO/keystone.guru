class SearchFilterDungeons extends SearchFilterImageSelect {
    constructor(selector, onChange) {
        super(selector, onChange, {
            array: true,
        });
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_image_select_dungeons_header')
            .replace(':number', '' + this.getValue().length);
    }
}
