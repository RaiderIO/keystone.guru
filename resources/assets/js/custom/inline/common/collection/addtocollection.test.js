// ---------------------------------------------------------------------------
// `addtocollection.js` is concatenated into a bundle in the browser and references its
// collaborators as bare globals. `$.ajax`, the Bootstrap modal, Noty and the notifications are
// replaced per test.
// ---------------------------------------------------------------------------

const fs         = require('node:fs');
const path       = require('node:path');
const jQuery     = require('jquery');
const Handlebars = require('handlebars');
const Lang       = require('lang.js');

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

globalThis.AddToCollectionRow = require('./addtocollectionrow').AddToCollectionRow;

const {CommonCollectionAddtocollection} = require('./addtocollection');

const MESSAGES = {
    'en.js':           {
        add_to_collection_loading:              'Loading',
        add_to_collection_load_failed:          'Load failed',
        add_to_collection_count:                ':count / :max',
        add_to_collection_new_collection:       'New collection with this route…',
        add_to_collection_no_collections:       'No collections',
        add_to_collection_max_collections:      'You have :max collections.',
        add_to_collection_kind_season_set:      ':season set · :covered/:total dungeons',
        add_to_collection_kind_free_form_none:  ':game_version · no dungeons',
        add_to_collection_kind_free_form_one:   ':game_version · :count dungeon',
        add_to_collection_kind_free_form_many:  ':game_version · :count dungeons',
        add_to_collection_blocked_game_version: 'Only :game_version routes',
        add_to_collection_blocked_season:       'Only :season routes',
        add_to_collection_blocked_full:         'Full',
        add_to_collection_added:                'Added to :name.',
        add_to_collection_removed:              'Removed from :name.',
        add_to_collection_undo:                 'Undo',
        add_to_collection_undone:               'Undone.',
        add_to_collection_save_failed:          'Save failed',
    },
    'en.gameversions': {retail: 'Retail'},
};

/**
 * @param {Object} overrides
 * @returns {Object}
 */
function collection(overrides = {}) {
    return Object.assign({
        public_key:     'colA',
        name:           'Season set',
        route_count:    3,
        max_routes:     24,
        game_version:           'gameversions.retail',
        season:                 {name: 'Season 3', name_long: 'Midnight Season 3', dungeon_count: 8},
        covered_dungeon_count:  1,
        contains_dungeon_route: false,
        blocked_reason:         null,
        store_url:      '/store/colA',
        delete_url:     '/delete/colA',
    }, overrides);
}

/**
 * @param {Object[]} collections
 * @param {Object} overrides
 * @returns {Object}
 */
function listResponse(collections, overrides = {}) {
    return Object.assign({
        collections:         collections,
        collection_count:    collections.length,
        max_collections:     25,
        may_create:          true,
        create_url:          '/collections/new?dungeon_route=route1',
    }, overrides);
}

