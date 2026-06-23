<?php

namespace App\Service\DungeonRoute;

use App\Logic\Structs\LatLng;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\EnemyPatrol;
use App\Models\Path;
use App\Models\Polyline;

class MapDrawingService implements MapDrawingServiceInterface
{
    /**
     * @param  DungeonRoute                         $dungeonRoute
     * @param  array<int, LatLng>                   $latLngs
     * @param  array<string, mixed>                 $connectionAttributes
     * @param  array<string, mixed>                 $polylineAttributes
     * @param  bool                                 $drawAsPatrols
     * @param  array<int, array<int|string, mixed>> $gradient
     * @return void
     */
    public function drawConnections(
        DungeonRoute $dungeonRoute,
        array        $latLngs,
        array        $connectionAttributes = [],
        array        $polylineAttributes = [],
        bool         $drawAsPatrols = false,
        array        $gradient = [
            [
                0,
                '#00FF00',
            ],
            [
                50,
                '#0000BB',
            ],
            [
                100,
                '#FF0000',
            ],
        ],
    ): void {
        if (empty($latLngs)) {
            return;
        }

        $weightStep = 100 / count($latLngs);

        // Save all polylines
        $currentWeight  = 0;
        $polyLines      = [];
        $previousLatLng = $latLngs[0];
        $modelClass     = $drawAsPatrols ? EnemyPatrol::class : Path::class;

        for ($i = 1; $i < count($latLngs); $i++) {
            $latLng             = $latLngs[$i];
            $polyLineAttributes = array_merge($polylineAttributes, [
                'model_class'   => $modelClass,
                'color'         => pickHexFromHandlers($gradient, $currentWeight += $weightStep),
                'vertices_json' => json_encode([
                    $previousLatLng->toArray(),
                    $latLng->toArray(),
                ]),
            ]);

            $previousLatLng = $latLng;

            $polyLines[] = Polyline::create($polyLineAttributes);
        }

        // Save all paths (as enemy patrols, so I can see them in the mapping version admin) and couple the polylines to them
        $i = 0;
        foreach ($polyLines as $polyLine) {
            $latLng = $latLngs[$i];

            $attributes = [
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $latLng->getFloor()->id,
                'polyline_id'      => $polyLine->id,
            ];

            if ($drawAsPatrols) {
                $model = EnemyPatrol::create(array_merge($connectionAttributes, $attributes, [
                    'mapping_version_id' => $dungeonRoute->mapping_version_id,
                ]));
            } else {
                $model = Path::create(array_merge($connectionAttributes, $attributes));
            }

            $polyLine->update([
                'model_id' => $model->id,
            ]);

            $i++;
        }
    }
}
