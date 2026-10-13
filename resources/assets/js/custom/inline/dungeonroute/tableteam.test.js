const {DungeonRouteTableTeam} = require('./tableteam');

describe('DungeonRouteTableTeam._removeFromThisTeam', () => {
    let previousJquery;
    let previousLang;

    beforeEach(() => {
        previousJquery = globalThis.$;
        previousLang = globalThis.lang;
        globalThis.$ = require('jquery');
        globalThis.lang = {get: (key) => key};
        globalThis.showSuccessNotification = vi.fn();
        globalThis.guardedAjaxClick = vi.fn();
        document.body.innerHTML = '<a class="dungeonroute-remove-from-this-team" data-publickey="abc123" data-id="999"></a>';
    });

    afterEach(() => {
        globalThis.$ = previousJquery;
        globalThis.lang = previousLang;
        document.body.innerHTML = '';
    });

    /**
     * @param {string|null} teamPublicKey
     * @returns {{getTableView: Function, redrawKeepingPage: Function}}
     */
    function buildDungeonrouteTable(teamPublicKey) {
        return {
            getTableView: () => ({getTeamPublicKey: () => teamPublicKey}),
            redrawKeepingPage: vi.fn(),
        };
    }

    it('_removeFromThisTeam_givenSuccessfulRemoval_redrawsTheTableKeepingTheCurrentPage', () => {
        // Arrange
        globalThis.guardedAjaxClick = vi.fn((element, settings) => settings.success({}));
        const clickEvent = {currentTarget: document.querySelector('.dungeonroute-remove-from-this-team')};
        const dungeonrouteTable = buildDungeonrouteTable('team123');
        const handler = new DungeonRouteTableTeam(dungeonrouteTable);

        // Act
        handler._removeFromThisTeam(clickEvent);

        // Assert
        expect(guardedAjaxClick).toHaveBeenCalledWith(clickEvent.currentTarget, expect.objectContaining({url: '/ajax/team/team123/route/abc123'}));
        expect(dungeonrouteTable.redrawKeepingPage).toHaveBeenCalledExactlyOnceWith();
    });

    it('_removeFromThisTeam_givenClick_sendsADeleteThroughAPost', () => {
        // Arrange
        const clickEvent = {currentTarget: document.querySelector('.dungeonroute-remove-from-this-team')};
        const handler = new DungeonRouteTableTeam(buildDungeonrouteTable('team123'));

        // Act
        handler._removeFromThisTeam(clickEvent);

        // Assert
        expect(guardedAjaxClick).toHaveBeenCalledExactlyOnceWith(clickEvent.currentTarget, expect.objectContaining({
            type: 'POST',
            url:  '/ajax/team/team123/route/abc123',
            data: {_method: 'DELETE'},
        }));
    });

    it('_removeFromThisTeam_givenSuccessfulRemoval_confirmsIt', () => {
        // Arrange
        globalThis.guardedAjaxClick = vi.fn((element, settings) => settings.success({}));
        const handler = new DungeonRouteTableTeam(buildDungeonrouteTable('team123'));

        // Act
        handler._removeFromThisTeam({currentTarget: document.querySelector('.dungeonroute-remove-from-this-team')});

        // Assert
        expect(showSuccessNotification).toHaveBeenCalledExactlyOnceWith('js.team_remove_route_successful');
    });

    it('_removeFromThisTeam_givenNoTeam_sendsNothingAndLogsAnError', () => {
        // Arrange
        const errorSpy = vi.spyOn(console, 'error').mockImplementation(() => {
        });
        const handler = new DungeonRouteTableTeam(buildDungeonrouteTable(null));

        // Act
        handler._removeFromThisTeam({currentTarget: document.querySelector('.dungeonroute-remove-from-this-team')});

        // Assert
        expect(guardedAjaxClick).not.toHaveBeenCalled();
        expect(errorSpy).toHaveBeenCalledExactlyOnceWith('Unable to remove from team, team ID not set!');
    });

    it('activate_givenARemoveLinkClicked_removesThatRouteFromTheTeam', () => {
        // Arrange
        const handler = new DungeonRouteTableTeam(buildDungeonrouteTable('team123'));
        handler.activate();

        // Act
        $('.dungeonroute-remove-from-this-team').trigger('click');

        // Assert
        expect(guardedAjaxClick).toHaveBeenCalledExactlyOnceWith(
            document.querySelector('.dungeonroute-remove-from-this-team'),
            expect.objectContaining({url: '/ajax/team/team123/route/abc123'}),
        );
    });
});
