// ---------------------------------------------------------------------------
// `orderedselect.js` is concatenated into a bundle in the browser and references
// its collaborators as bare globals, so `InlineCode` must be on `globalThis`
// before the class body is evaluated (same pattern as passwordinput.test.js).
//
// The control only moves DOM nodes and flips attributes, so these tests run it
// against a real jQuery over jsdom markup mirroring
// common/forms/orderedselect.blade.php. Draggable is left undefined: dragging
// needs real pointer events, and the up/down buttons cover the same reorder.
// ---------------------------------------------------------------------------

const jQuery = require('jquery');

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {CommonFormsOrderedselect} = require('./orderedselect');

const OPTIONS = {
    listSelector:      '#routes_list',
    templateSelector:  '#routes_template',
    addSelectSelector: '#routes_add',
    addButtonSelector: '#routes_add_button',
    emptySelector:     '#routes_empty',
    countSelector:     '#routes_count',
    fullSelector:      '#routes_full',
    statusSelector:    '#routes_status',
    max:               3,
    countText:         ':count / :max',
    moveUpText:        'Move :name up',
    moveDownText:      'Move :name down',
    removeText:        'Remove :name',
    addedStatusText:   'Added :name at position :position',
    movedStatusText:   'Moved :name to position :position',
    removedStatusText: 'Removed :name',
};

/**
 * @param {Number} id
 * @param {string} name
 * @returns {string}
 */
function itemHtml(id, name) {
    return `
        <li class="list-group-item ordered_select_item" data-id="${id}">
            <span class="ordered_select_handle"></span>
            <span class="ordered_select_position">0</span>
            <span class="ordered_select_label">${name}</span>
            <button type="button" class="ordered_select_up"></button>
            <button type="button" class="ordered_select_down"></button>
            <button type="button" class="ordered_select_remove"></button>
            <input type="hidden" name="routes[]" value="${id}">
        </li>`;
}

