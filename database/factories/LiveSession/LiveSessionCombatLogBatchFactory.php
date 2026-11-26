<?php

namespace Database\Factories\LiveSession;

use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionCombatLogBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveSessionCombatLogBatch>
 */
class LiveSessionCombatLogBatchFactory extends Factory
{
    protected $model = LiveSessionCombatLogBatch::class;

    public function definition(): array
    {
        return [
            'live_session_id' => LiveSession::factory(),
            'sequence'        => null,
            'lines'           => gzencode('6/11/2026 22:22:55.5142  CHALLENGE_MODE_START,"Pit of Saron",658,556,10,[162,9,10]', 6),
            'consumed_at'     => null,
        ];
    }

    /**
     * @param array<int, string> $lines
     */
    public function withLines(array $lines): static
    {
        return $this->state([
            'lines' => gzencode(implode("\n", $lines), 6),
        ]);
    }
}
