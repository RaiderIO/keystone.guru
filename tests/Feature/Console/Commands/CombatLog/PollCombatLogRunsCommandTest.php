<?php

namespace Tests\Feature\Console\Commands\CombatLog;

use App\Jobs\CombatLog\ProcessCombatLogSegments;
use App\Models\CharacterClassSpecialization;
use App\Models\CombatLog\ParsedCombatLog;
use App\Models\Dungeon;
use App\Models\Season;
use App\Service\CombatLog\CombatLogParsingCriteriaServiceInterface;
use App\Service\RaiderIO\Dtos\SearchAdvancedRun;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsResponse;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('CombatLog')]
final class PollCombatLogRunsCommandTest extends PublicTestCase
{
    private Dungeon $dungeon;

    private CharacterClassSpecialization $spec;

    private Season $season;

    /**
     * @throws Exception
     */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->dungeon = Dungeon::query()->whereNotNull('challenge_mode_id')->first();
        $this->spec    = CharacterClassSpecialization::query()->first();
        $this->season  = Season::query()->first();

        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeason')->willReturn($this->season);
        app()->instance(SeasonServiceInterface::class, $seasonService);
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
                Dungeon::class                      => collect([$this->dungeon]),
                CharacterClassSpecialization::class => collect(),
                default                             => collect(),
            },
        );
        $criteriaService->method('shouldParse')->willReturn(true);
        $criteriaService->expects($this->once())->method('recordParsed');
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $raiderIOService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOService->expects($this->once())->method('searchAdvancedRuns')
            ->willReturn(new SearchAdvancedRunsResponse([$run], 1));
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOService);

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
                Dungeon::class                      => collect([$this->dungeon]),
                CharacterClassSpecialization::class => collect(),
                default                             => collect(),
            },
        );
        $criteriaService->method('shouldParse')->willReturn(true);
        $criteriaService->expects($this->never())->method('recordParsed');
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $raiderIOService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOService->expects($this->once())->method('searchAdvancedRuns')
            ->willReturn(new SearchAdvancedRunsResponse([$run], 1));
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOService);

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
                Dungeon::class                      => collect(),
                CharacterClassSpecialization::class => collect([$this->spec]),
                default                             => collect(),
            },
        );
        $criteriaService->method('shouldParse')->willReturn(true);
        $criteriaService->expects($this->once())->method('recordParsed');
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $raiderIOService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOService->expects($this->once())->method('searchAdvancedRuns')
            ->willReturn(new SearchAdvancedRunsResponse([$run], 1));
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOService);

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

        $criteriaService = $this->createMockPublic(CombatLogParsingCriteriaServiceInterface::class);
        $criteriaService->method('getAllModelsForCriteria')->willReturn(collect());
        $criteriaService->method('getModelsEligibleForPolling')->willReturn(collect());
        $criteriaService->expects($this->never())->method('shouldParse');
        $criteriaService->expects($this->never())->method('recordParsed');
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $raiderIOService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOService->expects($this->never())->method('searchAdvancedRuns');
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOService);

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
                Dungeon::class                      => collect([$this->dungeon]),
                CharacterClassSpecialization::class => collect(),
                default                             => collect(),
            },
        );
        // Pre-check passes, but inner check fails after first run is processed
        $criteriaService->method('shouldParse')->willReturnOnConsecutiveCalls(true, true, false);
        $criteriaService->expects($this->once())->method('recordParsed');
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $raiderIOService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOService->expects($this->once())->method('searchAdvancedRuns')
            ->willReturn(new SearchAdvancedRunsResponse([$run1, $run2], 2));
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOService);

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
                Dungeon::class                      => collect([$this->dungeon]),
                CharacterClassSpecialization::class => collect(),
                default                             => collect(),
            },
        );
        // Pre-check fails immediately — criterion was filled by an earlier dispatch
        $criteriaService->method('shouldParse')->willReturn(false);
        $criteriaService->expects($this->never())->method('recordParsed');
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $raiderIOService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOService->expects($this->never())->method('searchAdvancedRuns');
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOService);

        // Act
        $this->artisan('combatlog:pollruns')->assertSuccessful();

        // Assert
        Bus::assertNotDispatched(ProcessCombatLogSegments::class);
    }

    /**
     * @param array<int, int> $memberSpecIds
     */
    private function makeRun(int $id, int $challengeModeId, array $memberSpecIds = [66, 70, 105, 250, 269]): SearchAdvancedRun
    {
        return new SearchAdvancedRun(
            id:              $id,
            challengeModeId: $challengeModeId,
            dungeonZoneId:   $this->dungeon->zone_id ?? 0,
            memberSpecIds:   $memberSpecIds,
            mythicLevel:     10,
            affixes:         [],
        );
    }
}
