<?php

namespace Tests\Unit\App\Logic\SimulationCraft;

use App\Logic\SimulationCraft\RaidEventPull;
use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Models\MountableArea;
use App\Models\Polyline;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Service\Coordinates\CoordinatesService;
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

    /**
     * A pull over real geometry: map coordinates are scaled by 10 into in-game yards, walked at 7 yards per second,
     * ridden at 14, with a 1.5 second mount cast.
     *
     * @return RaidEventPull&MockObject
     */
    private function makeRaidEventPullOverGeometry(int $rangedPullCompensationYards, bool $useMounts): RaidEventPull
    {
        config([
            'keystoneguru.character.default_movement_speed_yards_second' => 7,
            'keystoneguru.character.mounted_movement_speed_yards_second' => 14,
            'keystoneguru.character.mount_cast_time_seconds'             => 1.5,
        ]);

        $realCoordinatesService = new CoordinatesService();
        $this->coordinatesService->method('calculateIngameLocationForMapLocation')
            ->willReturnCallback(static fn(LatLng $latLng): IngameXY => new IngameXY($latLng->getLng() * 10, $latLng->getLat() * 10));
        $this->coordinatesService->method('distanceBetweenPoints')
            ->willReturnCallback($realCoordinatesService->distanceBetweenPoints(...));
        $this->coordinatesService->method('distance')
            ->willReturnCallback($realCoordinatesService->distance(...));
        $this->coordinatesService->method('intersection')
            ->willReturnCallback($realCoordinatesService->intersection(...));
        $this->coordinatesService->method('polygonContainsPoint')
            ->willReturnCallback($realCoordinatesService->polygonContainsPoint(...));

        $options = new SimulationCraftRaidEventsOptions([
            'ranged_pull_compensation_yards' => $rangedPullCompensationYards,
            'use_mounts'                     => $useMounts,
        ]);

        return $this->getMockBuilder(RaidEventPull::class)
            ->setConstructorArgs([$this->coordinatesService, $options])
            ->onlyMethods([])
            ->getMock();
    }

    /**
     * A floor holding one square mountable area spanning lat 0..10 and lng 0..10.
     */
    private function makeFloorWithMountableSquare(): Floor
    {
        $polyline = new Polyline([
            'vertices_json' => json_encode([
                ['lat' => 0, 'lng' => 0],
                ['lat' => 0, 'lng' => 10],
                ['lat' => 10, 'lng' => 10],
                ['lat' => 10, 'lng' => 0],
            ]),
        ]);

        $mountableArea = new MountableArea(['speed' => 14]);
        $mountableArea->setRelation('polyline', $polyline);

        $floor = $this->makeFloor(1);
        $floor->setRelation('mountableAreas', collect([$mountableArea]));

        return $floor;
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
    public function calculateDelay_givenSingleFloorTwoWaypointPath_callsAlongStretchWithCompensation(): void
    {
        // Arrange
        $floor = $this->makeFloor(1);
        $a     = new LatLng(0, 0, $floor);
        $b     = new LatLng(1, 1, $floor);

        $pull = $this->makeRaidEventPull(['calculateDelayAlongStretch']);
        $pull->expects($this->once())
            ->method('calculateDelayAlongStretch')
            ->with([$a, $b], true)
            ->willReturn(10.0);

        // Act
        $result = $pull->calculateDelay([$a, $b]);

        // Assert
        Assert::assertSame(10.0, $result);
    }

    #[Test]
    public function calculateDelay_givenMultiSegmentSameFloorPath_appliesCompensationToTheWholeStretch(): void
    {
        // Arrange
        $floor = $this->makeFloor(1);
        $a     = new LatLng(0, 0, $floor);
        $b     = new LatLng(1, 1, $floor);
        $c     = new LatLng(2, 2, $floor);

        $pull = $this->makeRaidEventPull(['calculateDelayAlongStretch']);
        $pull->expects($this->once())
            ->method('calculateDelayAlongStretch')
            ->with([$a, $b, $c], true)
            ->willReturn(13.0);

        // Act
        $result = $pull->calculateDelay([$a, $b, $c]);

        // Assert
        Assert::assertSame(13.0, $result);
    }

    #[Test]
    public function calculateDelay_givenCrossFloorPath_skipsCrossFloorTransitionAndAppliesCompensationToLastSameFloorStretch(): void
    {
        // Arrange
        $floor1 = $this->makeFloor(1);
        $floor2 = $this->makeFloor(2);
        $a      = new LatLng(0, 0, $floor1);  // kill zone on floor 1
        $fsm1   = new LatLng(1, 1, $floor1);  // floor-switch marker on floor 1
        $fsm2   = new LatLng(2, 2, $floor2);  // linked floor-switch marker on floor 2
        $b      = new LatLng(3, 3, $floor2);  // kill zone on floor 2

        $pull = $this->makeRaidEventPull(['calculateDelayAlongStretch']);
        // Only same-floor stretches are processed; the fsm1→fsm2 transition is skipped
        $pull->expects($this->exactly(2))
            ->method('calculateDelayAlongStretch')
            ->willReturnCallback(static function (array $stretch, bool $applyCompensation) use ($a, $fsm1, $fsm2, $b): float {
                if ($stretch === [$a, $fsm1]) {
                    Assert::assertFalse($applyCompensation, 'Compensation must not be applied to intermediate stretches');

                    return 6.0;
                }
                if ($stretch === [$fsm2, $b]) {
                    Assert::assertTrue($applyCompensation, 'Compensation must be applied to the last same-floor stretch');

                    return 9.0;
                }
                Assert::fail('Unexpected calculateDelayAlongStretch call');
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
        $pull = $this->makeRaidEventPull(['calculateDelayAlongStretch']);
        $pull->expects($this->never())->method('calculateDelayAlongStretch');

        // Act
        $result = $pull->calculateDelay([$a, $b]);

        // Assert
        Assert::assertSame(0.0, $result);
    }

    #[Test]
    public function calculateDelayAlongStretch_givenPointsOnDifferentFloors_throwsInvalidArgumentException(): void
    {
        // Arrange
        $pull = $this->makeRaidEventPullOverDistance(140, 0);

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $pull->calculateDelayAlongStretch([new LatLng(0, 0, $this->makeFloor(1)), new LatLng(1, 1, $this->makeFloor(2))]);
    }

    #[Test]
    #[DataProvider('walkingDelayProvider')]
    public function calculateDelayAlongStretch_givenNoMounts_returnsTheTimeToWalkTheDistance(
        float $ingameDistance,
        int   $rangedPullCompensationYards,
        bool  $applyRangedCompensation,
        float $expected,
    ): void {
        // Arrange
        $floor = $this->makeFloor(1);
        $pull  = $this->makeRaidEventPullOverDistance($ingameDistance, $rangedPullCompensationYards);

        // Act
        $result = $pull->calculateDelayAlongStretch([new LatLng(0, 0, $floor), new LatLng(1, 1, $floor)], $applyRangedCompensation);

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
    public function calculateDelayAlongStretch_givenMountedAllTheWay_returnsTheRideAndOneMountCast(): void
    {
        // Arrange
        $floor = $this->makeFloor(1);
        $pull  = $this->makeRaidEventPullOverDistance(140, 0, ['calculateMountedFactorAndMountCastsAlongStretch']);
        $pull->method('calculateMountedFactorAndMountCastsAlongStretch')->willReturn([[['factor' => 1.0, 'speed' => 14]], 1]);

        // Act
        $result = $pull->calculateDelayAlongStretch([new LatLng(0, 0, $floor), new LatLng(1, 1, $floor)]);

        // Assert - 140 yards at 14 yards per second plus a 1.5 second mount cast beats walking for 20 seconds
        Assert::assertEqualsWithDelta(11.5, $result, 0.0001);
    }

    #[Test]
    public function calculateDelayAlongStretch_givenMountCastsCostingMoreThanWalking_returnsTheTimeToWalk(): void
    {
        // Arrange
        $floor = $this->makeFloor(1);
        $pull  = $this->makeRaidEventPullOverDistance(140, 0, ['calculateMountedFactorAndMountCastsAlongStretch']);
        $pull->method('calculateMountedFactorAndMountCastsAlongStretch')->willReturn([[['factor' => 1.0, 'speed' => 14]], 10]);

        // Act
        $result = $pull->calculateDelayAlongStretch([new LatLng(0, 0, $floor), new LatLng(1, 1, $floor)]);

        // Assert - riding takes 10 seconds plus 15 seconds of mount casts, walking takes 20
        Assert::assertEqualsWithDelta(20.0, $result, 0.0001);
    }

    #[Test]
    public function calculateDelay_givenLastStretchOfManyShortSegments_appliesCompensationToTheStretchLength(): void
    {
        // Arrange - ten 3 yard steps east, 30 yards in all
        $floor = $this->makeFloor(1);
        $path  = [];
        for ($i = 0; $i <= 10; $i++) {
            $path[] = new LatLng(0, $i * 0.3, $floor);
        }
        $pull = $this->makeRaidEventPullOverGeometry(9, false);

        // Act
        $result = $pull->calculateDelay($path);

        // Assert - 30 - 9 = 21 yards walked at 7 yards per second
        Assert::assertEqualsWithDelta(3.0, $result, 0.0001);
    }

    #[Test]
    public function calculateDelay_givenStretchShorterThanCompensation_returnsZeroNotNegative(): void
    {
        // Arrange - two 3 yard steps, well within the 10 yard compensation
        $floor = $this->makeFloor(1);
        $path  = [
            new LatLng(0, 0, $floor),
            new LatLng(0, 0.3, $floor),
            new LatLng(0, 0.6, $floor),
        ];
        $pull = $this->makeRaidEventPullOverGeometry(10, false);

        // Act
        $result = $pull->calculateDelay($path);

        // Assert
        Assert::assertSame(0.0, $result);
    }

    #[Test]
    public function calculateDelay_givenPolylineInsideMountableArea_ridesWithOneMountCast(): void
    {
        // Arrange - four diagonal steps inside the mountable square, 4 * sqrt(200) ~= 56.57 yards
        $floor = $this->makeFloorWithMountableSquare();
        $path  = [
            new LatLng(1, 1, $floor),
            new LatLng(2, 2, $floor),
            new LatLng(3, 3, $floor),
            new LatLng(4, 4, $floor),
            new LatLng(5, 5, $floor),
        ];
        $pull = $this->makeRaidEventPullOverGeometry(0, true);

        // Act
        $result = $pull->calculateDelay($path);

        // Assert - riding 56.57 yards at 14 yards per second plus one 1.5 second cast beats walking for 8.08 seconds
        Assert::assertEqualsWithDelta((4 * sqrt(200) / 14) + 1.5, $result, 0.0001);
    }

    #[Test]
    public function calculateMountedFactorAndMountCastsAlongStretch_givenPolylineEnteringAndLeavingMountableArea_countsMountCastsOnce(): void
    {
        // Arrange - along lat 5: lng -5 (outside), 5 and 7 (inside), 15 and 20 (outside); 250 yards in all.
        // The square is entered 50 yards in and left 150 yards in.
        $floor   = $this->makeFloorWithMountableSquare();
        $stretch = [
            new LatLng(5, -5, $floor),
            new LatLng(5, 5, $floor),
            new LatLng(5, 7, $floor),
            new LatLng(5, 15, $floor),
            new LatLng(5, 20, $floor),
        ];
        $pull = $this->makeRaidEventPullOverGeometry(0, true);

        // Act
        [$factorsAndSpeeds, $mountCasts] = $pull->calculateMountedFactorAndMountCastsAlongStretch($stretch);

        // Assert - one ride of 100 of the 250 yards, mounted up once
        Assert::assertSame(1, $mountCasts);
        Assert::assertCount(1, $factorsAndSpeeds);
        Assert::assertEqualsWithDelta(0.4, $factorsAndSpeeds[0]['factor'], 0.0001);
        Assert::assertSame(14, $factorsAndSpeeds[0]['speed']);
    }

    #[Test]
    public function calculateMountedFactorAndMountCastsAlongStretch_givenPolylinePointOnAreaEdge_countsTheCrossingOnce(): void
    {
        // Arrange - along lat 5: lng -5 (outside), 0 (on the square's edge), 5 (inside); 100 yards in all
        $floor   = $this->makeFloorWithMountableSquare();
        $stretch = [
            new LatLng(5, -5, $floor),
            new LatLng(5, 0, $floor),
            new LatLng(5, 5, $floor),
        ];
        $pull = $this->makeRaidEventPullOverGeometry(0, true);

        // Act
        [$factorsAndSpeeds, $mountCasts] = $pull->calculateMountedFactorAndMountCastsAlongStretch($stretch);

        // Assert - entering at the last crossing mounts for the final 50 yards without counting a cast,
        // the same as a straight line from lng -5 to 5 would
        Assert::assertSame(0, $mountCasts);
        Assert::assertCount(1, $factorsAndSpeeds);
        Assert::assertEqualsWithDelta(0.5, $factorsAndSpeeds[0]['factor'], 0.0001);
    }

    #[Test]
    public function calculateMountedFactorAndMountCastsAlongStretch_givenPolylineTouchingAreaEdgeFromInside_staysMountedAllTheWay(): void
    {
        // Arrange - from inside the square to a point on its lng 0 edge, then back inside
        $floor   = $this->makeFloorWithMountableSquare();
        $stretch = [
            new LatLng(5, 5, $floor),
            new LatLng(5, 0, $floor),
            new LatLng(6, 5, $floor),
        ];
        $pull = $this->makeRaidEventPullOverGeometry(0, true);

        // Act
        [$factorsAndSpeeds, $mountCasts] = $pull->calculateMountedFactorAndMountCastsAlongStretch($stretch);

        // Assert
        Assert::assertSame(1, $mountCasts);
        Assert::assertCount(1, $factorsAndSpeeds);
        Assert::assertEqualsWithDelta(1.0, $factorsAndSpeeds[0]['factor'], 0.0001);
    }

    #[Test]
    public function calculateMountedFactorAndMountCastsAlongStretch_givenPolylineTouchingAreaEdgeFromOutside_neverMounts(): void
    {
        // Arrange - from outside the square to a point on its lng 0 edge, then back outside
        $floor   = $this->makeFloorWithMountableSquare();
        $stretch = [
            new LatLng(5, -5, $floor),
            new LatLng(5, 0, $floor),
            new LatLng(6, -5, $floor),
        ];
        $pull = $this->makeRaidEventPullOverGeometry(0, true);

        // Act
        [$factorsAndSpeeds, $mountCasts] = $pull->calculateMountedFactorAndMountCastsAlongStretch($stretch);

        // Assert
        Assert::assertSame(0, $mountCasts);
        Assert::assertSame([], $factorsAndSpeeds);
    }

    #[Test]
    public function calculateMountedFactorAndMountCastsAlongStretch_givenTwoPointLineThroughMountableArea_matchesTheStraightLineResult(): void
    {
        // Arrange - a straight line along lat 5 from lng -5 to 20, entering at 0 and leaving at 10
        $floor   = $this->makeFloorWithMountableSquare();
        $stretch = [
            new LatLng(5, -5, $floor),
            new LatLng(5, 20, $floor),
        ];
        $pull = $this->makeRaidEventPullOverGeometry(0, true);

        // Act
        [$factorsAndSpeeds, $mountCasts] = $pull->calculateMountedFactorAndMountCastsAlongStretch($stretch);

        // Assert
        Assert::assertSame(1, $mountCasts);
        Assert::assertCount(1, $factorsAndSpeeds);
        Assert::assertEqualsWithDelta(0.4, $factorsAndSpeeds[0]['factor'], 0.0001);
    }
}
