<?php

namespace App\Service\KillZonePath;

use App\Features\WalkGraphKillZonePaths;
use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Logic\Structs\PathEdge;
use App\Logic\Structs\PathNode;
use App\Logic\Structs\PathStep;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonTransport;
use App\Models\Faction;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\PathFinding\PathFindingServiceInterface;
use App\Service\WalkGraph\WalkGraphServiceInterface;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature as Pennant;

class KillZonePathService implements KillZonePathServiceInterface
{
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        private readonly PathFindingServiceInterface $pathFindingService,
        private readonly WalkGraphServiceInterface   $walkGraphService,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function calculateForRoute(DungeonRoute $dungeonRoute, bool $useFacade): array
    {
        [$nodes, $edges, $killZoneNodeIds] = $this->loadAndBuildGraph(
            $dungeonRoute,
            Pennant::active(WalkGraphKillZonePaths::class),
        );

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
        [$nodes, $edges, $killZoneNodeIds] = $this->loadAndBuildGraph($dungeonRoute, true);

        if (empty($killZoneNodeIds)) {
            return [];
        }

        $killZoneIds = array_keys($killZoneNodeIds);
        $nodeIdList  = array_values($killZoneNodeIds);
        $result      = [];

        // Dungeon start → first kill zone
        $result[$killZoneIds[0]] = isset($nodes['start'])
            ? $this->pathFindingService->findShortestPathSteps($nodes, $edges, 'start', $nodeIdList[0])
            : [];

        // Consecutive kill-zone pairs
        for ($i = 1; $i < count($nodeIdList); $i++) {
            $result[$killZoneIds[$i]] = $this->pathFindingService->findShortestPathSteps(
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
     * @param bool $useWalkGraph Weight same-floor edges by the floor's walk graph where it connects both nodes, and
     *                           route them along the walked path. Otherwise every same-floor edge is a straight line.
     *
     * @return array{array<string, PathNode>, PathEdge[], array<int, string>}
     */
    private function loadAndBuildGraph(DungeonRoute $dungeonRoute, bool $useWalkGraph): array
    {
        $dungeonRoute->loadMissing(['dungeon', 'mappingVersion', 'faction']);

        /** @var Collection<int, DungeonFloorSwitchMarker> $allMarkers */
        $allMarkers = DungeonFloorSwitchMarker::where('mapping_version_id', $dungeonRoute->mapping_version_id)
            ->with('floor')
            ->get();

        $transports = $this->getTransportsForRoute($dungeonRoute);

        $killZones = $dungeonRoute->killZones()
            ->with(['floor', 'enemies.floor', 'enemies.npc'])
            ->orderBy('index')
            ->get();
        $dungeonRoute->setRelation('killZones', $killZones);

        $dungeonStart = $dungeonRoute->getDungeonStart();

        /** @var array<string, PathNode> $nodes */
        $nodes = [];
        /** @var PathEdge[] $edges */
        $edges = [];

        // --- Dungeon start node ---
        if ($dungeonStart !== null) {
            $startLatLng = $dungeonStart->getLatLng();

            // Same facade-floor guard as the kill-zone nodes below (#3917) - a stale
            // dungeon_start_id can point at a start left on an old mapping version's
            // facade floor. A missing start node is already an anticipated state everywhere this is
            // consumed (both findPathsToKillZones() and computePathSegments() check isset()).
            if ($startLatLng->getFloor()?->facade !== true) {
                $nodes['start'] = new PathNode(
                    'start',
                    $startLatLng,
                    $this->coordinatesService->calculateIngameLocationForMapLocation($startLatLng),
                );
            }
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

        // --- Transport nodes ---
        foreach ($transports as $transport) {
            $id         = sprintf('dt_%d', $transport->id);
            $latLng     = new LatLng($transport->lat, $transport->lng, $transport->floor);
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
            // A facade floor's coordinates aren't real ingame coordinates, so
            // calculateIngameLocationForMapLocation() below rejects them - mirrors the floor-switch
            // marker skip above (#3917). Excluding the kill zone from the graph entirely (rather than
            // resolving its real floor) matches that same precedent: this is the collaborative-editing
            // combined view, not stored data the path visualization needs to trust.
            if ($killLocation === null || $killLocation->getFloor() === null || $killLocation->getFloor()->facade) {
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
            // --- Same-floor edges (bidirectional, weighted by walking distance, else ingame distance) ---
            /** @var array<int, PathNode[]> $nodesByFloor */
            $nodesByFloor = [];
            foreach ($nodes as $node) {
                $floorId = $node->latLng->getFloor()?->id;
                if ($floorId !== null) {
                    $nodesByFloor[$floorId][] = $node;
                }
            }

            $routeNodeIds = array_values($killZoneNodeIds);
            if (isset($nodes['start'])) {
                array_unshift($routeNodeIds, 'start');
            }

            /** @var array<string, array<string, true>> $routeLegs node id => node ids it is a leg with, either way round */
            $routeLegs = [];
            for ($i = 1; $i < count($routeNodeIds); $i++) {
                $routeLegs[$routeNodeIds[$i - 1]][$routeNodeIds[$i]] = true;
                $routeLegs[$routeNodeIds[$i]][$routeNodeIds[$i - 1]] = true;
            }

            $maxDetourRatio      = (float)config('keystoneguru.walk_graph.max_detour_ratio');
            $straightLinePenalty = (float)config('keystoneguru.walk_graph.straight_line_penalty');
            foreach ($nodesByFloor as $floorNodes) {
                $count = count($floorNodes);
                /** @var list<array{string, string}> $legs */
                $legs = [];
                for ($i = 0; $i < $count; $i++) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        if ($this->isLeg($routeLegs, $floorNodes[$i]->id, $floorNodes[$j]->id)) {
                            $legs[] = [$floorNodes[$i]->id, $floorNodes[$j]->id];
                        }
                    }
                }

                // Searching the walk graph for every pair instead cost ~220 ms on a 78 pull route
                $walkPaths = $useWalkGraph ? $this->getWalkPaths($floorNodes, $legs) : [];

                for ($i = 0; $i < $count; $i++) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        $a        = $floorNodes[$i];
                        $b        = $floorNodes[$j];
                        $walkPath = $walkPaths[$a->id][$b->id] ?? null;
                        // On a walked floor a path only takes its own legs: each pull to the next, and to and from
                        // floor switches. Straight lines over a third pull would otherwise cut through walls
                        if ($walkPaths !== [] && !$this->isLeg($routeLegs, $a->id, $b->id)) {
                            continue;
                        }

                        $straightDistance = $this->coordinatesService->distanceIngameXY($a->ingameXY, $b->ingameXY);
                        // A walk far longer than the straight line means the graph misses a way through, not a detour
                        if ($walkPath !== null && $walkPath['distance'] > $straightDistance * $maxDetourRatio) {
                            $walkPath = null;
                        }

                        if ($walkPath === null) {
                            // On a walked floor a straight leg crosses ground the walk graph could not confirm
                            $weight  = $walkPaths === [] ? $straightDistance : $straightDistance * $straightLinePenalty;
                            $edges[] = new PathEdge($a->id, $b->id, $weight);
                            $edges[] = new PathEdge($b->id, $a->id, $weight);
                        } else {
                            $waypoints = $this->convertWalkPathToWaypoints($walkPath['path']);
                            $edges[]   = new PathEdge($a->id, $b->id, $walkPath['distance'], $waypoints);
                            $edges[]   = new PathEdge($b->id, $a->id, $walkPath['distance'], array_reverse($waypoints));
                        }
                    }
                }
            }

            // --- Cross-floor edges via linked floor-switch marker pairs ---
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
                $edges[] = new PathEdge($fromId, $toId, (float)config('keystoneguru.walk_graph.floor_switch_cost'));
            }

            // --- Transport edges, one per linked pair direction ---
            // A transport is mounted travel over the distance between its ends, weighed as the yards walked in that time
            $walkedYardsPerMountedYard = (float)config('keystoneguru.character.default_movement_speed_yards_second') /
                (float)config('keystoneguru.character.mounted_movement_speed_yards_second');
            foreach ($transports as $transport) {
                $fromId = sprintf('dt_%d', $transport->id);
                $toId   = sprintf('dt_%d', $transport->linked_dungeon_transport_id);
                if (!isset($nodes[$toId])) {
                    continue;
                }

                $distance = $this->coordinatesService->distanceIngameXY($nodes[$fromId]->ingameXY, $nodes[$toId]->ingameXY);
                $edges[]  = new PathEdge($fromId, $toId, $distance * $walkedYardsPerMountedYard, [], true);
            }
        }

        return [$nodes, $edges, $killZoneNodeIds];
    }

