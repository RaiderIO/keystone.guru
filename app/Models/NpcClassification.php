<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $color
 *
 * @mixin \Eloquent
 */
class NpcClassification extends Model
{
    public $hidden = ['created_at', 'updated_at'];

    /**
     * Gets all derived NPCs from this classification.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function npcs()
    {
        return $this->hasMany('App\Models\Npc');
    }
}
