class SearchHandler {
    constructor() {

    }

    /**
     *
     * @protected
     */
    getSearchUrl() {
        return ``;
    }

    /**
     *
     * @param searchParams {SearchParams}
     * @param options {{}}
     */
    search(searchParams, options = {}) {
        console.assert(this instanceof SearchHandler, 'this was not a SearchHandler', this);
        console.assert(searchParams instanceof SearchParams, 'searchParams was not null or a SearchParams', searchParams);

        $.ajax({
            type: 'GET',
            url: this.getSearchUrl(),
            dataType: 'html',
            data: searchParams.params,
            beforeSend: function () {
                if (options.hasOwnProperty('beforeSend')) {
                    options.beforeSend();
                }
            },
            success: function (html, textStatus, xhr) {
                if (options.hasOwnProperty('success')) {
                    options.success(html, textStatus, xhr);
                }
            },
            complete: function () {
                if (options.hasOwnProperty('complete')) {
                    options.complete();
                }
            }
        });
    }
}
