// The lat/lng -> ingame expectations are lifted verbatim from
// tests/Unit/App/Service/Coordinates/CoordinatesServiceTest.php, the PHP service this is a port of.

const {rotateLatLng, roundHalfAwayFromZero} = require('../util');
global.rotateLatLng = rotateLatLng;
global.roundHalfAwayFromZero = roundHalfAwayFromZero;

const {MAP_MAX_LAT, MAP_MAX_LNG, MAP_SIZE, MAP_ASPECT_RATIO} = require('../constants');
global.MAP_MAX_LAT = MAP_MAX_LAT;
global.MAP_MAX_LNG = MAP_MAX_LNG;
global.MAP_SIZE = MAP_SIZE;
global.MAP_ASPECT_RATIO = MAP_ASPECT_RATIO;

const LatLng = require('../structs/latlng');
global.LatLng = LatLng;
const IngameXY = require('../structs/ingamexy');
global.IngameXY = IngameXY;

const CoordinatesService = require('./coordinatesservice');

const FACADE_FLOOR = {id: 1, name: 'floor.facade', facade: true};
const TARGET_FLOOR = {
    id: 2,
    name: 'floor.target',
    facade: false,
    ingame_min_x: 100,
    ingame_max_x: 1000,
    ingame_min_y: 100,
    ingame_max_y: 1000
};

/**
 * @param floorUnions {Object[]}
 * @param facadeEnabled {Boolean}
 * @param floors {Object[]}
 * @returns {Object}
 */
function createMapContext(floorUnions = [], facadeEnabled = true, floors = [FACADE_FLOOR, TARGET_FLOOR]) {
    return {
        getMappingVersion: () => ({facade_enabled: facadeEnabled}),
        getFloorUnions: () => floorUnions,
        getFloorUnionAreas: () => [],
        getFloorById: (floorId) => floors.find(floor => floor.id === floorId) ?? false
    };
}

/**
 * A floor union covering the top left quadrant of the facade.
 *
 * @param overrides {Object}
 * @returns {Object}
 */
function createFloorUnion(overrides = {}) {
    return Object.assign({
        id: 10,
        floor_id: FACADE_FLOOR.id,
        target_floor_id: TARGET_FLOOR.id,
        target_floor: TARGET_FLOOR,
        lat: -64,
        lng: 96,
        size: 128,
        rotation: 0,
        floor_union_areas: [{
            id: 100,
            floor_union_id: 10,
            vertices_json: JSON.stringify([
                {lat: 0, lng: 0},
                {lat: 0, lng: 192},
                {lat: -128, lng: 192},
                {lat: -128, lng: 0}
            ])
        }]
    }, overrides);
}

describe('CoordinatesService.calculateIngameLocationForMapLocation', () => {
    test.each([
        [MAP_MAX_LAT / 2, MAP_MAX_LNG / 2, {ingame_min_x: 0, ingame_max_x: 100, ingame_min_y: 0, ingame_max_y: 100}, 50, 50],
        [MAP_MAX_LAT / 2, MAP_MAX_LNG / 2, {ingame_min_x: 100, ingame_max_x: 1000, ingame_min_y: 100, ingame_max_y: 1000}, 550, 550],
        [MAP_MAX_LAT / 4, MAP_MAX_LNG / 4, {ingame_min_x: 100, ingame_max_x: 1000, ingame_min_y: 100, ingame_max_y: 1000}, 775, 775],
        [MAP_MAX_LAT / 10, MAP_MAX_LNG / 4, {ingame_min_x: 100, ingame_max_x: 1000, ingame_min_y: 50, ingame_max_y: 100}, 775, 95],
    ])('calculateIngameLocationForMapLocation_givenLatLng_returnsIngameXY', (lat, lng, bounds, expectedX, expectedY) => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext());
        let floor = Object.assign({id: 3, facade: false}, bounds);

        // Act
        let result = coordinatesService.calculateIngameLocationForMapLocation(new LatLng(lat, lng, floor));

        // Assert
        expect(result.getX()).toBeCloseTo(expectedX, 9);
        expect(result.getY()).toBeCloseTo(expectedY, 9);
        expect(result.getFloor()).toBe(floor);
    });

    test('calculateIngameLocationForMapLocation_givenFacadeFloor_throws', () => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext());

        // Act & assert
        expect(() => coordinatesService.calculateIngameLocationForMapLocation(new LatLng(-128, 192, FACADE_FLOOR)))
            .toThrow(/facade floor/);
    });

    test('calculateIngameLocationForMapLocation_givenNoFloor_throws', () => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext());

        // Act & assert
        expect(() => coordinatesService.calculateIngameLocationForMapLocation(new LatLng(-128, 192)))
            .toThrow(/No floor set/);
    });
});

