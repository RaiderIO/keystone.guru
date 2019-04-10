<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $dungeon_id
 * @property int $classification_id
 * @property string $name
 * @property int $base_health
 * @property int $enemy_forces
 * @property string $aggressiveness
 * @property \Illuminate\Support\Collection $enemies
 */
class Npc extends Model
{
    public $incrementing = false;
    public $timestamps = false;

    /**
     * Gets all derived enemies from this Npc.
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    function enemies()
    {
        return $this->hasMany('App\Models\Enemy');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    function dungeon()
    {
        return $this->belongsTo('App\Models\Dungeon');
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
