class SearchHandler {
    constructor(options) {
        this.options = options;

        this.loading = false;
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
     * @protected
     */
    getAjaxOptions() {
        return {};
    }

    /**
     *
     * @param searchParams {SearchParams}
     * @param options {{}}
     */
    search(searchParams, options = {}) {
        console.assert(this instanceof SearchHandler, 'this was not a SearchHandler', this);
        console.assert(searchParams instanceof SearchParams, 'searchParams was not null or a SearchParams', searchParams);

        let self = this;
        let response = null;

        $.ajax($.extend({}, {
            type: 'GET',
            url: this.getSearchUrl(),
            dataType: 'html',
            data: searchParams.params,
            beforeSend: function () {
                self.loading = true;
                if (typeof self.options.loaderFn === 'function') {
                    self.options.loaderFn(true, null);
                } else if (typeof self.options.loaderSelector !== 'undefined') {
                    $(self.options.loaderSelector).show();
                }

                if (options.hasOwnProperty('beforeSend')) {
                    options.beforeSend();
                }
            },
            success: function (html, textStatus, xhr) {
                response = html;

                if (options.hasOwnProperty('success')) {
                    options.success(html, textStatus, xhr);
                }
            },
            complete: function () {
                self.loading = false;
                if (typeof self.options.loaderFn === 'function') {
                    self.options.loaderFn(false, response);
                } else if (typeof self.options.loaderSelector !== 'undefined') {
                    $(self.options.loaderSelector).hide();
                }

                if (options.hasOwnProperty('complete')) {
                    options.complete();
                }
                // Reset just in case
                response = null;
            }
        }, this.getAjaxOptions()));
    }
}
