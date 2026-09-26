// ---------------------------------------------------------------------------
// `routes.js` is concatenated into a bundle in the browser and references its
// collaborators as bare globals. The ordered lists are the real
// CommonFormsOrderedselect over jsdom markup, reached through a stubbed
// `_inlineManager`; `$.ajax`, the route picker, Noty and the notifications are
// replaced per test.
// ---------------------------------------------------------------------------

const jQuery = require('jquery');
const Lang   = require('lang.js');

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {CommonFormsOrderedselect} = require('../forms/orderedselect');
const {CommonCollectionRoutes}   = require('./routes');
const {PickerDungeonRoute}       = require('../dungeonroute/pickerdungeonroute');

const MESSAGES = {
    'en.js':       {
        collection_dungeonroutes_count:       ':count / :max',
        collection_dungeonroutes_added_one:   'Added 1',
        collection_dungeonroutes_added_many:  'Added :count',
        collection_dungeonroutes_removed:     'Removed :name',
        collection_dungeonroutes_undo:        'Undo',
        collection_dungeonroutes_undone:      'Undone',
        collection_dungeonroutes_save_failed: 'Failed',
        orderedselect_count:                  ':count / :max',
        orderedselect_move_up:                'Up :name',
        orderedselect_move_down:              'Down :name',
        orderedselect_remove:                 'Remove :name',
        orderedselect_added_status:           'Added :name',
        orderedselect_moved_status:           'Moved :name',
        orderedselect_removed_status:         'Removed :name',
        orderedselect_full:                   'Full (maximum :max)',
    },
    'en.dungeons': {two: 'Two'},
};

/**
 * @param {string} prefix
 * @param {Array<[string, string]>} items Public key and title of every route in the list.
 * @returns {string}
 */
function listHtml(prefix, items) {
    let item = (id, name) => `
        <li class="list-group-item ordered_select_item" data-id="${id}">
            <span class="ordered_select_handle"></span>
            <span class="ordered_select_position">0</span>
            <span class="ordered_select_label">${name}</span>
            <span class="ordered_select_detail" hidden>
                <i class="ordered_select_detail_icon" hidden></i>
                <span class="ordered_select_detail_text"></span>
                <span class="visually-hidden ordered_select_detail_warning_text" hidden>Short on enemy forces</span>
            </span>
            <button type="button" class="ordered_select_up"></button>
            <button type="button" class="ordered_select_down"></button>
            <button type="button" class="ordered_select_remove"></button>
            <input type="hidden" name="dungeon_routes[]" value="${id}">
        </li>`;

    return `
        <div id="${prefix}">
            <ol id="${prefix}_list" class="ordered_select_list">${items.map(([id, name]) => item(id, name)).join('')}</ol>
            <p id="${prefix}_empty" hidden></p>
            <button id="${prefix}_add_button" type="button"></button>
            <span id="${prefix}_full" hidden></span>
            <div id="${prefix}_status"></div>
            <template id="${prefix}_template">${item('', '')}</template>
        </div>`;
}

/**
 * @param {string} prefix
 * @returns {Object}
 */
function orderedSelectOptions(prefix) {
    return {
        listSelector:      `#${prefix}_list`,
        templateSelector:  `#${prefix}_template`,
        addSelectSelector: `#${prefix}_add`,
        addButtonSelector: `#${prefix}_add_button`,
        emptySelector:     `#${prefix}_empty`,
        countSelector:     `#${prefix}_count`,
        fullSelector:      `#${prefix}_full`,
        statusSelector:    `#${prefix}_status`,
        max:               24,
        ajax:              true,
        rootSelector:      `#${prefix}`,
        fullCount:         3,
        showCountMax:      false,
    };
}

