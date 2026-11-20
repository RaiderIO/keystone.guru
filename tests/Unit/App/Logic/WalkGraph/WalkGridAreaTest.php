<?php

namespace Tests\Unit\App\Logic\WalkGraph;

use App\Logic\WalkGraph\WalkGridArea;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('WalkGraph')]
final class WalkGridAreaTest extends PublicTestCase
{
    #[Test]
    public function grow_givenRadiusOne_fillsTheEightNeighbours(): void
    {
        // Arrange
        $area = new WalkGridArea([[5, 5]], 2);

        // Act
        $area->grow(1);

        // Assert
        Assert::assertEqualsCanonicalizing($this->square(4, 4, 3), $area->getFilledCells());
    }

    #[Test]
    public function shrink_givenRadiusOne_keepsOnlyTheCellsWithAllNeighboursFilled(): void
    {
        // Arrange
        $area = new WalkGridArea($this->square(0, 0, 3), 1);

        // Act
        $area->shrink(1);

        // Assert
        Assert::assertSame([[1, 1]], $area->getFilledCells());
    }

    #[Test]
    public function removeSmallRegions_givenIslandSmallerThanTheMinimum_dropsIt(): void
    {
        // Arrange - a 3x3 region of 9 cells and a lone cell far from it
        $area = new WalkGridArea([...$this->square(0, 0, 3), [10, 10]], 1);

        // Act
        $area->removeSmallRegions(5);

        // Assert
        Assert::assertEqualsCanonicalizing($this->square(0, 0, 3), $area->getFilledCells());
    }

    #[Test]
    public function removeSmallRegions_givenEnclosedHoleSmallerThanTheMinimum_fillsIt(): void
    {
        // Arrange - a 5x5 region with its centre cell missing
        $area = new WalkGridArea($this->squareWithHole(0, 0, 5, 1), 1);

        // Act
        $area->removeSmallRegions(2);

        // Assert
        Assert::assertEqualsCanonicalizing($this->square(0, 0, 5), $area->getFilledCells());
    }

    #[Test]
    public function removeSmallRegions_givenEnclosedHoleAtTheMinimum_keepsIt(): void
    {
        // Arrange - a 7x7 region with a 3x3 hole of 9 cells
        $cells = $this->squareWithHole(0, 0, 7, 3);
        $area  = new WalkGridArea($cells, 1);

        // Act
        $area->removeSmallRegions(9);

        // Assert
        Assert::assertEqualsCanonicalizing($cells, $area->getFilledCells());
    }

    #[Test]
    public function traceRings_givenSingleCell_returnsItsFourCorners(): void
    {
        // Arrange
        $area = new WalkGridArea([[3, 4]], 1);

        // Act
        $rings = $area->traceRings();

        // Assert
        Assert::assertCount(1, $rings);
        Assert::assertEqualsCanonicalizing([[3, 4], [4, 4], [4, 5], [3, 5]], $rings[0]);
    }

    #[Test]
    public function traceRings_givenRegionWithHole_returnsAnOuterRingAndAHoleRing(): void
    {
        // Arrange - a 3x3 region with its centre cell missing
        $area = new WalkGridArea($this->squareWithHole(0, 0, 3, 1), 1);

        // Act
        $rings = $area->traceRings();

        // Assert
        Assert::assertEqualsCanonicalizing([
            [[0, 0], [0, 3], [3, 0], [3, 3]],
            [[1, 1], [1, 2], [2, 1], [2, 2]],
        ], $this->sortRings($rings));
    }

    #[Test]
    public function traceRings_givenCellsTouchingOnlyAtACorner_returnsARingPerCell(): void
    {
        // Arrange
        $area = new WalkGridArea([[0, 0], [1, 1]], 1);

        // Act
        $rings = $area->traceRings();

        // Assert
        Assert::assertEqualsCanonicalizing([
            [[0, 0], [0, 1], [1, 0], [1, 1]],
            [[1, 1], [1, 2], [2, 1], [2, 2]],
        ], $this->sortRings($rings));
    }

    #[Test]
    public function traceRings_givenStraightEdge_returnsOnlyTheCornersWhereTheRingTurns(): void
    {
        // Arrange - a 4x1 strip
        $area = new WalkGridArea([[0, 0], [1, 0], [2, 0], [3, 0]], 1);

        // Act
        $rings = $area->traceRings();

        // Assert
        Assert::assertCount(1, $rings);
        Assert::assertEqualsCanonicalizing([[0, 0], [4, 0], [4, 1], [0, 1]], $rings[0]);
    }

