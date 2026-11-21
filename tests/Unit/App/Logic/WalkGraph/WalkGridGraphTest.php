<?php

namespace Tests\Unit\App\Logic\WalkGraph;

use App\Logic\WalkGraph\WalkGrid;
use App\Logic\WalkGraph\WalkGridGraph;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('WalkGraph')]
final class WalkGridGraphTest extends PublicTestCase
{
    private const float CELL_SIZE = 3.0;

    #[Test]
    public function getDistancesFrom_givenWallBetweenTwoCells_returnsTheDistanceAroundTheWall(): void
    {
        // Arrange: a U-shaped corridor, (0,0) up to (0,4), across to (2,4) and down to (2,0); the wall is x = 1
        $graph = $this->createGraph([
            [[0, 0], [0, 1], [0, 2], [0, 3], [0, 4], [1, 4], [2, 4], [2, 3], [2, 2], [2, 1], [2, 0]],
        ]);
        $from = $graph->snap(1.5, 1.5, 1.0);
        $to   = $graph->snap(7.5, 1.5, 1.0);

        // Act
        $distances = $graph->getDistancesFrom($from, 1000.0);

        // Assert: 10 straight steps of one cell
        Assert::assertEqualsWithDelta(30.0, $distances[$to], 0.001);
    }

    #[Test]
    public function getDistancesFrom_givenDiagonalSteps_weighsThemByTheirLength(): void
    {
        // Arrange
        $graph = $this->createGraph([[[0, 0], [1, 1], [2, 2]]]);
        $from  = $graph->snap(1.5, 1.5, 1.0);
        $to    = $graph->snap(7.5, 7.5, 1.0);

        // Act
        $distances = $graph->getDistancesFrom($from, 1000.0);

        // Assert
        Assert::assertEqualsWithDelta(2 * self::CELL_SIZE * M_SQRT2, $distances[$to], 0.001);
    }

    #[Test]
    public function getDistancesFrom_givenLongerPathReachedFirst_returnsTheShortestDistance(): void
    {
        // Arrange - (1,3) is first reached over two diagonals and a diagonal (12.7 yd), later over four straights (12 yd)
        $graph = $this->createGraph([
            [[0, 0], [1, 1], [2, 2], [1, 3]],
            [[0, 0], [0, 1], [0, 2], [0, 3], [1, 3]],
        ]);
        $from = $graph->snap(1.5, 1.5, 1.0);
        $to   = $graph->snap(4.5, 10.5, 1.0);

        // Act
        $distances = $graph->getDistancesFrom($from, 1000.0);

        // Assert
        Assert::assertEqualsWithDelta(12.0, $distances[$to], 0.001);
    }

    #[Test]
    public function getDistancesFrom_givenMaxDistance_omitsNodesFurtherAway(): void
    {
        // Arrange
        $graph = $this->createGraph([[[0, 0], [1, 0], [2, 0], [3, 0]]]);
        $from  = $graph->snap(1.5, 1.5, 1.0);

        // Act
        $distances = $graph->getDistancesFrom($from, 6.0);

        // Assert
        $reachedPositions = array_map(static fn(int $node) => $graph->getNodePosition($node), array_keys($distances));
        Assert::assertEqualsCanonicalizing([[1.5, 1.5], [4.5, 1.5], [7.5, 1.5]], $reachedPositions);
    }

    #[Test]
    public function getNodePath_givenWallBetweenTwoCells_returnsThePathAroundTheWall(): void
    {
        // Arrange: a U-shaped corridor, (0,0) up to (0,2), across to (2,2) and down to (2,0); the wall is x = 1
        $graph = $this->createGraph([
            [[0, 0], [0, 1], [0, 2], [1, 2], [2, 2], [2, 1], [2, 0]],
        ]);
        $from = $graph->snap(1.5, 1.5, 1.0);
        $to   = $graph->snap(7.5, 1.5, 1.0);

        // Act
        $shortestPaths = $graph->getShortestPathsFrom($from, 1000.0);
        $path          = $graph->getNodePath($shortestPaths['previous'], $from, $to);

        // Assert
        Assert::assertSame(
            [[1.5, 1.5], [1.5, 4.5], [1.5, 7.5], [4.5, 7.5], [7.5, 7.5], [7.5, 4.5], [7.5, 1.5]],
            $graph->getNodePositions($path),
        );
        Assert::assertEqualsWithDelta(18.0, $shortestPaths['distances'][$to], 0.001);
    }

    #[Test]
    public function getNodePath_givenShorterPathFoundLater_returnsTheShortestPath(): void
    {
        // Arrange - (1,3) is first reached over two diagonals and a diagonal (12.7 yd), later over four straights (12 yd)
        $graph = $this->createGraph([
            [[0, 0], [1, 1], [2, 2], [1, 3]],
            [[0, 0], [0, 1], [0, 2], [0, 3], [1, 3]],
        ]);
        $from = $graph->snap(1.5, 1.5, 1.0);
        $to   = $graph->snap(4.5, 10.5, 1.0);

        // Act
        $shortestPaths = $graph->getShortestPathsFrom($from, 1000.0);
        $path          = $graph->getNodePath($shortestPaths['previous'], $from, $to);

        // Assert
        Assert::assertSame(
            [[1.5, 1.5], [1.5, 4.5], [1.5, 7.5], [1.5, 10.5], [4.5, 10.5]],
            $graph->getNodePositions($path),
        );
    }

