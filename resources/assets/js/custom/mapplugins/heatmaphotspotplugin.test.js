// Global stubs ($, L, getState, lang) come from the shared Vitest setup file
// (resources/assets/js/test/setup.js); the plugin's base class, map context class and templates are
// defined here before requiring the source.

global.MapPlugin = class MapPlugin {
    constructor(map) {
        this.map = map;
    }
};

global.MapContextDungeonExplore = class MapContextDungeonExplore {
};

const HeatmapHotspotPlugin = require('./heatmaphotspotplugin');

const ASSETS_BASE_URL = 'https://assets.example';

/**
 * @param npcs {Object[]}
 * @param deaths {Number}
 * @returns {Object} A hotspot shaped like one entry of the response's hotspots[].hotspots
 */
function makeHotspot(npcs, deaths = 100) {
    return {
        lat_lngs: [{lat: -10, lng: 10}, {lat: -10, lng: 20}, {lat: -20, lng: 15}],
        center: {lat: -13.33, lng: 15},
        deaths: deaths,
        npcs: npcs,
    };
}

const knownNpc = {npc_id: 3636, name: 'Deviate Ravager', portrait_url: 'images/enemyportraits/3636.png', deaths: 60, percentage: 60};
const unknownNpc = {npc_id: 448, name: null, portrait_url: null, deaths: 40, percentage: 40};

/**
 * Builds a plugin on a mock Leaflet map; the polygons and markers it adds are recorded per call.
 */
function createPlugin({currentFloorId = 1, isExplore = true} = {}) {
    const added = [];
    const layerGroup = {
        addTo: vi.fn(function () {
            return this;
        }),
        clearLayers: vi.fn(() => added.splice(0, added.length)),
        addLayer: vi.fn(layer => added.push(layer)),
    };

    const makeLayer = (type, args) => ({
        type,
        args,
        bindTooltip: vi.fn(),
        bindPopup: vi.fn(),
        on: vi.fn(),
    });

    global.L = {
        layerGroup: vi.fn(() => layerGroup),
        polygon: vi.fn((...args) => makeLayer('polygon', args)),
        marker: vi.fn((...args) => makeLayer('marker', args)),
        divIcon: vi.fn(options => options),
    };

    global.c = {map: {heatmapHotspot: {iconSize: 32, polygonOptions: {color: '#ffffff'}}}};
    global.getHandlebarsDefaultVariables = () => ({});
    global.Handlebars = {
        templates: {
            map_heatmap_hotspot_breakdown_template: data => `breakdown:${data.npcs.map(npc => npc.name).join(',')}`,
            map_enemy_visual_enemy_portrait_template: data => `portrait:${data.enemy_portrait_url}`,
        },
    };
    global.$ = {...global.$, extend: (target, ...sources) => Object.assign(target, ...sources)};

    const listeners = {};
    const state = {currentFloorId};
    global.getState = () => ({
        register: (event, context, callback) => {
            listeners[event] = callback;
        },
        getMapContext: () => (isExplore ? new global.MapContextDungeonExplore() : {}),
        getCurrentFloor: () => ({id: state.currentFloorId}),
    });

    const map = {
        leafletMap: {removeLayer: vi.fn()},
        options: {assetsBaseUrl: ASSETS_BASE_URL},
    };

    const plugin = new HeatmapHotspotPlugin(map);

    return {plugin, added, layerGroup, listeners, state};
}

