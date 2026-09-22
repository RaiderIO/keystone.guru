// ---------------------------------------------------------------------------
// Polyline is the base of every map object that owns a polyline. Most are open lines, but an enemy pack is a
// closed shape without a weight picker or an animated layer; these tests pin the seams that make both work.
// Follows the global-script recipe from killzone.test.js: stub the base class, require the source.
// ---------------------------------------------------------------------------

global.VersionableMapObject = class VersionableMapObject {
};

const {Polyline} = require('./polyline');

/**
 * @param geometry {Object} The GeoJSON geometry the layer reports
 * @returns {Polyline}
 */
function buildPolyline(geometry) {
    const polyline = Object.create(Polyline.prototype);
    polyline.polyline = {color: '#ff0000', color_animated: null, weight: 3};
    polyline.layer = {
        toGeoJSON: () => ({geometry}),
        setStyle: vi.fn(),
        redraw: vi.fn(),
    };
    polyline.layerAnimated = null;

    return polyline;
}

describe('Polyline.getVertices', () => {
    it('getVertices_givenLineString_returnsEveryVertex', () => {
        // Arrange
        const polyline = buildPolyline({type: 'LineString', coordinates: [[2, -1], [4, -3]]});

        // Act
        const vertices = polyline.getVertices();

        // Assert
        expect(vertices).toEqual([{lat: -1, lng: 2}, {lat: -3, lng: 4}]);
    });

    it('getVertices_givenPolygon_returnsTheOuterRingWithoutItsClosingVertex', () => {
        // Arrange
        const polyline = buildPolyline({type: 'Polygon', coordinates: [[[2, -1], [4, -3], [6, -5], [2, -1]]]});

        // Act
        const vertices = polyline.getVertices();

        // Assert
        expect(vertices).toEqual([{lat: -1, lng: 2}, {lat: -3, lng: 4}, {lat: -5, lng: 6}]);
    });
});

describe('Polyline.setPolylineWeight', () => {
    it('setPolylineWeight_givenEditableWeight_restylesTheLayer', () => {
        // Arrange
        const polyline = buildPolyline({type: 'LineString', coordinates: []});

        // Act
        polyline.setPolylineWeight(5);

        // Assert
        expect(polyline.polyline.weight).toBe(5);
        expect(polyline.layer.setStyle).toHaveBeenCalledWith({weight: 5});
    });

    it('setPolylineWeight_givenWeightNotEditable_storesTheWeightButKeepsTheLayerStyle', () => {
        // Arrange
        const polyline = buildPolyline({type: 'Polygon', coordinates: []});
        polyline._isWeightEditable = () => false;

        // Act
        polyline.setPolylineWeight(5);

        // Assert
        expect(polyline.polyline.weight).toBe(5);
        expect(polyline.layer.setStyle).not.toHaveBeenCalled();
    });
});

describe('Polyline.setPolylineColorAnimated', () => {
    it('setPolylineColorAnimated_givenNotAnimatable_storesTheColorWithoutAnAnimatedLayer', () => {
        // Arrange
        const polyline = buildPolyline({type: 'Polygon', coordinates: []});
        polyline._isAnimatable = () => false;
        polyline._setAnimatedLayerVisibility = vi.fn();

        // Act
        polyline.setPolylineColorAnimated('#00ff00');

        // Assert
        expect(polyline.polyline.color_animated).toBe('#00ff00');
        expect(polyline.layerAnimated).toBeNull();
        expect(polyline._setAnimatedLayerVisibility).not.toHaveBeenCalledWith(true);
    });
});
