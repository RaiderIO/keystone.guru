<?php

namespace Database\Factories;

use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionCombatLogBuffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveSessionCombatLogBuffer>
 */
class LiveSessionCombatLogBufferFactory extends Factory
{
    protected $model = LiveSessionCombatLogBuffer::class;

    public function definition(): array
    {
        return [
            'live_session_id' => LiveSession::factory(),
            'buffer'          => null,
            'last_sequence'   => null,
        ];
    }
}
