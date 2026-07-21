<?php

namespace App\Models\DungeonRoute;

use App\Models\Enemy;
use App\Models\RaidMarker;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int          $id
 * @property int          $dungeon_route_id
 * @property int          $raid_marker_id
 * @property int|null     $npc_id
 * @property int|null     $mdt_id
 * @property int|null     $enemy_id
 * @property DungeonRoute $dungeonRoute
 * @property RaidMarker   $raidMarker
 * @property Enemy|null   $enemy
 *
 * @mixin Eloquent
 */
class DungeonRouteEnemyRaidMarker extends Model
{
    protected $fillable = [
        'dungeon_route_id',
        'raid_marker_id',
        'npc_id',
        'mdt_id',
        'enemy_id',
    ];

    public $hidden = ['dungeon_route_id'];

    public $with = ['raidMarker'];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'npc_id'   => 'integer',
            'mdt_id'   => 'integer',
            'enemy_id' => 'integer',
        ];
    }

    /** @return BelongsTo<DungeonRoute, $this> */
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    /** @return BelongsTo<RaidMarker, $this> */
    public function raidMarker(): BelongsTo
    {
        return $this->belongsTo(RaidMarker::class);
    }

    /** @return BelongsTo<Enemy, $this> */
    public function enemy(): BelongsTo
    {
        return $this->belongsTo(Enemy::class);
    }
}
