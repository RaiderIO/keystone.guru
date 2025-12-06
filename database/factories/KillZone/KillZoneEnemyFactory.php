<?php

namespace Database\Factories\KillZone;

use Illuminate\Database\Eloquent\Factories\Factory;

class KillZoneEnemyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kill_zone_id' => 1,
            'npc_id'       => $this->faker->numberBetween(10000, 100000),
            'mdt_id'       => $this->faker->numberBetween(1, 100),
        ];
    }
}
