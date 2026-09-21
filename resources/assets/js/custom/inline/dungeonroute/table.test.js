// ---------------------------------------------------------------------------
// DungeonrouteTable is a global-script style class extending the bare global `InlineCode`.
// These tests target two pure helper methods used by the team routes table's "addremoveroute"
// column (see #3692): which action-dropdown template (and its variables) to render for a route,
// and the variables that drive that dropdown's conditional items (publish state highlighting,
// migrate-to-* availability, the "new mapping version" warning icon). Neither method touches
// `this` beyond `options.currentUserId` and (for `_getAddRemoveRouteTemplate`) the sibling
// `_getProfileActionsTemplateVariables` method, so they're called via `.call()` on a lightweight
// context that inherits from the real prototype, without constructing a real instance (which
// would otherwise need jQuery/Handlebars/DataTables globals).
// ---------------------------------------------------------------------------

globalThis.AFFIX_ENCRYPTED = 'Encrypted';
globalThis.AFFIX_SHROUDED = 'Shrouded';
globalThis.EXPANSION_SHADOWLANDS = 'sl';
globalThis.EXPANSION_DRAGONFLIGHT = 'df';

const fs = require('node:fs');
const path = require('node:path');

const {InlineCode} = require('../inlinecode');
globalThis.InlineCode = InlineCode;

// `_renderTitle` renders Handlebars templates through the bare globals the bundle provides.
const Handlebars = require('handlebars');
globalThis.Handlebars = Handlebars;
globalThis.getHandlebarsDefaultVariables = () => ({});
globalThis.$.extend = (target, ...sources) => Object.assign(target, ...sources);

const {DungeonrouteTable} = require('./table');

/**
 * @param {Object} overrides
 * @returns {Object}
 */
function buildRow(overrides = {}) {
    return Object.assign({
        public_key: 'abc123',
        published: 'world',
        has_team: true,
        author: {id: 1},
        dungeon: {expansion: {shortname: EXPANSION_DRAGONFLIGHT}},
        affixes: [],
        dungeon_latest_mapping_version_id: 5,
        mapping_version_id: 5,
    }, overrides);
}

/**
 * A `this` context for `.call()`-ing DungeonrouteTable prototype methods directly, without
 * constructing a real instance. Inherits from the real prototype (so methods like
 * `_getAddRemoveRouteTemplate` can call sibling methods such as
 * `_getProfileActionsTemplateVariables` on `this`) while only setting the `options` own property
 * the tested methods actually read.
 * @param {number} currentUserId
 * @returns {Object}
 */
function buildTableContext(currentUserId = 1) {
    return Object.assign(Object.create(DungeonrouteTable.prototype), {options: {currentUserId}});
}

describe('DungeonrouteTable._getAddRemoveRouteTemplate', () => {
    it('_getAddRemoveRouteTemplate_givenOwnedRouteOnTeam_returnsOwnRouteActionsTemplateWithProfileActionsVariables', () => {
        // Arrange
        const row = buildRow({has_team: true, author: {id: 1}});

        // Act
        const result = DungeonrouteTable.prototype._getAddRemoveRouteTemplate.call(buildTableContext(1), row);

        // Assert: the variable set must be exactly what _getProfileActionsTemplateVariables would
        // produce for this row - the template name and its variables come from the same branch,
        // so they cannot drift apart the way they could when the caller re-derived one from the
        // other via a repeated template-name string comparison.
        expect(result.templateName).toBe('team_dungeonroute_table_route_actions_own_route');
        expect(result.variables).toEqual(DungeonrouteTable.prototype._getProfileActionsTemplateVariables(row));
    });

    it('_getAddRemoveRouteTemplate_givenNotOwnedRouteOnTeam_returnsRouteActionsTemplateWithPublicKeyOnly', () => {
        // Arrange
        const row = buildRow({has_team: true, author: {id: 2}, public_key: 'abc123'});

        // Act
        const result = DungeonrouteTable.prototype._getAddRemoveRouteTemplate.call(buildTableContext(1), row);

        // Assert
        expect(result.templateName).toBe('team_dungeonroute_table_route_actions');
        expect(result.variables).toEqual({public_key: 'abc123'});
    });
});

