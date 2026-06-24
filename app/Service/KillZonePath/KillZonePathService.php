<?php

namespace App\Service\KillZonePath;

use App\Logic\Structs\LatLng;
use App\Logic\Structs\PathEdge;
use App\Logic\Structs\PathNode;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\PathFinding\PathFindingServiceInterface;
use Illuminate\Support\Collection;

class KillZonePathService implements KillZonePathServiceInterface
{
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        private readonly PathFindingServiceInterface $pathFindingService,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function calculateForRoute(DungeonRoute $dungeonRoute, bool $useFacade): array
    {
        [$nodes, $edges, $killZoneNodeIds] = $this->loadAndBuildGraph($dungeonRoute);

        if (empty($killZoneNodeIds)) {
            return [];
        }

        $segments = $this->computePathSegments($nodes, $edges, $killZoneNodeIds);

        return $this->convertSegmentsToOutput($segments, $dungeonRoute, $useFacade);
    }

    /**
     * {@inheritDoc}
     */
    public function findPathsToKillZones(DungeonRoute $dungeonRoute): array
    {
        [$nodes, $edges, $killZoneNodeIds] = $this->loadAndBuildGraph($dungeonRoute);

        if (empty($killZoneNodeIds)) {
            return [];
        }

        $killZoneIds = array_keys($killZoneNodeIds);
        $nodeIdList  = array_values($killZoneNodeIds);
        $result      = [];

        // Dungeon start → first kill zone
        $result[$killZoneIds[0]] = isset($nodes['start'])
            ? $this->pathFindingService->findShortestPath($nodes, $edges, 'start', $nodeIdList[0])
            : [];

        // Consecutive kill-zone pairs
        for ($i = 1; $i < count($nodeIdList); $i++) {
            $result[$killZoneIds[$i]] = $this->pathFindingService->findShortestPath(
                $nodes,
                $edges,
                $nodeIdList[$i - 1],
                $nodeIdList[$i],
            );
        }

        return $result;
    }

    /**
     * Loads all required data from the database and constructs the pathfinding graph.
     *
     * @return array{array<string, PathNode>, PathEdge[], array<int, string>}
     */
    private function loadAndBuildGraph(DungeonRoute $dungeonRoute): array
    {
        $dungeonRoute->loadMissing(['dungeon', 'mappingVersion']);

        /** @var Collection<int, DungeonFloorSwitchMarker> $allMarkers */
        $allMarkers = DungeonFloorSwitchMarker::where('mapping_version_id', $dungeonRoute->mapping_version_id)
            ->with('floor')
            ->get();

        $killZones = $dungeonRoute->killZones()
            ->with(['floor', 'killZoneEnemies'])
            ->orderBy('index')
            ->get();

        $dungeonStart = MapIcon::where('mapping_version_id', $dungeonRoute->mapping_version_id)
            ->where('map_icon_type_id', MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START])
            ->with('floor')
            ->first();

        /** @var array<string, PathNode> $nodes */
        $nodes = [];
        /** @var PathEdge[] $edges */
        $edges = [];

        // --- Dungeon start node ---
        if ($dungeonStart !== null) {
            $startLatLng    = $dungeonStart->getLatLng();
            $nodes['start'] = new PathNode(
                'start',
                $startLatLng,
                $this->coordinatesService->calculateIngameLocationForMapLocation($startLatLng),
            );
        }

        // --- Floor-switch marker nodes ---
        /** @var Collection<int, DungeonFloorSwitchMarker> $markersById */
        $markersById = $allMarkers->keyBy('id');
        foreach ($allMarkers as $marker) {
            if ($marker->floor->facade) {
                continue;
            }

            $id         = sprintf('fsm_%d', $marker->id);
            $latLng     = new LatLng($marker->lat, $marker->lng, $marker->floor);
            $nodes[$id] = new PathNode(
                $id,
                $latLng,
                $this->coordinatesService->calculateIngameLocationForMapLocation($latLng),
            );
        }

