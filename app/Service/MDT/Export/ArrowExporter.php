<?php

namespace App\Service\MDT\Export;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Exception\ImportWarning;
use App\Models\Arrow;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;

class ArrowExporter implements MDTObjectExporterInterface
{
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
    ) {
    }

    /**
     * Export arrows as MDT triangle objects.
     *
     * @param  Collection<int, ImportWarning> $warnings
     * @return array<int, mixed>
     */
    public function export(DungeonRoute $dungeonRoute, Collection $warnings): array
    {
        $objects = [];

        foreach ($dungeonRoute->arrows()->with(['floor'])->get() as $arrow) {
            /** @var Arrow $arrow */
            if ($arrow->polyline === null) {
                continue;
            }

            $verticesLatLngs = $arrow->polyline->getDecodedLatLngs($arrow->floor);

            $mdtLine = [
                'd' => [
                    1 => $arrow->polyline->weight,
                    2 => 1,
                    3 => $arrow->floor->mdt_sub_level ?? $arrow->floor->index,
                    4 => true,
                    5 => str_starts_with($arrow->polyline->color, '#') ? substr($arrow->polyline->color, 1) : $arrow->polyline->color,
                    6 => -8,
                    7 => true,
                ],
                'l' => [],
                't' => [],
            ];

            $vertexIndex            = 1;
            $firstMdtCoordinates    = null;
            $lastMdtCoordinates     = null;
            $previousMdtCoordinates = null;

            foreach ($verticesLatLngs as $vertexLatLng) {
                if ($dungeonRoute->mappingVersion->facade_enabled) {
                    $vertexLatLng = $this->coordinatesService->convertMapLocationToFacadeMapLocation(
                        $dungeonRoute->mappingVersion,
                        $vertexLatLng,
                    );

                    $mdtLine['d'][3] = $vertexLatLng->getFloor()->mdt_sub_level ?? $vertexLatLng->getFloor()->index;
                }

                $mdtCoordinates = Conversion::convertLatLngToMDTCoordinateString($vertexLatLng);

                $firstMdtCoordinates ??= $mdtCoordinates;
                $lastMdtCoordinates = $mdtCoordinates;

                if ($previousMdtCoordinates !== null) {
                    $mdtLine['l'][$vertexIndex++] = $previousMdtCoordinates['x'];
                    $mdtLine['l'][$vertexIndex++] = $previousMdtCoordinates['y'];
                    $mdtLine['l'][$vertexIndex++] = $mdtCoordinates['x'];
                    $mdtLine['l'][$vertexIndex++] = $mdtCoordinates['y'];
                }

                $previousMdtCoordinates = $mdtCoordinates;
            }

            // Compute arrow direction from shaft: atan2(dy, dx) of the last segment
            // ($lastMdtCoordinates is non-null whenever $firstMdtCoordinates is - both are assigned each iteration)
            if ($firstMdtCoordinates !== null) {
                $dx              = (float)$lastMdtCoordinates['x'] - (float)$firstMdtCoordinates['x'];
                $dy              = (float)$lastMdtCoordinates['y'] - (float)$firstMdtCoordinates['y'];
                $mdtLine['t'][1] = atan2($dy, $dx);
            }

            $objects[] = $mdtLine;
        }

        return $objects;
    }
}
