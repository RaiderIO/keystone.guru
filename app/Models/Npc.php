<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\belongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\hasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $dungeon_id
 * @property int $classification_id
 * @property int $npc_type_id
 * @property int $npc_class_id
 * @property string $name
 * @property int $base_health
 * @property int $enemy_forces
 * @property int $enemy_forces_teeming
 * @property string $aggressiveness
 * @property bool $dangerous
 * @property bool $truesight
 * @property bool $bursting
 * @property bool $bolstering
 * @property bool $sanguine
 *
 * @property Dungeon $dungeon
 * @property NpcClassification $classification
 * @property NpcType $type
 * @property NpcClass $class
 *
 * @property Enemy[]|Collection $enemies
 * @property NpcBolsteringWhitelist[]|Collection $npcbolsteringwhitelists
 *
 * @mixin Eloquent
 */
class Npc extends CacheModel
{
    public $incrementing = false;
    public $timestamps = false;

    protected $with = ['type', 'class', 'npcbolsteringwhitelists', 'spells'];
    protected $fillable = ['id', 'npc_type_id', 'npc_class_id', 'dungeon_id', 'name', 'base_health', 'enemy_forces', 'enemy_forces_teeming', 'aggressiveness'];

    // 'aggressive', 'unfriendly', 'neutral', 'friendly', 'awakened'
    public const AGGRESSIVENESS_AGGRESSIVE = 'aggressive';
    public const AGGRESSIVENESS_UNFRIENDLY = 'unfriendly';
    public const AGGRESSIVENESS_NEUTRAL = 'neutral';
    public const AGGRESSIVENESS_FRIENDLY = 'friendly';
    public const AGGRESSIVENESS_AWAKENED = 'awakened';

    public const ALL_AGGRESSIVENESS = [
        self::AGGRESSIVENESS_AGGRESSIVE,
        self::AGGRESSIVENESS_UNFRIENDLY,
        self::AGGRESSIVENESS_NEUTRAL,
        self::AGGRESSIVENESS_FRIENDLY,
        self::AGGRESSIVENESS_AWAKENED,
    ];

    /**
     * @return bool
     */
    public function isAwakened(): bool
    {
        return in_array($this->id, [161244, 161243, 161124, 161241]);
    }

    /**
     * @return bool
     */
    public function isEncrypted(): bool
    {
        return in_array($this->id, [185680, 185683, 185685]);
    }

    /**
     * @return bool
     */
    public function isPrideful(): bool
    {
        return $this->id === config('keystoneguru.prideful.npc_id');
    }

    /**
     * Gets all derived enemies from this Npc.
     *
     * @return hasMany
     */
    function enemies(): HasMany
    {
        return $this->hasMany('App\Models\Enemy');
    }

    /**
     * @return hasMany
     */
    function npcbolsteringwhitelists(): HasMany
    {
        return $this->hasMany('App\Models\NpcBolsteringWhitelist');
    }

    /**
     * @return belongsTo
     */
    function dungeon(): BelongsTo
    {
        return $this->belongsTo('App\Models\Dungeon');
    }

    /**
     * @return belongsTo
     */
    function classification(): BelongsTo
    {
        return $this->belongsTo('App\Models\NpcClassification');
    }

    /**
     * @return belongsTo
     */
    function type(): BelongsTo
    {
        // Not sure why the foreign key declaration is required here, but it is
        return $this->belongsTo('App\Models\NpcType', 'npc_type_id');
    }

    /**
     * @return belongsTo
     */
    function class(): BelongsTo
    {
        // Not sure why the foreign key declaration is required here, but it is
        return $this->belongsTo('App\Models\NpcClass', 'npc_class_id');
    }

    /**
     * @return BelongsToMany
     */
    public function spells(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Spell', 'npc_spells');
    }

    /**
     * @return HasMany
     */
    function npcspells(): HasMany
    {
        return $this->hasMany('App\Models\NpcSpell');
    }


    public static function boot()
    {
        parent::boot();

        // Delete Path properly if it gets deleted
        static::deleting(function ($item) {
            /** @var $item Npc */

            $item->npcbolsteringwhitelists()->delete();
            $item->npcspells()->delete();
        });
    }
}
