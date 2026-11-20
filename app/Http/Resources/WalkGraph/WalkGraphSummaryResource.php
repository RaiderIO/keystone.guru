<?php

namespace App\Http\Resources\WalkGraph;

use App\Models\Floor\Floor;
use App\Models\WalkGraph\WalkGraph;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * A walk graph's metadata and the dungeons whose floors use its ui map. Expects `floors.dungeon.expansion` loaded.
 *
 * @property WalkGraph $resource
 */
class WalkGraphSummaryResource extends JsonResource
{
    /**
     * @return array{
     *     ui_map_id: int,
     *     run_count: int,
     *     node_count: int,
     *     edge_count: int,
     *     largest_component_node_count: int,
     *     built_at: string,
     *     dungeons: array<int, array{key: string, name: string, expansion: string, floors: array<int, array{id: int, index: int, name: string}>}>
     * }
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'ui_map_id'                    => $this->resource->ui_map_id,
            'run_count'                    => $this->resource->run_count,
            'node_count'                   => $this->resource->node_count,
            'edge_count'                   => $this->resource->edge_count,
            'largest_component_node_count' => $this->resource->largest_component_node_count,
            'built_at'                     => $this->resource->built_at->toIso8601String(),
            'dungeons'                     => $this->resource->floors
                ->sortBy(['dungeon_id', 'index'])
                ->groupBy('dungeon_id')
                ->map(static function ($floors): array {
                    /** @var Floor $firstFloor */
                    $firstFloor = $floors->first();
                    $dungeon    = $firstFloor->dungeon;

                    return [
                        'key'       => $dungeon->key,
                        'name'      => __($dungeon->name, [], 'en_US'),
                        'expansion' => $dungeon->expansion->shortname,
                        'floors'    => $floors->map(static fn(Floor $floor): array => [
                            'id'    => $floor->id,
                            'index' => $floor->index,
                            'name'  => __($floor->name, [], 'en_US'),
                        ])->values()->all(),
                    ];
                })
                ->values()
                ->all(),
        ];
    }
}
