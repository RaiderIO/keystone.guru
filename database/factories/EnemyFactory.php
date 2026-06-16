<?php

namespace Database\Factories;

use App\Models\Enemy;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnemyFactory extends Factory
{
    protected $model = Enemy::class;

    public function definition(): array
    {
        return [
            'mdt_id'                        => $this->faker->randomNumber(),
            'mdt_npc_id'                    => null,
            'mdt_scale'                     => 1,
            'mdt_x'                         => (string)$this->faker->randomFloat(0, 100),
            'mdt_y'                         => (string)$this->faker->randomFloat(0, 100),
            'seasonal_type'                 => $this->faker->word(),
            'seasonal_index'                => $this->faker->randomNumber(),
            'teeming'                       => $this->faker->word(),
            'faction'                       => $this->faker->word(),
            'required'                      => $this->faker->boolean(),
            'skippable'                     => $this->faker->boolean(),
            'hyper_respawn'                 => $this->faker->boolean(),
            'kill_priority'                 => $this->faker->randomNumber(),
            'enemy_forces_override'         => $this->faker->randomNumber(),
            'enemy_forces_override_teeming' => $this->faker->randomNumber(),
            'dungeon_difficulty'            => $this->faker->randomNumber(),
            'lat'                           => $this->faker->latitude(),
            'lng'                           => $this->faker->longitude(),

            'mapping_version_id' => $this->faker->randomNumber(),
            'enemy_pack_id'      => null,
            'enemy_patrol_id'    => null,
            'npc_id'             => $this->faker->numberBetween(1000000, 9999999),
            'floor_id'           => $this->faker->randomNumber(),
            'exclusive_enemy_id' => null,
        ];
    }
}
