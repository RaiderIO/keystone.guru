<?php

namespace App\Console\Commands\CombatLog;

use App\Jobs\CombatLog\FetchCombatLogRunFanout;
use App\Logic\CombatLog\CombatLogVersion;
use App\Models\CharacterClassSpecialization;
use App\Models\CombatLog\ParsedCombatLog;
use App\Models\Dungeon;
use App\Service\CombatLog\CombatLogParsingCriteriaServiceInterface;
use App\Service\CombatLog\Dtos\CombatLogParsingCriterionCheck;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsFilter;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PollCombatLogRunsCommand extends Command
{
    protected $signature = 'combatlog:pollruns';

    protected $description = 'Polls Raider.IO for new M+ runs and dispatches combat log processing jobs.';

    public function __construct(
        private readonly CombatLogParsingCriteriaServiceInterface $criteriaService,
        private readonly RaiderIOApiServiceInterface              $raiderIOApiService,
        private readonly SeasonServiceInterface                   $seasonService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $season = $this->seasonService->getCurrentSeason();

        if ($season === null) {
            $this->warn('No current season found - skipping combat log polling.');

            return self::SUCCESS;
        }

        $combatLogVersion = array_key_last(CombatLogVersion::RETAIL_ALL);

        $dungeonCriteria = $this->criteriaService->getBelowThresholdCriteria($combatLogVersion, Dungeon::class);
        $specCriteria    = $this->criteriaService->getBelowThresholdCriteria($combatLogVersion, CharacterClassSpecialization::class);

        if ($dungeonCriteria->isEmpty() && $specCriteria->isEmpty()) {
            $this->info('All criteria at threshold - nothing to poll.');

            return self::SUCCESS;
        }

        $completedAtFrom = Carbon::now()->subDays((int)config('keystoneguru.raider_io.combat_log_polling.completed_at_window_days'));
        $mythicLevelMin  = (int)config('keystoneguru.raider_io.combat_log_polling.mythic_level_min');
        $limit           = (int)config('keystoneguru.raider_io.combat_log_polling.limit');

        $allDungeons = Dungeon::query()->get();

        $dungeonsByModelId = $allDungeons->keyBy('id');

        $belowThresholdSpecs = CharacterClassSpecialization::query()
            ->whereIn('id', $specCriteria->pluck('model_id'))
            ->get();

        $allSpecsByBlizzardId = CharacterClassSpecialization::query()
            ->get()
            ->keyBy('specialization_id');

        $existingRunIds = ParsedCombatLog::query()
            ->whereNotNull('run_id')
            ->pluck('run_id')
            ->flip()
            ->all();

        // Phase 1 — dungeon-based: each dungeon below threshold gets its own targeted query
        foreach ($dungeonCriteria as $criterion) {
            /** @var ?Dungeon $dungeon */
            $dungeon = $dungeonsByModelId->get($criterion->model_id);

            if ($dungeon === null) {
                continue;
            }

            $filter = new SearchAdvancedRunsFilter(
                dungeon:         $dungeon,
                season:          $season,
                specs:           $belowThresholdSpecs,
                completedAtFrom: $completedAtFrom,
                completedAtTo:   null,
                mythicLevelMin:  $mythicLevelMin,
                limit:           $limit,
                offset:          0,
            );
            $response = $this->raiderIOApiService->searchAdvancedRuns($filter);

            foreach ($response->runs as $run) {
                if (isset($existingRunIds[$run->id])) {
                    continue;
                }

                $dungeonCriterion = new CombatLogParsingCriterionCheck(Dungeon::class, $dungeon->id);

                if (!$this->criteriaService->shouldParse($combatLogVersion, [$dungeonCriterion])) {
                    break;
                }

                $specCriteriaForRun = $this->buildSpecCriteria($run->memberSpecIds, $allSpecsByBlizzardId);

                $this->dispatchRun($run->id, $combatLogVersion, $dungeonCriterion, $specCriteriaForRun, $existingRunIds);
            }
        }

        // Phase 2 — spec-based: a single query without dungeon filter catches runs from maxed-out dungeons
        if ($belowThresholdSpecs->isNotEmpty()) {
            $belowThresholdSpecBlizzardIds = $belowThresholdSpecs->pluck('specialization_id')->all();
            $dungeonsByChallengeModeId     = $allDungeons->keyBy('challenge_mode_id');

            $filter = new SearchAdvancedRunsFilter(
                dungeon:         null,
                season:          $season,
                specs:           $belowThresholdSpecs,
                completedAtFrom: $completedAtFrom,
                completedAtTo:   null,
                mythicLevelMin:  $mythicLevelMin,
                limit:           $limit,
                offset:          0,
            );
            $response = $this->raiderIOApiService->searchAdvancedRuns($filter);

            foreach ($response->runs as $run) {
                if (isset($existingRunIds[$run->id])) {
                    continue;
                }

                if (empty(array_intersect($run->memberSpecIds, $belowThresholdSpecBlizzardIds))) {
                    continue;
                }

                /** @var ?Dungeon $dungeon */
                $dungeon = $dungeonsByChallengeModeId->get($run->challengeModeId);

                if ($dungeon === null) {
                    continue;
                }

                $dungeonCriterion   = new CombatLogParsingCriterionCheck(Dungeon::class, $dungeon->id);
                $specCriteriaForRun = $this->buildSpecCriteria($run->memberSpecIds, $allSpecsByBlizzardId);

                $this->dispatchRun($run->id, $combatLogVersion, $dungeonCriterion, $specCriteriaForRun, $existingRunIds);
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param CombatLogParsingCriterionCheck[] $specCriteria
     * @param array<int, true>                 $existingRunIds
     */
    private function dispatchRun(
        int                            $runId,
        int                            $combatLogVersion,
        CombatLogParsingCriterionCheck $dungeonCriterion,
        array                          $specCriteria,
        array                        &                          $existingRunIds,
    ): void {
        $this->criteriaService->recordParsed(
            $combatLogVersion,
            array_merge([$dungeonCriterion], $specCriteria),
        );

        ParsedCombatLog::create(['run_id' => $runId]);
        $existingRunIds[$runId] = true;

        FetchCombatLogRunFanout::dispatch($runId, $combatLogVersion);
    }

    /**
     * @param  int[]                                         $memberSpecIds
     * @param  Collection<int, CharacterClassSpecialization> $specsByBlizzardId
     * @return CombatLogParsingCriterionCheck[]
     */
    private function buildSpecCriteria(array $memberSpecIds, Collection $specsByBlizzardId): array
    {
        $criteria = [];

        foreach ($memberSpecIds as $blizzardSpecId) {
            $spec = $specsByBlizzardId->get($blizzardSpecId);

            if ($spec !== null) {
                $criteria[] = new CombatLogParsingCriterionCheck(CharacterClassSpecialization::class, $spec->id);
            }
        }

        return $criteria;
    }
}
