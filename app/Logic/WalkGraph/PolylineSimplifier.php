<?php

namespace App\Logic\WalkGraph;

/**
 * Reduces an open polyline, such as a cell-by-cell walk graph path, to the points that shape it.
 *
 * Distances are measured to the segment between the kept points rather than to the infinite line through them
 * (as {@see WalkGridArea::simplifyRing()} does): a path that runs past its end and turns back would otherwise lose
 * the turning point.
 */
final class PolylineSimplifier
{
    /**
     * Drops the points between collinear steps, then runs Ramer-Douglas-Peucker over what is left.
     *
     * @param list<array{float, float}> $points
     * @param float                     $tolerance A point within this distance of the segment between the points kept
     *                                             around it is dropped. 0 only drops collinear points.
     *
     * @return list<array{float, float}> The first and last point are always kept
     */
    public static function simplify(array $points, float $tolerance): array
    {
        $points = self::removeCollinearPoints($points);
        if ($tolerance <= 0) {
            return $points;
        }

        return self::simplifyDouglasPeucker($points, $tolerance);
    }

    /**
     * @param list<array{float, float}> $points
     *
     * @return list<array{float, float}>
     */
    public static function removeCollinearPoints(array $points): array
    {
        $count = count($points);
        if ($count <= 2) {
            return $points;
        }

        $result = [$points[0]];
        for ($i = 1; $i < $count - 1; $i++) {
            [$previousX, $previousY] = $result[count($result) - 1];
            [$x, $y]                 = $points[$i];
            [$nextX, $nextY]         = $points[$i + 1];

            $cross = ($x - $previousX) * ($nextY - $y) - ($y - $previousY) * ($nextX - $x);
            $dot   = ($x - $previousX) * ($nextX - $x) + ($y - $previousY) * ($nextY - $y);
            // A point is only redundant when the path carries straight on through it, not when it turns back
            if (abs($cross) < 1e-9 && $dot > 0) {
                continue;
            }

            $result[] = $points[$i];
        }

        $result[] = $points[$count - 1];

        return $result;
    }

    /**
     * @param list<array{float, float}> $points
     *
     * @return list<array{float, float}>
     */
    private static function simplifyDouglasPeucker(array $points, float $tolerance): array
    {
        $count = count($points);
        if ($count <= 2) {
            return $points;
        }

        $furthestIndex    = 0;
        $furthestDistance = 0.0;
        for ($i = 1; $i < $count - 1; $i++) {
            $distance = self::distanceToSegment($points[$i], $points[0], $points[$count - 1]);
            if ($distance > $furthestDistance) {
                $furthestIndex    = $i;
                $furthestDistance = $distance;
            }
        }

        if ($furthestDistance <= $tolerance) {
            return [$points[0], $points[$count - 1]];
        }

        $before = self::simplifyDouglasPeucker(array_slice($points, 0, $furthestIndex + 1), $tolerance);
        $after  = self::simplifyDouglasPeucker(array_slice($points, $furthestIndex), $tolerance);

        return [...array_slice($before, 0, -1), ...$after];
    }

    /**
     * @param array{float, float} $point
     * @param array{float, float} $from
     * @param array{float, float} $to
     */
    private static function distanceToSegment(array $point, array $from, array $to): float
    {
        [$x, $y]         = $point;
        [$fromX, $fromY] = $from;
        [$toX, $toY]     = $to;

        $segmentX      = $toX - $fromX;
        $segmentY      = $toY - $fromY;
        $lengthSquared = $segmentX * $segmentX + $segmentY * $segmentY;
        if ($lengthSquared === 0.0) {
            return hypot($x - $fromX, $y - $fromY);
        }

        $t = max(0.0, min(1.0, (($x - $fromX) * $segmentX + ($y - $fromY) * $segmentY) / $lengthSquared));

        return hypot($x - ($fromX + $t * $segmentX), $y - ($fromY + $t * $segmentY));
    }
}
