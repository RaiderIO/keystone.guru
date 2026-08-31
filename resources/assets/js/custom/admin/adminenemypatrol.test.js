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

/**
 * A fake DungeonMap exposing only what AdminEnemyPatrol touches.
 */
function makeFakeMap() {
    return {
        register: vi.fn(),
        unregister: vi.fn(),
        mapObjectGroupManager: {
            getByName: () => ({layerGroup: {}}),
        },
    };
}

describe('AdminEnemyPatrol constructor', () => {
    it('does not register a map-wide map:mapstatechanged listener', () => {
        // Regression test for #4393: this listener used to fire a full redraw of connection
        // lines for EVERY admin enemy patrol on the floor on any unrelated map state change
        // (e.g. saving a single other patrol), causing a multi-second freeze on floors with
        // many patrols (e.g. Black Temple).
        const map = makeFakeMap();

        new AdminEnemyPatrol(map, null);

        const registeredEvents = map.register.mock.calls.map((call) => call[0]);
        expect(registeredEvents).not.toContain('map:mapstatechanged');
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
