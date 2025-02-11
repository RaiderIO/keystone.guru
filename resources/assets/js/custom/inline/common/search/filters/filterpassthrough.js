class SearchFilterPassThrough extends SearchFilter
{
    constructor() {
        super(null, null);

        this.value = '';
    }

    getValue() {
        return this.value;
    }

    setValue(value) {
        this.value = value;
    }
}
