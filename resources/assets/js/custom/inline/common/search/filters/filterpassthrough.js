class SearchFilterPassThrough extends SearchFilter
{
    constructor(options = {}) {
        super(null, null, options);

        this.value = '';
    }

    getValue() {
        return this.value;
    }

    setValue(value) {
        this.value = value;
    }
}