describe('CommonCollectionAddtocollection', () => {
    let previousGlobals;
    let ajaxCalls;
    let toasts;
    let modalShow;
    let code;

    beforeEach(() => {
        previousGlobals = {
            $:                       globalThis.$,
            lang:                    globalThis.lang,
            Handlebars:              globalThis.Handlebars,
            bootstrap:               globalThis.bootstrap,
            Noty:                    globalThis.Noty,
            showSuccessNotification: globalThis.showSuccessNotification,
            showInfoNotification:    globalThis.showInfoNotification,
            showErrorNotification:   globalThis.showErrorNotification,
        };
        globalThis.$ = jQuery;

        globalThis.lang       = new Lang({messages: MESSAGES, locale: 'en'});
        globalThis.Handlebars = Handlebars;
        Handlebars.templates  = {
            add_to_collection_row: Handlebars.compile(
                fs.readFileSync(path.join(__dirname, '../../../../handlebars/add_to_collection_row.handlebars'), 'utf8'),
            ),
        };

        ajaxCalls = [];
        jQuery.ajax = vi.fn((settings) => {
            ajaxCalls.push(settings);
        });

        modalShow = vi.fn();
        globalThis.bootstrap = {Modal: {getOrCreateInstance: () => ({show: modalShow})}};

        toasts = [];
        globalThis.Noty = {button: (text, classes, callback) => ({text, callback})};
        globalThis.showSuccessNotification = vi.fn((text, opts = {}) => toasts.push({text, opts}));
        globalThis.showInfoNotification = vi.fn();
        globalThis.showErrorNotification = vi.fn();

        document.body.innerHTML = `
            <a class="add_trigger" data-publickey="route1"></a>
            <div id="modal">
                <div id="status"></div>
                <ul id="list" hidden></ul>
                <div id="new"></div>
            </div>`;

        code = new CommonCollectionAddtocollection('add', 'common/collection/addtocollection', {
            triggerSelector:    '.add_trigger',
            modalSelector:      '#modal',
            statusSelector:     '#status',
            listSelector:       '#list',
            newSelector:        '#new',
            forDungeonRouteUrl: '/ajax/collections',
        });
        code.activate();
    });

    afterEach(() => {
        jQuery(document).off('click');
        Object.assign(globalThis, previousGlobals);
    });

    /**
     * @param {Object} json
     */
    function openWith(json) {
        jQuery('.add_trigger').trigger('click');
        ajaxCalls.shift().success(json);
    }

    it('open_givenATriggerClick_loadsTheCollectionsForThatRoute', () => {
        // Arrange

        // Act
        jQuery('.add_trigger').trigger('click');

        // Assert
        expect(modalShow).toHaveBeenCalled();
        expect(ajaxCalls[0].url).toBe('/ajax/collections');
        expect(ajaxCalls[0].data).toEqual({dungeon_route: 'route1'});
        expect(jQuery('#status').text()).toBe('Loading');
    });

    it('render_givenNoCollections_showsOnlyTheNewCollectionEntry', () => {
        // Arrange

        // Act
        openWith(listResponse([]));

        // Assert
        expect(jQuery('#list').prop('hidden')).toBe(true);
        expect(jQuery('#status').text()).toBe('No collections');
        expect(jQuery('#new a').attr('href')).toBe('/collections/new?dungeon_route=route1');
    });

    it('render_givenCollections_showsCountsAndDisablesTheOnesTheRouteCannotJoin', () => {
        // Arrange
        let json = listResponse([
            collection({public_key: 'member', contains_dungeon_route: true}),
            collection({public_key: 'open'}),
            collection({public_key: 'season', blocked_reason: 'season'}),
            collection({public_key: 'full', route_count: 24, blocked_reason: 'full'}),
        ]);

        // Act
        openWith(json);

        // Assert
        let $rows = jQuery('#list > li');
        expect($rows.length).toBe(4);
        expect(jQuery('#add_to_collection_member').prop('checked')).toBe(true);
        expect(jQuery('#add_to_collection_open').prop('disabled')).toBe(false);
        expect(jQuery('#add_to_collection_season').prop('disabled')).toBe(true);
        expect($rows.eq(2).find('.add_to_collection_reason').text()).toBe('Only Midnight Season 3 routes');
        expect(jQuery('#add_to_collection_full').prop('disabled')).toBe(true);
        expect($rows.eq(3).find('.add_to_collection_reason').text()).toBe('Full');
        expect($rows.eq(3).find('.add_to_collection_count').text()).toBe('24 / 24');
    });

    it('render_givenCollectionsOfEveryKind_wordsWhatEachCovers', () => {
        // Arrange
        let json = listResponse([
            collection({public_key: 'set'}),
            collection({public_key: 'none', season: null, covered_dungeon_count: 0}),
            collection({public_key: 'one', season: null, covered_dungeon_count: 1}),
            collection({public_key: 'many', season: null, covered_dungeon_count: 5}),
        ]);

        // Act
        openWith(json);

        // Assert
        let kinds = jQuery('#list > li').map((index, element) => jQuery(element).find('small').first().text()).get();
        expect(kinds).toEqual([
            'Season 3 set · 1/8 dungeons',
            'Retail · no dungeons',
            'Retail · 1 dungeon',
            'Retail · 5 dungeons',
        ]);
    });

    it('render_givenACollectionNameWithMarkup_rendersItAsText', () => {
        // Arrange
        let json = listResponse([collection({name: '<img src=x onerror=alert(1)>'})]);

        // Act
        openWith(json);

        // Assert
        expect(jQuery('#list img').length).toBe(0);
        expect(jQuery('#list').text()).toContain('<img src=x onerror=alert(1)>');
    });

    it('render_givenTheCollectionCap_disablesNewCollectionWithTheReason', () => {
        // Arrange
        let json = listResponse([collection()], {may_create: false, max_collections: 25});

        // Act
        openWith(json);

        // Assert
        expect(jQuery('#new a').length).toBe(0);
        expect(jQuery('#new button').prop('disabled')).toBe(true);
        expect(jQuery('#new').text()).toContain('You have 25 collections.');
    });

    it('toggle_givenAnEligibleCollection_addsTheRouteAndOffersUndo', () => {
        // Arrange
        openWith(listResponse([collection({name: 'A & B'})]));

        // Act
        jQuery('#add_to_collection_colA').prop('checked', true).trigger('change');
        let request = ajaxCalls.shift();
        request.success({});

        // Assert
        expect(request.type).toBe('POST');
        expect(request.url).toBe('/store/colA');
        expect(request.data).toEqual({dungeon_routes: ['route1']});
        expect(jQuery('#list .add_to_collection_count').text()).toBe('4 / 24');
        expect(jQuery('#add_to_collection_colA').prop('checked')).toBe(true);
        expect(toasts[0].text).toBe('Added to A &amp; B.');
        expect(toasts[0].opts.buttons[0].text).toBe('Undo');
    });

    it('toggle_givenACollectionNameWithMarkup_escapesItInTheToastAndTheError', () => {
        // Arrange
        openWith(listResponse([collection({name: '<img src=x onerror=alert(1)>'})]));

        // Act
        jQuery('#add_to_collection_colA').prop('checked', true).trigger('change');
        ajaxCalls.shift().success({});

        // Assert
        expect(toasts[0].text).toBe('Added to &lt;img src=x onerror=alert(1)&gt;.');
    });

    it('toggle_givenARejectedSaveWithMarkup_escapesTheServerMessage', () => {
        // Arrange
        openWith(listResponse([collection()]));

        // Act
        jQuery('#add_to_collection_colA').prop('checked', true).trigger('change');
        ajaxCalls.shift().error({responseJSON: {errors: {dungeon_routes: ['<b>nope</b>']}}});

        // Assert
        expect(showErrorNotification).toHaveBeenCalledWith('&lt;b&gt;nope&lt;/b&gt;');
    });

    it('undo_givenAnAddedRoute_removesItAgain', () => {
        // Arrange
        openWith(listResponse([collection()]));
        jQuery('#add_to_collection_colA').prop('checked', true).trigger('change');
        ajaxCalls.shift().success({});

        // Act
        toasts[0].opts.buttons[0].callback({close: vi.fn()});
        let request = ajaxCalls.shift();
        request.success({});

        // Assert
        expect(request.type).toBe('DELETE');
        expect(request.url).toBe('/delete/colA');
        expect(jQuery('#add_to_collection_colA').prop('checked')).toBe(false);
        expect(jQuery('#list .add_to_collection_count').text()).toBe('3 / 24');
        expect(showInfoNotification).toHaveBeenCalledWith('Undone.');
    });

    it('toggle_givenACollectionThatHoldsTheRoute_removesTheRoute', () => {
        // Arrange
        openWith(listResponse([collection({contains_dungeon_route: true})]));

        // Act
        jQuery('#add_to_collection_colA').prop('checked', false).trigger('change');
        let request = ajaxCalls.shift();
        request.success({});

        // Assert
        expect(request.type).toBe('DELETE');
        expect(jQuery('#list .add_to_collection_count').text()).toBe('2 / 24');
        expect(toasts[0].text).toBe('Removed from Season set.');
    });

    it('toggle_givenARemovalFromAFullCollection_makesItAvailableAgain', () => {
        // Arrange
        openWith(listResponse([collection({contains_dungeon_route: true, route_count: 24})]));

        // Act
        jQuery('#add_to_collection_colA').prop('checked', false).trigger('change');
        ajaxCalls.shift().success({});

        // Assert
        expect(jQuery('#add_to_collection_colA').prop('disabled')).toBe(false);
        expect(jQuery('#list .add_to_collection_reason').length).toBe(0);
    });

    it('toggle_givenARejectedSave_restoresTheRowAndShowsTheServerMessage', () => {
        // Arrange
        openWith(listResponse([collection()]));

        // Act
        jQuery('#add_to_collection_colA').prop('checked', true).trigger('change');
        ajaxCalls.shift().error({responseJSON: {errors: {dungeon_routes: ['A collection may hold at most 24 routes.']}}});

        // Assert
        expect(jQuery('#add_to_collection_colA').prop('checked')).toBe(false);
        expect(jQuery('#list .add_to_collection_count').text()).toBe('3 / 24');
        expect(showErrorNotification).toHaveBeenCalledWith('A collection may hold at most 24 routes.');
        expect(toasts.length).toBe(0);
    });

    it('toggle_givenASaveInProgress_disablesTheCheckboxAndIgnoresAnotherChange', () => {
        // Arrange
        openWith(listResponse([collection()]));

        // Act
        jQuery('#add_to_collection_colA').prop('checked', true).trigger('change');
        let isDisabledWhileSaving = jQuery('#add_to_collection_colA').prop('disabled');
        jQuery('#add_to_collection_colA').prop('checked', false).trigger('change');
        let requestCount = ajaxCalls.length;
        ajaxCalls.shift().success({});

        // Assert
        expect(isDisabledWhileSaving).toBe(true);
        expect(requestCount).toBe(1);
        expect(jQuery('#add_to_collection_colA').prop('disabled')).toBe(false);
        expect(jQuery('#add_to_collection_colA').prop('checked')).toBe(true);
    });

    it('open_givenALoadFailure_saysSo', () => {
        // Arrange

        // Act
        jQuery('.add_trigger').trigger('click');
        ajaxCalls.shift().error({});

        // Assert
        expect(jQuery('#status').text()).toBe('Load failed');
    });
});
