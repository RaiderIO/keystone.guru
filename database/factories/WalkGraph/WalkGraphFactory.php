<?php

namespace Database\Factories\WalkGraph;

use App\Logic\WalkGraph\WalkGrid;
use App\Models\WalkGraph\WalkGraph;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<WalkGraph>
 */
class WalkGraphFactory extends Factory
{
    protected $model = WalkGraph::class;

    public function definition(): array
    {
        return [
            'ui_map_id'                    => $this->faker->unique()->numberBetween(900_000, 999_999),
            'cell_size'                    => 3.0,
            'run_count'                    => 1,
            'node_count'                   => 2,
            'edge_count'                   => 1,
            'largest_component_node_count' => 2,
            'edges'                        => WalkGrid::encodeEdgeCounts([WalkGrid::edgeKey(0, 0, 1, 0) => 1]),
            'built_at'                     => Carbon::now(),
        ];
    }
}