describe('CoordinatesService.calculateMapLocationForIngameLocation', () => {
    test('calculateMapLocationForIngameLocation_givenIngameXY_isTheInverseOfTheForwardConversion', () => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext());
        let latLng = new LatLng(-64, 96, TARGET_FLOOR);

        // Act
        let ingameXY = coordinatesService.calculateIngameLocationForMapLocation(latLng);
        let result = coordinatesService.calculateMapLocationForIngameLocation(ingameXY);

        // Assert
        expect(result.getLat()).toBeCloseTo(latLng.getLat(), 9);
        expect(result.getLng()).toBeCloseTo(latLng.getLng(), 9);
    });

    test('calculateMapLocationForIngameLocation_givenFloorWithoutIngameBounds_throws', () => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext());
        let floor = {id: 4, name: 'floor.unmapped', facade: false, ingame_min_x: 0, ingame_max_x: 0, ingame_min_y: 0, ingame_max_y: 0};

        // Act & assert
        expect(() => coordinatesService.calculateMapLocationForIngameLocation(new IngameXY(0, 0, floor)))
            .toThrow(/does not have ingame coordinates set/);
    });
});

describe('CoordinatesService.polygonContainsPoint', () => {
    const square = [
        {lat: 0, lng: 0},
        {lat: 0, lng: 10},
        {lat: -10, lng: 10},
        {lat: -10, lng: 0}
    ];

    test('polygonContainsPoint_givenPointInside_returnsTrue', () => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext());

        // Act & assert
        expect(coordinatesService.polygonContainsPoint(new LatLng(-5, 5), square)).toBe(true);
    });

    test('polygonContainsPoint_givenPointOutside_returnsFalse', () => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext());

        // Act & assert
        expect(coordinatesService.polygonContainsPoint(new LatLng(-5, 15), square)).toBe(false);
        expect(coordinatesService.polygonContainsPoint(new LatLng(5, 5), square)).toBe(false);
    });

    test('polygonContainsPoint_givenClosedPolygon_doesNotCloseItAgain', () => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext());
        let closed = square.concat([square[0]]);

        // Act & assert
        expect(coordinatesService.polygonContainsPoint(new LatLng(-5, 5), closed)).toBe(true);
    });

    test('polygonContainsPoint_givenPolygon_doesNotMutateIt', () => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext());

        // Act
        coordinatesService.polygonContainsPoint(new LatLng(-5, 5), square);

        // Assert
        expect(square).toHaveLength(4);
    });
});

