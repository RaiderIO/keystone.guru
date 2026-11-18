<?php

namespace Tests\Feature\App\Models\DungeonRoute;

use App\Logic\Structs\LatLng;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\MapIcon;
use App\Service\Coordinates\CoordinatesServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRoute')]
#[Group('DungeonRouteMapContextMapIcons')]
final class DungeonRouteMapContextMapIconsTest extends PublicTestCase
{
    #[Test]
    public function mapContextMapIcons_givenFacadeEnabledMappingVersion_returnsConvertedMapIcons(): void
    {
        $route   = null;
        $mapIcon = null;

        try {
            // Arrange
            $route = DungeonRoute::factory()->create();
            $route->mappingVersion->facade_enabled = true;
            $floor   = $route->dungeon->floors->first();
            $mapIcon = $this->createMapIcon($route, $floor->id);

            $coordinatesService = $this->createMockPublic(CoordinatesServiceInterface::class);
            $coordinatesService->method('convertMapLocationToFacadeMapLocation')
                ->willReturn(new LatLng(-50.0, 50.0, $floor));

            // Act
            $mapIcons = $route->mapContextMapIcons($coordinatesService, true);

            // Assert
            $this->assertCount(1, $mapIcons);
            $this->assertEquals(-50.0, $mapIcons->first()->lat);
            $this->assertEquals(50.0, $mapIcons->first()->lng);
        } finally {
            $mapIcon?->delete();
            $route?->delete();
        }
    }

    #[Test]
    public function mapContextMapIcons_givenFacadeDisabledMappingVersion_returnsUnconvertedMapIcons(): void
    {
        $route   = null;
        $mapIcon = null;

        try {
            // Arrange
            $route = DungeonRoute::factory()->create();
            $route->mappingVersion->facade_enabled = false;
            $floor   = $route->dungeon->floors->first();
            $mapIcon = $this->createMapIcon($route, $floor->id);

            $coordinatesService = $this->createMockPublic(CoordinatesServiceInterface::class);
            $coordinatesService->method('convertMapLocationToFacadeMapLocation')
                ->willReturn(new LatLng(-50.0, 50.0, $floor));

            // Act
            $mapIcons = $route->mapContextMapIcons($coordinatesService, true);

            // Assert
            $this->assertCount(1, $mapIcons);
            $this->assertEquals(-100.0, $mapIcons->first()->lat);
            $this->assertEquals(100.0, $mapIcons->first()->lng);
            $this->assertSame($floor->id, $mapIcons->first()->floor_id);
        } finally {
            $mapIcon?->delete();
            $route?->delete();
        }
    }

    private function createMapIcon(DungeonRoute $route, int $floorId): MapIcon
    {
        return MapIcon::factory()->create([
            'dungeon_route_id' => $route->id,
            'floor_id'         => $floorId,
            'lat'              => -100.0,
            'lng'              => 100.0,
        ]);
    }
}
