<?php

namespace App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Exception\InvalidMDTDungeonException;
use App\Logic\MDT\Exception\InvalidMDTExpansionException;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\Mapping\MappingVersion;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;

class MDTMappingVersionService implements MDTMappingVersionServiceInterface
{
    public function __construct(
        private CacheServiceInterface       $cacheService,
        private CoordinatesServiceInterface $coordinatesService,
    ) {
    }

    /**
     * @throws InvalidMDTDungeonException
     * @throws InvalidMDTExpansionException
     * @throws \Exception
     */
    public function getMappingVersionAccuracy(MappingVersion $mappingVersion): ?Collection
    {
        $result = collect();
        foreach ($mappingVersion->dungeon->floors as $floor) {
            if ($floor->facade) {
                continue;
            }

            $result->put($floor->id, $this->getFloorAccuracy($mappingVersion, $floor));
        }

        // Remove any floors that resulted in null accuracy
        return $result->filter();
    }

    /**
     * Average accuracy (0–100) for a specific floor within this mapping version.
     * Returns null if not MDT-supported or if no enemies matched on the floor.
     */
    public function getFloorAccuracy(MappingVersion $mappingVersion, Floor $floor, ?float $floorUnionSizeOverride = null): ?float
    {
        // Skip dungeons not available in MDT
        if (Conversion::hasMDTDungeonName($mappingVersion->dungeon->key) === false) {
            return null;
        }

        $floorUnion = null;
        if ($floorUnionSizeOverride !== null) {
            /** @var FloorUnion $floorUnion */
            $floorUnion = $mappingVersion->getFloorUnionsForFloor($floor)->first();

            $floorUnion->size = $floorUnionSizeOverride;
        }

        // Max size the enemy can be off before we consider it 0% accurate. Which is 10 units
        $maxOffsetSquared = 10 * 10;

        /** @var Collection<Enemy> $enemies */
        $enemies = $mappingVersion->enemies()
            ->where('floor_id', $floor->id)
            ->with('floor')
            ->get();

        if ($enemies->isEmpty()) {
            return null;
        }

        $mdtNpcs = (new MDTDungeon(
            $this->cacheService,
            $this->coordinatesService,
            $mappingVersion->dungeon,
        ))->getMDTNPCs();

        $totalAccuracy = 0.0;
        $count         = 0;

        foreach ($enemies as $enemy) {
            $convertedLatLng = $mappingVersion->facade_enabled
                ? $this->coordinatesService->convertMapLocationToFacadeMapLocation($mappingVersion, $enemy->getLatLng(), $floorUnion)
                : $enemy->getLatLng();

            $converted = Conversion::convertLatLngToMDTCoordinate($convertedLatLng);

            [$npcId, $index] = explode('-', $enemy->getUniqueKey());

            foreach ($mdtNpcs as $mdtNpc) {
                if ($mdtNpc->getId() === (int)$npcId) {
                    if (!isset($mdtNpc->getClones()[$index])) {
                        // TODO structured logging
                        logger()->warning('No clone found for MDT NPC ' . $mdtNpc->getId() . ' and index ' . $index);
                        continue;
                    }
                    $clone = $mdtNpc->getClones()[$index];

                    $accuracy = max(0, (100 - (($this->getDistanceSquared(
                        $clone,
                        $converted,
                    ) / $maxOffsetSquared) * 100)));

                    if ($accuracy > 0) {
                        $totalAccuracy += $accuracy;
                        $count++;
                    }

                    break;
                }
            }
        }

        if ($count === 0) {
            return null;
        }

        return $totalAccuracy / $count;
    }

    private function getDistance(array $xy1, array $xy2): float
    {
        return sqrt($this->getDistanceSquared($xy1, $xy2));
    }

    private function getDistanceSquared(array $xy1, array $xy2): float
    {
        return pow($xy1['x'] - $xy2['x'], 2) + pow($xy1['y'] - $xy2['y'], 2);
    }
}