        // --- Kill-zone nodes ---
        /** @var array<int, string> $killZoneNodeIds Kill-zone ID → node ID, in index order */
        $killZoneNodeIds = [];
        foreach ($killZones as $killZone) {
            $killLocation = $killZone->getKillLocation();
            if ($killLocation === null || $killLocation->getFloor() === null) {
                continue;
            }

            $id         = sprintf('kz_%d', $killZone->id);
            $nodes[$id] = new PathNode(
                $id,
                $killLocation,
                $this->coordinatesService->calculateIngameLocationForMapLocation($killLocation),
            );
            $killZoneNodeIds[$killZone->id] = $id;
        }

        if (!empty($killZoneNodeIds)) {
            // --- Same-floor edges (bidirectional, weighted by ingame distance) ---
            /** @var array<int, PathNode[]> $nodesByFloor */
            $nodesByFloor = [];
            foreach ($nodes as $node) {
                $floorId = $node->latLng->getFloor()?->id;
                if ($floorId !== null) {
                    $nodesByFloor[$floorId][] = $node;
                }
            }

            foreach ($nodesByFloor as $floorNodes) {
                $count = count($floorNodes);
                for ($i = 0; $i < $count; $i++) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        $a       = $floorNodes[$i];
                        $b       = $floorNodes[$j];
                        $weight  = $this->coordinatesService->distanceIngameXY($a->ingameXY, $b->ingameXY);
                        $edges[] = new PathEdge($a->id, $b->id, $weight);
                        $edges[] = new PathEdge($b->id, $a->id, $weight);
                    }
                }
            }

            // --- Cross-floor edges via linked floor-switch marker pairs (weight 0) ---
            // A null linked_dungeon_floor_switch_marker_id indicates a one-way marker: no cross-floor
            // edge is added, so pathfinding cannot traverse through it in that direction. To make a
            // switch one-way in the admin map, set linked_dungeon_floor_switch_marker_id to null on
            // the marker whose direction of travel should be blocked.
            foreach ($allMarkers as $marker) {
                if ($marker->linked_dungeon_floor_switch_marker_id === null) {
                    continue;
                }

                $linkedMarker = $markersById->get($marker->linked_dungeon_floor_switch_marker_id);
                if ($linkedMarker === null) {
                    continue;
                }

                $fromId  = sprintf('fsm_%d', $marker->id);
                $toId    = sprintf('fsm_%d', $linkedMarker->id);
                $edges[] = new PathEdge($fromId, $toId, 0);
            }
        }

        return [$nodes, $edges, $killZoneNodeIds];
    }

    /**
     * Computes ordered path segments between consecutive kill zones for frontend rendering.
     * Empty paths are excluded.
     *
     * @param array<string, PathNode> $nodes
     * @param PathEdge[]              $edges
     * @param array<int, string>      $killZoneNodeIds
     *
     * @return LatLng[][]
     */
    private function computePathSegments(array $nodes, array $edges, array $killZoneNodeIds): array
    {
        $nodeIdList = array_values($killZoneNodeIds);
        $segments   = [];

        if (isset($nodes['start'])) {
            $path = $this->pathFindingService->findShortestPath($nodes, $edges, 'start', $nodeIdList[0]);
            if (!empty($path)) {
                $segments[] = $path;
            }
        }

        for ($i = 0; $i < count($nodeIdList) - 1; $i++) {
            $path = $this->pathFindingService->findShortestPath($nodes, $edges, $nodeIdList[$i], $nodeIdList[$i + 1]);
            if (!empty($path)) {
                $segments[] = $path;
            }
        }

        return $segments;
    }

    /**
     * @param LatLng[][] $segments
     *
     * @return array<array<array{lat: float, lng: float, floor_id: int|null}>>
     */
    private function convertSegmentsToOutput(array $segments, DungeonRoute $dungeonRoute, bool $useFacade): array
    {
        $result = [];
        foreach ($segments as $segment) {
            $points = [];
            foreach ($segment as $latLng) {
                if ($useFacade) {
                    $latLng = $this->coordinatesService->convertMapLocationToFacadeMapLocation(
                        $dungeonRoute->mappingVersion,
                        $latLng,
                    );
                }
                $points[] = $latLng->toArrayWithFloor();
            }
            $result[] = $points;
        }

        return $result;
    }
}
