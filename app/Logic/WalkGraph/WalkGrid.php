<?php

namespace App\Logic\WalkGraph;

/**
 * Integer encoding of the absolute walk grid: a cell is (floor(x / cellSize), floor(y / cellSize)) in in-game
 * coordinates, so a grid needs no origin and stays valid when a floor's bounds change.
 *
 * An edge joins two 8-connected neighbour cells and is stored from its canonical end, which makes the key
 * direction-agnostic: the step is always one of east, north, north-east or north-west.
 */
final class WalkGrid
{
    /** Cell coordinates are offset by this to keep them positive; ±1M cells is ±3000 km at 3 yd cells */
    private const int CELL_OFFSET = 1 << 20;

    private const int AXIS_BITS = 21;

    private const int AXIS_MASK = (1 << self::AXIS_BITS) - 1;

    /** @var array<int, array{int, int}> direction => [dx, dy] from the canonical end of an edge */
    private const array DIRECTIONS = [
        0 => [1, 0],
        1 => [0, 1],
        2 => [1, 1],
        3 => [-1, 1],
    ];

    public static function cellCoordinate(float $value, float $cellSize): int
    {
        return (int)floor($value / $cellSize);
    }

    public static function cellKey(int $cellX, int $cellY): int
    {
        return (($cellX + self::CELL_OFFSET) << self::AXIS_BITS) | ($cellY + self::CELL_OFFSET);
    }

    /**
     * @return array{int, int}
     */
    public static function decodeCellKey(int $cellKey): array
    {
        return [
            ($cellKey >> self::AXIS_BITS) - self::CELL_OFFSET,
            ($cellKey & self::AXIS_MASK) - self::CELL_OFFSET,
        ];
    }

    /**
     * @return int|null Null when the cells are the same or not 8-connected neighbours, or lie outside the grid.
     */
    public static function edgeKey(int $cellX, int $cellY, int $otherCellX, int $otherCellY): ?int
    {
        $deltaX = $otherCellX - $cellX;
        $deltaY = $otherCellY - $cellY;
        if (abs($deltaX) > 1 || abs($deltaY) > 1 || ($deltaX === 0 && $deltaY === 0)) {
            return null;
        }

        if ($deltaY < 0 || ($deltaY === 0 && $deltaX < 0)) {
            $cellX  = $otherCellX;
            $cellY  = $otherCellY;
            $deltaX = -$deltaX;
            $deltaY = -$deltaY;
        }

        if (abs($cellX) >= self::CELL_OFFSET - 1 || abs($cellY) >= self::CELL_OFFSET - 1) {
            return null;
        }

        $direction = match (true) {
            $deltaY === 0 => 0,
            $deltaX === 0 => 1,
            $deltaX === 1 => 2,
            default       => 3,
        };

        return (self::cellKey($cellX, $cellY) << 2) | $direction;
    }

    /**
     * @return array{int, int, int, int} [cellX, cellY, otherCellX, otherCellY]
     */
    public static function decodeEdgeKey(int $edgeKey): array
    {
        [$cellX, $cellY]   = self::decodeCellKey($edgeKey >> 2);
        [$deltaX, $deltaY] = self::DIRECTIONS[$edgeKey & 3];

        return [$cellX, $cellY, $cellX + $deltaX, $cellY + $deltaY];
    }

    /**
     * The 8-connected line of cells from one cell to another (Bresenham), both ends included.
     *
     * @return array<int, array{int, int}>
     */
    public static function rasterize(int $fromCellX, int $fromCellY, int $toCellX, int $toCellY): array
    {
        $deltaX = abs($toCellX - $fromCellX);
        $deltaY = -abs($toCellY - $fromCellY);
        $stepX  = $fromCellX < $toCellX ? 1 : -1;
        $stepY  = $fromCellY < $toCellY ? 1 : -1;
        $error  = $deltaX + $deltaY;

        $cells = [[$fromCellX, $fromCellY]];
        while ($fromCellX !== $toCellX || $fromCellY !== $toCellY) {
            $doubleError = 2 * $error;
            if ($doubleError >= $deltaY) {
                $error += $deltaY;
                $fromCellX += $stepX;
            }

            if ($doubleError <= $deltaX) {
                $error += $deltaX;
                $fromCellY += $stepY;
            }

            $cells[] = [$fromCellX, $fromCellY];
        }

        return $cells;
    }

    /**
     * @param array<int, int> $edgeKeys
     */
    public static function encodeEdgeKeys(array $edgeKeys): string
    {
        return gzcompress(pack('P*', ...array_values($edgeKeys)));
    }

    /**
     * @return array<int, int>
     */
    public static function decodeEdgeKeys(string $encoded): array
    {
        $raw = gzuncompress($encoded);
        if ($raw === false || $raw === '') {
            return [];
        }

        return array_values(unpack('P*', $raw));
    }

    /**
     * @param array<int, int> $edgeCounts edge key => count
     */
    public static function encodeEdgeCounts(array $edgeCounts): string
    {
        return gzcompress(pack('P*', ...array_keys($edgeCounts)) . pack('V*', ...array_values($edgeCounts)));
    }

    /**
     * @return array<int, int> edge key => count
     */
    public static function decodeEdgeCounts(string $encoded): array
    {
        $raw = gzuncompress($encoded);
        if ($raw === false || $raw === '') {
            return [];
        }

        // 8 bytes per key followed by 4 bytes per count
        $edgeCount = intdiv(strlen($raw), 12);
        $keys      = array_values(unpack('P*', substr($raw, 0, $edgeCount * 8)));
        $counts    = array_values(unpack('V*', substr($raw, $edgeCount * 8)));

        return array_combine($keys, $counts);
    }
}
