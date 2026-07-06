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
function createHeatPlugin() {
    const heatLayer = {
        addTo: vi.fn(),
        setLatLngs: vi.fn(),
        setOptions: vi.fn(),
    };

    global.L.heatLayer = vi.fn(() => heatLayer);

    const leafletMap = {
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

    return {plugin, heatLayer, leafletMap};
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
