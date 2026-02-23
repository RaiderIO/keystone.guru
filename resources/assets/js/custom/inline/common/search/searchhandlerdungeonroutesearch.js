class SearchHandlerDungeonRouteSearch extends SearchHandler {
    constructor(options) {
        let currentSnackbarId = null;
        super($.extend({}, {
            loaderFn: function (isLoading, json) {
                let state = getState();

                state.removeSnackbar(currentSnackbarId);

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
        console.assert(this instanceof SearchHandlerDungeonRouteSearch, 'this is not a SearchHandlerDungeonRouteSearch', this);
        return `/ajax/dungeonroute/search/${this.options.gameVersion}/${this.options.dungeon}`;
    }

    getAjaxOptions() {
        return {
            type: 'POST',
            dataType: 'json'
        };
    }
}
