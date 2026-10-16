// Follows the global-script model recipe documented at the top of killzone.test.js: define the globals
// dungeontransport.js touches at LOAD time before require()-ing it, then hand the class fake collaborators
// instead of building a real DungeonMap.

global.L = {
    Draw: {
        Marker: {extend: () => function () {}},
        Polyline: {extend: () => function () {}},
        Feature: {prototype: {initialize() {}}},
    },
    polyline: (latLngs, options) => ({type: 'polyline', latLngs, options}),
    polylineDecorator: (polyline, options) => ({type: 'decorator', polyline, options}),
    featureGroup: (layers) => ({
        type: 'featureGroup',
        layers,
        addTo(map) {
            map.addLayer(this);
            return this;
        },
    }),
    Symbol: {arrowHead: (options) => ({type: 'arrowHead', options})},
};
global.LeafletIconUnknown = {};
global.$.extend = Object.assign;
global.c = {
    map: {
        dungeontransport: {
            connectionPolylineOptions: {color: '#7f3fbf', opacity: 0.4},
            connectionPolylineMouseoverOptions: {opacity: 1},
        },
    },
};
global.MapContextMappingVersionEdit = class MapContextMappingVersionEdit {
};

// Stands in for Icon, providing only what DungeonTransport calls on `super`/`this`.
global.Icon = class Icon {
    constructor(map, layer = null, options = {}) {
        this.map = map;
        this.layer = layer;
        this.options = options;
        this.id = 0;
        this.comment = null;
        this.map_icon_type = {id: 1, name: 'Portal'};
        this.visible = true;
    }

    register(names, listener, fn) {
        this.handlers = this.handlers ?? {};
        for (const name of [].concat(names)) {
            (this.handlers[name] = this.handlers[name] ?? []).push(fn);
        }
    }

    signal(name) {
        (this.handlers?.[name] ?? []).forEach((fn) => fn());
    }

    unregister() {
    }

    onLayerInit() {
    }

    isVisible() {
        return this.visible;
    }

    getDisplayText() {
        return this.comment ?? this.map_icon_type.name;
    }

    setMapIconType(mapIconType) {
        this.map_icon_type_id = mapIconType.id;
        this.map_icon_type = mapIconType;
    }

    _rebuildDecorator() {
        this.rebuildDecoratorCount = (this.rebuildDecoratorCount ?? 0) + 1;
    }

    cleanup() {
    }
};

const {DungeonTransport} = require('./dungeontransport');

/**
 * A fake layer with a fixed position.
 */
function makeFakeLayer(lat, lng) {
    return {
        getLatLng: () => ({lat, lng}),
    };
}

/**
 * A fake global state viewing the given floor, recording floor switches.
 */
function makeFakeState(currentFloorId, facade = false) {
    return {
        floorSwitches: [],
        register() {
        },
        unregister() {
        },
        getCurrentFloor: () => ({id: currentFloorId, facade}),
        getMapContext: () => ({
            getFloorById: (id) => ({id, name: `floor_${id}`}),
            getMapIconTypeByKey: (key) => ({id: 60, key, name: 'Portal'}),
        }),
        setFloorId(floorId, center) {
            this.floorSwitches.push({floorId, center});
        },
    };
}

/**
 * Builds transports on one fake map. Each spec is {id, floorId, linkedId, lat, lng, pathVertices}.
 */
