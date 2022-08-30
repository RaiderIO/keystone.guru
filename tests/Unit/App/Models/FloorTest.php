<?php

namespace Tests\Unit\App\Models;

use App\Models\Floor;
use Tests\TestCase;

class FloorTest extends TestCase
{
    /**
     * Scenario: Tests that the ingame location
     *
     * @test
     * @param array $floorAttributes
     * @param array $latLng
     * @param array $expected
     * @return void
     * @dataProvider checkCalculateIngameLocationForMapLocation_GivenLatLng_ShouldReturn_Provider
     * @group
     */
    public function checkCalculateIngameLocationForMapLocation_GivenLatLng_ShouldReturn(array $floorAttributes, array $latLng, array $expected)
    {
        // Arrange
        $floor = new Floor($floorAttributes);

        // Act
        $result = $floor->calculateIngameLocationForMapLocation($latLng['lat'], $latLng['lng']);

        // Assert
        $this->assertEquals($expected['x'], $result['x']);
        $this->assertEquals($expected['y'], $result['y']);
    }

    /**
     * @return array
     */
    public function checkCalculateIngameLocationForMapLocation_GivenLatLng_ShouldReturn_Provider(): array
    {
        return [
            [
                ['ingame_min_x' => 0, 'ingame_max_x' => 100, 'ingame_min_y' => 0, 'ingame_max_y' => 100],
                ['lng' => Floor::MAP_MAX_LNG / 2, 'lat' => Floor::MAP_MAX_LAT / 2],
                ['x' => 50, 'y' => 50],
            ],
            [
                ['ingame_min_x' => 100, 'ingame_max_x' => 1000, 'ingame_min_y' => 100, 'ingame_max_y' => 1000],
                ['lng' => Floor::MAP_MAX_LNG / 2, 'lat' => Floor::MAP_MAX_LAT / 2],
                ['x' => 550, 'y' => 550],
            ],
            [
                ['ingame_min_x' => 100, 'ingame_max_x' => 1000, 'ingame_min_y' => 100, 'ingame_max_y' => 1000],
                ['lng' => Floor::MAP_MAX_LNG / 4, 'lat' => Floor::MAP_MAX_LAT / 4],
                ['x' => 325, 'y' => 325],
            ],
            [
                ['ingame_min_x' => 100, 'ingame_max_x' => 1000, 'ingame_min_y' => 50, 'ingame_max_y' => 100],
                ['lng' => Floor::MAP_MAX_LNG / 4, 'lat' => Floor::MAP_MAX_LAT / 10],
                ['x' => 325, 'y' => 55],
            ],
        ];
    }
}
