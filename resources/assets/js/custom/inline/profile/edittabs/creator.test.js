// ---------------------------------------------------------------------------
// ProfileEdittabsCreator is a global-script style class extending the bare global `InlineCode` and
// uses `$`/`jQuery` at runtime, same pattern as the other inline-code tests in this tree.
// ---------------------------------------------------------------------------

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {ProfileEdittabsCreator} = require('./creator');

/**
 * @param {{id: number, title: string, dungeon: string, checked?: boolean}[]} routes
 * @param {number} maxPinnedRoutes
 * @returns {ProfileEdittabsCreator}
 */
function buildAndActivate(routes, maxPinnedRoutes = 6) {
    document.body.innerHTML = `
        <input type="text" id="pinned_dungeon_routes_filter">
        <div id="pinned_dungeon_routes">
            ${routes.map((route) => `
                <label class="pinned-dungeon-route-item" data-search="${(route.title + ' ' + route.dungeon).toLowerCase()}">
                    <input type="checkbox" value="${route.id}" ${route.checked ? 'checked' : ''}>
                </label>
            `).join('')}
        </div>
    `;

    const code = new ProfileEdittabsCreator('creator', 'profile/edittabs/creator', {
        filterInputSelector: '#pinned_dungeon_routes_filter',
        itemSelector: '.pinned-dungeon-route-item',
        maxPinnedRoutes,
    });
    code.activate();

    return code;
}

describe('ProfileEdittabsCreator.activate', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('filterInput_givenSearchTermMatchingOneRoute_hidesTheOthers', () => {
        // Arrange
        buildAndActivate([
            {id: 1, title: 'Speedy Sprint', dungeon: 'Grim Batol'},
            {id: 2, title: 'Slow Stroll', dungeon: 'Operation Mechagon'},
        ]);

        // Act
        $('#pinned_dungeon_routes_filter').val('grim').trigger('input');

        // Assert
        const items = document.querySelectorAll('.pinned-dungeon-route-item');
        expect(items[0].classList.contains('d-none')).toBe(false);
        expect(items[1].classList.contains('d-none')).toBe(true);
    });

    it('filterInput_givenSearchTermCleared_showsAllRoutesAgain', () => {
        // Arrange
        buildAndActivate([
            {id: 1, title: 'Speedy Sprint', dungeon: 'Grim Batol'},
            {id: 2, title: 'Slow Stroll', dungeon: 'Operation Mechagon'},
        ]);
        $('#pinned_dungeon_routes_filter').val('grim').trigger('input');

        // Act
        $('#pinned_dungeon_routes_filter').val('').trigger('input');

        // Assert
        document.querySelectorAll('.pinned-dungeon-route-item').forEach((item) => {
            expect(item.classList.contains('d-none')).toBe(false);
        });
    });

    it('activate_givenAlreadyAtMaxPinnedRoutes_disablesUncheckedCheckboxes', () => {
        // Arrange & Act
        buildAndActivate([
            {id: 1, title: 'Route A', dungeon: 'Dungeon A', checked: true},
            {id: 2, title: 'Route B', dungeon: 'Dungeon B', checked: true},
            {id: 3, title: 'Route C', dungeon: 'Dungeon C'},
        ], 2);

        // Assert
        const checkboxes = document.querySelectorAll('input[type=checkbox]');
        expect(checkboxes[0].disabled).toBe(false);
        expect(checkboxes[1].disabled).toBe(false);
        expect(checkboxes[2].disabled).toBe(true);
    });

    it('checkboxChange_givenCheckingTheLastAllowedRoute_disablesTheRemainingUncheckedOnes', () => {
        // Arrange
        buildAndActivate([
            {id: 1, title: 'Route A', dungeon: 'Dungeon A', checked: true},
            {id: 2, title: 'Route B', dungeon: 'Dungeon B'},
            {id: 3, title: 'Route C', dungeon: 'Dungeon C'},
        ], 2);
        const checkboxes = document.querySelectorAll('input[type=checkbox]');
        expect(checkboxes[2].disabled).toBe(false);

        // Act
        $(checkboxes[1]).prop('checked', true).trigger('change');

        // Assert
        expect(checkboxes[2].disabled).toBe(true);
    });

    it('checkboxChange_givenUncheckingARouteBelowMax_reEnablesTheOthers', () => {
        // Arrange
        buildAndActivate([
            {id: 1, title: 'Route A', dungeon: 'Dungeon A', checked: true},
            {id: 2, title: 'Route B', dungeon: 'Dungeon B', checked: true},
            {id: 3, title: 'Route C', dungeon: 'Dungeon C'},
        ], 2);
        const checkboxes = document.querySelectorAll('input[type=checkbox]');
        expect(checkboxes[2].disabled).toBe(true);

        // Act
        $(checkboxes[0]).prop('checked', false).trigger('change');

        // Assert
        expect(checkboxes[2].disabled).toBe(false);
    });
});
