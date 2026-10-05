const {
    ProfileTableView,
    UserProfileTableView,
    FavoritesTableView,
    TeamTableView,
    TeamRoutePublishingTableView,
} = require('./tableview');

/**
 * Bootstrap's display utilities hide a cell with `d-none` and show it again from a breakpoint
 * with `d-{breakpoint}-table-cell`; these are the only classes the route table views use for it.
 */
const BREAKPOINTS = ['sm', 'md', 'lg', 'xl', 'xxl'];

/**
 * @param {string} className
 * @param {string|null} breakpoint null for the smallest screens (below sm)
 * @returns {boolean}
 */
function isVisibleAt(className, breakpoint) {
    const classes = (className ?? '').split(' ');
    if (!classes.includes('d-none')) {
        return true;
    }

    const shownFrom = BREAKPOINTS.findIndex(name => classes.includes(`d-${name}-table-cell`));

    return breakpoint !== null && shownFrom !== -1 && shownFrom <= BREAKPOINTS.indexOf(breakpoint);
}

/**
 * @param {Object} tableView
 * @param {string} viewMode
 * @param {string|null} breakpoint
 * @returns {string[]}
 */
function visibleColumnNames(tableView, viewMode, breakpoint) {
    return tableView.getColumns(viewMode)
        .filter(column => isVisibleAt(column.className, breakpoint))
        .map(column => column.name);
}

/**
 * @param {Function} TableViewClass
 * @param {boolean} isUserModerator
 * @returns {Object}
 */
function buildTableView(TableViewClass, isUserModerator) {
    const tableView = new TableViewClass();
    if (typeof tableView.setIsUserModerator === 'function') {
        tableView.setIsUserModerator(isUserModerator);
    }

    return tableView;
}

describe('TableView.getColumns', () => {
    let originalJquery;

    beforeEach(() => {
        originalJquery = globalThis.$;
        globalThis.$ = vi.fn(() => ({val: () => '-1'}));
    });

    afterEach(() => {
        globalThis.$ = originalJquery;
    });

    it.each([
        ['UserProfileTableView', false, 'biglist', UserProfileTableView, ['preview', 'title_description', 'enemy_forces']],
        ['UserProfileTableView', false, 'list', UserProfileTableView, ['title', 'dungeon', 'enemy_forces']],
        ['FavoritesTableView', false, 'biglist', FavoritesTableView, ['preview', 'title_description', 'enemy_forces']],
        ['FavoritesTableView', false, 'list', FavoritesTableView, ['title', 'dungeon', 'enemy_forces']],
        ['ProfileTableView', false, 'biglist', ProfileTableView, ['title_description', 'actions']],
        ['ProfileTableView', false, 'list', ProfileTableView, ['title', 'actions']],
        ['TeamTableView', true, 'biglist', TeamTableView, ['title_description', 'addremoveroute']],
        ['TeamTableView', true, 'list', TeamTableView, ['title', 'addremoveroute']],
        ['TeamTableView', false, 'biglist', TeamTableView, ['title_description']],
        ['TeamTableView', false, 'list', TeamTableView, ['title']],
        ['TeamRoutePublishingTableView', true, 'biglist', TeamRoutePublishingTableView, ['title', 'scheduling']],
        ['TeamRoutePublishingTableView', true, 'list', TeamRoutePublishingTableView, ['title', 'scheduling']],
    ])('getColumns_given%sOnAPhone_returnsOnlyThePriorityColumnsAsVisible (moderator: %s, %s)', (name, isUserModerator, viewMode, TableViewClass, expected) => {
        // Arrange
        const tableView = buildTableView(TableViewClass, isUserModerator);

        // Act
        const visible = visibleColumnNames(tableView, viewMode, null);

        // Assert
        expect(visible).toEqual(expected);
    });

    it.each([
        ['ProfileTableView', false, 'list', ProfileTableView, ['title', 'dungeon', 'actions']],
        ['TeamTableView', true, 'list', TeamTableView, ['title', 'dungeon', 'addremoveroute']],
    ])('getColumns_given%sOnALargePhone_returnsTheDungeonColumnAsVisible (moderator: %s, %s)', (name, isUserModerator, viewMode, TableViewClass, expected) => {
        // Arrange
        const tableView = buildTableView(TableViewClass, isUserModerator);

        // Act
        const visible = visibleColumnNames(tableView, viewMode, 'sm');

        // Assert
        expect(visible).toEqual(expected);
    });

    it.each([
        ['UserProfileTableView', false, 'biglist', UserProfileTableView, ['preview', 'title_description', 'dungeon', 'enemy_forces', 'views', 'rating']],
        ['UserProfileTableView', false, 'list', UserProfileTableView, ['title', 'dungeon', 'enemy_forces', 'views', 'rating']],
        ['ProfileTableView', false, 'biglist', ProfileTableView, ['preview', 'title_description', 'dungeon', 'author', 'enemy_forces', 'actions']],
        ['ProfileTableView', false, 'list', ProfileTableView, ['title', 'dungeon', 'author', 'enemy_forces', 'actions']],
        ['TeamTableView', true, 'biglist', TeamTableView, ['preview', 'title_description', 'features', 'addremoveroute']],
        ['TeamTableView', true, 'list', TeamTableView, ['title', 'dungeon', 'features', 'enemy_forces', 'author', 'addremoveroute']],
        ['TeamRoutePublishingTableView', true, 'biglist', TeamRoutePublishingTableView, ['preview', 'title', 'scheduling']],
        ['TeamRoutePublishingTableView', true, 'list', TeamRoutePublishingTableView, ['title', 'dungeon', 'enemy_forces', 'scheduling']],
    ])('getColumns_given%sOnATablet_returnsTheDesktopColumnsAsVisible (moderator: %s, %s)', (name, isUserModerator, viewMode, TableViewClass, expected) => {
        // Arrange
        const tableView = buildTableView(TableViewClass, isUserModerator);

        // Act
        const visible = visibleColumnNames(tableView, viewMode, 'md');

        // Assert
        expect(visible).toEqual(expected);
    });

    it.each([
        ['UserProfileTableView', false, UserProfileTableView],
        ['FavoritesTableView', false, FavoritesTableView],
        ['ProfileTableView', false, ProfileTableView],
        ['TeamTableView', true, TeamTableView],
        ['TeamRoutePublishingTableView', true, TeamRoutePublishingTableView],
    ])('getColumns_given%sOnADesktop_returnsEveryColumnAsVisible (moderator: %s)', (name, isUserModerator, TableViewClass) => {
        // Arrange
        const tableView = buildTableView(TableViewClass, isUserModerator);

        for (const viewMode of ['biglist', 'list']) {
            const allColumns = tableView.getColumns(viewMode).map(column => column.name);

            // Act
            const visible = visibleColumnNames(tableView, viewMode, 'lg');

            // Assert
            expect(allColumns.length).toBeGreaterThan(0);
            expect(visible).toEqual(allColumns);
        }
    });
});
