// Global stubs ($, L, lang, getState) come from the shared Vitest setup file (resources/assets/js/test/setup.js).
// The plugin resolves floors through the real CoordinatesService, so its dependencies are loaded as well.

const {rotateLatLng, roundHalfAwayFromZero} = require('../util');
global.rotateLatLng = rotateLatLng;
global.roundHalfAwayFromZero = roundHalfAwayFromZero;

const {MAP_MAX_LAT, MAP_MAX_LNG, MAP_SIZE, MAP_ASPECT_RATIO} = require('../constants');
global.MAP_MAX_LAT = MAP_MAX_LAT;
global.MAP_MAX_LNG = MAP_MAX_LNG;
global.MAP_SIZE = MAP_SIZE;
global.MAP_ASPECT_RATIO = MAP_ASPECT_RATIO;

global.LatLng = require('../structs/latlng');
global.IngameXY = require('../structs/ingamexy');

global.getFloorUnionMapObjectGroup = () => null;
global.getFloorUnionAreaMapObjectGroup = () => null;

global.CoordinatesService = require('../coordinates/coordinatesservice');

global.MapPlugin = class MapPlugin {
    constructor(map) {
        this.map = map;
    }
};

const FacadeFloorNavigationPlugin = require('./facadefloornavigationplugin');

const FACADE_FLOOR = {id: 1, name: 'floor.facade', facade: 1, facade_navigation: 1};
const TARGET_FLOOR = {
    id: 2,
    name: 'floor.target',
    facade: 0,
    facade_navigation: 0,
    ingame_min_x: 100,
    ingame_max_x: 1000,
    ingame_min_y: 100,
    ingame_max_y: 1000
};

/**
 * A floor union whose area covers the top left quadrant of the facade.
 *
 * @returns {Object}
 */
function createFloorUnion() {
    return {
        id: 10,
        floor_id: FACADE_FLOOR.id,
        target_floor_id: TARGET_FLOOR.id,
        lat: -64,
        lng: 96,
        size: 128,
        rotation: 0,
        floor_union_areas: [{
            id: 100,
            floor_union_id: 10,
            vertices_json: JSON.stringify([
                {lat: 0, lng: 0},
                {lat: 0, lng: 192},
                {lat: -128, lng: 192},
                {lat: -128, lng: 0}
            ])
        }]
    };
}

/**
 * @param options {{currentFloor?: Object, isMapAdmin?: Boolean, facadeEnabled?: Boolean, mapState?: Object|null, floorUnions?: Object[]}}
 */
function createPlugin(options = {}) {
    const currentFloor = options.currentFloor ?? FACADE_FLOOR;
    const floors = [FACADE_FLOOR, TARGET_FLOOR];
    const floorUnions = options.floorUnions ?? [createFloorUnion()];

    const mapContext = {
        getMappingVersion: () => ({facade_enabled: true}),
        getFloorUnions: () => floorUnions,
        getFloorUnionAreas: () => [],
        getFloorById: (floorId) => floors.find(floor => floor.id === floorId) ?? false
    };

    const state = {
        getMapContext: () => mapContext,
        getCurrentFloor: () => currentFloor,
        isMapAdmin: () => options.isMapAdmin ?? false,
        isCurrentDungeonFacadeEnabled: () => options.facadeEnabled ?? true,
        isVisibleFloorId: (floorId) => floors.some(floor => floor.id === floorId),
        setFloorId: vi.fn(),
    };
    global.getState = () => state;

    const layerGroup = {addTo: vi.fn(() => layerGroup), clearLayers: vi.fn(), addLayer: vi.fn()};
    global.L.layerGroup = vi.fn(() => layerGroup);
    global.L.polygon = vi.fn(() => ({bindTooltip: vi.fn()}));

    const containerClassList = {toggle: vi.fn()};
    const leafletMap = {
        on: vi.fn(() => leafletMap),
        off: vi.fn(() => leafletMap),
        removeLayer: vi.fn(),
        getContainer: () => ({classList: containerClassList}),
    };

    const map = {
        leafletMap,
        getMapState: () => options.mapState ?? null,
    };

    return {plugin: new FacadeFloorNavigationPlugin(map), state, leafletMap, layerGroup, containerClassList};
}

beforeEach(() => {
    global.c = {map: {facadefloornavigation: {polygonOptions: {}, tooltipOptions: {}}}};
});