describe('DungeonrouteTable._getProfileActionsTemplateVariables', () => {
    it('_getProfileActionsTemplateVariables_givenShadowlandsRouteWithoutEncryptedOrShroudedAffix_showsBothMigrateOptions', () => {
        // Arrange
        const row = buildRow({dungeon: {expansion: {shortname: EXPANSION_SHADOWLANDS}}, affixes: []});

        // Act
        const result = DungeonrouteTable.prototype._getProfileActionsTemplateVariables(row);

        // Assert
        expect(result.show_migrate_to_encrypted).toBe(true);
        expect(result.show_migrate_to_shrouded).toBe(true);
    });

    it('_getProfileActionsTemplateVariables_givenShadowlandsRouteWithEncryptedAffix_hidesMigrateToEncryptedOnly', () => {
        // Arrange
        const row = buildRow({
            dungeon: {expansion: {shortname: EXPANSION_SHADOWLANDS}},
            affixes: [{affixes: [{key: AFFIX_ENCRYPTED}]}],
        });

        // Act
        const result = DungeonrouteTable.prototype._getProfileActionsTemplateVariables(row);

        // Assert: already encrypted, so migrating to encrypted no longer applies; migrating to
        // shrouded is unaffected since that only checks for an existing shrouded affix.
        expect(result.show_migrate_to_encrypted).toBe(false);
        expect(result.show_migrate_to_shrouded).toBe(true);
    });

    it('_getProfileActionsTemplateVariables_givenShadowlandsRouteWithShroudedAffix_hidesBothMigrateOptions', () => {
        // Arrange
        const row = buildRow({
            dungeon: {expansion: {shortname: EXPANSION_SHADOWLANDS}},
            affixes: [{affixes: [{key: AFFIX_SHROUDED}]}],
        });

        // Act
        const result = DungeonrouteTable.prototype._getProfileActionsTemplateVariables(row);

        // Assert
        expect(result.show_migrate_to_encrypted).toBe(false);
        expect(result.show_migrate_to_shrouded).toBe(false);
    });

    it('_getProfileActionsTemplateVariables_givenNonShadowlandsRoute_hidesBothMigrateOptions', () => {
        // Arrange
        const row = buildRow({dungeon: {expansion: {shortname: EXPANSION_DRAGONFLIGHT}}, affixes: []});

        // Act
        const result = DungeonrouteTable.prototype._getProfileActionsTemplateVariables(row);

        // Assert
        expect(result.show_migrate_to_encrypted).toBe(false);
        expect(result.show_migrate_to_shrouded).toBe(false);
    });

    it('_getProfileActionsTemplateVariables_givenMappingVersionMismatch_setsHasNewMappingVersionTrue', () => {
        // Arrange
        const row = buildRow({dungeon_latest_mapping_version_id: 7, mapping_version_id: 5});

        // Act
        const result = DungeonrouteTable.prototype._getProfileActionsTemplateVariables(row);

        // Assert
        expect(result.has_new_mapping_version).toBe(true);
    });

    it('_getProfileActionsTemplateVariables_givenMatchingMappingVersion_setsHasNewMappingVersionFalse', () => {
        // Arrange
        const row = buildRow({dungeon_latest_mapping_version_id: 5, mapping_version_id: 5});

        // Act
        const result = DungeonrouteTable.prototype._getProfileActionsTemplateVariables(row);

        // Assert
        expect(result.has_new_mapping_version).toBe(false);
    });

    it('_getProfileActionsTemplateVariables_givenContinuationSeason_showsContinueInSeason', () => {
        // Arrange
        const getSpy = vi.spyOn(lang, 'get');
        const row = buildRow({continuation_season: {id: 18, name: 'The War Within Season 3'}});

        // Act
        const result = DungeonrouteTable.prototype._getProfileActionsTemplateVariables(row);

        // Assert
        expect(result.show_continue_in_season).toBe(true);
        expect(result.continuation_season_name).toBe('The War Within Season 3');
        expect(getSpy).toHaveBeenCalledWith('js.route_continue_in_season_label', {season: 'The War Within Season 3'});
        expect(getSpy).toHaveBeenCalledWith('js.route_continue_in_season_hint', {season: 'The War Within Season 3'});
    });

    it('_getProfileActionsTemplateVariables_givenNullContinuationSeason_hidesContinueInSeason', () => {
        // Arrange
        const row = buildRow({continuation_season: null});

        // Act
        const result = DungeonrouteTable.prototype._getProfileActionsTemplateVariables(row);

        // Assert
        expect(result.show_continue_in_season).toBe(false);
        expect(result.continue_in_season_label).toBeNull();
    });

    it('_getProfileActionsTemplateVariables_givenRowWithoutContinuationSeason_hidesContinueInSeason', () => {
        // Arrange: public listings carry no continuation_season key at all
        const row = buildRow();

        // Act
        const result = DungeonrouteTable.prototype._getProfileActionsTemplateVariables(row);

        // Assert
        expect(result.show_continue_in_season).toBe(false);
    });
});

