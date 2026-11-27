<?php

namespace App\Service\WalkGraph;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\EnemyPack;
use App\Models\Mapping\MappingVersion;
use App\Models\WalkGraph\EnemyPackFloorSwitchWalkDistance;
use App\Models\WalkGraph\EnemyPackWalkDistance;
use App\Models\WalkGraph\FloorSwitchMarkerWalkDistance;
use App\Repositories\Interfaces\WalkGraph\EnemyPackFloorSwitchWalkDistanceRepositoryInterface;
use App\Repositories\Interfaces\WalkGraph\EnemyPackWalkDistanceRepositoryInterface;
use App\Repositories\Interfaces\WalkGraph\FloorSwitchMarkerWalkDistanceRepositoryInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;

class PackDistanceService implements PackDistanceServiceInterface
{
    public function __construct(
        private readonly CoordinatesServiceInterface                         $coordinatesService,
        private readonly EnemyPackWalkDistanceRepositoryInterface            $enemyPackWalkDistanceRepository,
        private readonly EnemyPackFloorSwitchWalkDistanceRepositoryInterface $enemyPackFloorSwitchWalkDistanceRepository,
        private readonly FloorSwitchMarkerWalkDistanceRepositoryInterface    $floorSwitchMarkerWalkDistanceRepository,
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

        $floorSwitchGraph = $this->getFloorSwitchGraph($mappingVersion, $enemyPacks, $centroids);

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
                        'unverified'              => $walkDistance === null,
                        'floor_switch_marker_ids' => [],
                    ];
                }

                array_push($neighbours, ...$this->getCrossFloorNeighbours($floorSwitchGraph, $enemyPack, $enemyPacksByFloor));

                usort($neighbours, static fn(array $a, array $b) => $a['distance'] <=> $b['distance']);

                $result[$enemyPack->id] = array_slice($neighbours, 0, $neighbourCount);
            }
        }

        return $result;
    }

    /**
     * The floor switch markers of the mapping version as a graph: the walk between each pack and the markers on its
     * floor, between the markers of one floor, and a one-way edge from each marker to the marker it is linked to.
     *
     * @param  Collection<int, EnemyPack>                                                                                                                                                             $enemyPacks
     * @param  array<int, IngameXY>                                                                                                                                                                   $centroids  enemy pack id => centroid
     * @return array{packEdges: array<int, array<int, array{distance: float, unverified: bool}>>, markerEdges: array<int, array<int, array{distance: float, unverified: bool, isFloorSwitch: bool}>>} enemy pack id or marker id => marker id => the walk
     */
    private function getFloorSwitchGraph(MappingVersion $mappingVersion, Collection $enemyPacks, array $centroids): array
    {
        /** @var Collection<int, DungeonFloorSwitchMarker> $markers */
        $markers = $mappingVersion->dungeonFloorSwitchMarkers()
            ->with('floor')
            ->get()
            ->filter(static fn(DungeonFloorSwitchMarker $marker) => !$marker->floor->facade)
            ->keyBy('id');

        $graph = ['packEdges' => [], 'markerEdges' => []];
        if ($markers->isEmpty()) {
            return $graph;
        }

        $markerPositions = $markers->map(fn(DungeonFloorSwitchMarker $marker) => $this->coordinatesService->calculateIngameLocationForMapLocation(
            new LatLng($marker->lat, $marker->lng, $marker->floor),
        ));
        $markersByFloor = $markers->groupBy('floor_id');

        $packMarkerWalkDistances = $this->enemyPackFloorSwitchWalkDistanceRepository->getByMappingVersionId($mappingVersion->id)
            ->filter(static fn(EnemyPackFloorSwitchWalkDistance $distance) => $distance->walk_distance !== null)
            ->mapWithKeys(static fn(EnemyPackFloorSwitchWalkDistance $distance) => [
                sprintf('%d-%d', $distance->enemy_pack_id, $distance->dungeon_floor_switch_marker_id) => $distance->walk_distance,
            ]);

        foreach ($enemyPacks as $enemyPack) {
            if (!isset($centroids[$enemyPack->id])) {
                continue;
            }

            foreach ($markersByFloor->get($enemyPack->floor_id, collect()) as $marker) {
                $walkDistance = $packMarkerWalkDistances->get(sprintf('%d-%d', $enemyPack->id, $marker->id));

                $graph['packEdges'][$enemyPack->id][$marker->id] = [
                    'distance'   => $walkDistance ?? $this->coordinatesService->distanceIngameXY($centroids[$enemyPack->id], $markerPositions[$marker->id]),
                    'unverified' => $walkDistance === null,
                ];
            }
        }

        $markerWalkDistances = $this->floorSwitchMarkerWalkDistanceRepository->getByMappingVersionId($mappingVersion->id)
            ->filter(static fn(FloorSwitchMarkerWalkDistance $distance) => $distance->walk_distance !== null)
            ->mapWithKeys(static fn(FloorSwitchMarkerWalkDistance $distance) => [
                self::getPairKey($distance->dungeon_floor_switch_marker_id, $distance->other_dungeon_floor_switch_marker_id) => $distance->walk_distance,
            ]);

        foreach ($markersByFloor as $floorMarkers) {
            foreach ($floorMarkers as $marker) {
                foreach ($floorMarkers as $otherMarker) {
                    if ($otherMarker->id === $marker->id) {
                        continue;
                    }

                    $walkDistance = $markerWalkDistances->get(self::getPairKey($marker->id, $otherMarker->id));

                    $graph['markerEdges'][$marker->id][$otherMarker->id] = [
                        'distance'      => $walkDistance ?? $this->coordinatesService->distanceIngameXY($markerPositions[$marker->id], $markerPositions[$otherMarker->id]),
                        'unverified'    => $walkDistance === null,
                        'isFloorSwitch' => false,
                    ];
                }
            }
        }

        // A marker without a link is one-way: it can be arrived at, but not taken
        $floorSwitchCost = (float)config('keystoneguru.walk_graph.floor_switch_cost');
        foreach ($markers as $marker) {
            if ($marker->linked_dungeon_floor_switch_marker_id !== null && $markers->has($marker->linked_dungeon_floor_switch_marker_id)) {
                $graph['markerEdges'][$marker->id][$marker->linked_dungeon_floor_switch_marker_id] = [
                    'distance'      => $floorSwitchCost,
                    'unverified'    => false,
                    'isFloorSwitch' => true,
                ];
            }
        }

        return $graph;
    }

    /**
     * The shortest walk from the pack to each pack on another floor, through the floor switch markers.
     *
     * @param  array{packEdges: array<int, array<int, array{distance: float, unverified: bool}>>, markerEdges: array<int, array<int, array{distance: float, unverified: bool, isFloorSwitch: bool}>>} $floorSwitchGraph
     * @param  Collection<int, Collection<int, EnemyPack>>                                                                                                                                            $enemyPacksByFloor
     * @return list<array{enemy_pack_id: int, distance: float, unverified: bool, floor_switch_marker_ids: list<int>}>
     */
    private function getCrossFloorNeighbours(array $floorSwitchGraph, EnemyPack $enemyPack, Collection $enemyPacksByFloor): array
    {
        $packEdges = $floorSwitchGraph['packEdges'];
        if (!isset($packEdges[$enemyPack->id])) {
            return [];
        }

        $markerWalks = $this->getMarkerWalks($floorSwitchGraph['markerEdges'], $packEdges[$enemyPack->id]);

        $neighbours = [];
        foreach ($enemyPacksByFloor as $floorId => $floorEnemyPacks) {
            if ($floorId === $enemyPack->floor_id) {
                continue;
            }

            foreach ($floorEnemyPacks as $otherEnemyPack) {
                $best = null;
                foreach ($packEdges[$otherEnemyPack->id] ?? [] as $markerId => $edge) {
                    if (!isset($markerWalks[$markerId])) {
                        continue;
                    }

                    $distance = $markerWalks[$markerId]['distance'] + $edge['distance'];
                    if ($best === null || $distance < $best['distance']) {
                        $best = [
                            'distance'                => $distance,
                            'unverified'              => $markerWalks[$markerId]['unverified'] || $edge['unverified'],
                            'floor_switch_marker_ids' => $markerWalks[$markerId]['floorSwitchMarkerIds'],
                        ];
                    }
                }

                if ($best !== null) {
                    $neighbours[] = [
                        'enemy_pack_id'           => $otherEnemyPack->id,
                        'distance'                => round($best['distance'], 1),
                        'unverified'              => $best['unverified'],
                        'floor_switch_marker_ids' => $best['floor_switch_marker_ids'],
                    ];
                }
            }
        }

        return $neighbours;
    }

    /**
     * Dijkstra over the floor switch markers, starting from the walks of one pack to the markers on its floor.
     *
     * @param  array<int, array<int, array{distance: float, unverified: bool, isFloorSwitch: bool}>> $markerEdges
     * @param  array<int, array{distance: float, unverified: bool}>                                  $startEdges  marker id => the walk from the pack to it
     * @return array<int, array{distance: float, unverified: bool, floorSwitchMarkerIds: list<int>}> marker id => the shortest walk to it, and the markers taken on the way
     */
    private function getMarkerWalks(array $markerEdges, array $startEdges): array
    {
        $open = [];
        foreach ($startEdges as $markerId => $edge) {
            $open[$markerId] = ['distance' => $edge['distance'], 'unverified' => $edge['unverified'], 'floorSwitchMarkerIds' => []];
        }

        $done = [];
        while ($open !== []) {
            $markerId = array_key_first($open);
            foreach ($open as $candidateId => $candidate) {
                if ($candidate['distance'] < $open[$markerId]['distance']) {
                    $markerId = $candidateId;
                }
            }

            $walk = $done[$markerId] = $open[$markerId];
            unset($open[$markerId]);

            foreach ($markerEdges[$markerId] ?? [] as $nextMarkerId => $edge) {
                $distance = $walk['distance'] + $edge['distance'];
                if (isset($done[$nextMarkerId]) || (isset($open[$nextMarkerId]) && $open[$nextMarkerId]['distance'] <= $distance)) {
                    continue;
                }

                $open[$nextMarkerId] = [
                    'distance'             => $distance,
                    'unverified'           => $walk['unverified'] || $edge['unverified'],
                    'floorSwitchMarkerIds' => $edge['isFloorSwitch'] ? [...$walk['floorSwitchMarkerIds'], $markerId] : $walk['floorSwitchMarkerIds'],
                ];
            }
        }

        return $done;
    }

    private static function getPairKey(int $enemyPackId, int $otherEnemyPackId): string
    {
        return sprintf('%d-%d', min($enemyPackId, $otherEnemyPackId), max($enemyPackId, $otherEnemyPackId));
    }
}
