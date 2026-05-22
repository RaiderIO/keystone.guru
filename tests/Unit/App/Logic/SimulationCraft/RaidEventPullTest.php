<?php

namespace Tests\Unit\App\Logic\SimulationCraft;

use App\Logic\SimulationCraft\RaidEventPull;
use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Service\Coordinates\CoordinatesServiceInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

#[Group('SimulationCraft')]
final class RaidEventPullTest extends TestCase
{
    private CoordinatesServiceInterface&MockObject $coordinatesService;

    private SimulationCraftRaidEventsOptions&MockObject $options;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coordinatesService = $this->createMock(CoordinatesServiceInterface::class);
        $this->options            = $this->createMock(SimulationCraftRaidEventsOptions::class);
    }

    private function makeFloor(int $id): Floor
    {
        $floor     = new Floor();
        $floor->id = $id;

        return $floor;
    }

    /**
     * @param string[] $methodsToMock
     *
     * @return RaidEventPull&MockObject
     */
    private function makeRaidEventPull(array $methodsToMock = []): RaidEventPull
    {
        return $this->getMockBuilder(RaidEventPull::class)
            ->setConstructorArgs([$this->coordinatesService, $this->options])
            ->onlyMethods($methodsToMock)
            ->getMock();
    }

    #[Test]
    public function calculateDelay_givenEmptyPath_returnsZero(): void
    {
        // Arrange
        $pull = $this->makeRaidEventPull();

        // Act + Assert
        Assert::assertSame(0.0, $pull->calculateDelay([]));
    }

    #[Test]
    public function calculateDelay_givenSingleWaypoint_returnsZero(): void
    {
        // Arrange
        $pull = $this->makeRaidEventPull();

        // Act + Assert
        Assert::assertSame(0.0, $pull->calculateDelay([new LatLng(0, 0, $this->makeFloor(1))]));
    }

    #[Test]
    public function calculateDelay_givenSingleFloorTwoWaypointPath_callsBetweenPointsWithCompensation(): void
    {
        // Arrange
        $floor = $this->makeFloor(1);
        $a     = new LatLng(0, 0, $floor);
        $b     = new LatLng(1, 1, $floor);

        $pull = $this->makeRaidEventPull(['calculateDelayBetweenPoints']);
        $pull->expects($this->once())
            ->method('calculateDelayBetweenPoints')
            ->with($a, $b, true)
            ->willReturn(10.0);

        // Act
        $result = $pull->calculateDelay([$a, $b]);

        // Assert
        Assert::assertSame(10.0, $result);
    }

    #[Test]
    public function calculateDelay_givenMultiSegmentSameFloorPath_appliesCompensationOnlyToLastSegment(): void
    {
        // Arrange
        $floor = $this->makeFloor(1);
        $a     = new LatLng(0, 0, $floor);
        $b     = new LatLng(1, 1, $floor);
        $c     = new LatLng(2, 2, $floor);

        $pull = $this->makeRaidEventPull(['calculateDelayBetweenPoints']);
        $pull->expects($this->exactly(2))
            ->method('calculateDelayBetweenPoints')
            ->willReturnCallback(static function (LatLng $from, LatLng $to, bool $applyCompensation) use ($a, $b, $c): float {
                if ($from === $a && $to === $b) {
                    Assert::assertFalse($applyCompensation, 'Compensation must not be applied to intermediate segments');

                    return 5.0;
                }
                if ($from === $b && $to === $c) {
                    Assert::assertTrue($applyCompensation, 'Compensation must be applied to the last segment');

                    return 8.0;
                }
                Assert::fail(sprintf('Unexpected calculateDelayBetweenPoints call'));
            });

        // Act
        $result = $pull->calculateDelay([$a, $b, $c]);

        // Assert
        Assert::assertSame(13.0, $result);
    }

    #[Test]
    public function calculateDelay_givenCrossFloorPath_skipsCrossFloorTransitionAndAppliesCompensationToLastSameFloorSegment(): void
    {
        // Arrange
        $floor1 = $this->makeFloor(1);
        $floor2 = $this->makeFloor(2);
        $a      = new LatLng(0, 0, $floor1);  // kill zone on floor 1
        $fsm1   = new LatLng(1, 1, $floor1);  // floor-switch marker on floor 1
        $fsm2   = new LatLng(2, 2, $floor2);  // linked floor-switch marker on floor 2
        $b      = new LatLng(3, 3, $floor2);  // kill zone on floor 2

        $pull = $this->makeRaidEventPull(['calculateDelayBetweenPoints']);
        // Only same-floor segments are processed; the fsm1→fsm2 transition is skipped
        $pull->expects($this->exactly(2))
            ->method('calculateDelayBetweenPoints')
            ->willReturnCallback(static function (LatLng $from, LatLng $to, bool $applyCompensation) use ($a, $fsm1, $fsm2, $b): float {
                if ($from === $a && $to === $fsm1) {
                    Assert::assertFalse($applyCompensation, 'Compensation must not be applied to intermediate segments');

                    return 6.0;
                }
                if ($from === $fsm2 && $to === $b) {
                    Assert::assertTrue($applyCompensation, 'Compensation must be applied to the last same-floor segment');

                    return 9.0;
                }
                Assert::fail(sprintf('Unexpected calculateDelayBetweenPoints call'));
            });

        // Act
        $result = $pull->calculateDelay([$a, $fsm1, $fsm2, $b]);

        // Assert
        Assert::assertSame(15.0, $result);
    }

    #[Test]
    public function calculateDelay_givenPathWithOnlyCrossFloorTransition_returnsZero(): void
    {
        // Arrange
        $a    = new LatLng(0, 0, $this->makeFloor(1));
        $b    = new LatLng(1, 1, $this->makeFloor(2));
        $pull = $this->makeRaidEventPull(['calculateDelayBetweenPoints']);
        $pull->expects($this->never())->method('calculateDelayBetweenPoints');

        // Act
        $result = $pull->calculateDelay([$a, $b]);

        // Assert
        Assert::assertSame(0.0, $result);
    }
}
