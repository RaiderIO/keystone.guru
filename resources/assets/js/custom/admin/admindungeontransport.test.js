// Follows the global-script model recipe documented at the top of models/killzone.test.js. AdminDungeonTransport
// extends the real DungeonTransport here, which itself runs on a stub of Icon, so the pair path logic it relies on
// is the production code.

const fs = require('fs');
const path = require('path');
const Handlebars = require('handlebars');

/**
 * Stands in for the Leaflet.draw polyline handler a path is drawn with, firing 'disabled' like Leaflet.draw does.
 */
class FakeDrawPathHandler {
    constructor(map, options) {
        this.options = options;
        this.listeners = {};
        this.isEnabled = false;
        this.completeShape = vi.fn();
        this.deleteLastVertex = vi.fn();
        FakeDrawPathHandler.instances.push(this);
    }

    once(type, listener) {
        (this.listeners[type] = this.listeners[type] ?? []).push(listener);
    }

    enable() {
        this.isEnabled = true;
    }

    disable() {
        this.isEnabled = false;
        const listeners = this.listeners.disabled ?? [];
        this.listeners.disabled = [];
        listeners.forEach((listener) => listener());
    }
}

FakeDrawPathHandler.instances = [];

global.L = {
    latLng: (lat, lng) => ({lat, lng}),
    Draw: {
        Marker: {extend: () => function () {}},
        Polyline: {extend: () => FakeDrawPathHandler},
        Feature: {prototype: {initialize() {}}},
    },
    drawLocal: {
        draw: {
            toolbar: {
                actions: {title: 'Cancel drawing', text: 'Cancel'},
                finish: {title: 'Finish drawing', text: 'Finish'},
                undo: {title: 'Delete last point drawn', text: 'Delete last point'},
            },
        },
    },
};
global.$ = require('jquery');
global.Handlebars = {
    templates: {
        map_controls_draw_tool_status_template: Handlebars.compile(fs.readFileSync(
            path.join(__dirname, '../../handlebars/map_controls_draw_tool_status_template.handlebars'), 'utf8',
        )),
    },
};
global.c = {map: {dungeontransport: {connectionPolylineOptions: {}}}};
global.LeafletIconUnknown = {};
global.MapContextMappingVersionEdit = class MapContextMappingVersionEdit {
};
global.Attribute = class Attribute {
    constructor(options) {
        Object.assign(this, options);
    }
};
global.showErrorNotification = vi.fn();
global.LatLng = require('../structs/latlng');

const FACADE_FLOOR = {id: 9, facade: true};
const FLOOR_UNDER_THE_FACADE = {id: 2, facade: false};
const FLOOR_OFF_THE_FACADE = {id: 3, facade: false};
const FLOORS_BY_ID = {9: FACADE_FLOOR, 2: FLOOR_UNDER_THE_FACADE, 3: FLOOR_OFF_THE_FACADE};

/**
 * The facade holds FLOOR_UNDER_THE_FACADE 100 lat/lng up and to the right; its area there starts at lat 100, the facade
 * below that is dead space.
 */
global.CoordinatesService = class CoordinatesService {
    convertMapLocationToFacadeMapLocation(latLng) {
        return latLng.getFloor().id === FLOOR_UNDER_THE_FACADE.id ?
            new LatLng(latLng.getLat() + 100, latLng.getLng() + 100, FACADE_FLOOR) : latLng;
    }

    convertFacadeMapLocationToMapLocation(latLng, forceFloor = null) {
        return latLng.getLat() < 100 && forceFloor?.id !== FLOOR_UNDER_THE_FACADE.id ? latLng :
            new LatLng(latLng.getLat() - 100, latLng.getLng() - 100, FLOOR_UNDER_THE_FACADE);
    }
};

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
        this.savedData = this.getSaveData();
    }

    getSaveData() {
        return {lat: this.layer.getLatLng().lat, lng: this.layer.getLatLng().lng, floor_id: this.floor_id};
    }

    loadRemoteMapObject(remoteMapObject) {
        Object.assign(this, remoteMapObject);
    }

    isOnFloor(floor) {
        return floor.id === this.floor_id;
    }

    isVisible() {
        return this._visible ?? false;
    }

    setVisible(visible) {
        this._visible = visible;
    }

    _rebuildDecorator() {
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
    const signals = [];

    return {
        signals,
        register(name, listener, fn) {
            signals.push({name, listener, fn});
        },
        unregister(name, listener) {
            signals.splice(0, signals.length, ...signals.filter((signal) => signal.name !== name || signal.listener !== listener));
        },
        signal(name) {
            signals.filter((signal) => signal.name === name).forEach((signal) => signal.fn());
        },
        currentFloorId,
        getCurrentFloor() {
            return {id: this.currentFloorId, facade: FLOORS_BY_ID[this.currentFloorId]?.facade ?? facade};
        },
        getMapContext: () => ({
            getFloorById: (id) => FLOORS_BY_ID[id] ?? false,
            getMapIconTypeByKey: (key) => ({id: 60, key}),
        }),
        addSnackbar: vi.fn((html, options) => {
            $('body').html(`<div id="snackbar-1">${html}</div>`);
            options.onDomAdded('snackbar-1');

            return 'snackbar-1';
        }),
        removeSnackbar: vi.fn(),
    };
}

