// ---------------------------------------------------------------------------
// DungeonrouteTable is a global-script style class extending the bare global `InlineCode`.
// These tests target two pure helper methods used by the team routes table's "addremoveroute"
// column (see #3692): which action-dropdown template to render for a route, and the variables
// that drive that dropdown's conditional items (publish state highlighting, migrate-to-*
// availability, the "new mapping version" warning icon). Neither method touches `this` beyond
// `options.currentUserId`, so they're called directly via `.call()` without constructing a real
// instance (which would otherwise need jQuery/Handlebars/DataTables globals).
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

describe('DungeonrouteTable._getAddRemoveRouteTemplateName', () => {
    it('_getAddRemoveRouteTemplateName_givenRouteNotOnTeam_returnsAddRouteTemplate', () => {
        // Arrange
        const row = buildRow({has_team: false});

        // Act
        const result = DungeonrouteTable.prototype._getAddRemoveRouteTemplateName.call({options: {currentUserId: 1}}, row);

        // Assert
        expect(result).toBe('team_dungeonroute_table_add_route_actions');
    });

    it('_getAddRemoveRouteTemplateName_givenOwnedRouteOnTeam_returnsOwnRouteActionsTemplate', () => {
        // Arrange
        const row = buildRow({has_team: true, author: {id: 1}});

        // Act
        const result = DungeonrouteTable.prototype._getAddRemoveRouteTemplateName.call({options: {currentUserId: 1}}, row);

        // Assert
        expect(result).toBe('team_dungeonroute_table_route_actions_own_route');
    });

    it('_getAddRemoveRouteTemplateName_givenNotOwnedRouteOnTeam_returnsRouteActionsTemplate', () => {
        // Arrange
        const row = buildRow({has_team: true, author: {id: 2}});

        // Act
        const result = DungeonrouteTable.prototype._getAddRemoveRouteTemplateName.call({options: {currentUserId: 1}}, row);

        // Assert
        expect(result).toBe('team_dungeonroute_table_route_actions');
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
