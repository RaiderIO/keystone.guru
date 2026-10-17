// ---------------------------------------------------------------------------
// `dungeonroutesearchsidebar.js` is concatenated into a bundle in the browser and
// references its collaborators as bare globals, so they must be on `globalThis`
// before the class body is evaluated (same pattern as picker.test.js).
//
// The search results are rendered against a real jQuery over jsdom markup; the map
// side (Sidebar, the enemy filters, the map context) is stubbed, since jsdom has no map.
// ---------------------------------------------------------------------------

const fs         = require('node:fs');
const path       = require('node:path');
const jQuery     = require('jquery');
const Handlebars = require('handlebars');
const Lang       = require('lang.js');

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {SearchInlineBase}    = require('../../base/searchinlinebase');
globalThis.SearchInlineBase = SearchInlineBase;
globalThis.SearchHandler    = require('../search/searchhandler').SearchHandler;
globalThis.SearchHandlerDungeonRouteSearch = require('../search/searchhandlerdungeonroutesearch').SearchHandlerDungeonRouteSearch;
globalThis.PickerDungeonRoute = require('../dungeonroute/pickerdungeonroute').PickerDungeonRoute;
globalThis.Sidebar                     = class {};
globalThis.SearchFilterMythicLevel     = class {};
globalThis.SearchFilterTitle           = class {};
globalThis.SearchFilterUser            = class {};
globalThis.SearchFilterIncludedEnemies = class {};
globalThis.SearchFilterExcludedEnemies = class {};

const {CommonMapsDungeonroutesearchsidebar} = require('./dungeonroutesearchsidebar');

const MESSAGES = {
    'en.js':       {
        search_results_label:                  'Search results',
        no_search_results_label:               'No search results found',
        dungeonroute_picker_already_in_label:  'Already added',
        dungeonroute_picker_unpublished_label: 'Unpublished',
        dungeonroute_picker_key_level:         '+:level',
        dungeonroute_picker_key_range:         '+:min - +:max',
        dungeonroute_picker_enemy_forces:      ':count/:required',
        dungeonroute_picker_pulls_one:         '1 pull',
        dungeonroute_picker_pulls_many:        ':count pulls',
        dungeonroute_picker_views:             ':count views',
        dungeonroute_picker_votes:             ':count votes',
    },
    'en.dungeons': {ara_kara: 'Ara-Kara'},
};

const OPTIONS = {
    sidebarSearchResultSelector: '#dungeonroute_search_routes_container',
    enabledStateCookie:          'dungeonroute_search_enabled',
    enabledStateSelector:        '#dungeonroute_search_toggle',
    fallbackImageBaseUrl:        'https://assets/images',
};

/**
 * @param {string} publicKey
 * @returns {Object} A route as the search endpoint lists it.
 */
function route(publicKey) {
    return {
        public_key:                    publicKey,
        title:                         `Route ${publicKey}`,
        published:                     'world',
        level_min:                     2,
        level_max:                     10,
        teeming:                       0,
        views:                         1500,
        rating:                        8,
        rating_count:                  4,
        enemy_forces:                  310,
        enemy_forces_required:         300,
        enemy_forces_required_teeming: 350,
        pull_forces:                   [{enemy_forces: 40, has_boss: false}, {enemy_forces: 0, has_boss: true}],
        has_thumbnail:                 false,
        thumbnails:                    [],
        dungeon:                       {id: 3, name: 'dungeons.ara_kara', key: 'arakara', expansion: {key: 'tww'}},
    };
}

