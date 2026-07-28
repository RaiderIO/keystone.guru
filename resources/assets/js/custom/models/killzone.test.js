// ---------------------------------------------------------------------------
// RECIPE: testing a global-script model class (reusable for the other ~25 models)
//
// The model files are concatenated into one bundle in the browser, so they
// reference collaborators as bare globals (`MapObject`, `L`, `getState`,
// `Attribute`, the `MAP_OBJECT_GROUP_*` constants, ...) both at load time and
// inside their methods. To exercise such a class in isolation:
//
//   1. Define the globals it touches at LOAD time BEFORE `require()`-ing it:
//      - its base class (`MapObject`) as a lightweight stub that records signals
//        instead of wiring the real event system,
//      - a richer `L` than the shared setup provides (KillZone builds Leaflet
//        icons / draw handlers at the top of the file),
//      - the `MAP_OBJECT_GROUP_*` constants and an `Attribute` stub it uses.
//      (`$`, `$.inArray`, `lang`, `getState`, `Cookies` come from the shared
//      setup file; `getState` is overridden below with a richer fake.)
//   2. Pass a hand-rolled fake `map` (with a `mapObjectGroupManager`) and fake
//      `enemy` collaborators rather than constructing the whole DungeonMap.
//   3. For heavy, DOM/Leaflet-coupled methods (e.g. `redrawConnectionsToEnemies`),
//      replace them on the instance with `vi.fn()` to isolate the unit under test.
// ---------------------------------------------------------------------------

// 1a. Constants referenced as bare globals by the class body.
global.MAP_OBJECT_GROUP_ENEMY = 'enemy';
global.MAP_OBJECT_GROUP_KILLZONE = 'killzone';

// 1b. Minimal `Attribute` stub: the real one just copies its options onto itself.
global.Attribute = class Attribute {
    constructor(options) {
        Object.assign(this, options);
    }
};

// 1c. Lightweight base class. It records emitted signals on `this._signals` so
// tests can assert on them, and provides the super-methods KillZone calls.
global.MapObject = class MapObject {
    constructor(map, layer = null, options = {}) {
        this.map = map;
        this.layer = layer;
        this.options = options;
        this.synced = false;
        this._cachedAttributes = null;
        this._signals = [];
    }

    register() {}

    unregister() {}

    signal(event, data) {
        this._signals.push({event, data});
    }

    setSynced(synced) {
        this.synced = synced;
    }

    setDefaultVisible(visible) {
        this._defaultVisible = visible;
    }

    bindTooltip() {}

    onSaveSuccess() {}

    onDeleteSuccess() {}

    _getAttributes() {
        return [];
    }
};

// 1d. KillZone builds Leaflet icons and draw handlers at load time, so `L` needs
// more than the empty shared-setup stub.
global.L = {
    divIcon: () => ({}),
    Marker: {extend: () => function () {}},
    Draw: {
        Marker: {extend: () => function () {}},
        Feature: {prototype: {initialize() {}}},
    },
};

// 1e. getState() is called from the constructor; provide a fake that satisfies
// the event registrations it performs.
const fakeState = {
    register: () => {},
    getMapContext: () => ({register: () => {}}),
};
global.getState = () => fakeState;

const {KillZone} = require('./killzone');

/**
 * A fake enemy collaborator. Tracks its assigned kill zone and answers the
 * classification questions KillZone asks with sensible "plain enemy" defaults.
 */
function makeFakeEnemy(id) {
    return {
        id,
        enemy_pack_id: null,
        _killZone: null,
        setKillZone(killZone) {
            this._killZone = killZone;
        },
        getKillZone() {
            return this._killZone;
        },
        register() {},
        unregister() {},
        isPridefulNpc: () => false,
        isAwakenedNpc: () => false,
        isLinkedToLastBoss: () => false,
    };
}

/**
 * A fake DungeonMap exposing only what KillZone touches: an event bus and a
 * mapObjectGroupManager whose enemy group resolves the provided enemies by id.
 */
function makeFakeMap(enemiesById = {}) {
    const enemyGroup = {
        register: () => {},
        unregister: () => {},
        findMapObjectById: (id) => enemiesById[id] ?? null,
        setMapObjectVisibility: () => {},
    };
    const genericGroup = {
        register: () => {},
        unregister: () => {},
        findMapObjectById: () => null,
    };

    return {
        options: {edit: false, noUI: true},
        register: () => {},
        unregister: () => {},
        mapObjectGroupManager: {
            getByName: (name) => (name === MAP_OBJECT_GROUP_ENEMY ? enemyGroup : genericGroup),
        },
    };
}

