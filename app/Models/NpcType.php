<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $type
 *
 * @mixin \Eloquent
 */
class NpcType extends Model
{
    protected $fillable = ['id', 'type'];
    public $timestamps = false;

    public function getTypeKeyAttribute()
    {
        return strtolower($this->type);
    }

    /**
     * Gets all derived NPCs from this type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function npcs()
    {
        return $this->hasMany('App\Models\Npc');
    }
}
