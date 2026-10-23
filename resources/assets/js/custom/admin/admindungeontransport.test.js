// Follows the global-script model recipe documented at the top of models/killzone.test.js. AdminDungeonTransport
// extends the real DungeonTransport here, which itself runs on a stub of Icon, so the pair path logic it relies on
// is the production code.

global.L = {
    Draw: {
        Marker: {extend: () => function () {}},
        Polyline: {extend: () => function () {}},
        Feature: {prototype: {initialize() {}}},
    },
};
global.LeafletIconUnknown = {};
global.MapContextMappingVersionEdit = class MapContextMappingVersionEdit {
};
global.Attribute = class Attribute {
    constructor(options) {
        Object.assign(this, options);
    }
};
global.showErrorNotification = vi.fn();

// Stands in for Icon, providing only what DungeonTransport and AdminDungeonTransport call on `super`/`this`.
global.Icon = class Icon {
    constructor(map, layer = null, options = {}) {
        this.map = map;
        this.layer = layer;
        this.options = options;
        this.id = 0;
        this.saveCount = 0;
        this._cachedAttributes = null;
    }

    register() {
    }

    unregister() {
    }

    setSynced() {
    }

    setMapIconType(mapIconType) {
        this.map_icon_type_id = mapIconType.id;
    }

    _getAttributes() {
        return [];
    }

    save() {
        this.saveCount++;
    }

    onLayerInit() {
    }

    cleanup() {
    }
};

global.DungeonTransport = require('../models/dungeontransport').DungeonTransport;
const {AdminDungeonTransport} = require('./admindungeontransport');

/**
 * A fake global state viewing the given floor.
 */
function makeFakeState(currentFloorId, facade = false) {
    return {
        register() {
        },
        unregister() {
        },
        getCurrentFloor: () => ({id: currentFloorId, facade}),
        getMapContext: () => ({
            getMapIconTypeByKey: (key) => ({id: 60, key}),
        }),
    };
}

/**
 * Builds admin transports on one fake map whose layer points are their lat/lng. Each spec is
 * {id, floorId, linkedId, lat, lng, pathVertices}.
 */
function makeTransports(specs) {
    const transportsById = {};
    const mapObjectGroup = {
        findMapObjectById: (id) => transportsById[id] ?? null,
        register() {
        },
        unregister() {
        },
    };
    const toPoint = (latLng) => ({
        distanceTo: (other) => Math.hypot(latLng.lat - other.latLng.lat, latLng.lng - other.latLng.lng),
        latLng,
    });
    const map = {
        leafletMap: {
            closePopup() {
            },
            latLngToLayerPoint: toPoint,
        },
        mapObjectGroupManager: {
            getDungeonTransportMapObjectGroup: () => mapObjectGroup,
        },
    };

    for (const spec of specs) {
        const transport = new AdminDungeonTransport(map, {getLatLng: () => ({lat: spec.lat ?? 0, lng: spec.lng ?? 0})});
        transport.id = spec.id;
        transport.floor_id = spec.floorId ?? 1;
        transport.linked_dungeon_transport_id = spec.linkedId ?? null;
        transport.path_vertices_json = spec.pathVertices ? JSON.stringify(spec.pathVertices) : null;
        transportsById[spec.id] = transport;
    }

    return transportsById;
}

