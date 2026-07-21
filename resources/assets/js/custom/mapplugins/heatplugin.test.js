// Global stubs ($, L, getState) come from the shared Vitest setup file
// (resources/assets/js/test/setup.js). HeatPlugin additionally needs its base class and a
// handful of globals it references at construction time, so those are defined before requiring
// the source. Anything test-specific (heatLayer factory, state) is overridden per test below.

global.MapPlugin = class MapPlugin {
    constructor(map) {
        this.map = map;
    }

    addToMap() {
    }

    removeFromMap() {
    }

    toggle() {
    }
};

global.MapContextDungeonExplore = class MapContextDungeonExplore {
};

global.COMBAT_LOG_EVENT_DATA_TYPE_PLAYER_POSITION = 'player_position';
global.COMBAT_LOG_EVENT_DATA_TYPE_ENEMY_POSITION = 'enemy_position';
global.COMBAT_LOG_EVENT_DATA_TYPE_ENEMY_FAILURE = 'enemy_failure';
global.isMobile = () => true;

const HeatPlugin = require('./heatplugin');

/**
 * Builds a HeatPlugin backed by mock Leaflet/state objects. The map-level size gate lives in
 * DungeonMap now, so addToMap() here simply adds the layer.
 */
function createHeatPlugin(mapOptions = {}) {
    const heatLayer = {
        addTo: vi.fn(),
        setLatLngs: vi.fn(),
        setOptions: vi.fn(function (options) {
            Object.assign(this.options, options);
        }),
        options: {},
        // Stand-in for leaflet-heat's canvas element; the plugin re-parents this on a render-order
        // change. Each fake pane records what was appended so tests can assert the move.
        _canvas: {id: 'heat-canvas'},
    };

    global.L.heatLayer = vi.fn(() => heatLayer);

    // Fake Leaflet panes that record the last appended child, keyed by pane name.
    const panes = {
        overlayPane: {appended: null, appendChild(node) { this.appended = node; }},
        tooltipPane: {appended: null, appendChild(node) { this.appended = node; }},
    };

    const leafletMap = {
        addLayer: vi.fn(),
        removeLayer: vi.fn(),
        getPane: vi.fn((name) => panes[name]),
    };

    const map = {
        leafletMap,
        options: mapOptions,
        register: vi.fn(),
    };

    // Captures the state listeners the plugin registers so tests can fire signals at it.
    const listeners = {};
    global.getState = () => ({
        register: (event, context, callback) => {
            listeners[event] = callback;
        },
        getMapContext: () => new global.MapContextDungeonExplore(),
        getCurrentFloor: () => ({id: 1}),
    });

    const plugin = new HeatPlugin(map);

    return {plugin, heatLayer, leafletMap, panes, listeners};
}

beforeEach(() => {
    global.c = {map: {heatmapSettings: {}}};
    global.$ = {...global.$, extend: (target, ...sources) => Object.assign(target, ...sources)};
});

describe('HeatPlugin.addToMap', () => {
    it('addToMap_givenEnabled_addsLayer', () => {
        const {plugin, heatLayer, leafletMap} = createHeatPlugin();

        plugin.addToMap();

        expect(global.L.heatLayer).toHaveBeenCalledTimes(1);
        expect(heatLayer.addTo).toHaveBeenCalledWith(leafletMap);
        expect(plugin.heatLayer).toBe(heatLayer);
    });

    it('addToMap_givenDataArrivedWhileDeferred_reAppliesFloorData', () => {
        const {plugin, heatLayer} = createHeatPlugin();

        // Simulate floor data that arrived (and was stored) while the map deferred plugin loading.
        plugin.rawLatLngsByFloorId[1] = [[1, 2, 3]];

        plugin.addToMap();

        expect(heatLayer.setLatLngs).toHaveBeenCalledWith([[1, 2, 3]]);
    });
});

describe('HeatPlugin render order (show on top)', () => {
    it('constructor_givenDefaultHeatmapShowOnTopOption_seedsShowOnTop', () => {
        // The initial signal is missed (map applies settings before plugins register), so the
        // persisted setting must come from the map options at construction time.
        const {plugin} = createHeatPlugin({defaultHeatmapShowOnTop: true});

        expect(plugin.showOnTop).toBe(true);

        plugin.addToMap();
        expect(global.L.heatLayer).toHaveBeenCalledWith([], expect.objectContaining({pane: 'tooltipPane'}));
    });

    it('applyShowOnTop_givenShowOnTopEnabled_movesCanvasToTooltipPane', () => {
        const {plugin, heatLayer, panes, listeners} = createHeatPlugin();
        plugin.addToMap();

        listeners['heatmapshowontop:changed']({data: {onTop: true}});

        expect(heatLayer.options.pane).toBe('tooltipPane');
        expect(panes.tooltipPane.appended).toBe(heatLayer._canvas);
        expect(panes.overlayPane.appended).toBeNull();
    });

    it('applyShowOnTop_givenShowOnTopDisabled_movesCanvasToOverlayPane', () => {
        const {plugin, heatLayer, panes, listeners} = createHeatPlugin();
        plugin.showOnTop = true;
        plugin.addToMap();

        listeners['heatmapshowontop:changed']({data: {onTop: false}});

        expect(heatLayer.options.pane).toBe('overlayPane');
        expect(panes.overlayPane.appended).toBe(heatLayer._canvas);
    });

    it('applyShowOnTop_givenNullHeatLayer_doesNotThrow', () => {
        const {plugin, panes} = createHeatPlugin();

        expect(plugin.heatLayer).toBeNull();
        expect(() => plugin._applyShowOnTop()).not.toThrow();
        expect(panes.tooltipPane.appended).toBeNull();
        expect(panes.overlayPane.appended).toBeNull();
    });
});

describe('HeatPlugin null-layer guards', () => {
    it('toggle_givenNullHeatLayer_doesNotThrow', () => {
        const {plugin} = createHeatPlugin();

        expect(plugin.heatLayer).toBeNull();
        expect(() => plugin.toggle(true)).not.toThrow();
    });

    it('setOptions_givenNullHeatLayer_doesNotThrow', () => {
        const {plugin} = createHeatPlugin();

        expect(plugin.heatLayer).toBeNull();
        expect(() => plugin.setOptions({radius: 10})).not.toThrow();
    });
});