describe('CoordinatesService.convertFacadeMapLocationToMapLocation', () => {
    test('convertFacadeMapLocationToMapLocation_givenFacadeDisabled_returnsAnUnchangedCopy', () => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext([createFloorUnion()], false));
        let latLng = new LatLng(-64, 96, FACADE_FLOOR);

        // Act
        let result = coordinatesService.convertFacadeMapLocationToMapLocation(latLng);

        // Assert
        expect(result).not.toBe(latLng);
        expect(result.getLat()).toBe(-64);
        expect(result.getLng()).toBe(96);
        expect(result.getFloor()).toBe(FACADE_FLOOR);
    });

    test('convertFacadeMapLocationToMapLocation_givenPointInsideUnionArea_scalesOntoTheTargetFloor', () => {
        // Arrange
        let floorUnion = createFloorUnion();
        let coordinatesService = new CoordinatesService(createMapContext([floorUnion]));
        // The center of the union maps onto the center of the target floor's map
        let latLng = new LatLng(floorUnion.lat, floorUnion.lng, FACADE_FLOOR);

        // Act
        let result = coordinatesService.convertFacadeMapLocationToMapLocation(latLng);

        // Assert
        expect(result.getFloor()).toBe(TARGET_FLOOR);
        expect(result.getLat()).toBeCloseTo(MAP_MAX_LAT / 2, 9);
        expect(result.getLng()).toBeCloseTo(MAP_MAX_LNG / 2, 9);
    });

    test('convertFacadeMapLocationToMapLocation_givenPointInDeadSpace_leavesTheFloorAlone', () => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext([createFloorUnion()]));
        // Bottom right of the facade, outside the union's area
        let latLng = new LatLng(-200, 300, FACADE_FLOOR);

        // Act
        let result = coordinatesService.convertFacadeMapLocationToMapLocation(latLng);

        // Assert
        expect(result.getFloor()).toBe(FACADE_FLOOR);
        expect(result.getLat()).toBe(-200);
        expect(result.getLng()).toBe(300);
    });

    test('convertFacadeMapLocationToMapLocation_givenTwoUnions_picksTheOneWhoseAreaContainsThePoint', () => {
        // Arrange
        let otherFloor = Object.assign({}, TARGET_FLOOR, {id: 3, name: 'floor.other'});
        let leftUnion = createFloorUnion();
        let rightUnion = createFloorUnion({
            id: 11,
            target_floor_id: otherFloor.id,
            target_floor: otherFloor,
            lat: -192,
            lng: 288,
            floor_union_areas: [{
                id: 101,
                floor_union_id: 11,
                vertices_json: JSON.stringify([
                    {lat: -128, lng: 192},
                    {lat: -128, lng: 384},
                    {lat: -256, lng: 384},
                    {lat: -256, lng: 192}
                ])
            }]
        });
        let coordinatesService = new CoordinatesService(createMapContext([leftUnion, rightUnion], true, [FACADE_FLOOR, TARGET_FLOOR, otherFloor]));

        // Act
        let result = coordinatesService.convertFacadeMapLocationToMapLocation(new LatLng(-192, 288, FACADE_FLOOR));

        // Assert
        expect(result.getFloor()).toBe(otherFloor);
    });

    test('convertFacadeMapLocationToMapLocation_givenRotatedUnion_rotatesBeforeScaling', () => {
        // Arrange
        let floorUnion = createFloorUnion({rotation: 30});
        let coordinatesService = new CoordinatesService(createMapContext([floorUnion]));
        let latLng = new LatLng(-40, 60, FACADE_FLOOR);
        let unionCenter = new LatLng(floorUnion.lat, floorUnion.lng);
        let mapCenter = CoordinatesService.getMapCenterLatLng();

        let rotateThenScale = latLng.clone()
            .rotate(unionCenter, floorUnion.rotation)
            .scale(unionCenter, floorUnion.size, mapCenter, MAP_SIZE);
        let scaleThenRotate = latLng.clone()
            .scale(unionCenter, floorUnion.size, mapCenter, MAP_SIZE)
            .rotate(unionCenter, floorUnion.rotation);

        // Act
        let result = coordinatesService.convertFacadeMapLocationToMapLocation(latLng);

        // Assert
        expect(result.getLat()).toBeCloseTo(rotateThenScale.getLat(), 9);
        expect(result.getLng()).toBeCloseTo(rotateThenScale.getLng(), 9);
        expect(result.getLat()).not.toBeCloseTo(scaleThenRotate.getLat(), 3);
    });

    test('convertFacadeMapLocationToMapLocation_givenLatLng_doesNotMutateTheInput', () => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext([createFloorUnion()]));
        let latLng = new LatLng(-64, 96, FACADE_FLOOR);

        // Act
        coordinatesService.convertFacadeMapLocationToMapLocation(latLng);

        // Assert
        expect(latLng.getLat()).toBe(-64);
        expect(latLng.getLng()).toBe(96);
        expect(latLng.getFloor()).toBe(FACADE_FLOOR);
    });

    test('convertFacadeMapLocationToMapLocation_givenForcedFloor_usesThatUnionWithoutCheckingAreas', () => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext([createFloorUnion()]));
        // In dead space, so only the forced floor can produce a conversion
        let latLng = new LatLng(-200, 300, FACADE_FLOOR);

        // Act
        let result = coordinatesService.convertFacadeMapLocationToMapLocation(latLng, TARGET_FLOOR);

        // Assert
        expect(result.getFloor()).toBe(TARGET_FLOOR);
    });
});

