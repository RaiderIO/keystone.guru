<?php

namespace App\Models;

use App\Logic\MDT\Conversion;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Npc\NpcType;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use App\Models\Spell\Spell;
use App\Service\Season\SeasonServiceInterface;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Mockery\Exception;

/**
 * @property int                                    $id The ID of this Dungeon.
 * @property int                                    $expansion_id The linked expansion to this dungeon.
 * @property int                                    $game_version_id The linked game version to this dungeon.
 * @property int                                    $zone_id The ID of the location that WoW has given this dungeon.
 * @property int                                    $map_id The ID of the map (used internally in the game, used for simulation craft purposes)
 * @property int|null                               $instance_id The ID of the instance (used internally in the game, used for MDT mapping export purposes)
 * @property int|null                               $challenge_mode_id The ID of the M+ for this dungeon (used internally in the game, used for ARC)
 * @property int                                    $mdt_id The ID that MDT has given this dungeon.
 * @property string                                 $name The name of the dungeon.
 * @property string                                 $slug The url friendly slug of the dungeon.
 * @property string                                 $key Shorthand key of the dungeon
 * @property bool                                   $speedrun_enabled True if this dungeon has a speedrun enabled, false if it does not.
 * @property bool                                   $speedrun_difficulty_10_man_enabled True if this dungeon's speedrun is for 10-man.
 * @property bool                                   $speedrun_difficulty_25_man_enabled True if this dungeon's speedrun is for 25-man.
 * @property bool                                   $active True if this dungeon is active, false if it is not.
 * @property bool                                   $mdt_supported True if MDT is supported for this dungeon, false if it is not.
 *
 * @property Expansion                              $expansion
 * @property GameVersion                            $gameVersion
 * @property MappingVersion                         $currentMappingVersion
 *
 * @property Collection<MappingVersion>             $mappingVersions
 * @property Collection<Floor>                      $floors
 * @property Collection<Floor>                      $activeFloors
 * @property Collection<DungeonRoute>               $dungeonRoutes
 * @property Collection<DungeonRoute>               $dungeonRoutesForExport
 * @property Collection<Npc>                        $npcs
 * @property Collection<Enemy>                      $enemies
 * @property Collection<EnemyPack>                  $enemyPacks
 * @property Collection<EnemyPatrol>                $enemyPatrols
 * @property Collection<MapIcon>                    $mapIcons
 * @property Collection<DungeonFloorSwitchMarker>   $dungeonFloorSwitchMarkers
 * @property Collection<MountableArea>              $mountableAreas
 * @property Collection<DungeonSpeedrunRequiredNpc> $dungeonSpeedrunRequiredNpcs10Man
 * @property Collection<DungeonSpeedrunRequiredNpc> $dungeonSpeedrunRequiredNpcs25Man
 * @property Collection<Spell>                      $spells
 *
 * @method static Builder active()
 * @method static Builder inactive()
 * @method static Builder factionSelectionRequired()
 *
 * @mixin Eloquent
 */
class Dungeon extends CacheModel implements MappingModelInterface
{
    use DungeonConstants;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['floor_count', 'mdt_supported'];

    protected $fillable = [
        'expansion_id',
        'game_version_id',
        'active',
        'speedrun_enabled',
        'speedrun_difficulty_10_man_enabled',
        'speedrun_difficulty_25_man_enabled',
        'zone_id',
        'map_id',
        'instance_id',
        'challenge_mode_id',
        'mdt_id',
        'name',
        'key',
        'slug',
    ];

    public $with = ['expansion', 'gameVersion', 'floors'];

    public $hidden = ['slug', 'active', 'mdt_id', 'zone_id', 'instance_id', 'created_at', 'updated_at'];

    public $timestamps = false;

    /**
     * https://stackoverflow.com/a/34485411/771270
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return int The amount of floors this dungeon has.
     */
    public function getFloorCountAttribute(): int
    {
        return $this->floors->count();
    }

    public function getMdtSupportedAttribute(): bool
    {
        return Conversion::hasMDTDungeonName($this->key);
    }

