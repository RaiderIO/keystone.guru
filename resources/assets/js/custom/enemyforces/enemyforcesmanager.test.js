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

/**
 * A bare manager whose signals are recorded instead of dispatched.
 * @param options {{enemyForces?: Number, enemyForcesOverride?: Number|null}}
 */
function createSignallingManager({enemyForces = 0, enemyForcesOverride = null} = {}) {
    const manager = createManager();
    manager.enemyForces = enemyForces;
    manager.enemyForcesOverride = enemyForcesOverride;
    manager.signal = vi.fn();

    return manager;
}

describe('EnemyForcesManager._setEnemyForces', () => {
    it('_setEnemyForces_givenNewValue_storesItAndSignalsTheChange', () => {
        // Arrange
        const manager = createSignallingManager({enemyForces: 10});

        // Act
        manager._setEnemyForces(25);

        // Assert
        expect(manager.getEnemyForces()).toBe(25);
        expect(manager.signal).toHaveBeenCalledTimes(1);
        expect(manager.signal).toHaveBeenCalledWith('enemyforces:changed', {previousEnemyForces: 10, currentEnemyForces: 25});
    });

    it('_setEnemyForces_givenUnchangedValue_signalsNothing', () => {
        // Arrange
        const manager = createSignallingManager({enemyForces: 10});

        // Act
        manager._setEnemyForces(10);

        // Assert
        expect(manager.signal).not.toHaveBeenCalled();
    });

    it('_setEnemyForces_givenUnchangedValueAndForce_signalsAnyway', () => {
        // Arrange
        const manager = createSignallingManager({enemyForces: 10});

        // Act
        manager._setEnemyForces(10, true);

        // Assert
        expect(manager.signal).toHaveBeenCalledWith('enemyforces:changed', {previousEnemyForces: 10, currentEnemyForces: 10});
    });

    it('_setEnemyForces_givenOverrideActive_storesValueWithoutSignalling', () => {
        // Arrange
        const manager = createSignallingManager({enemyForces: 10, enemyForcesOverride: 50});

        // Act
        manager._setEnemyForces(25);

        // Assert: the override keeps winning, but the real value is kept for when it is removed
        expect(manager.enemyForces).toBe(25);
        expect(manager.getEnemyForces()).toBe(50);
        expect(manager.signal).not.toHaveBeenCalled();
    });
});

describe('EnemyForcesManager.setEnemyForcesOverride', () => {
    it('setEnemyForcesOverride_givenValue_reportsItAsEnemyForcesAndSignals', () => {
        // Arrange
        const manager = createSignallingManager({enemyForces: 10});

        // Act
        manager.setEnemyForcesOverride(50);

        // Assert
        expect(manager.getEnemyForces()).toBe(50);
        expect(manager.getEnemyForcesOverride()).toBe(50);
        expect(manager.signal).toHaveBeenCalledWith('enemyforces:changed', expect.anything());
    });

    it('setEnemyForcesOverride_givenNullAfterOverride_restoresRealValueAndSignalsIt', () => {
        // Arrange
        const manager = createSignallingManager({enemyForces: 10, enemyForcesOverride: 50});

        // Act
        manager.setEnemyForcesOverride(null);

        // Assert
        expect(manager.getEnemyForces()).toBe(10);
        expect(manager.getEnemyForcesOverride()).toBeNull();
        expect(manager.signal).toHaveBeenCalledWith('enemyforces:changed', expect.anything());
    });
});

describe('EnemyForcesManager.getEnemyForcesRequired', () => {
    const originalGetState = global.getState;

    afterEach(() => {
        global.getState = originalGetState;
    });

    function stubMapContext({teeming, required = 300, requiredTeeming = 360}) {
        global.getState = () => ({
            getMapContext: () => ({
                getTeeming: () => teeming,
                getEnemyForcesRequired: () => required,
                getEnemyForcesRequiredTeeming: () => requiredTeeming,
            }),
        });
    }

    it('getEnemyForcesRequired_givenNoTeeming_returnsRegularRequirement', () => {
        // Arrange
        stubMapContext({teeming: false});

        // Act & Assert
        expect(createManager().getEnemyForcesRequired()).toBe(300);
    });

    it('getEnemyForcesRequired_givenTeeming_returnsTeemingRequirement', () => {
        // Arrange
        stubMapContext({teeming: true});

        // Act & Assert
        expect(createManager().getEnemyForcesRequired()).toBe(360);
    });

    it('getEnemyForcesRequired_givenTeemingWithoutTeemingRequirement_returnsRegularRequirement', () => {
        // Arrange
        stubMapContext({teeming: true, requiredTeeming: 0});

        // Act & Assert
        expect(createManager().getEnemyForcesRequired()).toBe(300);
    });
});

describe('EnemyForcesManager constructor', () => {
    const originalGetState = global.getState;

    beforeEach(() => {
        global.MapContextLiveSession = class MapContextLiveSession {
        };
    });

    afterEach(() => {
        global.getState = originalGetState;
        delete global.MapContextLiveSession;
    });

    /**
     * Runs the real constructor against a kill zone map object group that records its handlers.
     * @param mapContext {Object}
     */
    function constructManager(mapContext = {}) {
        global.getState = () => ({getMapContext: () => mapContext});
        const handlers = {};
        const map = {
            mapObjectGroupManager: {
                getKillZoneMapObjectGroup: () => ({
                    register: (event, context, callback) => (handlers[event] = callback),
                }),
            },
        };

        const manager = new EnemyForcesManager(map);
        manager.signal = vi.fn();

        return {manager, handlers};
    }

    it('constructor_givenKillZoneEnemyEvents_tracksTheRouteEnemyForces', () => {
        // Arrange
        const {manager, handlers} = constructManager();

        // Act
        handlers['killzone:enemyadded']({data: {enemy: createEnemy({forces: 7})}});
        handlers['killzone:enemyadded']({data: {enemy: createEnemy({forces: 5})}});
        handlers['killzone:enemyremoved']({data: {enemy: createEnemy({forces: 7})}});
        handlers['killzone:enemieschanged']({data: {previousForces: 5, newForces: 9}});

        // Assert
        expect(manager.getEnemyForces()).toBe(9);
    });

    it('constructor_givenLiveSessionWithDifferentOverride_startsWithTheOverride', () => {
        // Arrange
        const mapContext = Object.assign(new MapContextLiveSession(), {
            getEnemyForces: () => 100,
            getEnemyForcesOverride: () => 120,
        });

        // Act
        const {manager} = constructManager(mapContext);

        // Assert
        expect(manager.getEnemyForcesOverride()).toBe(120);
        expect(manager.getEnemyForces()).toBe(120);
    });

    it('constructor_givenLiveSessionWithOverrideEqualToEnemyForces_startsWithoutOverride', () => {
        // Arrange
        const mapContext = Object.assign(new MapContextLiveSession(), {
            getEnemyForces: () => 100,
            getEnemyForcesOverride: () => 100,
        });

        // Act
        const {manager} = constructManager(mapContext);

        // Assert
        expect(manager.getEnemyForcesOverride()).toBeNull();
    });
});