describe('AdminDungeonTransport', () => {
    beforeEach(() => {
        global.showErrorNotification.mockClear();
    });

    test('canEditPath_givenFacade_returnsTrueForAPathAcrossFloors', () => {
        // Arrange
        global.getState = () => makeFakeState(9, true);
        const transportsById = makeTransports([
            {id: 1, pathVertices: [{lat: 1, lng: 1, floor_id: 1}, {lat: 2, lng: 2, floor_id: 2}]},
        ]);

        // Act
        const canEditPath = transportsById[1].canEditPath();

        // Assert
        expect(canEditPath).toBe(true);
    });

    test('canEditPath_givenRegularFloorAndAPathLeavingIt_returnsFalse', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const transportsById = makeTransports([
            {id: 1, pathVertices: [{lat: 1, lng: 1, floor_id: 1}, {lat: 2, lng: 2, floor_id: 2}]},
        ]);

        // Act
        const canEditPath = transportsById[1].canEditPath();

        // Assert
        expect(canEditPath).toBe(false);
    });

    test('canEditPath_givenRegularFloorAndThePartnersPathLeavingIt_returnsFalse', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const transportsById = makeTransports([
            {id: 1, linkedId: 2},
            {id: 2, linkedId: 1, pathVertices: [{lat: 1, lng: 1, floor_id: 2}, {lat: 2, lng: 2, floor_id: 1}]},
        ]);

        // Act
        const canEditPath = transportsById[1].canEditPath();

        // Assert
        expect(canEditPath).toBe(false);
    });

    test('canEditPath_givenRegularFloorAndAPathOnIt_returnsTrue', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const transportsById = makeTransports([
            {id: 1, pathVertices: [{lat: 1, lng: 1, floor_id: 1}, {lat: 2, lng: 2, floor_id: 1}]},
        ]);

        // Act
        const canEditPath = transportsById[1].canEditPath();

        // Assert
        expect(canEditPath).toBe(true);
    });

    test('onPathDrawn_givenLineDrawnTowardsTheTransport_storesItRunningAwayFromItOnTheCurrentFloor', () => {
        // Arrange
        global.getState = () => makeFakeState(4);
        const transportsById = makeTransports([{id: 1, lat: 0, lng: 0}]);

        // Act
        transportsById[1].onPathDrawn([{lat: 50, lng: 50}, {lat: 20, lng: 20}, {lat: 1, lng: 1}]);

        // Assert
        expect(transportsById[1].getPathVertices()).toEqual([
            {lat: 1, lng: 1, floor_id: 4},
            {lat: 20, lng: 20, floor_id: 4},
            {lat: 50, lng: 50, floor_id: 4},
        ]);
        expect(transportsById[1].saveCount).toBe(1);
    });

    test('onPathDrawn_givenLineDrawnAwayFromTheTransport_keepsItsOrder', () => {
        // Arrange
        global.getState = () => makeFakeState(4);
        const transportsById = makeTransports([{id: 1, lat: 0, lng: 0}]);

        // Act
        transportsById[1].onPathDrawn([{lat: 1, lng: 1}, {lat: 50, lng: 50}]);

        // Assert
        expect(transportsById[1].getPathVertices().map((vertex) => vertex.lat)).toEqual([1, 50]);
    });

    test('onPathDrawn_givenTwoWayPartnerHoldingThePairsPath_clearsThePartnersPath', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const transportsById = makeTransports([
            {id: 1, linkedId: 2},
            {id: 2, linkedId: 1, pathVertices: [{lat: 1, lng: 1, floor_id: 1}, {lat: 2, lng: 2, floor_id: 1}]},
        ]);

        // Act
        transportsById[1].onPathDrawn([{lat: 5, lng: 5}, {lat: 6, lng: 6}]);

        // Assert
        expect(transportsById[2].path_vertices_json).toBeNull();
        expect(transportsById[2].saveCount).toBe(1);
        expect(transportsById[1].getPathVertices()).toHaveLength(2);
    });

    test('onPathDrawn_givenOneWayPartnerWithItsOwnPath_leavesThatPath', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const partnerPath = [{lat: 1, lng: 1, floor_id: 1}, {lat: 2, lng: 2, floor_id: 1}];
        const transportsById = makeTransports([
            {id: 1, linkedId: 2},
            {id: 2, linkedId: 3, pathVertices: partnerPath},
            {id: 3},
        ]);

        // Act
        transportsById[1].onPathDrawn([{lat: 5, lng: 5}, {lat: 6, lng: 6}]);

        // Assert
        expect(transportsById[2].getPathVertices()).toEqual(partnerPath);
        expect(transportsById[2].saveCount).toBe(0);
    });

    test('clearPath_givenPathOnTheCurrentFloor_clearsIt', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const transportsById = makeTransports([
            {id: 1, pathVertices: [{lat: 1, lng: 1, floor_id: 1}, {lat: 2, lng: 2, floor_id: 1}]},
        ]);

        // Act
        transportsById[1].clearPath();

        // Assert
        expect(transportsById[1].path_vertices_json).toBeNull();
        expect(transportsById[1].saveCount).toBe(1);
        expect(global.showErrorNotification).not.toHaveBeenCalled();
    });

    test('clearPath_givenPathLeavingTheCurrentFloor_keepsItAndShowsAnError', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const pathVertices = [{lat: 1, lng: 1, floor_id: 1}, {lat: 2, lng: 2, floor_id: 2}];
        const transportsById = makeTransports([{id: 1, pathVertices}]);

        // Act
        transportsById[1].clearPath();

        // Assert
        expect(transportsById[1].getPathVertices()).toEqual(pathVertices);
        expect(transportsById[1].saveCount).toBe(0);
        expect(global.showErrorNotification).toHaveBeenCalledWith('js.dungeontransport_path_not_editable');
    });
});