function makeTransports(specs) {
    const transportsById = {};
    const pans = [];
    const mapLayers = [];
    const mapObjectGroup = {
        shown: true,
        // MapObjectGroup keys its objects by a string key, not by array index
        get objects() {
            return Object.fromEntries(Object.entries(transportsById).map(([id, transport]) => [`id-${id}`, transport]));
        },
        findMapObjectById: (id) => transportsById[id] ?? null,
        isShown() {
            return this.shown;
        },
        register() {
        },
        unregister() {
        },
    };
    const map = {
        leafletMap: {
            panTo: (latLng) => pans.push(latLng),
            addLayer: (layer) => mapLayers.push(layer),
            removeLayer: (layer) => mapLayers.splice(mapLayers.indexOf(layer), 1),
        },
        mapObjectGroupManager: {
            getDungeonTransportMapObjectGroup: () => mapObjectGroup,
        },
    };

    for (const spec of specs) {
        const transport = new DungeonTransport(map, makeFakeLayer(spec.lat ?? 0, spec.lng ?? 0));
        transport.id = spec.id;
        transport.floor_id = spec.floorId ?? 1;
        transport.linked_dungeon_transport_id = spec.linkedId ?? null;
        transport.path_vertices_json = spec.pathVertices ? JSON.stringify(spec.pathVertices) : null;
        transportsById[spec.id] = transport;
    }

    return {transportsById, pans, mapLayers, mapObjectGroup};
}

/**
 * @returns {{lat: Number, lng: Number, floor_id: Number}}
 */
function vertex(lat, floorId) {
    return {lat, lng: -lat, floor_id: floorId};
}

