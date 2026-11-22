<?php

namespace App\Logic\WalkGraph;

/**
 * Turns the walked cells of a walk grid into the outline of the area players can walk: a raster of cells that is grown,
 * closed and cleaned up, then traced into rings along the cell borders.
 *
 * Every ring keeps the area on its left, so a hole winds against the ring around it: drawn together as one path with
 * the even-odd or the non-zero fill rule, every ring inside another one cuts a hole into it, and every ring inside a
 * hole is an island again.
 */
final class WalkGridArea
{
    /** Ring tracing directions, counter-clockwise from east: [dx, dy] */
    private const array DIRECTIONS = [
        0 => [1, 0],
        1 => [0, 1],
        2 => [-1, 0],
        3 => [0, -1],
    ];

    private readonly int $minCellX;

    private readonly int $minCellY;

    private readonly int $width;

    private readonly int $height;

    /** @var array<int, bool> Raster index => whether the cell is part of the area, see {@see self::index()} */
    private array $filled;

    /**
     * @param list<array{int, int}> $cells  [cellX, cellY] of the cells that start out filled
     * @param int                   $margin Empty cells kept around the cells, so growing never runs off the raster
     */
    public function __construct(array $cells, int $margin)
    {
        $cellXs = array_column($cells, 0);
        $cellYs = array_column($cells, 1);

        $this->minCellX = ($cellXs === [] ? 0 : min($cellXs)) - $margin;
        $this->minCellY = ($cellYs === [] ? 0 : min($cellYs)) - $margin;
        $this->width    = ($cellXs === [] ? 0 : max($cellXs)) + $margin - $this->minCellX + 1;
        $this->height   = ($cellYs === [] ? 0 : max($cellYs)) + $margin - $this->minCellY + 1;
        $this->filled   = array_fill(0, $this->width * $this->height, false);

        foreach ($cells as [$cellX, $cellY]) {
            $this->filled[$this->index($cellX - $this->minCellX, $cellY - $this->minCellY)] = true;
        }
    }

    /**
     * The outline of the area around the walked cells.
     *
     * @param list<array{int, int}> $walkedCells       [cellX, cellY]
     * @param int                   $growCells         How far the area reaches beyond the walked cells
     * @param int                   $closeCells        Gaps between walked paths up to twice this wide are filled in
     * @param int                   $minRegionCells    Islands and holes smaller than this are dropped and filled in
     * @param float                 $simplifyTolerance In cells, see {@see self::simplifyRing()}
     * @param int                   $smoothIterations  See {@see self::smoothRing()}
     *
     * @return list<list<array{float, float}>> Rings of [x, y] cell-corner coordinates
     */
    public static function outline(
        array $walkedCells,
        int   $growCells,
        int   $closeCells,
        int   $minRegionCells,
        float $simplifyTolerance,
        int   $smoothIterations,
    ): array {
        $area = new self($walkedCells, $growCells + $closeCells + 1);
        $area->grow($growCells + $closeCells);
        $area->shrink($closeCells);
        $area->removeSmallRegions($minRegionCells);

        $rings = [];
        foreach ($area->traceRings() as $ring) {
            $ring = self::simplifyRing($ring, $simplifyTolerance);
            if (count($ring) >= 3) {
                $rings[] = self::smoothRing($ring, $smoothIterations);
            }
        }

        return $rings;
    }

