// ---------------------------------------------------------------------------
// Follows the recipe documented at the top of models/killzone.test.js: stub the
// directly-extended base class (`EnemyPatrol`) and the globals AdminEnemyPatrol
// touches at load/construction time, then exercise the class in isolation.
// ---------------------------------------------------------------------------

// 1a. Constants referenced as bare globals by the class body.
global.MAP_OBJECT_GROUP_ENEMY = 'enemy';
global.MAP_OBJECT_GROUP_ENEMY_PATROL = 'enemypatrol';

// 1b. Config object read at construction time (`c.map.adminenemypatrol.polylineOptions`).
global.c = {
    map: {
        adminenemypatrol: {
            polylineOptions: {},
        },
    },
};

// 1c. `AdminEnemyConnections` is only ever constructed/used through `redrawConnectionsToEnemies`,
// which the tests below isolate via `vi.fn()` - a no-op stub is enough for the constructor call.
global.AdminEnemyConnections = class AdminEnemyConnections {
    constructor() {
    }

    remove() {
    }

    draw() {
    }
};

// 1d. Lightweight base class mirroring the bits of the real `EnemyPatrol`/`MapObject` chain
// that `AdminEnemyPatrol` calls into (`super.onSaveSuccess`, `super.addEnemy`, `super.removeEnemy`,
// `super.cleanup`, `super.localDelete`).
global.EnemyPatrol = class EnemyPatrol {
    constructor(map, layer) {
        this.map = map;
        this.layer = layer;
        this.enemies = [];
        this._signals = [];
    }

    register() {
    }

    unregister() {
    }

    signal(event, data) {
        this._signals.push({event, data});
    }

    setSynced() {
    }

    shouldBeVisible() {
        return true;
    }

    getLayerLatLng() {
        return {lat: -10, lng: 20};
    }

    onSaveSuccess() {
    }

    addEnemy(enemy) {
        this.enemies.push(enemy);
    }

    removeEnemy(enemy) {
        this.enemies = this.enemies.filter((candidate) => candidate !== enemy);
    }

    localDelete() {
    }

    cleanup() {
    }
};

// 1e. `getState()` is called from the constructor (`floorid:changed`) and from `cleanup()`.
const fakeState = {
    register: vi.fn(),
    unregister: vi.fn(),
};
global.getState = () => fakeState;

const {AdminEnemyPatrol} = require('./adminenemypatrol');
const {fakeMapObjectGroupManager} = require('#test/fixtures/mapObjectGroupManager');

/**
 * A fake DungeonMap exposing only what AdminEnemyPatrol touches.
 *
 * @param {Object} groups Map object groups by name; any other name resolves to a group with an empty layer group.
 */
function makeFakeMap(groups = {}) {
    return {
        register: vi.fn(),
        unregister: vi.fn(),
        mapObjectGroupManager: fakeMapObjectGroupManager((name) => groups[name] ?? {layerGroup: {}}),
    };
}

/**
 * @param {Object} latLng
 * @param {boolean} visible
 * @returns {Object} An enemy as far as AdminEnemyPatrol's connection lines are concerned.
 */
function makeFakeEnemy(latLng, visible = true) {
    return {
        shouldBeVisible: () => visible,
        layer: {getLatLng: () => latLng},
        register: vi.fn(),
        unregister: vi.fn(),
        setEnemyPatrol: vi.fn(),
        save: vi.fn(),
    };
}

/**
 * @param {Object} map
 * @returns {AdminEnemyPatrol} A patrol whose connection lines are recorded rather than drawn.
 */
function makePatrolWithRecordedConnections(map = makeFakeMap()) {
    const patrol = new AdminEnemyPatrol(map, null);
    patrol.enemyConnections = {remove: vi.fn(), draw: vi.fn()};

    return patrol;
}

describe('AdminEnemyPatrol constructor', () => {
    it('does not register a map-wide map:mapstatechanged listener', () => {
        // Regression test for #4393: this listener used to fire a full redraw of connection
        // lines for EVERY admin enemy patrol on the floor on any unrelated map state change
        // (e.g. saving a single other patrol), causing a multi-second freeze on floors with
        // many patrols (e.g. Black Temple).
        const map = makeFakeMap();

        const patrol = new AdminEnemyPatrol(map, null);

        const registeredEvents = map.register.mock.calls.map((call) => call[0]);
        expect(registeredEvents).not.toContain('map:mapstatechanged');
        expect(fakeState.register).toHaveBeenCalledWith('floorid:changed', patrol, expect.any(Function));
    });

    it('redraws its connections when the floor changes', () => {
        // Arrange
        fakeState.register.mockClear();
        const patrol = makePatrolWithRecordedConnections();
        expect(fakeState.register).toHaveBeenCalledWith('floorid:changed', patrol, expect.any(Function));
        const onFloorChanged = fakeState.register.mock.calls[0][2];

        // Act
        onFloorChanged();

        // Assert
        expect(patrol.enemyConnections.remove).toHaveBeenCalledOnce();
        expect(patrol.enemyConnections.draw).toHaveBeenCalledOnce();
    });
});

