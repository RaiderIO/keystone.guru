<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property int $raid_marker_id
 * @property int $enemy_id
 *
 * @property DungeonRoute $dungeonroute
 * @property RaidMarker $raidmarker
 * @property Enemy $enemy
 *
 * @mixin Eloquent
 */
class DungeonRouteEnemyRaidMarker extends Model
{
    protected $fillable = ['dungeon_route_id', 'raid_marker_id', 'enemy_id'];
    public $hidden = ['dungeon_route_id'];
    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function dungeonroute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    /**
     * @return BelongsTo
     */
    public function raidmarker(): BelongsTo
    {
        return $this->belongsTo(RaidMarker::class);
    }

    /**
     * @return BelongsTo
     */
    public function enemy(): BelongsTo
    {
        return $this->belongsTo(Enemy::class);
    }
}
