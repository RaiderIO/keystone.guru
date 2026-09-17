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
     * Creates today's criterion rows for every model of every criterion model class in the given
     * band, leaving the count of a row that already exists alone.
     *
     * The spread bands materialise their rows as a side effect of shouldParse(), which the top band
     * never reaches - it has no budget to check, so its rows are only ever created by recordParsed()
     * when a run is actually dispatched. A top band that dispatches nothing therefore has no rows
     * for today at all, and the admin criteria page - which reads today's rows - cannot tell that
     * the band exists. Calling this makes the band visible with a count of 0 instead.
     */
    public function ensureCriteriaExist(int $combatLogVersion, Season $season, KeyLevelBand $band): void;

    /**
     * Resets all criterion counts for today (UTC date) to zero.
     *
     * The budget this hands back is released pro rata like any other, so a reset partway through
     * the day does not let a band spend its whole threshold at once - it spends it across the
     * polling opportunities it has left. A band whose last opportunity of the day has already
     * passed gets nothing back until tomorrow, reset or not.
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
