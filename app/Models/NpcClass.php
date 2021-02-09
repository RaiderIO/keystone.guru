<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 *
 * @mixin Eloquent
 */
class NpcClass extends CacheModel
{
    protected $fillable = ['id', 'name'];
    public $timestamps = false;

    public function getNameKeyAttribute()
    {
        return strtolower($this->name);
    }

    /**
     * Gets all derived NPCs from this class.
     *
     * @return HasMany
     */
    function npcs()
    {
        return $this->hasMany('App\Models\Npc');
    }
}
