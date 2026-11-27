<?php

namespace App\Service\PathFinding;

use App\Logic\Structs\LatLng;
use App\Logic\Structs\PathEdge;
use App\Logic\Structs\PathNode;
use App\Logic\Structs\PathStep;

interface PathFindingServiceInterface
{
    /**
     * Find the shortest path between two nodes using Dijkstra's algorithm.
     *
     * @param array<string, PathNode> $nodes Nodes keyed by their ID
     * @param array<int, PathEdge>    $edges Directed edges (create both directions for bidirectional edges)
     *
     * @return array<int, LatLng> Ordered waypoints from start to end, including the waypoints of every edge taken, empty
     *                            if no path exists
     */
    public function findShortestPath(
        array  $nodes,
        array  $edges,
        string $startNodeId,
        string $endNodeId,
    ): array;

    /**
     * {@see self::findShortestPath()}, with whether each point was reached by taking a transport.
     *
     * @param array<string, PathNode> $nodes Nodes keyed by their ID
     * @param array<int, PathEdge>    $edges Directed edges (create both directions for bidirectional edges)
     *
     * @return list<PathStep> Ordered steps from start to end, including the waypoints of every edge taken, empty if no
     *                        path exists
     */
    public function findShortestPathSteps(
        array  $nodes,
        array  $edges,
        string $startNodeId,
        string $endNodeId,
    ): array;
}
