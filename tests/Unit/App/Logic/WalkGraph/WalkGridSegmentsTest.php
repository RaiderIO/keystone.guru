<?php

namespace Tests\Unit\App\Logic\WalkGraph;

use App\Logic\WalkGraph\WalkGrid;
use App\Logic\WalkGraph\WalkGridSegments;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('WalkGraph')]
final class WalkGridSegmentsTest extends PublicTestCase
{
    #[Test]
    public function merge_givenStraightRunOfEdgesWithEqualRunCount_returnsOneSegment(): void
    {
        // Arrange - five edges east along y = 2, inserted out of order
        $edgeCounts = [];
        foreach ([3, 0, 4, 1, 2] as $cellX) {
            $edgeCounts[WalkGrid::edgeKey($cellX, 2, $cellX + 1, 2)] = 7;
        }

        // Act
        $segments = WalkGridSegments::merge($edgeCounts);

        // Assert
        Assert::assertSame([[0, 2, 5, 2, 7]], $segments);
    }

    #[Test]
    public function merge_givenDiagonalRun_returnsOneSegment(): void
    {
        // Arrange - three edges north-west
        $edgeCounts = [
            WalkGrid::edgeKey(0, 0, -1, 1)  => 2,
            WalkGrid::edgeKey(-1, 1, -2, 2) => 2,
            WalkGrid::edgeKey(-2, 2, -3, 3) => 2,
        ];

        // Act
        $segments = WalkGridSegments::merge($edgeCounts);

        // Assert
        Assert::assertSame([[0, 0, -3, 3, 2]], $segments);
    }

    #[Test]
    public function merge_givenRunCountChangeHalfway_splitsTheRun(): void
    {
        // Arrange - two edges walked by 3 runs, then two by 1 run
        $edgeCounts = [
            WalkGrid::edgeKey(0, 0, 0, 1) => 3,
            WalkGrid::edgeKey(0, 1, 0, 2) => 3,
            WalkGrid::edgeKey(0, 2, 0, 3) => 1,
            WalkGrid::edgeKey(0, 3, 0, 4) => 1,
        ];

        // Act
        $segments = WalkGridSegments::merge($edgeCounts);

        // Assert
        Assert::assertEqualsCanonicalizing([[0, 0, 0, 2, 3], [0, 2, 0, 4, 1]], $segments);
    }

    #[Test]
    public function merge_givenDirectionChange_splitsTheRun(): void
    {
        // Arrange - east, then north from the same corner
        $edgeCounts = [
            WalkGrid::edgeKey(0, 0, 1, 0) => 1,
            WalkGrid::edgeKey(1, 0, 2, 0) => 1,
            WalkGrid::edgeKey(2, 0, 2, 1) => 1,
        ];

        // Act
        $segments = WalkGridSegments::merge($edgeCounts);

        // Assert
        Assert::assertEqualsCanonicalizing([[0, 0, 2, 0, 1], [2, 0, 2, 1, 1]], $segments);
    }

    #[Test]
    public function merge_givenNoEdges_returnsNoSegments(): void
    {
        // Arrange & Act
        $segments = WalkGridSegments::merge([]);

        // Assert
        Assert::assertSame([], $segments);
    }
}
