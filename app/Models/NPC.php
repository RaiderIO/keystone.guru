<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $classification_id
 * @property int $game_id
 * @property string $name
 * @property int $base_health
 * @property \Illuminate\Support\Collection $enemies
 */
class NPC extends Model
{
    public $hidden = ['created_at', 'updated_at'];

    /**
     * Gets all derived enemies from this NPC.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function enemypack()
    {
        return $this->hasOne('App\Models\EnemyPack');
    }
}
