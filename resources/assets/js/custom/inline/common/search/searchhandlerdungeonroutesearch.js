class SearchHandlerDungeonRouteSearch extends SearchHandler {
    constructor(options) {
        let currentSnackbarId = null;
        super($.extend({}, {
            loaderFn: function (isLoading, response) {
                let state = getState();

                state.removeSnackbar(currentSnackbarId);

                let data = $.extend({}, getHandlebarsDefaultVariables());
                let template = null;
                if (isLoading) {
                    template = Handlebars.templates['map_heatmapsearch_loader'];
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
            dataType: 'html'
        };
    }
}
