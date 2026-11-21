<?php

namespace Tests\Unit\App\Logic\WalkGraph;

use App\Logic\WalkGraph\PolylineSimplifier;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('WalkGraph')]
final class PolylineSimplifierTest extends PublicTestCase
{
    /**
     * @param list<array{float, float}> $points
     */
    #[Test]
    #[DataProvider('simplify_givenTwoOrFewerPoints_returnsThemUnchanged_dataProvider')]
    public function simplify_givenTwoOrFewerPoints_returnsThemUnchanged(array $points): void
    {
        // Arrange & Act
        $result = PolylineSimplifier::simplify($points, 3.0);

        // Assert
        Assert::assertSame($points, $result);
    }

    /**
     * @return array<string, array{list<array{float, float}>}>
     */
    public static function simplify_givenTwoOrFewerPoints_returnsThemUnchanged_dataProvider(): array
    {
        return [
            'empty'      => [[]],
            'one point'  => [[[1.5, 1.5]]],
            'two points' => [[[1.5, 1.5], [10.5, 1.5]]],
        ];
    }

    #[Test]
    public function simplify_givenCollinearGridSteps_returnsTheEnds(): void
    {
        // Arrange
        $points = [[1.5, 1.5], [4.5, 1.5], [7.5, 1.5], [10.5, 1.5], [13.5, 1.5]];

        // Act
        $result = PolylineSimplifier::simplify($points, 0.0);

        // Assert
        Assert::assertSame([[1.5, 1.5], [13.5, 1.5]], $result);
    }

    #[Test]
    public function simplify_givenLShapedPath_keepsTheCorner(): void
    {
        // Arrange
        $points = [[1.5, 1.5], [4.5, 1.5], [7.5, 1.5], [7.5, 4.5], [7.5, 7.5]];

        // Act
        $result = PolylineSimplifier::simplify($points, 3.0);

        // Assert
        Assert::assertSame([[1.5, 1.5], [7.5, 1.5], [7.5, 7.5]], $result);
    }

    #[Test]
    public function simplify_givenStaircaseWithinTheTolerance_returnsAStraightLine(): void
    {
        // Arrange - one cell right, one cell up, repeated: no corner is more than 2.2 yd off the diagonal
        $points = [[0.0, 0.0], [3.0, 0.0], [3.0, 3.0], [6.0, 3.0], [6.0, 6.0], [9.0, 6.0], [9.0, 9.0]];

        // Act
        $result = PolylineSimplifier::simplify($points, 3.0);

        // Assert
        Assert::assertSame([[0.0, 0.0], [9.0, 9.0]], $result);
    }

    #[Test]
    public function simplify_givenCornerBeyondTheTolerance_keepsIt(): void
    {
        // Arrange - the corner is 4.2 yd off the line between the ends
        $points = [[0.0, 0.0], [6.0, 0.0], [6.0, 6.0]];

        // Act
        $result = PolylineSimplifier::simplify($points, 3.0);

        // Assert
        Assert::assertSame([[0.0, 0.0], [6.0, 0.0], [6.0, 6.0]], $result);
    }

    #[Test]
    public function simplify_givenPathTurningBackPastItsEnd_keepsTheTurningPoint(): void
    {
        // Arrange - out to x = 30 and back to x = 9, all on one line
        $points = [[0.0, 0.0], [15.0, 0.0], [30.0, 0.0], [21.0, 0.0], [9.0, 0.0]];

        // Act
        $result = PolylineSimplifier::simplify($points, 3.0);

        // Assert
        Assert::assertSame([[0.0, 0.0], [30.0, 0.0], [9.0, 0.0]], $result);
    }
}
