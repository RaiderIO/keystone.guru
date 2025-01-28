class CommonMapsHeatmapsearchsidebar extends SearchInlineBase {


    constructor(options) {
        super(new SearchHandlerHeatmap($.extend({}, {
            loaderSelector: options.loaderSelector,
        }, options)), options);

        this.sidebar = new Sidebar(options);

        this._draggable = null;

        // Previous search params are used to prevent searching for the same thing multiple times for no reason
        this._previousSearchParams = null;

        this.filters = {
            'event_type': new SearchFilterRadioEventType(this.options.filterEventTypeContainerSelector, this.options.filterEventTypeSelector, this._search.bind(this)),
            'data_type': new SearchFilterRadioDataType(this.options.filterDataTypeContainerSelector, this.options.filterDataTypeSelector, this._search.bind(this)),
            'level': new SearchFilterLevel(this.options.filterLevelSelector, this._search.bind(this), this.options.keyLevelMin, this.options.keyLevelMax),
            'affixes': new SearchFilterAffixes(this.options.filterAffixesSelector, this._search.bind(this)),
            'weekly_affix_groups': new SearchFilterWeeklyAffixGroups(this.options.filterWeeklyAffixGroupsSelector, this._search.bind(this)),
            'duration': new SearchFilterDuration(this.options.filterDurationSelector, this._search.bind(this), this.options.durationMin, this.options.durationMax),
        };

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
        (new HeatOptionMaxZoomHandler(1, 10)).apply(this.options.leafletHeatOptionsMaxZoomSelector, {
            onFinish: this._redrawHeatmap.bind(this)
        });
        (new HeatOptionMaxHandler(0, 3)).apply(this.options.leafletHeatOptionsMaxSelector, {
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

        let clearInputFn = function () {
            $($(this).closest('.row')).find('input').val(null);

            self._search();
        };

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

        this._search();
    }

    _toggleHeatmap(enabled) {
        console.assert(this instanceof CommonMapsHeatmapsearchsidebar, 'this is not a CommonMapsHeatmapsearchsidebar', this);
        this.map.pluginHeat.toggle(enabled);

        Cookies.set(this.options.enabledStateCookie, (enabled ? 1 : 0) + '', cookieDefaultAttributes);
    }

    _search(queryParameters, options) {
        console.assert(this instanceof CommonMapsHeatmapsearchsidebar, 'this is not a CommonMapsHeatmapsearchsidebar', this);
        let self = this;

        super._search({
            success: function (json) {
                getState().getDungeonMap().pluginHeat.setRawLatLngsPerFloor(json.data);
                $(self.options.searchResultDataDungeonRoutesSelector).html(
                    json.run_count
                );

                $(self.options.searchResultSelector).css('visibility', 'visible');
            },
        }, {
            dungeon_id: getState().getMapContext().getDungeon().id
        }, ['dungeon_id']);
    }

    /**
     *
     */
    cleanup() {
        console.assert(this instanceof CommonMapsHeatmapsearchsidebar, 'this is not a CommonMapsHeatmapsearchsidebar', this);

    }
}
