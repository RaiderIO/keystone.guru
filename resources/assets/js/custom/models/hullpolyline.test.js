// ---------------------------------------------------------------------------
// HullPolyline is the base of the closed polylines that are drawn as an offset hull outside the mapping
// editor (enemy packs, mountable areas). Follows the global-script recipe from killzone.test.js: stub the
// base class and the util globals the hull builder calls, require the source.
// ---------------------------------------------------------------------------

global.Polyline = class Polyline {
    rebindTooltip() {}
};

const {HullPolyline} = require('./hullpolyline');

/**
 * @param overrides {Object} Methods to put on the instance, on top of the prototype's
 * @returns {HullPolyline}
 */
function buildHullPolyline(overrides = {}) {
    const hullPolyline = Object.create(HullPolyline.prototype);
    hullPolyline._getHullMargin = () => 2;
    hullPolyline._getHullArcSegments = () => arcSegments;
    hullPolyline._getHullPolygonOptions = () => ({weight: 1});

    return Object.assign(hullPolyline, overrides);
}

let arcSegments;

describe('HullPolyline._createHullLayer', () => {
    let originalL;
    let originalHull;
    let originalCreateOffsetPolygon;

    beforeEach(() => {
        originalL = globalThis.L;
        originalHull = globalThis.hull;
        originalCreateOffsetPolygon = globalThis.createOffsetPolygon;

        globalThis.L = {polygon: vi.fn((latLngs, options) => ({latLngs, options}))};
        globalThis.hull = vi.fn((points) => points);
        globalThis.createOffsetPolygon = vi.fn((vertices) => vertices.map(vertex => [vertex.lat, vertex.lng]));
        arcSegments = vi.fn(() => 5);
    });

    afterEach(() => {
        globalThis.L = originalL;
        globalThis.hull = originalHull;
        globalThis.createOffsetPolygon = originalCreateOffsetPolygon;
    });

    it('createHullLayer_givenPoints_returnsPolygonAroundTheirHull', () => {
        // Arrange
        const hullPolyline = buildHullPolyline();
        const points = [[-10, 10], [-20, 20], [-10, 30]];

        // Act
        const result = hullPolyline._createHullLayer(points);

        // Assert
        expect(globalThis.hull).toHaveBeenCalledWith(points, 100);
        expect(arcSegments).toHaveBeenCalledWith(3);
        expect(globalThis.createOffsetPolygon).toHaveBeenCalledWith(
            [{lat: -10, lng: 10}, {lat: -20, lng: 20}, {lat: -10, lng: 30}],
            2,
            5
        );
        expect(result.options).toEqual({weight: 1});
        expect(result.latLngs[0]).toHaveLength(3);
    });

    it('createHullLayer_givenSinglePoint_returnsNull', () => {
        // Arrange
        const hullPolyline = buildHullPolyline();

        // Act
        const result = hullPolyline._createHullLayer([[-10, 10]]);

        // Assert
        expect(result).toBeNull();
        expect(globalThis.hull).not.toHaveBeenCalled();
    });

    it('createHullLayer_givenHullOfOnePoint_returnsNull', () => {
        // Arrange
        const hullPolyline = buildHullPolyline();
        globalThis.hull = vi.fn(() => [[-10, 10]]);

        // Act
        const result = hullPolyline._createHullLayer([[-10, 10], [-10, 10]]);

        // Assert
        expect(result).toBeNull();
        expect(globalThis.L.polygon).not.toHaveBeenCalled();
    });

    it('createHullLayer_givenPolygonCreationThrows_returnsNull', () => {
        // Arrange
        const hullPolyline = buildHullPolyline();
        globalThis.L = {polygon: vi.fn(() => { throw new Error('Invalid LatLng'); })};
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});

        // Act
        const result = hullPolyline._createHullLayer([[-10, 10], [-20, 20], [-10, 30]]);

        // Assert
        expect(result).toBeNull();
        expect(consoleError).toHaveBeenCalled();
        consoleError.mockRestore();
    });
});

describe('HullPolyline._updateHullLayer', () => {
    it('updateHullLayer_givenHullPoints_setsTheHullOnTheGroupAndRebindsTheTooltip', () => {
        // Arrange
        const layer = {};
        const group = {setLayerToMapObject: vi.fn()};
        const points = [[-10, 10], [-20, 20]];
        const hullPolyline = buildHullPolyline({
            _getHullPoints: () => points,
            _getHullMapObjectGroup: () => group,
            _createHullLayer: vi.fn(() => layer),
            rebindTooltip: vi.fn(),
        });

        // Act
        hullPolyline._updateHullLayer();

        // Assert
        expect(hullPolyline._createHullLayer).toHaveBeenCalledWith(points);
        expect(group.setLayerToMapObject).toHaveBeenCalledWith(layer, hullPolyline);
        expect(hullPolyline.rebindTooltip).toHaveBeenCalledTimes(1);
    });
});

describe('HullPolyline opt-outs', () => {
    it('isWeightEditable_givenHullPolyline_returnsFalse', () => {
        // Arrange
        const hullPolyline = buildHullPolyline();

        // Act
        const isWeightEditable = hullPolyline._isWeightEditable();

        // Assert
        expect(isWeightEditable).toBe(false);
    });

    it('isAnimatable_givenHullPolyline_returnsFalse', () => {
        // Arrange
        const hullPolyline = buildHullPolyline();

        // Act
        const isAnimatable = hullPolyline._isAnimatable();

        // Assert
        expect(isAnimatable).toBe(false);
    });
});