describe('DungeonrouteTable._getProfileActionsTemplateVariables add to collection', () => {
    it('_getProfileActionsTemplateVariables_givenOwnRouteWithAddToCollectionShown_setsShowAddToCollectionTrue', () => {
        // Arrange
        const context = buildTableContext(1);
        context.options.showAddToCollection = true;

        // Act
        const result = DungeonrouteTable.prototype._getProfileActionsTemplateVariables.call(context, buildRow({author: {id: 1}}));

        // Assert
        expect(result.show_add_to_collection).toBe(true);
    });

    it('_getProfileActionsTemplateVariables_givenSomeoneElsesRoute_setsShowAddToCollectionFalse', () => {
        // Arrange
        const context = buildTableContext(1);
        context.options.showAddToCollection = true;

        // Act
        const result = DungeonrouteTable.prototype._getProfileActionsTemplateVariables.call(context, buildRow({author: {id: 2}}));

        // Assert
        expect(result.show_add_to_collection).toBe(false);
    });

    it('_getProfileActionsTemplateVariables_givenAddToCollectionNotShown_setsShowAddToCollectionFalse', () => {
        // Arrange
        const context = buildTableContext(1);

        // Act
        const result = DungeonrouteTable.prototype._getProfileActionsTemplateVariables.call(context, buildRow({author: {id: 1}}));

        // Assert
        expect(result.show_add_to_collection).toBe(false);
    });
});

describe('DungeonrouteTable._renderTitle', () => {
    /**
     * The real title template, compiled from the same source the build precompiles, so the test
     * exercises the actual combination of composed string and template placeholders.
     * @returns {Object} A `this` context with the templates `_renderTitle` renders.
     */
    function buildRenderTitleContext() {
        const templateSource = fs.readFileSync(
            path.join(__dirname, '../../../handlebars/dungeonroute_table_title.handlebars'),
            'utf8'
        );

        Handlebars.templates = {
            dungeonroute_table_title_published: () => '<i class="fas fa-globe"></i>',
            dungeonroute_table_title: Handlebars.compile(templateSource),
        };

        return buildTableContext(1);
    }

    it('_renderTitle_givenTitleContainingMarkup_returnsTitleEscaped', () => {
        // Arrange
        const row = buildRow({title: '<b>bold</b>'});

        // Act
        const result = DungeonrouteTable.prototype._renderTitle.call(buildRenderTitleContext(), null, 'display', row, null, false);

        // Assert
        expect(result).toContain('&lt;b&gt;bold&lt;/b&gt;');
        expect(result).not.toContain('<b>bold</b>');
    });

    it('_renderTitle_givenPlainTitle_returnsTitleAlongsidePublishedMarkup', () => {
        // Arrange
        const row = buildRow({title: 'My route'});

        // Act
        const result = DungeonrouteTable.prototype._renderTitle.call(buildRenderTitleContext(), null, 'display', row, null, false);

        // Assert: the markup this method builds itself is still rendered as markup.
        expect(result).toContain('My route');
        expect(result).toContain('<i class="fas fa-globe"></i>');
    });
});

