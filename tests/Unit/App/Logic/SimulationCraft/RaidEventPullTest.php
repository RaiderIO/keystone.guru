<?php

namespace Tests\Unit\App\Logic\SimulationCraft;

use App\Logic\SimulationCraft\RaidEventPull;
use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Service\Coordinates\CoordinatesServiceInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

#[Group('SimulationCraft')]
final class RaidEventPullTest extends TestCase
{
    private CoordinatesServiceInterface&MockObject $coordinatesService;

    private SimulationCraftRaidEventsOptions&MockObject $options;

    #[\Override]
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

    /**
     * A pull whose two points lie $ingameDistance yards apart, walked at 7 yards per second.
     *
     * @param string[] $methodsToMock
     *
     * @return RaidEventPull&MockObject
     */
    private function makeRaidEventPullOverDistance(float $ingameDistance, int $rangedPullCompensationYards, array $methodsToMock = []): RaidEventPull
    {
        config([
            'keystoneguru.character.default_movement_speed_yards_second' => 7,
            'keystoneguru.character.mount_cast_time_seconds'             => 1.5,
        ]);

        $this->coordinatesService->method('calculateIngameLocationForMapLocation')->willReturn(new IngameXY());
        $this->coordinatesService->method('distanceBetweenPoints')->willReturn($ingameDistance);

        $options = new SimulationCraftRaidEventsOptions([
            'ranged_pull_compensation_yards' => $rangedPullCompensationYards,
            'use_mounts'                     => false,
        ]);

        return $this->getMockBuilder(RaidEventPull::class)
            ->setConstructorArgs([$this->coordinatesService, $options])
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

    #[Test]
    public function calculateDelayBetweenPoints_givenPointsOnDifferentFloors_throwsInvalidArgumentException(): void
    {
        // Arrange
        $pull = $this->makeRaidEventPullOverDistance(140, 0);

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $pull->calculateDelayBetweenPoints(new LatLng(0, 0, $this->makeFloor(1)), new LatLng(1, 1, $this->makeFloor(2)));
    }

    #[Test]
    #[DataProvider('walkingDelayProvider')]
    public function calculateDelayBetweenPoints_givenNoMounts_returnsTheTimeToWalkTheDistance(
        float $ingameDistance,
        int   $rangedPullCompensationYards,
        bool  $applyRangedCompensation,
        float $expected,
    ): void {
        // Arrange
        $floor = $this->makeFloor(1);
        $pull  = $this->makeRaidEventPullOverDistance($ingameDistance, $rangedPullCompensationYards);

        // Act
        $result = $pull->calculateDelayBetweenPoints(new LatLng(0, 0, $floor), new LatLng(1, 1, $floor), $applyRangedCompensation);

        // Assert
        Assert::assertEqualsWithDelta($expected, $result, 0.0001);
    }

    /**
     * @return array<string, array{float, int, bool, float}>
     */
    public static function walkingDelayProvider(): array
    {
        return [
            'compensation shortens the walk'              => [140, 70, true, 10.0],
            'compensation not applied walks it all'       => [140, 70, false, 20.0],
            'compensation beyond the distance walks none' => [20, 30, true, 0.0],
        ];
    }

    #[Test]
    public function calculateDelayBetweenPoints_givenMountedAllTheWay_returnsTheRideAndOneMountCast(): void
    {
        // Arrange
        $floor = $this->makeFloor(1);
        $pull  = $this->makeRaidEventPullOverDistance(140, 0, ['calculateMountedFactorAndMountCastsBetweenPoints']);
        $pull->method('calculateMountedFactorAndMountCastsBetweenPoints')->willReturn([[['factor' => 1.0, 'speed' => 14]], 1]);

        // Act
        $result = $pull->calculateDelayBetweenPoints(new LatLng(0, 0, $floor), new LatLng(1, 1, $floor));

        // Assert - 140 yards at 14 yards per second plus a 1.5 second mount cast beats walking for 20 seconds
        Assert::assertEqualsWithDelta(11.5, $result, 0.0001);
    }

    #[Test]
    public function calculateDelayBetweenPoints_givenMountCastsCostingMoreThanWalking_returnsTheTimeToWalk(): void
    {
        // Arrange
        $floor = $this->makeFloor(1);
        $pull  = $this->makeRaidEventPullOverDistance(140, 0, ['calculateMountedFactorAndMountCastsBetweenPoints']);
        $pull->method('calculateMountedFactorAndMountCastsBetweenPoints')->willReturn([[['factor' => 1.0, 'speed' => 14]], 10]);

        // Act
        $result = $pull->calculateDelayBetweenPoints(new LatLng(0, 0, $floor), new LatLng(1, 1, $floor));

        // Assert - riding takes 10 seconds plus 15 seconds of mount casts, walking takes 20
        Assert::assertEqualsWithDelta(20.0, $result, 0.0001);
    }
}
