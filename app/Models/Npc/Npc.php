<?php

namespace App\Models\Npc;

use App\Models\Affix;
use App\Models\CacheModel;
use App\Models\Characteristic;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Spell\Spell;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Override;

/**
 * @property int        $id
 * @property int        $classification_id
 * @property int        $npc_type_id
 * @property int        $npc_class_id
 * @property int|null   $display_id
 * @property int|null   $encounter_id
 * @property string     $name
 * @property int|null   $level
 * @property float|null $mdt_scale
 * @property string     $aggressiveness
 * @property bool       $dangerous
 * @property bool       $truesight
 * @property bool       $bursting
 * @property bool       $bolstering
 * @property bool       $sanguine
 * @property bool       $runs_away_in_fear
 * @property bool       $hyper_respawn
 *
 * @property string               $enemy_portrait_url
 * @property string               $wowhead_url
 * @property array<string, mixed> $tooltip_data
 *
 * @property NpcClassification|null $classification A few seeded NPCs carry an id no classification row matches
 * @property NpcType                $type
 * @property NpcClass               $class
 *
 * @property NpcEnemyForces|null                             $enemyForces
 * @property EloquentCollection<int, NpcEnemyForces>         $npcEnemyForces
 * @property EloquentCollection<int, Enemy>                  $enemies
 * @property EloquentCollection<int, Characteristic>         $characteristics
 * @property EloquentCollection<int, NpcCharacteristic>      $npcCharacteristics
 * @property EloquentCollection<int, Spell>                  $spells
 * @property EloquentCollection<int, NpcSpell>               $npcSpells
 * @property EloquentCollection<int, NpcBolsteringWhitelist> $npcbolsteringwhitelists
 * @property EloquentCollection<int, Dungeon>                $dungeons
 * @property EloquentCollection<int, NpcDungeon>             $npcDungeons
 * @property EloquentCollection<int, NpcHealth>              $npcHealths
 * @property float|null                                      $min_health              Computed aggregate column (getNpcsMinMaxHealth)
 * @property float|null                                      $max_health              Computed aggregate column (getNpcsMinMaxHealth)
 *
 * @mixin Eloquent
 */
class Npc extends CacheModel implements MappingModelInterface
{
    use SeederModel;

    public const string AGGRESSIVENESS_AGGRESSIVE = 'aggressive';
    public const string AGGRESSIVENESS_UNFRIENDLY = 'unfriendly';
    public const string AGGRESSIVENESS_NEUTRAL    = 'neutral';
    public const string AGGRESSIVENESS_FRIENDLY   = 'friendly';
    public const string AGGRESSIVENESS_AWAKENED   = 'awakened';

    public const array ALL_AGGRESSIVENESS = [
        self::AGGRESSIVENESS_AGGRESSIVE,
        self::AGGRESSIVENESS_UNFRIENDLY,
        self::AGGRESSIVENESS_NEUTRAL,
        self::AGGRESSIVENESS_FRIENDLY,
        self::AGGRESSIVENESS_AWAKENED,
    ];

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'dungeon_id',
        'classification_id',
        'npc_type_id',
        'npc_class_id',
        'display_id',
        'name',
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

    protected $appends = [
        'enemy_portrait_url',
        'wowhead_url',
    ];

    protected function casts(): array
    {
        return [
            'id'                => 'integer',
            'dungeon_id'        => 'integer',
            'classification_id' => 'integer',
            'npc_type_id'       => 'integer',
            'npc_class_id'      => 'integer',
            'display_id'        => 'integer',
            'level'             => 'integer',
            'mdt_scale'         => 'float',
        ];
    }

    #[\Override]
    public function resolveRouteBinding($value, $field = null): ?static
    {
        $id = (int)explode('-', (string)$value, 2)[0];

        /** @var static|null */
        return $this->where('id', $id)->first();
    }

    #[\Override]
    public function getRouteKey(): string
    {
        return sprintf('%d-%s', $this->id, Str::slug(__($this->name)));
    }

    public function getEnemyPortraitUrlAttribute(): string
    {
        return sprintf('images/enemyportraits/%d.png', $this->id);
    }

