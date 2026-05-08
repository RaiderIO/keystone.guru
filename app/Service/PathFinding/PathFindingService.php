<?php

namespace App\Service\PathFinding;

use App\Logic\Structs\LatLng;
use App\Logic\Structs\PathEdge;
use App\Logic\Structs\PathNode;
use SplPriorityQueue;

class PathFindingService implements PathFindingServiceInterface
{
    /**
     * @param array<string, PathNode> $nodes
     * @param PathEdge[]              $edges
     *
     * @return LatLng[]
     */
    public function findShortestPath(
        array  $nodes,
        array  $edges,
        string $startNodeId,
        string $endNodeId,
    ): array {
        if (!isset($nodes[$startNodeId]) || !isset($nodes[$endNodeId])) {
            return [];
        }

        if ($startNodeId === $endNodeId) {
            return [$nodes[$startNodeId]->latLng];
        }

        /** @var array<string, array<array{to: string, weight: float}>> $adjacency */
        $adjacency = [];
        foreach ($edges as $edge) {
            $adjacency[$edge->fromNodeId][] = [
                'to'     => $edge->toNodeId,
                'weight' => $edge->weight,
            ];
        }

        /** @var array<string, float> $distances */
        $distances = [];
        /** @var array<string, string|null> $previous */
        $previous = [];
        foreach ($nodes as $id => $node) {
            $distances[$id] = INF;
            $previous[$id]  = null;
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
                    $distances[$neighborId] = $newDist;
                    $previous[$neighborId]  = $currentId;
                    $queue->insert($neighborId, -$newDist);
                }
            }
        }

        if ($distances[$endNodeId] === INF) {
            return [];
        }

        $path    = [];
        $current = $endNodeId;
        while ($current !== null) {
            array_unshift($path, $nodes[$current]->latLng);
            $current = $previous[$current];
        }

        return $path;
    }
}
