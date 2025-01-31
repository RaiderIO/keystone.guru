class SearchHandlerHeatmap extends SearchHandler {
    constructor(options) {
        super($.extend({}, {
            loaderFn: function(isLoading) {
                console.log('loading', isLoading);
            }
        }, options));
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
}