describe('FacadeFloorNavigationPlugin.isActive', () => {
    it('isActive_givenFacadeFloorWithFacadeNavigation_returnsTrue', () => {
        const {plugin} = createPlugin();

        expect(plugin.isActive()).toBe(true);
    });

    it('isActive_givenFacadeFloorWithoutFacadeNavigation_returnsFalse', () => {
        const {plugin} = createPlugin({currentFloor: {...FACADE_FLOOR, facade_navigation: 0}});

        expect(plugin.isActive()).toBe(false);
    });

    it('isActive_givenNonFacadeFloor_returnsFalse', () => {
        const {plugin} = createPlugin({currentFloor: {...TARGET_FLOOR, facade_navigation: 1}});

        expect(plugin.isActive()).toBe(false);
    });

    it('isActive_givenSplitFloorsStyle_returnsFalse', () => {
        const {plugin} = createPlugin({facadeEnabled: false});

        expect(plugin.isActive()).toBe(false);
    });

    it('isActive_givenMapAdmin_returnsFalse', () => {
        const {plugin} = createPlugin({isMapAdmin: true});

        expect(plugin.isActive()).toBe(false);
    });
});

describe('FacadeFloorNavigationPlugin.addToMap', () => {
    it('addToMap_givenInactive_registersNoHandlers', () => {
        const {plugin, leafletMap} = createPlugin({currentFloor: TARGET_FLOOR});

        plugin.addToMap();

        expect(leafletMap.on).not.toHaveBeenCalled();
        expect(plugin.layerGroup).toBeNull();
    });

    it('addToMap_givenActive_registersMouseHandlers', () => {
        const {plugin, leafletMap, layerGroup} = createPlugin();

        plugin.addToMap();

        expect(leafletMap.on).toHaveBeenCalledWith('mousemove', plugin._onMouseMoveRef);
        expect(leafletMap.on).toHaveBeenCalledWith('mouseout', plugin._onMouseOutRef);
        expect(leafletMap.on).toHaveBeenCalledWith('click', plugin._onClickRef);
        expect(plugin.layerGroup).toBe(layerGroup);
        expect(layerGroup.addTo).toHaveBeenCalledWith(leafletMap);
    });
});

describe('FacadeFloorNavigationPlugin.removeFromMap', () => {
    it('removeFromMap_givenAddedToMap_unregistersHandlersAndRemovesTheHighlightLayer', () => {
        const {plugin, leafletMap, layerGroup} = createPlugin();
        plugin.addToMap();
        plugin._onLeafletMapMouseMove({latlng: {lat: -64, lng: 96}});

        plugin.removeFromMap();

        expect(leafletMap.off).toHaveBeenCalledWith('mousemove', plugin._onMouseMoveRef);
        expect(leafletMap.off).toHaveBeenCalledWith('mouseout', plugin._onMouseOutRef);
        expect(leafletMap.off).toHaveBeenCalledWith('click', plugin._onClickRef);
        expect(leafletMap.removeLayer).toHaveBeenCalledWith(layerGroup);
        expect(plugin.layerGroup).toBeNull();
        expect(plugin.hoveredFloorUnion).toBeNull();
    });
});

