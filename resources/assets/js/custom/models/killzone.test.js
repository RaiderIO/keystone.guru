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

    cleanup() {}

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

// 1d-bis. Collaborator classes the covered methods only reference through `instanceof`.
global.Enemy = class Enemy {};
global.MapContextLiveSession = class MapContextLiveSession {};
global.SelectKillZoneEnemySelectionOverpull = class SelectKillZoneEnemySelectionOverpull {};
global.EnemySelection = class EnemySelection {
    constructor(mapObject = null) {
        this._mapObject = mapObject;
    }

    getMapObject() {
        return this._mapObject;
    }

    register() {}

    unregister() {}
};

// 1e. getState() is called from the constructor; provide a fake that satisfies
// the event registrations it performs.
const fakeState = {
    register: () => {},
    unregister: () => {},
    getMapContext: () => ({register: () => {}, unregister: () => {}}),
};
global.getState = () => fakeState;

// 1f. cleanup() iterates the enemy group's objects via `$.each`; the shared setup's `$`
// stub does not define it.
global.$.each = (obj, callback) => {
    Object.keys(obj ?? {}).forEach((key) => callback(key, obj[key]));
};

const {KillZone} = require('./killzone');

/**
 * A fake enemy collaborator. Tracks its assigned kill zone and answers the classification
 * questions KillZone asks with defaults for an enemy with no special classification (not
 * prideful, awakened, or linked to the last boss).
 */
function makeFakeEnemy(id) {
    // KillZone._enemySelected() asserts `enemy instanceof Enemy`, so build off that prototype.
    return Object.assign(new Enemy(), {
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
        isObsolete: () => false,
        getEnemyForces: () => 10,
        getPackBuddies: () => [],
    });
}

/**
 * Builds a pack of fake enemies that all know each other as pack buddies.
 *
 * @param {Number} packId
 * @param {Number[]} ids
 * @returns {Object[]}
 */
function makeFakeEnemyPack(packId, ids) {
    const pack = ids.map((id) => {
        const enemy = makeFakeEnemy(id);
        enemy.enemy_pack_id = packId;
        return enemy;
    });

    // getPackBuddies() is pushed onto by _enemySelected(), so hand out a fresh array each call.
    pack.forEach((enemy) => {
        enemy.getPackBuddies = () => pack.filter((buddy) => buddy !== enemy);
    });

    return pack;
}

/**
 * @param {Object} enemy
 * @param {Object} [context]
 * @returns {Object}
 */
function enemySelectedEvent(enemy, context = {}) {
    return {data: {enemy: enemy, ignorePackBuddies: false}, context: context};
}

/**
 * A fake DungeonMap exposing only what KillZone touches: an event bus and a
 * mapObjectGroupManager whose enemy group resolves the provided enemies by id.
 *
 * @param {Object} enemiesById
 * @param {Object} [options]
 * @param {boolean} [options.hideKillZoneGroup] Mirrors MapObjectGroupManager.getByName()
 *   returning `false` (its "not found" sentinel, not `null`) for a group a page hides via
 *   its `hiddenMapObjectGroups` option - e.g. Explore mode hiding the 'killzone' group.
 */
