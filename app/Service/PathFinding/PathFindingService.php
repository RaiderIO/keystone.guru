<?php

namespace App\Service\PathFinding;

use App\Logic\Structs\LatLng;
use App\Logic\Structs\PathEdge;
use App\Logic\Structs\PathNode;
use App\Logic\Structs\PathStep;
use SplPriorityQueue;

class PathFindingService implements PathFindingServiceInterface
{
    /**
     * {@inheritDoc}
     */
    public function findShortestPath(
        array  $nodes,
        array  $edges,
        string $startNodeId,
        string $endNodeId,
    ): array {
        return array_map(
            static fn(PathStep $pathStep): LatLng => $pathStep->latLng,
            $this->findShortestPathSteps($nodes, $edges, $startNodeId, $endNodeId),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function findShortestPathSteps(
        array  $nodes,
        array  $edges,
        string $startNodeId,
        string $endNodeId,
    ): array {
        if (!isset($nodes[$startNodeId]) || !isset($nodes[$endNodeId])) {
            return [];
        }

        if ($startNodeId === $endNodeId) {
            return [new PathStep($nodes[$startNodeId]->latLng)];
        }

        /** @var array<string, array<array{to: string, weight: float, waypoints: list<LatLng>, isTransport: bool}>> $adjacency */
        $adjacency = [];
        foreach ($edges as $edge) {
            $adjacency[$edge->fromNodeId][] = [
                'to'          => $edge->toNodeId,
                'weight'      => $edge->weight,
                'waypoints'   => $edge->waypoints,
                'isTransport' => $edge->isTransport,
            ];
        }

        /** @var array<string, float> $distances */
        $distances = [];
        /** @var array<string, string|null> $previous */
        $previous = [];
        /** @var array<string, list<LatLng>> $previousWaypoints node => the waypoints of the edge from its previous node */
        $previousWaypoints = [];
        /** @var array<string, bool> $previousIsTransport node => whether the edge from its previous node is a transport */
        $previousIsTransport = [];
        foreach ($nodes as $id => $node) {
            $distances[$id]           = INF;
            $previous[$id]            = null;
            $previousWaypoints[$id]   = [];
            $previousIsTransport[$id] = false;
        }
        $distances[$startNodeId] = 0.0;

        // SplPriorityQueue is a max-heap; negate distances so smallest comes first
        $queue = new SplPriorityQueue();
        $queue->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
        $queue->insert($startNodeId, 0);

        /** @var array<string, true> $visited */
        $visited = [];

        while (!$queue->isEmpty()) {
            $item      = $queue->extract();
            $currentId = $item['data'];

            if (isset($visited[$currentId])) {
                continue;
            }
            $visited[$currentId] = true;

            if ($currentId === $endNodeId) {
                break;
            }

            foreach ($adjacency[$currentId] ?? [] as $neighbor) {
                $neighborId = $neighbor['to'];

                if (isset($visited[$neighborId])) {
                    continue;
                }

                $newDist = $distances[$currentId] + $neighbor['weight'];
                if ($newDist < $distances[$neighborId]) {
                    $distances[$neighborId]         = $newDist;
                    $previous[$neighborId]          = $currentId;
                    $previousWaypoints[$neighborId] = $neighbor['waypoints'];
                    $previousIsTransport[$neighborId] = $neighbor['isTransport'];
                    $queue->insert($neighborId, -$newDist);
                }
            }
        }

        if ($distances[$endNodeId] === INF) {
            return [];
        }

        /** @var list<list<PathStep>> $reversedLegs */
        $reversedLegs = [];
        $current      = $endNodeId;
        while ($current !== null) {
            $reversedLegs[] = [
                ...array_map(static fn(LatLng $waypoint): PathStep => new PathStep($waypoint), $previousWaypoints[$current]),
                new PathStep($nodes[$current]->latLng, $previousIsTransport[$current]),
            ];
            $current = $previous[$current];
        }

        return array_merge(...array_reverse($reversedLegs));
    }
}
