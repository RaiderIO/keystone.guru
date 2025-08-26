/**
 * @typedef {Object} HeatmapSearchOptions
 * @property {String} stateCookie
 * @property {Number} defaultState
 * @property {Boolean} hideOnMove
 * @property {String} currentFiltersSelector
 * @property {String} loaderSelector
 * @property {Boolean} passThroughEverything
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
    constructor(options) {
        super(new SearchHandlerHeatmap(options), options);

        let self = this;

        this.sidebar = new Sidebar(options);
        this.initializing = true;

        this._draggable = null;

        // Previous search params are used to prevent searching for the same thing multiple times for no reason
        this._previousSearchParams = null;

        this.filters = {
            'type': new SearchFilterRadioEventType(this.options.filterEventTypeContainerSelector, this.options.filterEventTypeSelector, function () {
                let $this = $(`${self.options.filterEventTypeSelector}:checked`);

                let isNpcDeath = $this.val() === COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH;
                $(self.options.filterDataTypeContainerSelector).toggle(isNpcDeath);
                self.filters['dataType'].toggle(isNpcDeath);


                let isPlayerDeath = $this.val() === COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH;
                $(self.options.filterClassesPlayerDeathsContainerSelector).toggle(isPlayerDeath);
                $(self.options.filterSpecializationsPlayerDeathsContainerSelector).toggle(isPlayerDeath);
                self.filters['includePlayerDeathClassIds'].toggle(isPlayerDeath);
                self.filters['includePlayerDeathSpecIds'].toggle(isPlayerDeath);

                self._search();
            }),
            'dataType': new SearchFilterRadioDataType(this.options.filterDataTypeContainerSelector, this.options.filterDataTypeSelector, this._search.bind(this)),
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
            'includeSpecIds': new SearchFilterSpecializations(this.options.filterSpecializationsSelector, self._search.bind(this)),
            'includePlayerDeathClassIds': new SearchFilterClassesPlayerDeaths(this.options.filterClassesPlayerDeathsSelector, this._search.bind(this)),
            'includePlayerDeathSpecIds': new SearchFilterSpecializationsPlayerDeaths(this.options.filterSpecializationsPlayerDeathsSelector, self._search.bind(this)),
            'duration': new SearchFilterDuration(this.options.filterDurationSelector, this._search.bind(this), this.options.durationMin, this.options.durationMax),

            'excludeSpecIds': new SearchFilterPassThrough(),
            'excludeClassIds': new SearchFilterPassThrough(),
            'excludeAffixIds': new SearchFilterPassThrough(),
            'excludePlayerDeathSpecIds': new SearchFilterPassThrough(),
            'excludePlayerDeathClassIds': new SearchFilterPassThrough(),
            'includePlayerSpellIds': new SearchFilterPassThrough(),
            'token': new SearchFilterPassThrough(),
        };

        // This will allow someone to bypass all UI elements and fully control the filters through parameters
        if (this.options.passThroughEverything) {
            for (let key in this.filters) {
                this.filters[key] = new SearchFilterPassThrough();
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

        this.initializing = false;
        this._search();
    }

    searchWithFilters(filters) {
        this._restoreFiltersFromQueryParams(filters);

        this._search();

        // Make sure the select dropdowns are updated properly - external changes don't cause a UI refresh
        refreshSelectPickers();
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
