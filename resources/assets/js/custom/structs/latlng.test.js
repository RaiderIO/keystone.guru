// The expected values are lifted verbatim from tests/Unit/App/Logic/Structs/LatLngTest.php, the
// PHP class this is a port of - a divergence between the two implementations has to fail here.

const {rotateLatLng, roundHalfAwayFromZero} = require('../util');
global.rotateLatLng = rotateLatLng;
global.roundHalfAwayFromZero = roundHalfAwayFromZero;

const {MAP_MAX_LAT, MAP_MAX_LNG, MAP_SIZE, MAP_ASPECT_RATIO} = require('../constants');
global.MAP_MAX_LAT = MAP_MAX_LAT;
global.MAP_MAX_LNG = MAP_MAX_LNG;
global.MAP_SIZE = MAP_SIZE;
global.MAP_ASPECT_RATIO = MAP_ASPECT_RATIO;

const LatLng = require('./latlng');

describe('LatLng.scale', () => {
    test.each([
        [new LatLng(25, 25), new LatLng(50, 50)],
        [new LatLng(75, 75), new LatLng(150, 150)],
    ])('scale_givenPositiveMapCoordinates_shouldScaleLatLng', (latLng, expected) => {
        // Arrange
        let currentCenter = new LatLng(50, 50);
        let targetCenter = new LatLng(100, 100);

        // Act
        let result = latLng.scale(currentCenter, 100, targetCenter, 200);

        // Assert
        expect(result.getLat()).toBeCloseTo(expected.getLat(), 9);
        expect(result.getLng()).toBeCloseTo(expected.getLng(), 9);
    });

    test('scale_givenRealisticMapCoordinates_shouldScaleLatLng', () => {
        // Arrange
        let currentCenter = new LatLng(-181.69, 273.31);
        let targetCenter = new LatLng(MAP_MAX_LAT / 2, MAP_MAX_LNG / 2);

        // Act
        let result = new LatLng(-195, 210).scale(currentCenter, 200, targetCenter, MAP_SIZE);

        // Assert
        expect(result.getLat()).toBeCloseTo(-145.0368, 4);
        expect(result.getLng()).toBeCloseTo(110.9632, 4);
    });
});

describe('LatLng.rotate', () => {
    test.each([
        // Top left to top right
        [new LatLng(25, 25), 90, new LatLng(25, 75)],
        // Top left to dead middle
        [new LatLng(25, 25), 45, new LatLng(14.644660940672622, 50)],
    ])('rotate_givenPositiveMapCoordinates_shouldRotateLatLng', (latLng, rotation, expected) => {
        // Arrange
        let currentCenter = new LatLng(50, 50);

        // Act
        let result = latLng.rotate(currentCenter, rotation);

        // Assert
        expect(result.getLat()).toBeCloseTo(expected.getLat(), 9);
        expect(result.getLng()).toBeCloseTo(expected.getLng(), 9);
    });

    test('rotate_givenScaledMapCoordinates_shouldRotateLatLng', () => {
        // Arrange
        let currentCenter = new LatLng(50, 50);
        let targetCenter = new LatLng(100, 100);

        // Act
        let result = new LatLng(25, 25)
            .scale(currentCenter, 100, targetCenter, 200)
            .rotate(targetCenter, 90);

        // Assert
        expect(result.getLat()).toBeCloseTo(50, 9);
        expect(result.getLng()).toBeCloseTo(150, 9);
    });
});

describe('LatLng', () => {
    test('clone_givenLatLng_returnsIndependentCopyKeepingTheFloor', () => {
        // Arrange
        let floor = {id: 12};
        let latLng = new LatLng(-100, 200, floor);

        // Act
        let clone = latLng.clone();
        clone.setLat(-50);

        // Assert
        expect(latLng.getLat()).toBe(-100);
        expect(clone.getFloor()).toBe(floor);
    });

    test('getLat_givenPrecisionAndNegativeHalfway_roundsAwayFromZeroLikePhp', () => {
        // Arrange
        let latLng = new LatLng(-1.5, 1.5);

        // Act & assert - PHP's round() is half away from zero, JS' Math.round() is half up
        expect(latLng.getLat(0)).toBe(-2);
        expect(latLng.getLng(0)).toBe(2);
    });

    test('toArrayWithFloor_givenNoFloor_returnsNullFloorId', () => {
        // Arrange
        let latLng = new LatLng(-1, 2);

        // Act
        let result = latLng.toArrayWithFloor();

        // Assert
        expect(result).toEqual({lat: -1, lng: 2, floor_id: null});
    });
});