describe('DungeonrouteTable._renderAuthor', () => {
    it('_renderAuthor_givenNameContainingMarkup_returnsNameEscaped', () => {
        // Arrange
        const row = buildRow({author: {id: 2, name: '<img src=x onerror=alert(1)>'}});

        // Act
        const result = DungeonrouteTable.prototype._renderAuthor.call(buildTableContext(1), row.author.name, 'display', row, null);

        // Assert: the cell must render the name as text, so parsing the output creates no element.
        expect(result).toContain('&lt;img');
        expect(result).not.toContain('<img');
        const cell = document.createElement('td');
        cell.innerHTML = result;
        expect(cell.children.length).toBe(0);
        expect(cell.textContent).toBe('<img src=x onerror=alert(1)>');
    });

    it('_renderAuthor_givenPlainName_returnsNameUnchanged', () => {
        // Arrange
        const row = buildRow({author: {id: 2, name: 'Wotuu'}});

        // Act
        const result = DungeonrouteTable.prototype._renderAuthor.call(buildTableContext(1), row.author.name, 'display', row, null);

        // Assert
        expect(result).toBe('Wotuu');
    });

    it('_renderAuthor_givenMissingName_returnsEmptyString', () => {
        // Arrange
        const row = buildRow({author: {id: 2}});

        // Act
        const result = DungeonrouteTable.prototype._renderAuthor.call(buildTableContext(1), row.author.name, 'display', row, null);

        // Assert
        expect(result).toBe('');
    });
});

/**
 * Swaps the setup file's minimal `$` stub for real jQuery around each test of the calling
 * describe block, since the methods under test query the DOM and issue AJAX requests.
 */
function useRealJQuery() {
    let stubbedJQuery;

    beforeEach(() => {
        stubbedJQuery = globalThis.$;
        globalThis.$ = require('jquery');
    });

    afterEach(() => {
        globalThis.$ = stubbedJQuery;
    });
}

describe('DungeonrouteTable._applyFilters', () => {
    useRealJQuery();

    /**
     * @param {Object|null} dt
     * @returns {Object}
     */
    function buildFilterContext(dt) {
        return Object.assign(Object.create(DungeonrouteTable.prototype), {
            _dt: dt,
            options: {
                dungeonSelectId: '#missing_dungeon',
                affixSelectId: '#missing_affix',
                attributesSelectId: '#missing_attributes',
            },
        });
    }

    it('_applyFilters_givenTableNotBuiltYet_doesNothing', () => {
        // Arrange
        const context = buildFilterContext(null);

        // Act
        const act = () => DungeonrouteTable.prototype._applyFilters.call(context);

        // Assert
        expect(act).not.toThrow();
    });

    it('_applyFilters_givenBuiltTable_searchesTheFilterColumnsAndRedraws', () => {
        // Arrange
        const columnSearch = vi.fn();
        const dt = {
            settings: () => ({
                init: () => ({
                    columns: [{name: 'title'}, {name: 'dungeon_id'}, {name: 'affixes.id'}, {name: 'routeattributes.name'}],
                }),
            }),
            column: vi.fn(() => ({search: columnSearch})),
            draw: vi.fn(),
        };

        // Act
        DungeonrouteTable.prototype._applyFilters.call(buildFilterContext(dt));

        // Assert
        expect(dt.column).toHaveBeenCalledTimes(3);
        expect(dt.column).toHaveBeenNthCalledWith(1, 1);
        expect(dt.column).toHaveBeenNthCalledWith(2, 2);
        expect(dt.column).toHaveBeenNthCalledWith(3, 3);
        expect(columnSearch).toHaveBeenNthCalledWith(2, []);
        expect(dt.draw).toHaveBeenCalledTimes(1);
    });
});

