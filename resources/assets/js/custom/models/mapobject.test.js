// ---------------------------------------------------------------------------
// Coverage for the MapObject base class - the visibility/filter chain that every
// map object on every map inherits (#3674, phase 2 continuation of #3292).
//
// Follows the global-script model recipe documented at the top of
// killzone.test.js: define the globals mapobject.js touches at LOAD time before
// require()-ing it, then hand the class fake collaborators instead of building a
// real DungeonMap.
//
// `shouldBeVisible()` is the interesting one: it is a chain of four independent
// filters (floor, teeming, seasonal index, faction) of which only the middle two
// sit behind the `!isMapAdmin()` guard. That asymmetry is half of the open bug
// #1389 - see the `(#1389)` describe block for the other half.
// ---------------------------------------------------------------------------

// 1a. Seasonal type constants referenced as bare globals by shouldBeVisible().
global.ENEMY_SEASONAL_TYPE_AWAKENED = 'awakened';
global.ENEMY_SEASONAL_TYPE_TORMENTED = 'tormented';
global.ENEMY_SEASONAL_TYPE_INSPIRING = 'inspiring';

// 1b. Minimal `Attribute` stub: the real one just copies its options onto itself.
// MapObject builds three of these in _getAttributes(), which the constructor calls
// through _setDefaults().
global.Attribute = class Attribute {
    constructor(options) {
        Object.assign(this, options);
    }
};

// 1c. A working - if small - stand-in for Signalable. MapObject registers on its own
// signals from the constructor and emits 'shown'/'hidden' from setVisible(), so a
// stub that swallows signals would not exercise those paths. It mirrors the real
// signalable.js on the parts a test can observe: listeners receive the
// `{name, context, data}` wrapper (NOT the raw payload), and unregister() takes an
// optional `fn` to narrow the removal. Emitted signals are additionally recorded on
// `_emittedSignals` so tests can assert on them.
global.Signalable = class Signalable {
    constructor() {
        this._signals = [];
        this._emittedSignals = [];
        this._cleanedUp = false;
    }

    register(names, listener, fn) {
        for (const name of Array.isArray(names) ? names : [names]) {
            this._signals.push({name, listener, callback: fn});
        }
    }

    unregister(names, listener, fn = null) {
        for (const name of Array.isArray(names) ? names : [names]) {
            this._signals = this._signals.filter((caller) => !(
                caller.name === name && caller.listener === listener && (fn === null || caller.callback === fn)
            ));
        }
    }

    signal(name, data = {}) {
        this._emittedSignals.push({event: name, data});

        for (const caller of this._signals) {
            if (caller.name === name) {
                caller.callback({name: name, context: this, data: data});
            }
        }
    }

    _cleanupSignals() {
        this._signals = [];
    }

    cleanup() {
        this._cleanedUp = true;
    }
};

// 1d. The constructor asserts `map instanceof DungeonMap`; the fake map below is built
// from this stub so the assert holds instead of spamming the console.
global.DungeonMap = class DungeonMap {
};

const {MapObject} = require('./mapobject');

/**
 * A fake DungeonMap exposing only what MapObject touches: an event bus, the
 * faction/teeming option lists _getAttributes() maps over, sandbox mode and a
 * leafletMap for isVisibleOnScreen().
 *
 * @param {Object} options
 * @param {boolean} options.sandbox Whether sandbox mode is enabled (disables the faction filter).
 * @param {L.LatLngBounds|null} options.bounds Bounds returned by the leaflet map.
 */
function makeFakeMap({sandbox = false, bounds = null} = {}) {
    const map = new global.DungeonMap();

    map.options = {
        edit: false,
        readonly: false,
        factions: [
            {key: 'any', description: 'Any'},
            {key: 'alliance', description: 'Alliance'},
            {key: 'horde', description: 'Horde'},
        ],
        teemingOptions: [
            {key: 'visible', description: 'Visible'},
            {key: 'hidden', description: 'Hidden'},
        ],
    };
    map.register = () => {
    };
    map.unregister = () => {
    };
    map.isSandboxModeEnabled = () => sandbox;
    map.leafletMap = {
        getBounds: () => bounds,
        removeLayer: () => {
        },
    };

    return map;
}

