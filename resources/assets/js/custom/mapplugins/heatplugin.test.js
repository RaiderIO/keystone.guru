// Global stubs ($, L, getState) come from the shared Vitest setup file
// (resources/assets/js/test/setup.js). HeatPlugin additionally needs its base class and a
// handful of globals it references at construction time, so those are defined before requiring
// the source. Anything test-specific (heatLayer factory, map size, requestAnimationFrame) is
// overridden per test below.

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
 * Builds a HeatPlugin backed by mock Leaflet/state objects.
 * @param options {{mapSizeX?: Number, containerWidth?: Number}}
 */
function createHeatPlugin({mapSizeX = 100, containerWidth = 100} = {}) {
    const heatLayer = {
        addTo: vi.fn(),
        setLatLngs: vi.fn(),
        setOptions: vi.fn(),
    };

    global.L.heatLayer = vi.fn(() => heatLayer);

    const container = {clientWidth: containerWidth};
    const leafletMap = {
        getSize: () => ({x: mapSizeX, y: 100}),
        getContainer: () => container,
        invalidateSize: vi.fn(),
        addLayer: vi.fn(),
        removeLayer: vi.fn(),
    };

    const map = {
        leafletMap,
        register: vi.fn(),
    };

    global.getState = () => ({
        register: vi.fn(),
        getMapContext: () => new global.MapContextDungeonExplore(),
        getCurrentFloor: () => ({id: 1}),
    });

    const plugin = new HeatPlugin(map);

    return {plugin, heatLayer, leafletMap, container};
}

beforeEach(() => {
    global.c = {map: {heatmapSettings: {}}};
    global.$ = {...global.$, extend: (target, ...sources) => Object.assign(target, ...sources)};
});

describe('HeatPlugin.addToMap', () => {
    it('addToMap_givenZeroWidthContainer_doesNotAddLayerImmediately', () => {
        const {plugin} = createHeatPlugin({mapSizeX: 0, containerWidth: 0});

        expect(() => plugin.addToMap()).not.toThrow();

        expect(global.L.heatLayer).not.toHaveBeenCalled();
        expect(plugin.heatLayer).toBeNull();
    });

    it('addToMap_givenNonZeroWidthContainer_addsLayer', () => {
        const {plugin, heatLayer, leafletMap} = createHeatPlugin({mapSizeX: 100});

        plugin.addToMap();

        expect(global.L.heatLayer).toHaveBeenCalledTimes(1);
        expect(heatLayer.addTo).toHaveBeenCalledWith(leafletMap);
        expect(plugin.heatLayer).toBe(heatLayer);
    });
});

describe('HeatPlugin deferred add', () => {
    let rafCallbacks;

    beforeEach(() => {
        rafCallbacks = [];
        window.requestAnimationFrame = vi.fn((callback) => {
            rafCallbacks.push(callback);
            return rafCallbacks.length;
        });
        window.cancelAnimationFrame = vi.fn();
    });

    it('addToMap_givenZeroWidthThenNonZeroWidth_addsLayerOnceLaidOut', () => {
        const {plugin, heatLayer, leafletMap, container} = createHeatPlugin({mapSizeX: 0, containerWidth: 0});

        plugin.addToMap();

        // Still zero width: the first frame re-schedules instead of adding the layer.
        rafCallbacks.shift()();
        expect(global.L.heatLayer).not.toHaveBeenCalled();

        // The container is laid out; the next frame invalidates the size and adds the layer.
        container.clientWidth = 500;
        rafCallbacks.shift()();

        expect(leafletMap.invalidateSize).toHaveBeenCalledTimes(1);
        expect(global.L.heatLayer).toHaveBeenCalledTimes(1);
        expect(plugin.heatLayer).toBe(heatLayer);
    });

    it('removeFromMap_givenPendingDeferredAdd_cancelsPendingFrame', () => {
        const {plugin} = createHeatPlugin({mapSizeX: 0, containerWidth: 0});

        plugin.addToMap();
        plugin.removeFromMap();

        expect(window.cancelAnimationFrame).toHaveBeenCalledTimes(1);

        // A frame that fires after removal must not add a layer.
        rafCallbacks.shift()();
        expect(global.L.heatLayer).not.toHaveBeenCalled();
    });
});

describe('HeatPlugin null-layer guards', () => {
    it('toggle_givenNullHeatLayer_doesNotThrow', () => {
        const {plugin} = createHeatPlugin({mapSizeX: 0, containerWidth: 0});

        expect(plugin.heatLayer).toBeNull();
        expect(() => plugin.toggle(true)).not.toThrow();
    });

    it('setOptions_givenNullHeatLayer_doesNotThrow', () => {
        const {plugin} = createHeatPlugin({mapSizeX: 0, containerWidth: 0});

        expect(plugin.heatLayer).toBeNull();
        expect(() => plugin.setOptions({radius: 10})).not.toThrow();
    });
});
