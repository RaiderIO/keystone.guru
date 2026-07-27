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

const {InlineCode} = require('../inlinecode');
globalThis.InlineCode = InlineCode;

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
    it('_getAddRemoveRouteTemplate_givenRouteNotOnTeam_returnsAddRouteTemplateWithPublicKeyOnly', () => {
        // Arrange
        const row = buildRow({has_team: false, public_key: 'abc123'});

        // Act
        const result = DungeonrouteTable.prototype._getAddRemoveRouteTemplate.call(buildTableContext(1), row);

        // Assert
        expect(result.templateName).toBe('team_dungeonroute_table_add_route_actions');
        expect(result.variables).toEqual({public_key: 'abc123'});
    });

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
});
