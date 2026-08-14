<?php

namespace Tests\Feature\Console\Commands\CombatLog;

use App\Jobs\CombatLog\ProcessCombatLogSegments;
use App\Models\CharacterClassSpecialization;
use App\Models\CombatLog\ParsedCombatLog;
use App\Models\Dungeon;
use App\Models\Season;
use App\Service\CombatLog\CombatLogParsingCriteriaServiceInterface;
use App\Service\CombatLog\CombatLogPollingBandServiceInterface;
use App\Service\CombatLog\Dtos\CombatLogParsingCriterionCheck;
use App\Service\CombatLog\Dtos\KeyLevelBand;
use App\Service\RaiderIO\Dtos\SearchAdvancedRun;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsFilter;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsResponse;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('CombatLog')]
final class PollCombatLogRunsCommandTest extends PublicTestCase
{
    private Dungeon $dungeon;

    private CharacterClassSpecialization $spec;

    private Season $season;

    private KeyLevelBand $spreadBand;

    private KeyLevelBand $topBand;

    /** @var array<int, int> */
    private array $capturedMythicLevelMins = [];

    /** @var array<int, int|null> */
    private array $capturedMythicLevelMaxes = [];

    /**
     * @throws Exception
     */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->dungeon    = Dungeon::query()->whereNotNull('challenge_mode_id')->first();
        $this->spec       = CharacterClassSpecialization::query()->first();
        $this->season     = Season::query()->first();
        $this->spreadBand = new KeyLevelBand(12, 16);
        $this->topBand    = new KeyLevelBand(22, null);

        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeason')->willReturn($this->season);
        app()->instance(SeasonServiceInterface::class, $seasonService);

