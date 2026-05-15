<?php

namespace App\Service\PathFinding;

use App\Logic\Structs\LatLng;
use App\Logic\Structs\PathEdge;
use App\Logic\Structs\PathNode;

interface PathFindingServiceInterface
{
    /**
     * Find the shortest path between two nodes using Dijkstra's algorithm.
     *
     * @param array<string, PathNode> $nodes Nodes keyed by their ID
     * @param PathEdge[]              $edges Directed edges (create both directions for bidirectional edges)
     *
     * @return LatLng[] Ordered waypoints from start to end, empty if no path exists
     */
    public function findShortestPath(
        array  $nodes,
        array  $edges,
        string $startNodeId,
        string $endNodeId,
    ): array;
}
