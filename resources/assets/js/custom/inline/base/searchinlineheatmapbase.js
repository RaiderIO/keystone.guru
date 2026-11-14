/**
 * @typedef {Object} HeatmapSearchBaseOptions
 * @property {String} stateCookie
 * @property {Number} defaultState
 * @property {Boolean} hideOnMove
 * @property {String} currentFiltersSelector
 * @property {String} loaderSelector
 * @property {Boolean} passThroughEverything
 * @property {Boolean} showDataSourceSnackbar
 *
 * @property {Number} minSamplesRequiredMin
 * @property {Number} minSamplesRequiredMax
 *
 * @property {String} enabledStateCookie
 * @property {String} enabledStateSelector
 *
 * @property {String} filterMinSamplesRequiredSelector
 *
 * @property {String[]} filterCollapseNames
 * @property {String} filterCookiePrefix
 *
 * @property {String} leafletHeatOptionsMinOpacitySelector
 * @property {String} leafletHeatOptionsMaxZoomSelector
 * @property {String} leafletHeatOptionsMaxSelector
 * @property {String} leafletHeatOptionsRadiusSelector
 * @property {String} leafletHeatOptionsBlurSelector
 * @property {String} leafletHeatOptionsGradientSelector
 * @property {String} leafletHeatOptionsPaneSelector
 *
 * @property {String} sidebarSelector
 * @property {String} sidebarToggleSelector
 * @property {String} sidebarScrollSelector
 * @property {String} anchor
 * @property {String} edit
 */

/**
 * The sidebar of a heatmap: everything but the filters it offers, which a subclass creates in _createFilters().
 *
 * @property {HeatmapSearchBaseOptions} options
 */
class SearchInlineHeatmapBase extends SearchInlineBase {
    constructor(id, bladePath, options) {
        super(new SearchHandlerHeatmap($.extend({}, {
            loaderSelector: options.loaderSelector,
        }, options)), id, bladePath, options);

        this.sidebar = new Sidebar(options);
        this.initializing = true;

        this._draggable = null;

        // Previous search params are used to prevent searching for the same thing multiple times for no reason
        this._previousSearchParams = null;

        this.filters = this._createFilters();

        // This will allow someone to bypass all UI elements and fully control the filters through parameters
        if (this.options.passThroughEverything) {
            for (let key in this.filters) {
                // If the filter uses the DOM, tell it to not to and just pass through everything - save values internally
                if (typeof this.filters[key].setPassThrough === 'function') {
                    this.filters[key].setPassThrough(true);
                }
            }
        }

        let state = getState();
        if (state.userHasRole(USER_ROLE_ADMIN) || state.userHasRole(USER_ROLE_INTERNAL_TEAM)) {
            this.filters['minSamplesRequired'] = new SearchFilterMinSamplesRequired(this.options.filterMinSamplesRequiredSelector, this._search.bind(this), this.options.minSamplesRequiredMin, this.options.minSamplesRequiredMax);
        }

        this._setupFilterCollapseCookies();
        this._setupLeafletHeatOptions();
    }

    /**
     * @returns {Object<string, SearchFilter>} The filters this sidebar offers, keyed by the request parameter they
     * fill.
     * @protected
     */
    _createFilters() {
        console.error('SearchInlineHeatmapBase::_createFilters() must be implemented by a subclass');

        return {};
    }

