<?php

namespace App\Service\MDT\Export;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Exception\ImportWarning;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\Export\Traits\ConvertsHtmlToMdtComment;
use Illuminate\Support\Collection;

class KillZoneDescriptionExporter implements MDTObjectExporterInterface
{
    use ConvertsHtmlToMdtComment;

    /** @var int How far away do we create notes in MDT */
    private const int KILL_ZONE_DESCRIPTION_DISTANCE = 3;

    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
    ) {
    }

    /**
     * For each kill zone, extract its description as an MDT note object.
     *
     * @param  Collection<int, ImportWarning> $warnings
     * @return array<int, mixed>
     */
    public function export(DungeonRoute $dungeonRoute, Collection $warnings): array
    {
        $objects = [];

        $dungeonRoute->loadMissing(['killZones.enemies.floor', 'killZones.floor']);

        foreach ($dungeonRoute->killZones as $killZone) {
            if (!isset($killZone->description)) {
                continue;
            }

            $floor  = $killZone->getDominantFloor();
            $latLng = $killZone->getEnemiesBoundingBoxNorthEdgeMiddleCoordinate(self::KILL_ZONE_DESCRIPTION_DISTANCE);

            // Maybe the pull had no enemies
            if ($latLng === null) {
                continue;
            }

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
                    3 => $floor->mdt_sub_level ?? $floor->index,
                    4 => true,
                    5 => $this->convertHtmlToMdtComment($killZone->description),
                    // MDT does not support HTML tags - get rid of them.
                ],
            ];
        }

        return $objects;
    }
}
