<?php

namespace Tests\Feature\App\Service\SimulationCraft;

use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Service\SimulationCraft\Dtos\TravelDelayTransition;
use App\Service\SimulationCraft\Enums\TravelDelayExclusion;
use App\Service\SimulationCraft\TravelDelayCalibrationServiceInterface;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\LoadsJsonFiles;
use Tests\TestCases\PublicTestCase;

#[Group('SimulationCraft')]
#[Group('TravelDelayCalibrationService')]
final class TravelDelayCalibrationServiceTest extends PublicTestCase
{
    use LoadsJsonFiles;

    private const string FIXTURE_NAME = 'TWW/tww_s1_ara_kara_city_of_echoes_3';

    private const string FIXTURE_ROOT_PATH = '../../../Controller/Api/V1/APICombatLogController/';

    private TravelDelayCalibrationServiceInterface $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TravelDelayCalibrationServiceInterface::class);
    }

    #[Test]
    public function createTransitions_givenConsecutivePulls_returnsGapBetweenLastDeathAndFirstEngage(): void
    {
        // Arrange
        $runStart = Carbon::parse('2026-11-21T17:00:00.000+00:00');

        // Act
        $transitions = $this->service->createTransitions(
            'some_dungeon',
            [1 => ['seconds' => 30, 'yards' => 210.0], 2 => ['seconds' => 20, 'yards' => 140.5]],
            [
                2 => ['start' => $runStart->copy()->addMilliseconds(42500), 'end' => $runStart->copy()->addSeconds(60)],
                1 => ['start' => $runStart->copy()->addSeconds(5), 'end' => $runStart->copy()->addSeconds(30)],
            ],
            collect([$runStart->copy()->addSeconds(70)]),
        );

        // Assert
        $this->assertCount(1, $transitions, 'The first pull has no pull before it to travel from');
        /** @var TravelDelayTransition $transition */
        $transition = $transitions->first();
        $this->assertSame('some_dungeon', $transition->dungeonKey);
        $this->assertSame(2, $transition->pullIndex);
        $this->assertSame(20, $transition->predictedSeconds);
        $this->assertEqualsWithDelta(12.5, $transition->observedSeconds, 0.0001);
        $this->assertNull($transition->exclusion, 'A player death after the gap must not exclude it');
        $this->assertEqualsWithDelta(140.5, $transition->predictedYards, 0.0001);
        $this->assertTrue($transition->previousPullEndedAt->equalTo($runStart->copy()->addSeconds(30)));
        $this->assertTrue($transition->pullStartedAt->equalTo($runStart->copy()->addMilliseconds(42500)));
    }

    #[Test]
    public function createTransitions_givenPullEngagedBeforePreviousDied_returnsOverlapExclusion(): void
    {
        // Arrange
        $runStart = Carbon::parse('2026-11-21T17:00:00.000+00:00');

        // Act
        $transitions = $this->service->createTransitions(
            'some_dungeon',
            [1 => ['seconds' => 30, 'yards' => 210.0], 2 => ['seconds' => 20, 'yards' => 140.5]],
            [
                1 => ['start' => $runStart->copy()->addSeconds(5), 'end' => $runStart->copy()->addSeconds(30)],
                2 => ['start' => $runStart->copy()->addSeconds(25), 'end' => $runStart->copy()->addSeconds(60)],
            ],
            collect(),
        );

        // Assert
        $this->assertCount(1, $transitions);
        $this->assertEqualsWithDelta(-5.0, $transitions->first()->observedSeconds, 0.0001);
        $this->assertSame(TravelDelayExclusion::Overlap, $transitions->first()->exclusion);
    }

    #[Test]
    public function createTransitions_givenPlayerDeathDuringPreviousPull_returnsPlayerDeathExclusion(): void
    {
        // Arrange
        $runStart = Carbon::parse('2026-11-21T17:00:00.000+00:00');

        // Act
        $transitions = $this->service->createTransitions(
            'some_dungeon',
            [1 => ['seconds' => 30, 'yards' => 210.0], 2 => ['seconds' => 20, 'yards' => 140.5]],
            [
                1 => ['start' => $runStart->copy()->addSeconds(5), 'end' => $runStart->copy()->addSeconds(30)],
                2 => ['start' => $runStart->copy()->addSeconds(50), 'end' => $runStart->copy()->addSeconds(60)],
            ],
            collect([$runStart->copy()->addSeconds(20)]),
        );

        // Assert
        $this->assertCount(1, $transitions);
        $this->assertSame(TravelDelayExclusion::PlayerDeath, $transitions->first()->exclusion);
    }

    #[Test]
    public function summarize_givenTransitions_returnsStatisticsOverIncludedTransitionsOnly(): void
    {
        // Arrange
        $transitions = collect([
            new TravelDelayTransition('some_dungeon', 2, 10, 8.0),
            new TravelDelayTransition('some_dungeon', 3, 20, 13.0),
            new TravelDelayTransition('some_dungeon', 4, 30, 18.0),
            new TravelDelayTransition('some_dungeon', 5, 40, 300.0, TravelDelayExclusion::PlayerDeath),
        ]);

        // Act
        $summary = $this->service->summarize($transitions);

        // Assert
        $this->assertSame(3, $summary->includedCount);
        $this->assertSame(1, $summary->excludedCount);
        $this->assertEqualsWithDelta(20.0, $summary->medianPredictedSeconds, 0.0001);
        $this->assertEqualsWithDelta(13.0, $summary->medianObservedSeconds, 0.0001);
        $this->assertEqualsWithDelta(60.0, $summary->totalPredictedSeconds, 0.0001);
        $this->assertEqualsWithDelta(39.0, $summary->totalObservedSeconds, 0.0001);
        $this->assertEqualsWithDelta(0.65, $summary->medianRatio, 0.0001);
        $this->assertEqualsWithDelta(0.5, $summary->slope, 0.0001);
        $this->assertEqualsWithDelta(3.0, $summary->intercept, 0.0001);
    }

    #[Test]
    public function summarize_givenNoSpreadInPredictions_returnsNoFit(): void
    {
        // Arrange
        $transitions = collect([
            new TravelDelayTransition('some_dungeon', 2, 0, 8.0),
            new TravelDelayTransition('some_dungeon', 3, 0, 12.0),
        ]);

        // Act
        $summary = $this->service->summarize($transitions);

        // Assert
        $this->assertEqualsWithDelta(10.0, $summary->medianObservedSeconds, 0.0001);
        $this->assertNull($summary->medianRatio, 'No transition has a predicted delay to compare with');
        $this->assertNull($summary->slope);
        $this->assertNull($summary->intercept);
    }

    #[Test]
    public function measureCombatLogRoute_givenCombatLogRoute_returnsTransitionsAndPersistsNothing(): void
    {
        // Arrange
        $combatLogRoute = CombatLogRouteRequestDto::createFromArray(
            $this->getJsonData(self::FIXTURE_NAME, self::FIXTURE_ROOT_PATH),
        );
        $countsBefore = $this->getRowCounts();

        // Act
        $transitions = $this->service->measureCombatLogRoute($combatLogRoute, 20, true);

        // Assert
        $this->assertGreaterThan(1, $transitions->count());
        $this->assertSame(
            range(2, $transitions->count() + 1),
            $transitions->map(static fn(TravelDelayTransition $transition): int => $transition->pullIndex)->all(),
            'Every pull after the first gets one transition, in pull order',
        );
        $this->assertSame('ara_karacityofechoes', $transitions->first()->dungeonKey);
        $this->assertTrue(
            $transitions->contains(static fn(TravelDelayTransition $transition): bool => $transition->predictedSeconds > 0),
            'The route must have been pathed, or every predicted delay is 0',
        );
        $this->assertTrue(
            $transitions->contains(static fn(TravelDelayTransition $transition): bool => $transition->predictedYards > 0),
            'Every pathed pull has a path length',
        );
        $this->assertTrue(
            $transitions->contains(static fn(TravelDelayTransition $transition): bool => $transition->isIncluded() && $transition->observedSeconds > 0),
        );
        $this->assertSame($countsBefore, $this->getRowCounts());
    }

    /**
     * @return array<class-string, int>
     */
    private function getRowCounts(): array
    {
        $result = [];
        foreach ([DungeonRoute::class, KillZone::class, KillZoneEnemy::class] as $modelClass) {
            $result[$modelClass] = $modelClass::query()->count();
        }

        return $result;
    }
}
