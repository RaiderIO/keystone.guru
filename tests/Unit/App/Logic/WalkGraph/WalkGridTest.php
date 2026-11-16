<?php

namespace Tests\Unit\App\Logic\WalkGraph;

use App\Logic\WalkGraph\WalkGrid;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('WalkGraph')]
final class WalkGridTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('edgeKey_givenNeighbourCellsInEitherOrder_returnsSameKey_DataProvider')]
    public function edgeKey_givenNeighbourCellsInEitherOrder_returnsSameKey(int $cellX, int $cellY, int $otherCellX, int $otherCellY): void
    {
        // Arrange & Act
        $edgeKey      = WalkGrid::edgeKey($cellX, $cellY, $otherCellX, $otherCellY);
        $reversedKey  = WalkGrid::edgeKey($otherCellX, $otherCellY, $cellX, $cellY);
        $decodedCells = WalkGrid::decodeEdgeKey($edgeKey);

        // Assert
        Assert::assertNotNull($edgeKey);
        Assert::assertSame($edgeKey, $reversedKey);
        $decodedPairs = [[$decodedCells[0], $decodedCells[1]], [$decodedCells[2], $decodedCells[3]]];
        Assert::assertEqualsCanonicalizing([[$cellX, $cellY], [$otherCellX, $otherCellY]], $decodedPairs);
    }

    /**
     * @return array<string, array{int, int, int, int}>
     */
    public static function edgeKey_givenNeighbourCellsInEitherOrder_returnsSameKey_DataProvider(): array
    {
        return [
            'east'                 => [0, 0, 1, 0],
            'north'                => [0, 0, 0, 1],
            'north-east'           => [0, 0, 1, 1],
            'north-west'           => [0, 0, -1, 1],
            'negative coordinates' => [-1200, -3400, -1201, -3399],
            'large coordinates'    => [5000, -5000, 5001, -5001],
        ];
    }

    #[Test]
    #[DataProvider('edgeKey_givenCellsThatAreNotNeighbours_returnsNull_DataProvider')]
    public function edgeKey_givenCellsThatAreNotNeighbours_returnsNull(int $cellX, int $cellY, int $otherCellX, int $otherCellY): void
    {
        // Arrange & Act
        $edgeKey = WalkGrid::edgeKey($cellX, $cellY, $otherCellX, $otherCellY);

        // Assert
        Assert::assertNull($edgeKey);
    }

    /**
     * @return array<string, array{int, int, int, int}>
     */
    public static function edgeKey_givenCellsThatAreNotNeighbours_returnsNull_DataProvider(): array
    {
        return [
            'same cell'        => [3, 3, 3, 3],
            'two cells east'   => [3, 3, 5, 3],
            'knight move'      => [3, 3, 4, 5],
            'outside the grid' => [1 << 20, 0, (1 << 20) + 1, 0],
        ];
    }

    #[Test]
    public function edgeKey_givenDifferentEdges_returnsDifferentKeys(): void
    {
        // Arrange
        $neighbours = [[1, 0], [0, 1], [1, 1], [-1, 1], [-1, 0], [0, -1], [-1, -1], [1, -1]];

        // Act
        $edgeKeys = array_map(static fn(array $neighbour) => WalkGrid::edgeKey(10, 10, 10 + $neighbour[0], 10 + $neighbour[1]), $neighbours);

        // Assert
        Assert::assertCount(8, array_unique($edgeKeys));
    }

    #[Test]
    public function rasterize_givenShallowLine_returnsEightConnectedCellsFromStartToEnd(): void
    {
        // Arrange & Act
        $cells = WalkGrid::rasterize(0, 0, 4, 2);

        // Assert: one cell per column, through the exact midpoint
        Assert::assertSame([0, 1, 2, 3, 4], array_column($cells, 0));
        Assert::assertSame([0, 0], $cells[0]);
        Assert::assertSame([2, 1], $cells[2]);
        Assert::assertSame([4, 2], $cells[4]);
        for ($i = 1; $i < count($cells); $i++) {
            Assert::assertNotNull(WalkGrid::edgeKey($cells[$i - 1][0], $cells[$i - 1][1], $cells[$i][0], $cells[$i][1]));
        }
    }

    #[Test]
    public function rasterize_givenLineGoingSouthWest_returnsEightConnectedCellsFromStartToEnd(): void
    {
        // Arrange & Act
        $cells = WalkGrid::rasterize(0, 0, -2, -3);

        // Assert
        Assert::assertSame([0, 0], $cells[0]);
        Assert::assertSame([-2, -3], $cells[count($cells) - 1]);
        Assert::assertCount(4, $cells);
        for ($i = 1; $i < count($cells); $i++) {
            Assert::assertNotNull(WalkGrid::edgeKey($cells[$i - 1][0], $cells[$i - 1][1], $cells[$i][0], $cells[$i][1]));
        }
    }

    #[Test]
    public function decodeEdgeCounts_givenEncodedEdgeCounts_returnsTheSameEdgeCounts(): void
    {
        // Arrange
        $edgeCounts = [
            WalkGrid::edgeKey(0, 0, 1, 0)           => 1,
            WalkGrid::edgeKey(-500, 20, -499, 21)   => 37,
            WalkGrid::edgeKey(800, -900, 799, -899) => 50,
        ];

        // Act
        $decoded = WalkGrid::decodeEdgeCounts(WalkGrid::encodeEdgeCounts($edgeCounts));

        // Assert
        Assert::assertSame($edgeCounts, $decoded);
    }

    #[Test]
    public function decodeEdgeKeys_givenEncodedEdgeKeys_returnsTheSameEdgeKeys(): void
    {
        // Arrange
        $edgeKeys = [WalkGrid::edgeKey(0, 0, 0, 1), WalkGrid::edgeKey(-3, -3, -2, -2)];

        // Act
        $decoded = WalkGrid::decodeEdgeKeys(WalkGrid::encodeEdgeKeys($edgeKeys));

        // Assert
        Assert::assertSame($edgeKeys, $decoded);
    }
}