describe('CommonCollectionRoutes', () => {
    let previousGlobals;
    let ajaxCalls;
    let toasts;
    let picker;
    let lists;
    let routesOptions;

    beforeEach(() => {
        vi.useFakeTimers();

        previousGlobals = {
            $:                       globalThis.$,
            lang:                    globalThis.lang,
            _inlineManager:          globalThis._inlineManager,
            Noty:                    globalThis.Noty,
            showSuccessNotification: globalThis.showSuccessNotification,
            showInfoNotification:    globalThis.showInfoNotification,
            showErrorNotification:   globalThis.showErrorNotification,
        };
        globalThis.$ = jQuery;
        globalThis.lang = new Lang({messages: MESSAGES, locale: 'en'});

        ajaxCalls = [];
        jQuery.ajax = vi.fn((settings) => {
            ajaxCalls.push(settings);
        });

        toasts = [];
        globalThis.Noty = {button: (text, classes, callback) => ({text, callback})};
        globalThis.showSuccessNotification = vi.fn((text, opts = {}) => toasts.push({text, opts}));
        globalThis.showInfoNotification = vi.fn();
        globalThis.showErrorNotification = vi.fn();

        document.body.innerHTML = `
            <span id="count"></span>
            <div id="picker"></div>
            ${listHtml('slot_1', [['keyA', 'Alpha'], ['keyB', 'Bravo']])}
            ${listHtml('slot_2', [])}
            ${listHtml('off_pool', [['keyOld', 'Old']])}`;

        lists = {};
        ['slot_1', 'slot_2', 'off_pool'].forEach((prefix) => {
            lists[`${prefix}_inline`] = new CommonFormsOrderedselect(`${prefix}_inline`, 'common/forms/orderedselect', orderedSelectOptions(prefix));
            lists[`${prefix}_inline`].activate();
        });

        picker = {open: vi.fn(), setExistingPublicKeys: vi.fn()};
        globalThis._inlineManager = {
            getInlineCodeById: (id) => (id === 'picker_inline' ? picker : lists[id]),
        };

        routesOptions = {
            dungeonRoutePickerInlineId: 'picker_inline',
            dungeonRoutePickerSelector: '#picker',
            countSelector:  '#count',
            sections:       [
                {inlineId: 'slot_1_inline', rootSelector: '#slot_1', addButtonSelector: '#slot_1_add_button', dungeonId: 1, canAdd: true, withDungeonName: false},
                {inlineId: 'slot_2_inline', rootSelector: '#slot_2', addButtonSelector: '#slot_2_add_button', dungeonId: 2, canAdd: true, withDungeonName: false},
                {inlineId: 'off_pool_inline', rootSelector: '#off_pool', addButtonSelector: '#off_pool_add_button', dungeonId: 3, canAdd: false, withDungeonName: false},
            ],
            storeUrl:       '/store',
            deleteUrl:      '/delete',
            orderUrl:       '/order',
            max:            24,
        };

        new CommonCollectionRoutes('routes', 'common/collection/routes', routesOptions).activate();
    });

    /**
     * Replaces the controller with the one a collection that does not exist yet gets: no endpoints to save to.
     */
    /**
     * Replaces the controller with one built from other options.
     * @param {Object} overrides
     */
    function withOptions(overrides) {
        // Re-parsing the markup drops the handlers the controller bound to it in beforeEach
        document.body.innerHTML = document.body.innerHTML;
        ['slot_1', 'slot_2', 'off_pool'].forEach((prefix) => {
            lists[`${prefix}_inline`] = new CommonFormsOrderedselect(`${prefix}_inline`, 'common/forms/orderedselect', orderedSelectOptions(prefix));
            lists[`${prefix}_inline`].activate();
        });

        new CommonCollectionRoutes('routes', 'common/collection/routes', Object.assign({}, routesOptions, overrides)).activate();
    }

    function asNewCollection() {
        withOptions({storeUrl: null, deleteUrl: null, orderUrl: null});
    }

    afterEach(() => {
        vi.useRealTimers();
        Object.assign(globalThis, previousGlobals);
        document.body.innerHTML = '';
    });

    /**
     * @param {string} type
     * @returns {Object}
     */
    function lastCall(type) {
        return ajaxCalls.filter((call) => call.type === type).pop();
    }

    it('addButton_givenASlot_opensThePickerOnItsDungeon', () => {
        // Arrange - done in beforeEach

        // Act
        document.querySelector('#slot_2_add_button').click();

        // Assert
        expect(picker.open).toHaveBeenCalledWith({dungeonId: 2});
    });

    it('onAdded_givenRoutesFromThePicker_putsEachInItsDungeonsSlotAndOffersUndo', () => {
        // Arrange
        const added = [
            {id: 21, public_key: 'keyC', title: 'Charlie', dungeon_id: 2, dungeon: 'Two', enemy_forces: 300, enemy_forces_required: 280},
            {id: 22, public_key: 'keyD', title: 'Delta', dungeon_id: 1, dungeon: 'One', enemy_forces: 100, enemy_forces_required: 280},
        ];

        // Act
        jQuery('#picker').trigger('dungeonroutepicker:confirmed', [{publicKeys: ['keyC', 'keyD'], response: {dungeon_routes: added}}]);

        // Assert
        expect(lists.slot_2_inline.getIds()).toEqual(['keyC']);
        expect(lists.slot_1_inline.getIds()).toEqual(['keyA', 'keyB', 'keyD']);
        expect(document.querySelector('#count').textContent).toBe('5 / 24');
        expect(picker.setExistingPublicKeys).toHaveBeenLastCalledWith(
            ['keyA', 'keyB', 'keyD', 'keyC', 'keyOld'],
            expect.objectContaining({keyC: 2, keyD: 1}),
        );
        expect(toasts[0].text).toBe('Added 2');
        expect(toasts[0].opts.buttons[0].text).toBe('Undo');
        // The row must read like the ones the server rendered, not wait for a page refresh for it
        expect(lists.slot_2_inline.getDetail('keyC')).toEqual({text: '300 / 280', isWarning: false});
        expect(lists.slot_1_inline.getDetail('keyD')).toEqual({text: '100 / 280', isWarning: true});
    });

    it('onAdded_givenNoRoutesWereStored_showsNoToast', () => {
        // Arrange - every posted route was already a member, so the controller stored none of them

        // Act
        jQuery('#picker').trigger('dungeonroutepicker:confirmed', [{publicKeys: ['keyA'], response: {dungeon_routes: []}}]);

        // Assert
        expect(lists.slot_1_inline.getIds()).toEqual(['keyA', 'keyB']);
        expect(lists.slot_2_inline.getIds()).toEqual([]);
        expect(toasts).toEqual([]);
        expect(globalThis.showInfoNotification).not.toHaveBeenCalled();
    });

    it('onRemoved_givenTheDeleteFails_putsTheEnemyForcesBackWithTheRoute', () => {
        // Arrange
        const detail = document.querySelector('#slot_1 [data-id="keyA"] .ordered_select_detail');
        detail.hidden = false;
        detail.classList.add('ordered_select_detail_warning');
        detail.querySelector('.ordered_select_detail_text').textContent = '90 / 280';

        // Act
        document.querySelector('#slot_1 [data-id="keyA"] .ordered_select_remove').click();
        lastCall('DELETE').error();

        // Assert
        expect(lists.slot_1_inline.getIds()).toEqual(['keyA', 'keyB']);
        expect(lists.slot_1_inline.getDetail('keyA')).toEqual({text: '90 / 280', isWarning: true});
    });

    it('onAdded_givenUndo_removesTheAddedRoutesAgain', () => {
        // Arrange
        const added = [{id: 21, public_key: 'keyC', title: 'Charlie', dungeon_id: 2, dungeon: 'Two', enemy_forces: 300, enemy_forces_required: 280}];
        jQuery('#picker').trigger('dungeonroutepicker:confirmed', [{publicKeys: ['keyC'], response: {dungeon_routes: added}}]);

        // Act
        toasts[0].opts.buttons[0].callback({close: vi.fn()});
        lastCall('DELETE').success({});

        // Assert
        expect(lastCall('DELETE').data).toEqual({dungeon_routes: ['keyC']});
        expect(lists.slot_2_inline.getIds()).toEqual([]);
        expect(document.querySelector('#count').textContent).toBe('3 / 24');
        expect(picker.setExistingPublicKeys).toHaveBeenLastCalledWith(['keyA', 'keyB', 'keyOld'], expect.any(Object));
    });

    it('onRemoved_givenARoute_deletesItAndUndoPutsItBackInPlace', () => {
        // Arrange
        document.querySelector('#slot_1 [data-id="keyA"] .ordered_select_remove').click();
        lastCall('DELETE').success({});

        // Act
        toasts[0].opts.buttons[0].callback({close: vi.fn()});
        lastCall('POST').success({dungeon_routes: [{id: 11, public_key: 'keyA', title: 'Alpha', dungeon_id: 1, dungeon: 'One'}]});

        // Assert
        expect(ajaxCalls[0]).toMatchObject({type: 'DELETE', url: '/delete', data: {dungeon_routes: ['keyA']}});
        expect(toasts[0].text).toBe('Removed Alpha');
        expect(lastCall('POST').data).toEqual({dungeon_routes: ['keyA']});
        expect(lists.slot_1_inline.getIds()).toEqual(['keyA', 'keyB']);
        expect(lastCall('PUT')).toMatchObject({url: '/order', data: {dungeon_routes: ['keyA', 'keyB', 'keyOld']}});
    });

    it('onRemoved_givenTheDeleteFails_putsTheRouteBack', () => {
        // Arrange - nothing beyond beforeEach

        // Act
        document.querySelector('#slot_1 [data-id="keyA"] .ordered_select_remove').click();
        lastCall('DELETE').error();

        // Assert
        expect(lists.slot_1_inline.getIds()).toEqual(['keyA', 'keyB']);
        expect(globalThis.showErrorNotification).toHaveBeenCalledWith('Failed');
        expect(toasts).toHaveLength(0);
    });

    it('onRemoved_givenASectionThePickerCannotFill_offersNoUndo', () => {
        // Arrange - nothing beyond beforeEach

        // Act
        document.querySelector('#off_pool [data-id="keyOld"] .ordered_select_remove').click();
        lastCall('DELETE').success({});

        // Assert
        expect(toasts[0].text).toBe('Removed Old');
        expect(toasts[0].opts.buttons).toBeUndefined();
    });

    it('onRemoved_givenADungeonStillAtItsLimitWithoutTheRoute_offersNoUndo', () => {
        // Arrange - the collection holds two routes of dungeon 1 but only one fits
        withOptions({maxPerDungeon: 1, dungeonIds: {keyA: 1, keyB: 1}});

        // Act
        document.querySelector('#slot_1 [data-id="keyA"] .ordered_select_remove').click();
        lastCall('DELETE').success({});

        // Assert
        expect(toasts[0].text).toBe('Removed Alpha');
        expect(toasts[0].opts.buttons).toBeUndefined();
    });

    it('onRemoved_givenADungeonWithRoomForTheRoute_offersUndo', () => {
        // Arrange
        withOptions({maxPerDungeon: 2, dungeonIds: {keyA: 1, keyB: 1}});

        // Act
        document.querySelector('#slot_1 [data-id="keyA"] .ordered_select_remove').click();
        lastCall('DELETE').success({});

        // Assert
        expect(toasts[0].opts.buttons[0].text).toBe('Undo');
    });

    it('onRemoved_givenATitleWithMarkup_escapesItInTheToast', () => {
        // Arrange
        document.querySelector('#slot_1 [data-id="keyA"] .ordered_select_label').textContent = '<img src=x onerror=alert(1)>';

        // Act
        document.querySelector('#slot_1 [data-id="keyA"] .ordered_select_remove').click();
        lastCall('DELETE').success({});

        // Assert
        expect(toasts[0].text).toBe('Removed &lt;img src=x onerror=alert(1)&gt;');
    });

    it('onRemoved_givenAMoveWaitingToBeSaved_stillStoresTheMovedOrder', () => {
        // Arrange - move Bravo up, then remove the off-pool route before the debounced save fires
        document.querySelector('#slot_1 [data-id="keyB"] .ordered_select_up').click();
        document.querySelector('#off_pool [data-id="keyOld"] .ordered_select_remove').click();
        lastCall('DELETE').success({});

        // Act
        vi.advanceTimersByTime(500);

        // Assert
        expect(lastCall('PUT').data).toEqual({dungeon_routes: ['keyB', 'keyA']});
    });

    it('onAdded_givenAMoveWaitingToBeSaved_stillStoresTheMovedOrder', () => {
        // Arrange
        document.querySelector('#slot_1 [data-id="keyB"] .ordered_select_up').click();
        const added = [{id: 21, public_key: 'keyC', title: 'Charlie', dungeon_id: 2, dungeon: 'Two'}];
        jQuery('#picker').trigger('dungeonroutepicker:confirmed', [{publicKeys: ['keyC'], response: {dungeon_routes: added}}]);

        // Act
        vi.advanceTimersByTime(500);

        // Assert
        expect(lastCall('PUT').data).toEqual({dungeon_routes: ['keyB', 'keyA', 'keyC', 'keyOld']});
    });

    it('onMoved_givenTheListIsPutBackWhileASaveIsInFlight_storesTheRestoredOrderToo', () => {
        // Arrange - move Bravo up and let that save start, then move it back before the save answers
        const bravo = () => document.querySelector('#slot_1 [data-id="keyB"]');
        bravo().querySelector('.ordered_select_up').click();
        vi.advanceTimersByTime(500);
        const inFlight = lastCall('PUT');
        bravo().querySelector('.ordered_select_down').click();
        vi.advanceTimersByTime(500);

        // Act
        inFlight.success({});

        // Assert
        const puts = ajaxCalls.filter((call) => call.type === 'PUT');
        expect(puts).toHaveLength(2);
        expect(puts[0].data).toEqual({dungeon_routes: ['keyB', 'keyA', 'keyOld']});
        expect(puts[1].data).toEqual({dungeon_routes: ['keyA', 'keyB', 'keyOld']});
    });

    it('onMoved_givenSeveralMovesInARow_storesTheWholeOrderOnce', () => {
        // Arrange
        const bravo = () => document.querySelector('#slot_1 [data-id="keyB"]');

        // Act
        bravo().querySelector('.ordered_select_up').click();
        bravo().querySelector('.ordered_select_down').click();
        bravo().querySelector('.ordered_select_up').click();
        vi.advanceTimersByTime(500);

        // Assert
        const puts = ajaxCalls.filter((call) => call.type === 'PUT');
        expect(puts).toHaveLength(1);
        expect(puts[0].data).toEqual({dungeon_routes: ['keyB', 'keyA', 'keyOld']});
    });

    it('onMoved_givenTheOrderEndsUnchanged_storesNothing', () => {
        // Arrange
        const bravo = () => document.querySelector('#slot_1 [data-id="keyB"]');

        // Act
        bravo().querySelector('.ordered_select_up').click();
        bravo().querySelector('.ordered_select_down').click();
        vi.advanceTimersByTime(500);

        // Assert
        expect(ajaxCalls.filter((call) => call.type === 'PUT')).toHaveLength(0);
    });

    it('onAdded_givenACollectionBeingCreated_fillsTheListsWithoutSavingAnything', () => {
        // Arrange
        asNewCollection();

        // Act - a drawer with no endpoint of its own reports the routes it picked from
        jQuery('#picker').trigger('dungeonroutepicker:confirmed', [{
            publicKeys: ['keyC'],
            dungeonRoutes: [new PickerDungeonRoute({
                public_key:                    'keyC',
                title:                         'Charlie',
                dungeon:                       {id: 2, name: 'dungeons.two'},
                teeming:                       0,
                enemy_forces:                  100,
                enemy_forces_required:         280,
                enemy_forces_required_teeming: 330,
            })],
            response:      null,
        }]);

        // Assert
        expect(lists.slot_2_inline.getIds()).toEqual(['keyC']);
        expect(lists.slot_2_inline.getDetail('keyC')).toEqual({text: '100 / 280', isWarning: true});
        expect(document.querySelector('#count').textContent).toBe('4 / 24');
        expect(ajaxCalls).toHaveLength(0);
        expect(toasts[0].text).toBe('Added 1');
        expect(toasts[0].opts.buttons).toBeUndefined();
    });

    it('onRemoved_givenACollectionBeingCreated_onlyUpdatesTheCount', () => {
        // Arrange
        asNewCollection();

        // Act
        document.querySelector('#slot_1 [data-id="keyA"] .ordered_select_remove').click();

        // Assert
        expect(lists.slot_1_inline.getIds()).toEqual(['keyB']);
        expect(document.querySelector('#count').textContent).toBe('2 / 24');
        expect(ajaxCalls).toHaveLength(0);
        expect(toasts).toHaveLength(0);
    });

    it('onMoved_givenACollectionBeingCreated_storesNothing', () => {
        // Arrange
        asNewCollection();

        // Act - the form posts the lists in their own order
        document.querySelector('#slot_1 [data-id="keyB"] .ordered_select_up').click();
        vi.advanceTimersByTime(500);

        // Assert
        expect(lists.slot_1_inline.getIds()).toEqual(['keyB', 'keyA']);
        expect(ajaxCalls).toHaveLength(0);
    });

    it('onMoved_givenTheSaveFails_restoresTheStoredOrder', () => {
        // Arrange
        document.querySelector('#slot_1 [data-id="keyB"] .ordered_select_up').click();
        vi.advanceTimersByTime(500);

        // Act
        lastCall('PUT').error();

        // Assert
        expect(lists.slot_1_inline.getIds()).toEqual(['keyA', 'keyB']);
    });
});
