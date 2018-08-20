<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $dungeon_id
 * @property int $classification_id
 * @property string $name
 * @property int $base_health
 * @property string $aggressiveness
 * @property \Illuminate\Support\Collection $enemies
 */
class Npc extends Model
{
    public $hidden = ['created_at', 'updated_at'];

    public $timestamps = false;

    /**
     * Gets all derived enemies from this Npc.
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    function enemypack()
    {
        return $this->hasMany('App\Models\EnemyPack');
    }

    /**
     * Gets all derived enemies from this Npc.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    function classification()
    {
        return $this->belongsTo('App\Models\NpcClassification');
    }
}
