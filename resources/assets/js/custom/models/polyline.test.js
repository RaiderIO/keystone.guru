// ---------------------------------------------------------------------------
// Polyline is the base of every map object that owns a polyline. Most are open lines, but an enemy pack is a
// closed shape without a weight picker or an animated layer; these tests pin the seams that make both work.
// Follows the global-script recipe from killzone.test.js: stub the base class, require the source.
// ---------------------------------------------------------------------------

global.VersionableMapObject = class VersionableMapObject {
};
global.c = {map: {polyline: {polylineOptionsAnimated: {delay: 400}}}};
global.$.extend = Object.assign;

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
    polyline.map = {
        drawnLayers: {
            hasLayer: vi.fn(() => false),
            addLayer: vi.fn(),
            removeLayer: vi.fn(),
        },
    };
    polyline._assignPopup = vi.fn();

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

    it('setPolylineWeight_givenAnAnimatedLayer_restylesItToo', () => {
        // Arrange
        const polyline = buildPolyline({type: 'LineString', coordinates: []});
        polyline.layerAnimated = {setStyle: vi.fn(), redraw: vi.fn()};

        // Act
        polyline.setPolylineWeight(5);

        // Assert
        expect(polyline.layerAnimated.setStyle).toHaveBeenCalledWith({weight: 5});
        expect(polyline.layerAnimated.redraw).toHaveBeenCalledTimes(1);
    });
});

describe('Polyline.setPolylineColorAnimated', () => {
    let animatedLayer;

    beforeEach(() => {
        animatedLayer = {};
        global.L = {polyline: {antPath: vi.fn(() => animatedLayer)}};
    });

    it('setPolylineColorAnimated_givenAnimatableWithAColor_showsAnAnimatedLayerAlongTheVertices', () => {
        // Arrange
        const polyline = buildPolyline({type: 'LineString', coordinates: [[2, -1], [4, -3]]});

        // Act
        polyline.setPolylineColorAnimated('#00ff00');

        // Assert
        expect(L.polyline.antPath).toHaveBeenCalledWith(
            [{lat: -1, lng: 2}, {lat: -3, lng: 4}],
            {delay: 400, color: '#ff0000', pulseColor: '#00ff00', weight: 3}
        );
        expect(polyline.layerAnimated).toBe(animatedLayer);
        expect(polyline._assignPopup).toHaveBeenCalledWith(animatedLayer);
        expect(polyline.map.drawnLayers.addLayer).toHaveBeenCalledWith(animatedLayer);
    });

    it('setPolylineColorAnimated_givenNull_removesTheExistingAnimatedLayer', () => {
        // Arrange
        const polyline = buildPolyline({type: 'LineString', coordinates: []});
        const previousAnimatedLayer = {};
        polyline.layerAnimated = previousAnimatedLayer;
        polyline.map.drawnLayers.hasLayer = vi.fn((layer) => layer === previousAnimatedLayer);

        // Act
        polyline.setPolylineColorAnimated(null);

        // Assert
        expect(polyline.polyline.color_animated).toBeNull();
        expect(polyline.map.drawnLayers.removeLayer).toHaveBeenCalledWith(previousAnimatedLayer);
        expect(polyline.layerAnimated).toBeNull();
        expect(L.polyline.antPath).not.toHaveBeenCalled();
    });

    it('setPolylineColorAnimated_givenNotAnimatable_storesTheColorWithoutAnAnimatedLayer', () => {
        // Arrange
        const polyline = buildPolyline({type: 'Polygon', coordinates: [[[2, -1], [4, -3], [2, -1]]]});
        polyline._isAnimatable = () => false;
        polyline._setAnimatedLayerVisibility = vi.fn();

        // Act
        polyline.setPolylineColorAnimated('#00ff00');

        // Assert
        expect(polyline.polyline.color_animated).toBe('#00ff00');
        expect(polyline.layerAnimated).toBeNull();
        expect(L.polyline.antPath).not.toHaveBeenCalled();
        expect(polyline._setAnimatedLayerVisibility).not.toHaveBeenCalledWith(true);
    });
});

describe('Polyline._setAnimatedLayerVisibility', () => {
    it('setAnimatedLayerVisibility_givenVisibleAndAlreadyOnTheMap_doesNotAddItAgain', () => {
        // Arrange
        const polyline = buildPolyline({type: 'LineString', coordinates: []});
        polyline.layerAnimated = {};
        polyline.map.drawnLayers.hasLayer = vi.fn(() => true);

        // Act
        polyline._setAnimatedLayerVisibility(true);

        // Assert
        expect(polyline.map.drawnLayers.hasLayer).toHaveBeenCalledWith(polyline.layerAnimated);
        expect(polyline.map.drawnLayers.addLayer).not.toHaveBeenCalled();
    });

    it('setAnimatedLayerVisibility_givenHiddenWhileNotOnTheMap_doesNotRemoveIt', () => {
        // Arrange
        const polyline = buildPolyline({type: 'LineString', coordinates: []});
        polyline.layerAnimated = {};

        // Act
        polyline._setAnimatedLayerVisibility(false);

        // Assert
        expect(polyline.map.drawnLayers.hasLayer).toHaveBeenCalledWith(polyline.layerAnimated);
        expect(polyline.map.drawnLayers.removeLayer).not.toHaveBeenCalled();
    });
});

describe('Polyline._isColorEditable', () => {
    it('isColorEditable_givenPolyline_returnsTrue', () => {
        // Arrange
        const polyline = buildPolyline({type: 'LineString', coordinates: []});

        // Act
        const isColorEditable = polyline._isColorEditable();

        // Assert
        expect(isColorEditable).toBe(true);
    });
});
