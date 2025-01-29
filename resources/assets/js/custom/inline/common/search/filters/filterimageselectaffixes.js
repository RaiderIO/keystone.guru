class SearchFilterAffixes extends SearchFilterImageSelect {
    constructor(selector, onChange) {
        super(selector, onChange);
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_image_select_affixes_header')
            .replace(':number', '' + this.getValue().length);
    }
}
