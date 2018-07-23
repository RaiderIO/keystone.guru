<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $enemy_pack_id
 * @property int $npc_id
 * @property int $floor_id
 * @property double $lat
 * @property double $lng
 * @property \App\Models\EnemyPack $enemyPack
 * @property \App\Models\Npc $npc
 * @property \App\Models\Floor $floor
 * @property \Illuminate\Support\Collection $vertices
 */
class Enemy extends Model
{
    public $hidden = ['created_at', 'updated_at'];

    function pack()
    {
        return $this->belongsTo('App\Models\EnemyPack');
    }

    function floor()
    {
        return $this->belongsTo('App\Models\Floor');
    }

    function npc()
    {
        return $this->belongsTo('App\Models\Npc');
    }
}