describe('CoordinatesService.convertMapLocationToFacadeMapLocation', () => {
    test('convertMapLocationToFacadeMapLocation_givenNoFloorUnions_returnsThePointUnchanged', () => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext([]));
        let latLng = new LatLng(-64, 96, TARGET_FLOOR);

        // Act
        let result = coordinatesService.convertMapLocationToFacadeMapLocation(latLng);

        // Assert
        expect(result.getLat()).toBe(-64);
        expect(result.getLng()).toBe(96);
    });

    test('convertMapLocationToFacadeMapLocation_givenFloorUnion_movesThePointOntoTheFacadeFloor', () => {
        // Arrange
        let floorUnion = createFloorUnion();
        let coordinatesService = new CoordinatesService(createMapContext([floorUnion]));

        // Act
        let result = coordinatesService.convertMapLocationToFacadeMapLocation(
            new LatLng(MAP_MAX_LAT / 2, MAP_MAX_LNG / 2, TARGET_FLOOR)
        );

        // Assert
        expect(result.getFloor()).toBe(FACADE_FLOOR);
        expect(result.getLat()).toBeCloseTo(floorUnion.lat, 9);
        expect(result.getLng()).toBeCloseTo(floorUnion.lng, 9);
    });

    test.each([0, 30, -45])('convertMapLocationToFacadeMapLocation_givenRotation_roundTripsBackToTheSamePoint', (rotation) => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext([createFloorUnion({rotation: rotation})]));
        let facadeLatLng = new LatLng(-40, 60, FACADE_FLOOR);

        // Act
        let floorLatLng = coordinatesService.convertFacadeMapLocationToMapLocation(facadeLatLng);
        let result = coordinatesService.convertMapLocationToFacadeMapLocation(floorLatLng);

        // Assert
        expect(floorLatLng.getFloor()).toBe(TARGET_FLOOR);
        expect(result.getLat()).toBeCloseTo(facadeLatLng.getLat(), 9);
        expect(result.getLng()).toBeCloseTo(facadeLatLng.getLng(), 9);
    });
});

describe('CoordinatesService.intersection', () => {
    test('intersection_givenCrossingSegments_returnsTheCrossingPoint', () => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext());

        // Act
        let result = coordinatesService.intersection(
            new LatLng(0, 0), new LatLng(10, 10),
            new LatLng(10, 0), new LatLng(0, 10)
        );

        // Assert
        expect(result.getLat()).toBeCloseTo(5, 9);
        expect(result.getLng()).toBeCloseTo(5, 9);
    });

    test('intersection_givenParallelSegments_returnsNull', () => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext());

        // Act & assert
        expect(coordinatesService.intersection(
            new LatLng(0, 0), new LatLng(10, 10),
            new LatLng(0, 5), new LatLng(10, 15)
        )).toBeNull();
    });

    test('intersection_givenSegmentsThatOnlyCrossWhenExtended_returnsNull', () => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext());

        // Act & assert
        expect(coordinatesService.intersection(
            new LatLng(0, 0), new LatLng(1, 1),
            new LatLng(10, 0), new LatLng(0, 10)
        )).toBeNull();
    });
});

describe('CoordinatesService.calculateGridLocationForIngameLocation', () => {
    test('calculateGridLocationForIngameLocation_givenIngameXY_snapsToTheCellCorner', () => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext());

        // Act - bounds are 100..1000 on both axes, so a 9x9 grid has 100 unit cells
        let result = coordinatesService.calculateGridLocationForIngameLocation(new IngameXY(555, 555, TARGET_FLOOR), 9, 9);

        // Assert
        expect(result.getX()).toBeCloseTo(500, 9);
        expect(result.getY()).toBeCloseTo(500, 9);
    });
});

describe('CoordinatesService.distance', () => {
    test('distance_givenTwoLatLngs_returnsTheEuclideanDistance', () => {
        // Arrange
        let coordinatesService = new CoordinatesService(createMapContext());

        // Act & assert
        expect(coordinatesService.distance(new LatLng(0, 0), new LatLng(3, 4))).toBeCloseTo(5, 9);
        expect(coordinatesService.distanceIngameXY(new IngameXY(0, 0), new IngameXY(3, 4))).toBeCloseTo(5, 9);
    });
});
