class SearchHandlerHeatmap extends SearchHandler {
    constructor(options) {
        let currentSnackbarId = null;
        super($.extend({}, {
            loaderFn: function (isLoading, response) {
                let state = getState();

                state.removeSnackbar(currentSnackbarId);

                let json = JSON.parse(response);
                let data = $.extend({}, getHandlebarsDefaultVariables());
                let template = null;
                if (isLoading) {
                    template = Handlebars.templates['map_heatmapsearch_loader'];
                } else if (json === null || json.hasOwnProperty('message')) {
                    template = Handlebars.templates['map_heatmapsearch_error_loading_data'];
                    if (json !== null && json.hasOwnProperty('message') && json.message === 'Invalid response from Raider.IO API') {
                        data.error = lang.get('js.too_much_data_label');
                    } else {
                        data.error = lang.get('js.error_loading_data_label');
                    }
                } else if (typeof this.showDataSourceSnackbar === 'undefined' || this.showDataSourceSnackbar) {
                    template = Handlebars.templates['map_heatmapsearch_run_count'];
                    data.run_count = lang.get('js.run_count_label', {
                        count: json.run_count
                    });
                }

                if (typeof template === 'function') {
                    currentSnackbarId = getState().addSnackbar(
                        template(data), {
                            compact: true
                        }
                    );
                }
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
