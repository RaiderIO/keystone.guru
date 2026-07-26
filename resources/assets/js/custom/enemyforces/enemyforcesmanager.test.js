// EnemyForcesManager is a global-script class in the concatenated bundle; the only thing it needs at
// module-load time is its base class `Signalable` (for `extends`). Stubbing that lets us require the
// source. We exercise `getEnemyForcesForEnemies` on a bare prototype instance (Object.create) so none
// of the heavy constructor (killzone wiring) has to run.

global.Signalable = class Signalable {
};

const EnemyForcesManager = require('./enemyforcesmanager');

/**
 * Builds a fake Enemy exposing only what getEnemyForcesForEnemies touches.
 * @param options {{forces?: Number, obsolete?: Boolean, ignored?: Boolean}}
 */
function createEnemy({forces = 0, obsolete = false, ignored = false}) {
    return {
        isObsolete: () => obsolete,
        shouldBeIgnored: () => ignored,
        getEnemyForces: () => forces,
    };
}

/**
 * Builds a bare EnemyForcesManager, skipping the constructor entirely.
 */
function createManager() {
    return Object.create(EnemyForcesManager.prototype);
}

describe('EnemyForcesManager.getEnemyForcesForEnemies', () => {
    it('getEnemyForcesForEnemies_givenSeveralEnemies_returnsTheirSum', () => {
        const manager = createManager();

        const result = manager.getEnemyForcesForEnemies([
            createEnemy({forces: 5}),
            createEnemy({forces: 3}),
            createEnemy({forces: 10}),
        ]);

        expect(result).toBe(18);
    });

    it('getEnemyForcesForEnemies_givenObsoleteEnemy_includesItInSum', () => {
        // An obsolete enemy is not pulled, but it is still standing there and still yields its enemy
        // forces to whoever walks past it - unlike KillZone.getEnemyForces(), which skips them.
        const manager = createManager();

        const result = manager.getEnemyForcesForEnemies([
            createEnemy({forces: 5}),
            createEnemy({forces: 100, obsolete: true}),
        ]);

        expect(result).toBe(105);
    });

    it('getEnemyForcesForEnemies_givenIgnoredEnemy_excludesItFromSum', () => {
        const manager = createManager();

        const result = manager.getEnemyForcesForEnemies([
            createEnemy({forces: 5}),
            createEnemy({forces: 100, ignored: true}),
        ]);

        expect(result).toBe(5);
    });

    it('getEnemyForcesForEnemies_givenNullEntry_skipsIt', () => {
        // Members are resolved by id out of the enemy map object group, which returns null for an
        // enemy that isn't present in this mapping version.
        const manager = createManager();

        const result = manager.getEnemyForcesForEnemies([
            createEnemy({forces: 5}),
            null,
        ]);

        expect(result).toBe(5);
    });

    it('getEnemyForcesForEnemies_givenNoEnemies_returnsZero', () => {
        const manager = createManager();

        expect(manager.getEnemyForcesForEnemies([])).toBe(0);
    });
});