describe('DungeonTransport', () => {
    test('travel_givenPartnerOnTheCurrentFloor_pansToThePartner', () => {
        // Arrange
        const state = makeFakeState(1);
        global.getState = () => state;
        const {transportsById, pans} = makeTransports([
            {id: 1, floorId: 1, linkedId: 2},
            {id: 2, floorId: 1, lat: -50, lng: 70},
        ]);

        // Act
        transportsById[1].travel();

        // Assert
        expect(pans).toEqual([{lat: -50, lng: 70}]);
        expect(state.floorSwitches).toEqual([]);
    });

    test('travel_givenPartnerOnAnotherFloor_switchesToThatFloorCenteredOnThePartner', () => {
        // Arrange
        const state = makeFakeState(1);
        global.getState = () => state;
        const {transportsById, pans} = makeTransports([
            {id: 1, floorId: 1, linkedId: 2},
            {id: 2, floorId: 3, lat: -50, lng: 70},
        ]);

        // Act
        transportsById[1].travel();

        // Assert
        expect(state.floorSwitches).toEqual([{floorId: 3, center: [-50, 70]}]);
        expect(pans).toEqual([]);
    });

    test('getDecorator_givenTwoWayPair_onlyTheLowerIdDrawsAPlainLine', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const {transportsById} = makeTransports([
            {id: 1, linkedId: 2, lat: -10, lng: 20},
            {id: 2, linkedId: 1, lat: -30, lng: 40},
        ]);

        // Act
        const lowerDecorator = transportsById[1]._getDecorator();
        const higherDecorator = transportsById[2]._getDecorator();

        // Assert
        expect(higherDecorator).toBeNull();
        expect(lowerDecorator.layers).toHaveLength(1);
        expect(lowerDecorator.layers[0].latLngs).toEqual([{lat: -10, lng: 20}, {lat: -30, lng: 40}]);
    });

    test('getDecorator_givenOneWayLink_drawsALineWithAnArrowheadFromTheHigherId', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const {transportsById} = makeTransports([
            {id: 1, linkedId: null},
            {id: 2, linkedId: 1, lat: -30, lng: 40},
        ]);

        // Act
        const decorator = transportsById[2]._getDecorator();

        // Assert
        expect(decorator.layers.map((layer) => layer.type)).toEqual(['polyline', 'decorator']);
        expect(decorator.layers[0].latLngs).toEqual([{lat: -30, lng: 40}, {lat: 0, lng: 0}]);
    });

    test('getDecorator_givenPartnerNotVisible_drawsNothing', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const {transportsById} = makeTransports([
            {id: 1, linkedId: 2},
            {id: 2, linkedId: 1},
        ]);
        transportsById[2].visible = false;

        // Act
        const decorator = transportsById[1]._getDecorator();

        // Assert
        expect(decorator).toBeNull();
    });

    test('shown_givenTheHigherIdOfATwoWayPairShownLast_rebuildsTheLowerIdsLine', () => {
        // Arrange - the lower id was shown first, while its partner was still hidden
        global.getState = () => makeFakeState(1);
        const {transportsById} = makeTransports([
            {id: 1, linkedId: 2},
            {id: 2, linkedId: 1},
        ]);
        const rebuildsBefore = transportsById[1].rebuildDecoratorCount ?? 0;

        // Act
        transportsById[2].signal('shown');

        // Assert
        expect(transportsById[1].rebuildDecoratorCount).toBe(rebuildsBefore + 1);
    });

    test('deleted_givenTheEndThatDoesNotDrawThePairsPath_removesThePathAndTheLine', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const {transportsById, mapLayers} = makeTransports([
            {id: 1, linkedId: 2, pathVertices: [vertex(1, 1), vertex(2, 1)]},
            {id: 2, linkedId: 1},
        ]);
        transportsById[1]._rebuildPathLayer();
        const layerCountBefore = mapLayers.length;

        // Act
        transportsById[2]._deleted = true;
        transportsById[2].signal('object:deleted');

        // Assert
        expect(layerCountBefore).toBe(1);
        expect(mapLayers).toHaveLength(0);
        expect(transportsById[1].pathLayer).toBeNull();
        expect(transportsById[1]._getDecorator()).toBeNull();
    });

    test('getDisplayText_givenPartnerOnAnotherFloor_namesThatFloor', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const {transportsById} = makeTransports([
            {id: 1, floorId: 1, linkedId: 2},
            {id: 2, floorId: 3},
        ]);

        // Act
        const text = transportsById[1].getDisplayText();

        // Assert
        expect(text).toBe('js.dungeontransport_to_floor_label');
    });

    test('getLinkedDungeonTransportSelectValues_givenOtherTransports_listsEveryOtherSavedTransport', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const {transportsById} = makeTransports([
            {id: 1, floorId: 1},
            {id: 2, floorId: 3},
            {id: 3, floorId: 1},
        ]);

        // Act
        const values = transportsById[1]._getLinkedDungeonTransportSelectValues();

        // Assert
        expect(values.map((value) => value.id)).toEqual([2, 3]);
    });

    test('getPathLatLngRuns_givenFacade_returnsTheWholePathAsOneRun', () => {
        // Arrange
        global.getState = () => makeFakeState(9, true);
        const {transportsById} = makeTransports([
            {id: 1, linkedId: 2, pathVertices: [vertex(1, 1), vertex(2, 2), vertex(3, 3)]},
            {id: 2},
        ]);

        // Act
        const runs = transportsById[1].getPathLatLngRuns({id: 9, facade: true});

        // Assert
        expect(runs).toEqual([[[1, -1], [2, -2], [3, -3]]]);
    });

    test('getPathLatLngRuns_givenRegularFloor_returnsOnlyTheRunsOfConsecutiveVerticesOnThatFloor', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const {transportsById} = makeTransports([
            {
                id: 1, linkedId: 2, pathVertices: [
                    vertex(1, 1), vertex(2, 1), vertex(3, 2), vertex(4, 1), vertex(5, 2), vertex(6, 1), vertex(7, 1), vertex(8, 1),
                ],
            },
            {id: 2},
        ]);

        // Act
        const runs = transportsById[1].getPathLatLngRuns({id: 1, facade: false});

        // Assert - the lone vertex 4 cannot form a line on its own
        expect(runs).toEqual([[[1, -1], [2, -2]], [[6, -6], [7, -7], [8, -8]]]);
    });

    test('getPairPathVertices_givenTwoWayPartnerHoldsThePath_returnsItReversed', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const {transportsById} = makeTransports([
            {id: 1, linkedId: 2},
            {id: 2, linkedId: 1, pathVertices: [vertex(1, 1), vertex(2, 1)]},
        ]);

        // Act
        const pathVertices = transportsById[1].getPairPathVertices();

        // Assert
        expect(pathVertices).toEqual([vertex(2, 1), vertex(1, 1)]);
    });

    test('getPairPathVertices_givenOneWayPartnerWithAPathElsewhere_returnsNoPath', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const {transportsById} = makeTransports([
            {id: 1, linkedId: 2},
            {id: 2, linkedId: 3, pathVertices: [vertex(1, 1), vertex(2, 1)]},
            {id: 3},
        ]);

        // Act
        const pathVertices = transportsById[1].getPairPathVertices();

        // Assert
        expect(pathVertices).toEqual([]);
    });

    test('getDecorator_givenPairWithAPath_drawsNoStraightLine', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const {transportsById} = makeTransports([
            {id: 1, linkedId: 2},
            {id: 2, linkedId: 1, pathVertices: [vertex(1, 1), vertex(2, 1)]},
        ]);

        // Act
        const decorator = transportsById[1]._getDecorator();

        // Assert
        expect(decorator).toBeNull();
    });

    test('rebuildPathLayer_givenPathCrossingAFloorNeitherEndIsOn_drawsItsRunOnThatFloor', () => {
        // Arrange
        global.getState = () => makeFakeState(2);
        const {transportsById, mapLayers} = makeTransports([
            {id: 1, floorId: 1, linkedId: 2, pathVertices: [vertex(1, 1), vertex(2, 2), vertex(3, 2), vertex(4, 3)]},
            {id: 2, floorId: 3, linkedId: 1},
        ]);
        transportsById[1].visible = false;
        transportsById[2].visible = false;

        // Act
        transportsById[1]._rebuildPathLayer();

        // Assert
        expect(mapLayers).toHaveLength(1);
        expect(mapLayers[0].layers.map((layer) => layer.type)).toEqual(['polyline']);
        expect(mapLayers[0].layers[0].latLngs).toEqual([[2, -2], [3, -3]]);
    });

    test('rebuildPathLayer_givenOneWayLink_drawsAnArrowheadOnEachRun', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const {transportsById, mapLayers} = makeTransports([
            {id: 1, linkedId: 2, pathVertices: [vertex(1, 1), vertex(2, 1), vertex(3, 2), vertex(4, 1), vertex(5, 1)]},
            {id: 2},
        ]);

        // Act
        transportsById[1]._rebuildPathLayer();

        // Assert
        expect(mapLayers[0].layers.map((layer) => layer.type)).toEqual(['polyline', 'decorator', 'polyline', 'decorator']);
    });

    test('rebuildPathLayer_givenTheHigherIdOfATwoWayPair_drawsNothing', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const {transportsById, mapLayers} = makeTransports([
            {id: 1, linkedId: 2},
            {id: 2, linkedId: 1, pathVertices: [vertex(1, 1), vertex(2, 1)]},
        ]);

        // Act
        transportsById[2]._rebuildPathLayer();
        const higherIdLayerCount = mapLayers.length;
        transportsById[1]._rebuildPathLayer();

        // Assert
        expect(higherIdLayerCount).toBe(0);
        expect(mapLayers).toHaveLength(1);
        expect(mapLayers[0].layers[0].latLngs).toEqual([[2, -2], [1, -1]]);
    });

    test('rebuildPathLayer_givenHiddenMapObjectGroup_removesThePath', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const {transportsById, mapLayers, mapObjectGroup} = makeTransports([
            {id: 1, linkedId: 2, pathVertices: [vertex(1, 1), vertex(2, 1)]},
            {id: 2},
        ]);
        transportsById[1]._rebuildPathLayer();
        const shownLayerCount = mapLayers.length;
        mapObjectGroup.shown = false;

        // Act
        transportsById[1]._rebuildPathLayer();

        // Assert
        expect(shownLayerCount).toBe(1);
        expect(mapLayers).toHaveLength(0);
    });

    test('constructor_givenNewTransport_defaultsToTheBluePortalIcon', () => {
        // Arrange
        global.getState = () => makeFakeState(1);

        // Act
        const {transportsById} = makeTransports([{id: 1}]);

        // Assert
        expect(transportsById[1].map_icon_type_id).toBe(60);
    });
});
