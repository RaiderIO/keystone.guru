// ---------------------------------------------------------------------------
// PolylineMapObjectGroup builds the layer of every polyline owner. Most owners are open lines; an enemy pack is a
// closed shape and must come back as a polygon. The vertex parsing is the real MapObjectGroup helper, shared with
// PolygonMapObjectGroup.
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

const VERTICES_JSON = JSON.stringify([{lat: -1, lng: 2}, {lat: -3, lng: 4}, {lat: -5, lng: 6}]);

/**
 * @param isClosedShape {boolean}
 * @returns {PolylineMapObjectGroup}
 */
function buildGroup(isClosedShape) {
    const group = Object.create(PolylineMapObjectGroup.prototype);
    group._isClosedShape = () => isClosedShape;

    return group;
}

describe('PolylineMapObjectGroup._createLayer', () => {
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

    it('createLayer_givenOpenLine_returnsPolylineWithEveryVertex', () => {
        // Arrange
        const group = buildGroup(false);

        // Act
        const layer = group._createLayer({polyline: {vertices_json: VERTICES_JSON}});

        // Assert
        expect(layer.type).toBe('polyline');
        expect(layer.points).toEqual([[-1, 2], [-3, 4], [-5, 6]]);
    });

    it('createLayer_givenClosedShape_returnsPolygonWithEveryVertex', () => {
        // Arrange
        const group = buildGroup(true);

        // Act
        const layer = group._createLayer({polyline: {vertices_json: VERTICES_JSON}});

        // Assert
        expect(layer.type).toBe('polygon');
        expect(layer.points).toEqual([[-1, 2], [-3, 4], [-5, 6]]);
    });

    it('createLayer_givenClosedShapeWithoutPolyline_returnsNull', () => {
        // Arrange
        const group = buildGroup(true);

        // Act
        const layer = group._createLayer({polyline: null});

        // Assert
        expect(layer).toBeNull();
        expect(globalThis.L.polygon).not.toHaveBeenCalled();
    });

    it('createLayer_givenOpenLineWithoutPolyline_returnsEmptyPolyline', () => {
        // Arrange
        const group = buildGroup(false);

        // Act
        const layer = group._createLayer({});

        // Assert
        expect(layer.type).toBe('polyline');
        expect(layer.points).toEqual([]);
    });
});
