<?php

namespace App\Console\Commands\CombatLog;

use App\Jobs\CombatLog\ProcessCombatLogSegments;
use App\Logic\CombatLog\CombatLogVersion;
use App\Models\CharacterClassSpecialization;
use App\Models\CombatLog\CombatLogParsingCriterion;
use App\Models\CombatLog\ParsedCombatLog;
use App\Models\Dungeon;
use App\Models\Interfaces\CombatLogCriterionModelInterface;
use App\Models\Season;
use App\Service\CombatLog\CombatLogParsingCriteriaServiceInterface;
use App\Service\CombatLog\CombatLogPollingBandServiceInterface;
use App\Service\CombatLog\Dtos\CombatLogParsingCriterionCheck;
use App\Service\CombatLog\Dtos\CombatLogRunContext;
use App\Service\CombatLog\Dtos\KeyLevelBand;
use App\Service\RaiderIO\Dtos\SearchAdvancedRun;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsFilter;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PollCombatLogRunsCommand extends Command
{
    protected $signature = 'combatlog:pollruns {--force : Bypass the ParsedCombatLog check and re-queue already-parsed runs}';

    protected $description = 'Polls Raider.IO for new M+ runs and dispatches combat log processing jobs.';

    public function __construct(
        private readonly CombatLogParsingCriteriaServiceInterface $criteriaService,
        private readonly CombatLogPollingBandServiceInterface     $bandService,
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
        $limit           = (int)config('keystoneguru.raider_io.combat_log_polling.limit');
        $completedAtFrom = Carbon::now()->subDays($windowDays);

        // Only one band is polled per hour: polling all of them every hour would multiply the
        // number of Raider.IO calls by the band count for no extra coverage.
        $spreadBand = $this->bandService->getSpreadBandForHour($season, Carbon::now()->hour);
        $topBand    = $this->bandService->getTopBand($season);

        $force = (bool)$this->option('force');

        /** @var array<int, true> $existingRunIds */
        $existingRunIds = $force ? [] : ParsedCombatLog::query()
            ->whereNotNull('run_id')
            ->pluck('run_id')
            ->flip()
            ->all();

        $this->info(sprintf(
            'combatlog:pollruns — season=%s version=%d window=%dd limit=%d existing_parsed=%d force=%s | bands=[%s] this_hour=%s top=%s',
            $season->name,
            $combatLogVersion,
            $windowDays,
            $limit,
            count($existingRunIds),
            $force ? 'yes' : 'no',
            implode(', ', array_map(strval(...), $this->bandService->getSpreadBands($season))),
            $spreadBand === null ? 'none' : (string)$spreadBand,
            (string)$topBand,
        ));

        $dispatchCounts = [
            'dispatched'       => 0,
            'skippedParsed'    => 0,
            'skippedNoDungeon' => 0,
        ];

        /** @var Collection<int|string, Dungeon> $dungeonsByChallengeModeId */
        $dungeonsByChallengeModeId = $this->criteriaService->getAllModelsForCriteria(Dungeon::class, $season)
            ->keyBy('challenge_mode_id');
        /** @var Collection<int|string, CharacterClassSpecialization> $allSpecsByBlizzardId */
        $allSpecsByBlizzardId = $this->criteriaService->getAllModelsForCriteria(CharacterClassSpecialization::class, $season)
            ->keyBy('specialization_id');

        if ($spreadBand !== null) {
            $this->pollSpreadBand(
                $season,
                $combatLogVersion,
                $spreadBand,
                $completedAtFrom,
                $limit,
                $dungeonsByChallengeModeId,
                $allSpecsByBlizzardId,
                $existingRunIds,
                $force,
                $dispatchCounts,
            );
        }

        $this->pollTopBand(
            $season,
            $combatLogVersion,
            $topBand,
            $completedAtFrom,
            $limit,
            $dungeonsByChallengeModeId,
            $allSpecsByBlizzardId,
            $existingRunIds,
            $force,
            $dispatchCounts,
        );

        $this->info(sprintf(
            'combatlog:pollruns — done dispatched=%d skipped_parsed=%d skipped_no_dungeon=%d',
            $dispatchCounts['dispatched'],
            $dispatchCounts['skippedParsed'],
            $dispatchCounts['skippedNoDungeon'],
        ));

        return self::SUCCESS;
    }

    /**
     * Polls each dungeon and each spec that still has budget left in this hour's band.
     *
     * @param Collection<string|int, Dungeon>                      $dungeonsByChallengeModeId
     * @param Collection<string|int, CharacterClassSpecialization> $allSpecsByBlizzardId
     * @param array<int, true>                                     $existingRunIds
     * @param array<string, int>                                   $dispatchCounts
     */
    private function pollSpreadBand(
        Season       $season,
        int          $combatLogVersion,
        KeyLevelBand $band,
        Carbon       $completedAtFrom,
        int          $limit,
        Collection   $dungeonsByChallengeModeId,
        Collection   $allSpecsByBlizzardId,
        array        &        $existingRunIds,
        bool         $force,
        array        &        $dispatchCounts,
    ): void {
        $criteriaSummary = [];

        foreach (array_keys(CombatLogParsingCriterion::VALID_CRITERIA) as $modelClass) {
            $eligibleModels = $this->criteriaService->getModelsEligibleForPolling($combatLogVersion, $modelClass, $season, $band);

            $criteriaSummary[] = sprintf('%s=%d eligible', class_basename($modelClass), $eligibleModels->count());

            foreach ($eligibleModels as $model) {
                $primaryCheck = new CombatLogParsingCriterionCheck($modelClass, $model->getKey(), $band);

                // Re-evaluate before each API call: prior dispatches may have already
                // recorded enough runs for this criterion via recordParsed().
                if (!$this->criteriaService->shouldParse($combatLogVersion, [$primaryCheck])) {
                    continue;
                }

                $filter   = $this->buildFilterForCriterion($modelClass, $model, $season, $band, $completedAtFrom, $limit);
                $response = $this->raiderIOApiService->searchAdvancedRuns($filter);

                foreach ($response->runs as $run) {
                    if (isset($existingRunIds[$run->id])) {
                        $dispatchCounts['skippedParsed']++;
                        continue;
                    }

                    if (!$this->criteriaService->shouldParse($combatLogVersion, [$primaryCheck])) {
                        break;
                    }

                    $this->dispatchRun($run, $season, $combatLogVersion, $band, $dungeonsByChallengeModeId, $allSpecsByBlizzardId, $existingRunIds, $force, $dispatchCounts);
                }
            }
        }

        $this->info(sprintf('combatlog:pollruns — band %s | %s', $band, implode(', ', $criteriaSummary)));
    }

    /**
     * Polls the highest keys of the season in a single query across all dungeons. These runs are
     * always dispatched: no budget is consulted, and they are counted against their own band so
     * they cannot eat into the budgets of the spread bands.
     *
     * @param Collection<string|int, Dungeon>                      $dungeonsByChallengeModeId
     * @param Collection<string|int, CharacterClassSpecialization> $allSpecsByBlizzardId
     * @param array<int, true>                                     $existingRunIds
     * @param array<string, int>                                   $dispatchCounts
     */
    private function pollTopBand(
        Season       $season,
        int          $combatLogVersion,
        KeyLevelBand $band,
        Carbon       $completedAtFrom,
        int          $limit,
        Collection   $dungeonsByChallengeModeId,
        Collection   $allSpecsByBlizzardId,
        array        &        $existingRunIds,
        bool         $force,
        array        &        $dispatchCounts,
    ): void {
        $dispatchedBefore = $dispatchCounts['dispatched'];

        $response = $this->raiderIOApiService->searchAdvancedRuns(new SearchAdvancedRunsFilter(
            dungeon:         null,
            season:          $season,
            specs:           collect(),
            completedAtFrom: $completedAtFrom,
            completedAtTo:   null,
            mythicLevelMin:  $band->min,
            mythicLevelMax:  $band->max,
            limit:           $limit,
            offset:          0,
        ));

        foreach ($response->runs as $run) {
            if (isset($existingRunIds[$run->id])) {
                $dispatchCounts['skippedParsed']++;
                continue;
            }

            $this->dispatchRun($run, $season, $combatLogVersion, $band, $dungeonsByChallengeModeId, $allSpecsByBlizzardId, $existingRunIds, $force, $dispatchCounts);
        }

        $this->info(sprintf(
            'combatlog:pollruns — top band %s | available=%s dispatched=%d',
            $band,
            $response->total ?? '?',
            $dispatchCounts['dispatched'] - $dispatchedBefore,
        ));
    }

    /**
     * @param array<int, true>                                     $existingRunIds
     * @param Collection<string|int, Dungeon>                      $dungeonsByChallengeModeId
     * @param Collection<string|int, CharacterClassSpecialization> $allSpecsByBlizzardId
     * @param array<string, int>                                   $dispatchCounts
     */
    private function dispatchRun(
        SearchAdvancedRun $run,
        Season            $season,
        int               $combatLogVersion,
        KeyLevelBand      $band,
        Collection        $dungeonsByChallengeModeId,
        Collection        $allSpecsByBlizzardId,
        array             &             $existingRunIds,
        bool              $force,
        array             &             $dispatchCounts,
    ): void {
        /** @var ?Dungeon $dungeon */
        $dungeon = $dungeonsByChallengeModeId->get($run->challengeModeId);

        if ($dungeon === null) {
            $dispatchCounts['skippedNoDungeon']++;

            return;
        }

        $dungeonCriterion = new CombatLogParsingCriterionCheck(Dungeon::class, $dungeon->id, $band);
        $specCriteria     = $this->buildSpecCriteria($run->memberSpecIds, $allSpecsByBlizzardId, $band);

        $this->criteriaService->recordParsed($combatLogVersion, array_merge([$dungeonCriterion], $specCriteria));

        if (!$force) {
            ParsedCombatLog::create(['run_id' => $run->id]);
            $existingRunIds[$run->id] = true;
        }

        ProcessCombatLogSegments::dispatch($season, $run->id, $combatLogVersion, new CombatLogRunContext($run->mythicLevel, $run->affixes));

        $dispatchCounts['dispatched']++;
    }

    /**
     * @param class-string                     $modelClass
     * @param CombatLogCriterionModelInterface $model
     */
    private function buildFilterForCriterion(
        string                           $modelClass,
        CombatLogCriterionModelInterface $model,
        Season                           $season,
        KeyLevelBand                     $band,
        Carbon                           $completedAtFrom,
        int                              $limit,
    ): SearchAdvancedRunsFilter {
        if ($modelClass === Dungeon::class) {
            /** @var Dungeon $model */
            $dungeon = $model;
            /** @var Collection<int, CharacterClassSpecialization> $specs */
            $specs = collect();
        } elseif ($modelClass === CharacterClassSpecialization::class) {
            $dungeon = null;
            /** @var Collection<int, CharacterClassSpecialization> $specs */
            $specs = collect([$model]);
        } else {
            throw new \UnexpectedValueException(sprintf('Unknown model class: %s', $modelClass));
        }

        return new SearchAdvancedRunsFilter(
            dungeon:         $dungeon,
            season:          $season,
            specs:           $specs,
            completedAtFrom: $completedAtFrom,
            completedAtTo:   null,
            mythicLevelMin:  $band->min,
            mythicLevelMax:  $band->max,
            limit:           $limit,
            offset:          0,
        );
    }

    /**
     * @param  int[]                                                $memberSpecIds
     * @param  Collection<int|string, CharacterClassSpecialization> $specsByBlizzardId
     * @return CombatLogParsingCriterionCheck[]
     */
    private function buildSpecCriteria(array $memberSpecIds, Collection $specsByBlizzardId, KeyLevelBand $band): array
    {
        $criteria = [];

        foreach ($memberSpecIds as $blizzardSpecId) {
            /** @var CharacterClassSpecialization|null $spec */
            $spec = $specsByBlizzardId->get($blizzardSpecId);

            if ($spec !== null) {
                $criteria[] = new CombatLogParsingCriterionCheck(CharacterClassSpecialization::class, $spec->id, $band);
            }
        }

        return $criteria;
    }
}