/**
 * Installs a fake global state for the duration of a test.
 *
 * @param {Object} options
 * @param {Number} options.currentFloorId The floor the map is currently displaying.
 * @param {boolean} options.mapAdmin Whether we're on the admin (mapping) page.
 * @param {boolean} options.teeming Whether the map itself is teeming.
 * @param {Number|null} options.seasonalIndex The map's seasonal index.
 * @param {String} options.faction The map's faction.
 */
function setFakeState({
                          currentFloorId = 1,
                          mapAdmin = false,
                          teeming = false,
                          seasonalIndex = null,
                          faction = 'any',
                      } = {}) {
    global.getState = () => ({
        isDebug: () => false,
        isMapAdmin: () => mapAdmin,
        getCurrentFloor: () => ({id: currentFloorId}),
        getMapContext: () => ({
            getTeeming: () => teeming,
            getSeasonalIndex: () => seasonalIndex,
            getFaction: () => faction,
        }),
    });
}

/**
 * Builds a MapObject sitting on floor 1, with any per-instance properties applied on top.
 * `seasonal_index`/`seasonal_type` are deliberately NOT set by default - the base class does
 * not define them, and shouldBeVisible() checks for their presence with hasOwnProperty().
 *
 * @param {Object} properties
 * @param {DungeonMap|null} map
 * @returns {MapObject}
 */
function makeMapObject(properties = {}, map = null) {
    const mapObject = new MapObject(map ?? makeFakeMap(), null, {name: 'mapobject'});

    mapObject.floor_id = 1;

    return Object.assign(mapObject, properties);
}

const signalsOf = (mapObject, event) => mapObject._emittedSignals.filter((signal) => signal.event === event);

describe('MapObject.shouldBeVisible - floor filter', () => {
    it('hides an object that lives on another floor', () => {
        setFakeState({currentFloorId: 2});

        expect(makeMapObject().shouldBeVisible()).toBe(false);
    });

    it('shows an object on the floor currently displayed', () => {
        setFakeState({currentFloorId: 1});

        expect(makeMapObject().shouldBeVisible()).toBe(true);
    });
});

describe('MapObject.shouldBeVisible - teeming filter', () => {
    it('hides a teeming-only object on a non-teeming map', () => {
        setFakeState({teeming: false});

        expect(makeMapObject({teeming: 'visible'}).shouldBeVisible()).toBe(false);
    });

    it('hides a teeming-hidden object on a teeming map', () => {
        setFakeState({teeming: true});

        expect(makeMapObject({teeming: 'hidden'}).shouldBeVisible()).toBe(false);
    });

    it('shows a teeming-only object on a teeming map', () => {
        setFakeState({teeming: true});

        expect(makeMapObject({teeming: 'visible'}).shouldBeVisible()).toBe(true);
    });

    it('shows a teeming-hidden object on a non-teeming map', () => {
        setFakeState({teeming: false});

        expect(makeMapObject({teeming: 'hidden'}).shouldBeVisible()).toBe(true);
    });

    it('shows an object without a teeming preference on either map', () => {
        setFakeState({teeming: true});
        expect(makeMapObject().shouldBeVisible()).toBe(true);

        setFakeState({teeming: false});
        expect(makeMapObject().shouldBeVisible()).toBe(true);
    });

    it('ignores the teeming filter when the map is in admin (mapping version edit) mode', () => {
        setFakeState({teeming: false, mapAdmin: true});

        expect(makeMapObject({teeming: 'visible'}).shouldBeVisible()).toBe(true);
    });
});

