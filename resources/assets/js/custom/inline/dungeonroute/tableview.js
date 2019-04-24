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
            table: [
                'dungeon',
                'affixes',
                'attributes',
                'setup',
                'author',
                'views',
                'rating'
            ],
            biglist: [
                'preview',
                'dungeon',
                'features',
                'author',
                'views',
                'rating'
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
            table: [
                'title',
                'dungeon',
                'affixes',
                'attributes',
                'setup',
                'published',
                'actions'
            ],
            biglist: [
                'preview',
                'title',
                'dungeon',
                'features',
                'published',
                'actions'
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
            table: [
                'title',
                'dungeon',
                'affixes',
                'attributes',
                'setup',
                'author'
            ],
            biglist: [
                'preview',
                'title',
                'dungeon',
                'features',
                'author'
            ]
        };
    }

    getName() {
        return 'team';
    }
}