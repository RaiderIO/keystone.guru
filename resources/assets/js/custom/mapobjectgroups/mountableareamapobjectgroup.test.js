// ---------------------------------------------------------------------------
// Mountable areas are closed polylines: the group must build a polygon in the mountable area colour from the
// nested polyline's vertices.
// ---------------------------------------------------------------------------

const {Signalable} = require('../signalable');
globalThis.Signalable = Signalable;
globalThis.KillZone = class KillZone {
};
globalThis.Handlebars = require('handlebars');
globalThis.isColorDark = () => true;

const {MapObjectGroup} = require('./mapobjectgroup');
globalThis.MapObjectGroup = MapObjectGroup;
const {PolylineMapObjectGroup} = require('./polylinemapobjectgroup');
globalThis.PolylineMapObjectGroup = PolylineMapObjectGroup;
globalThis.c = {map: {mountablearea: {color: '#eb4934'}}};

const {MountableAreaMapObjectGroup} = require('./mountableareamapobjectgroup');

describe('MountableAreaMapObjectGroup._createLayer', () => {
    let originalL;

    beforeEach(() => {
        originalL = globalThis.L;
        globalThis.L = {
            polyline: vi.fn((points, options) => ({type: 'polyline', points, options})),
            polygon: vi.fn((points, options) => ({type: 'polygon', points, options})),
        };
    });

    afterEach(() => {
        globalThis.L = originalL;
    });

    it('createLayer_givenMountableAreaWithPolyline_buildsAPolygonInTheMountableAreaColor', () => {
        // Arrange
        const group = Object.create(MountableAreaMapObjectGroup.prototype);
        const verticesJson = JSON.stringify([{lat: -1, lng: 2}, {lat: -3, lng: 4}, {lat: -5, lng: 6}]);

        // Act
        const layer = group._createLayer({polyline: {vertices_json: verticesJson}});

        // Assert
        expect(layer.type).toBe('polygon');
        expect(layer.points).toHaveLength(3);
        expect(layer.options).toEqual({color: '#eb4934'});
    });

    it('createLayer_givenMountableAreaWithoutPolyline_returnsNull', () => {
        // Arrange
        const group = Object.create(MountableAreaMapObjectGroup.prototype);

        // Act
        const layer = group._createLayer({polyline: null});

        // Assert
        expect(layer).toBeNull();
    });
});
