class SearchFilterAffixes extends SearchFilterImageSelect {
    constructor(selector, onChange) {
        super(selector, onChange);
    }

    getFilterHeaderText() {
        return lang.get('js.filter_image_select_affixes_header')
            .replace(':number', '' + this.getValue().length);
    }
}
