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
globalThis.METRIC_CATEGORY_DUNGEON_ROUTE_MDT_COPY = 1;
globalThis.METRIC_TAG_MDT_COPY_VIEW = 'view';
globalThis.METRIC_TAG_MDT_COPY_EMBED = 'embed';
globalThis.DRAFT_SOURCE_MDT_IMPORT = 'mdt_import';

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
        dungeon: {expansion: {key: EXPANSION_DRAGONFLIGHT}},
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
        const row = buildRow({dungeon: {expansion: {key: EXPANSION_SHADOWLANDS}}, affixes: []});

        // Act
        const result = DungeonrouteTable.prototype._getProfileActionsTemplateVariables(row);

        // Assert
        expect(result.show_migrate_to_encrypted).toBe(true);
        expect(result.show_migrate_to_shrouded).toBe(true);
    });

    it('_getProfileActionsTemplateVariables_givenShadowlandsRouteWithEncryptedAffix_hidesMigrateToEncryptedOnly', () => {
        // Arrange
        const row = buildRow({
            dungeon: {expansion: {key: EXPANSION_SHADOWLANDS}},
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
            dungeon: {expansion: {key: EXPANSION_SHADOWLANDS}},
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
        const row = buildRow({dungeon: {expansion: {key: EXPANSION_DRAGONFLIGHT}}, affixes: []});

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
    it('_getProfileActionsTemplateVariables_givenMdtImportDraft_flagsMdtImportDraft', () => {
        // Arrange
        const row = buildRow({is_upgrade_draft: true, draft_source: 'mdt_import'});

        // Act
        const result = DungeonrouteTable.prototype._getProfileActionsTemplateVariables(row);

        // Assert
        expect(result.is_upgrade_draft).toBe(true);
        expect(result.is_mdt_import_draft).toBe(true);
    });

    it('_getProfileActionsTemplateVariables_givenDraftWithoutSource_flagsMappingUpgradeDraft', () => {
        // Arrange: drafts that predate draft_source carry null and are mapping upgrades
        const row = buildRow({is_upgrade_draft: true, draft_source: null});

        // Act
        const result = DungeonrouteTable.prototype._getProfileActionsTemplateVariables(row);

        // Assert
        expect(result.is_upgrade_draft).toBe(true);
        expect(result.is_mdt_import_draft).toBe(false);
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

    it('_renderTitle_givenTitleWithDescription_returnsBothInsideTheRoutesTableTitleWrapper', () => {
        // Arrange
        const row = buildRow({title: 'My route', description: 'Pull everything'});

        // Act
        const result = DungeonrouteTable.prototype._renderTitle.call(buildRenderTitleContext(), null, 'display', row, null, true);

        // Assert: the phone stylesheet breaks long words inside this wrapper only.
        const wrapper = new DOMParser().parseFromString(result, 'text/html').querySelector('.routes_table_title');
        expect(wrapper).not.toBeNull();
        expect(wrapper.textContent).toContain('My route');
        expect(wrapper.querySelector('.description-content').textContent).toContain('Pull everything');
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

    afterEach(() => {
        document.body.innerHTML = '';
    });

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
        expect(columnSearch).toHaveBeenNthCalledWith(3, []);
        expect(dt.draw).toHaveBeenCalledTimes(1);
    });

    it('_applyFilters_givenSelectedFilters_searchesEachFilterColumnForItsSelection', () => {
        // Arrange
        document.body.innerHTML = `
            <select id="dungeon"><option value="-1">All</option><option value="12" selected>Ara-Kara</option></select>
            <select id="affixes" multiple><option value="7" selected>Fortified</option><option value="8" selected>Tyrannical</option></select>
            <select id="attributes" multiple><option value="rogue-shroud" selected>Shroud</option></select>`;
        const searches = {};
        const dt = {
            settings: () => ({
                init: () => ({
                    columns: [{name: 'routeattributes.name'}, {name: 'title'}, {name: 'affixes.id'}, {name: 'dungeon_id'}],
                }),
            }),
            column: vi.fn((index) => ({search: (value) => searches[index] = value})),
            draw: vi.fn(),
        };
        const context = Object.assign(Object.create(DungeonrouteTable.prototype), {
            _dt: dt,
            options: {dungeonSelectId: '#dungeon', affixSelectId: '#affixes', attributesSelectId: '#attributes'},
        });

        // Act
        DungeonrouteTable.prototype._applyFilters.call(context);

        // Assert
        expect(searches).toEqual({0: ['rogue-shroud'], 2: ['7', '8'], 3: '12'});
        expect(dt.draw).toHaveBeenCalledExactlyOnceWith();
    });
});

/**
 * A `this` context whose DataTable is a spy, with a `#filter` button that records clicks so a test
 * can assert the action redrew in place instead of going through the filter button.
 * @returns {{context: Object, dt: Object, filterClicked: Function}}
 */
function arrangeRowActionContext() {
    document.body.innerHTML = '<button id="filter"></button><a class="dungeonroute-delete dungeonroute-continue-in-season" data-publickey="abc123" data-season="Season 3"></a>';
    const filterClicked = vi.fn();
    document.getElementById('filter').addEventListener('click', filterClicked);
    globalThis.lang = {get: (key) => key};
    globalThis.showSuccessNotification = vi.fn();
    globalThis.showConfirmYesCancel = (message, onYes) => onYes();
    vi.spyOn($, 'ajax').mockImplementation((settings) => settings.success({}));
    const dt = {draw: vi.fn()};
    const context = Object.assign(Object.create(DungeonrouteTable.prototype), {_dt: dt, options: {filterButtonSelector: '#filter'}});

    return {context, dt, filterClicked};
}

describe('DungeonrouteTable row actions', () => {
    useRealJQuery();

    it('_promptDeleteDungeonRouteClicked_givenConfirmedDelete_redrawsInPlaceKeepingThePage', () => {
        // Arrange
        const {context, dt, filterClicked} = arrangeRowActionContext();
        const clickEvent = {target: document.querySelector('.dungeonroute-delete'), preventDefault: vi.fn()};

        // Act
        DungeonrouteTable.prototype._promptDeleteDungeonRouteClicked.call(context, clickEvent);

        // Assert
        expect($.ajax).toHaveBeenCalledWith(expect.objectContaining({type: 'DELETE', url: '/ajax/abc123'}));
        expect(dt.draw).toHaveBeenCalledExactlyOnceWith(false);
        expect(filterClicked).not.toHaveBeenCalled();
        expect(clickEvent.preventDefault).toHaveBeenCalledTimes(1);
    });

    it('_promptDeleteDungeonRouteClicked_givenClick_asksWithADangerButtonNamingTheDeletion', () => {
        // Arrange
        const {context} = arrangeRowActionContext();
        globalThis.showConfirmYesCancel = vi.fn();
        const clickEvent = {target: document.querySelector('.dungeonroute-delete'), preventDefault: vi.fn()};

        // Act
        DungeonrouteTable.prototype._promptDeleteDungeonRouteClicked.call(context, clickEvent);

        // Assert
        expect(globalThis.showConfirmYesCancel).toHaveBeenCalledExactlyOnceWith('js.route_delete_confirm', expect.any(Function), null, {
            yesLabel:    'js.route_delete_confirm_yes',
            yesClass:    'btn btn-danger me-1',
            cancelClass: 'btn btn-secondary',
        });
    });

    it('_changePublishState_givenSuccessfulChange_redrawsInPlaceKeepingThePage', () => {
        // Arrange
        const {context, dt, filterClicked} = arrangeRowActionContext();

        // Act
        DungeonrouteTable.prototype._changePublishState.call(context, 'abc123', 'unpublished');

        // Assert
        expect($.ajax).toHaveBeenCalledWith(expect.objectContaining({
            type: 'POST',
            url:  '/ajax/abc123/publishedState',
            data: {published_state: 'unpublished'},
        }));
        expect(dt.draw).toHaveBeenCalledExactlyOnceWith(false);
        expect(filterClicked).not.toHaveBeenCalled();
    });

    it.each([
        ['_migrateTo', (context, clickEvent) => DungeonrouteTable.prototype._migrateTo.call(context, clickEvent, 'encrypted'), '/ajax/abc123/migrate/encrypted'],
        ['_continueInSeasonClicked', (context, clickEvent) => DungeonrouteTable.prototype._continueInSeasonClicked.call(context, clickEvent), '/ajax/abc123/continue'],
    ])('%s_givenConfirmedAction_redrawsInPlaceKeepingThePage', (name, act, expectedUrl) => {
        // Arrange
        const {context, dt, filterClicked} = arrangeRowActionContext();
        const clickEvent = {target: document.querySelector('.dungeonroute-delete'), preventDefault: vi.fn()};

        // Act
        act(context, clickEvent);

        // Assert
        expect($.ajax).toHaveBeenCalledWith(expect.objectContaining({url: expectedUrl}));
        expect(dt.draw).toHaveBeenCalledExactlyOnceWith(false);
        expect(filterClicked).not.toHaveBeenCalled();
    });
});

describe('DungeonrouteTable.overrideSelection', () => {
    useRealJQuery();

    /**
     * A `this` context over the filter selects, with a `refreshSelectPickers` spy that records which
     * values the selects held each time the Tom Select controls were synced to them.
     * @returns {{context: Object, refreshedValues: Array<Object>, filterClicked: Function}}
     */
    function arrangeOverrideContext() {
        document.body.innerHTML = `
            <select id="dungeon"><option value="-1">All</option><option value="5">Dungeon</option></select>
            <select id="affixes" multiple><option value="11">Old</option><option value="12">New</option></select>
            <select id="attributes" multiple><option value="melee">Melee</option></select>
            <select id="requirements" multiple><option value="favorite">Favorite</option></select>
            <select id="tags" multiple><option value="speed">Speed</option></select>
            <button id="filter"></button>`;
        $('#affixes').val(['11']);
        const refreshedValues = [];
        globalThis.refreshSelectPickers = vi.fn(() => refreshedValues.push({dungeon: $('#dungeon').val(), affixes: $('#affixes').val()}));
        const filterClicked = vi.fn();
        document.getElementById('filter').addEventListener('click', filterClicked);
        const context = Object.assign(Object.create(DungeonrouteTable.prototype), {
            options: {
                dungeonSelectId:      '#dungeon',
                affixSelectId:        '#affixes',
                attributesSelectId:   '#attributes',
                requirementsSelectId: '#requirements',
                tagsSelectId:         '#tags',
                filterButtonSelector: '#filter',
            },
        });

        return {context, refreshedValues, filterClicked};
    }

    afterEach(() => {
        delete globalThis.refreshSelectPickers;
        document.body.innerHTML = '';
    });

    it('overrideSelection_givenDungeonAndAffixGroup_syncsSelectPickersToTheOverrideBeforeFiltering', () => {
        // Arrange
        const {context, refreshedValues, filterClicked} = arrangeOverrideContext();
        filterClicked.mockImplementation(() => refreshedValues.push('filtered'));

        // Act
        DungeonrouteTable.prototype.overrideSelection.call(context, 5, [12]);

        // Assert
        expect(refreshedValues).toEqual([{dungeon: '5', affixes: ['12']}, 'filtered']);
    });
});

describe('DungeonrouteTable._stepBackFromEmptyPage', () => {
    /**
     * @param {number} page
     * @returns {{context: Object, dt: Object}}
     */
    function arrangeStepBackContext(page) {
        const chain = {draw: vi.fn()};
        const dt = {page: Object.assign(vi.fn(() => chain), {info: () => ({page})}), chain};

        return {context: Object.assign(Object.create(DungeonrouteTable.prototype), {_dt: dt}), dt};
    }

    it('_stepBackFromEmptyPage_givenEmptyPageAfterFirst_stepsBackOnePageAndRedrawsInPlace', () => {
        // Arrange
        const {context, dt} = arrangeStepBackContext(2);

        // Act
        DungeonrouteTable.prototype._stepBackFromEmptyPage.call(context, {json: {data: []}});

        // Assert
        expect(dt.page).toHaveBeenCalledExactlyOnceWith('previous');
        expect(dt.chain.draw).toHaveBeenCalledExactlyOnceWith(false);
    });

    it('_stepBackFromEmptyPage_givenEmptyFirstPage_staysPut', () => {
        // Arrange
        const {context, dt} = arrangeStepBackContext(0);

        // Act
        DungeonrouteTable.prototype._stepBackFromEmptyPage.call(context, {json: {data: []}});

        // Assert
        expect(dt.page).not.toHaveBeenCalled();
    });

    it('_stepBackFromEmptyPage_givenPageWithSingleRow_staysPut', () => {
        // Arrange
        const {context, dt} = arrangeStepBackContext(2);

        // Act
        DungeonrouteTable.prototype._stepBackFromEmptyPage.call(context, {json: {data: [{public_key: 'abc123'}]}});

        // Assert
        expect(dt.page).not.toHaveBeenCalled();
    });
});

describe('DungeonrouteTable.redrawKeepingPage', () => {
    it('redrawKeepingPage_givenBuiltTable_redrawsWithoutResettingPaging', () => {
        // Arrange
        const dt = {draw: vi.fn()};
        const context = Object.assign(Object.create(DungeonrouteTable.prototype), {_dt: dt});

        // Act
        DungeonrouteTable.prototype.redrawKeepingPage.call(context);

        // Assert
        expect(dt.draw).toHaveBeenCalledExactlyOnceWith(false);
    });
});

describe('DungeonrouteTable._renderViews', () => {
    /**
     * @returns {Object} A `this` context with the real metrics template compiled.
     */
    function buildRenderViewsContext() {
        const templateSource = fs.readFileSync(
            path.join(__dirname, '../../../handlebars/dungeonroute_table_views_metrics.handlebars'),
            'utf8'
        );

        Handlebars.templates = {
            dungeonroute_table_views_metrics: Handlebars.compile(templateSource),
        };

        return buildTableContext(1);
    }

    it('_renderViews_givenLargeCounts_returnsAbbreviatedCountsWithExactCountsOnHover', () => {
        // Arrange
        const row = buildRow({
            views:               52340,
            views_embed:         1200,
            metric_aggregations: [
                {category: METRIC_CATEGORY_DUNGEON_ROUTE_MDT_COPY, tag: METRIC_TAG_MDT_COPY_VIEW, value: 2140},
                {category: METRIC_CATEGORY_DUNGEON_ROUTE_MDT_COPY, tag: METRIC_TAG_MDT_COPY_EMBED, value: 3000000},
            ],
        });

        // Act
        const result = DungeonrouteTable.prototype._renderViews.call(buildRenderViewsContext(), row.views, 'display', row, null);

        // Assert
        expect(result).toContain('<span class="views" title="52340/1200">52.3K/1.2K</span>');
        expect(result).toContain('<span class="copies" title="2140/3000000">2.1K/3M</span>');
    });

    it('_renderViews_givenSmallCountsAndNoMetrics_returnsCountsUnchanged', () => {
        // Arrange
        const row = buildRow({views: 42, views_embed: 0, metric_aggregations: []});

        // Act
        const result = DungeonrouteTable.prototype._renderViews.call(buildRenderViewsContext(), row.views, 'display', row, null);

        // Assert
        expect(result).toContain('<span class="views" title="42/0">42/0</span>');
        expect(result).toContain('<span class="copies" title="0/0">0/0</span>');
    });

    it('_renderViews_givenAnyCounts_keepsEachIconOnTheSameLineAsItsCount', () => {
        // Arrange
        const row = buildRow({views: 52340, views_embed: 1200, metric_aggregations: []});
        const container = document.createElement('div');

        // Act
        container.innerHTML = DungeonrouteTable.prototype._renderViews.call(buildRenderViewsContext(), row.views, 'display', row, null);

        // Assert
        const counts = container.querySelectorAll('span.views, span.copies');
        expect(counts).toHaveLength(2);
        counts.forEach(count => {
            expect(count.closest('.row').classList).toContain('flex-nowrap');
            expect(count.parentElement.classList).toContain('text-nowrap');
        });
    });
});

describe('DungeonrouteTable._getProfileActionsTemplateVariables upgrade drafts', () => {
    it('_getProfileActionsTemplateVariables_givenAnUpgradeDraft_flagsItAsTheDraftAndKeepsTheRouteIdentity', () => {
        // Arrange
        const row = buildRow({public_key: 'xyz789', published: 'team', is_upgrade_draft: true, has_upgrade_draft: false});

        // Act
        const result = DungeonrouteTable.prototype._getProfileActionsTemplateVariables.call(buildTableContext(1), row);

        // Assert
        expect(result).toMatchObject({public_key: 'xyz789', published: 'team', is_upgrade_draft: true, has_upgrade_draft: false});
    });

    it('_getProfileActionsTemplateVariables_givenARouteWithAnUpgradeDraft_flagsThatItHasOne', () => {
        // Arrange
        const row = buildRow({is_upgrade_draft: false, has_upgrade_draft: true});

        // Act
        const result = DungeonrouteTable.prototype._getProfileActionsTemplateVariables.call(buildTableContext(1), row);

        // Assert
        expect(result.is_upgrade_draft).toBe(false);
        expect(result.has_upgrade_draft).toBe(true);
    });

    it('_getProfileActionsTemplateVariables_givenARowWithoutUpgradeDraftFields_flagsNeither', () => {
        // Act
        const result = DungeonrouteTable.prototype._getProfileActionsTemplateVariables.call(buildTableContext(1), buildRow());

        // Assert
        expect(result.is_upgrade_draft).toBe(false);
        expect(result.has_upgrade_draft).toBe(false);
    });
});

describe('DungeonrouteTable._renderTitle tags', () => {
    let tagTemplate;

    /**
     * @returns {Object} A `this` context with the real title template and a tag template that echoes the tag's name.
     */
    function buildRenderTitleContext() {
        tagTemplate = vi.fn((data) => `<span class="tag">${data.name}</span>`);
        Handlebars.templates = {
            dungeonroute_table_title_published: () => '',
            dungeonroute_table_title: Handlebars.compile(fs.readFileSync(
                path.join(__dirname, '../../../handlebars/dungeonroute_table_title.handlebars'),
                'utf8'
            )),
            tag_render_template: tagTemplate,
        };
        globalThis.isColorDark = require('../../colorutil').isColorDark;

        return buildTableContext(1);
    }

    /**
     * @param {string} html
     * @returns {string[]}
     */
    function renderedTagNames(html) {
        const wrapper = new DOMParser().parseFromString(html, 'text/html').querySelector('.routes_table_title');

        return Array.from(wrapper.querySelectorAll('.tag')).map((tag) => tag.textContent);
    }

    it('_renderTitle_givenPersonalTags_rendersEveryTagUnderTheTitle', () => {
        // Arrange
        const row = buildRow({title: 'My route', tagspersonal: [{name: 'WIP', color: '#000000'}, {name: 'Guild', color: null}]});

        // Act
        const result = DungeonrouteTable.prototype._renderTitle.call(buildRenderTitleContext(), null, 'display', row, null, false);

        // Assert
        expect(renderedTagNames(result)).toEqual(['WIP', 'Guild']);
        expect(tagTemplate).toHaveBeenNthCalledWith(1, {name: 'WIP', color: '#000000', edit: false, dark: true});
        expect(tagTemplate).toHaveBeenNthCalledWith(2, {name: 'Guild', color: null, edit: false, dark: false});
    });

    it('_renderTitle_givenOnlyTeamTags_rendersTheTeamTags', () => {
        // Arrange
        const row = buildRow({title: 'My route', tagsteam: [{name: 'Raid night', color: '#ffffff'}]});

        // Act
        const result = DungeonrouteTable.prototype._renderTitle.call(buildRenderTitleContext(), null, 'display', row, null, false);

        // Assert
        expect(renderedTagNames(result)).toEqual(['Raid night']);
        expect(tagTemplate).toHaveBeenCalledExactlyOnceWith({name: 'Raid night', color: '#ffffff', edit: false, dark: false});
    });
});

describe('DungeonrouteTable._getDefaultSearchColumns', () => {
    it('_getDefaultSearchColumns_givenAColumnWithADefaultSearch_searchesOnlyThatColumn', () => {
        // Arrange
        const tableView = {getColumns: vi.fn(() => [{name: 'title'}, {name: 'dungeon', defaultSearch: '12'}, {name: 'views'}])};
        const context = Object.assign(Object.create(DungeonrouteTable.prototype), {_tableView: tableView, _viewMode: 'list'});

        // Act
        const result = DungeonrouteTable.prototype._getDefaultSearchColumns.call(context);

        // Assert
        expect(tableView.getColumns).toHaveBeenCalledExactlyOnceWith('list');
        expect(result).toEqual([null, {search: '12'}, null]);
    });
});

describe('DungeonrouteTable.getRouteDataByPublicKey', () => {
    /**
     * @returns {Object}
     */
    function buildRouteDataContext() {
        return Object.assign(Object.create(DungeonrouteTable.prototype), {
            _routeData: [{public_key: 'a', title: 'A'}, {public_key: 'b', title: 'B'}],
        });
    }

    it('getRouteDataByPublicKey_givenAListedRoute_returnsItsRow', () => {
        expect(DungeonrouteTable.prototype.getRouteDataByPublicKey.call(buildRouteDataContext(), 'b')).toEqual({public_key: 'b', title: 'B'});
    });

    it('getRouteDataByPublicKey_givenARouteThatIsNotListed_returnsNull', () => {
        expect(DungeonrouteTable.prototype.getRouteDataByPublicKey.call(buildRouteDataContext(), 'c')).toBeNull();
    });
});

describe('DungeonrouteTable.activate', () => {
    useRealJQuery();

    let previousLang;

    beforeEach(() => {
        previousLang = globalThis.lang;
        globalThis.lang = {get: vi.fn((key) => key)};
        globalThis.showSuccessNotification = vi.fn();
    });

    afterEach(() => {
        globalThis.lang = previousLang;
        document.body.innerHTML = '';
    });

    /**
     * @param {Object} options
     * @returns {{context: Object, dt: Object}}
     */
    function activateTable(options = {}) {
        document.body.innerHTML = `
            <button id="filter"></button>
            <button class="table_view_toggle list" data-viewmode="list"></button>
            <div id="mass_delete_picker"></div>`;
        const dt = {
            settings: () => ({init: () => ({columns: []})}),
            draw: vi.fn(),
        };
        const context = Object.assign(Object.create(DungeonrouteTable.prototype), {
            _dt: dt,
            _viewMode: 'biglist',
            refreshTable: vi.fn(),
            options: Object.assign({
                filterButtonSelector: '#filter',
                tableListViewToggleSelector: '.table_view_toggle',
                massDeletePickerSelector: '#mass_delete_picker',
            }, options),
        });
        DungeonrouteTable.prototype.activate.call(context);

        return {context, dt};
    }

    it('activate_givenTheMassDeletePickerDeletedOneRoute_confirmsItAndRedrawsKeepingThePage', () => {
        // Arrange
        const {dt} = activateTable();

        // Act
        $('#mass_delete_picker').trigger('dungeonroutepicker:confirmed', [{publicKeys: ['a'], dungeonRoutes: [], response: null}]);

        // Assert
        expect(lang.get).toHaveBeenCalledWith('js.routes_delete_successful_one', {count: 1});
        expect(showSuccessNotification).toHaveBeenCalledExactlyOnceWith('js.routes_delete_successful_one');
        expect(dt.draw).toHaveBeenCalledExactlyOnceWith(false);
    });

    it('activate_givenTheMassDeletePickerDeletedSeveralRoutes_namesHowMany', () => {
        // Arrange
        activateTable();

        // Act
        $('#mass_delete_picker').trigger('dungeonroutepicker:confirmed', [{publicKeys: ['a', 'b'], dungeonRoutes: [], response: null}]);

        // Assert
        expect(lang.get).toHaveBeenCalledWith('js.routes_delete_successful_many', {count: 2});
        expect(showSuccessNotification).toHaveBeenCalledExactlyOnceWith('js.routes_delete_successful_many');
    });

    it('activate_givenTheFilterButtonClicked_appliesTheFilters', () => {
        // Arrange
        const {dt} = activateTable();

        // Act
        $('#filter').trigger('click');

        // Assert
        expect(dt.draw).toHaveBeenCalledExactlyOnceWith();
    });

    it('activate_givenAViewModeToggleClicked_switchesToThatViewAndRebuildsTheTable', () => {
        // Arrange
        const {context} = activateTable();

        // Act
        $('.table_view_toggle').trigger('click');

        // Assert
        expect(context._viewMode).toBe('list');
        expect(context.refreshTable).toHaveBeenCalledTimes(1);
    });
});

describe('DungeonrouteTable clone actions', () => {
    useRealJQuery();

    let previousLang;
    let confirmYes;

    beforeEach(() => {
        previousLang = globalThis.lang;
        globalThis.lang = {get: (key) => key};
        Handlebars.templates = {
            dungeonroute_table_profile_clone_to_team_template: Handlebars.compile(fs.readFileSync(
                path.join(__dirname, '../../../handlebars/dungeonroute_table_profile_clone_to_team_template.handlebars'),
                'utf8'
            )),
        };
        document.body.innerHTML = '<a class="dungeonroute-clone dungeonroute-clone-to-team" data-publickey="abc123"></a>';
        confirmYes = null;
        globalThis.showConfirmYesCancel = vi.fn((html, onYes) => {
            document.body.insertAdjacentHTML('beforeend', `<div class="confirm">${html}</div>`);
            confirmYes = onYes;
        });
        globalThis.showErrorNotification = vi.fn();
        globalThis.showSuccessNotification = vi.fn();
        globalThis.refreshSelectPickers = vi.fn();
        vi.spyOn($, 'ajax').mockImplementation(() => {
        });
    });

    afterEach(() => {
        globalThis.lang = previousLang;
        document.body.innerHTML = '';
    });

    /**
     * @returns {{context: Object, dt: Object}}
     */
    function buildCloneContext() {
        const dt = {draw: vi.fn()};
        const context = Object.assign(Object.create(DungeonrouteTable.prototype), {
            _dt: dt,
            options: {teams: [{public_key: 'teamA', name: 'Team A'}, {public_key: 'teamB', name: 'Team B'}]},
        });

        return {context, dt};
    }

    /**
     * @returns {{target: HTMLElement, preventDefault: Function}}
     */
    function cloneClick() {
        return {target: document.querySelector('.dungeonroute-clone-to-team'), preventDefault: vi.fn()};
    }

    it('_promptCloneToTeamClicked_givenClick_offersEveryTeamOfTheUser', () => {
        // Arrange
        const {context} = buildCloneContext();

        // Act
        DungeonrouteTable.prototype._promptCloneToTeamClicked.call(context, cloneClick());

        // Assert
        const radios = Array.from(document.querySelectorAll('.confirm input[type="radio"]'));
        expect(radios.map((radio) => radio.value)).toEqual(['teamA', 'teamB']);
        expect(radios.map((radio) => radio.name)).toEqual(['clone-to-team-abc123', 'clone-to-team-abc123']);
    });

    it('_promptCloneToTeamClicked_givenATeamPicked_clonesTheRouteToItAndRedrawsKeepingThePage', () => {
        // Arrange
        const {context, dt} = buildCloneContext();
        DungeonrouteTable.prototype._promptCloneToTeamClicked.call(context, cloneClick());
        document.querySelector('.confirm input[value="teamB"]').checked = true;

        // Act
        confirmYes();
        $.ajax.mock.calls[0][0].success({});

        // Assert
        expect($.ajax).toHaveBeenCalledExactlyOnceWith(expect.objectContaining({type: 'POST', url: '/ajax/abc123/clone/team/teamB'}));
        expect(showSuccessNotification).toHaveBeenCalledExactlyOnceWith('js.route_clone_successful');
        expect(dt.draw).toHaveBeenCalledExactlyOnceWith(false);
    });

    it('_promptCloneToTeamClicked_givenNoTeamPicked_saysSoAndClonesNothing', () => {
        // Arrange
        const {context} = buildCloneContext();
        DungeonrouteTable.prototype._promptCloneToTeamClicked.call(context, cloneClick());

        // Act
        confirmYes();

        // Assert
        expect(showErrorNotification).toHaveBeenCalledExactlyOnceWith('js.route_clone_select_team');
        expect($.ajax).not.toHaveBeenCalled();
    });

    it('_cloneDungeonRouteClicked_givenClick_opensTheCloneOfTheRouteInANewTab', () => {
        // Arrange
        const openedLinks = [];
        vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(function () {
            openedLinks.push({href: this.getAttribute('href'), target: this.getAttribute('target')});
        });
        const clickEvent = {target: document.querySelector('.dungeonroute-clone'), preventDefault: vi.fn()};

        // Act
        const result = DungeonrouteTable.prototype._cloneDungeonRouteClicked.call(Object.create(DungeonrouteTable.prototype), clickEvent);

        // Assert
        expect(openedLinks).toEqual([{href: '/abc123/clone', target: '_blank'}]);
        expect(clickEvent.preventDefault).toHaveBeenCalledTimes(1);
        expect(result).toBe(false);
    });
});

describe('DungeonrouteTable.overrideSelection', () => {
    useRealJQuery();

    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('overrideSelection_givenASelection_putsEveryFilterOnItAndAppliesIt', () => {
        // Arrange
        document.body.innerHTML = `
            <button id="filter"></button>
            <select id="dungeon"><option value="-1">All</option><option value="12">Ara-Kara</option></select>
            <select id="affixes" multiple><option value="7">Fortified</option><option value="8" selected>Tyrannical</option></select>
            <select id="attributes" multiple><option value="rogue-shroud">Shroud</option></select>
            <select id="requirements" multiple><option value="favorite">Favorite</option></select>
            <select id="tags" multiple><option value="mine">mine</option></select>`;
        const filterClicked = vi.fn();
        document.getElementById('filter').addEventListener('click', filterClicked);
        globalThis.refreshSelectPickers = vi.fn();
        const context = Object.assign(Object.create(DungeonrouteTable.prototype), {
            options: {
                filterButtonSelector: '#filter',
                dungeonSelectId: '#dungeon',
                affixSelectId: '#affixes',
                attributesSelectId: '#attributes',
                requirementsSelectId: '#requirements',
                tagsSelectId: '#tags',
            },
        });

        // Act
        DungeonrouteTable.prototype.overrideSelection.call(context, 12, ['7'], ['rogue-shroud'], ['favorite'], ['mine']);

        // Assert
        expect($('#dungeon').val()).toBe('12');
        expect($('#affixes').val()).toEqual(['7']);
        expect($('#attributes').val()).toEqual(['rogue-shroud']);
        expect($('#requirements').val()).toEqual(['favorite']);
        expect($('#tags').val()).toEqual(['mine']);
        expect(refreshSelectPickers).toHaveBeenCalledTimes(1);
        expect(filterClicked).toHaveBeenCalledTimes(1);
    });
});
