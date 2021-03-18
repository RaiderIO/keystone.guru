class SearchParams {
    constructor(options) {
        this.fields = [{
            name: 'offset',
            default: 0
        }, {
            name: 'title',
            default: ''
        }, {
            name: 'enemy_forces',
            default: 1
        }, {
            name: 'rating',
            default: 1
        }, {
            name: 'user',
            default: ''
        }];

        for (let i = 0; i < this.fields.length; i++) {
            let field = this.fields[i];
            this[field.name] = options.hasOwnProperty(field.name) ? options[field.name] : field.default;
        }
    }

    /**
     *
     * @returns {{offset: number, title: string}}
     */
    toObject() {
        let result = {};
        for (let i = 0; i < this.fields.length; i++) {
            let field = this.fields[i];

            let value = this[field.name];
            // Prevent sending empty strings
            if (value !== null && value !== '') {
                result[field.name] = value;
            }
        }

        return result;
    }

    /**
     *
     * @param searchParams
     * @returns {boolean}
     */
    equals(searchParams) {
        return searchParams instanceof SearchParams &&
            (JSON.stringify(searchParams.toObject()) === JSON.stringify(this.toObject()));
    }
}