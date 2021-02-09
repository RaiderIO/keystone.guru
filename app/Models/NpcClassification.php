<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $color
 *
 * @mixin Eloquent
 */
class NpcClassification extends CacheModel
{
    public $hidden = ['created_at', 'updated_at'];

    /**
     * Gets all derived NPCs from this classification.
     *
     * @return HasMany
     */
    function npcs()
    {
        return $this->hasMany('App\Models\Npc');
    }
}
