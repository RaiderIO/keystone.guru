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
        dungeon:                       {id: 3, name: 'dungeons.ara_kara', key: 'arakara', expansion: {shortname: 'tww'}},
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