describe('AdminEnemyPatrol.redrawConnectionsToEnemies', () => {
    it('draws lines from the patrol to its visible enemies on the patrol group\'s layer group', () => {
        // Arrange
        const patrolLayerGroup = {name: 'patrol layer group'};
        const patrol = makePatrolWithRecordedConnections(makeFakeMap({enemypatrol: {layerGroup: patrolLayerGroup}}));
        patrol.enemies = [makeFakeEnemy({lat: -1, lng: 1}), makeFakeEnemy({lat: -2, lng: 2}, false), makeFakeEnemy({lat: -3, lng: 3})];

        // Act
        patrol.redrawConnectionsToEnemies();

        // Assert
        expect(patrol.enemyConnections.remove).toHaveBeenCalledOnce();
        expect(patrol.enemyConnections.draw).toHaveBeenCalledWith(
            patrolLayerGroup,
            {lat: -10, lng: 20},
            [{lat: -1, lng: 1}, {lat: -3, lng: 3}]
        );
    });

    it('only removes the existing lines when the patrol is not visible', () => {
        // Arrange
        const patrol = makePatrolWithRecordedConnections();
        patrol.shouldBeVisible = () => false;
        patrol.enemies = [makeFakeEnemy({lat: -1, lng: 1})];

        // Act
        patrol.redrawConnectionsToEnemies();

        // Assert
        expect(patrol.enemyConnections.remove).toHaveBeenCalledOnce();
        expect(patrol.enemyConnections.draw).not.toHaveBeenCalled();
    });
});

describe('AdminEnemyPatrol.addEnemy / removeEnemy', () => {
    it('redraws its connections whenever an added enemy changes', () => {
        // Arrange
        const patrol = makePatrolWithRecordedConnections();
        const enemy = makeFakeEnemy({lat: -1, lng: 1});

        // Act
        patrol.addEnemy(enemy);

        // Assert
        expect(patrol.enemies).toEqual([enemy]);
        expect(enemy.register).toHaveBeenCalledWith('object:changed', patrol, expect.any(Function));
        enemy.register.mock.calls[0][2]();
        expect(patrol.enemyConnections.draw).toHaveBeenCalledWith(expect.anything(), expect.anything(), [{lat: -1, lng: 1}]);
    });

    it('stops listening to a removed enemy', () => {
        // Arrange
        const patrol = makePatrolWithRecordedConnections();
        const enemy = makeFakeEnemy({lat: -1, lng: 1});
        patrol.addEnemy(enemy);

        // Act
        patrol.removeEnemy(enemy);

        // Assert
        expect(patrol.enemies).toEqual([]);
        expect(enemy.unregister).toHaveBeenCalledWith('object:changed', patrol);
    });
});

describe('AdminEnemyPatrol.localDelete', () => {
    it('detaches and saves only the enemies on this patrol, then removes its lines', () => {
        // Arrange
        const ownEnemy = Object.assign(makeFakeEnemy({lat: -1, lng: 1}), {enemy_patrol_id: 7});
        const otherEnemy = Object.assign(makeFakeEnemy({lat: -2, lng: 2}), {enemy_patrol_id: 8});
        const patrol = makePatrolWithRecordedConnections(makeFakeMap({enemy: {objects: {1: ownEnemy, 2: otherEnemy}}}));
        patrol.id = 7;

        // Act
        patrol.localDelete();

        // Assert
        expect(ownEnemy.setEnemyPatrol).toHaveBeenCalledWith(null);
        expect(ownEnemy.save).toHaveBeenCalledOnce();
        expect(otherEnemy.setEnemyPatrol).not.toHaveBeenCalled();
        expect(otherEnemy.save).not.toHaveBeenCalled();
        expect(patrol.enemyConnections.remove).toHaveBeenCalledOnce();
    });
});

describe('AdminEnemyPatrol.onSaveSuccess', () => {
    it('redraws only this patrol\'s own connections', () => {
        const patrol = new AdminEnemyPatrol(makeFakeMap(), null);
        patrol.redrawConnectionsToEnemies = vi.fn();

        patrol.onSaveSuccess({id: 5}, false);

        expect(patrol.redrawConnectionsToEnemies).toHaveBeenCalledOnce();
    });
});

describe('AdminEnemyPatrol.cleanup', () => {
    it('does not unregister a map:mapstatechanged listener', () => {
        const map = makeFakeMap();
        const patrol = new AdminEnemyPatrol(map, null);

        patrol.cleanup();

        const unregisteredEvents = map.unregister.mock.calls.map((call) => call[0]);
        expect(unregisteredEvents).not.toContain('map:mapstatechanged');
        expect(fakeState.unregister).toHaveBeenCalledWith('floorid:changed', patrol);
    });
});
