<?php

namespace Database\Factories\LiveSession;

use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionEnemyState;
use App\Models\LiveSession\LiveSessionEnemyStateType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveSessionEnemyState>
 */
class LiveSessionEnemyStateFactory extends Factory
{
    protected $model = LiveSessionEnemyState::class;

    public function definition(): array
    {
        return [
            'live_session_id' => LiveSession::factory(),
            'state'           => LiveSessionEnemyStateType::Killed,
            'npc_id'          => $this->faker->numberBetween(10000, 100000),
            'mdt_id'          => $this->faker->numberBetween(1, 100),
        ];
    }

    public function inState(LiveSessionEnemyStateType $state): self
    {
        return $this->state([
            'state' => $state,
        ]);
    }
}
