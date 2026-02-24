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
            'includedEnemies': new SearchFilterIncludedEnemies(this._search.bind(this)),
            'excludedEnemies': new SearchFilterExcludedEnemies(this._search.bind(this)),
            // 'offset': new SearchFilterInputText(this.options.filterOffsetSelector, this._search.bind(this)),
            // 'offset': new SearchFilterInputText(this.options.filterOffsetSelector, this._search.bind(this)),
        };

        this.dungeonRouteCache = {};


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
        // Set the filter values prior to activating them (can't do this before since the map won't be initialized)
        let enemyMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        this.filters.includedEnemies.enemyMapObjectGroup = enemyMapObjectGroup;
        this.filters.excludedEnemies.enemyMapObjectGroup = enemyMapObjectGroup;

        super.activate();
        console.assert(this instanceof CommonMapsDungeonroutesearchsidebar, 'this is not a CommonMapsDungeonroutesearchsidebar', this);

        this.map = getState().getDungeonMap();

        let $enabledState = $(this.options.enabledStateSelector);
        $enabledState.on('change', function () {
            let enabled = $(this).is(':checked');
            // @TODO Implement
        });


        this.sidebar.activate();

        if (this.options.defaultState > 1 && $('#map').width() > this.options.defaultState) {
            this.sidebar.showSidebar();
        }

        this.initializing = false;
        this._search();

        this.enemySelectionMapState = new DungeonRouteSearchEnemySelection(this.map, null);
        this.map.setMapState(
            this.enemySelectionMapState
        );
        this.enemySelectionMapState.register('enemyselection:enemyselected', this, this._enemySelected.bind(this));
    }

    searchWithFilters(filters) {
        this._restoreFiltersFromQueryParams(filters);

        this._search();

        // Make sure the select dropdowns are updated properly - external changes don't cause a UI refresh
        refreshSelectPickers();
    }


    /**
     * Triggered when an enemy was selected by the user when edit mode was enabled.
     * @param enemySelectedEvent {object} The event that was triggered when an enemy was selected (or de-selected).
     * Will add/remove the enemy to the list to be redrawn.
     */
    _enemySelected(enemySelectedEvent) {
        let enemy = enemySelectedEvent.data.enemy;
        let ignorePackBuddies = enemySelectedEvent.data.ignorePackBuddies;
        console.assert(enemy instanceof Enemy, 'enemy is not an Enemy', enemy);

        // Only when we're saved
        if (this.id === 0) {
            console.warn('Not handling _enemySelected; killzone not (yet) saved!', this, enemy.id);
            return;
        }

        // If the enemy was part of a pack..
        if (enemy.enemy_pack_id !== 0 && !ignorePackBuddies) {
            let packBuddies = enemy.getPackBuddies();
            packBuddies.push(enemy);
            // Add all enemies in the pack to this killzone as well
            for (let i = 0; i < packBuddies.length; i++) {
                let packBuddy = packBuddies[i];
                // If we should couple the enemy in addition to our own..
                if (packBuddy.enemy_pack_id === enemy.enemy_pack_id) {
                    // Remove it too if we should
                    this._toggleEnemy(packBuddy);
                }
            }
        } else {
            this._toggleEnemy(enemy);
        }
    }

    /**
     *
     * @param {Enemy} enemy
     * @private
     */
    _toggleEnemy(enemy) {
        // 3 states - overpulled, obsolete, normal
        if (enemy.getOverpulledKillZoneId() === null && !enemy.isObsolete()) {
            enemy.setOverpulledKillZoneId(1);
        } else if (!enemy.isObsolete()) {
            enemy.setOverpulledKillZoneId(null);
            enemy.setObsolete(true);
        } else {
            enemy.setObsolete(false);
        }
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
                    // User clicked the radio button
                    $($routeRow.find('.apply_route_radio')).on('click', function (event) {
                        let $this = $(this);
                        self._loadDungeonRoute($this.closest('.card_dungeonroute.horizontal'), $this.data('publickey'));

                        event.preventDefault();
                    });
                    // User clicked the route title
                    $($routeRow.find('.apply_route')).on('click', function (event) {
                        let $this = $(this);
                        self._loadDungeonRoute($this.closest('.card_dungeonroute.horizontal'), $this.data('publickey'));

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
     * @param $card {jQuery}
     * @param publicKey {String}
     * @private
     */
    _loadDungeonRoute($card, publicKey) {
        let mapContext = getState().getMapContext();
        let unset = mapContext.getDungeonRoute()?.publicKey === publicKey;

        // Reset to empty circles
        $('.apply_route_radio').find('i').removeClass('fa-dot-circle').addClass('fa-circle');
        if (!unset) {
            // Apply the dot circle to this row
            $card.find('.apply_route_radio i').removeClass('fa-circle').addClass('fa-dot-circle');
        }

        // Reset borders on card
        $('.card_dungeonroute.horizontal').removeClass('border-primary border-2').addClass('border-dark border-1');
        if (!unset) {
            // Apply borders to card
            $card.removeClass('border-dark').addClass('border-primary border-2');
        }

        if (this.dungeonRouteCache[publicKey]) {
            if (unset) {
                mapContext.setDungeonRoute(null)
            } else {
                mapContext.setDungeonRoute(this.dungeonRouteCache[publicKey]);
            }
            return;
        }

        let self = this;
        $.ajax({
            type: 'GET',
            url: `/ajax/dungeonroute/${publicKey}/mapcontext`,
            success: function (json) {
                mapContext.setDungeonRoute(
                    json
                );

                self.dungeonRouteCache[publicKey] = json;
            }
        });
    }
}
