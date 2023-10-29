<?php

namespace Tests\Unit\App\Logic\Structs;

use App\Logic\Structs\LatLng;
use App\Service\Coordinates\CoordinatesService;
use PHPUnit\Framework\Assert;
use Tests\PublicTestCase;

class LatLngTest extends PublicTestCase
{
    /**
     * @test
     * @group LatLng
     *
     * @param LatLng $latLng
     * @param LatLng $expected
     * @return void
     * @dataProvider scale_givenPositiveMapCoordinates_shouldScaleLatLng_DataProvider
     */
    public function scale_givenPositiveMapCoordinates_shouldScaleLatLng(
        LatLng $latLng,
        LatLng $expected
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
     * @return array
     */
    public function scale_givenPositiveMapCoordinates_shouldScaleLatLng_DataProvider(): array
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


    /**
     * @test
     * @group LatLng
     *
     * @param LatLng $latLng
     * @param LatLng $expected
     * @return void
     * @dataProvider scale_givenRealisticMapCoordinates_shouldScaleLatLng_DataProvider
     */
    public function scale_givenRealisticMapCoordinates_shouldScaleLatLng(
        LatLng $latLng,
        LatLng $expected
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
     * @return array
     */
    public function scale_givenRealisticMapCoordinates_shouldScaleLatLng_DataProvider(): array
    {
        return [
            [
                new LatLng(-195, 210),
                new LatLng(-145.0368, 110.9632),
            ],
//            [
//                new LatLng(75, 75),
//                new LatLng(150, 150),
//            ],
        ];
    }
}
