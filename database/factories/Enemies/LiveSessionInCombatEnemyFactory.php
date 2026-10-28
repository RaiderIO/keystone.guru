<?php

namespace Database\Factories\Enemies;

use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionInCombatEnemy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveSessionInCombatEnemy>
 */
class LiveSessionInCombatEnemyFactory extends Factory
{
    protected $model = LiveSessionInCombatEnemy::class;

    public function definition(): array
    {
        return [
            'live_session_id' => LiveSession::factory(),
            'npc_id'          => $this->faker->numberBetween(10000, 100000),
            'mdt_id'          => $this->faker->numberBetween(1, 100),
        ];
    }
}
