<?php

namespace App\Models\CombatLog;

use Database\Factories\CombatLog\CombatLogWalkGraphContributionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The walk grid edges players walked on one ui map in one combat log file. A run is several files, which share
 * a run key, so a graph can count runs rather than files.
 *
 * @property int    $id
 * @property int    $ui_map_id
 * @property string $run_key
 * @property string $combat_log_path
 * @property int    $edge_count
 * @property string $edges           Compressed edge keys, see {@see \App\Logic\WalkGraph\WalkGrid::encodeEdgeKeys()}
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @mixin Eloquent
 */
class CombatLogWalkGraphContribution extends Model
{
    /** @use HasFactory<CombatLogWalkGraphContributionFactory> */
    use HasFactory;

    protected $connection = 'combatlog';

    protected $fillable = [
        'ui_map_id',
        'run_key',
        'combat_log_path',
        'edge_count',
        'edges',
    ];

    protected $hidden = [
        'edges',
    ];
}
