<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Models\Season;
use App\Service\CombatLog\CombatLogPollingBandService;
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
