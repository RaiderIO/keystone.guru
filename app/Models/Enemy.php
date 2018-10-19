<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $enemy_pack_id
 * @property int $npc_id
 * @property int $floor_id
 * @property bool $is_infested
 * @property string $teeming
 * @property string $faction
 * @property string $enemy_forces_override
 * @property double $lat
 * @property double $lng
 * @property \App\Models\EnemyPack $enemyPack
 * @property \App\Models\Npc $npc
 * @property \App\Models\Floor $floor
 * @property \Illuminate\Support\Collection $vertices
 */
class Enemy extends Model
{
    public $with = ['npc'];
    public $hidden = ['npc_id'];
    public $timestamps = false;

    /**
     * Checks if this enemy is Infested or not.
     * @return bool
     */
    function getIsInfestedAttribute()
    {
        return true;
    }

    /**
     * Gets the infested votes for this enemy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function infestedvotes()
    {
        return $this->hasMany('App\Models\InfestedEnemyVote');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function pack()
    {
        return $this->belongsTo('App\Models\EnemyPack');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function floor()
    {
        return $this->belongsTo('App\Models\Floor');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function npc()
    {
        return $this->belongsTo('App\Models\Npc');
    }
}
