<?php

namespace App\Service\WalkGraph;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\EnemyPack;
use App\Models\Mapping\MappingVersion;
use App\Models\WalkGraph\EnemyPackWalkDistance;
use App\Repositories\Interfaces\WalkGraph\EnemyPackWalkDistanceRepositoryInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;

class PackDistanceService implements PackDistanceServiceInterface
{
    public function __construct(
        private readonly CoordinatesServiceInterface              $coordinatesService,
        private readonly EnemyPackWalkDistanceRepositoryInterface $enemyPackWalkDistanceRepository,
    ) {
    }

    public function getPackCentroids(Collection $enemyPacks): array
    {
        $centroids = [];
        foreach ($enemyPacks as $enemyPack) {
            if ($enemyPack->enemies->isEmpty()) {
                continue;
            }

            $sumX = 0.0;
            $sumY = 0.0;
            foreach ($enemyPack->enemies as $enemy) {
                $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation(
                    new LatLng($enemy->lat, $enemy->lng, $enemyPack->floor),
                );
                $sumX += $ingameXY->getX();
                $sumY += $ingameXY->getY();
            }

            $enemyCount                = $enemyPack->enemies->count();
            $centroids[$enemyPack->id] = new IngameXY($sumX / $enemyCount, $sumY / $enemyCount, $enemyPack->floor);
        }

        return $centroids;
    }

    public function getNeighbours(MappingVersion $mappingVersion, Collection $enemyPacks, int $neighbourCount): array
    {
        $centroids     = $this->getPackCentroids($enemyPacks);
        $walkDistances = $this->enemyPackWalkDistanceRepository->getByMappingVersionId($mappingVersion->id)
            ->filter(static fn(EnemyPackWalkDistance $distance) => $distance->walk_distance !== null)
            ->mapWithKeys(static fn(EnemyPackWalkDistance $distance) => [
                self::getPairKey($distance->enemy_pack_id, $distance->other_enemy_pack_id) => $distance->walk_distance,
            ]);

        /** @var Collection<int, Collection<int, EnemyPack>> $enemyPacksByFloor */
        $enemyPacksByFloor = $enemyPacks->filter(static fn(EnemyPack $enemyPack) => isset($centroids[$enemyPack->id]))
            ->groupBy('floor_id');

        $result = [];
        foreach ($enemyPacksByFloor as $floorEnemyPacks) {
            foreach ($floorEnemyPacks as $enemyPack) {
                $neighbours = [];
                foreach ($floorEnemyPacks as $otherEnemyPack) {
                    if ($otherEnemyPack->id === $enemyPack->id) {
                        continue;
                    }

                    $walkDistance = $walkDistances->get(self::getPairKey($enemyPack->id, $otherEnemyPack->id));
                    $neighbours[] = [
                        'enemy_pack_id' => $otherEnemyPack->id,
                        'distance'      => round($walkDistance ?? $this->coordinatesService->distanceIngameXY(
                            $centroids[$enemyPack->id],
                            $centroids[$otherEnemyPack->id],
                        ), 1),
                        'unverified' => $walkDistance === null,
                    ];
                }

                usort($neighbours, static fn(array $a, array $b) => $a['distance'] <=> $b['distance']);

                $result[$enemyPack->id] = array_slice($neighbours, 0, $neighbourCount);
            }
        }

        return $result;
    }

    private static function getPairKey(int $enemyPackId, int $otherEnemyPackId): string
    {
        return sprintf('%d-%d', min($enemyPackId, $otherEnemyPackId), max($enemyPackId, $otherEnemyPackId));
    }
}
