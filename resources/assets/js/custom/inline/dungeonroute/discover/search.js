class DungeonrouteDiscoverSearch extends SearchInlineBase {

    constructor(options) {
        super(new SearchHandlerDungeonRoute(
            options.targetContainerSelector,
            options.loadMoreSelector,
            $.extend({}, {
                limit: options.limit,
                loaderSelector: options.loaderSelector,
            }, options)
        ), options);

        this.filters = {
            'season': new SearchFilterManualSeason(this._search.bind(this)),
            'expansion': new SearchFilterManualExpansion(this._search.bind(this)),
            'dungeons': new SearchFilterDungeons('.grid_dungeon.selectable', this._search.bind(this)),
            'title': new SearchFilterTitle('#title', this._search.bind(this)),
            'level': new SearchFilterLevel('#level', this._search.bind(this), this.options.levelMin, this.options.levelMax),
            'affixgroups': new SearchFilterAffixGroups(`.filter_affix.${this.options.currentExpansion} select`, this._search.bind(this)),
            'affixes': new SearchFilterAffixes('.select_icon.class_icon.selectable', this._search.bind(this)),
            'enemy_forces': new SearchFilterEnemyForces('#enemy_forces', this._search.bind(this)),
            'rating': new SearchFilterRating('#rating', this._search.bind(this)),
            'user': new SearchFilterUser('#user', this._search.bind(this)),
        };
    }

    /**
     */
    activate() {
        super.activate();

        let self = this;

        // Whenever the tab is changed, apply the new filter
        let $tabs = $('#search_dungeon_select_tabs a[data-toggle="tab"]').on('show.bs.tab', function (e) {
            let expansion = $(e.target).data('expansion');

            if (typeof expansion !== 'undefined') {
                self._selectSeason(null);
                self._selectExpansion(expansion);
            } else {
                let season = $(e.target).data('season');
                self._selectSeason(season);
                self._selectExpansion(null);
            }
        });

        // If we have seasons and should select one
        if (this.options.gameVersion.has_seasons && this.filters.expansion.getValue() === '') {
            // If we didn't have an expansion from the URL, select the first tab instead
            let selectedSeason = this.filters.season.getValue() ?? this.options.nextSeason ?? this.options.currentSeason;

            $(`#season-${selectedSeason}-grid-tab`).tab('show');
            this._selectSeason(selectedSeason);
            this._selectExpansion(null);
        } else {
            // If we didn't have an expansion from the URL, select the first tab instead (we don't have seasons so 0 is correct)
            let selectedExpansion = this.filters.expansion.getValue() !== '' ?
                this.filters.expansion.getValue() :
                $($tabs[0]).data('expansion');

            $(`#${selectedExpansion}-grid-tab`).tab('show');
            this._selectSeason(null);
            this._selectExpansion(selectedExpansion);
        }

        this.initialized = true;

        // Show some not very useful routes to get people to start using the filters
        this._search();
    }

    /**
     *
     * @param expansion {String|null}
     * @private
     */
    _selectExpansion(expansion) {
        if (expansion !== null) {
            $(`#search_dungeon .grid_dungeon`).removeClass('selectable');
            $(`#${expansion}-grid-content .grid_dungeon`).addClass('selectable');

            // Update the affix group list
            this.filters.affixgroups.options.selector = `.filter_affix.${expansion} select`;
            this.filters.affixgroups.activate();

            $(`.filter_affix`).hide().filter(`.${expansion}`).show();
        }
        this.filters.expansion.setValue(expansion);
    }

    /**
     *
     * @param season {String|null}
     * @private
     */
    _selectSeason(season) {
        if (season !== null) {
            $(`#search_dungeon .grid_dungeon`).removeClass('selectable');
            $(`#season-${season}-grid-content .grid_dungeon`).addClass('selectable');
        }

        // Update the affix group list
        this.filters.affixgroups.options.selector = `.filter_affix.${this.options.currentExpansion} select`;
        this.filters.affixgroups.activate();

        $(`.filter_affix`).hide().filter(`.${this.options.currentExpansion}`).show();

        this.filters.season.setValue(season);
    }

    _search(options = {}, queryParameters = {}) {
        if (!this.initialized) {
            return;
        }

        return super._search();
    }

    cleanup() {
    }
}
