// Follows the global-script model recipe documented at the top of killzone.test.js: define the globals
// dungeontransport.js touches at LOAD time before require()-ing it, then hand the class fake collaborators
// instead of building a real DungeonMap.

global.L = {
    Draw: {
        Marker: {extend: () => function () {}},
        Feature: {prototype: {initialize() {}}},
    },
    polyline: (latLngs, options) => ({type: 'polyline', latLngs, options}),
    polylineDecorator: (polyline, options) => ({type: 'decorator', polyline, options}),
    featureGroup: (layers) => ({type: 'featureGroup', layers}),
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

    register() {
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
function makeFakeState(currentFloorId) {
    return {
        floorSwitches: [],
        getCurrentFloor: () => ({id: currentFloorId}),
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
 * Builds transports on one fake map. Each spec is {id, floorId, linkedId, lat, lng}.
 */
function makeTransports(specs) {
    const transportsById = {};
    const pans = [];
    const map = {
        leafletMap: {panTo: (latLng) => pans.push(latLng)},
        mapObjectGroupManager: {
            getDungeonTransportMapObjectGroup: () => ({
                // MapObjectGroup keys its objects by a string key, not by array index
                objects: Object.fromEntries(Object.entries(transportsById).map(([id, transport]) => [`id-${id}`, transport])),
                findMapObjectById: (id) => transportsById[id] ?? null,
            }),
        },
    };

    for (const spec of specs) {
        const transport = new DungeonTransport(map, makeFakeLayer(spec.lat ?? 0, spec.lng ?? 0));
        transport.id = spec.id;
        transport.floor_id = spec.floorId ?? 1;
        transport.linked_dungeon_transport_id = spec.linkedId ?? null;
        transportsById[spec.id] = transport;
    }

    return {transportsById, pans};
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

    test('constructor_givenNewTransport_defaultsToTheBluePortalIcon', () => {
        // Arrange
        global.getState = () => makeFakeState(1);

        // Act
        const {transportsById} = makeTransports([{id: 1}]);

        // Assert
        expect(transportsById[1].map_icon_type_id).toBe(60);
    });
});