    /**
     * Applies the side effects of the filters that were just restored without firing their change events. Restoring
     * a radio doesn't fire its change event, so whatever its change handler shows or hides must be applied by hand.
     *
     * @protected
     */
    _applyFilterVisibility() {

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

    _setupLeafletHeatOptions() {

        (new HeatOptionMinOpacityHandler(0, 1)).apply(this.options.leafletHeatOptionsMinOpacitySelector, {
            onFinish: this._redrawHeatmap.bind(this)
        });
        (new HeatOptionMaxZoomHandler(1, 30)).apply(this.options.leafletHeatOptionsMaxZoomSelector, {
            onFinish: this._redrawHeatmap.bind(this)
        });
        (new HeatOptionMaxHandler(0, 20)).apply(this.options.leafletHeatOptionsMaxSelector, {
            onFinish: this._redrawHeatmap.bind(this)
        });
        (new HeatOptionRadiusHandler(0, 50)).apply(this.options.leafletHeatOptionsRadiusSelector, {
            onFinish: this._redrawHeatmap.bind(this)
        });
        (new HeatOptionBlurHandler(0, 30)).apply(this.options.leafletHeatOptionsBlurSelector, {
            onFinish: this._redrawHeatmap.bind(this)
        });
        $(this.options.leafletHeatOptionsGradientSelector).on('change', this._redrawHeatmap.bind(this));
        $(this.options.leafletHeatOptionsPaneSelector).on('change', this._redrawHeatmap.bind(this));
    }

    _redrawHeatmap() {
        let options = {
            minOpacity: parseFloat($(this.options.leafletHeatOptionsMinOpacitySelector).val()),
            maxZoom: parseFloat($(this.options.leafletHeatOptionsMaxZoomSelector).val()),
            max: parseFloat($(this.options.leafletHeatOptionsMaxSelector).val()),
            radius: parseInt($(this.options.leafletHeatOptionsRadiusSelector).val()),
            blur: parseInt($(this.options.leafletHeatOptionsBlurSelector).val()),
            gradient: JSON.parse($(this.options.leafletHeatOptionsGradientSelector).val()),
            pane: $(this.options.leafletHeatOptionsPaneSelector).val(),
        };
        console.log('Redrawing heatmap', options);
        getState().getDungeonMap().pluginHeat.setOptions(options);
    }

    /**
     * Makes sure that if week 1 and 7 are selected, all weeks in between are selected as well.
     *
     * @param selector {String}
     * @protected
     */
    _selectWeeksInBetween(selector) {
        let $select = $(selector);
        let val = $select.val();
        let min = parseInt(val[0]), max = parseInt(val[val.length - 1]);
        let weeks = [];
        for (let i = min; i <= max; i++) {
            weeks.push(i);
        }
        $select.val(weeks);
    }

    /**
     *
     */
    activate() {
        super.activate();
        console.assert(this instanceof SearchInlineHeatmapBase, 'this is not a SearchInlineHeatmapBase', this);

        let self = this;

        this.map = getState().getDungeonMap();

        let $enabledState = $(this.options.enabledStateSelector);
        $enabledState.on('change', function () {
            let enabled = $(this).is(':checked');
            self._toggleHeatmap(enabled);
        });

        this._toggleHeatmap($enabledState.is(':checked'));

        this.sidebar.activate();

        if (this.options.defaultState > 1 && $('#map').width() > this.options.defaultState) {
            this.sidebar.showSidebar();
        }

        // The filters were just restored from the URL by super.activate(). Restoring a radio doesn't fire its
        // change event, so its side effects must be applied by hand - otherwise the filters that don't apply to
        // the restored value stay enabled and end up back in the URL, while their containers stay hidden.
        this._applyFilterVisibility();

        this.initializing = false;
        this._search();
    }

    searchWithFilters(filters) {
        this._restoreFiltersFromQueryParams(filters);

        // Same as in activate(): restoring a radio doesn't fire its change event, so its side effects must be applied
        // before we build the search params from the filters.
        this._applyFilterVisibility();

        this._search();

        // Make sure the select dropdowns are updated properly - external changes don't cause a UI refresh
        refreshSelectPickers();
    }

    _toggleHeatmap(enabled) {
        console.assert(this instanceof SearchInlineHeatmapBase, 'this is not a SearchInlineHeatmapBase', this);
        this.map.pluginHeat.toggle(enabled);

        Cookies.set(this.options.enabledStateCookie, (enabled ? 1 : 0) + '', cookieDefaultAttributes);
    }

    _search() {
        console.assert(this instanceof SearchInlineHeatmapBase, 'this is not a SearchInlineHeatmapBase', this);

        if (this.initializing) {
            return;
        }

        super._search({
            success: function (json) {
                getState().getDungeonMap().pluginHeat.setRawLatLngsPerFloor(
                    json.data,
                    json.data_type,
                    json.run_count,
                    json.weight_max,
                    json.grid_size_x,
                    json.grid_size_y,
                );

                if (json.hasOwnProperty('url')) {
                    console.log(json.url);
                }
            },
        }, {
            dungeonId: getState().getMapContext().getDungeon().id
        }, ['dungeonId']);
    }

    /**
     *
     */
    cleanup() {
        console.assert(this instanceof SearchInlineHeatmapBase, 'this is not a SearchInlineHeatmapBase', this);

    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchInlineHeatmapBase};
}
