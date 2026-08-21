<?php

namespace App\Service\CombatLog;

use App\Models\CharacterClassSpecialization;
use App\Models\CombatLog\CombatLogParsingCriterion;
use App\Models\Dungeon;
use App\Models\Interfaces\CombatLogCriterionModelInterface;
use App\Models\Season;
use App\Service\CombatLog\Dtos\CombatLogParsingCriterionCheck;
use App\Service\CombatLog\Dtos\KeyLevelBand;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CombatLogParsingCriteriaService implements CombatLogParsingCriteriaServiceInterface
{
    /**
     * @param CombatLogParsingCriterionCheck[] $criteria
     */
    public function shouldParse(int $combatLogVersion, array $criteria): bool
    {
        $today = Carbon::now()->toDateString();

        foreach ($criteria as $criterion) {
            // The top band is always parsed - it has no budget to run out of
            if ($criterion->getBand()->isTopBand()) {
                continue;
            }

            $row = $this->findOrCreate($combatLogVersion, $criterion, $today);

            if ($row->count >= $row->threshold) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param CombatLogParsingCriterionCheck[] $criteria
     */
    public function recordParsed(int $combatLogVersion, array $criteria, ?string $date = null): void
    {
        $date ??= Carbon::now()->toDateString();

        foreach ($criteria as $criterion) {
            $this->findOrCreate($combatLogVersion, $criterion, $date)
                ->increment('count');
        }
    }

    /**
     * @param CombatLogParsingCriterionCheck[] $criteria
     */
    public function releaseParsed(int $combatLogVersion, array $criteria, string $date): void
    {
        foreach ($criteria as $criterion) {
            $band = $criterion->getBand();

            // Deliberately no findOrCreate: a row that doesn't exist has nothing to give back, and
            // creating one here would resurrect a row that resetAllForToday() or the date rolling
            // over has already dealt with. The count > 0 guard makes this atomic against a
            // concurrent reset, so a release can never underflow the (unsigned) column.
            CombatLogParsingCriterion::query()
                ->where('combat_log_version', $combatLogVersion)
                ->where('model_class', $criterion->getModelClass())
                ->where('model_id', $criterion->getModelId())
                ->where('mythic_level_min', $band->min)
                ->where('date', $date)
                ->where('count', '>', 0)
                ->decrement('count');
        }
    }

    public function resetAllForToday(): void
    {
        CombatLogParsingCriterion::query()
            ->where('date', Carbon::now()->toDateString())
            ->update(['count' => 0]);
    }

    public function getBelowThresholdCriteria(int $combatLogVersion, string $modelClass): Collection
    {
        return CombatLogParsingCriterion::query()
            ->where('combat_log_version', $combatLogVersion)
            ->where('model_class', $modelClass)
            ->where('date', Carbon::now()->toDateString())
            ->whereColumn('count', '<', 'threshold')
            ->get();
    }

    public function getAllModelsForCriteria(string $modelClass, Season $season): Collection
    {
        return match ($modelClass) {
            Dungeon::class                      => $season->dungeons()->get(),
            CharacterClassSpecialization::class => CharacterClassSpecialization::query()->get(),
            default                             => collect(),
        };
    }

    public function getModelsEligibleForPolling(int $combatLogVersion, string $modelClass, Season $season, KeyLevelBand $band): Collection
    {
        $allModels = $this->getAllModelsForCriteria($modelClass, $season);

        if ($band->isTopBand()) {
            return $allModels;
        }

        /** @var array<int, true> $atThresholdModelIds */
        $atThresholdModelIds = CombatLogParsingCriterion::query()
            ->where('combat_log_version', $combatLogVersion)
            ->where('model_class', $modelClass)
            ->where('mythic_level_min', $band->min)
            ->where('date', Carbon::now()->toDateString())
            ->whereColumn('count', '>=', 'threshold')
            ->pluck('model_id')
            ->flip()
            ->all();

        return $allModels->filter(fn(CombatLogCriterionModelInterface $model) => !isset($atThresholdModelIds[$model->getKey()]));
    }

    private function findOrCreate(
        int                            $combatLogVersion,
        CombatLogParsingCriterionCheck $criterion,
        string                         $date,
    ): CombatLogParsingCriterion {
        $band = $criterion->getBand();

        /** @var CombatLogParsingCriterion */
        return CombatLogParsingCriterion::query()->firstOrCreate(
            [
                'combat_log_version' => $combatLogVersion,
                'model_class'        => $criterion->getModelClass(),
                'model_id'           => $criterion->getModelId(),
                'mythic_level_min'   => $band->min,
                'date'               => $date,
            ],
            [
                'mythic_level_max' => $band->max,
                'count'            => 0,
                'threshold'        => $this->getDefaultThreshold($criterion->getModelClass(), $criterion->getModelId(), $band),
            ],
        );
    }

    /**
     * Thresholds are configured per model per band - that is the granularity of the inputs on the
     * admin page - so a new row inherits from the same model in the same band, and nothing else.
     * Inheriting across bands would hand every band the budget that was configured for a single
     * one; inheriting across models would silently apply one dungeon's raised threshold to every
     * other dungeon whose row happens to be created later.
     */
    private function getDefaultThreshold(string $modelClass, int $modelId, KeyLevelBand $band): int
    {
        if ($band->isTopBand()) {
            return 0;
        }

        $lastConfiguredThreshold = CombatLogParsingCriterion::query()
            ->where('model_class', $modelClass)
            ->where('model_id', $modelId)
            ->where('mythic_level_min', $band->min)
            // Top band rows share this column with whichever spread band starts on the same level
            // once the max key level rises, and they carry a meaningless threshold of 0. Inheriting
            // that would leave the new spread band permanently at its budget, day after day.
            ->whereNotNull('mythic_level_max')
            ->orderBy('date', 'desc')
            ->value('threshold');

        return $lastConfiguredThreshold
            ?? (int)config('keystoneguru.raider_io.combat_log_polling.bands.default_threshold');
    }
}
