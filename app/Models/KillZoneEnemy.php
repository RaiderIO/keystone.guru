<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\JoinClause;

/**
 * @property int $id
 * @property int $kill_zone_id
 * @property int $npc_id
 * @property int $mdt_id
 *
 * @property KillZone $killzone
 * @property Enemy $enemy
 * @property Npc $npc
 *
 * @mixin Eloquent
 */
class KillZoneEnemy extends Model
{
    public $hidden = ['id', 'kill_zone_id'];

    public $timestamps = false;

    protected $fillable = [
        'kill_zone_id',
        'npc_id',
        'mdt_id',
    ];

    /**
     * @return BelongsTo
     */
    public function killzone(): BelongsTo
    {
        return $this->belongsTo(KillZone::class);
    }

    /**
     * @return BelongsTo
     */
    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    /**
     * @return Enemy
     */
    public function getEnemy(): Enemy
    {
        /** @var Enemy $result */
        $result = Enemy::select('enemies.*')
            ->join('kill_zone_enemies', function (JoinClause $clause) {
                $clause->on('kill_zone_enemies.npc_id', 'enemies.npc_id')
                    ->on('kill_zone_enemies.mdt_id', 'enemies.mdt_id');
            })
            ->join('kill_zones', 'kill_zones.id', 'kill_zone_enemies.kill_zone_id')
            ->join('dungeon_routes', 'dungeon_routes.id', 'kill_zones.dungeon_route_id')
            ->whereColumn('mapping_version_id', 'dungeon_routes.mapping_version_id')
            ->where('kill_zone_enemies.npc_id', $this->npc_id)
            ->where('kill_zone_enemies.mdt_id', $this->mdt_id)
            ->first();

        return $result;
    }
}
