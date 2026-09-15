<?php

namespace Tests\Unit\App\Service\Coordinates;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\Floor\FloorUnionArea;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\Coordinates\CoordinatesService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\ServiceFixtures;
use Tests\TestCases\PublicTestCase;

/**
 * Every test named after a case in resources/assets/js/custom/coordinates/coordinatesservice.test.js
 * mirrors that case: same fixtures, same inputs, same expectations and tolerances, so the PHP service
 * and its JS port are held to identical results.
 */
#[Group('CoordinatesService')]
final class CoordinatesServiceTest extends PublicTestCase
{
    /**
     * Jest's toBeCloseTo(expected, 9) passes when |expected - actual| < 10^-9 / 2.
     */
    private const float JEST_CLOSE_TO_9_DIGITS = 0.5e-9;

    /**
     * Jest's not.toBeCloseTo(expected, 3) passes when |expected - actual| >= 10^-3 / 2.
     */
    private const float JEST_CLOSE_TO_3_DIGITS = 0.5e-3;

    private const int FACADE_FLOOR_ID = 1;

    private const int TARGET_FLOOR_ID = 2;

    /**
     * The top left quadrant of the facade.
     *
     * @var array<int, array{lat: int, lng: int}>
     */
    private const array TOP_LEFT_QUADRANT_VERTICES = [
        ['lat' => 0, 'lng' => 0],
        ['lat' => 0, 'lng' => 192],
        ['lat' => -128, 'lng' => 192],
        ['lat' => -128, 'lng' => 0],
    ];

    /**
     * The bottom right quadrant of the facade.
     *
     * @var array<int, array{lat: int, lng: int}>
     */
    private const array BOTTOM_RIGHT_QUADRANT_VERTICES = [
        ['lat' => -128, 'lng' => 192],
        ['lat' => -128, 'lng' => 384],
        ['lat' => -256, 'lng' => 384],
        ['lat' => -256, 'lng' => 192],
    ];

    /**
     * @var array<int, array{lat: int, lng: int}>
     */
    private const array SQUARE_VERTICES = [
        ['lat' => 0, 'lng' => 0],
        ['lat' => 0, 'lng' => 10],
        ['lat' => -10, 'lng' => 10],
        ['lat' => -10, 'lng' => 0],
    ];

    /**
     * Dungeons whose enemy positions deviate a lot from MDT's but are valid, manually checked.
     *
     * King's Rest earns its place differently from the rest: it had a complete hand-made mapping
     * before MDT 6.2 repointed it to a zoomed facade, and the import deliberately keeps a mapper's
     * position over MDT's whenever the two are within 150 in-game units. Those kept positions were
     * placed in the non-facade frame the mapper worked in, so measuring them in MDT's new frame
     * surfaces drift that comparing within a single frame never showed - 31 of its 101 enemies
     * exceed the margin, median 17.5. The conversion is not what deviates: the 30 enemies whose
     * positions do come from MDT 6.2 round-trip back to within 0.07 (#3734).
     *
     * @var array<int, string>
     */
    private const array DEVIATES_FROM_MDT_DUNGEON_KEYS = [
        DungeonKey::EYE_OF_AZSHARA->value,
        DungeonKey::VAULT_OF_THE_WARDENS->value,
        DungeonKey::WINDRUNNER_SPIRE->value,
        DungeonKey::NEXUS_POINT_XENAS->value,
        DungeonKey::THE_ROOKERY->value,
        DungeonKey::KINGS_REST->value,
        DungeonKey::TEMPLE_OF_SETHRALISS->value,
        // MDT ships no group/clone data for Den of Nalorakk's hand-authored packs, so its MDT
        // clone list is far short of KG's enemy count (19 vs 33 for npc 241809 alone).
        DungeonKey::DEN_OF_NALORAKK->value,
    ];

    /**
     * Scenario: Tests that the ingame location is converted correctly from the given map location.
     */
    #[Test]
    #[DataProvider('calculateIngameLocationForMapLocation_givenLatLng_returnsIngameXY_DataProvider')]
    public function calculateIngameLocationForMapLocation_givenLatLng_returnsIngameXY(
        LatLng   $latLng,
        IngameXY $expected,
    ): void {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        // Act
        $result = $coordinatesService->calculateIngameLocationForMapLocation($latLng);

        // Assert
        $this->assertEquals($expected->getX(), $result->getX());
        $this->assertEquals($expected->getY(), $result->getY());
        $this->assertSame($latLng->getFloor(), $result->getFloor());
    }

