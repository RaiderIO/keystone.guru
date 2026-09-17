<?php

namespace App\Service\MDT\Export;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Exception\ImportWarning;
use App\Models\Brushline;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Path;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;

class LineExporter implements MDTObjectExporterInterface
{
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
    ) {
    }

    /**
     * @param  Collection<int, ImportWarning> $warnings
     * @return array<int, mixed>
     */
    public function export(DungeonRoute $dungeonRoute, Collection $warnings): array
    {
        $objects = [];

        /** @var Collection<int, Path|Brushline> $brushlines */
        $brushlines = $dungeonRoute->brushlines()->with(['floor'])->get()->toBase();

        $lines = $brushlines->merge(
            $dungeonRoute->paths()->with(['floor'])->get()->toBase(),
        );

        foreach ($lines as $line) {
            /** @var Path|Brushline $line */
            $mdtLine = [
                'd' => [
                    1 => $line->polyline->weight,
                    2 => 1,
                    3 => $line->floor->mdt_sub_level ?? $line->floor->index,
                    4 => true,
                    5 => str_starts_with($line->polyline->color, '#') ? substr($line->polyline->color, 1) : $line->polyline->color,
                    6 => -8,
                    7 => true,
                ],
                'l' => [],
            ];

            if ($line instanceof Brushline) {
                $mdtLine['d'][7] = true;
            }

            $vertexIndex            = 1;
            $verticesLatLngs        = $line->polyline->getDecodedLatLngs($line->floor);
            $previousMdtCoordinates = null;

            foreach ($verticesLatLngs as $vertexLatLng) {
                if ($dungeonRoute->mappingVersion->facade_enabled) {
                    $vertexLatLng = $this->coordinatesService->convertMapLocationToFacadeMapLocation(
                        $dungeonRoute->mappingVersion,
                        $vertexLatLng,
                    );

                    // The floor of the line should be updated too
                    $mdtLine['d'][3] = $vertexLatLng->getFloor()->mdt_sub_level ?? $vertexLatLng->getFloor()->index;
                }

                $mdtCoordinates = Conversion::convertLatLngToMDTCoordinateString($vertexLatLng);

                if ($previousMdtCoordinates !== null) {
                    // We must do A -> B, B -> C, C -> D. I don't know why he wants the previous coordinates too, but alas that's how it works
                    $mdtLine['l'][$vertexIndex++] = $previousMdtCoordinates['x'];
                    $mdtLine['l'][$vertexIndex++] = $previousMdtCoordinates['y'];
                    $mdtLine['l'][$vertexIndex++] = $mdtCoordinates['x'];
                    $mdtLine['l'][$vertexIndex++] = $mdtCoordinates['y'];
                }

                $previousMdtCoordinates = $mdtCoordinates;
            }

            $objects[] = $mdtLine;
        }

        return $objects;
    }
}
