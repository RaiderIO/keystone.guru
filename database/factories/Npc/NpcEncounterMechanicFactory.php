<?php

namespace Database\Factories\Npc;

use App\Models\Npc\NpcEncounterMechanic;
use App\Models\Npc\NpcEncounterMechanicType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NpcEncounterMechanic>
 */
class NpcEncounterMechanicFactory extends Factory
{
    protected $model = NpcEncounterMechanic::class;

    public function definition(): array
    {
        return [
            'dungeon_id'       => $this->faker->numberBetween(900_000, 999_999),
            'encounter_id'     => $this->faker->numberBetween(900_000, 999_999),
            'type'             => NpcEncounterMechanicType::Adds,
            'npc_id'           => $this->faker->numberBetween(900_000_000, 999_999_999),
            'spell_id'         => null,
            'first_ms'         => 30_000,
            'first_health_pct' => null,
            'cooldown_ms'      => 60_000,
            'duration_ms'      => null,
            'count'            => 2,
            'run_count'        => 5,
            'total_run_count'  => 5,
            'data'             => null,
        ];
    }
}
