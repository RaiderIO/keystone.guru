<?php

namespace Tests\Unit\App\Logic\WalkGraph;

use App\Logic\WalkGraph\WalkEdgeCollector;
use App\Logic\WalkGraph\WalkGrid;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('WalkGraph')]
final class WalkEdgeCollectorTest extends PublicTestCase
{
    private const int UI_MAP_ID = 2500;

    private const string PLAYER = 'Player-1-00000001';

    #[Test]
    public function addSample_givenWalkingStep_recordsTheEdgesAlongTheStep(): void
    {
        // Arrange
        $collector = $this->createCollector();

        // Act
        $collector->addSample(self::PLAYER, self::UI_MAP_ID, 100.0, 1.5, 1.5);
        $collector->addSample(self::PLAYER, self::UI_MAP_ID, 101.0, 7.5, 1.5);

        // Assert
        Assert::assertEquals(
            [self::UI_MAP_ID => [WalkGrid::edgeKey(0, 0, 1, 0), WalkGrid::edgeKey(1, 0, 2, 0)]],
            $collector->getEdgeKeysByUiMapId(),
        );
    }

    #[Test]
    public function addSample_givenGapLongerThanMaxStepSeconds_recordsNoEdge(): void
    {
        // Arrange
        $collector = $this->createCollector();

        // Act
        $collector->addSample(self::PLAYER, self::UI_MAP_ID, 100.0, 1.5, 1.5);
        $collector->addSample(self::PLAYER, self::UI_MAP_ID, 102.5, 7.5, 1.5);

        // Assert
        Assert::assertSame([], $collector->getEdgeKeysByUiMapId());
    }

    #[Test]
    public function addSample_givenStepLongerThanMaxStepDistance_recordsNoEdge(): void
    {
        // Arrange
        $collector = $this->createCollector();

        // Act
        $collector->addSample(self::PLAYER, self::UI_MAP_ID, 100.0, 1.5, 1.5);
        $collector->addSample(self::PLAYER, self::UI_MAP_ID, 101.9, 21.5, 1.5);

        // Assert
        Assert::assertSame([], $collector->getEdgeKeysByUiMapId());
    }

    #[Test]
    public function addSample_givenStepFasterThanMaxSpeed_recordsNoEdge(): void
    {
        // Arrange
        $collector = $this->createCollector();

        // Act: 10 yd in half a second is 20 yd/s
        $collector->addSample(self::PLAYER, self::UI_MAP_ID, 100.0, 1.5, 1.5);
        $collector->addSample(self::PLAYER, self::UI_MAP_ID, 100.5, 11.5, 1.5);

        // Assert
        Assert::assertSame([], $collector->getEdgeKeysByUiMapId());
    }

    #[Test]
    public function addSample_givenStepToAnotherUiMap_recordsNoEdge(): void
    {
        // Arrange
        $collector = $this->createCollector();

        // Act
        $collector->addSample(self::PLAYER, self::UI_MAP_ID, 100.0, 1.5, 1.5);
        $collector->addSample(self::PLAYER, self::UI_MAP_ID + 1, 101.0, 7.5, 1.5);

        // Assert
        Assert::assertSame([], $collector->getEdgeKeysByUiMapId());
    }

    #[Test]
    public function addSample_givenStepsOfDifferentPlayers_recordsNoEdgeBetweenThem(): void
    {
        // Arrange
        $collector = $this->createCollector();

        // Act
        $collector->addSample(self::PLAYER, self::UI_MAP_ID, 100.0, 1.5, 1.5);
        $collector->addSample('Player-1-00000002', self::UI_MAP_ID, 101.0, 7.5, 1.5);

        // Assert
        Assert::assertSame([], $collector->getEdgeKeysByUiMapId());
    }

    #[Test]
    public function addSample_givenPlayerStoodStillBeforeWalking_timesTheStepFromTheLastSighting(): void
    {
        // Arrange
        $collector = $this->createCollector();

        // Act: seen at the same spot for 10 s, then one step 1 s after the last sighting
        $collector->addSample(self::PLAYER, self::UI_MAP_ID, 100.0, 1.5, 1.5);
        $collector->addSample(self::PLAYER, self::UI_MAP_ID, 105.0, 1.5, 1.5);
        $collector->addSample(self::PLAYER, self::UI_MAP_ID, 110.0, 1.5, 1.5);
        $collector->addSample(self::PLAYER, self::UI_MAP_ID, 111.0, 4.5, 1.5);

        // Assert
        Assert::assertSame([self::UI_MAP_ID => [WalkGrid::edgeKey(0, 0, 1, 0)]], $collector->getEdgeKeysByUiMapId());
    }

    #[Test]
    public function reset_givenCollectedEdges_forgetsEdgesAndLastSamples(): void
    {
        // Arrange
        $collector = $this->createCollector();
        $collector->addSample(self::PLAYER, self::UI_MAP_ID, 100.0, 1.5, 1.5);
        $collector->addSample(self::PLAYER, self::UI_MAP_ID, 101.0, 4.5, 1.5);

        // Act
        $collector->reset();
        $collector->addSample(self::PLAYER, self::UI_MAP_ID, 101.5, 7.5, 1.5);

        // Assert
        Assert::assertSame([], $collector->getEdgeKeysByUiMapId());
    }

    private function createCollector(): WalkEdgeCollector
    {
        return new WalkEdgeCollector(3.0, 2.0, 15.0, 14.0);
    }
}
