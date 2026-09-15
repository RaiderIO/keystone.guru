<?php

namespace Tests\Feature\App\Models\DungeonRoute;

use App\Logic\Structs\LatLng;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRoute')]
#[Group('DungeonRouteMapContextKillZones')]
final class DungeonRouteMapContextKillZonesTest extends PublicTestCase
{
    #[Test]
    public function mapContextKillZones_givenFacade_returnsConvertedKillZonesAndLeavesTheRoutesOwnUntouched(): void
    {
        $route = null;

        try {
            // Arrange
            $route = DungeonRoute::factory()->create();
            $floor = $route->dungeon->floors->first();
            KillZone::create([
                'dungeon_route_id' => $route->id,
                'floor_id'         => $floor->id,
                'color'            => '#ff0000',
                'index'            => 1,
                'lat'              => -100.0,
                'lng'              => 100.0,
            ]);
            $route->load('killZones');

            $coordinatesService = $this->createMockPublic(CoordinatesServiceInterface::class);
            $coordinatesService->method('convertMapLocationToFacadeMapLocation')
                ->willReturn(new LatLng(-50.0, 50.0, $floor));

            // Act
            $killZones = $route->mapContextKillZones($coordinatesService, true);

            // Assert
            $this->assertEquals(-50.0, $killZones->first()->lat);
            $this->assertEquals(50.0, $killZones->first()->lng);
            $this->assertEquals(-100.0, $route->killZones->first()->lat);
            $this->assertEquals(100.0, $route->killZones->first()->lng);
        } finally {
            $route?->delete();
        }
    }

    #[Test]
    public function mapContextKillZones_givenKillZonePathsCalculatedFirst_runsNoKillZoneQuery(): void
    {
        $route = null;

        try {
            // Arrange
            $route = DungeonRoute::factory()->create();
            foreach ([1, 2] as $index) {
                KillZone::create([
                    'dungeon_route_id' => $route->id,
                    'color'            => '#ff0000',
                    'index'            => $index,
                ]);
            }

            app(KillZonePathServiceInterface::class)->calculateForRoute($route, false);

            $killZoneQueries = 0;
            DB::listen(static function (QueryExecuted $query) use (&$killZoneQueries): void {
                if (str_contains($query->sql, 'from `kill_zones`')) {
                    $killZoneQueries++;
                }
            });

            // Act - CI runs with the model cache on, which would answer these queries without reaching the database
            $killZones = app('model-cache')->runDisabled(
                static fn() => $route->mapContextKillZones(app(CoordinatesServiceInterface::class), false),
            );

            // Assert
            $this->assertSame(0, $killZoneQueries);
            $this->assertSame([1, 2], $killZones->pluck('index')->all());
        } finally {
            $route?->delete();
        }
    }
}
