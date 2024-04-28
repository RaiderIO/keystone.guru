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
            'level': new SearchFilterLevel('#filter_level', this._search.bind(this), this.options.levelMin, this.options.levelMax),
            'affix_groups': new SearchFilterAffixGroups(`#filter_affixes`, this._search.bind(this)),
            'affixes': new SearchFilterAffixes('.select_icon.class_icon.selectable', this._search.bind(this)),
        };
    }


    /**
     *
     */
    activate() {
        super.activate();

        console.assert(this instanceof CommonMapsHeatmapsearchsidebar, 'this is not a CommonMapsHeatmapsearchsidebar', this);

        this.map = getState().getDungeonMap();

        let clearInputFn = function () {
            $($(this).closest('.row')).find('input').val(null);
        };

        $(this.options.filterDateFromClearBtnSelector).bind('click', clearInputFn)
        $(this.options.filterDateToClearBtnSelector).bind('click', clearInputFn);

        this.sidebar.activate();

        if (this.options.defaultState > 1 && $('#map').width() > this.options.defaultState) {
            this.sidebar.showSidebar();
        }

        this._search();
    }

    _search(queryParameters, options) {
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
