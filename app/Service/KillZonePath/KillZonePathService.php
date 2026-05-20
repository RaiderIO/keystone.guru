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
     * @return array<array<array{lat: float, lng: float, floor_id: int|null}>>
     */
    public function calculateForRoute(DungeonRoute $dungeonRoute, bool $useFacade): array
    {
        $dungeonRoute->loadMissing(['dungeon', 'mappingVersion']);

        /** @var Collection<DungeonFloorSwitchMarker> $allMarkers */
        $allMarkers = DungeonFloorSwitchMarker::where('mapping_version_id', $dungeonRoute->mapping_version_id)
            ->with('floor')
            ->get();

        $killZones = $dungeonRoute->killZones()
            ->with(['floor'])
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
        if ($dungeonStart !== null && $dungeonStart->floor !== null) {
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
            if ($marker->floor === null || $marker->floor->facade) {
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

        if (empty($killZoneNodeIds)) {
            return [];
        }

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
        foreach ($allMarkers as $marker) {
            if ($marker->linked_dungeon_floor_switch_marker_id === null) {
                continue;
            }

            $linkedMarker = $markersById->get($marker->linked_dungeon_floor_switch_marker_id);
            if ($linkedMarker === null || $linkedMarker->floor === null) {
                continue;
            }

            $fromId  = sprintf('fsm_%d', $marker->id);
            $toId    = sprintf('fsm_%d', $linkedMarker->id);
            $edges[] = new PathEdge($fromId, $toId, 0);
        }

        // --- Compute segments ---
        $nodeIdList = array_values($killZoneNodeIds);
        $segments   = [];

        // Dungeon start → first kill zone
        if (isset($nodes['start'])) {
            $path = $this->pathFindingService->findShortestPath($nodes, $edges, 'start', $nodeIdList[0]);
            if (!empty($path)) {
                $segments[] = $path;
            }
        }

        // Consecutive kill-zone pairs
        for ($i = 0; $i < count($nodeIdList) - 1; $i++) {
            $path = $this->pathFindingService->findShortestPath($nodes, $edges, $nodeIdList[$i], $nodeIdList[$i + 1]);
            if (!empty($path)) {
                $segments[] = $path;
            }
        }

        return $this->convertSegmentsToOutput($segments, $dungeonRoute, $useFacade);
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