describe('FacadeFloorNavigationPlugin mouse handling', () => {
    it('mouseMove_givenPointInsideFloorUnionArea_highlightsFloorUnion', () => {
        const {plugin, layerGroup, containerClassList} = createPlugin();
        plugin.addToMap();

        plugin._onLeafletMapMouseMove({latlng: {lat: -64, lng: 96}});

        expect(plugin.hoveredFloorUnion?.id).toBe(10);
        expect(layerGroup.addLayer).toHaveBeenCalledTimes(1);
        const highlight = layerGroup.addLayer.mock.calls[0][0];
        expect(highlight.bindTooltip).toHaveBeenCalledWith('floor.target', c.map.facadefloornavigation.tooltipOptions);
        expect(containerClassList.toggle).toHaveBeenLastCalledWith('facade_floor_navigation_hover', true);
    });

    it('mouseMove_givenStringVertices_highlightsFloorUnion', () => {
        const floorUnion = createFloorUnion();
        floorUnion.floor_union_areas[0].vertices_json = JSON.stringify([
            {lat: '-10', lng: '0'},
            {lat: '-10', lng: '192'},
            {lat: '-128', lng: '192'},
            {lat: '-128', lng: '0'}
        ]);
        const {plugin} = createPlugin({floorUnions: [floorUnion]});
        plugin.addToMap();

        plugin._onLeafletMapMouseMove({latlng: {lat: -64, lng: 96}});

        expect(plugin.hoveredFloorUnion?.id).toBe(10);
    });

    it('mouseMove_givenPointOutsideFloorUnionAreas_clearsHighlight', () => {
        const {plugin, layerGroup, containerClassList} = createPlugin();
        plugin.addToMap();
        plugin._onLeafletMapMouseMove({latlng: {lat: -64, lng: 96}});
        layerGroup.clearLayers.mockClear();

        plugin._onLeafletMapMouseMove({latlng: {lat: -200, lng: 300}});

        expect(plugin.hoveredFloorUnion).toBeNull();
        expect(layerGroup.clearLayers).toHaveBeenCalledOnce();
        expect(containerClassList.toggle).toHaveBeenLastCalledWith('facade_floor_navigation_hover', false);
    });

    it('mouseMove_givenActiveMapState_clearsHighlight', () => {
        const mapState = {state: 'active'};
        const {plugin} = createPlugin({mapState: null});
        plugin.addToMap();
        plugin._onLeafletMapMouseMove({latlng: {lat: -64, lng: 96}});
        const hoveredWithoutMapState = plugin.hoveredFloorUnion?.id;
        plugin.map.getMapState = () => mapState;

        plugin._onLeafletMapMouseMove({latlng: {lat: -64, lng: 96}});

        expect(hoveredWithoutMapState).toBe(10);
        expect(plugin.hoveredFloorUnion).toBeNull();
    });

    it('mouseOut_givenHighlightedFloorUnion_clearsHighlight', () => {
        const {plugin} = createPlugin();
        plugin.addToMap();
        plugin._onLeafletMapMouseMove({latlng: {lat: -64, lng: 96}});
        const hoveredBeforeMouseOut = plugin.hoveredFloorUnion?.id;

        plugin._onLeafletMapMouseOut();

        expect(hoveredBeforeMouseOut).toBe(10);
        expect(plugin.hoveredFloorUnion).toBeNull();
    });

    it('mouseMove_givenFloorUnionWithoutTargetFloor_highlightsNothing', () => {
        const floorUnion = {...createFloorUnion(), target_floor_id: null};
        const {plugin} = createPlugin({floorUnions: [floorUnion]});
        plugin.addToMap();

        plugin._onLeafletMapMouseMove({latlng: {lat: -64, lng: 96}});

        expect(plugin.hoveredFloorUnion).toBeNull();
    });

    it('mouseMove_givenFloorUnionOfAnotherFloor_highlightsNothing', () => {
        const floorUnion = {...createFloorUnion(), floor_id: TARGET_FLOOR.id};
        const {plugin} = createPlugin({floorUnions: [floorUnion]});
        plugin.addToMap();

        plugin._onLeafletMapMouseMove({latlng: {lat: -64, lng: 96}});

        expect(plugin.hoveredFloorUnion).toBeNull();
    });

    it('click_givenPointInsideFloorUnionArea_setsTargetFloor', () => {
        const {plugin, state} = createPlugin();
        plugin.addToMap();

        plugin._onLeafletMapClick({latlng: {lat: -64, lng: 96}});

        expect(state.setFloorId).toHaveBeenCalledWith(TARGET_FLOOR.id);
    });

    it('click_givenPointOutsideFloorUnionAreas_doesNothing', () => {
        const {plugin, state} = createPlugin();
        plugin.addToMap();

        plugin._onLeafletMapClick({latlng: {lat: -200, lng: 300}});

        expect(state.setFloorId).not.toHaveBeenCalled();
    });

    it('click_givenTargetFloorNotVisible_doesNothing', () => {
        const {plugin, state} = createPlugin();
        state.isVisibleFloorId = (floorId) => floorId !== TARGET_FLOOR.id;
        plugin.addToMap();

        plugin._onLeafletMapClick({latlng: {lat: -64, lng: 96}});

        expect(state.setFloorId).not.toHaveBeenCalled();
    });

    it('click_givenActiveMapState_doesNothing', () => {
        const {plugin, state} = createPlugin({mapState: {}});
        plugin.addToMap();

        plugin._onLeafletMapClick({latlng: {lat: -64, lng: 96}});

        expect(state.setFloorId).not.toHaveBeenCalled();
    });
});
