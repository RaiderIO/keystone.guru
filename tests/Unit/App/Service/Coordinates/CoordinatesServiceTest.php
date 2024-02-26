<?php

namespace Tests\Unit\App\Service\Coordinates;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Service\Coordinates\CoordinatesService;
use Tests\TestCases\PublicTestCase;
use Tests\Unit\Fixtures\ServiceFixtures;

final class CoordinatesServiceTest extends PublicTestCase
{
    /**
     * Scenario: Tests that the ingame location
     *
     * @return void
     */
    #[Test]
    #[DataProvider('checkCalculateIngameLocationForMapLocation_GivenLatLng_ShouldReturn_Provider')]
    #[Group('')]
    public function checkCalculateIngameLocationForMapLocation_GivenLatLng_ShouldReturn(LatLng $latLng, IngameXY $expected): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        // Act
        $result = $coordinatesService->calculateIngameLocationForMapLocation($latLng);

        // Assert
        $this->assertEquals($expected->getX(), $result->getX());
        $this->assertEquals($expected->getY(), $result->getY());
    }

    /**
     * @return array
     */
    public static function checkCalculateIngameLocationForMapLocation_GivenLatLng_ShouldReturn_Provider(): array
    {
        return [
            [
                new LatLng(CoordinatesService::MAP_MAX_LAT / 2, CoordinatesService::MAP_MAX_LNG / 2,
                    new Floor(['ingame_min_x' => 0, 'ingame_max_x' => 100, 'ingame_min_y' => 0, 'ingame_max_y' => 100])
                ),
                new IngameXY(50, 50),
            ],
            [
                new LatLng(CoordinatesService::MAP_MAX_LAT / 2, CoordinatesService::MAP_MAX_LNG / 2,
                    new Floor(['ingame_min_x' => 100, 'ingame_max_x' => 1000, 'ingame_min_y' => 100, 'ingame_max_y' => 1000])
                ),
                new IngameXY(550, 550),
            ],
            [
                new LatLng(CoordinatesService::MAP_MAX_LAT / 4, CoordinatesService::MAP_MAX_LNG / 4,
                    new Floor(['ingame_min_x' => 100, 'ingame_max_x' => 1000, 'ingame_min_y' => 100, 'ingame_max_y' => 1000])
                ),
                new IngameXY(775, 775),
            ],
            [
                new LatLng(CoordinatesService::MAP_MAX_LAT / 10, CoordinatesService::MAP_MAX_LNG / 4,
                    new Floor(['ingame_min_x' => 100, 'ingame_max_x' => 1000, 'ingame_min_y' => 50, 'ingame_max_y' => 100])
                ),
                new IngameXY(775, 95),
            ],
        ];
    }
}
