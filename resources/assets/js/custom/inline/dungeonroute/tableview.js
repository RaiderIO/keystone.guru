class TableView {
    constructor() {
        this._columns = [];
    }

    getAjaxParameters() {
        return {};
    }

    getName() {
        return 'OVERWRITEME';
    }

    /**
     * Get the names of the columns that should be displayed for this TableView.
     * @param view
     * @returns {*}
     */
    getColumns(view) {
        return this._columns[view];
    }
}

class RoutesTableView extends TableView {
    constructor() {
        super();

        this._columns = {
            list: [
                {name: 'dungeon', width: '15%'},
                {name: 'affixes', width: '15%'},
                {name: 'attributes', width: '15%'},
                {name: 'setup', width: '15%'},
                {name: 'author', width: '10%'},
                {name: 'enemy_forces', width: '5%'},
                {name: 'views', width: '5%'},
                {name: 'rating', width: '5%'},
            ],
            biglist: [
                {name: 'preview', width: '15%', clickable: false},
                {name: 'dungeon', width: '13%', className: 'd-none d-md-table-cell'},
                {name: 'features', width: '20%'},
                {name: 'author', width: '10%'},
                {name: 'enemy_forces', width: '5%'},
                {name: 'views', width: '5%'},
                {name: 'rating', width: '5%'},
            ]
        };
    }

    getName() {
        return 'routes';
    }
}

class ProfileTableView extends TableView {
    constructor() {
        super();

        this._columns = {
            list: [
                {name: 'title', width: '15%', className: 'pt-0 pb-0'},
                {name: 'dungeon', width: '15%'},
                {name: 'affixes', width: '15%', className: 'd-none d-lg-table-cell'},
                {name: 'attributes', width: '15%', className: 'd-none d-lg-table-cell'},
                // {name: 'setup', width: '15%', className: 'd-none d-lg-table-cell'},
                {name: 'enemy_forces', width: '10%'},
                {name: 'actions', width: '10%', clickable: false},
            ],
            biglist: [
                {name: 'preview', width: '15%', clickable: false},
                {name: 'title', width: '15%'},
                {name: 'dungeon', width: '13%', className: 'd-none d-md-table-cell'},
                {name: 'features', width: '25%', className: 'd-none d-lg-table-cell'},
                {name: 'enemy_forces', width: '10%'},
                {name: 'actions', width: '7%', clickable: false},
            ]
        };
    }

    getAjaxParameters() {
        return {mine: 1};
    }

    getName() {
        return 'profile';
    }
}

class FavoritesTableView extends ProfileTableView {
    getAjaxParameters() {
        return {favorites: 1};
    }

    getName() {
        return 'favorites';
    }
}

class UserProfileTableView extends ProfileTableView {
    constructor() {
        super();

        this._user = null;
    }

    getAjaxParameters() {
        return {user_id: this._user.id};
    }

    getName() {
        return 'userprofile';
    }

    /**
     * Sets the user for which we're viewing the profile
     * @param user
     */
    setUser(user) {
        console.log('setUser', user);
        this._user = user;
    }
}

class TeamTableView extends TableView {
    constructor() {
        super();

        this._teamPublicKey = '';
        this._addMode = false;
        this._isUserModerator = false;
    }

    /**
     * Set the team ID (for filtering purposes)
     * @param value
     */
    setTeamPublicKey(value) {
        this._teamPublicKey = value;
    }

    /**
     * Sets 'add route mode' to be enabled or not.
     * @param value True or false.
     */
    setAddMode(value) {
        this._addMode = value;
    }

    setIsUserModerator(value) {
        this._isUserModerator = value;
    }

    /**
     * Gets the Id of the team that was set for this view.
     * @returns {*}
     */
    getTeamPublicKey() {
        return this._teamPublicKey;
    }

    /**
     * Get the parameters when sending the AJAX request
     * @returns {{team_public_key: *}}
     */
    getAjaxParameters() {
        let params = {team_public_key: this._teamPublicKey};
        if (this._addMode) {
            params.available = 1;
        }
        return params;
    }

    getColumns(view) {
        this._columns = {
            list: [
                {name: 'title', width: '15%'},
                {name: 'dungeon', width: '15%'},
                {name: 'features', width: '25%'},
                // {name: 'setup', width: '15%'},
                {name: 'enemy_forces', width: '10%'},
                {name: 'author', width: '10%'},
            ],
            biglist: [
                {name: 'preview', width: '15%', clickable: false},
                {name: 'title', width: '15%'},
                {name: 'dungeon', width: '15%', className: 'd-none d-lg-table-cell'},
                {name: 'features', width: '25%'},
                {name: 'author', width: '10%', className: 'd-none d-lg-table-cell'},
            ]
        };

        // Push different columns based on if add mode is enabled or not
        if (this._isUserModerator) {
            this._columns.list.push({name: 'addremoveroute', width: '15%', clickable: false});
            this._columns.biglist.push({name: 'addremoveroute', width: '15%', clickable: false});
        }

        return super.getColumns(view);
    }

    getName() {
        return 'team';
    }
}