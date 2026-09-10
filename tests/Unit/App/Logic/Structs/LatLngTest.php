<?php

namespace Tests\Unit\App\Logic\Structs;

use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Service\Coordinates\CoordinatesService;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Every test here has a same-named case in resources/assets/js/custom/structs/latlng.test.js with the
 * same inputs and expectations, so the PHP struct and its JS port are held to identical results.
 */
#[Group('LatLng')]
final class LatLngTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('scale_givenPositiveMapCoordinates_shouldScaleLatLng_DataProvider')]
    public function scale_givenPositiveMapCoordinates_shouldScaleLatLng(
        LatLng $latLng,
        LatLng $expected,
    ): void {
        // Arrange
        $currentCenter  = new LatLng(50, 50);
        $currentMapSize = 100;
        $targetCenter   = new LatLng(100, 100);
        $targetMapSize  = 200;

        // Act
        $result = $latLng->scale($currentCenter, $currentMapSize, $targetCenter, $targetMapSize);

        // Assert
        Assert::assertEquals($expected->getLat(), $result->getLat());
        Assert::assertEquals($expected->getLng(), $result->getLng());
    }

    /**
     * @return array<int, mixed>
     */
    public static function scale_givenPositiveMapCoordinates_shouldScaleLatLng_DataProvider(): array
    {
        return [
            [
                new LatLng(25, 25),
                new LatLng(50, 50),
            ],
            [
                new LatLng(75, 75),
                new LatLng(150, 150),
            ],
        ];
    }

    #[Test]
    #[DataProvider('scale_givenRealisticMapCoordinates_shouldScaleLatLng_DataProvider')]
    public function scale_givenRealisticMapCoordinates_shouldScaleLatLng(
        LatLng $latLng,
        LatLng $expected,
    ): void {
        // Arrange
        $currentCenter  = new LatLng(-181.69, 273.31);
        $currentMapSize = 200;
        $targetCenter   = new LatLng(CoordinatesService::MAP_MAX_LAT / 2, CoordinatesService::MAP_MAX_LNG / 2);
        $targetMapSize  = CoordinatesService::MAP_SIZE;

        // Act
        $result = $latLng->scale($currentCenter, $currentMapSize, $targetCenter, $targetMapSize);

        // Assert
        Assert::assertEquals($expected->getLat(), $result->getLat());
        Assert::assertEquals($expected->getLng(), $result->getLng());
    }

    /**
     * @return array<int, mixed>
     */
    public static function scale_givenRealisticMapCoordinates_shouldScaleLatLng_DataProvider(): array
    {
        return [
            [
                new LatLng(-195, 210),
                new LatLng(-145.0368, 110.9632),
            ],
        ];
    }

    #[Test]
    #[DataProvider('rotate_givenPositiveMapCoordinates_shouldRotateLatLng_DataProvider')]
    public function rotate_givenPositiveMapCoordinates_shouldRotateLatLng(
        LatLng $latLng,
        int    $rotation,
        LatLng $expected,
    ): void {
        // Arrange
        $currentCenter = new LatLng(50, 50);

        // Act
        $result = $latLng->rotate($currentCenter, $rotation);

        // Assert
        Assert::assertEquals($expected->getLat(), $result->getLat());
        Assert::assertEquals($expected->getLng(), $result->getLng());
    }

    /**
     * @return array<int, mixed>
     */
    public static function rotate_givenPositiveMapCoordinates_shouldRotateLatLng_DataProvider(): array
    {
        return [
            // Top left to top right
            [
                new LatLng(25, 25),
                90,
                new LatLng(25, 75),
            ],
            // Top left to dead middle
            [
                new LatLng(25, 25),
                45,
                new LatLng(14.644660940672622, 50),
            ],
        ];
    }

    #[Test]
    #[DataProvider('rotate_givenScaledMapCoordinates_shouldRotateLatLng_DataProvider')]
    public function rotate_givenScaledMapCoordinates_shouldRotateLatLng(
        LatLng $latLng,
        int    $rotation,
        LatLng $expected,
    ): void {
        // Arrange
        $currentCenter  = new LatLng(50, 50);
        $currentMapSize = 100;
        $targetCenter   = new LatLng(100, 100);
        $targetMapSize  = 200;

        // Act
        $result = $latLng->scale($currentCenter, $currentMapSize, $targetCenter, $targetMapSize)
            ->rotate($targetCenter, $rotation);

        // Assert
        Assert::assertEquals($expected->getLat(), $result->getLat());
        Assert::assertEquals($expected->getLng(), $result->getLng());
    }

    /**
     * @return array<int, mixed>
     */
    public static function rotate_givenScaledMapCoordinates_shouldRotateLatLng_DataProvider(): array
    {
        return [
            // Top left to top right
            [
                new LatLng(25, 25),
                90,
                new LatLng(50, 150),
            ],
            // Top left to dead middle
            //            [
            //                new LatLng(25, 25),
            //                45,
            //                new LatLng(14.644660940672622, 50),
            //            ],
        ];
    }

    #[Test]
    public function clone_givenLatLng_returnsIndependentCopyKeepingTheFloor(): void
    {
        // Arrange
        $floor  = new Floor()->forceFill(['id' => 12]);
        $latLng = new LatLng(-100, 200, $floor);

        // Act
        $clone = clone $latLng;
        $clone->setLat(-50);

        // Assert
        Assert::assertSame(-100.0, $latLng->getLat());
        Assert::assertSame($floor, $clone->getFloor());
    }

    #[Test]
    public function getLat_givenPrecisionAndNegativeHalfway_roundsAwayFromZeroLikePhp(): void
    {
        // Arrange
        $latLng = new LatLng(-1.5, 1.5);

        // Act
        $lat = $latLng->getLat(0);
        $lng = $latLng->getLng(0);

        // Assert
        Assert::assertSame(-2.0, $lat);
        Assert::assertSame(2.0, $lng);
    }

    #[Test]
    public function toArrayWithFloor_givenNoFloor_returnsNullFloorId(): void
    {
        // Arrange
        $latLng = new LatLng(-1, 2);

        // Act
        $result = $latLng->toArrayWithFloor();

        // Assert
        Assert::assertSame(['lat' => -1.0, 'lng' => 2.0, 'floor_id' => null], $result);
    }
}
