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
        transport.facade_path_vertices_json = spec.facadePathVertices ? JSON.stringify(spec.facadePathVertices) : null;
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
});