const signalsOf = (killZone, event) => killZone._signals.filter((signal) => signal.event === event);

describe('KillZone constructor', () => {
    it('initializes the documented defaults without wiring a real map', () => {
        const killZone = new KillZone(makeFakeMap(), null);

        expect(killZone.id).toBe(0);
        expect(killZone.label).toBe('KillZone');
        expect(killZone.enemies).toEqual([]);
        expect(killZone.spellIds).toEqual([]);
    });
});

describe('KillZone._addEnemy', () => {
    it('adds the enemy id, attaches the kill zone and signals once', () => {
        const killZone = new KillZone(makeFakeMap(), null);
        const enemy = makeFakeEnemy(42);
        killZone._signals = [];

        killZone._addEnemy(enemy);

        expect(killZone.enemies).toEqual([42]);
        expect(enemy.getKillZone()).toBe(killZone);
        expect(signalsOf(killZone, 'killzone:enemyadded')).toHaveLength(1);
    });

    it('does not add the same enemy twice nor signal again', () => {
        const killZone = new KillZone(makeFakeMap(), null);
        const enemy = makeFakeEnemy(42);
        killZone._signals = [];

        killZone._addEnemy(enemy);
        killZone._addEnemy(enemy);

        expect(killZone.enemies).toEqual([42]);
        expect(signalsOf(killZone, 'killzone:enemyadded')).toHaveLength(1);
    });
});

describe('KillZone._removeEnemy', () => {
    it('removes the enemy id, detaches it and signals removal', () => {
        const enemy = makeFakeEnemy(42);
        const killZone = new KillZone(makeFakeMap({42: enemy}), null);
        killZone._addEnemy(enemy);
        killZone._signals = [];

        killZone._removeEnemy(enemy);

        expect(killZone.enemies).toEqual([]);
        expect(enemy.getKillZone()).toBeNull();
        expect(signalsOf(killZone, 'killzone:enemyremoved')).toHaveLength(1);
    });

    it('does nothing when the enemy is not part of the kill zone', () => {
        const enemy = makeFakeEnemy(42);
        const killZone = new KillZone(makeFakeMap({42: enemy}), null);
        killZone._signals = [];

        killZone._removeEnemy(enemy);

        expect(killZone.enemies).toEqual([]);
        expect(signalsOf(killZone, 'killzone:enemyremoved')).toHaveLength(0);
    });
});

describe('KillZone._getAttributes', () => {
    it('appends the kill-zone specific attributes onto the base attributes', () => {
        const killZone = new KillZone(makeFakeMap(), null);

        const names = killZone._getAttributes(true).map((attribute) => attribute.name);

        expect(names).toEqual(expect.arrayContaining([
            'floor_id', 'color', 'description', 'lat', 'lng', 'index', 'enemies', 'spells', 'killzone_paths',
        ]));
    });

    it('returns the cached attributes on a subsequent unforced call', () => {
        const killZone = new KillZone(makeFakeMap(), null);

        const first = killZone._getAttributes(true);
        const second = killZone._getAttributes(false);

        expect(second).toBe(first);
    });
});

describe('KillZone.onSaveSuccess', () => {
    it('emits killzone:changed carrying the saved enemy forces', () => {
        const killZone = new KillZone(makeFakeMap(), null);
        // Isolate from the heavy, Leaflet-coupled redraw.
        killZone.redrawConnectionsToEnemies = vi.fn();
        killZone._signals = [];

        killZone.onSaveSuccess({enemy_forces: 123}, true);

        const changed = signalsOf(killZone, 'killzone:changed');
        expect(changed).toHaveLength(1);
        expect(changed[0].data).toEqual({enemy_forces: 123, mass_save: true});
        expect(killZone.redrawConnectionsToEnemies).toHaveBeenCalledOnce();
    });
});

describe('KillZone.toString', () => {
    it('describes itself by its pull index', () => {
        const killZone = new KillZone(makeFakeMap(), null);
        killZone.setIndex(7);

        expect(killZone.toString()).toBe('Pull 7');
    });
});
