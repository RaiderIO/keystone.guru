class TableView {
    constructor() {
        this._columns = [];
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
                {name: 'author', width: '15%'},
                {name: 'views', width: '5%'},
                {name: 'rating', width: '5%'},
            ],
            biglist: [
                {name: 'preview', width: '15%'},
                {name: 'dungeon', width: '13%'},
                {name: 'features', width: '25%'},
                {name: 'author', width: '10%'},
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
                {name: 'title', width: '15%'},
                {name: 'dungeon', width: '15%'},
                {name: 'affixes', width: '15%'},
                {name: 'attributes', width: '15%'},
                {name: 'setup', width: '15%'},
                {name: 'published', width: '5%'},
                {name: 'actions', width: '10%'},
            ],
            biglist: [
                {name: 'preview', width: '15%'},
                {name: 'title', width: '15%'},
                {name: 'dungeon', width: '13%'},
                {name: 'features', width: '25%'},
                {name: 'published', width: '5%'},
                {name: 'actions', width: '7%'},
            ]
        };
    }

    getName() {
        return 'profile';
    }
}

class TeamTableView extends TableView {
    constructor() {
        super();

        this._columns = {
            list: [
                {name: 'title', width: '15%'},
                {name: 'dungeon', width: '15%'},
                {name: 'affixes', width: '15%'},
                {name: 'attributes', width: '15%'},
                {name: 'setup', width: '15%'},
                {name: 'author', width: '10%'},
                {name: 'actions', width: '15%'},
            ],
            biglist: [
                {name: 'preview', width: '15%'},
                {name: 'title', width: '15%'},
                {name: 'dungeon', width: '15%'},
                {name: 'features', width: '25%'},
                {name: 'author', width: '10%'},
                {name: 'actions', width: '15%'},
            ]
        };
    }

    getName() {
        return 'team';
    }
}