<?php

namespace App\Console\Commands\CombatLog;

use App\Jobs\CombatLog\FetchCombatLogRunFanout;
use App\Logic\CombatLog\CombatLogVersion;
use App\Models\CharacterClassSpecialization;
use App\Models\CombatLog\CombatLogParsingCriterion;
use App\Models\CombatLog\ParsedCombatLog;
use App\Models\Dungeon;
use App\Models\Season;
use App\Service\CombatLog\CombatLogParsingCriteriaServiceInterface;
use App\Service\CombatLog\Dtos\CombatLogParsingCriterionCheck;
use App\Service\CombatLog\Dtos\CombatLogRunContext;
use App\Service\RaiderIO\Dtos\SearchAdvancedRun;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsFilter;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PollCombatLogRunsCommand extends Command
{
    protected $signature = 'combatlog:pollruns {--force : Bypass the ParsedCombatLog check and re-queue already-parsed runs}';

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
            $this->warn('combatlog:pollruns — no current season found, skipping');

            return self::SUCCESS;
        }

        $combatLogVersion = array_key_last(CombatLogVersion::RETAIL_ALL);

        $windowDays      = (int)config('keystoneguru.raider_io.combat_log_polling.completed_at_window_days');
        $mythicLevelMin  = (int)config('keystoneguru.raider_io.combat_log_polling.mythic_level_min');
        $limit           = (int)config('keystoneguru.raider_io.combat_log_polling.limit');
        $completedAtFrom = Carbon::now()->subDays($windowDays);

        $allModelsByClass      = [];
        $eligibleModelsByClass = [];
        $criteriaSummary       = [];

        foreach (array_keys(CombatLogParsingCriterion::VALID_CRITERIA) as $modelClass) {
            $allModelsByClass[$modelClass]      = $this->criteriaService->getAllModelsForCriteria($modelClass, $season);
            $eligibleModelsByClass[$modelClass] = $this->criteriaService->getModelsEligibleForPolling($combatLogVersion, $modelClass, $season);

            $shortClass        = class_basename($modelClass);
            $allCount          = $allModelsByClass[$modelClass]->count();
            $eligibleCount     = $eligibleModelsByClass[$modelClass]->count();
            $criteriaSummary[] = sprintf('%s=%d/%d eligible', $shortClass, $eligibleCount, $allCount);
        }

        $force = (bool)$this->option('force');

        $existingRunIds = $force ? [] : ParsedCombatLog::query()
            ->whereNotNull('run_id')
            ->pluck('run_id')
            ->flip()
            ->all();

        $this->info(sprintf(
            'combatlog:pollruns — season=%s version=%d window=%dd min_level=%d limit=%d existing_parsed=%d force=%s | %s',
            $season->name,
            $combatLogVersion,
            $windowDays,
            $mythicLevelMin,
            $limit,
            count($existingRunIds),
            $force ? 'yes' : 'no',
            implode(', ', $criteriaSummary),
        ));

        if (collect($eligibleModelsByClass)->every(fn(Collection $c) => $c->isEmpty())) {
            $this->warn('combatlog:pollruns — all criteria at threshold, nothing to poll');

            return self::SUCCESS;
        }

        $dungeonsByChallengeModeId = $allModelsByClass[Dungeon::class]->keyBy('challenge_mode_id');
        $allSpecsByBlizzardId      = $allModelsByClass[CharacterClassSpecialization::class]->keyBy('specialization_id');

        $totalDispatched       = 0;
        $totalSkippedParsed    = 0;
        $totalSkippedNoDungeon = 0;

        foreach (array_keys(CombatLogParsingCriterion::VALID_CRITERIA) as $modelClass) {
            foreach ($eligibleModelsByClass[$modelClass] as $model) {
                $primaryCheck = new CombatLogParsingCriterionCheck($modelClass, $model->id);

                // Re-evaluate before each API call: prior dispatches may have already
                // recorded enough runs for this criterion via recordParsed().
                if (!$this->criteriaService->shouldParse($combatLogVersion, [$primaryCheck])) {
                    continue;
                }

                $filter   = $this->buildFilterForCriterion($modelClass, $model, $season, $completedAtFrom, $mythicLevelMin, $limit);
                $response = $this->raiderIOApiService->searchAdvancedRuns($filter);

                foreach ($response->runs as $run) {
                    if (isset($existingRunIds[$run->id])) {
                        $totalSkippedParsed++;
                        continue;
                    }

                    if (!$this->criteriaService->shouldParse($combatLogVersion, [$primaryCheck])) {
                        break;
                    }

                    $dispatched = $this->dispatchRun($run, $combatLogVersion, $dungeonsByChallengeModeId, $allSpecsByBlizzardId, $existingRunIds, $force);

                    if ($dispatched) {
                        $totalDispatched++;
                    } else {
                        $totalSkippedNoDungeon++;
                    }
                }
            }
        }

        $this->info(sprintf(
            'combatlog:pollruns — done dispatched=%d skipped_parsed=%d skipped_no_dungeon=%d',
            $totalDispatched,
            $totalSkippedParsed,
            $totalSkippedNoDungeon,
        ));

        return self::SUCCESS;
    }

    /**
     * @param  array<int, true> $existingRunIds
     * @return bool             Whether the run was dispatched (false means the dungeon was not found)
     */
    private function dispatchRun(
        SearchAdvancedRun $run,
        int               $combatLogVersion,
        Collection        $dungeonsByChallengeModeId,
        Collection        $allSpecsByBlizzardId,
        array            &             $existingRunIds,
        bool              $force = false,
    ): bool {
        /** @var ?Dungeon $dungeon */
        $dungeon = $dungeonsByChallengeModeId->get($run->challengeModeId);

        if ($dungeon === null) {
            return false;
        }

        $dungeonCriterion = new CombatLogParsingCriterionCheck(Dungeon::class, $dungeon->id);
        $specCriteria     = $this->buildSpecCriteria($run->memberSpecIds, $allSpecsByBlizzardId);

        $this->criteriaService->recordParsed($combatLogVersion, array_merge([$dungeonCriterion], $specCriteria));

        if (!$force) {
            ParsedCombatLog::create(['run_id' => $run->id]);
            $existingRunIds[$run->id] = true;
        }

        FetchCombatLogRunFanout::dispatch($run->id, $combatLogVersion, new CombatLogRunContext($run->mythicLevel, $run->affixes));

        return true;
    }

    /**
     * @param  class-string             $modelClass
     * @param  Model                    $model
     * @param  Season                   $season
     * @param  Carbon                   $completedAtFrom
     * @param  int                      $mythicLevelMin
     * @param  int                      $limit
     * @return SearchAdvancedRunsFilter
     */
    private function buildFilterForCriterion(
        string $modelClass,
        Model  $model,
        Season $season,
        Carbon $completedAtFrom,
        int    $mythicLevelMin,
        int    $limit,
    ): SearchAdvancedRunsFilter {
        return match ($modelClass) {
            Dungeon::class => new SearchAdvancedRunsFilter(
                dungeon:         $model,
                season:          $season,
                specs:           collect(),
                completedAtFrom: $completedAtFrom,
                completedAtTo:   null,
                mythicLevelMin:  $mythicLevelMin,
                limit:           $limit,
                offset:          0,
            ),
            /** @var CharacterClassSpecialization $model */
            CharacterClassSpecialization::class => new SearchAdvancedRunsFilter(
                dungeon:         null,
                season:          $season,
                specs:           collect([$model]),
                completedAtFrom: $completedAtFrom,
                completedAtTo:   null,
                mythicLevelMin:  $mythicLevelMin,
                limit:           $limit,
                offset:          0,
            ),
        };
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
