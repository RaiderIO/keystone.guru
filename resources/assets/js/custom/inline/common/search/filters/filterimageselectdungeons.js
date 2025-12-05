class SearchFilterDungeons extends SearchFilterImageSelect {
    constructor(selector, onChange, options = {}) {
        super(selector, onChange, $.extend({}, options, {
            array: true,
        }));
    }

    getFilterHeaderText() {
        return lang.get('js.filter_image_select_dungeons_header')
            .replace(':number', '' + this.getValue().length);
    }
}
