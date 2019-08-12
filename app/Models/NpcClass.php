<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 *
 * @mixin \Eloquent
 */
class NpcClass extends Model
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function npcs()
    {
        return $this->hasMany('App\Models\Npc');
    }
}
