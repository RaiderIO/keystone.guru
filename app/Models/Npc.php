<?php

namespace App\Models;

use App\Models\Mapping\MappingModelInterface;
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
 * @property int $display_id
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
class Npc extends CacheModel implements MappingModelInterface
{
    public $incrementing = false;
    public $timestamps = false;

    protected $with = ['type', 'class', 'npcbolsteringwhitelists', 'spells'];
    protected $fillable = [
        'id',
        'dungeon_id',
        'npc_type_id',
        'npc_class_id',
        'display_id',
        'name',
        'base_health',
        'enemy_forces',
        'enemy_forces_teeming',
        'aggressiveness',
    ];

    // 'aggressive', 'unfriendly', 'neutral', 'friendly', 'awakened'
    public const AGGRESSIVENESS_AGGRESSIVE = 'aggressive';
    public const AGGRESSIVENESS_UNFRIENDLY = 'unfriendly';
    public const AGGRESSIVENESS_NEUTRAL    = 'neutral';
    public const AGGRESSIVENESS_FRIENDLY   = 'friendly';
    public const AGGRESSIVENESS_AWAKENED   = 'awakened';

    public const ALL_AGGRESSIVENESS = [
        self::AGGRESSIVENESS_AGGRESSIVE,
        self::AGGRESSIVENESS_UNFRIENDLY,
        self::AGGRESSIVENESS_NEUTRAL,
        self::AGGRESSIVENESS_FRIENDLY,
        self::AGGRESSIVENESS_AWAKENED,
    ];

    /**
     * Gets all derived enemies from this Npc.
     *
     * @return hasMany
     */
    public function enemies(): HasMany
    {
        return $this->hasMany(Enemy::class);
    }

    /**
     * @return hasMany
     */
    public function npcbolsteringwhitelists(): HasMany
    {
        return $this->hasMany(NpcBolsteringWhitelist::class);
    }

    /**
     * @return belongsTo
     */
    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }

    /**
     * @return belongsTo
     */
    public function classification(): BelongsTo
    {
        return $this->belongsTo(NpcClassification::class);
    }

    /**
     * @return belongsTo
     */
    public function type(): BelongsTo
    {
        // Not sure why the foreign key declaration is required here, but it is
        return $this->belongsTo(NpcType::class, 'npc_type_id');
    }

    /**
     * @return belongsTo
     */
    public function class(): BelongsTo
    {
        // Not sure why the foreign key declaration is required here, but it is
        return $this->belongsTo(NpcClass::class, 'npc_class_id');
    }

    /**
     * @return BelongsToMany
     */
    public function spells(): BelongsToMany
    {
        return $this->belongsToMany(Spell::class, 'npc_spells');
    }

    /**
     * @return HasMany
     */
    public function npcspells(): HasMany
    {
        return $this->hasMany(NpcSpell::class);
    }

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
     * @return bool
     */
    public function isAffectedByFortified(): bool
    {
        return in_array($this->classification_id, [NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_NORMAL], NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_ELITE]]);
    }

    /**
     * @return bool
     */
    public function isAffectedByTyrannical(): bool
    {
        return in_array($this->classification_id, [NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS], NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS]]);
    }

    /**
     * @param int $keyLevel
     * @param bool $fortified
     * @param bool $tyrannical
     * @return float
     */
    public function getScalingFactor(int $keyLevel, bool $fortified, bool $tyrannical): float
    {
        $keyLevelFactor = 1;
        // 2 because we start counting up at key level 3 (+2 = 0)
        for ($i = 2; $i < $keyLevel; $i++) {
            $keyLevelFactor *= ($i < 10 ? config('keystoneguru.keystone.scaling_factor') : config('keystoneguru.keystone.scaling_factor_past_10'));
        }

        if ($fortified && $this->isAffectedByFortified()) {
            $keyLevelFactor *= 1.2;
        } else if ($tyrannical && $this->isAffectedByTyrannical()) {
            $keyLevelFactor *= 1.3;
        }

        return round($keyLevelFactor * 100) / 100;
    }

    /**
     * @param int $keyLevel
     * @param bool $fortified
     * @param bool $tyrannical
     * @param bool $thundering
     * @return void
     */
    public function calculateHealthForKey(int $keyLevel, bool $fortified, bool $tyrannical, bool $thundering): float
    {
        $thunderingFactor = $thundering && $keyLevel >= 10 ? 1.05 : 1;
        return round($this->base_health * $this->getScalingFactor($keyLevel, $fortified, $tyrannical, $thundering) * $thunderingFactor);
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

    /**
     * @return int
     */
    public function getDungeonId(): int
    {
        return $this->dungeon_id ?? -1;
    }
}
