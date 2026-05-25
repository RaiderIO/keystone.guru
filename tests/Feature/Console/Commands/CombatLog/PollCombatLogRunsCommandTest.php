<?php

namespace Tests\Feature\Console\Commands\CombatLog;

use App\Jobs\CombatLog\FetchCombatLogRunFanout;
use App\Logic\CombatLog\CombatLogVersion;
use App\Models\CharacterClassSpecialization;
use App\Models\CombatLog\CombatLogParsingCriterion;
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
    private int $combatLogVersion;

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

        $this->combatLogVersion = array_key_last(CombatLogVersion::RETAIL_ALL);
        $this->dungeon          = Dungeon::query()->whereNotNull('challenge_mode_id')->first();
        $this->spec             = CharacterClassSpecialization::query()->first();
        $this->season           = Season::query()->first();

        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeason')->willReturn($this->season);
        app()->instance(SeasonServiceInterface::class, $seasonService);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenDungeonCriteriaBelowThreshold_dispatchesJobsForReturnedRuns(): void
    {
        // Arrange
        Bus::fake();

        $dungeonCriterion = $this->makeDungeonCriterion($this->dungeon->id);
        $run              = $this->makeRun(1001, $this->dungeon->challenge_mode_id);

        $criteriaService = $this->createMockPublic(CombatLogParsingCriteriaServiceInterface::class);
        $criteriaService->method('getBelowThresholdCriteria')->willReturnCallback(
            fn(int $version, string $modelClass) => match ($modelClass) {
                Dungeon::class                      => collect([$dungeonCriterion]),
                CharacterClassSpecialization::class => collect(),
                default                             => collect(),
            },
        );
        $criteriaService->expects($this->once())->method('shouldParse')->willReturn(true);
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
            Bus::assertDispatched(FetchCombatLogRunFanout::class);
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

        $runId            = 9001;
        $dungeonCriterion = $this->makeDungeonCriterion($this->dungeon->id);
        $run              = $this->makeRun($runId, $this->dungeon->challenge_mode_id);

        $criteriaService = $this->createMockPublic(CombatLogParsingCriteriaServiceInterface::class);
        $criteriaService->method('getBelowThresholdCriteria')->willReturnCallback(
            fn(int $version, string $modelClass) => match ($modelClass) {
                Dungeon::class                      => collect([$dungeonCriterion]),
                CharacterClassSpecialization::class => collect(),
                default                             => collect(),
            },
        );
        $criteriaService->expects($this->never())->method('shouldParse');
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
            Bus::assertNotDispatched(FetchCombatLogRunFanout::class);
        } finally {
            ParsedCombatLog::query()->where('run_id', $runId)->delete();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenDungeonAtThresholdButSpecBelow_dispatchesJobInPhase2(): void
    {
        // Arrange
        Bus::fake();

        $specCriterion = $this->makeSpecCriterion($this->spec->id);
        $run           = $this->makeRun(2001, $this->dungeon->challenge_mode_id, [$this->spec->specialization_id]);

        $criteriaService = $this->createMockPublic(CombatLogParsingCriteriaServiceInterface::class);
        $criteriaService->method('getBelowThresholdCriteria')->willReturnCallback(
            fn(int $version, string $modelClass) => match ($modelClass) {
                Dungeon::class                      => collect(),
                CharacterClassSpecialization::class => collect([$specCriterion]),
                default                             => collect(),
            },
        );
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
            Bus::assertDispatched(FetchCombatLogRunFanout::class);
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
        $criteriaService->method('getBelowThresholdCriteria')->willReturn(collect());
        $criteriaService->expects($this->never())->method('shouldParse');
        $criteriaService->expects($this->never())->method('recordParsed');
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $raiderIOService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOService->expects($this->never())->method('searchAdvancedRuns');
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOService);

        // Act
        $this->artisan('combatlog:pollruns')->assertSuccessful();

        // Assert
        Bus::assertNotDispatched(FetchCombatLogRunFanout::class);
    }

    private function makeDungeonCriterion(int $dungeonId): CombatLogParsingCriterion
    {
        $criterion              = new CombatLogParsingCriterion();
        $criterion->model_class = Dungeon::class;
        $criterion->model_id    = $dungeonId;

        return $criterion;
    }

    private function makeSpecCriterion(int $specId): CombatLogParsingCriterion
    {
        $criterion              = new CombatLogParsingCriterion();
        $criterion->model_class = CharacterClassSpecialization::class;
        $criterion->model_id    = $specId;

        return $criterion;
    }

    private function makeRun(int $id, int $challengeModeId, array $memberSpecIds = [66, 70, 105, 250, 269]): SearchAdvancedRun
    {
        return new SearchAdvancedRun(
            id:              $id,
            challengeModeId: $challengeModeId,
            dungeonZoneId:   $this->dungeon->zone_id ?? 0,
            memberSpecIds:   $memberSpecIds,
        );
    }
}
