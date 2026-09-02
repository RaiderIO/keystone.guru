<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Models\Season;
use App\Service\CombatLog\CombatLogPollingBandService;
use App\Service\CombatLog\Dtos\KeyLevelBand;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsFilter;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsResponse;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use RuntimeException;
use Tests\Fixtures\LoggingFixtures;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('CombatLogPollingBandService')]
final class CombatLogPollingBandServiceTest extends PublicTestCase
{
    private const int LEVEL_MIN     = 2;
    private const int BAND_WIDTH    = 5;
    private const int PROBE_CEILING = 40;
    private const int MIN_RUNS      = 25;
    private const int CACHE_MINUTES = 50;

    private Season $season;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->season = Season::query()->firstOrFail();

        config([
            'keystoneguru.raider_io.combat_log_polling.bands.level_min'                      => self::LEVEL_MIN,
            'keystoneguru.raider_io.combat_log_polling.bands.width'                          => self::BAND_WIDTH,
            'keystoneguru.raider_io.combat_log_polling.top_band.levels_below_max'            => 2,
            'keystoneguru.raider_io.combat_log_polling.top_band.min_runs_for_level'          => self::MIN_RUNS,
            'keystoneguru.raider_io.combat_log_polling.top_band.probe_window_days'           => 7,
            'keystoneguru.raider_io.combat_log_polling.top_band.probe_level_ceiling'         => self::PROBE_CEILING,
            'keystoneguru.raider_io.combat_log_polling.top_band.max_key_level_cache_minutes' => self::CACHE_MINUTES,
        ]);

        $this->forgetCaches();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->forgetCaches();

        parent::tearDown();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getMaxKeyLevel_givenOnlyLowLevelsPlayedInVolume_returnsHighestLevelWithVolume(): void
    {
        // Arrange — 24 is the highest level anyone is really running
        $service = $this->makeService(fn(int $level): int => $level <= 24 ? 300 : 0);

        // Act
        $result = $service->getMaxKeyLevel($this->season);

        // Assert
        $this->assertSame(24, $result);
    }