    /**
     * Gets the amount of enemy forces that this dungeon has mapped (non-zero enemy_forces on NPCs)
     */
    public function getEnemyForcesMappedStatusAttribute(): array
    {
        $result = [];
        $npcs   = [];

        try {
            // Loop through all floors
            foreach ($this->npcs as $npc) {
                /** @var $npc Npc */
                if ($npc !== null && $npc->classification_id < NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS]) {
                    /** @var NpcEnemyForces|null $npcEnemyForces */
                    $npcEnemyForces = $npc->enemyForcesByMappingVersion()->first();

                    $npcs[$npc->id] = ($npcEnemyForces?->enemy_forces ?? -1) >= 0;
                }
            }
        } catch (Exception $exception) {
            dd($exception);
        }

        // Calculate which ones are unmapped
        $unmappedCount = 0;
        foreach ($npcs as $id => $npc) {
            if (!$npc) {
                $unmappedCount++;
            }
        }

        $total              = count($npcs);
        $result['npcs']     = $npcs;
        $result['unmapped'] = $unmappedCount;
        $result['total']    = $total;
        $result['percent']  = $total <= 0 ? 0 : 100 - (($unmappedCount / $total) * 100);

        return $result;
    }

    public function expansion(): BelongsTo
    {
        return $this->belongsTo(Expansion::class);
    }

    public function gameVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class);
    }

    public function mappingVersions(): HasMany
    {
        return $this->hasMany(MappingVersion::class)->orderByDesc('mapping_versions.version');
    }

    public function currentMappingVersion(): HasOne
    {
        return $this->hasOne(MappingVersion::class)
            ->without(['dungeon'])
            ->orderByDesc('mapping_versions.version')
            ->limit(1);
    }

    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class)->orderBy('index');
    }

    public function spells(): BelongsToMany
    {
        return $this->belongsToMany(Spell::class, 'spell_dungeons');
    }

    public function activeFloors(): HasMany
    {
        return $this->floors()->active();
    }

    public function floorsForMapFacade(MappingVersion $mappingVersion, ?bool $useFacade = null): HasMany
    {
        $useFacade = $useFacade ?? $mappingVersion->facade_enabled;

        // If we use facade
        // If we have facade, only return facade floor
        // Otherwise, return all non-facade floors

        return $this->hasMany(Floor::class)
            ->where(static function (Builder $builder) use ($mappingVersion, $useFacade) {
                $builder->when(!$useFacade, static function (Builder $builder) {
                    $builder->where('facade', 0);
                })->when($useFacade, static function (Builder $builder) use ($mappingVersion, $useFacade) {
                    if ($mappingVersion->facade_enabled) {
                        $builder->where('facade', $useFacade);
                    } else {
                        $builder->where('facade', 0);
                    }
                });
            })
            ->orderBy('index');
    }

    public function dungeonRoutes(): HasMany
    {
        return $this->hasMany(DungeonRoute::class);
    }

    public function dungeonRoutesForExport(): HasMany
    {
        return $this->dungeonRoutes()->where('demo', true);
    }

    public function npcs(bool $includeGlobalNpcs = true): HasMany
    {
        return $this->hasMany(Npc::class)
            ->when($includeGlobalNpcs, static function (Builder $builder) {
                $builder->orWhere('dungeon_id', -1);
            });
    }

    public function enemies(): HasManyThrough
    {
        return $this->hasManyThrough(Enemy::class, Floor::class);
    }

    public function enemyPacks(): HasManyThrough
    {
        return $this->hasManyThrough(EnemyPack::class, Floor::class);
    }

    public function enemyPatrols(): HasManyThrough
    {
        return $this->hasManyThrough(EnemyPatrol::class, Floor::class);
    }

    public function mapIcons(): HasManyThrough
    {
        return $this->hasManyThrough(MapIcon::class, Floor::class)
            ->where(static fn(Builder $builder) => $builder
                ->whereNull('dungeon_route_id'));
    }

    public function dungeonFloorSwitchMarkers(): HasManyThrough
    {
        return $this->hasManyThrough(DungeonFloorSwitchMarker::class, Floor::class);
    }

    public function mountableAreas(): HasManyThrough
    {
        return $this->hasManyThrough(MountableArea::class, Floor::class);
    }

    public function dungeonSpeedrunRequiredNpcs10Man(): HasManyThrough
    {
        return $this->hasManyThrough(DungeonSpeedrunRequiredNpc::class, Floor::class)
            ->where('difficulty', Dungeon::DIFFICULTY_10_MAN);
    }

    public function dungeonSpeedrunRequiredNpcs25Man(): HasManyThrough
    {
        return $this->hasManyThrough(DungeonSpeedrunRequiredNpc::class, Floor::class)
            ->where('difficulty', Dungeon::DIFFICULTY_25_MAN);
    }

    /**
     * Scope a query to only the Siege of Boralus dungeon.
     */
    public function scopeFactionSelectionRequired(Builder $query): Builder
    {
        return $query->whereIn('key', [self::DUNGEON_SIEGE_OF_BORALUS, self::DUNGEON_THE_NEXUS]);
    }

    /**
     * Scope a query to only include active dungeons.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('dungeons.active', 1);
    }

    /**
     * Scope a query to only include inactive dungeons.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('dungeons.active', 0);
    }

    public function getDungeonStart(): ?MapIcon
    {
        $result = null;

        foreach ($this->floors as $floor) {
            foreach ($floor->mapIcons as $mapicon) {
                if ($mapicon->map_icon_type_id === MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START]) {
                    $result = $mapicon;
                    break;
                }
            }
        }

        return $result;
    }

    public function getFacadeFloor(): ?Floor
    {
        return $this->floors->first(static fn(Floor $floor) => $floor->facade);
    }

    /**
     * Get the season that is active for this dungeon right now (preferring upcoming seasons if current and next season overlap)
     */
    public function getActiveSeason(SeasonServiceInterface $seasonService): ?Season
    {
        $nextSeason = $seasonService->getNextSeasonOfExpansion();
        if ($nextSeason !== null && $nextSeason->hasDungeon($this)) {
            return $nextSeason;
        }

        // $currentSeason cannot be null - there's always a season for the current expansion
        $currentSeason = $seasonService->getCurrentSeason();
        if ($currentSeason->hasDungeon($this)) {
            return $currentSeason;
        }

        // Timewalking fallback
        return $seasonService->getCurrentSeason($this->expansion);
    }

    private function getNpcsHealthBuilderEnemyForcesDungeonExclusionList(): array
    {
        // Unpack all raids in a single array, see https://stackoverflow.com/a/46861938/771270
        $allRaids = array_merge(...array_values(self::ALL_RAID));

        return array_merge(
            $allRaids,
            // These expansions never had M+ so ignore exclusions based on enemy forces since they never had any
            self::ALL[Expansion::EXPANSION_CLASSIC],
            self::ALL[Expansion::EXPANSION_TBC],
            self::ALL[Expansion::EXPANSION_WOTLK],
            self::ALL[Expansion::EXPANSION_CATACLYSM],
            self::ALL[Expansion::EXPANSION_MOP],
            self::ALL[Expansion::EXPANSION_WOD],
        );
    }

    private function getNpcsHealthBuilder(MappingVersion $mappingVersion): HasMany
    {
        return $this->npcs(false)
            // Ensure that there's at least one enemy by having this join
            ->join('enemies', 'enemies.npc_id', 'npcs.id')
            ->where('enemies.mapping_version_id', $mappingVersion->id)
            ->where('classification_id', '<', NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS])
            ->where('npc_type_id', '!=', NpcType::CRITTER)
            ->whereIn('aggressiveness', [Npc::AGGRESSIVENESS_AGGRESSIVE, Npc::AGGRESSIVENESS_UNFRIENDLY, Npc::AGGRESSIVENESS_AWAKENED])
            ->when(!in_array($this->key, $this->getNpcsHealthBuilderEnemyForcesDungeonExclusionList()),
                static fn(Builder $builder) => $builder
                    ->join('npc_enemy_forces', 'npc_enemy_forces.npc_id', 'npcs.id')
                    ->where('npc_enemy_forces.mapping_version_id', $mappingVersion->id))
            ->groupBy('enemies.npc_id');
    }

    /**
     * Get the minimum amount of health of all NPCs in this dungeon.
     */
    public function getNpcsMinHealth(MappingVersion $mappingVersion): int
    {
        return $this->getNpcsHealthBuilder($mappingVersion)->orderBy('npcs.base_health')->min('base_health') ?? 10000;
    }

    /**
     * Get the maximum amount of health of all NPCs in this dungeon.
     */
    public function getNpcsMaxHealth(MappingVersion $mappingVersion): int
    {
        return $this->getNpcsHealthBuilder($mappingVersion)->orderByDesc('npcs.base_health')->max('base_health') ?? 10000;
    }

    /**
     * @return Collection<Npc>
     */
    public function getInUseNpcs(): Collection
    {
        return Npc::select('npcs.*')
            ->leftJoin('npc_enemy_forces', 'npcs.id', 'npc_enemy_forces.npc_id')
            ->where(fn(Builder $builder) => $builder->where('npcs.dungeon_id', $this->id)
                ->orWhere('npcs.dungeon_id', -1))
            ->where(function (Builder $builder) {
                $builder->where(function (Builder $builder) {
                    // Enemy forces may be not set, that means that we assume 0. They MAY be missing entirely for bosses
                    // or for other exceptions listed below
                    $builder->where('npc_enemy_forces.mapping_version_id', $this->currentMappingVersion->id)
                        ->whereNotNull('npc_enemy_forces.enemy_forces');
                })->orWhereIn('npcs.classification_id', [
                    NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS],
                    NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS],
                    NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_RARE],
                ])->orWhereIn('npcs.id', [
                    // Neltharion's Lair:
                    // Burning Geodes are in the mapping but give 0 enemy forces.
                    // They're in the mapping because they're dangerous af
                    101437,
                    // Halls of Infusion:
                    // Aqua Ragers are in the mapping but give 0 enemy forces - so would be excluded.
                    // They're in the mapping because they are a significant drain on time and excluding them would raise questions about why they're gone
                    190407,
                    // Brackenhide Hollow:
                    // Witherlings that are a significant nuisance to be included in the mapping. They give 0 enemy forces.
                    194273,
                    // Rotfang Hyena are part of Gutshot boss but, they are part of the mapping. They give 0 enemy forces.
                    194745,
                    // Wild Lashers give 0 enemy forces but are in the mapping regardless
                    191243,
                    // Wither Slashers give 0 enemy forces but are in the mapping regardless
                    194469,
                    // Gutstabbers give 0 enemy forces but are in the mapping regardless
                    197857,
                    // Nokhud Offensive:
                    // War Ohuna gives 0 enemy forces but is in the mapping regardless
                    192803,
                    // Stormsurge Totem gives 0 enemy forces but is in the mapping regardless
                    194897,
                    // Unstable Squall gives 0 enemy forces but is in the mapping regardless
                    194895,
                    // Primal Gust gives 0 enemy forces but is in the mapping regardless
                    195579,
                    // Dawn of the Infinite:
                    // Temporal Deviation gives 0 enemy forces but is in the mapping regardless
                    206063,
                    // Iridikron's Creation
                    204918,
                ]);
            })
            ->get();
    }

    /**
     * @return Collection<int>
     */
    public function getInUseNpcIds(): Collection
    {
        return $this->getInUseNpcs()
            ->pluck('id')
            // Brackenhide Hollow:  Odd exception to make Brackenhide Gnolls show up. They aren't in the MDT mapping, so
            // they don't get npc_enemy_forces pushed. But we do need them to show up for us since they convert
            // into Witherlings which ARE on the mapping. Without this exception, they wouldn't turn up and the
            // Witherlings would never get mapped properly
            ->push(194373);
    }

    public function isFactionSelectionRequired(): bool
    {
        return in_array($this->key, [self::DUNGEON_SIEGE_OF_BORALUS, self::DUNGEON_THE_NEXUS]);
    }

    public function getImageUrl(): string
    {
        return url(sprintf('images/dungeons/%s/%s.jpg', $this->expansion->shortname, $this->key));
    }

    public function getImage32Url(): string
    {
        return url(sprintf('images/dungeons/%s/%s_3-2.jpg', $this->expansion->shortname, $this->key));
    }

    public function getImageTransparentUrl(): string
    {
        return url(sprintf('images/dungeons/%s/%s_transparent.png', $this->expansion->shortname, $this->key));
    }

    public function getImageWallpaperUrl(): string
    {
        return url(sprintf('images/dungeons/%s/%s_wallpaper.jpg', $this->expansion->shortname, $this->key));
    }

    public function hasImageWallpaper(): bool
    {
        return file_exists(resource_path(sprintf('assets/images/dungeons/%s/%s_wallpaper.jpg', $this->expansion->shortname, $this->key)));
    }

    public static function findExpansionByKey(string $key): ?string
    {
        $result = null;

        foreach (array_merge_recursive(self::ALL, self::ALL_RAID) as $expansion => $dungeonKeys) {
            if (in_array($key, $dungeonKeys)) {
                $result = $expansion;
                break;
            }
        }

        return $result;
    }

    public function getDungeonId(): ?int
    {
        return $this->id;
    }

    public static function boot(): void
    {
        parent::boot();

        static::deleting(static fn(Model $model) => false);
    }
}
