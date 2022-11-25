<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 *
 * @property Collection|Npc[] $npcs
 *
 * @mixin Eloquent
 */
class NpcClass extends CacheModel
{
    protected $fillable = ['id', 'name'];
    public $timestamps = false;

    /**
     * @return string
     */
    public function getNameKeyAttribute(): string
    {
        return strtolower($this->name);
    }

    /**
     * Gets all derived NPCs from this class.
     *
     * @return HasMany
     */
    public function npcs(): HasMany
    {
        return $this->hasMany(Npc::class);
    }
}