    #[Test]
    public function simplifyRing_givenStaircaseWithinTheTolerance_returnsItsStraightLine(): void
    {
        // Arrange - a triangle whose long side is a staircase of one-cell steps
        $ring = [[0, 0], [4, 0], [4, 1], [3, 1], [3, 2], [2, 2], [2, 3], [1, 3], [1, 4], [0, 4]];

        // Act
        $simplified = WalkGridArea::simplifyRing($ring, 0.75);

        // Assert
        Assert::assertSame([[0, 0], [4, 0], [4, 1], [0, 4]], $simplified);
    }

    #[Test]
    public function simplifyRing_givenCornerBeyondTheTolerance_keepsIt(): void
    {
        // Arrange
        $ring = [[0, 0], [4, 0], [4, 4], [0, 4]];

        // Act
        $simplified = WalkGridArea::simplifyRing($ring, 1.0);

        // Assert
        Assert::assertSame($ring, $simplified);
    }

    #[Test]
    public function smoothRing_givenOneIteration_cutsEveryCornerAQuarterAlongItsEdges(): void
    {
        // Arrange
        $ring = [[0, 0], [4, 0], [4, 4], [0, 4]];

        // Act
        $smoothed = WalkGridArea::smoothRing($ring, 1);

        // Assert
        Assert::assertSame([
            [1.0, 0.0], [3.0, 0.0],
            [4.0, 1.0], [4.0, 3.0],
            [3.0, 4.0], [1.0, 4.0],
            [0.0, 3.0], [0.0, 1.0],
        ], $smoothed);
    }

    #[Test]
    public function outline_givenNoCells_returnsNoRings(): void
    {
        // Act
        $rings = WalkGridArea::outline([], 2, 2, 30, 0.75, 2);

        // Assert
        Assert::assertSame([], $rings);
    }

    #[Test]
    public function outline_givenLoopAroundAWideObstacle_returnsAnOuterRingAndAHoleRing(): void
    {
        // Arrange - a path walked around the border of a 20x20 square
        $walkedCells = array_values(array_filter(
            $this->square(0, 0, 20),
            static fn(array $cell): bool => in_array($cell[0], [0, 19], true) || in_array($cell[1], [0, 19], true),
        ));

        // Act
        $rings = WalkGridArea::outline($walkedCells, 1, 0, 4, 0.0, 0);

        // Assert - grown by one cell, the path spans -1..21 on the outside and 2..18 around the obstacle
        Assert::assertEqualsCanonicalizing([
            [[-1.0, -1.0], [-1.0, 21.0], [21.0, -1.0], [21.0, 21.0]],
            [[2.0, 2.0], [2.0, 18.0], [18.0, 2.0], [18.0, 18.0]],
        ], $this->sortRings($rings));
    }

    #[Test]
    public function outline_givenPathsCloserThanTheClosingWidth_fillsTheGapBetweenThem(): void
    {
        // Arrange - two parallel paths 20 cells long with a 3 cell gap between them
        $walkedCells = [];
        for ($x = 0; $x < 20; $x++) {
            $walkedCells[] = [$x, 0];
            $walkedCells[] = [$x, 4];
        }

        // Act
        $rings = WalkGridArea::outline($walkedCells, 0, 2, 1, 0.0, 0);

        // Assert
        Assert::assertCount(1, $rings);
    }

    #[Test]
    public function outline_givenSimplifiedRingWithTooFewCorners_dropsIt(): void
    {
        // Arrange - a lone cell, whose ring the simplification collapses to a line
        $walkedCells = [[0, 0]];

        // Act
        $rings = WalkGridArea::outline($walkedCells, 0, 0, 1, 5.0, 0);

        // Assert
        Assert::assertSame([], $rings);
    }

    /**
     * @return list<array{int, int}>
     */
    private function square(int $minX, int $minY, int $size): array
    {
        $cells = [];
        for ($y = $minY; $y < $minY + $size; $y++) {
            for ($x = $minX; $x < $minX + $size; $x++) {
                $cells[] = [$x, $y];
            }
        }

        return $cells;
    }

    /**
     * @return list<array{int, int}> A square with a centred square hole
     */
    private function squareWithHole(int $minX, int $minY, int $size, int $holeSize): array
    {
        $holeMin = intdiv($size - $holeSize, 2);

        return array_values(array_filter(
            $this->square($minX, $minY, $size),
            static fn(array $cell): bool => $cell[0] - $minX < $holeMin || $cell[0] - $minX >= $holeMin + $holeSize ||
                $cell[1] - $minY < $holeMin || $cell[1] - $minY >= $holeMin + $holeSize,
        ));
    }

    /**
     * Each ring's corners in a fixed order, since tracing starts each ring at an arbitrary corner.
     *
     * @param list<list<array{float|int, float|int}>> $rings
     *
     * @return list<list<array{float|int, float|int}>>
     */
    private function sortRings(array $rings): array
    {
        return array_map(static function (array $ring): array {
            sort($ring);

            return $ring;
        }, $rings);
    }
}
