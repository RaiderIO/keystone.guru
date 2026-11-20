<?php

namespace App\Logic\WalkGraph;

/**
 * Merges the edges of a walk graph into straight segments for drawing: a run of edges that continue each other in the
 * same direction with the same run count becomes one segment.
 */
final class WalkGridSegments
{
    /**
     * @param array<int, int> $edgeCounts edge key => run count, see {@see WalkGrid::decodeEdgeCounts()}
     *
     * @return list<array{int, int, int, int, int}> [fromCellX, fromCellY, toCellX, toCellY, runCount]
     */
    public static function merge(array $edgeCounts): array
    {
        $segments = [];
        foreach ($edgeCounts as $edgeKey => $runCount) {
            [$fromCellX, $fromCellY, $toCellX, $toCellY] = WalkGrid::decodeEdgeKey($edgeKey);
            $deltaX                                      = $toCellX - $fromCellX;
            $deltaY                                      = $toCellY - $fromCellY;

            // Only the first edge of a run starts a segment
            $previousEdgeKey = WalkGrid::edgeKey($fromCellX - $deltaX, $fromCellY - $deltaY, $fromCellX, $fromCellY);
            if ($previousEdgeKey !== null && ($edgeCounts[$previousEdgeKey] ?? null) === $runCount) {
                continue;
            }

            while (true) {
                $nextEdgeKey = WalkGrid::edgeKey($toCellX, $toCellY, $toCellX + $deltaX, $toCellY + $deltaY);
                if ($nextEdgeKey === null || ($edgeCounts[$nextEdgeKey] ?? null) !== $runCount) {
                    break;
                }

                $toCellX += $deltaX;
                $toCellY += $deltaY;
            }

            $segments[] = [$fromCellX, $fromCellY, $toCellX, $toCellY, $runCount];
        }

        return $segments;
    }
}
