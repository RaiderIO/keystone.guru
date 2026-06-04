<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\Brushline;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Polyline;
use App\Service\MDT\MDTExportStringServiceInterface;
use App\Service\MDT\MDTImportStringServiceInterface;
use Illuminate\Support\Collection;
use Tests\Feature\Traits\GeneratesDungeonRoutes;

abstract class MDTImportStringServiceTestBase extends MDTExportStringServiceTestBase
{
    use GeneratesDungeonRoutes;

    protected function exportDungeonRouteToString(DungeonRoute $dungeonRoute, ?Collection $warnings = null): string
    {
        return app()->make(MDTExportStringServiceInterface::class)
            ->setDungeonRoute($dungeonRoute)
            ->getEncodedString($warnings ?? new Collection());
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
