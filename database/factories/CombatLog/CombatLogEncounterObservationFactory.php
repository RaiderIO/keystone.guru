<?php

namespace Database\Factories\CombatLog;

use App\Models\CombatLog\CombatLogEncounterObservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CombatLogEncounterObservation>
 */
class CombatLogEncounterObservationFactory extends Factory
{
    protected $model = CombatLogEncounterObservation::class;

    public function definition(): array
    {
        $runId = $this->faker->unique()->numberBetween(900_000_000, 999_999_999);

        return [
            'dungeon_id'      => $this->faker->numberBetween(900_000, 999_999),
            'encounter_id'    => $this->faker->numberBetween(900_000, 999_999),
            'run_key'         => sprintf('run:%d', $runId),
            'combat_log_path' => sprintf('/tmp/run_%d_segment_2.txt', $runId),
            'key_level'       => $this->faker->numberBetween(2, 20),
            'affix_group_id'  => null,
            'success'         => true,
            'duration_ms'     => $this->faker->numberBetween(60_000, 300_000),
            'data'            => [
                'npcs'              => [],
                'no_damage_windows' => [],
                'shared_health'     => [],
            ],
        ];
    }
}
