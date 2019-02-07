<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * @property int $id
 * @property int $enemy_pack_id
 * @property int $npc_id
 * @property int $floor_id
 * @property int $mdt_id The ID in MDT (clone index) that this enemy is coupled to
 * @property int $mdt_npc_index The index of the NPC in MDT
 * @property int $enemy_id Only used for temp MDT enemies
 * @property bool $is_mdt Only used for temp MDT enemies
 * @property string $teeming
 * @property string $faction
 * @property string $enemy_forces_override
 * @property double $lat
 * @property double $lng
 * @property \App\Models\EnemyPack $enemyPack
 * @property \App\Models\Npc $npc
 * @property \App\Models\Floor $floor
 */
class Enemy extends Model
{
    public $with = ['npc'];
    public $hidden = ['npc_id'];
    public $timestamps = false;

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
