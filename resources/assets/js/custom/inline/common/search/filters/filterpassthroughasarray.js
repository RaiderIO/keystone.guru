class SearchFilterPassThroughAsArray extends SearchFilterPassThrough
{
    getValue() {
        return this.value.split(',').filter(item => item.length > 0).map(item => item.trim());
    }

    setValue(value) {
        this.value = value;
    }
}
