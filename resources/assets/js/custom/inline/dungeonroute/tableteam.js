class DungeonRouteTableTeam {
    constructor(dungeonrouteTable) {
        /** @type DungeonrouteTable */
        this._dungeonrouteTable = dungeonrouteTable;
    }

    activate() {
        console.assert(this instanceof DungeonRouteTableTeam, 'this is not a DungeonRouteTableTeam', this);

        let $removeFromThisTeam = $('.dungeonroute-remove-from-this-team');
        $removeFromThisTeam.unbind('click').bind('click', this._removeFromThisTeam.bind(this));
    }

    /**
     * Removes a route from the currently assigned team.
     * @param clickEvent
     * @private
     */
    _removeFromThisTeam(clickEvent) {
        console.assert(this instanceof DungeonRouteTableTeam, 'this is not a DungeonRouteTableTeam', this);

        let self = this;
        let teamPublicKey = this._dungeonrouteTable.getTableView().getTeamPublicKey();
        if (teamPublicKey !== null) {
            let key = $(clickEvent.currentTarget).attr('data-publickey');

            guardedAjaxClick(clickEvent.currentTarget, {
                type: 'POST',
                url: `/ajax/team/${teamPublicKey}/route/${key}`,
                data: {
                    _method: 'DELETE'
                },
                dataType: 'json',
                success: function (json) {
                    showSuccessNotification(lang.get('js.team_remove_route_successful'));
                    self._dungeonrouteTable.redrawKeepingPage(true);
                }
            });
        } else {
            console.error('Unable to remove from team, team ID not set!');
        }
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {DungeonRouteTableTeam};
}