describe('HeatmapHotspotPlugin.setHotspotsPerFloor', () => {
    it('setHotspotsPerFloor_givenHotspotsOnTheCurrentFloor_drawsAPolygonAndTopNpcPortraitPerHotspot', () => {
        const {plugin, added} = createPlugin();
        plugin.addToMap();
        const hotspot = makeHotspot([unknownNpc, knownNpc]);

        plugin.setHotspotsPerFloor([{floor_id: 1, hotspots: [hotspot]}, {floor_id: 2, hotspots: [makeHotspot([knownNpc])]}]);

        expect(added.map(layer => layer.type)).toEqual(['polygon', 'marker']);
        expect(added[0].args[0]).toBe(hotspot.lat_lngs);
        expect(added[1].args[0]).toBe(hotspot.center);
        expect(added[1].args[1].icon.html).toContain(`portrait:${ASSETS_BASE_URL}/images/enemyportraits/unknown.png`);
        expect(added[1].args[1].icon.html).toContain('enemy_icon_npc_enemy_portrait');
        for (const layer of added) {
            expect(layer.bindTooltip).toHaveBeenCalledWith('breakdown:js.heatmap_hotspot_unknown_npc_label,Deviate Ravager', expect.anything());
            expect(layer.bindPopup).toHaveBeenCalledWith('breakdown:js.heatmap_hotspot_unknown_npc_label,Deviate Ravager', expect.anything());
        }
    });

    it('setHotspotsPerFloor_givenNoHotspotsField_removesThePreviousHotspots', () => {
        const {plugin, added} = createPlugin();
        plugin.addToMap();
        plugin.setHotspotsPerFloor([{floor_id: 1, hotspots: [makeHotspot([knownNpc])]}]);
        expect(added).toHaveLength(2);

        plugin.setHotspotsPerFloor(undefined);

        expect(added).toHaveLength(0);
        expect(plugin.hotspotsByFloorId).toEqual({});
    });

    it('setHotspotsPerFloor_givenDataBeforeTheMapIsSized_drawsItOnceAddedToTheMap', () => {
        const {plugin, added} = createPlugin();
        plugin.setHotspotsPerFloor([{floor_id: 1, hotspots: [makeHotspot([knownNpc])]}]);
        expect(added).toHaveLength(0);

        plugin.addToMap();

        expect(added.map(layer => layer.type)).toEqual(['polygon', 'marker']);
    });
});

describe('HeatmapHotspotPlugin floors and visibility', () => {
    it('floorChanged_givenHotspotsOnTheNewFloor_drawsThoseInstead', () => {
        const {plugin, added, listeners} = createPlugin();
        plugin.addToMap();
        const otherFloorHotspot = makeHotspot([knownNpc]);
        plugin.setHotspotsPerFloor([
            {floor_id: 1, hotspots: [makeHotspot([unknownNpc]), makeHotspot([unknownNpc])]},
            {floor_id: 2, hotspots: [otherFloorHotspot]},
        ]);
        expect(added).toHaveLength(4);

        listeners['floorid:changed']({data: {floorId: 2}});

        expect(added).toHaveLength(2);
        expect(added[0].args[0]).toBe(otherFloorHotspot.lat_lngs);
    });

    it('toggle_givenDisabledThenEnabled_hidesAndRestoresTheHotspots', () => {
        const {plugin, added} = createPlugin();
        plugin.addToMap();
        plugin.setHotspotsPerFloor([{floor_id: 1, hotspots: [makeHotspot([knownNpc])]}]);

        plugin.toggle(false);
        const countWhileHidden = added.length;
        plugin.toggle(true);

        expect(countWhileHidden).toBe(0);
        expect(added).toHaveLength(2);
    });

    it('addToMap_givenMapContextIsNotExplore_addsNoLayer', () => {
        const {plugin} = createPlugin({isExplore: false});

        plugin.addToMap();

        expect(global.L.layerGroup).not.toHaveBeenCalled();
        expect(plugin.layerGroup).toBeNull();
    });
});

describe('HeatmapHotspotPlugin.getBreakdownTemplateData', () => {
    it('getBreakdownTemplateData_givenKnownAndUnknownNpcs_namesAndPortraitsEach', () => {
        const {plugin} = createPlugin();
        const originalLang = global.lang;
        global.lang = {get: vi.fn((key, replacements) => `${key}${JSON.stringify(replacements ?? {})}`)};

        let data;
        try {
            data = plugin.getBreakdownTemplateData(makeHotspot([knownNpc, unknownNpc], 100));
        } finally {
            global.lang = originalLang;
        }

        expect(data.title).toBe('js.heatmap_hotspot_breakdown_title{}');
        expect(data.deaths_label).toBe('js.heatmap_hotspot_deaths_label{"count":100}');
        expect(data.npcs).toEqual([
            {name: 'Deviate Ravager', portrait_url: `${ASSETS_BASE_URL}/images/enemyportraits/3636.png`, percentage: 60},
            {name: 'js.heatmap_hotspot_unknown_npc_label{"id":448}', portrait_url: `${ASSETS_BASE_URL}/images/enemyportraits/unknown.png`, percentage: 40},
        ]);
    });
});
