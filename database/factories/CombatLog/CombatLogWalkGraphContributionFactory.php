<?php

namespace Database\Factories\CombatLog;

use App\Logic\WalkGraph\WalkGrid;
use App\Models\CombatLog\CombatLogWalkGraphContribution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CombatLogWalkGraphContribution>
 */
class CombatLogWalkGraphContributionFactory extends Factory
{
    protected $model = CombatLogWalkGraphContribution::class;

    public function definition(): array
    {
        $runId    = $this->faker->unique()->numberBetween(900_000_000, 999_999_999);
        $edgeKeys = [WalkGrid::edgeKey(0, 0, 1, 0)];

        return [
            'ui_map_id'       => $this->faker->numberBetween(900_000, 999_999),
            'run_key'         => sprintf('run:%d', $runId),
            'combat_log_path' => sprintf('/tmp/run_%d_segment_1.txt', $runId),
            'edge_count'      => count($edgeKeys),
            'edges'           => WalkGrid::encodeEdgeKeys($edgeKeys),
        ];
    }

    /**
     * @param array<int, int> $edgeKeys
     */
    public function withEdgeKeys(array $edgeKeys): static
    {
        return $this->state(fn() => [
            'edge_count' => count($edgeKeys),
            'edges'      => WalkGrid::encodeEdgeKeys($edgeKeys),
        ]);
    }
}
