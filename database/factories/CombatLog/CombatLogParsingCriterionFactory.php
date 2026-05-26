<?php

namespace Database\Factories\CombatLog;

use App\Logic\CombatLog\CombatLogVersion;
use App\Models\CharacterClassSpecialization;
use App\Models\CombatLog\CombatLogParsingCriterion;
use App\Models\Dungeon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CombatLogParsingCriterionFactory extends Factory
{
    protected $model = CombatLogParsingCriterion::class;

    public function definition(): array
    {
        return [
            'combat_log_version' => CombatLogVersion::RETAIL_12_0_5,
            'model_class'        => Dungeon::class,
            'model_id'           => 1,
            'date'               => Carbon::now()->toDateString(),
            'count'              => 0,
            'threshold'          => 100,
        ];
    }

    public function forDungeon(int $dungeonId, int $combatLogVersion = CombatLogVersion::RETAIL_12_0_5): self
    {
        return $this->state([
            'model_class'        => Dungeon::class,
            'model_id'           => $dungeonId,
            'combat_log_version' => $combatLogVersion,
        ]);
    }

    public function forClassSpec(int $characterClassSpecializationId, int $combatLogVersion = CombatLogVersion::RETAIL_12_0_5): self
    {
        return $this->state([
            'model_class'        => CharacterClassSpecialization::class,
            'model_id'           => $characterClassSpecializationId,
            'combat_log_version' => $combatLogVersion,
        ]);
    }

    public function withCount(int $count): self
    {
        return $this->state(['count' => $count]);
    }

    public function atThreshold(): self
    {
        return $this->state(fn(array $attributes) => ['count' => $attributes['threshold']]);
    }
}
