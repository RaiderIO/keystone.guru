// ---------------------------------------------------------------------------
// `routes.js` is concatenated into a bundle in the browser and references its
// collaborators as bare globals. The ordered lists are the real
// CommonFormsOrderedselect over jsdom markup, reached through a stubbed
// `_inlineManager`; `$.ajax`, the route picker, Noty and the notifications are
// replaced per test.
// ---------------------------------------------------------------------------

const jQuery = require('jquery');

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {CommonFormsOrderedselect} = require('../forms/orderedselect');
const {CommonCollectionRoutes}   = require('./routes');

/**
 * @param {string} prefix
 * @param {Array<[Number, string]>} items
 * @returns {string}
 */
function listHtml(prefix, items) {
    let item = (id, name) => `
        <li class="list-group-item ordered_select_item" data-id="${id}">
            <span class="ordered_select_handle"></span>
            <span class="ordered_select_position">0</span>
            <span class="ordered_select_label">${name}</span>
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
            <template id="${prefix}_template">${item(0, '')}</template>
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
        countText:         ':count / :max',
        moveUpText:        'Up :name',
        moveDownText:      'Down :name',
        removeText:        'Remove :name',
        addedStatusText:   'Added :name',
        movedStatusText:   'Moved :name',
        removedStatusText: 'Removed :name',
    };
}

describe('CommonCollectionRoutes', () => {
    let previousGlobals;
    let ajaxCalls;
    let toasts;
    let picker;
    let lists;

    beforeEach(() => {
        vi.useFakeTimers();

        previousGlobals = {
            $:                       globalThis.$,
            _inlineManager:          globalThis._inlineManager,
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

        toasts = [];
        globalThis.Noty = {button: (text, classes, callback) => ({text, callback})};
        globalThis.showSuccessNotification = vi.fn((text, opts = {}) => toasts.push({text, opts}));
        globalThis.showInfoNotification = vi.fn();
        globalThis.showErrorNotification = vi.fn();

        document.body.innerHTML = `
            <span id="count"></span>
            <div id="picker"></div>
            ${listHtml('slot_1', [[11, 'Alpha'], [12, 'Bravo']])}
            ${listHtml('slot_2', [])}
            ${listHtml('foreign', [[13, 'Old']])}`;

        lists = {};
        ['slot_1', 'slot_2', 'foreign'].forEach((prefix) => {
            lists[`${prefix}_inline`] = new CommonFormsOrderedselect(`${prefix}_inline`, 'common/forms/orderedselect', orderedSelectOptions(prefix));
            lists[`${prefix}_inline`].activate();
        });

        picker = {open: vi.fn(), setExistingPublicKeys: vi.fn()};
        globalThis._inlineManager = {
            getInlineCodeById: (id) => (id === 'picker_inline' ? picker : lists[id]),
        };

        new CommonCollectionRoutes('routes', 'common/collection/routes', {
            pickerInlineId: 'picker_inline',
            pickerSelector: '#picker',
            countSelector:  '#count',
            sections:       [
                {inlineId: 'slot_1_inline', rootSelector: '#slot_1', addButtonSelector: '#slot_1_add_button', dungeonId: 1, isForeign: false, withDungeonName: false},
                {inlineId: 'slot_2_inline', rootSelector: '#slot_2', addButtonSelector: '#slot_2_add_button', dungeonId: 2, isForeign: false, withDungeonName: false},
                {inlineId: 'foreign_inline', rootSelector: '#foreign', addButtonSelector: '#foreign_add_button', dungeonId: null, isForeign: true, withDungeonName: true},
            ],
            dungeonRoutes:  {11: 'keyA', 12: 'keyB', 13: 'keyOld'},
            storeUrl:       '/store',
            deleteUrl:      '/delete',
            orderUrl:       '/order',
            max:            24,
            countText:      ':count / :max',
            addedOneText:   'Added 1',
            addedManyText:  'Added :count',
            removedText:    'Removed :name',
            undoText:       'Undo',
            undoneText:     'Undone',
            saveFailedText: 'Failed',
        }).activate();
    });

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
            {id: 21, public_key: 'keyC', title: 'Charlie', dungeon_id: 2, dungeon: 'Two'},
            {id: 22, public_key: 'keyD', title: 'Delta', dungeon_id: 1, dungeon: 'One'},
        ];

        // Act
        jQuery('#picker').trigger('routepicker:added', [{publicKeys: ['keyC', 'keyD'], response: {dungeon_routes: added}}]);

        // Assert
        expect(lists.slot_2_inline.getIds()).toEqual(['21']);
        expect(lists.slot_1_inline.getIds()).toEqual(['11', '12', '22']);
        expect(document.querySelector('#count').textContent).toBe('5 / 24');
        expect(picker.setExistingPublicKeys).toHaveBeenLastCalledWith(['keyA', 'keyB', 'keyOld', 'keyC', 'keyD']);
        expect(toasts[0].text).toBe('Added 2');
        expect(toasts[0].opts.buttons[0].text).toBe('Undo');
    });

    it('onAdded_givenUndo_removesTheAddedRoutesAgain', () => {
        // Arrange
        const added = [{id: 21, public_key: 'keyC', title: 'Charlie', dungeon_id: 2, dungeon: 'Two'}];
        jQuery('#picker').trigger('routepicker:added', [{publicKeys: ['keyC'], response: {dungeon_routes: added}}]);

        // Act
        toasts[0].opts.buttons[0].callback({close: vi.fn()});
        lastCall('DELETE').success({});

        // Assert
        expect(lastCall('DELETE').data).toEqual({dungeon_routes: ['keyC']});
        expect(lists.slot_2_inline.getIds()).toEqual([]);
        expect(document.querySelector('#count').textContent).toBe('3 / 24');
        expect(picker.setExistingPublicKeys).toHaveBeenLastCalledWith(['keyA', 'keyB', 'keyOld']);
    });

    it('onRemoved_givenARoute_deletesItAndUndoPutsItBackInPlace', () => {
        // Arrange
        document.querySelector('#slot_1 [data-id="11"] .ordered_select_remove').click();
        lastCall('DELETE').success({});

        // Act
        toasts[0].opts.buttons[0].callback({close: vi.fn()});
        lastCall('POST').success({dungeon_routes: [{id: 11, public_key: 'keyA', title: 'Alpha', dungeon_id: 1, dungeon: 'One'}]});

        // Assert
        expect(ajaxCalls[0]).toMatchObject({type: 'DELETE', url: '/delete', data: {dungeon_routes: ['keyA']}});
        expect(toasts[0].text).toBe('Removed Alpha');
        expect(lastCall('POST').data).toEqual({dungeon_routes: ['keyA']});
        expect(lists.slot_1_inline.getIds()).toEqual(['11', '12']);
        expect(lastCall('PUT')).toMatchObject({url: '/order', data: {dungeon_routes: ['keyA', 'keyB', 'keyOld']}});
    });

    it('onRemoved_givenTheDeleteFails_putsTheRouteBack', () => {
        // Arrange - nothing beyond beforeEach

        // Act
        document.querySelector('#slot_1 [data-id="11"] .ordered_select_remove').click();
        lastCall('DELETE').error();

        // Assert
        expect(lists.slot_1_inline.getIds()).toEqual(['11', '12']);
        expect(globalThis.showErrorNotification).toHaveBeenCalledWith('Failed');
        expect(toasts).toHaveLength(0);
    });

    it('onRemoved_givenAForeignRoute_offersNoUndo', () => {
        // Arrange - nothing beyond beforeEach

        // Act
        document.querySelector('#foreign [data-id="13"] .ordered_select_remove').click();
        lastCall('DELETE').success({});

        // Assert
        expect(toasts[0].text).toBe('Removed Old');
        expect(toasts[0].opts.buttons).toBeUndefined();
    });

    it('onMoved_givenSeveralMovesInARow_storesTheWholeOrderOnce', () => {
        // Arrange
        const bravo = () => document.querySelector('#slot_1 [data-id="12"]');

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
        const bravo = () => document.querySelector('#slot_1 [data-id="12"]');

        // Act
        bravo().querySelector('.ordered_select_up').click();
        bravo().querySelector('.ordered_select_down').click();
        vi.advanceTimersByTime(500);

        // Assert
        expect(ajaxCalls.filter((call) => call.type === 'PUT')).toHaveLength(0);
    });

    it('onMoved_givenTheSaveFails_restoresTheStoredOrder', () => {
        // Arrange
        document.querySelector('#slot_1 [data-id="12"] .ordered_select_up').click();
        vi.advanceTimersByTime(500);

        // Act
        lastCall('PUT').error();

        // Assert
        expect(lists.slot_1_inline.getIds()).toEqual(['11', '12']);
    });
});
