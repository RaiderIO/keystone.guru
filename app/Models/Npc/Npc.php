<?php

namespace App\Models\Npc;

use App\Models\Affix;
use App\Models\CacheModel;
use App\Models\Characteristic;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Spell\Spell;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * @property int                                $id
 * @property int                                $dungeon_id
 * @property int                                $classification_id
 * @property int                                $npc_type_id
 * @property int                                $npc_class_id
 * @property int|null                           $display_id
 * @property int|null                           $encounter_id
 * @property string                             $name
 * @property int                                $base_health
 * @property int|null                           $health_percentage Null = 100% health
 * @property int|null                           $level
 * @property float|null                         $mdt_scale
 * @property string                             $aggressiveness
 * @property bool                               $dangerous
 * @property bool                               $truesight
 * @property bool                               $bursting
 * @property bool                               $bolstering
 * @property bool                               $sanguine
 * @property bool                               $runs_away_in_fear
 * @property bool                               $hyper_respawn
 *
 * @property Dungeon                            $dungeon
 * @property NpcClassification                  $classification
 * @property NpcType                            $type
 * @property NpcClass                           $class
 *
 * @property NpcEnemyForces|null                $enemyForces
 * @property Collection<NpcEnemyForces>         $npcEnemyForces
 * @property Collection<Enemy>                  $enemies
 * @property Collection<Characteristic>         $characteristics
 * @property Collection<NpcCharacteristic>      $npcCharacteristics
 * @property Collection<Spell>                  $spells
 * @property Collection<NpcSpell>               $npcSpells
 * @property Collection<NpcBolsteringWhitelist> $npcbolsteringwhitelists
 *
 * @mixin Eloquent
 */
class Npc extends CacheModel implements MappingModelInterface
{
    use SeederModel;

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

    public $incrementing = false;

    public $timestamps = false;

    protected $with = ['type', 'class', 'npcbolsteringwhitelists', 'characteristics', 'spells'];

    protected $fillable = [
        'id',
        'dungeon_id',
        'classification_id',
        'npc_type_id',
        'npc_class_id',
        'display_id',
        'name',
        'base_health',
        'health_percentage',
        'level',
        'mdt_scale',
        'aggressiveness',
        'dangerous',
        'truesight',
        'bursting',
        'bolstering',
        'sanguine',
        'runs_away_in_fear',
    ];

    protected $casts = [
        'id'                => 'integer',
        'dungeon_id'        => 'integer',
        'classification_id' => 'integer',
        'npc_type_id'       => 'integer',
        'npc_class_id'      => 'integer',
        'display_id'        => 'integer',
        'base_health'       => 'integer',
        'health_percentage' => 'integer',
        'level'             => 'integer',
        'mdt_scale'         => 'float',
    ];

    /**
     * Gets all derived enemies from this Npc.
     */
    public function enemies(): HasMany
    {
        return $this->hasMany(Enemy::class);
    }