    /**
     * @return array<int, mixed>
     */
    public static function calculateIngameLocationForMapLocation_givenLatLng_returnsIngameXY_DataProvider(): array
    {
        return [
            [
                new LatLng(
                    CoordinatesService::MAP_MAX_LAT / 2,
                    CoordinatesService::MAP_MAX_LNG / 2,
                    new Floor([
                        'ingame_min_x' => 0,
                        'ingame_max_x' => 100,
                        'ingame_min_y' => 0,
                        'ingame_max_y' => 100,
                    ]),
                ),
                new IngameXY(50, 50),
            ],
            [
                new LatLng(
                    CoordinatesService::MAP_MAX_LAT / 2,
                    CoordinatesService::MAP_MAX_LNG / 2,
                    new Floor([
                        'ingame_min_x' => 100,
                        'ingame_max_x' => 1000,
                        'ingame_min_y' => 100,
                        'ingame_max_y' => 1000,
                    ]),
                ),
                new IngameXY(550, 550),
            ],
            [
                new LatLng(
                    CoordinatesService::MAP_MAX_LAT / 4,
                    CoordinatesService::MAP_MAX_LNG / 4,
                    new Floor([
                        'ingame_min_x' => 100,
                        'ingame_max_x' => 1000,
                        'ingame_min_y' => 100,
                        'ingame_max_y' => 1000,
                    ]),
                ),
                new IngameXY(775, 775),
            ],
            [
                new LatLng(
                    CoordinatesService::MAP_MAX_LAT / 10,
                    CoordinatesService::MAP_MAX_LNG / 4,
                    new Floor([
                        'ingame_min_x' => 100,
                        'ingame_max_x' => 1000,
                        'ingame_min_y' => 50,
                        'ingame_max_y' => 100,
                    ]),
                ),
                new IngameXY(775, 95),
            ],
        ];
    }

    #[Test]
    public function calculateIngameLocationForMapLocation_givenFacadeFloor_throws(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/facade floor/');

        // Act
        $coordinatesService->calculateIngameLocationForMapLocation(new LatLng(-128, 192, $this->createFacadeFloor()));
    }

    #[Test]
    public function calculateIngameLocationForMapLocation_givenNoFloor_throws(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/No floor set/');

        // Act
        $coordinatesService->calculateIngameLocationForMapLocation(new LatLng(-128, 192));
    }

    #[Test]
    public function calculateMapLocationForIngameLocation_givenIngameXY_isTheInverseOfTheForwardConversion(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $latLng             = new LatLng(-64, 96, $this->createTargetFloor());

        // Act
        $ingameXY = $coordinatesService->calculateIngameLocationForMapLocation($latLng);
        $result   = $coordinatesService->calculateMapLocationForIngameLocation($ingameXY);

        // Assert
        $this->assertEqualsWithDelta($latLng->getLat(), $result->getLat(), self::JEST_CLOSE_TO_9_DIGITS);
        $this->assertEqualsWithDelta($latLng->getLng(), $result->getLng(), self::JEST_CLOSE_TO_9_DIGITS);
    }

    #[Test]
    public function calculateMapLocationForIngameLocation_givenFloorWithoutIngameBounds_throws(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $floor              = $this->createFloor(4, 'floor.unmapped', false, [
            'ingame_min_x' => 0,
            'ingame_max_x' => 0,
            'ingame_min_y' => 0,
            'ingame_max_y' => 0,
        ]);

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/does not have ingame coordinates set/');

        // Act
        $coordinatesService->calculateMapLocationForIngameLocation(new IngameXY(0, 0, $floor));
    }

