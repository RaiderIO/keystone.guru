/**
 * @typedef {Object} DungeonRouteSearchOptions
 * @property {String} stateCookie
 * @property {Number} defaultState
 * @property {Boolean} hideOnMove
 * @property {String} currentFiltersSelector
 * @property {String} loaderSelector
 *
 * @property {String} keyLevelSelector
 * @property {Number} keyLevelMin
 * @property {Number} keyLevelMax
 *
 * @property {String} enabledStateCookie
 * @property {String} enabledStateSelector
 *
 * @property {String} filterKeyLevelSelector
 * @property {String} filterTitleSelector
 * @property {String} filterUsernameSelector
 *
 * @property {String} filterCollapseNames
 * @property {String} filterCookiePrefix
 *
 * @property {String} sidebarSelector
 * @property {String} sidebarToggleSelector
 * @property {String} sidebarScrollSelector
 * @property {String} anchor
 * @property {String} edit
 *
 * @property {String[]} filterCollapseNames
 */

/**
 * @property {DungeonRouteSearchOptions} options
 */
class CommonMapsDungeonroutesearchsidebar extends SearchInlineBase {
    constructor(options) {
        super(new SearchHandlerDungeonRouteSearch(options), options);

        this.sidebar = new Sidebar(options);
        this.initializing = true;

        this._draggable = null;

        // Previous search params are used to prevent searching for the same thing multiple times for no reason
        this._previousSearchParams = null;

        this.filters = {
            'keyLevel': new SearchFilterMythicLevel(this.options.filterKeyLevelSelector, this._search.bind(this), this.options.keyLevelMin, this.options.keyLevelMax),
            'title': new SearchFilterTitle(this.options.filterTitleSelector, this._search.bind(this)),
            'username': new SearchFilterUser(this.options.filterUsernameSelector, this._search.bind(this)),
            // 'offset': new SearchFilterInputText(this.options.filterOffsetSelector, this._search.bind(this)),
            // 'offset': new SearchFilterInputText(this.options.filterOffsetSelector, this._search.bind(this)),
        };


        this._setupFilterCollapseCookies();
    }

    _setupFilterCollapseCookies() {
        // Return early if we don't have the required options
        if (typeof this.options.filterCookiePrefix === 'undefined' || this.options.filterCookiePrefix === null ||
            typeof this.options.filterCollapseNames === 'undefined' || this.options.filterCollapseNames.length === 0) {
            return;
        }

        let self = this;

        for (let key in this.options.filterCollapseNames) {
            let collapseName = this.options.filterCollapseNames[key];

            // Only if there's actually an accordeon for this filter
            let $collapse = $(`#filter_accordeon_${collapseName}`);
            if ($collapse.length > 0) {
                $collapse.on('shown.bs.collapse', function () {
                    Cookies.set(self.options.filterCookiePrefix + collapseName, '1', cookieDefaultAttributes);
                }).on('hidden.bs.collapse', function () {
                    Cookies.set(self.options.filterCookiePrefix + collapseName, '0', cookieDefaultAttributes);
                });
            }
        }
    }

    /**
     *
     */
    activate() {
        super.activate();
        console.assert(this instanceof CommonMapsDungeonroutesearchsidebar, 'this is not a CommonMapsDungeonroutesearchsidebar', this);

        let self = this;

        this.map = getState().getDungeonMap();

        let $enabledState = $(this.options.enabledStateSelector);
        $enabledState.on('change', function () {
            let enabled = $(this).is(':checked');
            self._toggleHeatmap(enabled);
        });


        this.sidebar.activate();

        if (this.options.defaultState > 1 && $('#map').width() > this.options.defaultState) {
            this.sidebar.showSidebar();
        }

        this.initializing = false;
        this._search();
    }

    searchWithFilters(filters) {
        this._restoreFiltersFromQueryParams(filters);

        this._search();

        // Make sure the select dropdowns are updated properly - external changes don't cause a UI refresh
        refreshSelectPickers();
    }

    _search() {
        console.assert(this instanceof CommonMapsDungeonroutesearchsidebar, 'this is not a CommonMapsDungeonroutesearchsidebar', this);

        if (this.initializing) {
            return;
        }

        let self = this;

        super._search({
            success: function (response) {

                let $searchResultsContainer = $(self.options.sidebarSearchResultSelector);

                let template = Handlebars.templates['map_sidebar_dungeon_route_search_results'];

                $searchResultsContainer.empty();
                $searchResultsContainer.html(
                    template($.extend({}, getHandlebarsDefaultVariables(), {
                        search_results: response,
                    }))
                );

                $searchResultsContainer.find('.search_results').children().each(function () {
                    let $routeRow = $(this);
                    $($routeRow.find('.route_title')).on('click', function (event) {

                        self._loadDungeonRoute($(this).data('publickey'));

                        event.preventDefault();
                    });
                });

                (new ThumbnailRefresh()).refreshHandlers();
            },
        }, {}, ['dungeonId']);
    }

    /**
     *
     */
    cleanup() {
        console.assert(this instanceof CommonMapsDungeonroutesearchsidebar, 'this is not a CommonMapsDungeonroutesearchsidebar', this);

    }

    /**
     *
     * @param publicKey {String}
     * @private
     */
    _loadDungeonRoute(publicKey) {
        $.ajax({
            type: 'GET',
            url: `/ajax/dungeonroute/${publicKey}/mapcontext`,
            success: function (json) {
                getState().setMapContext($.extend({}, mapContextStaticData, mapContextDungeonData, mapContextMappingVersionData, json));
            }
        });
    }
}
