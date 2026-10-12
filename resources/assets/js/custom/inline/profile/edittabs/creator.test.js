// ---------------------------------------------------------------------------
// `creator.js` is concatenated into a bundle in the browser and references its
// collaborators as bare globals. The pinned-routes list is the real
// CommonFormsOrderedselect over jsdom markup, reached through a stubbed
// `_inlineManager`; the route picker drawer is a stub that only records what
// it is told.
// ---------------------------------------------------------------------------

const jQuery = require('jquery');
const Lang   = require('lang.js');

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {CommonFormsOrderedselect} = require('../../common/forms/orderedselect');
const {PickerDungeonRoute}       = require('../../common/dungeonroute/pickerdungeonroute');
const {ProfileEdittabsCreator}   = require('./creator');

const MESSAGES = {
    'en.js':       {
        orderedselect_count:          ':count / :max',
        orderedselect_move_up:        'Up :name',
        orderedselect_move_down:      'Down :name',
        orderedselect_remove:         'Remove :name',
        orderedselect_removed_status: 'Removed :name',
        orderedselect_full:           'Full (maximum :max)',
    },
    'en.dungeons': {two: 'Two'},
};

/**
 * @param {string|Number} id
 * @param {string} name
 * @returns {string}
 */
function itemHtml(id, name) {
    return `
        <li class="list-group-item ordered_select_item" data-id="${id}">
            <span class="ordered_select_handle"></span>
            <span class="ordered_select_position">0</span>
            <span class="ordered_select_label">${name}</span>
            <span class="ordered_select_detail" hidden>
                <i class="ordered_select_detail_icon" hidden></i>
                <span class="ordered_select_detail_text"></span>
            </span>
            <button type="button" class="ordered_select_up"></button>
            <button type="button" class="ordered_select_down"></button>
            <button type="button" class="ordered_select_remove"></button>
            <input type="hidden" name="pinned_dungeon_routes[]" value="${id}">
        </li>`;
}

/**
 * @param {string} publicKey
 * @param {string} title
 * @returns {PickerDungeonRoute}
 */
function pickerDungeonRoute(publicKey, title) {
    return new PickerDungeonRoute({public_key: publicKey, title: title, dungeon: {id: 2, name: 'dungeons.two'}});
}

describe('ProfileEdittabsCreator', () => {
    let previousGlobals;
    let picker;
    let orderedSelect;

    beforeEach(() => {
        previousGlobals = {
            $:              globalThis.$,
            lang:           globalThis.lang,
            _inlineManager: globalThis._inlineManager,
        };
        globalThis.$ = jQuery;
        globalThis.lang = new Lang({messages: MESSAGES, locale: 'en'});

        document.body.innerHTML = `
            <div id="picker"></div>
            <div id="pinned">
                <span id="pinned_count"></span>
                <ol id="pinned_list" class="ordered_select_list">${itemHtml(11, 'Alpha — Two')}</ol>
                <p id="pinned_empty" hidden></p>
                <button id="pinned_add_button" type="button"></button>
                <span id="pinned_full" hidden></span>
                <div id="pinned_status"></div>
                <template id="pinned_template">${itemHtml('', '')}</template>
            </div>`;

        orderedSelect = new CommonFormsOrderedselect('pinned_inline', 'common/forms/orderedselect', {
            listSelector:      '#pinned_list',
            templateSelector:  '#pinned_template',
            addSelectSelector: '#pinned_add',
            addButtonSelector: '#pinned_add_button',
            emptySelector:     '#pinned_empty',
            countSelector:     '#pinned_count',
            fullSelector:      '#pinned_full',
            statusSelector:    '#pinned_status',
            max:               2,
            ajax:              true,
            rootSelector:      '#pinned',
            fullCount:         1,
            showCountMax:      true,
        });
        orderedSelect.activate();

        picker = {setExistingPublicKeys: vi.fn()};
        globalThis._inlineManager = {
            getInlineCodeById: (id) => (id === 'picker_inline' ? picker : orderedSelect),
        };

        new ProfileEdittabsCreator('creator', 'profile/edittabs/creator', {
            pickerSelector:             '#picker',
            pickerInlineId:             'picker_inline',
            orderedSelectSelector:      '#pinned',
            orderedSelectInlineId:      'pinned_inline',
            dungeonRouteIdsByPublicKey: {keyAlpha: 11, keyBravo: 12, keyCharlie: 13},
        }).activate();
    });

    afterEach(() => {
        Object.assign(globalThis, previousGlobals);
        document.body.innerHTML = '';
    });

    /**
     * @param {PickerDungeonRoute[]} dungeonRoutes
     */
    function confirmPicker(dungeonRoutes) {
        jQuery('#picker').trigger('dungeonroutepicker:confirmed', [{
            publicKeys:    dungeonRoutes.map(dungeonRoute => dungeonRoute.publicKey),
            dungeonRoutes: dungeonRoutes,
            response:      null,
        }]);
    }

    it('appends the picked routes to the list as their ids, labelled with their dungeon', () => {
        // Act
        confirmPicker([pickerDungeonRoute('keyBravo', 'Bravo')]);

        // Assert
        expect(orderedSelect.getIds()).toEqual(['11', '12']);
        expect(jQuery('#pinned_list .ordered_select_item[data-id="12"] .ordered_select_label').text()).toBe('Bravo — Two');
        expect(jQuery('#pinned_list .ordered_select_item[data-id="12"] input[type="hidden"]').val()).toBe('12');
    });

    it('tells the drawer and the list which routes are pinned after a pick', () => {
        // Act
        confirmPicker([pickerDungeonRoute('keyBravo', 'Bravo')]);

        // Assert
        expect(picker.setExistingPublicKeys).toHaveBeenLastCalledWith(['keyAlpha', 'keyBravo']);
        expect(jQuery('#pinned_add_button').prop('disabled')).toBe(true);
        expect(jQuery('#pinned_full').prop('hidden')).toBe(false);
    });

    it('skips a picked route that cannot be pinned', () => {
        // Act
        confirmPicker([pickerDungeonRoute('keyUnknown', 'Unknown'), pickerDungeonRoute('keyCharlie', 'Charlie')]);

        // Assert
        expect(orderedSelect.getIds()).toEqual(['11', '13']);
        expect(picker.setExistingPublicKeys).toHaveBeenLastCalledWith(['keyAlpha', 'keyCharlie']);
    });

    it('hands a route removed from the list back to the drawer and frees its place', () => {
        // Arrange
        confirmPicker([pickerDungeonRoute('keyBravo', 'Bravo')]);

        // Act
        jQuery('#pinned_list .ordered_select_item[data-id="11"] .ordered_select_remove').trigger('click');

        // Assert
        expect(orderedSelect.getIds()).toEqual(['12']);
        expect(picker.setExistingPublicKeys).toHaveBeenLastCalledWith(['keyBravo']);
        expect(jQuery('#pinned_add_button').prop('disabled')).toBe(false);
    });
});