function makeFakeMap(enemiesById = {}, options = {}) {
    const enemyGroup = {
        register: () => {},
        unregister: () => {},
        findMapObjectById: (id) => enemiesById[id] ?? null,
        setMapObjectVisibility: () => {},
        objects: {},
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
            getByName: (name) => {
                if (name === MAP_OBJECT_GROUP_ENEMY) {
                    return enemyGroup;
                }
                if (name === MAP_OBJECT_GROUP_KILLZONE && options.hideKillZoneGroup) {
                    return false;
                }
                return genericGroup;
            },
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
        expect(killZone.overpulledEnemies).toEqual([]);
    });

    // Regression test for the Explore-mode "killZoneMapObjectGroup.register is not a
    // function" crash: EditKillZoneEnemySelection.isEnemySelectable() builds a throwaway
    // KillZone on every enemy click, even on pages (like Explore) whose hiddenMapObjectGroups
    // option means no KillZoneMapObjectGroup was ever instantiated.
    it('does not throw when the killzone map object group is hidden for the current page', () => {
        expect(() => new KillZone(makeFakeMap({}, {hideKillZoneGroup: true}), null)).not.toThrow();
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

describe('KillZone.cleanup', () => {
    // Regression test: EditKillZoneEnemySelection.isEnemySelectable() always calls
    // cleanup() on its throwaway KillZone, including on pages where the killzone map
    // object group was never instantiated (see the constructor test above).
    it('does not throw when the killzone map object group is hidden for the current page', () => {
        const killZone = new KillZone(makeFakeMap({}, {hideKillZoneGroup: true}), null);

        expect(() => killZone.cleanup()).not.toThrow();
    });
});

describe('KillZone.toString', () => {
    it('describes itself by its pull index', () => {
        const killZone = new KillZone(makeFakeMap(), null);
        killZone.setIndex(7);

        expect(killZone.toString()).toBe('Pull 7');
    });
});

describe('KillZone._mapStateChanged', () => {
    /**
     * @param {KillZone} killZone
     * @param {?Object} previousMapState
     * @param {?Object} newMapState
     */
    const fireMapStateChanged = (killZone, previousMapState, newMapState) => killZone._mapStateChanged({
        data: {previousMapState: previousMapState, newMapState: newMapState},
    });

    /**
     * @param {Number} id
     * @returns {KillZone}
     */
    function makeSelectableKillZone(id) {
        const killZone = new KillZone(makeFakeMap(), null);
        killZone.id = id;
        // Isolate from the heavy, Leaflet-coupled redraw - the redraw is what is being counted.
        killZone.redrawConnectionsToEnemies = vi.fn();

        return killZone;
    }

    it('redraws when this kill zone becomes the selected one', () => {
        const killZone = makeSelectableKillZone(5);

        fireMapStateChanged(killZone, null, new EnemySelection({id: 5}));

        expect(killZone.redrawConnectionsToEnemies).toHaveBeenCalledOnce();
    });

    it('redraws when this kill zone stops being the selected one', () => {
        const killZone = makeSelectableKillZone(5);

        fireMapStateChanged(killZone, new EnemySelection({id: 5}), null);

        expect(killZone.redrawConnectionsToEnemies).toHaveBeenCalledOnce();
    });

    // The regression this pins (#4589): every pull redrew its enemy hull on every map state change,
    // three addLayer plus up to three removeLayer calls each, for a visually identical result.
    it('does not redraw when another kill zone is the target of the selection', () => {
        const killZone = makeSelectableKillZone(5);

        fireMapStateChanged(killZone, new EnemySelection({id: 6}), new EnemySelection({id: 7}));

        expect(killZone.redrawConnectionsToEnemies).not.toHaveBeenCalled();
    });

    it('does not redraw when neither map state is an enemy selection', () => {
        const killZone = makeSelectableKillZone(5);

        fireMapStateChanged(killZone, null, {});

        expect(killZone.redrawConnectionsToEnemies).not.toHaveBeenCalled();
    });
});

describe('KillZone._enemySelected', () => {
    /**
     * @param {Object[]} enemies
     * @returns {KillZone}
     */
    function makeSavedKillZone(enemies) {
        const enemiesById = {};
        enemies.forEach((enemy) => (enemiesById[enemy.id] = enemy));

        const killZone = new KillZone(makeFakeMap(enemiesById), null);
        killZone.id = 5;
        killZone.redrawConnectionsToEnemies = vi.fn();
        killZone._signals = [];

        return killZone;
    }

    // The regression this pins (#4590): each pack buddy emitted its own killzone:enemyadded, and every
    // one of those fans out to a killzone:changed that rebinds tooltips and updates every sidebar row.
    it('collapses a pack add into a single killzone:enemieschanged signal', () => {
        const pack = makeFakeEnemyPack(7, [1, 2, 3]);
        const killZone = makeSavedKillZone(pack);

        killZone._enemySelected(enemySelectedEvent(pack[0]));

        // _enemySelected() walks the pack buddies before the clicked enemy itself.
        expect(killZone.enemies).toEqual([2, 3, 1]);
        expect(signalsOf(killZone, 'killzone:enemyadded')).toHaveLength(0);

        const changed = signalsOf(killZone, 'killzone:enemieschanged');
        expect(changed).toHaveLength(1);
        expect(changed[0].data).toEqual({previousForces: 0, newForces: 30});
    });

    it('collapses a pack removal into a single killzone:enemieschanged signal', () => {
        const pack = makeFakeEnemyPack(7, [1, 2, 3]);
        const killZone = makeSavedKillZone(pack);
        pack.forEach((enemy) => killZone._addEnemy(enemy));
        killZone._signals = [];

        killZone._enemySelected(enemySelectedEvent(pack[0]));

        expect(killZone.enemies).toEqual([]);
        expect(signalsOf(killZone, 'killzone:enemyremoved')).toHaveLength(0);

        const changed = signalsOf(killZone, 'killzone:enemieschanged');
        expect(changed).toHaveLength(1);
        expect(changed[0].data).toEqual({previousForces: 30, newForces: 0});
    });

    it('still signals per enemy when the enemy is not part of a pack', () => {
        const enemy = makeFakeEnemy(1);
        enemy.enemy_pack_id = 0;
        const killZone = makeSavedKillZone([enemy]);

        killZone._enemySelected(enemySelectedEvent(enemy));

        expect(signalsOf(killZone, 'killzone:enemyadded')).toHaveLength(1);
        expect(signalsOf(killZone, 'killzone:enemieschanged')).toHaveLength(0);
    });

    // killzone:enemieschanged carries an enemy forces delta that already includes the overpulled
    // enemies, and nothing listens to it for the overpull visuals - so that path keeps its own signals.
    it('leaves the overpull path signalling per pack buddy', () => {
        const pack = makeFakeEnemyPack(7, [1, 2, 3]);
        const killZone = makeSavedKillZone(pack);

        killZone._enemySelected(enemySelectedEvent(pack[0], new SelectKillZoneEnemySelectionOverpull()));

        expect(killZone.overpulledEnemies).toEqual([2, 3, 1]);
        expect(signalsOf(killZone, 'killzone:overpulledenemyadded')).toHaveLength(3);
        expect(signalsOf(killZone, 'killzone:enemieschanged')).toHaveLength(0);
    });
});