    /**
     * @param array<string, array<string, true>> $routeLegs node id => node ids it is a leg of the route with
     */
    private function isLeg(array $routeLegs, string $nodeId, string $otherNodeId): bool
    {
        return isset($routeLegs[$nodeId][$otherNodeId]) ||
            str_starts_with($nodeId, 'fsm_') ||
            str_starts_with($otherNodeId, 'fsm_') ||
            str_starts_with($nodeId, 'dt_') ||
            str_starts_with($otherNodeId, 'dt_');
    }

    /**
     * @return Collection<int, DungeonTransport> The transports of the route's mapping version that the route's faction
     *                                           can take within the dungeon, off the facade floor.
     */
    private function getTransportsForRoute(DungeonRoute $dungeonRoute): Collection
    {
        $factionKey = $dungeonRoute->faction?->key;

        return DungeonTransport::query()
            ->where('mapping_version_id', $dungeonRoute->mapping_version_id)
            ->whereNull('target_dungeon_id')
            ->when(
                $factionKey !== null && $factionKey !== Faction::FACTION_UNSPECIFIED,
                static fn($query) => $query->whereIn('faction', [Faction::FACTION_ANY, $factionKey]),
            )
            ->with('floor')
            ->get()
            ->reject(static fn(DungeonTransport $transport): bool => $transport->floor === null || $transport->floor->facade)
            ->values();
    }