    /**
     * A handful of world-record attempts must not drag the max key level up: doing so shrinks the
     * always-parsed top band to almost nothing.
     *
     * @throws Exception
     */
    #[Test]
    public function getMaxKeyLevel_givenHandfulOfRunsAboveTheNoiseFloor_ignoresThem(): void
    {
        // Arrange — 4 runs exist at 25, far below the noise floor, while 24 is played in volume
        $service = $this->makeService(fn(int $level): int => match (true) {
            $level <= 24  => 292,
            $level === 25 => 4,
            default       => 0,
        });

        // Act
        $result = $service->getMaxKeyLevel($this->season);

        // Assert
        $this->assertSame(24, $result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getMaxKeyLevel_givenProbeFails_failsClosedInsteadOfParsingEverything(): void
    {
        // Arrange
        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->method('searchAdvancedRuns')->willThrowException(new RuntimeException('upstream down'));
        $service = new CombatLogPollingBandService($raiderIOApiService, LoggingFixtures::createCombatLogPollingBandServiceLogging($this));

        // Act
        $result = $service->getMaxKeyLevel($this->season);

        // Assert — the ceiling matches no runs at all, which beats always parsing every key
        $this->assertSame(self::PROBE_CEILING, $result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getMaxKeyLevel_givenNoLevelPlayedInVolume_failsClosedOnTheCeiling(): void
    {
        // Arrange — cannot happen in a live season, so treat it as a broken probe
        $service = $this->makeService(fn(int $level): int => 0);

        // Act
        $result = $service->getMaxKeyLevel($this->season);

        // Assert — falling back to the minimum level would make the top band match every run there
        // is, and the top band is dispatched without consulting any budget
        $this->assertSame(self::PROBE_CEILING, $result);
    }

    /**
     * A throttled or malformed response has no run count. Counting that as "nobody plays this
     * level" walks the probe all the way to the bottom and caches the result for a week.
     *
     * @throws Exception
     */
    #[Test]
    public function getMaxKeyLevel_givenUnreadableRunCount_failsClosedInsteadOfWalkingDown(): void
    {
        // Arrange
        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->method('searchAdvancedRuns')->willReturn(new SearchAdvancedRunsResponse([], null));
        $service = new CombatLogPollingBandService($raiderIOApiService, LoggingFixtures::createCombatLogPollingBandServiceLogging($this));

        // Act
        $result = $service->getMaxKeyLevel($this->season);

        // Assert
        $this->assertSame(self::PROBE_CEILING, $result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getMaxKeyLevel_givenPriorResultCached_doesNotProbeAgain(): void
    {
        // Arrange
        $probeCount = 0;
        $service    = $this->makeService(function (int $level) use (&$probeCount): int {
            $probeCount++;

            return $level <= 24 ? 300 : 0;
        });
        $service->getMaxKeyLevel($this->season);
        $probesForFirstCall = $probeCount;

        // Act
        $result = $service->getMaxKeyLevel($this->season);

        // Assert
        $this->assertSame(24, $result);
        $this->assertSame($probesForFirstCall, $probeCount);
    }

    /**
     * At the start of a season everybody starts at the minimum key level and climbs from there. If
     * the probed max were cached for days, the top band's floor would stay pinned near the bottom -
     * and since the top band is dispatched without consulting any budget, that means parsing every
     * run of the season instead of a spread of them.
     *
     * @throws Exception
     */
    #[Test]
    public function getMaxKeyLevel_givenTheMaxClimbsEarlyInASeason_reprobesWithinTheHour(): void
    {
        // Arrange - the first day of the season, where nobody is past a +3 yet
        Carbon::setTestNow(Carbon::create(2026, 3, 4, 12));
        $highestPlayedLevel = 3;
        $service            = $this->makeService(function (int $level) use (&$highestPlayedLevel): int {
            return $level <= $highestPlayedLevel ? 300 : 0;
        });

        $this->assertSame(3, $service->getMaxKeyLevel($this->season));

        // Act - the raiders have caught up by the time the next hourly run comes around
        $highestPlayedLevel = 14;
        Carbon::setTestNow(Carbon::now()->addMinutes(self::CACHE_MINUTES + 1));

        // Assert - the cached max did not survive into the next scheduled run of combatlog:pollruns
        $this->assertSame(14, $service->getMaxKeyLevel($this->season));
        $this->assertSame(12, $service->getTopBand($this->season)->min);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getTopBand_givenMaxKeyLevel_startsTwoLevelsBelowIt(): void
    {
        // Arrange
        $service = $this->makeService(fn(int $level): int => $level <= 24 ? 300 : 0);

        // Act
        $result = $service->getTopBand($this->season);

        // Assert
        $this->assertSame(22, $result->min);
        $this->assertNull($result->max);
        $this->assertTrue($result->isTopBand());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getSpreadBands_givenTopBandFloor_coversEveryLevelBelowItWithoutOverlap(): void
    {
        // Arrange
        $service = $this->makeService(fn(int $level): int => $level <= 24 ? 300 : 0);

        // Act
        $result = $service->getSpreadBands($this->season);

        // Assert
        $this->assertSame(
            ['2-6', '7-11', '12-16', '17-21'],
            array_map(strval(...), $result),
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getSpreadBands_givenTopBandFloorMidBand_clampsLastBandToTheFloor(): void
    {
        // Arrange — max 21 puts the top band floor at 19, halfway through the 17-21 band
        $service = $this->makeService(fn(int $level): int => $level <= 21 ? 300 : 0);

        // Act
        $result = $service->getSpreadBands($this->season);

        // Assert — no spread band may reach into the always-parsed top band
        $this->assertSame(
            ['2-6', '7-11', '12-16', '17-18'],
            array_map(strval(...), $result),
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getSpreadBandForHour_givenConsecutiveHours_rotatesThroughEveryBand(): void
    {
        // Arrange
        $service = $this->makeService(fn(int $level): int => $level <= 24 ? 300 : 0);

        // Act
        $bandsByHour = array_map(
            fn(int $hour): string => (string)$service->getSpreadBandForHour($this->season, $hour),
            range(0, 8),
        );

        // Assert — 4 bands, each polled once every 4 hours
        $this->assertSame(
            ['2-6', '7-11', '12-16', '17-21', '2-6', '7-11', '12-16', '17-21', '2-6'],
            $bandsByHour,
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getSpreadBandForHour_givenNoRoomBelowTheTopBand_returnsNull(): void
    {
        // Arrange — a max of 3 puts the top band floor at the minimum level, leaving no room below
        $service = $this->makeService(fn(int $level): int => $level <= 3 ? 300 : 0);

        // Act
        $result = $service->getSpreadBandForHour($this->season, 0);

        // Assert
        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getBudgetWindowForBand_givenFirstBandThroughTheDay_releasesOneSliceEveryRotation(): void
    {
        // Arrange — 4 bands, so band 2-6 is polled at hours 0, 4, 8, 12, 16 and 20
        $service = $this->makeService(fn(int $level): int => $level <= 24 ? 300 : 0);
        $band    = $service->getSpreadBands($this->season)[0];

        // Act
        $windows = array_map(
            fn(int $hour): string => sprintf(
                '%d/%d',
                $service->getBudgetWindowForBand($this->season, $band, $hour)->elapsedOpportunities,
                $service->getBudgetWindowForBand($this->season, $band, $hour)->totalOpportunities,
            ),
            [0, 4, 8, 12, 16, 20],
        );

        // Assert
        $this->assertSame(['1/6', '2/6', '3/6', '4/6', '5/6', '6/6'], $windows);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getBudgetWindowForBand_givenLastBandAtHour23_releasesTheFullBudget(): void
    {
        // Arrange — 4 bands, so band 17-21 sits at index 3 and is polled at hours 3, 7, ... 23
        $service = $this->makeService(fn(int $level): int => $level <= 24 ? 300 : 0);
        $band    = $service->getSpreadBands($this->season)[3];

        // Act
        $firstWindow = $service->getBudgetWindowForBand($this->season, $band, 3);
        $lastWindow  = $service->getBudgetWindowForBand($this->season, $band, 23);

        // Assert
        $this->assertSame(1, $firstWindow->elapsedOpportunities);
        $this->assertSame(6, $firstWindow->totalOpportunities);
        $this->assertSame(6, $lastWindow->elapsedOpportunities);
        $this->assertSame(6, $lastWindow->totalOpportunities);
    }

    /**
     * With a single band the rotation does nothing and the window degenerates to the plain hour of
     * the day - the (hour + 1) / 24 spread the issue describes.
     *
     * @throws Exception
     */
    #[Test]
    public function getBudgetWindowForBand_givenASingleSpreadBand_releasesOneTwentyFourthPerHour(): void
    {
        // Arrange — a max of 9 puts the top band floor at 7, leaving exactly one band below it
        $service = $this->makeService(fn(int $level): int => $level <= 9 ? 300 : 0);
        $bands   = $service->getSpreadBands($this->season);

        $this->assertCount(1, $bands, 'Expected exactly one spread band for this arrangement');

        // Act
        $hour0  = $service->getBudgetWindowForBand($this->season, $bands[0], 0);
        $hour23 = $service->getBudgetWindowForBand($this->season, $bands[0], 23);

        // Assert
        $this->assertSame(1, $hour0->elapsedOpportunities);
        $this->assertSame(24, $hour0->totalOpportunities);
        $this->assertSame(24, $hour23->elapsedOpportunities);
        $this->assertSame(24, $hour23->totalOpportunities);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getBudgetWindowForBand_givenTopBand_releasesTheFullBudget(): void
    {
        // Arrange
        $service = $this->makeService(fn(int $level): int => $level <= 24 ? 300 : 0);

        // Act
        $window = $service->getBudgetWindowForBand($this->season, $service->getTopBand($this->season), 0);

        // Assert
        $this->assertSame(1, $window->elapsedOpportunities);
        $this->assertSame(1, $window->totalOpportunities);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getBudgetWindowForBand_givenBandNotInTheRotation_releasesTheFullBudget(): void
    {
        // Arrange — a band that no longer exists now that the max key level has moved
        $service = $this->makeService(fn(int $level): int => $level <= 24 ? 300 : 0);

        // Act
        $window = $service->getBudgetWindowForBand($this->season, new KeyLevelBand(97, 99), 0);

        // Assert
        $this->assertSame(1, $window->elapsedOpportunities);
        $this->assertSame(1, $window->totalOpportunities);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getBudgetWindowForBand_givenNoSpreadBandsAtAll_releasesTheFullBudgetWithoutDividingByZero(): void
    {
        // Arrange — a max of 3 puts the top band floor at the minimum level, leaving no room below
        $service = $this->makeService(fn(int $level): int => $level <= 3 ? 300 : 0);

        // Act
        $window = $service->getBudgetWindowForBand($this->season, new KeyLevelBand(2, 6), 0);

        // Assert
        $this->assertSame(1, $window->elapsedOpportunities);
        $this->assertSame(1, $window->totalOpportunities);
    }

    /**
     * @param  callable(int): int $runCountForLevel
     * @throws Exception
     */
    private function makeService(callable $runCountForLevel): CombatLogPollingBandService
    {
        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->method('searchAdvancedRuns')->willReturnCallback(
            function (SearchAdvancedRunsFilter $filter) use ($runCountForLevel): SearchAdvancedRunsResponse {
                // The probe asks about exactly one level at a time
                $this->assertSame($filter->mythicLevelMin, $filter->mythicLevelMax);

                return new SearchAdvancedRunsResponse([], $runCountForLevel($filter->mythicLevelMin));
            },
        );

        return new CombatLogPollingBandService($raiderIOApiService, LoggingFixtures::createCombatLogPollingBandServiceLogging($this));
    }

    private function forgetCaches(): void
    {
        Cache::forget(sprintf('combatlog:pollruns:max_key_level:%d', $this->season->id));
        Cache::forget(sprintf('combatlog:pollruns:max_key_level_last_known:%d', $this->season->id));
    }
}
