<?php

namespace App\Models;

use App\Logic\MDT\Conversion;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\Interfaces\TracksPageViewInterface;
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
 * @property boolean                                $raid True if the dungeon is actually a raid, false if it is not.
 * @property string                                 $name The name of the dungeon.
 * @property string                                 $slug The url friendly slug of the dungeon.
 * @property string                                 $key Shorthand key of the dungeon
 * @property bool                                   $heatmap_enabled True if this dungeon has a heatmap enabled, false if it does not.
 * @property bool                                   $speedrun_enabled True if this dungeon has a speedrun enabled, false if it does not.
 * @property bool                                   $speedrun_difficulty_10_man_enabled True if this dungeon's speedrun is for 10-man.
 * @property bool                                   $speedrun_difficulty_25_man_enabled True if this dungeon's speedrun is for 25-man.
 * @property int                                    $views The amount of views this dungeon has had.
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
class Dungeon extends CacheModel implements MappingModelInterface, TracksPageViewInterface
{
    use DungeonConstants;

    public const PAGE_VIEW_SOURCE_VIEW_DUNGEON       = 1;
    public const PAGE_VIEW_SOURCE_VIEW_DUNGEON_EMBED = 2;

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
        'raid',
        'heatmap_enabled',
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
        'views',
    ];

    public $with = ['expansion', 'gameVersion', 'floors'];

    public $hidden = [
        'expansion_id',
        'game_version_id',
        'map_id',
        'challenge_mode_id',
        'heatmap_enabled',
        'speedrun_enabled',
        'speedrun_difficulty_10_man_enabled',
        'speedrun_difficulty_25_man_enabled',
        'views',
        'active',
        'mdt_id',
        'zone_id',
        'instance_id'
    ];

    public $timestamps = false;

    private $activeSeasonCache = null;

    /**
     * https://stackoverflow.com/a/34485411/771270
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getMdtSupportedAttribute(): bool
    {
        return Conversion::hasMDTDungeonName($this->key);
    }

    /**
     * @return int The amount of floors this dungeon has.
     */
    public function getFloorCountAttribute(): int
    {
        return $this->floors->count();
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
        return $query->whereIn('key', [/*self::DUNGEON_SIEGE_OF_BORALUS,*/ self::DUNGEON_THE_NEXUS]);
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
            foreach ($floor->mapIcons($this->currentMappingVersion)->get() as $mapIcon) {
                if ($mapIcon->map_icon_type_id === MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START]) {
                    $result = $mapIcon;
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
     * @TODO business logic should not be in a model - move to service
     */
    public function getActiveSeason(SeasonServiceInterface $seasonService, bool $useCache = true): ?Season
    {
        if ($useCache && $this->activeSeasonCache !== null) {
            return $this->activeSeasonCache;
        }

        return $this->activeSeasonCache =
            $seasonService->getUpcomingSeasonForDungeon($this) ??
            $seasonService->getMostRecentSeasonForDungeon($this) ??
            // Timewalking fallback
            $seasonService->getCurrentSeason($this->expansion);
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

    public function getNpcsMinMaxHealth(MappingVersion $mappingVersion): array
    {
        $result = $this->npcs(false)
            ->selectRaw('MIN(npcs.base_health * (COALESCE(npcs.health_percentage, 100) / 100)) AS min_health,
                                   MAX(npcs.base_health * (COALESCE(npcs.health_percentage, 100) / 100)) AS max_health')
            // Ensure that there's at least one enemy by having this join
            ->join('enemies', 'enemies.npc_id', 'npcs.id')
            ->where('enemies.mapping_version_id', $mappingVersion->id)
            ->where('classification_id', '<', NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS])
            ->where('npc_type_id', '!=', NpcType::CRITTER)
            ->whereIn('aggressiveness', [Npc::AGGRESSIVENESS_AGGRESSIVE, Npc::AGGRESSIVENESS_UNFRIENDLY, Npc::AGGRESSIVENESS_AWAKENED])
            // If we don't show em - don't take em into account for health calculations
            ->where(function ($query) {
                $query->whereNull('enemies.seasonal_type')
                    ->orWhere('enemies.seasonal_type', '!=', Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER);
            })
            ->first();

        return [
            (int)$result->min_health > 0 ? (int)$result->min_health : 10000,
            (int)$result->max_health > 0 ? (int)$result->max_health : 10000,
        ];
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

    public function trackPageView(int $source = 0): bool
    {
        // Handle route views counting
        if ($result = PageView::trackPageView($this->id, Dungeon::class, $source)) {
            // Do not update the updated_at time - triggering a refresh of the thumbnails
            $this->timestamps = false;

            $this->update(['views' => ++$this->views]);
        }

        return $result;
    }

    public static function boot(): void
    {
        parent::boot();

        static::deleting(static fn(Model $model) => false);
    }
}