    #[Test]
    public function polygonContainsPoint_givenPointInside_returnsTrue(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        // Act
        $result = $coordinatesService->polygonContainsPoint(new LatLng(-5, 5), self::SQUARE_VERTICES);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function polygonContainsPoint_givenPointOutside_returnsFalse(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        // Act
        $resultRightOfSquare = $coordinatesService->polygonContainsPoint(new LatLng(-5, 15), self::SQUARE_VERTICES);
        $resultAboveSquare   = $coordinatesService->polygonContainsPoint(new LatLng(5, 5), self::SQUARE_VERTICES);

        // Assert
        $this->assertFalse($resultRightOfSquare);
        $this->assertFalse($resultAboveSquare);
    }

    #[Test]
    public function polygonContainsPoint_givenClosedPolygon_doesNotCloseItAgain(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $closed             = [...self::SQUARE_VERTICES, self::SQUARE_VERTICES[0]];

        // Act
        $result = $coordinatesService->polygonContainsPoint(new LatLng(-5, 5), $closed);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function polygonContainsPoint_givenPolygon_doesNotMutateIt(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $square             = self::SQUARE_VERTICES;

        // Act
        $coordinatesService->polygonContainsPoint(new LatLng(-5, 5), $square);

        // Assert
        $this->assertCount(4, $square);
    }

    #[Test]
    public function convertFacadeMapLocationToMapLocation_givenFacadeDisabled_returnsAnUnchangedCopy(): void
    {
        // Arrange
        $facadeFloor        = $this->createFacadeFloor();
        $targetFloor        = $this->createTargetFloor();
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $mappingVersion     = $this->createMappingVersion([$this->createFloorUnion($facadeFloor, $targetFloor)], false);
        $latLng             = new LatLng(-64, 96, $facadeFloor);

        // Act
        $result = $coordinatesService->convertFacadeMapLocationToMapLocation($mappingVersion, $latLng);

        // Assert
        $this->assertNotSame($latLng, $result);
        $this->assertSame(-64.0, $result->getLat());
        $this->assertSame(96.0, $result->getLng());
        $this->assertSame($facadeFloor, $result->getFloor());
    }

    #[Test]
    public function convertFacadeMapLocationToMapLocation_givenPointInsideUnionArea_scalesOntoTheTargetFloor(): void
    {
        // Arrange
        $facadeFloor        = $this->createFacadeFloor();
        $targetFloor        = $this->createTargetFloor();
        $floorUnion         = $this->createFloorUnion($facadeFloor, $targetFloor);
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $mappingVersion     = $this->createMappingVersion([$floorUnion]);
        // The center of the union maps onto the center of the target floor's map
        $latLng = new LatLng($floorUnion->lat, $floorUnion->lng, $facadeFloor);

        // Act
        $result = $coordinatesService->convertFacadeMapLocationToMapLocation($mappingVersion, $latLng);

        // Assert
        $this->assertSame($targetFloor, $result->getFloor());
        $this->assertEqualsWithDelta(CoordinatesService::MAP_MAX_LAT / 2, $result->getLat(), self::JEST_CLOSE_TO_9_DIGITS);
        $this->assertEqualsWithDelta(CoordinatesService::MAP_MAX_LNG / 2, $result->getLng(), self::JEST_CLOSE_TO_9_DIGITS);
    }

    #[Test]
    public function convertFacadeMapLocationToMapLocation_givenPointInDeadSpace_leavesTheFloorAlone(): void
    {
        // Arrange
        $facadeFloor        = $this->createFacadeFloor();
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $mappingVersion     = $this->createMappingVersion([$this->createFloorUnion($facadeFloor, $this->createTargetFloor())]);
        // Bottom right of the facade, outside the union's area
        $latLng = new LatLng(-200, 300, $facadeFloor);

        // Act
        $result = $coordinatesService->convertFacadeMapLocationToMapLocation($mappingVersion, $latLng);

        // Assert
        $this->assertSame($facadeFloor, $result->getFloor());
        $this->assertSame(-200.0, $result->getLat());
        $this->assertSame(300.0, $result->getLng());
    }

    #[Test]
    public function convertFacadeMapLocationToMapLocation_givenTwoUnions_picksTheOneWhoseAreaContainsThePoint(): void
    {
        // Arrange
        $facadeFloor = $this->createFacadeFloor();
        $otherFloor  = $this->createTargetFloor(3, 'floor.other');
        $leftUnion   = $this->createFloorUnion($facadeFloor, $this->createTargetFloor());
        $rightUnion  = $this->createFloorUnion($facadeFloor, $otherFloor, [
            'id'  => 11,
            'lat' => -192,
            'lng' => 288,
        ], 101, self::BOTTOM_RIGHT_QUADRANT_VERTICES);
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $mappingVersion     = $this->createMappingVersion([$leftUnion, $rightUnion]);

        // Act
        $result = $coordinatesService->convertFacadeMapLocationToMapLocation($mappingVersion, new LatLng(-192, 288, $facadeFloor));

        // Assert
        $this->assertSame($otherFloor, $result->getFloor());
    }

    #[Test]
    public function convertFacadeMapLocationToMapLocation_givenRotatedUnion_rotatesBeforeScaling(): void
    {
        // Arrange
        $facadeFloor        = $this->createFacadeFloor();
        $floorUnion         = $this->createFloorUnion($facadeFloor, $this->createTargetFloor(), ['rotation' => 30]);
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $mappingVersion     = $this->createMappingVersion([$floorUnion]);
        $latLng             = new LatLng(-40, 60, $facadeFloor);
        $unionCenter        = new LatLng($floorUnion->lat, $floorUnion->lng);
        $mapCenter          = CoordinatesService::getMapCenterLatLng();

        $rotateThenScale = (clone $latLng)
            ->rotate($unionCenter, $floorUnion->rotation)
            ->scale($unionCenter, $floorUnion->size, $mapCenter, CoordinatesService::MAP_SIZE);
        $scaleThenRotate = (clone $latLng)
            ->scale($unionCenter, $floorUnion->size, $mapCenter, CoordinatesService::MAP_SIZE)
            ->rotate($unionCenter, $floorUnion->rotation);

        // Act
        $result = $coordinatesService->convertFacadeMapLocationToMapLocation($mappingVersion, $latLng);

        // Assert
        $this->assertEqualsWithDelta($rotateThenScale->getLat(), $result->getLat(), self::JEST_CLOSE_TO_9_DIGITS);
        $this->assertEqualsWithDelta($rotateThenScale->getLng(), $result->getLng(), self::JEST_CLOSE_TO_9_DIGITS);
        $this->assertGreaterThanOrEqual(self::JEST_CLOSE_TO_3_DIGITS, abs($scaleThenRotate->getLat() - $result->getLat()));
    }

    #[Test]
    public function convertFacadeMapLocationToMapLocation_givenLatLng_doesNotMutateTheInput(): void
    {
        // Arrange
        $facadeFloor        = $this->createFacadeFloor();
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $mappingVersion     = $this->createMappingVersion([$this->createFloorUnion($facadeFloor, $this->createTargetFloor())]);
        $latLng             = new LatLng(-64, 96, $facadeFloor);

        // Act
        $coordinatesService->convertFacadeMapLocationToMapLocation($mappingVersion, $latLng);

        // Assert
        $this->assertSame(-64.0, $latLng->getLat());
        $this->assertSame(96.0, $latLng->getLng());
        $this->assertSame($facadeFloor, $latLng->getFloor());
    }

    #[Test]
    public function convertFacadeMapLocationToMapLocation_givenForcedFloor_usesThatUnionWithoutCheckingAreas(): void
    {
        // Arrange
        $facadeFloor        = $this->createFacadeFloor();
        $targetFloor        = $this->createTargetFloor();
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $mappingVersion     = $this->createMappingVersion([$this->createFloorUnion($facadeFloor, $targetFloor)]);
        // In dead space, so only the forced floor can produce a conversion
        $latLng = new LatLng(-200, 300, $facadeFloor);

        // Act
        $result = $coordinatesService->convertFacadeMapLocationToMapLocation($mappingVersion, $latLng, $targetFloor);

        // Assert
        $this->assertSame($targetFloor, $result->getFloor());
    }

    #[Test]
    public function convertMapLocationToFacadeMapLocation_givenNoFloorUnions_returnsThePointUnchanged(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $mappingVersion     = $this->createMappingVersion([]);
        $latLng             = new LatLng(-64, 96, $this->createTargetFloor());

        // Act
        $result = $coordinatesService->convertMapLocationToFacadeMapLocation($mappingVersion, $latLng);

        // Assert
        $this->assertSame(-64.0, $result->getLat());
        $this->assertSame(96.0, $result->getLng());
    }

    #[Test]
    public function convertMapLocationToFacadeMapLocation_givenFloorUnion_movesThePointOntoTheFacadeFloor(): void
    {
        // Arrange
        $facadeFloor        = $this->createFacadeFloor();
        $targetFloor        = $this->createTargetFloor();
        $floorUnion         = $this->createFloorUnion($facadeFloor, $targetFloor);
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $mappingVersion     = $this->createMappingVersion([$floorUnion]);

        // Act
        $result = $coordinatesService->convertMapLocationToFacadeMapLocation(
            $mappingVersion,
            new LatLng(CoordinatesService::MAP_MAX_LAT / 2, CoordinatesService::MAP_MAX_LNG / 2, $targetFloor),
        );

        // Assert
        $this->assertSame($facadeFloor, $result->getFloor());
        $this->assertEqualsWithDelta($floorUnion->lat, $result->getLat(), self::JEST_CLOSE_TO_9_DIGITS);
        $this->assertEqualsWithDelta($floorUnion->lng, $result->getLng(), self::JEST_CLOSE_TO_9_DIGITS);
    }

    #[Test]
    #[DataProvider('convertMapLocationToFacadeMapLocation_givenRotation_roundTripsBackToTheSamePoint_DataProvider')]
    public function convertMapLocationToFacadeMapLocation_givenRotation_roundTripsBackToTheSamePoint(int $rotation): void
    {
        // Arrange
        $facadeFloor        = $this->createFacadeFloor();
        $targetFloor        = $this->createTargetFloor();
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $mappingVersion     = $this->createMappingVersion([
            $this->createFloorUnion($facadeFloor, $targetFloor, ['rotation' => $rotation]),
        ]);
        $facadeLatLng = new LatLng(-40, 60, $facadeFloor);

        // Act
        $floorLatLng = $coordinatesService->convertFacadeMapLocationToMapLocation($mappingVersion, $facadeLatLng);
        $result      = $coordinatesService->convertMapLocationToFacadeMapLocation($mappingVersion, $floorLatLng);

        // Assert
        $this->assertSame($targetFloor, $floorLatLng->getFloor());
        $this->assertEqualsWithDelta($facadeLatLng->getLat(), $result->getLat(), self::JEST_CLOSE_TO_9_DIGITS);
        $this->assertEqualsWithDelta($facadeLatLng->getLng(), $result->getLng(), self::JEST_CLOSE_TO_9_DIGITS);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function convertMapLocationToFacadeMapLocation_givenRotation_roundTripsBackToTheSamePoint_DataProvider(): array
    {
        return [
            'rotation 0'   => [0],
            'rotation 30'  => [30],
            'rotation -45' => [-45],
        ];
    }

    #[Test]
    public function intersection_givenCrossingSegments_returnsTheCrossingPoint(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        // Act
        $result = $coordinatesService->intersection(
            new LatLng(0, 0),
            new LatLng(10, 10),
            new LatLng(10, 0),
            new LatLng(0, 10),
        );

        // Assert
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(5, $result->getLat(), self::JEST_CLOSE_TO_9_DIGITS);
        $this->assertEqualsWithDelta(5, $result->getLng(), self::JEST_CLOSE_TO_9_DIGITS);
    }

    #[Test]
    public function intersection_givenParallelSegments_returnsNull(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        // Act
        $result = $coordinatesService->intersection(
            new LatLng(0, 0),
            new LatLng(10, 10),
            new LatLng(0, 5),
            new LatLng(10, 15),
        );

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function intersection_givenSegmentsThatOnlyCrossWhenExtended_returnsNull(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        // Act
        $result = $coordinatesService->intersection(
            new LatLng(0, 0),
            new LatLng(1, 1),
            new LatLng(10, 0),
            new LatLng(0, 10),
        );

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function calculateGridLocationForIngameLocation_givenIngameXY_snapsToTheCellCorner(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        // Act - bounds are 100..1000 on both axes, so a 9x9 grid has 100 unit cells
        $result = $coordinatesService->calculateGridLocationForIngameLocation(new IngameXY(555, 555, $this->createTargetFloor()), 9, 9);

        // Assert
        $this->assertEqualsWithDelta(500, $result->getX(), self::JEST_CLOSE_TO_9_DIGITS);
        $this->assertEqualsWithDelta(500, $result->getY(), self::JEST_CLOSE_TO_9_DIGITS);
    }

    #[Test]
    public function distance_givenTwoLatLngs_returnsTheEuclideanDistance(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        // Act
        $latLngDistance   = $coordinatesService->distance(new LatLng(0, 0), new LatLng(3, 4));
        $ingameXYDistance = $coordinatesService->distanceIngameXY(new IngameXY(0, 0), new IngameXY(3, 4));

        // Assert
        $this->assertEqualsWithDelta(5, $latLngDistance, self::JEST_CLOSE_TO_9_DIGITS);
        $this->assertEqualsWithDelta(5, $ingameXYDistance, self::JEST_CLOSE_TO_9_DIGITS);
    }

    /**
     * Scenario: Tests that mapping versions accuracy vs MDT isn't totally out of whack
     * @throws \Exception
     */
    #[Test]
    #[Group('UsesLua')]
    public function checkConvertMapLocationToFacadeMapLocationAccuracy_GivenMappingVersion_ShouldBeWithinMargin(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        // Every dungeon this test can say anything about: present in MDT, and not one of the
        // manually-checked dungeons that deviate a lot from the MDT data while still being valid.
        $checkableDungeonIds = Dungeon::query()
            ->whereNotIn('key', self::DEVIATES_FROM_MDT_DUNGEON_KEYS)
            ->get()
            ->filter(static fn(Dungeon $dungeon) => Conversion::hasMDTDungeonName($dungeon->key))
            ->pluck('id');

        // Select the mapping version with the highest version number, unique by game_version_id.
        // The uncheckable dungeons are filtered here rather than only in the loop below on purpose:
        // exactly one mapping version per game version is examined, so a dungeon that gets skipped
        // owning the newest one leaves this test asserting nothing at all. That is how it silently
        // stopped covering anything once King's Rest was allowlisted, and it is a live scenario -
        // a dungeon is unmapped in Conversion right up until it is added to DUNGEON_NAME_MAPPING,
        // which is exactly when a new season's mapping is being imported (#3734).
        $mappingVersions = MappingVersion::with(['enemies', 'dungeon'])
            ->whereIn('id', function ($query) use ($checkableDungeonIds) {
                $query->selectRaw('MAX(id)')
                    ->from('mapping_versions')
//                    ->where('id', 606) // temp
                    ->whereIn('dungeon_id', $checkableDungeonIds)
                    ->groupBy('game_version_id');
            })
            ->get();

        $margin      = 20;
        $comparisons = 0;

        foreach ($mappingVersions as $mappingVersion) {
            // Skip dungeons not available in MDT
            if (Conversion::hasMDTDungeonName($mappingVersion->dungeon->key) === false) {
                continue;
            }

            // MDT 6.2 deleted its MistsOfPandaria folder, so there is no MoP mapping left to compare
            // against. Conversion is keyed by dungeon, not by game version, so a dungeon that exists in
            // both MoP Classic and retail (Temple of the Jade Serpent) now resolves to its retail lua -
            // correct for the retail mapping version, meaningless for the MoP one.
            if ($mappingVersion->gameVersion->key === GameVersion::GAME_VERSION_MOP) {
                continue;
            }

            /** @var MappingVersion $mappingVersion */
            $enemies = $mappingVersion->enemies()->with('floor')->get();

            $mdtNpcs = new MDTDungeon(
                ServiceFixtures::getCacheServiceMock($this),
                $coordinatesService,
                $mappingVersion->dungeon,
            )->getMDTNPCs();

            /** @var Collection<string, array{x: float, y: float}> $result */
            $result = collect();
            foreach ($enemies as $enemy) {
                $result->put(
                    $enemy->getUniqueKey(),
                    Conversion::convertLatLngToMDTCoordinate(
                        $coordinatesService->convertMapLocationToFacadeMapLocation(
                            $mappingVersion,
                            $enemy->getLatLng(),
                        ),
                    ),
                );
            }

            // Assert
            foreach ($result as $uniqueKey => $converted) {
                [$npcId, $index] = explode('-', (string)$uniqueKey);

                foreach ($mdtNpcs as $mdtNpc) {
                    if ($mdtNpc->getId() === (int)$npcId) {
                        $clone  = $mdtNpc->getClones()[$index];
                        $cloneX = $clone['x'];
                        $cloneY = $clone['y'];

                        $mvId        = $mappingVersion->id;
                        $dungeonName = __($mappingVersion->dungeon->name);

                        $this->assertGreaterThan(
                            $clone['x'] - $margin,
                            $converted['x'],
                            "X coordinate for NPC ID $npcId (index $index) is too low [MDT XY: [$cloneX, $cloneY], MappingVersion: $mvId, Dungeon: $dungeonName]",
                        );
                        $this->assertLessThan(
                            $clone['x'] + $margin,
                            $converted['x'],
                            "X coordinate for NPC ID $npcId (index $index) is too high [MDT XY: [$cloneX, $cloneY], MappingVersion: $mvId, Dungeon: $dungeonName]",
                        );

                        $this->assertGreaterThan(
                            $clone['y'] - $margin,
                            $converted['y'],
                            "Y coordinate for NPC ID $npcId (index $index) is too low [MDT XY: [$cloneX, $cloneY], MappingVersion: $mvId, Dungeon: $dungeonName]",
                        );
                        $this->assertLessThan(
                            $clone['y'] + $margin,
                            $converted['y'],
                            "Y coordinate for NPC ID $npcId (index $index) is too high [MDT XY: [$cloneX, $cloneY], MappingVersion: $mvId, Dungeon: $dungeonName]",
                        );

                        $comparisons++;

                        break;
                    }
                }
            }
        }

        // Without this the test reports as passing when every mapping version it selected was
        // skipped, having asserted nothing at all - which is exactly how it silently stopped
        // covering anything once a skipped dungeon owned the newest mapping version (#3734).
        $this->assertGreaterThan(
            0,
            $comparisons,
            'No enemy was compared against MDT at all - this test covered nothing',
        );
    }

    /**
     * @param array<string, int> $ingameBounds
     */
    private function createFloor(int $id, string $name, bool $facade, array $ingameBounds = []): Floor
    {
        return new Floor()->forceFill([
            'id'     => $id,
            'name'   => $name,
            'facade' => $facade,
            ...$ingameBounds,
        ]);
    }

    private function createFacadeFloor(): Floor
    {
        return $this->createFloor(self::FACADE_FLOOR_ID, 'floor.facade', true);
    }

    private function createTargetFloor(int $id = self::TARGET_FLOOR_ID, string $name = 'floor.target'): Floor
    {
        return $this->createFloor($id, $name, false, [
            'ingame_min_x' => 100,
            'ingame_max_x' => 1000,
            'ingame_min_y' => 100,
            'ingame_max_y' => 1000,
        ]);
    }

    /**
     * A floor union covering the top left quadrant of the facade, unless overridden.
     *
     * @param array<string, int>                    $overrides
     * @param array<int, array{lat: int, lng: int}> $areaVertices
     */
    private function createFloorUnion(
        Floor $facadeFloor,
        Floor $targetFloor,
        array $overrides = [],
        int   $floorUnionAreaId = 100,
        array $areaVertices = self::TOP_LEFT_QUADRANT_VERTICES,
    ): FloorUnion {
        $floorUnion = new FloorUnion()->forceFill([
            'id'              => 10,
            'floor_id'        => $facadeFloor->id,
            'target_floor_id' => $targetFloor->id,
            'lat'             => -64,
            'lng'             => 96,
            'size'            => 128,
            'rotation'        => 0,
            ...$overrides,
        ]);

        $floorUnionArea = new FloorUnionArea()->forceFill([
            'id'             => $floorUnionAreaId,
            'floor_union_id' => $floorUnion->id,
            'vertices_json'  => json_encode($areaVertices),
        ]);

        return $floorUnion
            ->setRelation('floor', $facadeFloor)
            ->setRelation('targetFloor', $targetFloor)
            ->setRelation('floorUnionAreas', new EloquentCollection([$floorUnionArea]));
    }

    /**
     * The in-memory counterpart of the JS test's createMapContext(): the floor union lookups read
     * from the given unions instead of the database.
     *
     * @param array<int, FloorUnion> $floorUnions
     */
    private function createMappingVersion(array $floorUnions, bool $facadeEnabled = true): MappingVersion
    {
        $floorUnionCollection = new EloquentCollection($floorUnions);

        $mappingVersion = $this->createPartialMockPublic(MappingVersion::class, [
            'getFloorUnionsOnFloor',
            'getFloorUnionsForFloor',
        ]);
        $mappingVersion->method('getFloorUnionsOnFloor')->willReturnCallback(
            static fn(int $floorId): EloquentCollection => $floorUnionCollection->where('floor_id', $floorId)->values(),
        );
        $mappingVersion->method('getFloorUnionsForFloor')->willReturnCallback(
            static fn(Floor $floor): EloquentCollection => $floorUnionCollection->where('target_floor_id', $floor->id)->values(),
        );
        $mappingVersion->facade_enabled = $facadeEnabled;

        return $mappingVersion;
    }
}