describe('MapObject.shouldBeVisible - seasonal index filter', () => {
    it('hides an object whose seasonal index does not match the map', () => {
        setFakeState({seasonalIndex: 1});

        expect(makeMapObject({seasonal_index: 2}).shouldBeVisible()).toBe(false);
    });

    it('shows an object whose seasonal index matches the map', () => {
        setFakeState({seasonalIndex: 2});

        expect(makeMapObject({seasonal_index: 2}).shouldBeVisible()).toBe(true);
    });

    it('ignores an unset seasonal index', () => {
        setFakeState({seasonalIndex: 1});

        expect(makeMapObject({seasonal_index: null}).shouldBeVisible()).toBe(true);
    });

    it('applies the filter to awakened and tormented enemies', () => {
        setFakeState({seasonalIndex: 1});

        expect(makeMapObject({
            seasonal_index: 2,
            seasonal_type: ENEMY_SEASONAL_TYPE_AWAKENED,
        }).shouldBeVisible()).toBe(false);
        expect(makeMapObject({
            seasonal_index: 2,
            seasonal_type: ENEMY_SEASONAL_TYPE_TORMENTED,
        }).shouldBeVisible()).toBe(false);
    });

    it('leaves other seasonal types alone, even on a seasonal index mismatch', () => {
        setFakeState({seasonalIndex: 1});

        expect(makeMapObject({
            seasonal_index: 2,
            seasonal_type: ENEMY_SEASONAL_TYPE_INSPIRING,
        }).shouldBeVisible()).toBe(true);
    });

    it('ignores the seasonal index filter for map admins', () => {
        setFakeState({seasonalIndex: 1, mapAdmin: true});

        expect(makeMapObject({seasonal_index: 2}).shouldBeVisible()).toBe(true);
    });
});

describe('MapObject.shouldBeVisible - faction filter', () => {
    it('hides an object of the opposing faction', () => {
        setFakeState({faction: 'horde'});

        expect(makeMapObject({faction: 'alliance'}).shouldBeVisible()).toBe(false);
    });

    it('shows an object of the same faction', () => {
        setFakeState({faction: 'horde'});

        expect(makeMapObject({faction: 'horde'}).shouldBeVisible()).toBe(true);
    });

    it('shows a faction-agnostic object on a faction-specific map', () => {
        setFakeState({faction: 'horde'});

        expect(makeMapObject({faction: 'any'}).shouldBeVisible()).toBe(true);
    });

    it('shows a faction-specific object on a faction-agnostic map', () => {
        setFakeState({faction: 'any'});

        expect(makeMapObject({faction: 'alliance'}).shouldBeVisible()).toBe(true);
    });

    it('applies the faction filter the same way in sandbox mode', () => {
        setFakeState({faction: 'horde'});

        const mapObject = makeMapObject({faction: 'alliance'}, makeFakeMap({sandbox: true}));

        expect(mapObject.shouldBeVisible()).toBe(false);
    });
});

describe('MapObject.shouldBeVisible - faction filter in the admin mapping editor (#1389)', () => {
    // Two independent things combine into #1389:
    //
    //  1. the faction check sits OUTSIDE the `!isMapAdmin()` guard that the teeming and seasonal
    //     checks live in, so it still runs for map admins, and
    //  2. the admin map context does not send the faction *key* 'any' but the string
    //     'unspecified': MapContextMappingVersion::toArray() emits
    //     `__(strtolower(Faction 'unspecified'->name))`, and 'unspecified' is not a resolvable
    //     translation key, so it comes through verbatim. It therefore never equals 'any', and the
    //     `faction !== 'any'` escape hatch that spares every other map never fires.
    //
    // The result is that faction-specific enemies disappear from the mapping editor. These tests
    // pin the current behaviour; fixing #1389 on either side flips the first expectation to
    // `true`, and the sibling tests above keep the non-admin paths honest meanwhile.
    it('currently hides a faction-specific object from the admin mapping editor', () => {
        setFakeState({faction: 'unspecified', mapAdmin: true});

        expect(makeMapObject({faction: 'alliance'}).shouldBeVisible()).toBe(false);
    });

    it('would show it if the admin map context sent the faction-agnostic key instead', () => {
        setFakeState({faction: 'any', mapAdmin: true});

        expect(makeMapObject({faction: 'alliance'}).shouldBeVisible()).toBe(true);
    });
});

describe('MapObject.shouldBeVisible - filter precedence', () => {
    it('applies the floor filter before any of the map-context filters', () => {
        // A map admin on the wrong floor is still hidden: the admin bypass only covers the
        // teeming/seasonal filters, never the floor the object physically sits on.
        setFakeState({currentFloorId: 2, mapAdmin: true});

        expect(makeMapObject({teeming: 'visible'}).shouldBeVisible()).toBe(false);
    });
});