describe('DungeonrouteTable._promptDeleteDungeonRouteClicked', () => {
    useRealJQuery();

    /**
     * @param {Object} pageInfo
     * @returns {{dt: Object, context: Object, boundHandler: Function, clickEvent: Object}}
     */
    function arrangeConfirmedDelete(pageInfo) {
        document.body.innerHTML = '<button id="filter"></button><a class="dungeonroute-delete" data-publickey="abc123"></a>';
        globalThis.lang = {get: (key) => key};
        globalThis.showSuccessNotification = vi.fn();
        globalThis.showConfirmYesCancel = (message, onYes) => onYes();
        vi.spyOn($, 'ajax').mockImplementation((settings) => settings.success({}));
        const calls = [];
        const dt = {
            page: Object.assign(vi.fn((direction) => calls.push(`page:${direction}`)), {info: () => pageInfo}),
            draw: vi.fn((resetPaging) => calls.push(`draw:${resetPaging}`)),
        };
        const context = Object.assign(Object.create(DungeonrouteTable.prototype), {_dt: dt, options: {filterButtonSelector: '#filter'}});
        const boundHandler = DungeonrouteTable.prototype._promptDeleteDungeonRouteClicked.bind(context);
        const clickEvent = {target: document.querySelector('.dungeonroute-delete'), preventDefault: vi.fn()};

        return {dt, calls, boundHandler, clickEvent};
    }

    it('_promptDeleteDungeonRouteClicked_givenConfirmedDeleteOnMiddlePage_redrawsInPlaceKeepingThePage', () => {
        // Arrange
        const {dt, calls, boundHandler, clickEvent} = arrangeConfirmedDelete({page: 1, pages: 3, start: 25, end: 50});
        const filterClicked = vi.fn();
        document.getElementById('filter').addEventListener('click', filterClicked);

        // Act
        boundHandler(clickEvent);

        // Assert
        expect($.ajax).toHaveBeenCalledWith(expect.objectContaining({type: 'DELETE', url: '/ajax/abc123'}));
        expect(dt.draw).toHaveBeenCalledTimes(1);
        expect(calls).toEqual(['draw:false']);
        expect(filterClicked).not.toHaveBeenCalled();
        expect(clickEvent.preventDefault).toHaveBeenCalledTimes(1);
    });

    it('_promptDeleteDungeonRouteClicked_givenDeletingLastRowOfLaterPage_stepsBackOnePageBeforeRedrawing', () => {
        // Arrange
        const {calls, boundHandler, clickEvent} = arrangeConfirmedDelete({page: 2, pages: 3, start: 50, end: 51});

        // Act
        boundHandler(clickEvent);

        // Assert
        expect(calls).toEqual(['page:previous', 'draw:false']);
    });

    it('_promptDeleteDungeonRouteClicked_givenDeletingOnlyRowOfFirstPage_staysOnFirstPage', () => {
        // Arrange
        const {dt, calls, boundHandler, clickEvent} = arrangeConfirmedDelete({page: 0, pages: 1, start: 0, end: 1});

        // Act
        boundHandler(clickEvent);

        // Assert
        expect(dt.page).not.toHaveBeenCalled();
        expect(calls).toEqual(['draw:false']);
    });
});

describe('DungeonrouteTable.redrawKeepingPage', () => {
    it('redrawKeepingPage_givenNoRowRemoved_redrawsWithoutTouchingThePage', () => {
        // Arrange
        const dt = {page: Object.assign(vi.fn(), {info: vi.fn()}), draw: vi.fn()};
        const context = Object.assign(Object.create(DungeonrouteTable.prototype), {_dt: dt});

        // Act
        DungeonrouteTable.prototype.redrawKeepingPage.call(context);

        // Assert
        expect(dt.page).not.toHaveBeenCalled();
        expect(dt.draw).toHaveBeenCalledWith(false);
    });
});
