<?php

namespace App\Console\Commands\CombatLog;

use App\Jobs\CombatLog\ProcessCombatLogSegments;
use App\Logic\CombatLog\CombatLogVersion;
use App\Models\CharacterClassSpecialization;
use App\Models\CharacterRace;
use App\Models\CombatLog\CombatLogParsingCriterion;
use App\Models\CombatLog\ParsedCombatLog;
use App\Models\Dungeon;
use App\Models\Interfaces\CombatLogCriterionModelInterface;
use App\Models\Season;
use App\Service\CombatLog\CombatLogParsingCriteriaServiceInterface;
use App\Service\CombatLog\CombatLogPollingBandServiceInterface;
use App\Service\CombatLog\CombatLogPollingHealthServiceInterface;
use App\Service\CombatLog\Dtos\CombatLogParsingCriterionCheck;
use App\Service\CombatLog\Dtos\CombatLogRunContext;
use App\Service\CombatLog\Dtos\KeyLevelBand;
use App\Service\CombatLog\Enums\CombatLogPollingFailureReason;
use App\Service\RaiderIO\Dtos\SearchAdvancedRun;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsFilter;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsResponse;
use App\Service\RaiderIO\Enums\RaiderIOFaction;
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
        private readonly CombatLogPollingHealthServiceInterface   $healthService,
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

        // Runs that must not be dispatched (again): those already parsed in an earlier invocation,
        // and those dispatched earlier in this one. Filled in per API response instead of up front -
        // parsed_combat_logs grows into the many thousands over a season, and every run id we could
        // care about is already right there in the response we just received.
        /** @var array<int, true> $knownRunIds */
        $knownRunIds = [];

        $this->info(sprintf(
            'combatlog:pollruns — season=%s version=%d window=%dd limit=%d force=%s | bands=[%s] this_hour=%s top=%s',
            $season->name,
            $combatLogVersion,
            $windowDays,
            $limit,
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
        /** @var Collection<int, CharacterRace> $criterionRaces */
        $criterionRaces = $this->criteriaService->getAllModelsForCriteria(CharacterRace::class, $season);

        if ($spreadBand !== null) {
            $this->pollSpreadBand(
                $season,
                $combatLogVersion,
                $spreadBand,
                $completedAtFrom,
                $limit,
                $dungeonsByChallengeModeId,
                $allSpecsByBlizzardId,
                $criterionRaces,
                $knownRunIds,
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
            $criterionRaces,
            $knownRunIds,
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
     * @param Collection<int, CharacterRace>                       $criterionRaces
     * @param array<int, true>                                     $knownRunIds
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
        Collection   $criterionRaces,
        array        &        $knownRunIds,
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

                $this->recordSearchFailure($response);

                $this->markAlreadyParsedRuns($response->runs, $knownRunIds, $force);

                foreach ($response->runs as $run) {
                    if (isset($knownRunIds[$run->id])) {
                        $dispatchCounts['skippedParsed']++;
                        continue;
                    }

                    if (!$this->criteriaService->shouldParse($combatLogVersion, [$primaryCheck])) {
                        break;
                    }

                    $this->dispatchRun($run, $season, $combatLogVersion, $band, $dungeonsByChallengeModeId, $allSpecsByBlizzardId, $criterionRaces, $knownRunIds, $force, $dispatchCounts);
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
     * @param Collection<int, CharacterRace>                       $criterionRaces
     * @param array<int, true>                                     $knownRunIds
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
        Collection   $criterionRaces,
        array        &        $knownRunIds,
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

        $this->recordSearchFailure($response);

        $this->markAlreadyParsedRuns($response->runs, $knownRunIds, $force);

        foreach ($response->runs as $run) {
            if (isset($knownRunIds[$run->id])) {
                $dispatchCounts['skippedParsed']++;
                continue;
            }

            $this->dispatchRun($run, $season, $combatLogVersion, $band, $dungeonsByChallengeModeId, $allSpecsByBlizzardId, $criterionRaces, $knownRunIds, $force, $dispatchCounts);
        }

        $this->info(sprintf(
            'combatlog:pollruns — top band %s | available=%s dispatched=%d',
            $band,
            $response->total ?? '?',
            $dispatchCounts['dispatched'] - $dispatchedBefore,
        ));
    }

    /**
     * @param array<int, true>                                     $knownRunIds
     * @param Collection<string|int, Dungeon>                      $dungeonsByChallengeModeId
     * @param Collection<string|int, CharacterClassSpecialization> $allSpecsByBlizzardId
     * @param Collection<int, CharacterRace>                       $criterionRaces
     * @param array<string, int>                                   $dispatchCounts
     */
    private function dispatchRun(
        SearchAdvancedRun $run,
        Season            $season,
        int               $combatLogVersion,
        KeyLevelBand      $band,
        Collection        $dungeonsByChallengeModeId,
        Collection        $allSpecsByBlizzardId,
        Collection        $criterionRaces,
        array             &             $knownRunIds,
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
        $raceCriteria     = $this->buildRaceCriteria($run->faction, $criterionRaces, $band);
        $criteria         = array_merge([$dungeonCriterion], $specCriteria, $raceCriteria);

        // The date is captured here and carried into the job rather than left to Carbon::now(): it is
        // the date these counts land on, and the job hands it back to releaseParsed() if the run turns
        // out to yield nothing - which can be after midnight, on a retried or backlogged job (#4173).
        $criteriaDate = Carbon::now()->toDateString();

        $this->criteriaService->recordParsed($combatLogVersion, $criteria, $criteriaDate);

        if (!$force) {
            ParsedCombatLog::create(['run_id' => $run->id]);
        }

        // Marked even when forcing: the same run legitimately comes back from several criterion
        // queries and from the top band, and dispatching it twice in one invocation helps nobody.
        $knownRunIds[$run->id] = true;

        ProcessCombatLogSegments::dispatch(
            $season,
            $run->id,
            $combatLogVersion,
            new CombatLogRunContext($run->mythicLevel, $run->affixes),
            $criteria,
            $criteriaDate,
        );

        $this->healthService->recordDispatched();

        $dispatchCounts['dispatched']++;
    }

    /**
     * Counts a search that came back with nothing because Raider.IO answered with something that isn't
     * a run listing. A valid response always carries a total, so a null one is the error - an empty
     * result set for a narrow filter is a legitimate answer and is not counted (#4173).
     */
    private function recordSearchFailure(SearchAdvancedRunsResponse $response): void
    {
        if ($response->total === null) {
            $this->healthService->recordFailure(CombatLogPollingFailureReason::SearchApiError);
        }
    }

    /**
     * Marks every run in this batch that was already parsed in an earlier invocation. Only the run
     * ids of this one batch are looked up - bounded by the configured page limit and served by the
     * unique index on run_id - so the size of parsed_combat_logs never enters into it.
     *
     * @param SearchAdvancedRun[] $runs
     * @param array<int, true>    $knownRunIds
     */
    private function markAlreadyParsedRuns(array $runs, array &$knownRunIds, bool $force): void
    {
        if ($force) {
            return;
        }

        $runIdsToCheck = [];

        foreach ($runs as $run) {
            if (!isset($knownRunIds[$run->id])) {
                $runIdsToCheck[] = $run->id;
            }
        }

        if ($runIdsToCheck === []) {
            return;
        }

        foreach (ParsedCombatLog::query()->whereIn('run_id', $runIdsToCheck)->pluck('run_id') as $runId) {
            $knownRunIds[(int)$runId] = true;
        }
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
        $faction = null;

        if ($modelClass === Dungeon::class) {
            /** @var Dungeon $model */
            $dungeon = $model;
            /** @var Collection<int, CharacterClassSpecialization> $specs */
            $specs = collect();
        } elseif ($modelClass === CharacterClassSpecialization::class) {
            $dungeon = null;
            /** @var Collection<int, CharacterClassSpecialization> $specs */
            $specs = collect([$model]);
        } elseif ($modelClass === CharacterRace::class) {
            // Raider.IO knows nothing about races - not as a filter, not even as a field on a run -
            // so a race is polled through the one race adjacent dimension the search API does have:
            // the faction every one of its members belongs to. Narrowing further by the classes the
            // race can be is not possible either: several memberClassIds entries are ANDed, so they
            // ask for a group holding all of those classes at once rather than any one of them.
            $dungeon = null;
            /** @var Collection<int, CharacterClassSpecialization> $specs */
            $specs = collect();
            /** @var CharacterRace $model */
            $faction = $model->faction;
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
            faction:         $faction,
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

    /**
     * A race criterion counts a run when the run's faction is the race's faction - the closest thing
     * to "this run contained that race" that Raider.IO's data allows. A cross faction group reports
     * no faction at all and counts towards no race.
     *
     * Unlike buildSpecCriteria(), which emits an entry per party member and so increments a spec
     * twice for a group running two of it, this emits at most one entry per race: a faction is a
     * property of the whole group, and counting it five times would make the configured threshold
     * mean something different for races than it does for every other criterion.
     *
     * @param  Collection<int, CharacterRace>   $criterionRaces
     * @return CombatLogParsingCriterionCheck[]
     */
    private function buildRaceCriteria(?int $runFaction, Collection $criterionRaces, KeyLevelBand $band): array
    {
        if ($runFaction === null) {
            return [];
        }

        $criteria = [];

        foreach ($criterionRaces as $race) {
            if (RaiderIOFaction::fromFaction($race->faction)?->value === $runFaction) {
                $criteria[] = new CombatLogParsingCriterionCheck(CharacterRace::class, $race->id, $band);
            }
        }

        return $criteria;
    }
}