describe('MapObject.setVisible', () => {
    it('signals shown when the object becomes visible', () => {
        setFakeState();
        const mapObject = makeMapObject();

        mapObject.setVisible(true);

        expect(mapObject.isVisible()).toBe(true);
        expect(signalsOf(mapObject, 'shown')).toHaveLength(1);
        expect(signalsOf(mapObject, 'hidden')).toHaveLength(0);
    });

    it('signals hidden when the object becomes invisible', () => {
        setFakeState();
        const mapObject = makeMapObject();
        mapObject.setVisible(true);

        mapObject.setVisible(false);

        expect(mapObject.isVisible()).toBe(false);
        expect(signalsOf(mapObject, 'hidden')).toHaveLength(1);
    });

    it('does not signal again when the visibility did not change', () => {
        setFakeState();
        const mapObject = makeMapObject();

        mapObject.setVisible(true);
        mapObject.setVisible(true);

        expect(signalsOf(mapObject, 'shown')).toHaveLength(1);
    });

    it('signals hidden when a freshly constructed object is set to invisible', () => {
        // _visible starts as null rather than false, so the very first setVisible(false) is a
        // change and must still reach the listeners that clean up decorators.
        setFakeState();
        const mapObject = makeMapObject();

        mapObject.setVisible(false);

        expect(signalsOf(mapObject, 'hidden')).toHaveLength(1);
    });
});

describe('MapObject.isVisible', () => {
    it('reports a never-initialized object as not visible', () => {
        setFakeState();

        expect(makeMapObject().isVisible()).toBe(false);
    });
});

describe('MapObject.isDefaultVisible', () => {
    it('defaults to visible', () => {
        setFakeState();

        expect(makeMapObject().isDefaultVisible()).toBe(true);
    });

    it('reflects what setDefaultVisible was given', () => {
        setFakeState();
        const mapObject = makeMapObject();

        mapObject.setDefaultVisible(false);

        expect(mapObject.isDefaultVisible()).toBe(false);
    });
});

describe('MapObject.isVisibleOnScreen', () => {
    /**
     * A fake Leaflet layer sitting at a fixed position.
     *
     * @param {Object} latLng
     * @returns {Object}
     */
    const makeFakeLayer = (latLng = {lat: 0, lng: 0}) => ({getLatLng: () => latLng});

    /**
     * A fake Leaflet bounds object containing everything or nothing.
     *
     * @param {boolean} contains
     * @returns {Object}
     */
    const makeFakeBounds = (contains) => ({contains: () => contains});

    it('is false while the object has no layer yet', () => {
        setFakeState();
        const mapObject = makeMapObject();
        mapObject.setVisible(true);

        expect(mapObject.isVisibleOnScreen(makeFakeBounds(true))).toBe(false);
    });

    it('is false for a hidden object even when its layer is within bounds', () => {
        setFakeState();
        const mapObject = makeMapObject({layer: makeFakeLayer()});
        mapObject.setVisible(false);

        expect(mapObject.isVisibleOnScreen(makeFakeBounds(true))).toBe(false);
    });

    it('is true for a visible object whose layer is within the given bounds', () => {
        setFakeState();
        const mapObject = makeMapObject({layer: makeFakeLayer()});
        mapObject.setVisible(true);

        expect(mapObject.isVisibleOnScreen(makeFakeBounds(true))).toBe(true);
    });

    it('is false for a visible object whose layer is outside the given bounds', () => {
        setFakeState();
        const mapObject = makeMapObject({layer: makeFakeLayer()});
        mapObject.setVisible(true);

        expect(mapObject.isVisibleOnScreen(makeFakeBounds(false))).toBe(false);
    });

    it('falls back to the current map bounds when none are given', () => {
        setFakeState();
        const map = makeFakeMap({bounds: makeFakeBounds(true)});
        const mapObject = makeMapObject({layer: makeFakeLayer()}, map);
        mapObject.setVisible(true);

        expect(mapObject.isVisibleOnScreen()).toBe(true);
    });
});