describe('CommonMapsDungeonroutesearchsidebar', () => {
    let previousJquery;
    let previousGetState;
    const templatesDir = path.join(__dirname, '../../../../handlebars');
    let ajaxCalls;
    let mapContext;
    let sidebar;

    beforeEach(() => {
        previousJquery   = globalThis.$;
        previousGetState = globalThis.getState;
        globalThis.$     = jQuery;

        globalThis.lang = new Lang({messages: MESSAGES, locale: 'en'});
        globalThis.getHandlebarsDefaultVariables = () => MESSAGES['en.js'];
        globalThis.Handlebars                    = Handlebars;
        Handlebars.templates = {};
        ['dungeonroute_picker_row', 'map_sidebar_dungeon_route_search_results'].forEach((name) => {
            Handlebars.templates[name] = Handlebars.compile(
                fs.readFileSync(path.join(templatesDir, `${name}.handlebars`), 'utf8'),
            );
        });

        ajaxCalls = [];
        jQuery.ajax = vi.fn((settings) => {
            ajaxCalls.push(settings);
        });

        let shownRoute = null;
        mapContext     = {
            getDungeonRoute: vi.fn(() => shownRoute),
            setDungeonRoute: vi.fn((json) => {
                shownRoute = json;
            }),
        };
        globalThis.getState = () => ({getMapContext: () => mapContext});
        globalThis.Cookies = {set: vi.fn()};
        globalThis.cookieDefaultAttributes = {path: '/'};

        document.body.innerHTML = '<div id="dungeonroute_search_routes_container"></div>';

        sidebar = new CommonMapsDungeonroutesearchsidebar(
            'sidebar', 'common/maps/dungeonroutesearchsidebar', Object.assign({}, OPTIONS),
        );
    });

    afterEach(() => {
        globalThis.$        = previousJquery;
        globalThis.getState = previousGetState;
        document.body.innerHTML = '';
    });

    /**
     * Answers the most recent map context request.
     * @param {string} publicKey
     */
    function respondWithMapContext(publicKey) {
        ajaxCalls[ajaxCalls.length - 1].success({publicKey: publicKey});
    }

    /**
     * @param {string} publicKey
     * @returns {jQuery}
     */
    function row(publicKey) {
        return jQuery(`.route_picker_row[data-public-key="${publicKey}"]`);
    }

    /**
     * Activates the sidebar on a page whose Enabled/Disabled toggle starts in the given state.
     * @param {boolean} enabled
     */
    function activateWithToggle(enabled) {
        jQuery('body').append(`<input id="dungeonroute_search_toggle" type="checkbox" ${enabled ? 'checked' : ''}>`);
        vi.spyOn(SearchInlineBase.prototype, 'activate').mockImplementation(() => {
        });
        vi.spyOn(sidebar, '_search').mockImplementation(() => {
        });
        globalThis.getEnemyMapObjectGroup = () => ({});
        globalThis.DungeonRouteSearchEnemySelection = class {
            register() {
            }
        };
        const dungeonMap = {setMapState: vi.fn()};
        globalThis.getState = () => ({getMapContext: () => mapContext, getDungeonMap: () => dungeonMap});
        sidebar.sidebar = {activate: vi.fn()};

        sidebar.activate();
    }

    it('_renderSearchResults_givenRoutes_rendersAPickerRowWithARadioPerRoute', () => {
        // Arrange
        const rows = [route('a'), route('b')];

        // Act
        sidebar._renderSearchResults(rows);

        // Assert
        const $rows = jQuery('.search_results .route_picker_row');
        expect($rows.length).toBe(2);
        expect($rows.first().find('.route_picker_title').text()).toBe('Route a');
        expect($rows.first().find('.route_picker_dungeon').text()).toBe('Ara-Kara');
        const $inputs = $rows.find('.route_picker_checkbox');
        expect($inputs.toArray().map(input => input.type)).toEqual(['radio', 'radio']);
        expect($inputs.toArray().map(input => input.name)).toEqual(['dungeonroute_search_route', 'dungeonroute_search_route']);
    });

    it('_renderSearchResults_givenNull_saysNothingWasFoundAndClearsTheShownRoute', () => {
        // Arrange
        mapContext.setDungeonRoute({publicKey: 'a'});

        // Act
        sidebar._renderSearchResults(null);

        // Assert
        expect(jQuery('#dungeonroute_search_routes_container h5').text()).toBe('No search results found');
        expect(jQuery('.route_picker_row').length).toBe(0);
        expect(mapContext.getDungeonRoute()).toBeNull();
    });

    it('clickingARow_givenNoShownRoute_loadsAndSelectsThatRoute', () => {
        // Arrange
        sidebar._renderSearchResults([route('a'), route('b')]);

        // Act
        row('b').find('.route_picker_checkbox').trigger('click');
        respondWithMapContext('b');

        // Assert
        expect(ajaxCalls[ajaxCalls.length - 1].url).toBe('/ajax/dungeonroute/b/mapcontext');
        expect(mapContext.getDungeonRoute()).toEqual({publicKey: 'b'});
        expect(row('b').hasClass('route_picker_row_selected')).toBe(true);
        expect(row('b').find('.route_picker_checkbox').prop('checked')).toBe(true);
        expect(row('a').hasClass('route_picker_row_selected')).toBe(false);
    });

    it('clickingARow_givenThatRouteIsShown_unselectsIt', () => {
        // Arrange
        sidebar._renderSearchResults([route('a')]);
        row('a').find('.route_picker_checkbox').trigger('click');
        respondWithMapContext('a');

        // Act
        row('a').find('.route_picker_checkbox').trigger('click');

        // Assert
        expect(mapContext.getDungeonRoute()).toBeNull();
        expect(row('a').hasClass('route_picker_row_selected')).toBe(false);
        expect(row('a').find('.route_picker_checkbox').prop('checked')).toBe(false);
    });

    it('_renderSearchResults_givenTheShownRouteIsListedAgain_keepsItSelected', () => {
        // Arrange
        sidebar._renderSearchResults([route('a'), route('b')]);
        row('a').find('.route_picker_checkbox').trigger('click');
        respondWithMapContext('a');
        const requestCount = ajaxCalls.length;

        // Act
        sidebar._renderSearchResults([route('b'), route('a')]);

        // Assert
        expect(mapContext.getDungeonRoute()).toEqual({publicKey: 'a'});
        expect(row('a').hasClass('route_picker_row_selected')).toBe(true);
        expect(row('a').find('.route_picker_checkbox').prop('checked')).toBe(true);
        // The route's map context is cached, so it is not fetched again
        expect(ajaxCalls.length).toBe(requestCount);
    });

    it('changingTheToggle_givenAShownRouteAndSwitchedOff_hidesItFromTheMapButKeepsThePick', () => {
        // Arrange
        activateWithToggle(true);
        sidebar._renderSearchResults([route('a')]);
        row('a').find('.route_picker_checkbox').trigger('click');
        respondWithMapContext('a');

        // Act
        jQuery('#dungeonroute_search_toggle').prop('checked', false).trigger('change');

        // Assert
        expect(mapContext.getDungeonRoute()).toBeNull();
        expect(row('a').hasClass('route_picker_row_selected')).toBe(true);
        expect(row('a').find('.route_picker_checkbox').prop('checked')).toBe(true);
        expect(Cookies.set).toHaveBeenLastCalledWith('dungeonroute_search_enabled', '0', {path: '/'});
    });

    it('changingTheToggle_givenAPickedRouteAndSwitchedBackOn_showsThePickedRouteAgain', () => {
        // Arrange
        activateWithToggle(true);
        sidebar._renderSearchResults([route('a'), route('b')]);
        row('b').find('.route_picker_checkbox').trigger('click');
        respondWithMapContext('b');
        jQuery('#dungeonroute_search_toggle').prop('checked', false).trigger('change');
        const requestCount = ajaxCalls.length;

        // Act
        jQuery('#dungeonroute_search_toggle').prop('checked', true).trigger('change');

        // Assert
        expect(mapContext.getDungeonRoute()).toEqual({publicKey: 'b'});
        expect(ajaxCalls.length).toBe(requestCount);
        expect(Cookies.set).toHaveBeenLastCalledWith('dungeonroute_search_enabled', '1', {path: '/'});
    });

    it('clickingARow_givenTheToggleStartedOff_picksTheRouteWithoutShowingIt', () => {
        // Arrange
        activateWithToggle(false);
        sidebar._renderSearchResults([route('a')]);

        // Act
        row('a').find('.route_picker_checkbox').trigger('click');

        // Assert
        expect(row('a').hasClass('route_picker_row_selected')).toBe(true);
        expect(ajaxCalls.length).toBe(0);
        expect(mapContext.getDungeonRoute()).toBeNull();
    });

    it('changingTheToggle_givenNoPickedRoute_leavesTheMapAlone', () => {
        // Arrange
        activateWithToggle(true);
        sidebar._renderSearchResults([route('a')]);
        mapContext.setDungeonRoute.mockClear();

        // Act
        jQuery('#dungeonroute_search_toggle').prop('checked', false).trigger('change');
        jQuery('#dungeonroute_search_toggle').prop('checked', true).trigger('change');

        // Assert
        expect(mapContext.setDungeonRoute).not.toHaveBeenCalled();
        expect(ajaxCalls.length).toBe(0);
    });

    it('respondingWithARoute_givenTheToggleSwitchedOffWhileItLoaded_doesNotShowIt', () => {
        // Arrange
        activateWithToggle(true);
        sidebar._renderSearchResults([route('a')]);
        row('a').find('.route_picker_checkbox').trigger('click');
        jQuery('#dungeonroute_search_toggle').prop('checked', false).trigger('change');

        // Act
        respondWithMapContext('a');

        // Assert
        expect(mapContext.getDungeonRoute()).toBeNull();
    });

    it('respondingWithARoute_givenAnotherRoutePickedWhileItLoaded_showsOnlyTheLatestPick', () => {
        // Arrange
        sidebar._renderSearchResults([route('a'), route('b')]);
        row('a').find('.route_picker_checkbox').trigger('click');
        const routeARequest = ajaxCalls[ajaxCalls.length - 1];
        row('b').find('.route_picker_checkbox').trigger('click');
        respondWithMapContext('b');

        // Act
        routeARequest.success({publicKey: 'a'});

        // Assert
        expect(mapContext.getDungeonRoute()).toEqual({publicKey: 'b'});
        expect(row('b').hasClass('route_picker_row_selected')).toBe(true);
    });

    it('_search_givenStillInitializing_doesNotSearch', () => {
        // Arrange
        const baseSearch = vi.spyOn(SearchInlineBase.prototype, '_search').mockImplementation(() => {
        });

        // Act
        sidebar._search();

        // Assert
        expect(baseSearch).not.toHaveBeenCalled();
    });

    it.each([
        [200, 1],
        [204, 0],
    ])('_search_givenResponseStatus%s_rendersTheRowsOnlyForA200', (status, expectedRowCount) => {
        // Arrange
        const baseSearch = vi.spyOn(SearchInlineBase.prototype, '_search').mockImplementation(() => {
        });
        sidebar.initializing = false;
        sidebar._search();
        const [options, queryParameters, blacklist] = baseSearch.mock.calls[0];

        // Act
        options.success([route('a')], 'success', {status: status});

        // Assert
        expect(baseSearch).toHaveBeenCalledTimes(1);
        expect(queryParameters).toEqual({});
        expect(blacklist).toEqual(['dungeonId']);
        expect(jQuery('.route_picker_row').length).toBe(expectedRowCount);
    });

    it('_toggleEnemy_givenRepeatedToggles_cyclesFromNormalToOverpulledToObsoleteAndBack', () => {
        // Arrange
        const enemy = {
            overpulledKillZoneId:    null,
            obsolete:                false,
            getOverpulledKillZoneId() {
                return this.overpulledKillZoneId;
            },
            setOverpulledKillZoneId(killZoneId) {
                this.overpulledKillZoneId = killZoneId;
            },
            isObsolete() {
                return this.obsolete;
            },
            setObsolete(obsolete) {
                this.obsolete = obsolete;
            },
        };
        const states = [];

        // Act
        for (let i = 0; i < 3; i++) {
            sidebar._toggleEnemy(enemy);
            states.push({overpulledKillZoneId: enemy.overpulledKillZoneId, obsolete: enemy.obsolete});
        }

        // Assert
        expect(states).toEqual([
            {overpulledKillZoneId: 1, obsolete: false},
            {overpulledKillZoneId: null, obsolete: true},
            {overpulledKillZoneId: null, obsolete: false},
        ]);
    });

    it.each([
        [false, ['selected', 'same pack']],
        [true, ['selected']],
    ])('_enemySelected_givenIgnorePackBuddies%s_togglesTheEnemyAndOnlyItsOwnPackBuddies', (ignorePackBuddies, expectedToggled) => {
        // Arrange
        globalThis.Enemy = class {};
        const samePackBuddy  = {name: 'same pack', enemy_pack_id: 5};
        const otherPackEnemy = {name: 'other pack', enemy_pack_id: 6};
        const enemy          = Object.assign(new Enemy(), {
            name:            'selected',
            enemy_pack_id:   5,
            getPackBuddies: () => [samePackBuddy, otherPackEnemy],
        });
        const toggled = [];
        vi.spyOn(sidebar, '_toggleEnemy').mockImplementation((toggledEnemy) => toggled.push(toggledEnemy.name));

        // Act
        sidebar._enemySelected({data: {enemy: enemy, ignorePackBuddies: ignorePackBuddies}});

        // Assert
        expect(toggled.sort()).toEqual([...expectedToggled].sort());
    });

    it('_renderSearchResults_givenTheShownRouteIsNoLongerListed_clearsTheShownRoute', () => {
        // Arrange
        sidebar._renderSearchResults([route('a')]);
        row('a').find('.route_picker_checkbox').trigger('click');
        respondWithMapContext('a');

        // Act
        sidebar._renderSearchResults([route('b')]);

        // Assert
        expect(mapContext.getDungeonRoute()).toBeNull();
        expect(jQuery('.route_picker_row_selected').length).toBe(0);
    });
});