/**
 * @returns {String[]} The texts of the draw actions in the snackbar, in order.
 */
function snackbarDrawActionTexts() {
    return $('#snackbar-1 .draw_tool_snackbar .leaflet-draw-actions a').map((index, a) => a.textContent).get();
}

/**
 * A fake marker that moves, and fires the listeners it was given.
 */
function makeFakeLayer(lat, lng) {
    let latLng = {lat, lng};
    const listeners = {};

    return {
        getLatLng: () => latLng,
        setLatLng(newLatLng) {
            latLng = newLatLng;
        },
        on(type, listener) {
            listeners[type] = listener;

            return this;
        },
        off(type) {
            delete listeners[type];
        },
        fire(type) {
            listeners[type]?.();
        },
    };
}

/**
 * Builds admin transports on one fake map whose layer points are their lat/lng. Each spec is
 * {id, floorId, linkedId, lat, lng, pathVertices}.
 */
function makeTransports(specs) {
    const transportsById = {};
    const mapObjectGroup = {
        objects: transportsById,
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
        const transport = new AdminDungeonTransport(map, makeFakeLayer(spec.lat ?? 0, spec.lng ?? 0));
        transport.id = spec.id;
        transport.lat = spec.lat ?? 0;
        transport.lng = spec.lng ?? 0;
        transport.floor_id = spec.floorId ?? 1;
        transport.linked_dungeon_transport_id = spec.linkedId ?? null;
        transport.path_vertices_json = spec.pathVertices ? JSON.stringify(spec.pathVertices) : null;
        transport.facade_path_vertices_json = spec.facadePathVertices ? JSON.stringify(spec.facadePathVertices) : null;
        transport.onLayerInit();
        transportsById[spec.id] = transport;
    }

    return transportsById;
}

