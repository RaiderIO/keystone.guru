<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;

interface MapDrawingServiceInterface
{
    /**
     * @param array<int, mixed>                    $latLngs
     * @param array<string, mixed>                 $connectionAttributes
     * @param array<string, mixed>                 $polylineAttributes
     * @param array<int, array<int|string, mixed>> $gradient
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
                // Green
                '#00FF00',
            ],
            [
                50,
                // Blue (avoid orangy tints since the map is brown/orange)
                '#0000BB',
            ],
            [
                100,
                // Red
                '#FF0000',
            ],
        ],
    ): void;
}