    #[Test]
    public function getNodePath_givenNodeBeyondTheMaxDistance_returnsEmptyPath(): void
    {
        // Arrange
        $graph = $this->createGraph([[[0, 0], [1, 0], [2, 0], [3, 0]]]);
        $from  = $graph->snap(1.5, 1.5, 1.0);
        $near  = $graph->snap(7.5, 1.5, 1.0);
        $far   = $graph->snap(10.5, 1.5, 1.0);

        // Act
        $shortestPaths = $graph->getShortestPathsFrom($from, 6.0);

        // Assert - the node within the maximum is the positive control
        Assert::assertSame([], $graph->getNodePath($shortestPaths['previous'], $from, $far));
        Assert::assertCount(3, $graph->getNodePath($shortestPaths['previous'], $from, $near));
    }

    #[Test]
    public function getNodePath_givenNodeInAnotherComponent_returnsEmptyPath(): void
    {
        // Arrange
        $graph = $this->createGraph([
            [[0, 0], [1, 0], [2, 0]],
            [[10, 10], [11, 10]],
        ]);
        $from = $graph->snap(1.5, 1.5, 1.0);
        // snap() never lands on the fragment, so take its node directly
        $fragmentNode = $this->findNodeAt($graph, 31.5, 31.5);

        // Act
        $shortestPaths = $graph->getShortestPathsFrom($from, 1000.0);

        // Assert
        Assert::assertSame([], $graph->getNodePath($shortestPaths['previous'], $from, $fragmentNode));
    }

    #[Test]
    public function getNodePath_givenTheStartNode_returnsOnlyTheStartNode(): void
    {
        // Arrange
        $graph = $this->createGraph([[[0, 0], [1, 0]]]);
        $from  = $graph->snap(1.5, 1.5, 1.0);

        // Act
        $shortestPaths = $graph->getShortestPathsFrom($from, 1000.0);

        // Assert
        Assert::assertSame([$from], $graph->getNodePath($shortestPaths['previous'], $from, $from));
    }

    #[Test]
    public function snap_givenFragmentNearerThanTheLargestComponent_snapsToTheLargestComponent(): void
    {
        // Arrange: a long corridor along y = 0 and a two-cell fragment at y = 2
        $graph = $this->createGraph([
            [[0, 0], [1, 0], [2, 0], [3, 0], [4, 0]],
            [[2, 2], [3, 2]],
        ]);

        // Act: (7.5, 7.5) is the centre of fragment cell (2, 2)
        $node = $graph->snap(7.5, 7.5, 10.0);

        // Assert
        Assert::assertNotNull($node);
        Assert::assertSame([7.5, 1.5], $graph->getNodePosition($node));
    }

    #[Test]
    public function snap_givenNoNodeWithinTheRadius_returnsNull(): void
    {
        // Arrange
        $graph = $this->createGraph([[[0, 0], [1, 0]]]);

        // Act - the nearest cell centre is 16.4 yd away, inside the searched square but outside the radius
        $node = $graph->snap(14.5, 14.5, 15.0);

        // Assert
        Assert::assertNull($node);
    }

    #[Test]
    public function construct_givenEdges_countsNodesEdgesAndTheLargestComponent(): void
    {
        // Arrange & Act
        $graph = $this->createGraph([
            [[0, 0], [1, 0], [2, 0], [3, 0]],
            [[10, 10], [11, 10]],
        ]);

        // Assert
        Assert::assertSame(6, $graph->getNodeCount());
        Assert::assertSame(4, $graph->getEdgeCount());
        Assert::assertSame(4, $graph->getLargestComponentNodeCount());
    }

    private function findNodeAt(WalkGridGraph $graph, float $x, float $y): int
    {
        for ($node = 0; $node < $graph->getNodeCount(); $node++) {
            if ($graph->getNodePosition($node) === [$x, $y]) {
                return $node;
            }
        }

        Assert::fail(sprintf('No node at %s, %s', $x, $y));
    }

    /**
     * @param array<int, array<int, array{int, int}>> $paths walked paths of neighbouring cells
     */
    private function createGraph(array $paths): WalkGridGraph
    {
        $edgeCounts = [];
        foreach ($paths as $path) {
            for ($i = 1; $i < count($path); $i++) {
                $edgeCounts[WalkGrid::edgeKey($path[$i - 1][0], $path[$i - 1][1], $path[$i][0], $path[$i][1])] = 1;
            }
        }

        return new WalkGridGraph(self::CELL_SIZE, $edgeCounts);
    }
}
