<?php

namespace App\Service\CombatLog;

use App\Models\Interfaces\CombatLogCriterionModelInterface;
use App\Models\Season;
use App\Service\CombatLog\Dtos\CombatLogParsingCriterionCheck;
use Illuminate\Support\Collection;

interface CombatLogParsingCriteriaServiceInterface
{
    /**
     * Returns true if ALL given criteria counts for today are below their configured thresholds
     * for the given combat log version.
     *
     * Note: call recordParsed() immediately when this returns true (at webhook accept time,
     * not after processing) so concurrent requests see updated counts.
     *
     * @param CombatLogParsingCriterionCheck[] $criteria
     */
    public function shouldParse(int $combatLogVersion, array $criteria): bool;

    /**
     * Increments the count for each given criterion for today.
     * Must be called immediately when a combat log is accepted for processing.
     *
     * @param CombatLogParsingCriterionCheck[] $criteria
     */
    public function recordParsed(int $combatLogVersion, array $criteria): void;

    /**
     * Resets all criterion counts for today (UTC date) to zero.
     */
    public function resetAllForToday(): void;

    /**
     * Returns all criteria rows for today where count < threshold for the given model class.
     *
     * @return Collection<int, mixed>
     */
    public function getBelowThresholdCriteria(int $combatLogVersion, string $modelClass): Collection;

    /**
     * Returns all model instances that are valid polling targets for the given criteria model class.
     * - Dungeon: all dungeons belonging to the given season
     * - CharacterClassSpecialization: all specializations
     *
     * @param  class-string<CombatLogCriterionModelInterface> $modelClass
     * @return Collection<int, mixed>
     */
    public function getAllModelsForCriteria(string $modelClass, Season $season): Collection;

    /**
     * Returns all models from getAllModelsForCriteria() that are still eligible for polling today:
     * models with no row yet (implicit count = 0) and models with count < threshold.
     * Models with count >= threshold are excluded.
     *
     * @param  class-string<CombatLogCriterionModelInterface> $modelClass
     * @return Collection<int, mixed>
     */
    public function getModelsEligibleForPolling(int $combatLogVersion, string $modelClass, Season $season): Collection;
}
