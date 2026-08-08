<?php

namespace Database\Factories\LiveSession;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\LiveSession\LiveSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<LiveSession>
 */
class LiveSessionFactory extends Factory
{
    protected $model = LiveSession::class;

    public function definition(): array
    {
        return [
            'dungeon_route_id' => DungeonRoute::factory(),
            'user_id'          => 1,
            'public_key'       => LiveSession::generateRandomPublicKey(checkUsages: false),
            'expires_at'       => null,
        ];
    }

    public function expired(): self
    {
        return $this->state([
            'expires_at' => Carbon::now()->subHour(),
        ]);
    }
}
