<?php

namespace App\Logic\WalkGraph;

use SplPriorityQueue;

/**
 * An undirected walk graph over walk grid cells, held as dense int arrays: path finding over a few thousand
 * nodes stays well under a millisecond per query, where object-based nodes and edges cost ~100x that.
 *
 * Snapping only ever lands on the largest connected component: a stray fragment of a few walked cells otherwise
 * captures a nearby point and makes it unreachable from everything else.
 */
final class WalkGridGraph
{
    /** @var array<int, int> cell key => node */
    private array $nodeByCellKey = [];

    /** @var array<int, int> node => cell x */
    private array $nodeCellX = [];

    /** @var array<int, int> node => cell y */
    private array $nodeCellY = [];

    /** @var array<int, array<int, int>> node => neighbour nodes */
    private array $neighbours = [];

    /** @var array<int, array<int, float>> node => distances to the neighbours, same order */
    private array $neighbourDistances = [];

    /** @var array<int, int> node => component */
    private array $componentByNode = [];

    private int $largestComponent = -1;

    private int $largestComponentNodeCount = 0;

    private int $edgeCount = 0;

    /**
     * @param array<int, int> $edgeCounts edge key => number of runs that walked it
     */
    public function __construct(
        private readonly float $cellSize,
        array                  $edgeCounts,
    ) {
        $diagonalDistance = $cellSize * M_SQRT2;

        foreach ($edgeCounts as $edgeKey => $count) {
            [$cellX, $cellY, $otherCellX, $otherCellY] = WalkGrid::decodeEdgeKey($edgeKey);

            $node      = $this->getOrCreateNode($cellX, $cellY);
            $otherNode = $this->getOrCreateNode($otherCellX, $otherCellY);
            $distance  = ($cellX !== $otherCellX && $cellY !== $otherCellY) ? $diagonalDistance : $cellSize;

            $this->neighbours[$node][]              = $otherNode;
            $this->neighbourDistances[$node][]      = $distance;
            $this->neighbours[$otherNode][]         = $node;
            $this->neighbourDistances[$otherNode][] = $distance;
            $this->edgeCount++;
        }

        $this->labelComponents();
    }

    public function getCellSize(): float
    {
        return $this->cellSize;
    }

    public function getNodeCount(): int
    {
        return count($this->nodeCellX);
    }

    public function getEdgeCount(): int
    {
        return $this->edgeCount;
    }

    public function getLargestComponentNodeCount(): int
    {
        return $this->largestComponentNodeCount;
    }

    /**
     * @return array{float, float} the in-game x/y of the node's cell centre
     */
    public function getNodePosition(int $node): array
    {
        return [
            ($this->nodeCellX[$node] + 0.5) * $this->cellSize,
            ($this->nodeCellY[$node] + 0.5) * $this->cellSize,
        ];
    }

    /**
     * The node of the largest component whose cell centre is nearest to the position, within a radius.
     */
    public function snap(float $x, float $y, float $radius): ?int
    {
        $centerCellX = WalkGrid::cellCoordinate($x, $this->cellSize);
        $centerCellY = WalkGrid::cellCoordinate($y, $this->cellSize);
        $cellRadius  = (int)ceil($radius / $this->cellSize);

        $bestNode     = null;
        $bestDistance = INF;
        for ($cellY = $centerCellY - $cellRadius; $cellY <= $centerCellY + $cellRadius; $cellY++) {
            for ($cellX = $centerCellX - $cellRadius; $cellX <= $centerCellX + $cellRadius; $cellX++) {
                $node = $this->nodeByCellKey[WalkGrid::cellKey($cellX, $cellY)] ?? null;
                if ($node === null || $this->componentByNode[$node] !== $this->largestComponent) {
                    continue;
                }

                [$nodeX, $nodeY] = $this->getNodePosition($node);
                $distance        = hypot($nodeX - $x, $nodeY - $y);
                if ($distance <= $radius && $distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestNode     = $node;
                }
            }
        }

        return $bestNode;
    }

    /**
     * Every node, of any component, whose cell centre lies within the radius of the position.
     *
     * @return list<int>
     */
    public function getNodesWithinRadius(float $x, float $y, float $radius): array
    {
        $centerCellX = WalkGrid::cellCoordinate($x, $this->cellSize);
        $centerCellY = WalkGrid::cellCoordinate($y, $this->cellSize);
        $cellRadius  = (int)ceil($radius / $this->cellSize);

        $nodes = [];
        for ($cellY = $centerCellY - $cellRadius; $cellY <= $centerCellY + $cellRadius; $cellY++) {
            for ($cellX = $centerCellX - $cellRadius; $cellX <= $centerCellX + $cellRadius; $cellX++) {
                $node = $this->nodeByCellKey[WalkGrid::cellKey($cellX, $cellY)] ?? null;
                if ($node === null) {
                    continue;
                }

                [$nodeX, $nodeY] = $this->getNodePosition($node);
                if (hypot($nodeX - $x, $nodeY - $y) <= $radius) {
                    $nodes[] = $node;
                }
            }
        }

        return $nodes;
    }

