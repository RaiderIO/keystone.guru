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

    public const NPC_CLASS_MELEE         = 'melee';
    public const NPC_CLASS_RANGED        = 'ranged';
    public const NPC_CLASS_CASTER        = 'caster';
    public const NPC_CLASS_HEALER        = 'healer';
    public const NPC_CLASS_CASTER_MELEE  = 'caster/melee';
    public const NPC_CLASS_HEALER_CASTER = 'healer/caster';
    public const NPC_CLASS_HEALER_MELEE  = 'healer/melee';
    public const NPC_CLASS_RANGED_CASTER = 'ranged/caster';
    public const NPC_CLASS_RANGED_HEALER = 'ranged/healer';
    public const NPC_CLASS_RANGED_MELEE  = 'ranged/melee';

    public const ALL = [
        self::NPC_CLASS_MELEE         => 1,
        self::NPC_CLASS_RANGED        => 2,
        self::NPC_CLASS_CASTER        => 3,
        self::NPC_CLASS_HEALER        => 4,
        self::NPC_CLASS_CASTER_MELEE  => 5,
        self::NPC_CLASS_HEALER_CASTER => 6,
        self::NPC_CLASS_HEALER_MELEE  => 7,
        self::NPC_CLASS_RANGED_CASTER => 8,
        self::NPC_CLASS_RANGED_HEALER => 9,
        self::NPC_CLASS_RANGED_MELEE  => 10,
    ];

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
