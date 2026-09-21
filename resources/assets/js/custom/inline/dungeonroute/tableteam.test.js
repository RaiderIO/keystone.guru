const {DungeonRouteTableTeam} = require('./tableteam');

describe('DungeonRouteTableTeam._removeFromThisTeam', () => {
    it('_removeFromThisTeam_givenSuccessfulRemoval_redrawsTheTableKeepingTheCurrentPage', () => {
        // Arrange
        globalThis.lang = {get: (key) => key};
        globalThis.showSuccessNotification = vi.fn();
        globalThis.guardedAjaxClick = vi.fn((element, settings) => settings.success({}));
        const clickEvent = {currentTarget: {getAttribute: () => 'abc123'}};
        globalThis.$ = vi.fn(() => ({attr: () => 'abc123'}));
        const dungeonrouteTable = {
            getTableView: () => ({getTeamPublicKey: () => 'team123'}),
            redrawKeepingPage: vi.fn(),
        };
        const handler = new DungeonRouteTableTeam(dungeonrouteTable);

        // Act
        handler._removeFromThisTeam(clickEvent);

        // Assert
        expect(guardedAjaxClick).toHaveBeenCalledWith(clickEvent.currentTarget, expect.objectContaining({url: '/ajax/team/team123/route/abc123'}));
        expect(dungeonrouteTable.redrawKeepingPage).toHaveBeenCalledWith(true);
    });
});
