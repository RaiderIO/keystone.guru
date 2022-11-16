<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $kill_zone_id
 * @property int $npc_id
 * @property int $mdt_id
 *
 * @property KillZone $killzone
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
    public function enemy(): Enemy
    {
        return Enemy::where('npc_id', $this->npc_id)->where('mdt_id', $this->mdt_id)->first();
    }
}
