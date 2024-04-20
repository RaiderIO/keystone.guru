class SearchHandlerHeatmap extends SearchHandler {
    constructor(options) {
        super(options);
    }

    getSearchUrl() {
        console.assert(this instanceof SearchHandlerHeatmap, 'this is not a SearchHandlerHeatmap', this);
        return `/ajax/heatmap/data`;
    }

    getAjaxOptions() {
        return {
            type: 'POST',
            dataType: 'json'
        };
    }

    /**
     * @param searchParams {SearchParams}
     * @param options {{}}
     */
    search(searchParams, options = {}) {
        console.assert(this instanceof SearchHandlerHeatmap, 'this is not a SearchHandlerHeatmap', this);

        let self = this;

        let extendedOptions = $.extend({}, options, {
            success: function (json) {
                self.applySearchResultToMap(searchParams, json);
            },
        });

        super.search(searchParams, extendedOptions);

        this.previousSearchParams = searchParams;
    }

    applySearchResultToMap(searchParams, json) {
        console.assert(this instanceof SearchHandlerHeatmap, 'this is not a SearchHandlerHeatmap', this);


    }
}
