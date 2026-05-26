<?php

namespace App\Service\CombatLog;

use App\Models\CharacterClassSpecialization;
use App\Models\CombatLog\CombatLogParsingCriterion;
use App\Models\Dungeon;
use App\Models\Season;
use App\Service\CombatLog\Dtos\CombatLogParsingCriterionCheck;
use Illuminate\Database\Eloquent\Model;
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
            $row = $this->findOrCreate($combatLogVersion, $criterion->getModelClass(), $criterion->getModelId(), $today);

            if ($row->count >= $row->threshold) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param CombatLogParsingCriterionCheck[] $criteria
     */
    public function recordParsed(int $combatLogVersion, array $criteria): void
    {
        $today = Carbon::now()->toDateString();

        foreach ($criteria as $criterion) {
            $this->findOrCreate($combatLogVersion, $criterion->getModelClass(), $criterion->getModelId(), $today)
                ->increment('count');
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

    public function getModelsEligibleForPolling(int $combatLogVersion, string $modelClass, Season $season): Collection
    {
        $allModels = $this->getAllModelsForCriteria($modelClass, $season);

        /** @var array<int, true> $atThresholdModelIds */
        $atThresholdModelIds = CombatLogParsingCriterion::query()
            ->where('combat_log_version', $combatLogVersion)
            ->where('model_class', $modelClass)
            ->where('date', Carbon::now()->toDateString())
            ->whereColumn('count', '>=', 'threshold')
            ->pluck('model_id')
            ->flip()
            ->all();

        return $allModels->filter(fn(Model $model) => !isset($atThresholdModelIds[$model->id]));
    }

    private function findOrCreate(
        int    $combatLogVersion,
        string $modelClass,
        int    $modelId,
        string $date,
    ): CombatLogParsingCriterion {
        /** @var CombatLogParsingCriterion */
        return CombatLogParsingCriterion::query()->firstOrCreate(
            [
                'combat_log_version' => $combatLogVersion,
                'model_class'        => $modelClass,
                'model_id'           => $modelId,
                'date'               => $date,
            ],
            ['count' => 0, 'threshold' => $this->getDefaultThreshold($modelClass)],
        );
    }

    private function getDefaultThreshold(string $modelClass): int
    {
        return CombatLogParsingCriterion::query()
            ->where('model_class', $modelClass)
            ->orderBy('date', 'desc')
            ->value('threshold') ?? 100;
    }
}