describe('AdminDungeonTransport', () => {
    beforeEach(() => {
        global.showErrorNotification.mockClear();
        FakeDrawPathHandler.instances = [];
        $('body').empty();
    });

    test('startDrawingPath_givenEditablePath_showsASnackbarWithTheDrawActions', () => {
        // Arrange
        const state = makeFakeState(1);
        global.getState = () => state;
        const transportsById = makeTransports([{id: 1}]);

        // Act
        transportsById[1].startDrawingPath();

        // Assert
        expect(FakeDrawPathHandler.instances).toHaveLength(1);
        expect(FakeDrawPathHandler.instances[0].isEnabled).toBe(true);
        expect($('#snackbar-1 .draw_tool_status_label').text()).toBe('js.dungeontransport_draw_path_button_text_label');
        expect(snackbarDrawActionTexts()).toEqual(['Finish', 'Delete last point', 'Cancel']);
        // Leaflet.draw's stylesheet hides a bare .leaflet-draw-actions; the toolbar's snackbar shows its actions as buttons
        expect($('#snackbar-1 .leaflet-draw-actions.row > li.btn > a > i.fas').length).toBe(3);
    });

    test.each([
        ['Finish', (handler) => expect(handler.completeShape).toHaveBeenCalledTimes(1)],
        ['Delete last point', (handler) => expect(handler.deleteLastVertex).toHaveBeenCalledTimes(1)],
        ['Cancel', (handler) => expect(handler.isEnabled).toBe(false)],
    ])('startDrawingPath_givenSnackbarAction%sClicked_runsItOnTheDrawHandler', (actionText, assertHandler) => {
        // Arrange
        const state = makeFakeState(1);
        global.getState = () => state;
        const transportsById = makeTransports([{id: 1}]);
        transportsById[1].startDrawingPath();
        const handler = FakeDrawPathHandler.instances[0];

        // Act
        $('#snackbar-1 .leaflet-draw-actions a').filter((index, a) => a.textContent === actionText)[0].click();

        // Assert
        assertHandler(handler);
    });

    test('startDrawingPath_givenDrawingStops_removesTheSnackbar', () => {
        // Arrange
        const state = makeFakeState(1);
        global.getState = () => state;
        const transportsById = makeTransports([{id: 1}]);
        transportsById[1].startDrawingPath();
        expect(state.removeSnackbar).not.toHaveBeenCalled();

        // Act
        FakeDrawPathHandler.instances[0].disable();

        // Assert
        expect(state.removeSnackbar).toHaveBeenCalledWith('snackbar-1');
    });

    test('startDrawingPath_givenFloorChangesWhileDrawing_cancelsTheDrawing', () => {
        // Arrange
        const state = makeFakeState(1);
        global.getState = () => state;
        const transportsById = makeTransports([{id: 1}]);
        transportsById[1].startDrawingPath();
        const handler = FakeDrawPathHandler.instances[0];
        expect(handler.isEnabled).toBe(true);

        // Act
        state.signal('floorid:changed');

        // Assert
        expect(handler.isEnabled).toBe(false);
        expect(state.removeSnackbar).toHaveBeenCalledWith('snackbar-1');
    });

    test('startDrawingPath_givenDrawingStops_stopsListeningForFloorChanges', () => {
        // Arrange
        const state = makeFakeState(1);
        global.getState = () => state;
        const transportsById = makeTransports([{id: 1}]);
        transportsById[1].startDrawingPath();
        const handler = FakeDrawPathHandler.instances[0];
        expect(state.signals.filter((signal) => signal.listener === handler)).toHaveLength(1);

        // Act
        handler.disable();

        // Assert
        expect(state.signals.filter((signal) => signal.listener === handler)).toHaveLength(0);
    });

    test('startDrawingPath_givenPathLeavingTheCurrentFloor_showsAnErrorAndNoSnackbar', () => {
        // Arrange
        const state = makeFakeState(1);
        global.getState = () => state;
        const transportsById = makeTransports([
            {id: 1, pathVertices: [{lat: 1, lng: 1, floor_id: 1}, {lat: 2, lng: 2, floor_id: 2}]},
        ]);

        // Act
        transportsById[1].startDrawingPath();

        // Assert
        expect(state.addSnackbar).not.toHaveBeenCalled();
        expect(FakeDrawPathHandler.instances).toHaveLength(0);
        expect(global.showErrorNotification).toHaveBeenCalledWith('js.dungeontransport_path_not_editable');
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

    test('onPathDrawn_givenThePairsFacadePaths_dropsThemSoTheNewPathIsDrawnUntilTheSaveResponds', () => {
        // Arrange
        global.getState = () => makeFakeState(9, true);
        const facadePathVertices = [{lat: 1, lng: 1, floor_id: 9}, {lat: 2, lng: 2, floor_id: 9}];
        const transportsById = makeTransports([
            {id: 1, linkedId: 2, pathVertices: [{lat: 7, lng: 7, floor_id: 1}], facadePathVertices},
            {id: 2, linkedId: 1, pathVertices: [{lat: 8, lng: 8, floor_id: 1}], facadePathVertices},
        ]);

        // Act
        transportsById[1].onPathDrawn([{lat: 5, lng: 5}, {lat: 6, lng: 6}]);

        // Assert
        expect(transportsById[1].facade_path_vertices_json).toBeNull();
        expect(transportsById[2].facade_path_vertices_json).toBeNull();
        expect(transportsById[1].getPairPathVertices(true).map((vertex) => vertex.lat)).toEqual([5, 6]);
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
    test('isOnFloor_givenFacadeAndAFloorTheFacadeHolds_returnsTrue', () => {
        // Arrange
        global.getState = () => makeFakeState(FACADE_FLOOR.id);
        const transportsById = makeTransports([{id: 1, floorId: FLOOR_UNDER_THE_FACADE.id, lat: 10, lng: 20}]);

        // Act
        const isOnFloor = transportsById[1].isOnFloor(FACADE_FLOOR);

        // Assert
        expect(isOnFloor).toBe(true);
    });

    test('isOnFloor_givenFacadeAndAFloorTheFacadeDoesNotHold_returnsFalse', () => {
        // Arrange
        global.getState = () => makeFakeState(FACADE_FLOOR.id);
        const transportsById = makeTransports([{id: 1, floorId: FLOOR_OFF_THE_FACADE.id, lat: 10, lng: 20}]);

        // Act
        const isOnFloor = transportsById[1].isOnFloor(FACADE_FLOOR);

        // Assert
        expect(isOnFloor).toBe(false);
    });

    test('setVisible_givenFacade_movesTheLayerToWhereTheFacadeShowsTheTransport', () => {
        // Arrange
        global.getState = () => makeFakeState(FACADE_FLOOR.id);
        const transportsById = makeTransports([{id: 1, floorId: FLOOR_UNDER_THE_FACADE.id, lat: 10, lng: 20}]);

        // Act
        transportsById[1].setVisible(true);

        // Assert
        expect(transportsById[1].layer.getLatLng()).toEqual({lat: 110, lng: 120});
    });

    test('setVisible_givenTheFloorItIsStoredOnAfterTheFacade_movesTheLayerBack', () => {
        // Arrange
        const state = makeFakeState(FACADE_FLOOR.id);
        global.getState = () => state;
        const transportsById = makeTransports([{id: 1, floorId: FLOOR_UNDER_THE_FACADE.id, lat: 10, lng: 20}]);
        transportsById[1].setVisible(true);
        state.currentFloorId = FLOOR_UNDER_THE_FACADE.id;

        // Act
        transportsById[1].setVisible(true);

        // Assert
        expect(transportsById[1].layer.getLatLng()).toEqual({lat: 10, lng: 20});
    });

    test('loadRemoteMapObject_givenFacade_movesTheLayerOffTheStoredLocationTheGroupPutItOn', () => {
        // Arrange
        global.getState = () => makeFakeState(FACADE_FLOOR.id);
        const transportsById = makeTransports([{id: 1, floorId: FLOOR_UNDER_THE_FACADE.id, lat: 10, lng: 20}]);
        transportsById[1].setVisible(true);
        transportsById[1].layer.setLatLng({lat: 30, lng: 40});

        // Act
        transportsById[1].loadRemoteMapObject({lat: 30, lng: 40, floor_id: FLOOR_UNDER_THE_FACADE.id});

        // Assert
        expect(transportsById[1].layer.getLatLng()).toEqual({lat: 130, lng: 140});
    });

    test('getSaveData_givenTransportMovedOnTheFacade_returnsTheLocationOnTheFloorUnderIt', () => {
        // Arrange
        global.getState = () => makeFakeState(FACADE_FLOOR.id);
        const transportsById = makeTransports([{id: 1, floorId: FLOOR_UNDER_THE_FACADE.id, lat: 10, lng: 20}]);
        transportsById[1].setVisible(true);
        transportsById[1].layer.setLatLng({lat: 150, lng: 160});

        // Act
        const saveData = transportsById[1].getSaveData();

        // Assert
        expect(saveData).toEqual({lat: 50, lng: 60, floor_id: FLOOR_UNDER_THE_FACADE.id});
    });

    test('getSaveData_givenTransportMovedIntoTheFacadesDeadSpace_keepsItOnTheFloorItIsStoredOn', () => {
        // Arrange
        global.getState = () => makeFakeState(FACADE_FLOOR.id);
        const transportsById = makeTransports([{id: 1, floorId: FLOOR_UNDER_THE_FACADE.id, lat: 10, lng: 20}]);
        transportsById[1].setVisible(true);
        transportsById[1].layer.setLatLng({lat: 50, lng: 60});

        // Act
        const saveData = transportsById[1].getSaveData();

        // Assert
        expect(saveData).toEqual({lat: -50, lng: -40, floor_id: FLOOR_UNDER_THE_FACADE.id});
    });

    test('getSaveData_givenTransportNotMovedOnTheFacade_returnsItsStoredLocationUnchanged', () => {
        // Arrange - 0.1 + 100 - 100 drifts to 0.09999999999999432
        global.getState = () => makeFakeState(FACADE_FLOOR.id);
        const transportsById = makeTransports([{id: 1, floorId: FLOOR_UNDER_THE_FACADE.id, lat: 0.1, lng: 0.2}]);
        transportsById[1].setVisible(true);

        // Act
        const saveData = transportsById[1].getSaveData();

        // Assert
        expect(saveData).toEqual({lat: 0.1, lng: 0.2, floor_id: FLOOR_UNDER_THE_FACADE.id});
    });

    test('getSaveData_givenTransportMovedOnTheFloorItIsStoredOn_returnsTheLayersLocation', () => {
        // Arrange
        global.getState = () => makeFakeState(FLOOR_UNDER_THE_FACADE.id);
        const transportsById = makeTransports([{id: 1, floorId: FLOOR_UNDER_THE_FACADE.id, lat: 10, lng: 20}]);
        transportsById[1].setVisible(true);
        transportsById[1].layer.setLatLng({lat: 150, lng: 160});

        // Act
        const saveData = transportsById[1].getSaveData();

        // Assert
        expect(saveData).toEqual({lat: 150, lng: 160, floor_id: FLOOR_UNDER_THE_FACADE.id});
    });

    test('save_givenTransportDragged_movesTheStartOfItsPathAlong', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const transportsById = makeTransports([
            {id: 1, linkedId: 2, pathVertices: [{lat: 1, lng: 1, floor_id: 1}, {lat: 5, lng: 5, floor_id: 1}]},
            {id: 2, linkedId: 1},
        ]);
        transportsById[1].layer.setLatLng({lat: 2, lng: 3});
        transportsById[1].layer.fire('dragend');

        // Act
        transportsById[1].save();

        // Assert
        expect(transportsById[1].getPathVertices()).toEqual([{lat: 2, lng: 3, floor_id: 1}, {lat: 5, lng: 5, floor_id: 1}]);
        expect(transportsById[1].savedData).toEqual({lat: 2, lng: 3, floor_id: 1});
    });

    test('save_givenTransportDraggedThatItsPartnersPathRunsTo_movesTheEndOfThatPathAlongAndSavesIt', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const transportsById = makeTransports([
            {id: 1, linkedId: 2},
            {id: 2, linkedId: 1, pathVertices: [{lat: 5, lng: 5, floor_id: 1}, {lat: 1, lng: 1, floor_id: 1}]},
        ]);
        transportsById[1].layer.setLatLng({lat: 2, lng: 3});
        transportsById[1].layer.fire('dragend');

        // Act
        transportsById[1].save();

        // Assert
        expect(transportsById[2].getPathVertices()).toEqual([{lat: 5, lng: 5, floor_id: 1}, {lat: 2, lng: 3, floor_id: 1}]);
        expect(transportsById[2].saveCount).toBe(1);
    });

    test('save_givenTransportDraggedOnTheFacade_movesItsPathAlongOnTheFloorUnderIt', () => {
        // Arrange
        global.getState = () => makeFakeState(FACADE_FLOOR.id);
        const facadePathVertices = [{lat: 110, lng: 110, floor_id: FACADE_FLOOR.id}, {lat: 150, lng: 150, floor_id: FACADE_FLOOR.id}];
        const transportsById = makeTransports([{
            id: 1,
            floorId: FLOOR_UNDER_THE_FACADE.id,
            lat: 10,
            lng: 10,
            pathVertices: [{lat: 10, lng: 10, floor_id: FLOOR_UNDER_THE_FACADE.id}, {lat: 50, lng: 50, floor_id: FLOOR_UNDER_THE_FACADE.id}],
            facadePathVertices,
        }]);
        transportsById[1].setVisible(true);
        transportsById[1].layer.setLatLng({lat: 120, lng: 130});
        transportsById[1].layer.fire('dragend');

        // Act
        transportsById[1].save();

        // Assert
        expect(transportsById[1].getPathVertices()[0]).toEqual({lat: 20, lng: 30, floor_id: FLOOR_UNDER_THE_FACADE.id});
        // The save response brings the facade's view of the moved path
        expect(transportsById[1].facade_path_vertices_json).toBeNull();
    });

    test('save_givenTransportNotDragged_leavesItsPathAlone', () => {
        // Arrange
        global.getState = () => makeFakeState(1);
        const pathVertices = [{lat: 1, lng: 1, floor_id: 1}, {lat: 5, lng: 5, floor_id: 1}];
        const transportsById = makeTransports([{id: 1, lat: 2, lng: 3, pathVertices}]);

        // Act
        transportsById[1].save();

        // Assert
        expect(transportsById[1].getPathVertices()).toEqual(pathVertices);
        expect(transportsById[1].saveCount).toBe(1);
    });
});
