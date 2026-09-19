// ---------------------------------------------------------------------------
// `addtocollection.js` is concatenated into a bundle in the browser and references its
// collaborators as bare globals. `$.ajax`, the Bootstrap modal, Noty and the notifications are
// replaced per test.
// ---------------------------------------------------------------------------

const jQuery = require('jquery');

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {CommonCollectionAddtocollection} = require('./addtocollection');

/**
 * @param {Object} overrides
 * @returns {Object}
 */
function collection(overrides = {}) {
    return Object.assign({
        public_key:     'colA',
        name:           'Season set',
        kind_label:     'Season 3 set · 1/8 dungeons',
        route_count:    3,
        max_routes:     24,
        is_member:      false,
        blocked_reason: null,
        blocked_text:   null,
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
        create_blocked_text: null,
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
            bootstrap:               globalThis.bootstrap,
            Noty:                    globalThis.Noty,
            showSuccessNotification: globalThis.showSuccessNotification,
            showInfoNotification:    globalThis.showInfoNotification,
            showErrorNotification:   globalThis.showErrorNotification,
        };
        globalThis.$ = jQuery;

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
            loadingText:        'Loading',
            loadFailedText:     'Load failed',
            countText:          ':count / :max',
            newCollectionText:  'New collection with this route…',
            noCollectionsText:  'No collections',
            fullText:           'Full',
            addedText:          'Added to :name.',
            removedText:        'Removed from :name.',
            undoText:           'Undo',
            undoneText:         'Undone.',
            saveFailedText:     'Save failed',
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
            collection({public_key: 'member', is_member: true}),
            collection({public_key: 'open'}),
            collection({public_key: 'season', blocked_reason: 'season', blocked_text: 'Only Season 2 routes'}),
            collection({public_key: 'full', route_count: 24, blocked_reason: 'full', blocked_text: 'Full'}),
        ]);

        // Act
        openWith(json);

        // Assert
        let $rows = jQuery('#list > li');
        expect($rows.length).toBe(4);
        expect(jQuery('#add_to_collection_member').prop('checked')).toBe(true);
        expect(jQuery('#add_to_collection_open').prop('disabled')).toBe(false);
        expect(jQuery('#add_to_collection_season').prop('disabled')).toBe(true);
        expect($rows.eq(2).find('.add_to_collection_reason').text()).toBe('Only Season 2 routes');
        expect(jQuery('#add_to_collection_full').prop('disabled')).toBe(true);
        expect($rows.eq(3).find('.add_to_collection_count').text()).toBe('24 / 24');
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
        let json = listResponse([collection()], {may_create: false, create_blocked_text: 'You have 25 collections.'});

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

    it('toggle_givenAMemberCollection_removesTheRoute', () => {
        // Arrange
        openWith(listResponse([collection({is_member: true})]));

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
        openWith(listResponse([collection({is_member: true, route_count: 24})]));

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

    it('open_givenALoadFailure_saysSo', () => {
        // Arrange

        // Act
        jQuery('.add_trigger').trigger('click');
        ajaxCalls.shift().error({});

        // Assert
        expect(jQuery('#status').text()).toBe('Load failed');
    });
});