    /**
     * Everything the hover tooltip shows, in one payload - the counterpart of Spell::$tooltip_data (#4096).
     *
     * Deliberately NOT in $appends, unlike the spell one: NPCs are serialized in bulk into the map
     * context, and computing this for every NPC of a dungeon would both lazy-load four relations per
     * NPC and grow that payload with text no map ever renders. Render sites opt in instead, either by
     * reading $npc->tooltip_data or by appending it to a collection they eager-loaded themselves.
     *
     * Reads the classification, type, characteristics and npcHealths relations - eager-load all four
     * wherever this is rendered for more than one NPC.
     *
     * @return array<string, mixed>
     */
    public function getTooltipDataAttribute(): array
    {
        return array_filter([
            'name'        => __($this->name),
            'portraitUrl' => ksgAsset($this->enemy_portrait_url),
            // A handful of seeded NPCs carry a classification_id no npc_classifications row matches,
            // so this relation really can come back null - the badge is then left out entirely
            'classification'    => $this->classification === null ? null : __($this->classification->name),
            'classificationKey' => $this->classification?->key,
            'health'            => $this->getTooltipHealth(),
            'type'              => $this->type->type,
            // Mirrors the flags the NPC's own compendium page shows; bursting/bolstering/sanguine are
            // deliberately left out there as well
            'flags' => array_values(array_filter([
                $this->dangerous ? __('view_admin.npc.edit.dangerous') : null,
                $this->truesight ? __('view_admin.npc.edit.truesight') : null,
                $this->runs_away_in_fear ? __('view_admin.npc.edit.runs_away_in_fear') : null,
            ])),
            // Observed crowd control only - an NPC without a characteristic was never seen affected by
            // one, which is not the same as being immune to it (#4028), so nothing is listed as absent
            'characteristics' => $this->characteristics->map(static fn(Characteristic $characteristic): array => [
                'name'    => __($characteristic->name),
                'iconUrl' => ksgAssetImage(sprintf('spells/%s.jpg', $characteristic->icon_name)),
            ])->values()->all(),
        ], static fn(mixed $value): bool => $value !== null && $value !== []);
    }

    public function getWowheadUrlAttribute(): string
    {
        $result = sprintf('https://www.wowhead.com/npc=%d', $this->id);

        if (!empty(__($this->name))) {
            $result .= '/' . Str::slug(__($this->name));
        }

        return $result;
    }

    /**
     * Gets all derived enemies from this Npc.
     *
     * @return HasMany<Enemy, $this>
     */
    public function enemies(): HasMany
    {
        return $this->hasMany(Enemy::class);
    }

    /** @return HasMany<NpcBolsteringWhitelist, $this> */
    public function npcbolsteringwhitelists(): HasMany
    {
        return $this->hasMany(NpcBolsteringWhitelist::class);
    }

    /** @return BelongsToMany<Dungeon, $this> */
    public function dungeons(): BelongsToMany
    {
        return $this->belongsToMany(Dungeon::class, 'npc_dungeons');
    }

    /** @return HasMany<NpcDungeon, $this> */
    public function npcDungeons(): HasMany
    {
        return $this->hasMany(NpcDungeon::class);
    }

    /** @return BelongsTo<NpcClassification, $this> */
    public function classification(): BelongsTo
    {
        return $this->belongsTo(NpcClassification::class);
    }

    /** @return BelongsTo<NpcType, $this> */
    public function type(): BelongsTo
    {
        // Not sure why the foreign key declaration is required here, but it is
        return $this->belongsTo(NpcType::class, 'npc_type_id');
    }

    /** @return BelongsTo<NpcClass, $this> */
    public function class(): BelongsTo
    {
        // Not sure why the foreign key declaration is required here, but it is
        return $this->belongsTo(NpcClass::class, 'npc_class_id');
    }

    /** @return BelongsToMany<Characteristic, $this> */
    public function characteristics(): BelongsToMany
    {
        return $this->belongsToMany(Characteristic::class, 'npc_characteristics')->orderBy('characteristics.id');
    }

    /** @return HasMany<NpcCharacteristic, $this> */
    public function npcCharacteristics(): HasMany
    {
        return $this->hasMany(NpcCharacteristic::class)->orderBy('characteristic_id');
    }

    /** @return BelongsToMany<Spell, $this> */
    public function spells(bool $onlyVisibleOnMap = true): BelongsToMany
    {
        /** @var BelongsToMany<Spell, $this> $query */
        $query = $this->belongsToMany(Spell::class, 'npc_spells')
            ->when($onlyVisibleOnMap, static fn($query) => $query->where('hidden_on_map', false))
            ->orderBy('spells.id');

        return $query;
    }

    /** @return HasMany<NpcSpell, $this> */
    public function npcSpells(): HasMany
    {
        return $this->hasMany(NpcSpell::class)->orderBy('spell_id');
    }

    /** @return HasMany<NpcEnemyForces, $this> */
    public function npcEnemyForces(): HasMany
    {
        return $this->hasMany(NpcEnemyForces::class)->orderByDesc('mapping_version_id');
    }

    /** @return HasOne<NpcEnemyForces, $this> */
    public function enemyForces(): HasOne
    {
        return $this->hasOne(NpcEnemyForces::class)->orderByDesc('mapping_version_id');
    }

    /** @return HasMany<NpcHealth, $this> */
    public function npcHealths(): HasMany
    {
        return $this->hasMany(NpcHealth::class);
    }

