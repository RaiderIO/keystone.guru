class CommonMapsHeatmapsearchsidebar extends SearchInlineBase {


    constructor(id, bladePath, options) {
        super(new SearchHandlerHeatmap($.extend({}, {
            loaderSelector: options.loaderSelector,
        }, options)), id, bladePath, options);

        this.sidebar = new Sidebar(options);

        this._draggable = null;

        // Previous search params are used to prevent searching for the same thing multiple times for no reason
        this._previousSearchParams = null;

        this.filters = {
            'event_type': new SearchFilterRadioEventType(this.options.filterEventTypeContainerSelector, this.options.filterEventTypeSelector, this._search.bind(this)),
            'data_type': new SearchFilterRadioDataType(this.options.filterDataTypeContainerSelector, this.options.filterDataTypeSelector, this._search.bind(this)),
            'level': new SearchFilterLevel(this.options.filterLevelSelector, this._search.bind(this), this.options.keyLevelMin, this.options.keyLevelMax),
            'affix_groups': new SearchFilterAffixGroups(this.options.filterAffixGroupsSelector, this._search.bind(this)),
            'affixes': new SearchFilterAffixes(this.options.filterAffixesSelector, this._search.bind(this)),
            'weekly_affix_groups': new SearchFilterWeeklyAffixGroups(this.options.filterWeeklyAffixGroupsSelector, this._search.bind(this)),
            'duration': new SearchFilterDuration(this.options.filterDurationSelector, this._search.bind(this), this.options.durationMin, this.options.durationMax),
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
        });
    }

    /**
     *
     */
    cleanup() {
        console.assert(this instanceof CommonMapsHeatmapsearchsidebar, 'this is not a CommonMapsHeatmapsearchsidebar', this);

    }
}
