<?php

namespace App\Models\Npc;

use App\Models\CacheModel;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int    $id
 * @property string $name
 * @property string $key
 *
 * @property Collection<Npc> $npcs
 *
 * @mixin Eloquent
 */
class NpcClass extends CacheModel
{
    use SeederModel;

    protected $fillable = [
        'id',
        'key',
        'name',
    ];

    protected $visible = [
        'name',
        'key',
    ];

    public $timestamps = false;

    public const NPC_CLASS_MELEE         = 'melee';
    public const NPC_CLASS_RANGED        = 'ranged';
    public const NPC_CLASS_CASTER        = 'caster';
    public const NPC_CLASS_HEALER        = 'healer';
    public const NPC_CLASS_CASTER_MELEE  = 'caster_melee';
    public const NPC_CLASS_HEALER_CASTER = 'healer_caster';
    public const NPC_CLASS_HEALER_MELEE  = 'healer_melee';
    public const NPC_CLASS_RANGED_CASTER = 'ranged_caster';
    public const NPC_CLASS_RANGED_HEALER = 'ranged_healer';
    public const NPC_CLASS_RANGED_MELEE  = 'ranged_melee';

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

    public function getNameKeyAttribute(): string
    {
        return strtolower($this->name);
    }

    /**
     * Gets all derived NPCs from this class.
     */
    public function npcs(): HasMany
    {
        return $this->hasMany(Npc::class);
    }
}