        $bandService = $this->createMockPublic(CombatLogPollingBandServiceInterface::class);
        $bandService->method('getSpreadBands')->willReturn([$this->spreadBand]);
        $bandService->method('getSpreadBandForHour')->willReturn($this->spreadBand);
        $bandService->method('getTopBand')->willReturn($this->topBand);
        app()->instance(CombatLogPollingBandServiceInterface::class, $bandService);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenDungeonEligible_dispatchesJobsForReturnedRuns(): void
    {
        // Arrange
        Bus::fake();

        $run = $this->makeRun(1001, $this->dungeon->challenge_mode_id);

        $criteriaService = $this->makeCriteriaService(eligibleDungeons: collect([$this->dungeon]));
        $criteriaService->method('shouldParse')->willReturn(true);
        $criteriaService->expects($this->once())->method('recordParsed');
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $this->mockRaiderIOApiService(spreadRuns: [$run]);

        try {
            // Act
            $this->artisan('combatlog:pollruns')->assertSuccessful();

            // Assert
            Bus::assertDispatched(ProcessCombatLogSegments::class);
        } finally {
            ParsedCombatLog::query()->where('run_id', $run->id)->delete();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenRunAlreadyInParsedCombatLogs_skipsRun(): void
    {
        // Arrange
        Bus::fake();

        $runId = 9001;
        $run   = $this->makeRun($runId, $this->dungeon->challenge_mode_id);

        $criteriaService = $this->makeCriteriaService(eligibleDungeons: collect([$this->dungeon]));
        $criteriaService->method('shouldParse')->willReturn(true);
        $criteriaService->expects($this->never())->method('recordParsed');
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $this->mockRaiderIOApiService(spreadRuns: [$run]);

        try {
            ParsedCombatLog::create(['run_id' => $runId]);

            // Act
            $this->artisan('combatlog:pollruns')->assertSuccessful();

            // Assert
            Bus::assertNotDispatched(ProcessCombatLogSegments::class);
        } finally {
            ParsedCombatLog::query()->where('run_id', $runId)->delete();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenSpecEligible_dispatchesJobForReturnedRun(): void
    {
        // Arrange
        Bus::fake();

        $run = $this->makeRun(2001, $this->dungeon->challenge_mode_id, [$this->spec->specialization_id]);

        $criteriaService = $this->makeCriteriaService(eligibleSpecs: collect([$this->spec]));
        $criteriaService->method('shouldParse')->willReturn(true);
        $criteriaService->expects($this->once())->method('recordParsed');
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $this->mockRaiderIOApiService(spreadRuns: [$run]);

        try {
            // Act
            $this->artisan('combatlog:pollruns')->assertSuccessful();

            // Assert
            Bus::assertDispatched(ProcessCombatLogSegments::class);
        } finally {
            ParsedCombatLog::query()->where('run_id', $run->id)->delete();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenAllCriteriaAtThreshold_dispatchesNoJobs(): void
    {
        // Arrange
        Bus::fake();

        $criteriaService = $this->makeCriteriaService();
        $criteriaService->expects($this->never())->method('shouldParse');
        $criteriaService->expects($this->never())->method('recordParsed');
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $this->mockRaiderIOApiService();

        // Act
        $this->artisan('combatlog:pollruns')->assertSuccessful();

        // Assert
        Bus::assertNotDispatched(ProcessCombatLogSegments::class);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenCriterionReachesThresholdMidLoop_skipsRemainingRuns(): void
    {
        // Arrange
        Bus::fake();

        $run1 = $this->makeRun(3001, $this->dungeon->challenge_mode_id);
        $run2 = $this->makeRun(3002, $this->dungeon->challenge_mode_id);

        $criteriaService = $this->makeCriteriaService(eligibleDungeons: collect([$this->dungeon]));
        // Pre-check passes, but inner check fails after first run is processed
        $criteriaService->method('shouldParse')->willReturnOnConsecutiveCalls(true, true, false);
        $criteriaService->expects($this->once())->method('recordParsed');
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $this->mockRaiderIOApiService(spreadRuns: [$run1, $run2]);

        try {
            // Act
            $this->artisan('combatlog:pollruns')->assertSuccessful();

            // Assert — only run1 dispatched; run2 skipped because criterion reached threshold
            Bus::assertDispatchedTimes(ProcessCombatLogSegments::class, 1);
        } finally {
            ParsedCombatLog::query()->whereIn('run_id', [$run1->id, $run2->id])->delete();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenCriterionAlreadyAtThresholdBeforeApiCall_skipsApiCall(): void
    {
        // Arrange
        Bus::fake();

        $criteriaService = $this->makeCriteriaService(eligibleDungeons: collect([$this->dungeon]));
        // Pre-check fails immediately — criterion was filled by an earlier dispatch
        $criteriaService->method('shouldParse')->willReturn(false);
        $criteriaService->expects($this->never())->method('recordParsed');
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $this->mockRaiderIOApiService();

        // Act
        $this->artisan('combatlog:pollruns')->assertSuccessful();

        // Assert — the only call left is the top band one, which never consults a budget
        Bus::assertNotDispatched(ProcessCombatLogSegments::class);
        $this->assertSame([$this->topBand->min], $this->capturedMythicLevelMins);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenThisHoursBand_queriesRaiderIOForThatBandOnly(): void
    {
        // Arrange
        Bus::fake();

        $criteriaService = $this->makeCriteriaService(eligibleDungeons: collect([$this->dungeon]));
        $criteriaService->method('shouldParse')->willReturn(true);
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $this->mockRaiderIOApiService();

        // Act
        $this->artisan('combatlog:pollruns')->assertSuccessful();

        // Assert — one call for this hour's spread band, one for the top band
        $this->assertSame([$this->spreadBand->min, $this->topBand->min], $this->capturedMythicLevelMins);
        $this->assertSame([$this->spreadBand->max, null], $this->capturedMythicLevelMaxes);
    }

    /**
     * The whole point of the top band: those runs are parsed no matter how full the day's budgets
     * already are.
     *
     * @throws Exception
     */
    #[Test]
    public function handle_givenTopBandRunAndExhaustedBudgets_dispatchesRunAnyway(): void
    {
        // Arrange
        Bus::fake();

        $run = $this->makeRun(4001, $this->dungeon->challenge_mode_id, mythicLevel: 23);

        $criteriaService = $this->makeCriteriaService(eligibleDungeons: collect([$this->dungeon]));
        $criteriaService->method('shouldParse')->willReturn(false);
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $this->mockRaiderIOApiService(topRuns: [$run]);

        try {
            // Act
            $this->artisan('combatlog:pollruns')->assertSuccessful();

            // Assert
            Bus::assertDispatchedTimes(ProcessCombatLogSegments::class, 1);
        } finally {
            ParsedCombatLog::query()->where('run_id', $run->id)->delete();
        }
    }

    /**
     * Counting an always-parsed run against the spread bands would let the top band eat the budget
     * of the very bands it is supposed to leave alone.
     *
     * @throws Exception
     */
    #[Test]
    public function handle_givenTopBandRun_recordsItAgainstTheTopBandOnly(): void
    {
        // Arrange
        Bus::fake();

        $run = $this->makeRun(4002, $this->dungeon->challenge_mode_id, mythicLevel: 23);

        /** @var array<int, CombatLogParsingCriterionCheck> $recordedCriteria */
        $recordedCriteria = [];

        $criteriaService = $this->makeCriteriaService(eligibleDungeons: collect([$this->dungeon]));
        $criteriaService->method('shouldParse')->willReturn(false);
        $criteriaService->method('recordParsed')->willReturnCallback(
            function (int $version, array $criteria) use (&$recordedCriteria): void {
                $recordedCriteria = array_merge($recordedCriteria, $criteria);
            },
        );
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $this->mockRaiderIOApiService(topRuns: [$run]);

        try {
            // Act
            $this->artisan('combatlog:pollruns')->assertSuccessful();

            // Assert
            $this->assertNotEmpty($recordedCriteria);
            foreach ($recordedCriteria as $criterion) {
                $this->assertTrue($criterion->getBand()->isTopBand());
                $this->assertSame($this->topBand->min, $criterion->getBand()->min);
            }
        } finally {
            ParsedCombatLog::query()->where('run_id', $run->id)->delete();
        }
    }

    /**
     * The same run legitimately comes back from several criterion queries and again from the top
     * band. Dispatching it more than once wastes a parse, and inserting it twice trips the unique
     * index on parsed_combat_logs.run_id.
     *
     * @throws Exception
     */
    #[Test]
    public function handle_givenSameRunReturnedByMultipleBands_dispatchesItOnce(): void
    {
        // Arrange
        Bus::fake();

        $run = $this->makeRun(5001, $this->dungeon->challenge_mode_id, mythicLevel: 23);

        $criteriaService = $this->makeCriteriaService(eligibleDungeons: collect([$this->dungeon]));
        $criteriaService->method('shouldParse')->willReturn(true);
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $this->mockRaiderIOApiService(spreadRuns: [$run], topRuns: [$run]);

        try {
            // Act
            $this->artisan('combatlog:pollruns')->assertSuccessful();

            // Assert
            Bus::assertDispatchedTimes(ProcessCombatLogSegments::class, 1);
            $this->assertSame(1, ParsedCombatLog::query()->where('run_id', $run->id)->count());
        } finally {
            ParsedCombatLog::query()->where('run_id', $run->id)->delete();
        }
    }

    /**
     * parsed_combat_logs grows into the many thousands of rows over a season, so the already-parsed
     * check must never load the table - it looks up only the run ids of the batch it just received.
     *
     * @throws Exception
     */
    #[Test]
    public function handle_givenRunsReturned_looksUpOnlyTheRunIdsOfThatBatch(): void
    {
        // Arrange
        Bus::fake();

        $run = $this->makeRun(5002, $this->dungeon->challenge_mode_id);

        $criteriaService = $this->makeCriteriaService(eligibleDungeons: collect([$this->dungeon]));
        $criteriaService->method('shouldParse')->willReturn(true);
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $this->mockRaiderIOApiService(spreadRuns: [$run]);

        /** @var string[] $selects */
        $selects = [];
        DB::connection('combatlog')->listen(function (QueryExecuted $query) use (&$selects): void {
            if (str_contains($query->sql, 'parsed_combat_logs') && str_starts_with($query->sql, 'select')) {
                $selects[] = $query->sql;
            }
        });

        try {
            // Act
            $this->artisan('combatlog:pollruns')->assertSuccessful();

            // Assert - every read of the table is scoped to the run ids of a single response
            $this->assertNotEmpty($selects);
            foreach ($selects as $sql) {
                $this->assertStringContainsString('`run_id` in (', $sql);
            }
        } finally {
            ParsedCombatLog::query()->where('run_id', $run->id)->delete();
        }
    }

    /**
     * @param  Collection<int, Dungeon>|null                       $eligibleDungeons
     * @param  Collection<int, CharacterClassSpecialization>|null  $eligibleSpecs
     * @throws Exception
     * @return MockObject&CombatLogParsingCriteriaServiceInterface
     */
    private function makeCriteriaService(?Collection $eligibleDungeons = null, ?Collection $eligibleSpecs = null): MockObject
    {
        $criteriaService = $this->createMockPublic(CombatLogParsingCriteriaServiceInterface::class);
        $criteriaService->method('getAllModelsForCriteria')->willReturnCallback(
            fn(string $modelClass) => match ($modelClass) {
                Dungeon::class                      => collect([$this->dungeon]),
                CharacterClassSpecialization::class => CharacterClassSpecialization::query()->get(),
                default                             => collect(),
            },
        );
        $criteriaService->method('getModelsEligibleForPolling')->willReturnCallback(
            fn(int $version, string $modelClass) => match ($modelClass) {
                Dungeon::class                      => $eligibleDungeons ?? collect(),
                CharacterClassSpecialization::class => $eligibleSpecs ?? collect(),
                default                             => collect(),
            },
        );

        return $criteriaService;
    }

    /**
     * @param  SearchAdvancedRun[] $spreadRuns
     * @param  SearchAdvancedRun[] $topRuns
     * @throws Exception
     */
    private function mockRaiderIOApiService(array $spreadRuns = [], array $topRuns = []): void
    {
        $this->capturedMythicLevelMins  = [];
        $this->capturedMythicLevelMaxes = [];

        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->method('searchAdvancedRuns')->willReturnCallback(
            function (SearchAdvancedRunsFilter $filter) use ($spreadRuns, $topRuns): SearchAdvancedRunsResponse {
                $this->capturedMythicLevelMins[]  = $filter->mythicLevelMin;
                $this->capturedMythicLevelMaxes[] = $filter->mythicLevelMax;

                $runs = $filter->mythicLevelMax === null ? $topRuns : $spreadRuns;

                return new SearchAdvancedRunsResponse($runs, count($runs));
            },
        );
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);
    }

    /**
     * @param array<int, int> $memberSpecIds
     */
    private function makeRun(
        int   $id,
        int   $challengeModeId,
        array $memberSpecIds = [66, 70, 105, 250, 269],
        int   $mythicLevel = 14,
    ): SearchAdvancedRun {
        return new SearchAdvancedRun(
            id:              $id,
            challengeModeId: $challengeModeId,
            dungeonZoneId:   $this->dungeon->zone_id ?? 0,
            memberSpecIds:   $memberSpecIds,
            mythicLevel:     $mythicLevel,
            affixes:         [],
        );
    }
}
