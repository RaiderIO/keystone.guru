<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Logic\Structs\LatLng;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\Path;
use App\Models\Polyline;
use App\Service\DungeonRoute\MapDrawingServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MapDrawingService')]
final class MapDrawingServiceTest extends PublicTestCase
{
    #[Test]
    public function drawConnections_givenThreePoints_savesAPathPerConnectionCoupledToItsPolyline(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = DungeonRoute::factory()->create(['expires_at' => null]);
            /** @var Floor $floor */
            $floor   = $dungeonRoute->dungeon->floors()->firstOrFail();
            $latLngs = [
                new LatLng(-100, 100, $floor),
                new LatLng(-110, 110, $floor),
                new LatLng(-120, 120, $floor),
            ];

            // Act
            app(MapDrawingServiceInterface::class)->drawConnections($dungeonRoute, $latLngs);

            // Assert
            $paths = Path::query()->where('dungeon_route_id', $dungeonRoute->id)->get();
            $this->assertCount(2, $paths);
            foreach ($paths as $path) {
                /** @var Path $path */
                $this->assertSame($path->id, Polyline::query()->findOrFail($path->polyline_id)->model_id);
            }
        } finally {
            if ($dungeonRoute !== null) {
                $polylineIds = Path::query()->where('dungeon_route_id', $dungeonRoute->id)->pluck('polyline_id');
                Path::query()->where('dungeon_route_id', $dungeonRoute->id)->delete();
                Polyline::query()->whereIn('id', $polylineIds)->delete();
                $dungeonRoute->delete();
            }
        }
    }
}