describe('CommonFormsOrderedselect', () => {
    let previousJquery;

    beforeEach(() => {
        previousJquery = globalThis.$;
        globalThis.$   = jQuery;

        document.body.innerHTML = `
            <form>
                <span id="routes_count"></span>
                <ol id="routes_list">${itemHtml(2, 'Bravo')}${itemHtml(1, 'Alpha')}</ol>
                <p id="routes_empty" hidden>Empty</p>
                <select id="routes_add">
                    <option value="">Choose</option>
                    <option value="1" disabled>Alpha</option>
                    <option value="2" disabled>Bravo</option>
                    <option value="3">Charlie</option>
                    <option value="4">Delta</option>
                </select>
                <button id="routes_add_button" type="button">Add</button>
                <span id="routes_full" hidden>Full</span>
                <div id="routes_status"></div>
                <template id="routes_template">${itemHtml(0, '')}</template>
            </form>`;

        new CommonFormsOrderedselect('routes', 'common/forms/orderedselect', OPTIONS).activate();
    });

    afterEach(() => {
        globalThis.$            = previousJquery;
        document.body.innerHTML = '';
    });

    /**
     * What the form would post, in order.
     *
     * @returns {string[]}
     */
    function postedIds() {
        return new FormData(document.querySelector('form')).getAll('routes[]');
    }

    /**
     * @param {string} name
     * @returns {HTMLElement}
     */
    function itemNamed(name) {
        return [...document.querySelectorAll('#routes_list .ordered_select_item')]
            .find((item) => item.querySelector('.ordered_select_label').textContent === name);
    }

    function addOption(value) {
        document.querySelector('#routes_add').value = value;
        document.querySelector('#routes_add_button').click();
    }

    it('activate_givenServerRenderedItems_numbersThemAndDisablesTheOuterMoves', () => {
        // Arrange - done in beforeEach

        // Act - done in beforeEach

        // Assert
        expect(postedIds()).toEqual(['2', '1']);
        expect([...document.querySelectorAll('.ordered_select_position')].map((el) => el.textContent)).toEqual(['1', '2']);
        expect(itemNamed('Bravo').querySelector('.ordered_select_up').disabled).toBe(true);
        expect(itemNamed('Alpha').querySelector('.ordered_select_down').disabled).toBe(true);
        expect(itemNamed('Bravo').querySelector('.ordered_select_remove').getAttribute('aria-label')).toBe('Remove Bravo');
        expect(document.querySelector('#routes_count').textContent).toBe('2 / 3');
    });

    it('addSelected_givenAnOption_appendsItAndPostsItLast', () => {
        // Arrange - done in beforeEach

        // Act
        addOption('3');

        // Assert
        expect(postedIds()).toEqual(['2', '1', '3']);
        expect(document.querySelector('#routes_add option[value="3"]').disabled).toBe(true);
        expect(document.querySelector('#routes_add').value).toBe('');
        expect(document.querySelector('#routes_status').textContent).toBe('Added Charlie at position 3');
    });

    it('addSelected_givenTheListReachesMax_disablesAddingAndShowsTheFullNote', () => {
        // Arrange - done in beforeEach

        // Act
        addOption('3');

        // Assert
        expect(document.querySelector('#routes_add').disabled).toBe(true);
        expect(document.querySelector('#routes_add_button').disabled).toBe(true);
        expect(document.querySelector('#routes_full').hidden).toBe(false);
        expect(document.activeElement).toBe(itemNamed('Charlie').querySelector('.ordered_select_remove'));
    });

    it('addSelected_givenNoOptionChosen_addsNothing', () => {
        // Arrange
        document.querySelector('#routes_add').value = '';

        // Act
        document.querySelector('#routes_add_button').click();

        // Assert
        expect(postedIds()).toEqual(['2', '1']);
    });

    it('onMoveClicked_givenMoveUp_swapsTheOrderThatIsPosted', () => {
        // Arrange
        const upButton = itemNamed('Alpha').querySelector('.ordered_select_up');

        // Act
        upButton.click();

        // Assert
        expect(postedIds()).toEqual(['1', '2']);
        expect(itemNamed('Alpha').querySelector('.ordered_select_position').textContent).toBe('1');
        expect(document.querySelector('#routes_status').textContent).toBe('Moved Alpha to position 1');
    });

    it('onMoveClicked_givenTheItemReachesTheTop_movesFocusToItsDownButton', () => {
        // Arrange
        const alpha = itemNamed('Alpha');

        // Act
        alpha.querySelector('.ordered_select_up').click();

        // Assert
        expect(alpha.querySelector('.ordered_select_up').disabled).toBe(true);
        expect(document.activeElement).toBe(alpha.querySelector('.ordered_select_down'));
    });

    it('onMoveClicked_givenMoveDown_swapsTheOrderThatIsPosted', () => {
        // Arrange
        const downButton = itemNamed('Bravo').querySelector('.ordered_select_down');

        // Act
        downButton.click();

        // Assert
        expect(postedIds()).toEqual(['1', '2']);
    });

    it('onRemoveClicked_givenAnItem_removesItAndOffersItAgain', () => {
        // Arrange
        addOption('3');

        // Act
        itemNamed('Charlie').querySelector('.ordered_select_remove').click();

        // Assert
        expect(postedIds()).toEqual(['2', '1']);
        expect(document.querySelector('#routes_add option[value="3"]').disabled).toBe(false);
        expect(document.querySelector('#routes_add').disabled).toBe(false);
        expect(document.querySelector('#routes_full').hidden).toBe(true);
        expect(document.querySelector('#routes_status').textContent).toBe('Removed Charlie');
    });

    it('onRemoveClicked_givenTheLastItem_showsTheEmptyStateAndFocusesTheAddSelect', () => {
        // Arrange
        itemNamed('Bravo').querySelector('.ordered_select_remove').click();

        // Act
        itemNamed('Alpha').querySelector('.ordered_select_remove').click();

        // Assert
        expect(postedIds()).toEqual([]);
        expect(document.querySelector('#routes_list').hidden).toBe(true);
        expect(document.querySelector('#routes_empty').hidden).toBe(false);
        expect(document.activeElement).toBe(document.querySelector('#routes_add'));
    });
});