    public function npcbolsteringwhitelists(): HasMany
    {
        return $this->hasMany(NpcBolsteringWhitelist::class);
    }

    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }

    public function classification(): BelongsTo
    {
        return $this->belongsTo(NpcClassification::class);
    }

    public function type(): BelongsTo
    {
        // Not sure why the foreign key declaration is required here, but it is
        return $this->belongsTo(NpcType::class, 'npc_type_id');
    }

    public function class(): BelongsTo
    {
        // Not sure why the foreign key declaration is required here, but it is
        return $this->belongsTo(NpcClass::class, 'npc_class_id');
    }

    public function characteristics(): BelongsToMany
    {
        return $this->belongsToMany(Characteristic::class, 'npc_characteristics')->orderBy('characteristics.id');
    }

    public function npcCharacteristics(): HasMany
    {
        return $this->hasMany(NpcCharacteristic::class)->orderBy('characteristic_id');
    }

    public function spells(bool $onlyVisibleOnMap = true): BelongsToMany
    {
        return $this->belongsToMany(Spell::class, 'npc_spells')
            ->when($onlyVisibleOnMap, static fn($query) => $query->where('hidden_on_map', false))
            ->orderBy('spells.id');
    }

    public function npcSpells(): HasMany
    {
        return $this->hasMany(NpcSpell::class)->orderBy('spell_id');
    }

    public function npcEnemyForces(): HasMany
    {
        return $this->hasMany(NpcEnemyForces::class)->orderByDesc('mapping_version_id');
    }

    public function enemyForces(): HasOne
    {
        return $this->hasOne(NpcEnemyForces::class)->orderByDesc('mapping_version_id');
    }

    public function setEnemyForces(int $enemyForces, ?MappingVersion $mappingVersion = null): NpcEnemyForces
    {
        if ($this->dungeon_id === -1 && $mappingVersion === null) {
            throw new InvalidArgumentException('Unable to set enemy forces for global npc without a mapping version!');
        }

        $npcEnemyForces = $this->enemyForcesByMappingVersion($mappingVersion)->first();

        if ($npcEnemyForces === null) {
            $npcEnemyForces = NpcEnemyForces::create([
                'mapping_version_id' => ($mappingVersion ?? $this->dungeon->currentMappingVersion)->id,
                'npc_id'             => $this->id,
                'enemy_forces'       => $enemyForces,
            ]);
        } else {
            $npcEnemyForces->update([
                'enemy_forces' => $enemyForces,
            ]);
        }

        return $npcEnemyForces;
    }

    public function enemyForcesByMappingVersion(?int $mappingVersionId = null): HasOne
    {
        $belongsTo = $this->hasOne(NpcEnemyForces::class);

        // Most recent
        if ($mappingVersionId === null) {
            $belongsTo->orderByDesc('mapping_version_id')->limit(1);
        } else {
            $belongsTo->where('mapping_version_id', $mappingVersionId);
        }

        return $belongsTo;
    }

    public function isEmissary(): bool
    {
        return in_array($this->id, [155432, 155433, 155434]);
    }

    public function isAwakened(): bool
    {
        return in_array($this->id, [161244, 161243, 161124, 161241]);
    }

    public function isEncrypted(): bool
    {
        return in_array($this->id, [185680, 185683, 185685]);
    }

    public function isPrideful(): bool
    {
        return $this->id === config('keystoneguru.prideful.npc_id');
    }

    public function isShrouded(): bool
    {
        return $this->id === config('keystoneguru.shrouded.npc_id');
    }

    public function isShroudedZulGamux(): bool
    {
        return $this->id === config('keystoneguru.shrouded.npc_id_zul_gamux');
    }

    public function isAffectedByFortified(): bool
    {
        return in_array($this->classification_id, [NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_NORMAL], NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_ELITE]]);
    }

    public function isAffectedByTyrannical(): bool
    {
        return in_array($this->classification_id, [NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS], NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS]]);
    }

    public function getScalingFactor(int $keyLevel, array $affixes = []): float
    {
        $keyLevelFactor = 1;
        for ($i = 1; $i < $keyLevel; $i++) {
            $keyLevelFactor *= ($i < 10 ? config('keystoneguru.keystone.scaling_factor') : config('keystoneguru.keystone.scaling_factor_past_10'));
        }

        if (in_array(Affix::AFFIX_FORTIFIED, $affixes) && $this->isAffectedByFortified()) {
            $keyLevelFactor *= config('keystoneguru.keystone.affix_scaling_factor.fortified');
        }

        if (in_array(Affix::AFFIX_TYRANNICAL, $affixes) && $this->isAffectedByTyrannical()) {
            $keyLevelFactor *= config('keystoneguru.keystone.affix_scaling_factor.tyrannical');
        }

        if ($keyLevel >= 10 && in_array(Affix::AFFIX_THUNDERING, $affixes)) {
            $keyLevelFactor *= config('keystoneguru.keystone.affix_scaling_factor.thundering');
        }

        if ($keyLevel >= 12 && in_array(Affix::AFFIX_XALATATHS_GUILE, $affixes)) {
            $keyLevelFactor *= config('keystoneguru.keystone.affix_scaling_factor.xalataths_guile');
        }

        return round($keyLevelFactor * 100) / 100;
    }

    /**
     * @param int   $keyLevel
     * @param array $affixes A list of Affix:: constants
     * @return float
     */
    public function calculateHealthForKey(int $keyLevel, array $affixes = []): float
    {
        return round($this->base_health * (($this->health_percentage ?? 100) / 100) * $this->getScalingFactor($keyLevel, $affixes));
    }

    /**
     * Upon creation of a new NPC, we must create npc enemy forces for each mapping version
     */
    public function createNpcEnemyForcesForExistingMappingVersions(?int $existingEnemyForces = null): bool
    {
        $result = true;

        if ($existingEnemyForces > 0) {
            $this->load('dungeon');
            // Create new enemy forces for this enemy for each relevant mapping version
            // If no dungeon is found (dungeon_id = -1) we grab all mapping versions instead
            $mappingVersions = $this->dungeon?->mappingVersions ?? MappingVersion::all();
            foreach ($mappingVersions as $mappingVersion) {
                $result = $result && NpcEnemyForces::create([
                        'npc_id'               => $this->id,
                        'mapping_version_id'   => $mappingVersion->id,
                        'enemy_forces'         => $existingEnemyForces ?? 0,
                        'enemy_forces_teeming' => null,
                    ]);
            }
        }

        return $result;
    }

    public function getDungeonId(): ?int
    {
        return $this->dungeon_id ?? null;
    }

    protected static function booted(): void
    {
        parent::booted();

        // Delete Npc properly if it gets deleted
        static::deleting(static function (Npc $npc) {
            $npc->npcbolsteringwhitelists()->delete();
            $npc->npcCharacteristics()->delete();
            $npc->npcSpells()->delete();
            $npc->npcEnemyForces()->delete();
        });
    }
}