    public function setEnemyForces(int $enemyForces, MappingVersion $mappingVersion): NpcEnemyForces
    {
        $npcEnemyForces = $this->enemyForcesByMappingVersion($mappingVersion->id);

        if ($npcEnemyForces === null) {
            $npcEnemyForces = NpcEnemyForces::create([
                'mapping_version_id' => $mappingVersion->id,
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

    public function enemyForcesByMappingVersion(?int $mappingVersionId = null): ?NpcEnemyForces
    {
        return $this->npcEnemyForces->when($mappingVersionId !== null, fn(Collection $collection) => $collection->filter(fn(NpcEnemyForces $npcEnemyForces) => $npcEnemyForces->mapping_version_id === $mappingVersionId))->sortByDesc('mapping_version_id')
            ->first();
    }

    public function isEmissary(): bool
    {
        return in_array($this->id, [
            155432,
            155433,
            155434,
        ]);
    }

    public function isAwakened(): bool
    {
        return in_array($this->id, [
            161244,
            161243,
            161124,
            161241,
        ]);
    }

    public function isEncrypted(): bool
    {
        return in_array($this->id, [
            185680,
            185683,
            185685,
        ]);
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
        return !$this->isBoss();
    }

    public function isAffectedByTyrannical(): bool
    {
        return $this->isBoss();
    }

    public function isBoss(): bool
    {
        return in_array($this->classification_id, [
            NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS],
            NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS],
        ]);
    }

    /** @param array<int, string> $affixes */
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

    public function getHealthByGameVersion(GameVersion $gameVersion): ?NpcHealth
    {
        return $this->npcHealths->keyBy('game_version_id')->get($gameVersion->id);
    }

    /**
     * @param array<int, string> $affixes A list of Affix:: string constants
     */
    public function calculateHealthForKey(GameVersion $gameVersion, int $keyLevel, array $affixes = []): float
    {
        $health = $this->getHealthByGameVersion($gameVersion);
        if ($health === null) {
            return 123456; // This is a placeholder value, should never happen
        }

        return round($health->health * (($health->percentage ?? 100) / 100) * $this->getScalingFactor($keyLevel, $affixes));
    }

    /**
     * Upon creation of a new NPC, we must create npc enemy forces for each mapping version
     */
    public function createNpcEnemyForcesForExistingMappingVersions(?int $existingEnemyForces = null): bool
    {
        $result = true;

        if ($existingEnemyForces > 0) {
            $this->load('dungeons');

            // Create new enemy forces for this enemy for each relevant mapping version
            // If no dungeon is found (dungeon_id = -1) we grab all mapping versions instead
            $this->dungeons->load([ // @phpstan-ignore argument.type (Larastan passes concrete relation type; contravariant closure parameter is correct at runtime)
                'mappingVersions' => function (HasMany $query): void {
                    $query->without('dungeon');
                },
            ]);

            foreach ($this->dungeons as $dungeon) {
                foreach ($dungeon->mappingVersions as $mappingVersion) {
                    $result = $result && NpcEnemyForces::create([ // @phpstan-ignore booleanAnd.leftAlwaysTrue, booleanAnd.rightAlwaysTrue
                        'npc_id'               => $this->id,
                        'mapping_version_id'   => $mappingVersion->id,
                        'enemy_forces'         => $existingEnemyForces,
                        'enemy_forces_teeming' => null,
                    ]);
                }
            }
        }

        return $result;
    }

    public function getDungeonId(): ?int
    {
        /** @var Dungeon|null $dungeon */
        $dungeon = $this->dungeons->first();

        return $dungeon?->id;
    }

    /**
     * The NPC's base health as the tooltip states it, or null when we have nothing worth stating.
     *
     * `percentage` is part of the answer, not a footnote to it: MDT records a good many creatures as
     * a fraction of a stored base (a Blinding Vale add at 30% of its pack leader's health), so the
     * raw column on its own overstates them - the same product calculateHealthForKey() scales from.
     */
    private function getTooltipHealth(): ?int
    {
        $npcHealth = $this->getHealthByGameVersion(GameVersion::getUserOrDefaultGameVersion());

        // A health of exactly the placeholder means we never learned this NPC's health, so it says
        // nothing worth a row - a fair few dungeons still carry those (#4094). Checked before the
        // percentage is applied, since the placeholder is what the column stores, not what it means.
        if ($npcHealth === null || $npcHealth->health === NpcHealth::HEALTH_PLACEHOLDER) {
            return null;
        }

        return (int)round($npcHealth->health * (($npcHealth->percentage ?? 100) / 100));
    }

    #[Override]
    protected static function booted(): void
    {
        parent::booted();

        // Delete Npc properly if it gets deleted
        static::deleting(static function (Npc $npc) {
            $npc->npcbolsteringwhitelists()->delete();
            $npc->npcCharacteristics()->delete();
            $npc->npcSpells()->delete();
            $npc->npcEnemyForces()->delete();
            $npc->npcDungeons()->delete();
        });
    }
}
