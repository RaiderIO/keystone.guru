class DungeonRouteTableTeam {
    constructor(dungeonrouteTable) {
        /** @type DungeonrouteTable */
        this._dungeonrouteTable = dungeonrouteTable;
    }

    activate() {
        console.assert(this instanceof DungeonRouteTableTeam, 'this is not a DungeonRouteTableTeam', this);

        let $addToThisTeam = $('.dungeonroute-add-to-this-team');
        $addToThisTeam.unbind('click').bind('click', this._addToThisTeam.bind(this));

        let $removeFromThisTeam = $('.dungeonroute-remove-from-this-team');
        $removeFromThisTeam.unbind('click').bind('click', this._removeFromThisTeam.bind(this));
    }

    /**
     * Adds the route to the currently assigned team.
     * @param clickEvent
     * @private
     */
    _addToThisTeam(clickEvent) {
        console.assert(this instanceof DungeonRouteTableTeam, 'this is not a DungeonRouteTableTeam', this);

        let teamPublicKey = this._dungeonrouteTable.getTableView().getTeamPublicKey();
        if (teamPublicKey !== '') {
            let key = $(clickEvent.currentTarget).attr('data-publickey');

            $.ajax({
                type: 'POST',
                url: `/ajax/team/${teamPublicKey}/route/${key}`,
                dataType: 'json',
                success: function (json) {
                    showSuccessNotification(lang.get('messages.team_add_route_successful'));
                    // Refresh the table
                    $('#dungeonroute_filter').trigger('click');
                }
            });
        } else {
            console.error('Unable to add to team, team ID not set!');
        }
    }

    /**
     * Removes a route from the currently assigned team.
     * @param clickEvent
     * @private
     */
    _removeFromThisTeam(clickEvent) {
        console.assert(this instanceof DungeonRouteTableTeam, 'this is not a DungeonRouteTableTeam', this);

        let teamPublicKey = this._dungeonrouteTable.getTableView().getTeamPublicKey();
        if (teamPublicKey !== '') {
            let key = $(clickEvent.currentTarget).attr('data-publickey');

            $.ajax({
                type: 'POST',
                url: `/ajax/team/${teamPublicKey}/route/${key}`,
                data: {
                    _method: 'DELETE'
                },
                dataType: 'json',
                success: function (json) {
                    showSuccessNotification(lang.get('messages.team_remove_route_successful'));
                    // Refresh the table
                    $('#dungeonroute_filter').trigger('click');
                }
            });
        } else {
            console.error('Unable to add to team, team ID not set!');
        }
    }
}
