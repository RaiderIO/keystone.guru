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
// sit behind the `!isMapAdmin()` guard; the faction filter is spared for admins by the
// map context sending the faction key 'any'.
// ---------------------------------------------------------------------------

// 1a. Seasonal type and teeming constants referenced as bare globals by shouldBeVisible().
global.ENEMY_SEASONAL_TYPE_AWAKENED = 'awakened';
global.ENEMY_SEASONAL_TYPE_TORMENTED = 'tormented';
global.ENEMY_SEASONAL_TYPE_INSPIRING = 'inspiring';
global.TEEMING_VISIBLE = 'visible';
global.TEEMING_HIDDEN = 'hidden';

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

// 1e. loadRemoteMapObject() converts numeric strings through util.js' isNumeric(), and save()'s error
// path checks the map context against MapContextDungeonRoute before falling back to defaultAjaxErrorFn().
global.isNumeric = require('../util').isNumeric;
global.MapContextDungeonRoute = class MapContextDungeonRoute {
};

const {MapObject} = require('./mapobject');

/**
 * A fake DungeonMap exposing only what MapObject touches: an event bus, the
 * faction/teeming option lists _getAttributes() maps over, sandbox mode and a
 * leafletMap for isVisibleOnScreen().
 *
 * @param {Object} options
 * @param {boolean} options.sandbox Whether sandbox mode is enabled. MapObject never reads it; the faction filter applies either way.
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
            {key: TEEMING_VISIBLE, description: 'Visible'},
            {key: TEEMING_HIDDEN, description: 'Hidden'},
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

        expect(makeMapObject({teeming: TEEMING_VISIBLE}).shouldBeVisible()).toBe(false);
    });

    it('hides a teeming-hidden object on a teeming map', () => {
        setFakeState({teeming: true});

        expect(makeMapObject({teeming: TEEMING_HIDDEN}).shouldBeVisible()).toBe(false);
    });

    it('shows a teeming-only object on a teeming map', () => {
        setFakeState({teeming: true});

        expect(makeMapObject({teeming: TEEMING_VISIBLE}).shouldBeVisible()).toBe(true);
    });

    it('shows a teeming-hidden object on a non-teeming map', () => {
        setFakeState({teeming: false});

        expect(makeMapObject({teeming: TEEMING_HIDDEN}).shouldBeVisible()).toBe(true);
    });

    it('shows an object without a teeming preference on either map', () => {
        setFakeState({teeming: true});
        expect(makeMapObject().shouldBeVisible()).toBe(true);

        setFakeState({teeming: false});
        expect(makeMapObject().shouldBeVisible()).toBe(true);
    });

    it('ignores the teeming filter when the map is in admin (mapping version edit) mode', () => {
        setFakeState({teeming: false, mapAdmin: true});

        expect(makeMapObject({teeming: TEEMING_VISIBLE}).shouldBeVisible()).toBe(true);
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

describe('MapObject.shouldBeVisible - faction filter in the admin mapping editor', () => {
    // The admin map context sends the faction-agnostic key 'any' (MapContextMappingVersion::toArray()),
    // so the `faction !== 'any'` escape hatch spares faction-specific objects in the mapping editor.
    it('shows a faction-specific object when the admin map context sends the faction-agnostic key', () => {
        setFakeState({faction: 'any', mapAdmin: true});

        expect(makeMapObject({faction: 'alliance'}).shouldBeVisible()).toBe(true);
    });
});

describe('MapObject.shouldBeVisible - filter precedence', () => {
    it('applies the floor filter before any of the map-context filters', () => {
        // A map admin on the wrong floor is still hidden: the admin bypass only covers the
        // teeming/seasonal filters, never the floor the object physically sits on.
        setFakeState({currentFloorId: 2, mapAdmin: true});

        expect(makeMapObject({teeming: TEEMING_VISIBLE}).shouldBeVisible()).toBe(false);
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

describe('MapObject.loadRemoteMapObject', () => {
    it('converts each remote value by the type of its attribute', () => {
        setFakeState();
        const mapObject = makeMapObject();
        mapObject._getAttributes = () => [
            new Attribute({name: 'enabled', type: 'bool'}),
            new Attribute({name: 'disabled', type: 'bool'}),
            new Attribute({name: 'count', type: 'int'}),
            new Attribute({name: 'label', type: 'int'}),
            new Attribute({name: 'size', type: 'float'}),
            new Attribute({name: 'scale', type: 'double'}),
        ];

        mapObject.loadRemoteMapObject({enabled: 1, disabled: '0', count: '12', label: 'abc', size: '1.5', scale: '2.25'});

        expect(mapObject.enabled).toBe(true);
        expect(mapObject.disabled).toBe(false);
        expect(mapObject.count).toBe(12);
        expect(mapObject.label).toBe('abc');
        expect(mapObject.size).toBe(1.5);
        expect(mapObject.scale).toBe(2.25);
    });

    it('hands a value to the attribute setter instead of assigning it', () => {
        setFakeState();
        const mapObject = makeMapObject();

        mapObject.loadRemoteMapObject({faction: ''});

        expect(mapObject.faction).toBe('any');
    });

    it('loads a nested object attribute into its own object', () => {
        setFakeState();
        const mapObject = makeMapObject();
        mapObject._getAttributes = () => [
            new Attribute({
                name: 'polyline',
                type: 'object',
                attributes: [
                    new Attribute({name: 'color', type: 'string'}),
                    new Attribute({name: 'weight', type: 'int'}),
                ],
            }),
        ];

        mapObject.loadRemoteMapObject({polyline: {color: '#ff0000', weight: '3', ignored: true}});

        expect(mapObject.polyline).toEqual({color: '#ff0000', weight: 3});
    });

    it('leaves attributes the remote object does not carry untouched', () => {
        setFakeState();
        const mapObject = makeMapObject({teeming: TEEMING_HIDDEN});

        mapObject.loadRemoteMapObject({id: '5'});

        expect(mapObject.id).toBe(5);
        expect(mapObject.teeming).toBe(TEEMING_HIDDEN);
    });

    it('signals object:initialized once, after the first load only', () => {
        setFakeState();
        const mapObject = makeMapObject();

        mapObject.loadRemoteMapObject({id: 5});
        mapObject.loadRemoteMapObject({id: 6});

        expect(mapObject.isInitialized()).toBe(true);
        expect(signalsOf(mapObject, 'object:initialized')).toHaveLength(1);
    });

    it('signals object:initialized only once every attribute has loaded, nested ones included', () => {
        setFakeState();
        const mapObject = makeMapObject();
        mapObject._getAttributes = () => [
            new Attribute({name: 'polyline', type: 'object', attributes: [new Attribute({name: 'color', type: 'string'})]}),
            new Attribute({name: 'description', type: 'string'}),
        ];
        let descriptionWhenInitialized = null;
        mapObject.register('object:initialized', mapObject, () => (descriptionWhenInitialized = mapObject.description));

        mapObject.loadRemoteMapObject({polyline: {color: '#ff0000'}, description: 'Pull 1'});

        expect(descriptionWhenInitialized).toBe('Pull 1');
        expect(signalsOf(mapObject, 'object:initialized')).toHaveLength(1);
    });

    it('does not initialize from an empty remote object', () => {
        setFakeState();
        const mapObject = makeMapObject();
        const consoleWarn = vi.spyOn(console, 'warn').mockImplementation(() => {
        });

        expect(() => mapObject.loadRemoteMapObject(null)).not.toThrow();

        expect(consoleWarn).toHaveBeenCalledWith('Unable to parse empty remoteMapObject');
        expect(mapObject.isInitialized()).toBe(false);
        expect(signalsOf(mapObject, 'object:initialized')).toHaveLength(0);
    });
});

describe('MapObject.setSynced', () => {
    it('rebinds the tooltip and popup and signals object:changed when synced', () => {
        setFakeState();
        const mapObject = makeMapObject();
        mapObject.bindTooltip = vi.fn();
        mapObject._assignPopup = vi.fn();

        mapObject.setSynced(true, true);

        expect(mapObject.synced).toBe(true);
        expect(mapObject.bindTooltip).toHaveBeenCalledTimes(1);
        expect(mapObject._assignPopup).toHaveBeenCalledTimes(1);
        expect(signalsOf(mapObject, 'object:changed')).toEqual([{event: 'object:changed', data: {mass_save: true}}]);
    });

    it('only records the state when unsynced', () => {
        setFakeState();
        const mapObject = makeMapObject();
        mapObject.bindTooltip = vi.fn();
        mapObject._assignPopup = vi.fn();
        mapObject.setSynced(true);

        mapObject.setSynced(false);

        expect(mapObject.synced).toBe(false);
        expect(mapObject.bindTooltip).toHaveBeenCalledTimes(1);
        expect(signalsOf(mapObject, 'object:changed')).toHaveLength(1);
    });
});

describe('MapObject.rebindTooltip', () => {
    it('unbinds the current tooltip before binding it again', () => {
        setFakeState();
        const calls = [];
        const mapObject = makeMapObject({layer: {unbindTooltip: () => calls.push('unbind')}});
        mapObject.bindTooltip = () => calls.push('bind');

        mapObject.rebindTooltip();

        expect(calls).toEqual(['unbind', 'bind']);
    });

    it('only binds when there is no layer yet', () => {
        setFakeState();
        const mapObject = makeMapObject();
        mapObject.bindTooltip = vi.fn();

        expect(() => mapObject.rebindTooltip()).not.toThrow();
        expect(mapObject.bindTooltip).toHaveBeenCalledTimes(1);
    });
});

describe('MapObject.save and delete', () => {
    const originalAjax = global.$.ajax;

    beforeEach(() => {
        global.$.ajax = vi.fn();
        global.defaultAjaxErrorFn = vi.fn();
    });

    afterEach(() => {
        global.$.ajax = originalAjax;
    });

    /**
     * Installs a fake state whose map context belongs to the route with the given public key.
     *
     * @param {String} publicKey
     */
    function setRouteState(publicKey = 'abc123') {
        global.getState = () => ({
            isDebug: () => false,
            getMapContext: () => ({getPublicKey: () => publicKey}),
        });
    }

    /**
     * A map object of a model with route model binding, on a map that can close popups.
     *
     * @param {Object} options The options a subclass passes to the MapObject constructor.
     * @returns {MapObject}
     */
    function makeSavableMapObject(options = {name: 'killzone', has_route_model_binding: true}) {
        setFakeState();
        const map = makeFakeMap();
        map.leafletMap.closePopup = vi.fn();
        const mapObject = new MapObject(map, null, options);
        mapObject.getSaveData = () => ({color: '#ff0000'});
        mapObject._assignPopup = vi.fn();
        setRouteState();

        return mapObject;
    }

    it('saves an existing object with route model binding through a PUT to its own url', () => {
        const mapObject = makeSavableMapObject();
        mapObject.id = 7;

        mapObject.save();

        expect(global.$.ajax).toHaveBeenCalledTimes(1);
        const ajaxOptions = global.$.ajax.mock.calls[0][0];
        expect(ajaxOptions.type).toBe('PUT');
        expect(ajaxOptions.url).toBe('/ajax/abc123/killzone/7');
        expect(ajaxOptions.data).toEqual({color: '#ff0000'});
        expect(signalsOf(mapObject, 'save:beforesend')).toHaveLength(1);
    });

    it('creates a new object through a POST to the collection url', () => {
        const mapObject = makeSavableMapObject();

        mapObject.save();

        const ajaxOptions = global.$.ajax.mock.calls[0][0];
        expect(ajaxOptions.type).toBe('POST');
        expect(ajaxOptions.url).toBe('/ajax/abc123/killzone');
    });

    it('posts an existing object without route model binding to the collection url', () => {
        const mapObject = makeSavableMapObject({name: 'brushline'});
        mapObject.id = 7;

        mapObject.save();

        const ajaxOptions = global.$.ajax.mock.calls[0][0];
        expect(ajaxOptions.type).toBe('POST');
        expect(ajaxOptions.url).toBe('/ajax/abc123/brushline');
    });

    it('saves to the save_url option when one is given', () => {
        const mapObject = makeSavableMapObject({name: 'enemy', save_url: '/ajax/admin/enemy'});

        mapObject.save();

        expect(global.$.ajax.mock.calls[0][0].url).toBe('/ajax/admin/enemy');
    });

    it('applies the saved object and signals save:success when the save succeeds', () => {
        const mapObject = makeSavableMapObject();
        mapObject.onSaveSuccess = vi.fn();
        mapObject.save();

        global.$.ajax.mock.calls[0][0].success({id: '9'});

        expect(mapObject.id).toBe(9);
        expect(mapObject.synced).toBe(true);
        expect(mapObject.map.leafletMap.closePopup).toHaveBeenCalledTimes(1);
        expect(signalsOf(mapObject, 'save:success')).toEqual([{event: 'save:success', data: {json: {id: '9'}}}]);
        expect(mapObject.onSaveSuccess).toHaveBeenCalledWith({id: '9'});
    });

    it('marks the object unsynced and reports the error when the save fails', () => {
        const mapObject = makeSavableMapObject();
        mapObject.synced = true;
        mapObject.save();
        const xhr = {responseJSON: {message: 'Server error'}};

        global.$.ajax.mock.calls[0][0].error(xhr, 'error', 'Internal Server Error');

        expect(mapObject.synced).toBe(false);
        expect(global.defaultAjaxErrorFn).toHaveBeenCalledWith(xhr, 'error', 'Internal Server Error');
        expect(signalsOf(mapObject, 'save:error')).toHaveLength(1);
    });

    it('never saves or deletes a local object', () => {
        const mapObject = makeSavableMapObject();
        mapObject.setIsLocal(true);

        mapObject.save();
        mapObject.delete();

        expect(global.$.ajax).not.toHaveBeenCalled();
        expect(signalsOf(mapObject, 'save:beforesend')).toHaveLength(0);
    });

    it('deletes through a DELETE to its own url and cleans up once it succeeded', () => {
        const mapObject = makeSavableMapObject();
        mapObject.id = 7;
        mapObject.cleanup = vi.fn();

        mapObject.delete();
        const ajaxOptions = global.$.ajax.mock.calls[0][0];
        ajaxOptions.success({});

        expect(ajaxOptions.url).toBe('/ajax/abc123/killzone/7');
        expect(ajaxOptions.data).toEqual({_method: 'DELETE'});
        expect(signalsOf(mapObject, 'object:deleted')).toEqual([{event: 'object:deleted', data: {mass_delete: false}}]);
        expect(signalsOf(mapObject, 'delete:success')).toHaveLength(1);
        expect(mapObject.cleanup).toHaveBeenCalledTimes(1);
    });

    it('marks the object unsynced and reports the error when the delete fails', () => {
        const mapObject = makeSavableMapObject({name: 'enemy', delete_url: '/ajax/admin/enemy/7'});
        mapObject.synced = true;
        mapObject.delete();
        const ajaxOptions = global.$.ajax.mock.calls[0][0];
        const xhr = {};

        ajaxOptions.error(xhr, 'error', 'Internal Server Error');

        expect(ajaxOptions.url).toBe('/ajax/admin/enemy/7');
        expect(mapObject.synced).toBe(false);
        expect(global.defaultAjaxErrorFn).toHaveBeenCalledWith(xhr, 'error', 'Internal Server Error');
        expect(signalsOf(mapObject, 'delete:success')).toHaveLength(0);
    });
});
