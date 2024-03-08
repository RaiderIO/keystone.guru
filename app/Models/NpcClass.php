<?php

namespace App\Models;

use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int              $id
 * @property string           $name
 * @property Collection|Npc[] $npcs
 *
 * @mixin Eloquent
 */
class NpcClass extends CacheModel
{
    use SeederModel;

    protected $fillable = ['id', 'name'];

    public $timestamps = false;

    public const NPC_CLASS_MELEE = 'melee';

    public const NPC_CLASS_RANGED = 'ranged';

    public const NPC_CLASS_CASTER = 'caster';

    public const NPC_CLASS_HEALER = 'healer';

    public const NPC_CLASS_CASTER_MELEE = 'caster_melee';

    public const NPC_CLASS_HEALER_CASTER = 'healer_caster';

    public const NPC_CLASS_HEALER_MELEE = 'healer_melee';

    public const NPC_CLASS_RANGED_CASTER = 'ranged_caster';

    public const NPC_CLASS_RANGED_HEALER = 'ranged_healer';

    public const NPC_CLASS_RANGED_MELEE = 'ranged_melee';

    public const ALL = [
        self::NPC_CLASS_MELEE,
        self::NPC_CLASS_RANGED,
        self::NPC_CLASS_CASTER,
        self::NPC_CLASS_HEALER,
        self::NPC_CLASS_CASTER_MELEE,
        self::NPC_CLASS_HEALER_CASTER,
        self::NPC_CLASS_HEALER_MELEE,
        self::NPC_CLASS_RANGED_CASTER,
        self::NPC_CLASS_RANGED_HEALER,
        self::NPC_CLASS_RANGED_MELEE,
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