    /**
     * @param PathNode[]                  $floorNodes nodes that are all on the same floor
     * @param list<array{string, string}> $legs       the pairs of nodes to walk
     *
     * @return array<string, array<string, array{distance: float, path: list<IngameXY>}>> node id => node id => the walk
     */
    private function getWalkPaths(array $floorNodes, array $legs): array
    {
        $floor = $floorNodes[0]->latLng->getFloor();
        if ($floor === null) {
            return [];
        }

        $points = [];
        foreach ($floorNodes as $node) {
            $points[$node->id] = $node->ingameXY;
        }

        return $this->walkGraphService->getWalkPaths($floor, $points, $legs);
    }

    /**
     * @param list<IngameXY> $walkPath the walk from one node to the other, both ends included
     *
     * @return list<LatLng> the points between the two nodes
     */
    private function convertWalkPathToWaypoints(array $walkPath): array
    {
        return array_map(
            fn(IngameXY $ingameXY): LatLng => $this->coordinatesService->calculateMapLocationForIngameLocation($ingameXY),
            array_slice($walkPath, 1, -1),
        );
    }

    /**
     * Computes ordered path segments between consecutive kill zones for frontend rendering.
     * Empty paths are excluded. A path that takes a transport is split at it, so the transport is not drawn as a walk.
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
            $path = $this->pathFindingService->findShortestPathSteps($nodes, $edges, 'start', $nodeIdList[0]);
            array_push($segments, ...$this->splitPathAtTransports($path));
        }

        for ($i = 0; $i < count($nodeIdList) - 1; $i++) {
            $path = $this->pathFindingService->findShortestPathSteps($nodes, $edges, $nodeIdList[$i], $nodeIdList[$i + 1]);
            array_push($segments, ...$this->splitPathAtTransports($path));
        }

        return $segments;
    }

    /**
     * @param list<PathStep> $path
     *
     * @return list<list<LatLng>> The runs of the path between the transports it takes, in path order.
     */
    private function splitPathAtTransports(array $path): array
    {
        $segments = [];
        $current  = [];
        foreach ($path as $pathStep) {
            if ($pathStep->arrivedByTransport && !empty($current)) {
                $segments[] = $current;
                $current    = [];
            }
            $current[] = $pathStep->latLng;
        }

        if (!empty($current)) {
            $segments[] = $current;
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
