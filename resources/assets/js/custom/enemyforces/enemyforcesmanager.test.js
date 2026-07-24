// EnemyForcesManager is a global-script class in the concatenated bundle; the only thing it needs at
// module-load time is its base class `Signalable` (for `extends`) and the `MAP_OBJECT_GROUP_ENEMY`
// constant. Stubbing those lets us require the source. We exercise `getEnemyForcesForFloor` on a bare
// prototype instance (Object.create) so none of the heavy constructor (killzone wiring) has to run.

global.Signalable = class Signalable {
};
global.MAP_OBJECT_GROUP_ENEMY = 'enemy';

const EnemyForcesManager = require('./enemyforcesmanager');

/**
 * Builds a fake Enemy exposing only what getEnemyForcesForFloor touches.
 * @param options {{floorId: Number, forces?: Number, obsolete?: Boolean, ignored?: Boolean}}
 */
function createEnemy({floorId, forces = 0, obsolete = false, ignored = false}) {
    return {
        floor_id: floorId,
        isObsolete: () => obsolete,
        shouldBeIgnored: () => ignored,
        getEnemyForces: () => forces,
    };
}

/**
 * Builds a bare EnemyForcesManager whose enemy map object group returns the given enemies.
 * @param enemies {Array|false} The enemies to expose, or false to simulate no enemy group.
 */
function createManager(enemies) {
    const manager = Object.create(EnemyForcesManager.prototype);

    const enemyMapObjectGroup = enemies === false ? false : {objects: {...enemies}};

    manager.map = {
        mapObjectGroupManager: {
            getByName: () => enemyMapObjectGroup,
        },
    };

    return manager;
}

describe('EnemyForcesManager.getEnemyForcesForFloor', () => {
    it('getEnemyForcesForFloor_givenEnemiesOnMultipleFloors_returnsOnlyMatchingFloorSum', () => {
        const manager = createManager([
            createEnemy({floorId: 1, forces: 5}),
            createEnemy({floorId: 1, forces: 3}),
            createEnemy({floorId: 2, forces: 10}),
        ]);

        expect(manager.getEnemyForcesForFloor(1)).toBe(8);
        expect(manager.getEnemyForcesForFloor(2)).toBe(10);
    });

    it('getEnemyForcesForFloor_givenObsoleteEnemy_excludesItFromSum', () => {
        const manager = createManager([
            createEnemy({floorId: 1, forces: 5}),
            createEnemy({floorId: 1, forces: 100, obsolete: true}),
        ]);

        expect(manager.getEnemyForcesForFloor(1)).toBe(5);
    });

    it('getEnemyForcesForFloor_givenIgnoredEnemy_excludesItFromSum', () => {
        const manager = createManager([
            createEnemy({floorId: 1, forces: 5}),
            createEnemy({floorId: 1, forces: 100, ignored: true}),
        ]);

        expect(manager.getEnemyForcesForFloor(1)).toBe(5);
    });

    it('getEnemyForcesForFloor_givenFloorWithoutEnemies_returnsZero', () => {
        const manager = createManager([
            createEnemy({floorId: 1, forces: 5}),
        ]);

        expect(manager.getEnemyForcesForFloor(99)).toBe(0);
    });

    it('getEnemyForcesForFloor_givenNoEnemyGroup_returnsZero', () => {
        const manager = createManager(false);

        expect(manager.getEnemyForcesForFloor(1)).toBe(0);
    });
});