    /**
     * Dijkstra from one node, not expanding past a maximum distance.
     *
     * @return array<int, float> node => walking distance, for every node reached within the maximum
     */
    public function getDistancesFrom(int $fromNode, float $maxDistance): array
    {
        return $this->getShortestPathsFrom($fromNode, $maxDistance)['distances'];
    }

    /**
     * Dijkstra from one node, not expanding past a maximum distance. Returns the walking distance of every node reached
     * within the maximum, and the node before each one on its shortest path (the start node has none), from which
     * {@see self::getNodePath()} rebuilds the path.
     *
     * @param list<int>         $targetNodes   When given, the search stops as soon as every one of these nodes is settled:
     *                                         their distances and paths are final, those of other nodes may not be.
     * @param array<int, mixed> $terminalNodes node => anything: these nodes are reached but never walked through. The
     *                                         start node is always walked from.
     *
     * @return array{distances: array<int, float>, previous: array<int, int>}
     */
    public function getShortestPathsFrom(int $fromNode, float $maxDistance, array $targetNodes = [], array $terminalNodes = []): array
    {
        $distances        = [$fromNode => 0.0];
        $previous         = [];
        $settled          = [];
        $remainingTargets = array_flip($targetNodes);
        unset($remainingTargets[$fromNode]);
        $hasTargets = $remainingTargets !== [];
        $queue      = new SplPriorityQueue();
        $queue->insert($fromNode, 0.0);

        while (!$queue->isEmpty()) {
            /** @var int $node */
            $node = $queue->extract();
            if (isset($settled[$node])) {
                continue;
            }

            $settled[$node] = true;
            if ($hasTargets) {
                unset($remainingTargets[$node]);
                if ($remainingTargets === []) {
                    break;
                }
            }

            if ($node !== $fromNode && isset($terminalNodes[$node])) {
                continue;
            }

            $nodeDistance = $distances[$node];

            foreach ($this->neighbours[$node] as $index => $neighbour) {
                if (isset($settled[$neighbour])) {
                    continue;
                }

                $distance = $nodeDistance + $this->neighbourDistances[$node][$index];
                if ($distance > $maxDistance) {
                    continue;
                }

                if (!isset($distances[$neighbour]) || $distance < $distances[$neighbour]) {
                    $distances[$neighbour] = $distance;
                    $previous[$neighbour]  = $node;
                    $queue->insert($neighbour, -$distance);
                }
            }
        }

        return [
            'distances' => $distances,
            'previous'  => $previous,
        ];
    }

    /**
     * The nodes on the shortest path from the start node of a {@see self::getShortestPathsFrom()} search to a node.
     *
     * @param array<int, int> $previous the search's node => predecessor
     *
     * @return list<int> the start node first and the node last; empty when the search did not reach the node
     */
    public function getNodePath(array $previous, int $fromNode, int $toNode): array
    {
        if ($toNode !== $fromNode && !isset($previous[$toNode])) {
            return [];
        }

        $path = [$toNode];
        $node = $toNode;
        while ($node !== $fromNode) {
            $node   = $previous[$node];
            $path[] = $node;
        }

        return array_reverse($path);
    }

    /**
     * @param list<int> $nodes
     *
     * @return list<array{float, float}> the in-game x/y of each node's cell centre
     */
    public function getNodePositions(array $nodes): array
    {
        return array_map(fn(int $node): array => $this->getNodePosition($node), $nodes);
    }

    private function getOrCreateNode(int $cellX, int $cellY): int
    {
        $cellKey = WalkGrid::cellKey($cellX, $cellY);

        $node = $this->nodeByCellKey[$cellKey] ?? null;
        if ($node === null) {
            $node                            = count($this->nodeCellX);
            $this->nodeByCellKey[$cellKey]   = $node;
            $this->nodeCellX[$node]          = $cellX;
            $this->nodeCellY[$node]          = $cellY;
            $this->neighbours[$node]         = [];
            $this->neighbourDistances[$node] = [];
        }

        return $node;
    }

    private function labelComponents(): void
    {
        $component = 0;
        foreach ($this->nodeCellX as $startNode => $cellX) {
            if (isset($this->componentByNode[$startNode])) {
                continue;
            }

            $this->componentByNode[$startNode] = $component;
            $stack                             = [$startNode];
            $size                              = 0;
            while ($stack !== []) {
                $node = array_pop($stack);
                $size++;
                foreach ($this->neighbours[$node] as $neighbour) {
                    if (!isset($this->componentByNode[$neighbour])) {
                        $this->componentByNode[$neighbour] = $component;
                        $stack[]                           = $neighbour;
                    }
                }
            }

            if ($size > $this->largestComponentNodeCount) {
                $this->largestComponentNodeCount = $size;
                $this->largestComponent          = $component;
            }

            $component++;
        }
    }
}