    /**
     * Fills every cell within the radius of a filled cell.
     */
    public function grow(int $radius): void
    {
        $offsets = self::diskOffsets($radius);
        $grown   = array_fill(0, count($this->filled), false);

        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if (!$this->filled[$this->index($x, $y)]) {
                    continue;
                }

                foreach ($offsets as [$deltaX, $deltaY]) {
                    if ($this->isOnRaster($x + $deltaX, $y + $deltaY)) {
                        $grown[$this->index($x + $deltaX, $y + $deltaY)] = true;
                    }
                }
            }
        }

        $this->filled = $grown;
    }

    /**
     * Keeps only the filled cells whose whole radius is filled.
     */
    public function shrink(int $radius): void
    {
        $offsets = self::diskOffsets($radius);
        $shrunk  = $this->filled;

        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if (!$this->filled[$this->index($x, $y)]) {
                    continue;
                }

                foreach ($offsets as [$deltaX, $deltaY]) {
                    if (!$this->isFilled($x + $deltaX, $y + $deltaY)) {
                        $shrunk[$this->index($x, $y)] = false;
                        break;
                    }
                }
            }
        }

        $this->filled = $shrunk;
    }

    /**
     * Empties the filled regions smaller than the minimum, then fills the enclosed empty regions smaller than it. Both
     * are 4-connected.
     */
    public function removeSmallRegions(int $minCells): void
    {
        foreach ([true, false] as $value) {
            $visited = [];
            for ($index = 0; $index < count($this->filled); $index++) {
                if ($this->filled[$index] !== $value || isset($visited[$index])) {
                    continue;
                }

                [$region, $touchesEdge] = $this->floodFill($index, $visited);
                // The empty region around the area always touches the raster edge, and is never filled
                if (count($region) < $minCells && ($value || !$touchesEdge)) {
                    foreach ($region as $regionIndex) {
                        $this->filled[$regionIndex] = !$value;
                    }
                }
            }
        }
    }

    /**
     * Traces the border between filled and empty cells into closed rings, with the filled cells on the left. Two filled
     * cells that only touch at a corner end up in separate rings, matching the 4-connected regions.
     *
     * @return list<list<array{int, int}>> Rings of [x, y] cell-corner coordinates, one per corner where the ring turns
     */
    public function traceRings(): array
    {
        /** @var array<int, array<int, bool>> $borderEdges corner index => direction => unused */
        $borderEdges = [];
        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if (!$this->filled[$this->index($x, $y)]) {
                    continue;
                }

                if (!$this->isFilled($x, $y - 1)) {
                    $borderEdges[$this->cornerIndex($x, $y)][0] = true;
                }

                if (!$this->isFilled($x + 1, $y)) {
                    $borderEdges[$this->cornerIndex($x + 1, $y)][1] = true;
                }

                if (!$this->isFilled($x, $y + 1)) {
                    $borderEdges[$this->cornerIndex($x + 1, $y + 1)][2] = true;
                }

                if (!$this->isFilled($x - 1, $y)) {
                    $borderEdges[$this->cornerIndex($x, $y + 1)][3] = true;
                }
            }
        }

        $rings = [];
        foreach (array_keys($borderEdges) as $startCorner) {
            while (($borderEdges[$startCorner] ?? []) !== []) {
                $rings[] = $this->traceRing($borderEdges, $startCorner, (int)array_key_first($borderEdges[$startCorner]));
            }
        }

        return $rings;
    }

    /**
     * Ramer-Douglas-Peucker on a closed ring: drops the corners that lie within the tolerance of the line between the
     * corners kept around them, which turns the cell staircases into straight lines.
     *
     * @param list<array{float|int, float|int}> $ring
     *
     * @return list<array{float|int, float|int}>
     */
    public static function simplifyRing(array $ring, float $tolerance): array
    {
        $count = count($ring);
        if ($count <= 3 || $tolerance <= 0) {
            return $ring;
        }

        // Split the ring at the corner furthest from its first one, and simplify both halves as open lines
        $furthestIndex    = 0;
        $furthestDistance = -1.0;
        for ($i = 1; $i < $count; $i++) {
            $distance = hypot($ring[$i][0] - $ring[0][0], $ring[$i][1] - $ring[0][1]);
            if ($distance > $furthestDistance) {
                $furthestIndex    = $i;
                $furthestDistance = $distance;
            }
        }

        $firstHalf  = self::simplifyLine(array_slice($ring, 0, $furthestIndex + 1), $tolerance);
        $secondHalf = self::simplifyLine([...array_slice($ring, $furthestIndex), $ring[0]], $tolerance);

        return [...array_slice($firstHalf, 0, -1), ...array_slice($secondHalf, 0, -1)];
    }

    /**
     * Chaikin's corner cutting on a closed ring: every iteration replaces each corner by two points a quarter of the
     * way along its edges, rounding the corners off.
     *
     * @param list<array{float|int, float|int}> $ring
     *
     * @return list<array{float, float}>
     */
    public static function smoothRing(array $ring, int $iterations): array
    {
        $ring = array_map(static fn(array $point): array => [(float)$point[0], (float)$point[1]], $ring);

        for ($iteration = 0; $iteration < $iterations; $iteration++) {
            $smoothed = [];
            $count    = count($ring);
            for ($i = 0; $i < $count; $i++) {
                [$fromX, $fromY] = $ring[$i];
                [$toX, $toY]     = $ring[($i + 1) % $count];

                $smoothed[] = [0.75 * $fromX + 0.25 * $toX, 0.75 * $fromY + 0.25 * $toY];
                $smoothed[] = [0.25 * $fromX + 0.75 * $toX, 0.25 * $fromY + 0.75 * $toY];
            }

            $ring = $smoothed;
        }

        return $ring;
    }

    /**
     * @return list<array{int, int}> [cellX, cellY] of the filled cells
     */
    public function getFilledCells(): array
    {
        $cells = [];
        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if ($this->filled[$this->index($x, $y)]) {
                    $cells[] = [$x + $this->minCellX, $y + $this->minCellY];
                }
            }
        }

        return $cells;
    }

    /**
     * @param array<int, array<int, bool>> $borderEdges Consumed as the ring is traced
     *
     * @return list<array{int, int}>
     */
    private function traceRing(array &$borderEdges, int $startCorner, int $startDirection): array
    {
        $ring      = [];
        $corner    = $startCorner;
        $direction = $startDirection;
        $previous  = null;

        // The start edge stays available until the ring has come back around to it
        while (true) {
            if ($direction !== $previous) {
                $ring[] = [
                    $corner % ($this->width + 1) + $this->minCellX,
                    intdiv($corner, $this->width + 1) + $this->minCellY,
                ];
            }

            [$deltaX, $deltaY] = self::DIRECTIONS[$direction];
            $corner += $deltaX + $deltaY * ($this->width + 1);
            $previous = $direction;

            // A left turn keeps cells that only touch at this corner apart; then straight on, then a right turn
            $direction = null;
            foreach ([($previous + 1) % 4, $previous, ($previous + 3) % 4] as $candidate) {
                if (isset($borderEdges[$corner][$candidate])) {
                    $direction = $candidate;
                    break;
                }
            }

            if ($direction === null || ($corner === $startCorner && $direction === $startDirection)) {
                break;
            }

            $this->consumeBorderEdge($borderEdges, $corner, $direction);
        }

        $this->consumeBorderEdge($borderEdges, $startCorner, $startDirection);

        // The ring closes where it started; drop the start corner when the ring runs straight through it
        if (count($ring) > 1 && $previous === $startDirection) {
            array_shift($ring);
        }

        return $ring;
    }

    /**
     * @param array<int, array<int, bool>> $borderEdges
     */
    private function consumeBorderEdge(array &$borderEdges, int $corner, int $direction): void
    {
        unset($borderEdges[$corner][$direction]);
        if ($borderEdges[$corner] === []) {
            unset($borderEdges[$corner]);
        }
    }

    /**
     * @param array<int, bool> $visited
     *
     * @return array{list<int>, bool} The region's raster indexes, and whether it touches the raster edge
     */
    private function floodFill(int $startIndex, array &$visited): array
    {
        $value       = $this->filled[$startIndex];
        $region      = [];
        $touchesEdge = false;
        $stack       = [$startIndex];

        $visited[$startIndex] = true;
        while ($stack !== []) {
            $index    = array_pop($stack);
            $region[] = $index;

            $x = $index % $this->width;
            $y = intdiv($index, $this->width);
            if ($x === 0 || $y === 0 || $x === $this->width - 1 || $y === $this->height - 1) {
                $touchesEdge = true;
            }

            foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$deltaX, $deltaY]) {
                if (!$this->isOnRaster($x + $deltaX, $y + $deltaY)) {
                    continue;
                }

                $neighbourIndex = $this->index($x + $deltaX, $y + $deltaY);
                if (!isset($visited[$neighbourIndex]) && $this->filled[$neighbourIndex] === $value) {
                    $visited[$neighbourIndex] = true;
                    $stack[]                  = $neighbourIndex;
                }
            }
        }

        return [$region, $touchesEdge];
    }

    /**
     * @param list<array{float|int, float|int}> $line
     *
     * @return list<array{float|int, float|int}> The line's first and last point are always kept
     */
    private static function simplifyLine(array $line, float $tolerance): array
    {
        $count = count($line);
        if ($count <= 2) {
            return $line;
        }

        [$fromX, $fromY] = $line[0];
        [$toX, $toY]     = $line[$count - 1];
        $length          = hypot($toX - $fromX, $toY - $fromY);

        $furthestIndex    = 0;
        $furthestDistance = 0.0;
        for ($i = 1; $i < $count - 1; $i++) {
            [$x, $y] = $line[$i];
            // The distance to the line through both ends, or to the end itself when both ends coincide
            $distance = $length === 0.0 ?
                hypot($x - $fromX, $y - $fromY) :
                abs(($toX - $fromX) * ($fromY - $y) - ($fromX - $x) * ($toY - $fromY)) / $length;

            if ($distance > $furthestDistance) {
                $furthestIndex    = $i;
                $furthestDistance = $distance;
            }
        }

        if ($furthestDistance <= $tolerance) {
            return [$line[0], $line[$count - 1]];
        }

        $before = self::simplifyLine(array_slice($line, 0, $furthestIndex + 1), $tolerance);
        $after  = self::simplifyLine(array_slice($line, $furthestIndex), $tolerance);

        return [...array_slice($before, 0, -1), ...$after];
    }

    /**
     * @return list<array{int, int}> The [dx, dy] of every cell within the radius, the centre included
     */
    private static function diskOffsets(int $radius): array
    {
        $offsets = [];
        for ($deltaY = -$radius; $deltaY <= $radius; $deltaY++) {
            for ($deltaX = -$radius; $deltaX <= $radius; $deltaX++) {
                // r * (r + 1) instead of r² rounds the disk out, so a radius of 1 covers the 8 neighbours too
                if ($deltaX * $deltaX + $deltaY * $deltaY <= $radius * ($radius + 1)) {
                    $offsets[] = [$deltaX, $deltaY];
                }
            }
        }

        return $offsets;
    }

    private function index(int $x, int $y): int
    {
        return $y * $this->width + $x;
    }

    private function cornerIndex(int $x, int $y): int
    {
        return $y * ($this->width + 1) + $x;
    }

    private function isOnRaster(int $x, int $y): bool
    {
        return $x >= 0 && $y >= 0 && $x < $this->width && $y < $this->height;
    }

    private function isFilled(int $x, int $y): bool
    {
        return $this->isOnRaster($x, $y) && $this->filled[$this->index($x, $y)];
    }
}
