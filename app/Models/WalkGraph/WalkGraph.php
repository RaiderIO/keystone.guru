<?php

namespace App\Models\WalkGraph;

use Database\Factories\WalkGraph\WalkGraphFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Where players walked on one ui map, aggregated over the most recent runs. Keyed by ui map rather than floor:
 * floors sharing a ui map share its in-game coordinate space, and the geometry only changes with game patches.
 *
 * @property int    $id
 * @property int    $ui_map_id
 * @property float  $cell_size
 * @property int    $run_count
 * @property int    $node_count
 * @property int    $edge_count
 * @property int    $largest_component_node_count
 * @property string $edges                        Compressed edge key => run count, see {@see \App\Logic\WalkGraph\WalkGrid::encodeEdgeCounts()}
 * @property Carbon $built_at
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @mixin Eloquent
 */
class WalkGraph extends Model
{
    /** @use HasFactory<WalkGraphFactory> */
    use HasFactory;

    protected $fillable = [
        'ui_map_id',
        'cell_size',
        'run_count',
        'node_count',
        'edge_count',
        'largest_component_node_count',
        'edges',
        'built_at',
    ];

    protected $hidden = [
        'edges',
    ];

    public function casts(): array
    {
        return [
            'cell_size' => 'float',
            'built_at'  => 'datetime',
        ];
    }
}
