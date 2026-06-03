<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Models\Brushline;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Polyline;
use App\Service\MDT\MDTExportStringServiceInterface;
use App\Service\MDT\MDTImportStringServiceInterface;
use Illuminate\Support\Collection;
use Tests\Feature\Traits\GeneratesDungeonRoutes;

abstract class MDTImportStringServiceTestBase extends MDTExportStringServiceTestBase
{
    use GeneratesDungeonRoutes;

    /**
     * Returns enemies that are guaranteed to survive an import round-trip.
     * Filters out teeming-only enemies, MDT placeholders, and seasonally-restricted
     * enemies that would be skipped by the import service based on route conditions.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Enemy>
     */
    protected function getSafeMdtEnemies(DungeonRoute $dungeonRoute, int $limit = 1): \Illuminate\Database\Eloquent\Collection
    {
        return $dungeonRoute->mappingVersion->enemies()
            ->whereNotNull('mdt_id')
            ->where(fn($q) => $q->where('teeming', '!=', Enemy::TEEMING_VISIBLE)->orWhereNull('teeming'))
            ->where(fn($q) => $q->where('seasonal_type', '!=', Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER)->orWhereNull('seasonal_type'))
            ->whereNull('seasonal_index')
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    protected function exportDungeonRouteToString(DungeonRoute $dungeonRoute): string
    {
        return app()->make(MDTExportStringServiceInterface::class)
            ->setDungeonRoute($dungeonRoute)
            ->getEncodedString(new Collection());
    }

    protected function importStringToDungeonRoute(string $encodedString, bool $assignNotesToPulls = false): DungeonRoute
    {
        return app()->make(MDTImportStringServiceInterface::class)
            ->setEncodedString($encodedString)
            ->getDungeonRoute(
                warnings: new Collection(),
                errors: new Collection(),
                sandbox: true,
                save: false,
                assignNotesToPulls: $assignNotesToPulls,
            );
    }

    protected function createBrushlineForRoute(DungeonRoute $dungeonRoute): Brushline
    {
        $floor = $dungeonRoute->dungeon->floors()->first();

        $brushline = Brushline::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor->id,
            'polyline_id'      => -1,
        ]);

        $polyline = Polyline::create([
            'model_id'      => $brushline->id,
            'model_class'   => Brushline::class,
            'color'         => '#FF0000',
            'weight'        => 2,
            'vertices_json' => json_encode([
                ['lat' => -100.0, 'lng' => 200.0],
                ['lat' => -150.0, 'lng' => 250.0],
            ]),
        ]);

        $brushline->update(['polyline_id' => $polyline->id]);

        return $brushline;
    }
}
