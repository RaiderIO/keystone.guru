<?php

namespace App\Service\CombatLog;

use App\Models\CombatLog\CombatLogParsingCriterion;
use App\Service\CombatLog\Dtos\CombatLogParsingCriterionCheck;
use Illuminate\Support\Carbon;

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
            CombatLogParsingCriterion::query()
                ->where('combat_log_version', $combatLogVersion)
                ->where('model_class', $criterion->getModelClass())
                ->where('model_id', $criterion->getModelId())
                ->where('date', $today)
                ->increment('count');
        }
    }

    public function resetAllForToday(): void
    {
        CombatLogParsingCriterion::query()
            ->where('date', Carbon::now()->toDateString())
            ->update(['count' => 0]);
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
            ['count' => 0, 'threshold' => 100],
        );
    }
}
