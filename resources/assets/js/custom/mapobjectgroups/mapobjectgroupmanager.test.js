globalThis.Signalable = globalThis.Signalable ?? class Signalable {
};

const {MapObjectGroupManager} = require('./mapobjectgroupmanager');

// The real constants, so the accessor tests assert against the values the site actually uses rather than
// values the test invented - a mis-paired constant has to fail.
const MAP_OBJECT_GROUP_CONSTANTS = require('../constants');

// One row per named accessor: [method name, the MAP_OBJECT_GROUP_* name it must look up].
const ACCESSORS = [
    ['getUserMousePositionMapObjectGroup', MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_USER_MOUSE_POSITION],
    ['getEnemyPatrolMapObjectGroup', MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_ENEMY_PATROL],
    ['getEnemyMapObjectGroup', MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_ENEMY],
    ['getEnemyPackMapObjectGroup', MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_ENEMY_PACK],
    ['getEnemyForcesCheckpointMapObjectGroup', MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_ENEMY_FORCES_CHECKPOINT],
    ['getPathMapObjectGroup', MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_PATH],
    ['getDungeonFloorSwitchMarkerMapObjectGroup', MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER],
    ['getBrushlineMapObjectGroup', MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_BRUSHLINE],
    ['getArrowMapObjectGroup', MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_ARROW],
    ['getMapIconMapObjectGroup', MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_MAPICON],
    ['getKillZoneMapObjectGroup', MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_KILLZONE],
    ['getKillZonePathMapObjectGroup', MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_KILLZONE_PATH],
    ['getMountableAreaMapObjectGroup', MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_MOUNTABLE_AREA],
    ['getFloorUnionMapObjectGroup', MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_FLOOR_UNION],
    ['getFloorUnionAreaMapObjectGroup', MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_FLOOR_UNION_AREA],
];

/**
 * A manager holding the given groups. Object.create bypasses the constructor, which wants a real map and
 * state manager to build the groups itself.
 *
 * @param {{names: string[]}[]} mapObjectGroups
 * @returns {MapObjectGroupManager}
 */
function buildManager(mapObjectGroups) {
    const manager = Object.create(MapObjectGroupManager.prototype);
    manager.mapObjectGroups = mapObjectGroups;

    return manager;
}

/**
 * A manager with one group per entry of MAP_OBJECT_GROUP_NAMES, each registered under its own name only.
 *
 * @returns {MapObjectGroupManager}
 */
function buildManagerWithEveryGroup() {
    return buildManager(MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_NAMES.map(name => ({names: [name]})));
}

describe('MapObjectGroupManager', () => {
    // The accessors read the constants as bare globals, which is what the concatenated bundle gives them
    const installedConstants = Object.keys(MAP_OBJECT_GROUP_CONSTANTS).filter(key => key.startsWith('MAP_OBJECT_GROUP_'));

    beforeEach(() => {
        installedConstants.forEach(key => globalThis[key] = MAP_OBJECT_GROUP_CONSTANTS[key]);
    });

    afterEach(() => {
        installedConstants.forEach(key => delete globalThis[key]);
    });

    it('getByName_givenRegisteredGroup_returnsThatGroup', () => {
        // Arrange
        const enemyMapObjectGroup = {names: ['enemy']};
        const manager = buildManager([{names: ['enemypatrol']}, enemyMapObjectGroup]);

        // Act
        const result = manager.getByName('enemy');

        // Assert
        expect(result).toBe(enemyMapObjectGroup);
    });

    it('getByName_givenAliasOfAMultiNameGroup_returnsThatGroup', () => {
        // Arrange - the map icon group registers the awakened obelisk alias as a second name
        const mapIconMapObjectGroup = {
            names: [MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_MAPICON, MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_MAPICON_AWAKENED_OBELISK],
        };
        const manager = buildManager([mapIconMapObjectGroup]);

        // Act
        const result = manager.getByName(MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_MAPICON_AWAKENED_OBELISK);

        // Assert
        expect(result).toBe(mapIconMapObjectGroup);
    });

    it('getByName_givenGroupThisMapDoesNotHave_returnsNull', () => {
        // Arrange - a page that hides the group never creates it
        const manager = buildManager([{names: ['enemy']}]);

        // Act
        const result = manager.getByName('killzone');

        // Assert
        expect(result).toBeNull();
    });

    it('getByName_givenNoGroupsAtAll_returnsNull', () => {
        // Arrange - what a group constructed first sees while the constructor is still building the rest
        const manager = buildManager([]);

        // Act
        const result = manager.getByName('enemy');

        // Assert
        expect(result).toBeNull();
    });

    it.each(ACCESSORS)('%s_givenRegisteredGroup_returnsItsOwnGroup', (methodName, expectedGroupName) => {
        // Arrange
        const manager = buildManagerWithEveryGroup();

        // Act
        const result = manager[methodName]();

        // Assert
        expect(result).not.toBeNull();
        expect(result.names).toEqual([expectedGroupName]);
    });

    it.each(ACCESSORS)('%s_givenGroupThisMapDoesNotHave_returnsNull', (methodName, groupName) => {
        // Arrange - every group except the one this accessor is for
        const manager = buildManager(MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_NAMES
            .filter(name => name !== groupName)
            .map(name => ({names: [name]})));

        // Act
        const result = manager[methodName]();

        // Assert
        expect(result).toBeNull();
    });

    it('accessors_givenEveryRegisteredGroupName_haveOneAccessorEach', () => {
        // Arrange - MAP_OBJECT_GROUP_MAPICON_AWAKENED_OBELISK is deliberately absent from
        // MAP_OBJECT_GROUP_NAMES (it is an alias of the map icon group), so it needs no accessor either
        const covered = ACCESSORS.map(([, groupName]) => groupName);
        const declared = Object.getOwnPropertyNames(MapObjectGroupManager.prototype)
            .filter(name => /^get\w+MapObjectGroup$/.test(name));

        // Act & Assert - a new map object group must come with an accessor of its own, and every accessor
        // on the class must be pinned by the table above
        expect(covered.slice().sort()).toEqual(MAP_OBJECT_GROUP_CONSTANTS.MAP_OBJECT_GROUP_NAMES.slice().sort());
        expect(declared.slice().sort()).toEqual(ACCESSORS.map(([methodName]) => methodName).sort());
    });
});
