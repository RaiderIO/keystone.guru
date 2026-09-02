<?php

namespace App\Service\CombatLog;

use App\Models\CombatLog\CombatLogParsingCriterion;
use App\Models\Interfaces\CombatLogCriterionModelInterface;
use App\Models\Season;
use App\Service\CombatLog\Dtos\CombatLogParsingCriterionCheck;
use App\Service\CombatLog\Dtos\KeyLevelBand;
use App\Service\CombatLog\Dtos\PollingBudgetWindow;
use Illuminate\Support\Collection;

interface CombatLogParsingCriteriaServiceInterface
{
    /**
     * Returns true if ALL given criteria counts for today are below the share of their configured
     * thresholds that the given budget window has released so far. Criteria in the top band are
     * always parseable: those runs bypass the budgets entirely.
     *
     * Note: call recordParsed() immediately when this returns true (at webhook accept time,
     * not after processing) so concurrent requests see updated counts.
     *
     * @param CombatLogParsingCriterionCheck[] $criteria
     */
    public function shouldParse(int $combatLogVersion, array $criteria, PollingBudgetWindow $budgetWindow): bool;

    /**
     * Increments the count for each given criterion on the given date (today when omitted).
     * Must be called immediately when a combat log is accepted for processing.
     *
     * @param CombatLogParsingCriterionCheck[] $criteria
     */
    public function recordParsed(int $combatLogVersion, array $criteria, ?string $date = null): void;

    /**
     * Gives back what recordParsed() took: decrements the count for each given criterion on the
     * date it was recorded on. Called when a run that was recorded as parsed turns out to yield no
     * data at all (unavailable segments, a failed download, an unparsable log), so that the budget
     * it consumed goes to a run that does yield data instead.
     *
     * @param CombatLogParsingCriterionCheck[] $criteria
     */
    public function releaseParsed(int $combatLogVersion, array $criteria, string $date): void;

    /**
     * Resets all criterion counts for today (UTC date) to zero.
     */
    public function resetAllForToday(): void;

    /**
     * Returns all criteria rows for today where count < threshold for the given model class.
     *
     * @return Collection<int, CombatLogParsingCriterion>
     */
    public function getBelowThresholdCriteria(int $combatLogVersion, string $modelClass): Collection;

    /**
     * Returns all model instances that are valid polling targets for the given criteria model class.
     * - Dungeon: all dungeons belonging to the given season
     * - CharacterClassSpecialization: all specializations
     *
     * @param  class-string<CombatLogCriterionModelInterface>    $modelClass
     * @return Collection<int, CombatLogCriterionModelInterface>
     */
    public function getAllModelsForCriteria(string $modelClass, Season $season): Collection;

    /**
     * Returns all models from getAllModelsForCriteria() that are still eligible for polling in the
     * given band during the given budget window: models with no row yet for that band (implicit
     * count = 0) and models whose count is still below the share of their threshold that the
     * window has released. Every model is eligible in the top band.
     *
     * @param  class-string<CombatLogCriterionModelInterface>    $modelClass
     * @return Collection<int, CombatLogCriterionModelInterface>
     */
    public function getModelsEligibleForPolling(
        int                 $combatLogVersion,
        string              $modelClass,
        Season              $season,
        KeyLevelBand        $band,
        PollingBudgetWindow $budgetWindow,
    ): Collection;
}
