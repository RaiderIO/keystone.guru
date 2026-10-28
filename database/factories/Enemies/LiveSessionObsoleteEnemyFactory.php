<?php

namespace Database\Factories\Enemies;

use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionObsoleteEnemy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveSessionObsoleteEnemy>
 */
class LiveSessionObsoleteEnemyFactory extends Factory
{
    protected $model = LiveSessionObsoleteEnemy::class;

    public function definition(): array
    {
        return [
            'live_session_id' => LiveSession::factory(),
            'npc_id'          => $this->faker->numberBetween(10000, 100000),
            'mdt_id'          => $this->faker->numberBetween(1, 100),
        ];
    }
}
