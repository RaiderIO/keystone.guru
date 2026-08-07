/**
 * @typedef {Object} HeatmapSearchOptions
 * @property {String} stateCookie
 * @property {Number} defaultState
 * @property {Boolean} hideOnMove
 * @property {String} currentFiltersSelector
 * @property {String} loaderSelector
 * @property {Boolean} passThroughEverything
 * @property {Boolean} showDataSourceSnackbar
 *
 * @property {String} keyLevelSelector
 * @property {Number} keyLevelMin
 * @property {Number} keyLevelMax
 * @property {Number} itemLevelMin
 * @property {Number} itemLevelMax
 * @property {Number} playerDeathsMin
 * @property {Number} playerDeathsMax
 * @property {Number} durationMin
 * @property {Number} durationMax
 *
 * @property {String} enabledStateCookie
 * @property {String} enabledStateSelector
 *
 * @property {String} filterEventTypeContainerSelector
 * @property {String} filterEventTypeSelector
 * @property {String} filterDataTypeContainerSelector
 * @property {String} filterDataTypeSelector
 * @property {String} filterPlayerSpellsContainerSelector
 * @property {String} filterPlayerSpellsSelector
 * @property {String} filterRegionContainerSelector
 * @property {String} filterRegionSelector
 * @property {String} filterKeyLevelSelector
 * @property {String} filterItemLevelSelector
 * @property {String} filterPlayerDeathsSelector
 * @property {String} filterAffixesSelector
 * @property {String} filterWeeklyAffixGroupsSelector
 * @property {String} filterClassesSelector
 * @property {String} filterSpecializationsSelector
 * @property {String} filterClassesPlayerDeathsContainerSelector
 * @property {String} filterClassesPlayerDeathsSelector
 * @property {String} filterSpecializationsPlayerDeathsContainerSelector
 * @property {String} filterSpecializationsPlayerDeathsSelector
 * @property {String} filterDurationSelector
 * @property {String} filterMinSamplesRequiredSelector
 *
 * @property {String} filterCollapseNames
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
 *
 * @property {String[]} filterCollapseNames
 */

/**
 * @property {HeatmapSearchOptions} options
 */
