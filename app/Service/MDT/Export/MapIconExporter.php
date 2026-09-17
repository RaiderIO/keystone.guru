<?php

namespace App\Service\MDT\Export;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Exception\ImportWarning;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\MapIcon;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\Export\Traits\ConvertsHtmlToMdtComment;
use Illuminate\Support\Collection;

class MapIconExporter implements MDTObjectExporterInterface
{
    use ConvertsHtmlToMdtComment;

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

        foreach ($dungeonRoute->mapicons()->with(['floor'])->get() as $mapIcon) {
            /** @var MapIcon $mapIcon */
            $latLng = $mapIcon->getLatLng();
            if ($dungeonRoute->mappingVersion->facade_enabled) {
                $latLng = $this->coordinatesService->convertMapLocationToFacadeMapLocation(
                    $dungeonRoute->mappingVersion,
                    $latLng,
                );
            }

            $mdtCoordinates = Conversion::convertLatLngToMDTCoordinateString($latLng);

            $objects[] = [
                'n' => true,
                'd' => [
                    1 => $mdtCoordinates['x'],
                    2 => $mdtCoordinates['y'],
                    3 => $latLng->getFloor()->mdt_sub_level ?? $latLng->getFloor()->index,
                    4 => true,
                    5 => $this->convertHtmlToMdtComment($mapIcon->comment ?? __($mapIcon->mapIconType?->name) ?? ''), // @phpstan-ignore nullsafe.neverNull, nullCoalesce.expr
                ],
            ];
        }

        return $objects;
    }
}
