const {DungeonRouteTableSelection} = require('./tableselection');

/**
 * One row checkbox per public key, as a freshly drawn page of the table renders them: unticked and enabled.
 * @param {string[]} publicKeys
 * @returns {HTMLInputElement[]}
 */
function drawCheckboxes(publicKeys) {
    return publicKeys.map(publicKey => {
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.value = publicKey;

        return checkbox;
    });
}

describe('DungeonRouteTableSelection', () => {
    test('select_givenTheMaximumReached_returnsFalseAndKeepsTheSelection', () => {
        // Arrange
        const selection = new DungeonRouteTableSelection(['a', 'b'], 2);

        // Act
        const changed = selection.select('c');

        // Assert
        expect(changed).toBe(false);
        expect(selection.isFull()).toBe(true);
        expect(selection.getSelectedPublicKeys()).toEqual(['a', 'b']);
    });

    test('applyToCheckboxes_givenTheMaximumReached_disablesTheUnselectedCheckboxes', () => {
        // Arrange
        const selection = new DungeonRouteTableSelection([], 2);
        const checkboxes = drawCheckboxes(['a', 'b', 'c', 'd']);
        selection.select('a');
        selection.select('c');

        // Act
        selection.applyToCheckboxes(checkboxes);

        // Assert
        expect(checkboxes.map(checkbox => checkbox.checked)).toEqual([true, false, true, false]);
        expect(checkboxes.map(checkbox => checkbox.disabled)).toEqual([false, true, false, true]);
    });

    test('applyToCheckboxes_givenTheMaximumNotReached_enablesEveryCheckbox', () => {
        // Arrange
        const selection = new DungeonRouteTableSelection(['a', 'b'], 2);
        const checkboxes = drawCheckboxes(['a', 'b', 'c']);
        selection.applyToCheckboxes(checkboxes);

        // Act
        selection.deselect('b');
        selection.applyToCheckboxes(checkboxes);

        // Assert
        expect(checkboxes.map(checkbox => checkbox.checked)).toEqual([true, false, false]);
        expect(checkboxes.map(checkbox => checkbox.disabled)).toEqual([false, false, false]);
    });

    test('applyToCheckboxes_givenARedrawOfAnotherPageAndBack_ticksTheSelectedRowsAgain', () => {
        // Arrange
        const selection = new DungeonRouteTableSelection(['p2-b'], 3);
        selection.applyToCheckboxes(drawCheckboxes(['p1-a', 'p1-b']));
        selection.select('p1-a');
        selection.applyToCheckboxes(drawCheckboxes(['p2-a', 'p2-b']));

        // Act
        const pageOne = drawCheckboxes(['p1-a', 'p1-b']);
        selection.applyToCheckboxes(pageOne);
        const pageTwo = drawCheckboxes(['p2-a', 'p2-b']);
        selection.applyToCheckboxes(pageTwo);

        // Assert
        expect(pageOne.map(checkbox => checkbox.checked)).toEqual([true, false]);
        expect(pageTwo.map(checkbox => checkbox.checked)).toEqual([false, true]);
        expect(selection.getSelectedPublicKeys()).toEqual(['p2-b', 'p1-a']);
    });

    test('constructor_givenDuplicatesAndMoreThanTheMaximum_keepsTheFirstUniqueKeys', () => {
        // Arrange
        const selectedPublicKeys = ['a', 'a', 'b', 'c'];

        // Act
        const selection = new DungeonRouteTableSelection(selectedPublicKeys, 2);

        // Assert
        expect(selection.getSelectedPublicKeys()).toEqual(['a', 'b']);
    });

    test('isFull_givenNoMaximum_returnsFalse', () => {
        // Arrange
        const selection = new DungeonRouteTableSelection(['a', 'b', 'c']);

        // Act
        const isFull = selection.isFull();

        // Assert
        expect(isFull).toBe(false);
        expect(selection.getCount()).toBe(3);
    });
});
