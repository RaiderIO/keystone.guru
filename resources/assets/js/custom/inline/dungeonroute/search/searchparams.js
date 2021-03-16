class SearchParams {
    constructor(offset = 0, title = '') {
        this.offset = offset;
        this.title = title;
    }

    toObject() {
        return {
            offset: this.offset,
            title: this.title,
        }
    }
}