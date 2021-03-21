class SearchFilterInput extends SearchFilter {
    constructor(options) {
        super(options);
    }

    getValue() {
        return $(this.options.selector).val();
    }
}