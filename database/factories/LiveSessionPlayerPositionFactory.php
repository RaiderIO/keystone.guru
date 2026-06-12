<?php

namespace Database\Factories;

use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveSessionPlayerPosition>
 */
class LiveSessionPlayerPositionFactory extends Factory
{
    protected $model = LiveSessionPlayerPosition::class;

    public function definition(): array
    {
        $serverId  = $this->faker->numberBetween(1, 9999);
        $charBytes = strtoupper(substr(md5($this->faker->uuid()), 0, 8));

        return [
            'live_session_id' => LiveSession::factory(),
            'player_guid'     => sprintf('Player-%d-%s', $serverId, $charBytes),
            'character_name'  => $this->faker->firstName(),
            'lat'             => $this->faker->randomFloat(4, -400, 400),
            'lng'             => $this->faker->randomFloat(4, -400, 400),
            'floor_id'        => 1,
        ];
    }
}
