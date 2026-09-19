// TeamEdit is a global-script style class extending the bare global `InlineCode`; `_renderName` is
// called via the prototype without constructing an instance (which would need jQuery/DataTables).

const {InlineCode} = require('../inlinecode');
globalThis.InlineCode = InlineCode;
globalThis.Handlebars = require('handlebars');

const {TeamEdit} = require('./edit');

describe('TeamEdit._renderName', () => {
    it('_renderName_givenNameContainingMarkup_returnsNameEscaped', () => {
        // Arrange
        const name = '<img src=x onerror=alert(1)>';

        // Act
        const result = TeamEdit.prototype._renderName(name, 'display', {name}, null);

        // Assert: the cell must render the name as text, so parsing the output creates no element.
        const cell = document.createElement('td');
        cell.innerHTML = result;
        expect(cell.children.length).toBe(0);
        expect(cell.textContent).toBe(name);
    });

    it('_renderName_givenPlainName_returnsNameUnchanged', () => {
        // Arrange
        const name = 'Wotuu';

        // Act
        const result = TeamEdit.prototype._renderName(name, 'display', {name}, null);

        // Assert
        expect(result).toBe('Wotuu');
    });
});

describe('TeamEdit route picker host', () => {
    const jQuery = require('jquery');

    /**
     * A `this` for TeamEdit prototype methods: a stub picker and a real jQuery, no DataTables.
     * @param {string[]} teamRoutePublicKeys
     * @returns {Object}
     */
    function buildContext(teamRoutePublicKeys = []) {
        return Object.assign(Object.create(TeamEdit.prototype), {
            options: {teamPublicKey: 'team123', dungeonrouteFilterSelector: '#dungeonroute_filter'},
            _routePicker: {setExistingPublicKeys: vi.fn(), reload: vi.fn()},
            _teamRoutePublicKeys: new Set(teamRoutePublicKeys),
        });
    }

    beforeEach(() => {
        globalThis.$ = jQuery;
        globalThis.showSuccessNotification = vi.fn();
        globalThis.showInfoNotification = vi.fn();
        globalThis.showErrorNotification = vi.fn();
        globalThis.Noty = {button: vi.fn((text, classes, callback) => ({text, callback}))};
        globalThis.lang = {get: (key, replacements) => replacements ? `${key}:${replacements.count}` : key};
    });

    it('_onRoutesAdded_givenAResponseListingTheAddedRoutes_offersUndoForOnlyThoseRoutes', () => {
        // Arrange
        const context = buildContext(['old1']);
        context._undoAddRoutes = vi.fn();

        // Act
        TeamEdit.prototype._onRoutesAdded.call(context, {publicKeys: ['a', 'b'], response: {public_keys: ['b']}});

        // Assert
        expect([...context._teamRoutePublicKeys]).toEqual(['old1', 'b']);
        expect(showSuccessNotification).toHaveBeenCalledTimes(1);
        expect(showSuccessNotification.mock.calls[0][0]).toBe('js.team_add_route_successful');
        const undoButton = showSuccessNotification.mock.calls[0][1].buttons[0];
        undoButton.callback({close: vi.fn()});
        expect(context._undoAddRoutes).toHaveBeenCalledWith(['b']);
    });

    it('_onRoutesAdded_givenNothingWasNewlyAdded_showsNoUndo', () => {
        // Arrange
        const context = buildContext();

        // Act
        TeamEdit.prototype._onRoutesAdded.call(context, {publicKeys: ['a'], response: {public_keys: []}});

        // Assert
        expect(showSuccessNotification).not.toHaveBeenCalled();
    });

    it('_getRoutesAddedText_givenSeveralRoutes_returnsTheCountedText', () => {
        // Arrange - nothing beyond the translation stub

        // Act
        const result = TeamEdit.prototype._getRoutesAddedText.call(buildContext(), 3);

        // Assert
        expect(result).toBe('js.team_add_routes_successful:3');
    });

    it('_undoAddRoutes_givenEveryRemoveSucceeds_removesEachRouteAndFreesThemInThePicker', () => {
        // Arrange
        const context = buildContext(['a', 'b', 'c']);
        const ajax = vi.spyOn(jQuery, 'ajax').mockImplementation(() => jQuery.Deferred().resolve().promise());

        // Act
        TeamEdit.prototype._undoAddRoutes.call(context, ['a', 'b']);

        // Assert
        expect(ajax.mock.calls.map(call => call[0].url)).toEqual(['/ajax/team/team123/route/a', '/ajax/team/team123/route/b']);
        expect(ajax.mock.calls.every(call => call[0].data._method === 'DELETE')).toBe(true);
        expect(showInfoNotification).toHaveBeenCalledWith('js.team_add_routes_undone');
        expect(context._routePicker.setExistingPublicKeys).toHaveBeenCalledWith(['c']);
    });

    it('_undoAddRoutes_givenARemoveFails_reportsIt', () => {
        // Arrange
        const context = buildContext(['a', 'b']);
        let call = 0;
        vi.spyOn(jQuery, 'ajax').mockImplementation(() => (call++ === 0 ?
            jQuery.Deferred().reject().promise() : jQuery.Deferred().resolve().promise()));

        // Act
        TeamEdit.prototype._undoAddRoutes.call(context, ['a', 'b']);

        // Assert
        expect(showErrorNotification).toHaveBeenCalledWith('js.team_add_routes_undo_failed');
        expect(showInfoNotification).not.toHaveBeenCalled();
    });
});