class CommonMapsHeatmapsearchsidebar extends SearchInlineBase {
    constructor(id, bladePath, options) {
        super(new SearchHandlerHeatmap($.extend({}, {
            loaderSelector: options.loaderSelector,
        }, options)), id, bladePath, options);

        let self = this;

        this.sidebar = new Sidebar(options);
        this.initializing = true;

        this._draggable = null;

        // Previous search params are used to prevent searching for the same thing multiple times for no reason
        this._previousSearchParams = null;

        this.filters = {
            'type': new SearchFilterRadioEventType(this.options.filterEventTypeContainerSelector, this.options.filterEventTypeSelector, function () {
                self._applyEventTypeVisibility();

                self._search();
            }),
            'dataType': new SearchFilterRadioDataType(this.options.filterDataTypeContainerSelector, this.options.filterDataTypeSelector, this._search.bind(this)),
            'includePlayerSpellIds':new SearchFilterPlayerSpells(this.options.filterPlayerSpellsSelector, this._search.bind(this)),
            'region': new SearchFilterRadioRegion(this.options.filterRegionContainerSelector, this.options.filterRegionSelector, this._search.bind(this)),
            'keyLevel': new SearchFilterMythicLevel(this.options.filterKeyLevelSelector, this._search.bind(this), this.options.keyLevelMin, this.options.keyLevelMax),
            'itemLevel': new SearchFilterItemLevel(this.options.filterItemLevelSelector, this._search.bind(this), this.options.itemLevelMin, this.options.itemLevelMax),
            'playerDeaths': new SearchFilterPlayerDeaths(this.options.filterPlayerDeathsSelector, this._search.bind(this), this.options.playerDeathsMin, this.options.playerDeathsMax),
            'includeAffixIds': new SearchFilterAffixes(this.options.filterAffixesSelector, this._search.bind(this)),
            'weeklyAffixGroups': new SearchFilterWeeklyAffixGroups(this.options.filterWeeklyAffixGroupsSelector, function () {
                // Make sure that if we select week 1 and 7, we select all weeks in between as well
                let $select = $(self.options.filterWeeklyAffixGroupsSelector);
                let val = $select.val();
                let min = parseInt(val[0]), max = parseInt(val[val.length - 1]);
                let weeks = [];
                for (let i = min; i <= max; i++) {
                    weeks.push(i);
                }
                $select.val(weeks);

                self._search();
            }),
            'includeClassIds': new SearchFilterClasses(this.options.filterClassesSelector, this._search.bind(this)),
            'includeSpecIds': new SearchFilterSpecializations(this.options.filterSpecializationsSelector, this._search.bind(this)),
            'includePlayerDeathClassIds': new SearchFilterClassesPlayerDeaths(this.options.filterClassesPlayerDeathsSelector, this._search.bind(this)),
            'includePlayerDeathSpecIds': new SearchFilterSpecializationsPlayerDeaths(this.options.filterSpecializationsPlayerDeathsSelector, this._search.bind(this)),
            'duration': new SearchFilterDuration(this.options.filterDurationSelector, this._search.bind(this), this.options.durationMin, this.options.durationMax),

            'excludeSpecIds': new SearchFilterPassThrough(),
            'excludeClassIds': new SearchFilterPassThrough(),
            'excludeAffixIds': new SearchFilterPassThrough(),
            'excludePlayerDeathSpecIds': new SearchFilterPassThrough(),
            'excludePlayerDeathClassIds': new SearchFilterPassThrough(),
            // 'includePlayerSpellIds': new SearchFilterPassThrough(),
            'showSidebar': new SearchFilterPassThrough(),
            'token': new SearchFilterPassThrough(),
            'season': new SearchFilterPassThrough(),
        };

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
     *
     */
    activate() {
        super.activate();
        console.assert(this instanceof CommonMapsHeatmapsearchsidebar, 'this is not a CommonMapsHeatmapsearchsidebar', this);

        let self = this;

        this.map = getState().getDungeonMap();

        // let clearInputFn = function () {
        //     $($(this).closest('.row')).find('input').val(null);
        //
        //     self._search();
        // };

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
        // change event, so the event type's side effects (which filters apply, which containers are visible)
        // must be applied by hand - otherwise the filters that don't apply to the restored event type stay
        // enabled and end up back in the URL, while their containers stay hidden (#3744).
        this._applyEventTypeVisibility();

        this.initializing = false;
        this._search();
    }

    searchWithFilters(filters) {
        this._restoreFiltersFromQueryParams(filters);

        // Same as in activate(): restoring the event type doesn't fire its change event, so its side effects
        // must be applied before we build the search params from the filters (#3744).
        this._applyEventTypeVisibility();

        this._search();

        // Make sure the select dropdowns are updated properly - external changes don't cause a UI refresh
        refreshSelectPickers();
    }

    /**
     * Enables/disables the filters that only apply to a specific event type, and shows/hides their containers
     * (which the blade renders hidden by default).
     *
     * @protected
     */
    _applyEventTypeVisibility() {
        console.assert(this instanceof CommonMapsHeatmapsearchsidebar, 'this is not a CommonMapsHeatmapsearchsidebar', this);

        // Pass through mode means the sidebar is hidden entirely (see 'passThroughEverything' in
        // heatmapsearch.blade.php) and the filters are fully controlled by whoever embedded us - there's no UI
        // to keep in sync, so don't disable any of the filters they passed based on the event type.
        if (this.options.passThroughEverything) {
            return;
        }

        let eventType = this.filters['type'].getValue();

        let isNpcDeath = eventType === COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH;
        $(this.options.filterDataTypeContainerSelector).toggle(isNpcDeath);
        this.filters['dataType'].toggle(isNpcDeath);


        let isPlayerDeath = eventType === COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH;
        $(this.options.filterClassesPlayerDeathsContainerSelector).toggle(isPlayerDeath);
        $(this.options.filterSpecializationsPlayerDeathsContainerSelector).toggle(isPlayerDeath);
        this.filters['includePlayerDeathClassIds'].toggle(isPlayerDeath);
        this.filters['includePlayerDeathSpecIds'].toggle(isPlayerDeath);


        let isPlayerSpell = eventType === COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_SPELL;
        $(this.options.filterPlayerSpellsContainerSelector).toggle(isPlayerSpell);
        this.filters['includePlayerSpellIds'].toggle(isPlayerSpell);
    }

    _toggleHeatmap(enabled) {
        console.assert(this instanceof CommonMapsHeatmapsearchsidebar, 'this is not a CommonMapsHeatmapsearchsidebar', this);
        this.map.pluginHeat.toggle(enabled);

        Cookies.set(this.options.enabledStateCookie, (enabled ? 1 : 0) + '', cookieDefaultAttributes);
    }

    _search() {
        console.assert(this instanceof CommonMapsHeatmapsearchsidebar, 'this is not a CommonMapsHeatmapsearchsidebar', this);

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
                    json.grid_size_x ?? null,
                    json.grid_size_y ?? null,
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
        console.assert(this instanceof CommonMapsHeatmapsearchsidebar, 'this is not a CommonMapsHeatmapsearchsidebar', this);

    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonMapsHeatmapsearchsidebar};
}
