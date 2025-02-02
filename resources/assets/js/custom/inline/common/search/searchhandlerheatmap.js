class SearchHandlerHeatmap extends SearchHandler {
    constructor(options) {

        let currentSnackbarId = null;
        super($.extend({}, {
            loaderFn: function (isLoading, json) {
                let state = getState();

                state.removeSnackbar(currentSnackbarId);

                let data = $.extend({}, getHandlebarsDefaultVariables());
                let template;
                if (isLoading) {
                    template = Handlebars.templates['map_heatmapsearch_loader'];
                } else {
                    template = Handlebars.templates['map_heatmapsearch_run_count'];
                    data.run_count = lang.get('messages.run_count_label', {count: json.run_count});
                }

                currentSnackbarId = getState().addSnackbar(
                    template(data), {
                        compact: true
                    }
                );
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
