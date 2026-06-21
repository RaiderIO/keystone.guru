<?php

namespace App\Models\Floor;

use App\Logic\Structs\LatLng;
use App\Logic\Structs\MapBounds;
use App\Models\CacheModel;
use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\EnemyPatrol;
use App\Models\MapIcon;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\MountableArea;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use App\Models\Traits\HasLatLng;
use App\Models\Traits\SeederModel;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Deprecated;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * @property int         $id
 * @property int         $dungeon_id
 * @property int         $index
 * @property int|null    $mdt_sub_level
 * @property int|null    $ui_map_id
 * @property string|null $map_name                           The map name that Blizzard gives to this floor
 * @property string      $name
 * @property bool        $default
 * @property bool        $facade
 * @property int         $min_enemy_size
 * @property int         $max_enemy_size
 * @property int|null    $enemy_engagement_max_range         When generating dungeon routes, this is the maximum range from engagement of an enemy where we consider enemies in the mapping to match up
 * @property int|null    $enemy_engagement_max_range_patrols The max range after which we're considering patrols
 * @property float       $ingame_min_x
 * @property float       $ingame_min_y
 * @property float       $ingame_max_x
 * @property float       $ingame_max_y
 * @property int|null    $percentage_display_zoom
 * @property int|null    $zoom_max
 * @property bool        $active
 *
 * @property Dungeon         $dungeon
 * @property FloorUnion|null $floorUnion
 *
 * @property EloquentCollection<int, Enemy>                      $enemies
 * @property EloquentCollection<int, EnemyPack>                  $enemypacks
 * @property EloquentCollection<int, EnemyPatrol>                $enemypatrols
 * @property EloquentCollection<int, MapIcon>                    $mapIcons
 * @property EloquentCollection<int, DungeonFloorSwitchMarker>   $dungeonFloorSwitchMarkers
 * @property EloquentCollection<int, MountableArea>              $mountableAreas
 * @property EloquentCollection<int, FloorUnion>                 $floorUnions
 * @property EloquentCollection<int, FloorUnionArea>             $floorUnionAreas
 * @property EloquentCollection<int, Enemy>                      $enemiesForExport
 * @property EloquentCollection<int, EnemyPack>                  $enemyPacksForExport
 * @property EloquentCollection<int, EnemyPatrol>                $enemyPatrolsForExport
 * @property EloquentCollection<int, MapIcon>                    $mapIconsForExport
 * @property EloquentCollection<int, DungeonFloorSwitchMarker>   $dungeonFloorSwitchMarkersForExport
 * @property EloquentCollection<int, MountableArea>              $mountableAreasForExport
 * @property EloquentCollection<int, FloorUnion>                 $floorUnionsForExport
 * @property EloquentCollection<int, FloorUnionArea>             $floorUnionAreasForExport
 * @property EloquentCollection<int, FloorCoupling>              $floorcouplings
 * @property EloquentCollection<int, DungeonSpeedrunRequiredNpc> $dungeonSpeedrunRequiredNpcs10Man
 * @property EloquentCollection<int, DungeonSpeedrunRequiredNpc> $dungeonSpeedrunRequiredNpcs25Man
 * @property EloquentCollection<int, Floor>                      $connectedFloors
 * @property EloquentCollection<int, Floor>                      $directConnectedFloors
 * @property EloquentCollection<int, Floor>                      $reverseConnectedFloors
 *
 * @method static Builder active()
 * @method static Builder indexOrFacade(MappingVersion $mappingVersion, int $floorIndex)
 * @method static Builder defaultOrFacade(MappingVersion $mappingVersion)
 *
 * @mixin Eloquent
 */
class Floor extends CacheModel implements MappingModelInterface
{
    use HasFactory;
    use HasLatLng;
    use SeederModel;

    // Can map certain floors to others here, so that we can put enemies that are on their own floor (like some final
    // bosses) and put them on the main floor without introducing a 2nd floor.
    public const array UI_MAP_ID_MAPPING = [
        // Court of Stars
        762 => 761,
        763 => 761,
        // Siege of Boralus
        876 => 1162,
        // Kul Tiras -> Siege of Boralus
        895 => 1162,
        // Tiragarde Sound -> Siege of Boralus
        //        1533 => 1162, // Bastion -> Siege of Boralus ????
        // Brackenhide Hollow
        2106 => 2096,
        // Mists of Tirna Scithe
        1565 => 1669,
        // Ardenweald -> Mists of Tirna Scithe
        // Temple of the Jade Serpent
        430 => 429,
        // Algeth'ar Academy
        2099 => 2097,
        // Ruby Life Pools (Dragon Isles zone)
        1978 => 2095,
        // Nokhud Offensive
        2023 => 2093,
        // City of Threads,
        2216 => 2357,
        // City of Threads (Lower) -> City of Echoes
        // The Dawnbreaker
        2215 => 2359,
        // Harrowfall -> The Dawnbreaker
        // Twilight Highlands -> Grim Batol
        241 => 293,
        // Karazhan
        809 => 350, // Servant's Quarters
        810 => 351, // Upper Livery Stables
        811 => 352, // The Banquet Hall
        812 => 353, // The Guest Chambers
        813 => 354, // Opera Hall Balcony
        814 => 355, // Master's Terrace
        815 => 356, // Lower Broken Stair
        816 => 357, // Upper Broken Stair
        817 => 358, // The Menagerie
        818 => 359, // Guardian's Library
        819 => 360, // Library Floor
        820 => 361, // Upper Library
        821 => 362, // Gamesman's Hall
        822 => 363, // Netherspace
    ];

    /**
     * Some IDs which are open world, and we cannot resolve to a dungeon floor.
     */
    public const array UI_MAP_ID_OPEN_WORLD = [
        // Unknown
        0,
        // Eastern Kingdoms
        13,
        // Deadwind Pass
        42,
        // Broken Isles
        619,
        // Azsuna
        630,
        // Highmountain
        650,
        // Suramar
        680,
        // Mechagon
        1490,
        // Mechagon
        1493,
        // Revendreth
        1525,
        // Maldraxxus
        1536,
        // The Shadowlands
        1550,
        // Torghast
        1804,
        1896,
        1914,
        // Bastion
        1533,
        1813,
        // Maldraxxus
        1814,
        // Fungal Terminus
        1819,
        // Pit of Anguish
        1820,
        // The Root Cellar
        1825,
        // Tazavesh, the Veiled Market
        2016,
        // Resonant Peaks
        2059,
        // Vault of the Incarnates
        2119,
        2120,
        2121,
        2122,
        2123,
        2124,
        2125,
        2126,
        // Ohn'ahran Plains
        2129,
        // The Forbidden Reach
        2151,
        // Abberus
        2170,
        // The Ringing Deeps
        2214,
        // The Nighthold
        2220,
        // Isle of Dorn
        2248,
        // Khaz Algar
        2274,
        // Hall of Awakening
        2322,
        // Dornogal
        2339,
        // Undermine
        2346,
        // Slayer's Rise
        2397,
        // Tazavesh
        2472,
        // ???
        1411,
        1414,
        1421,
        1436,
        1450,
        1897,
        1916,
        1918,
        2116,
        2137,
        2138,
        2324,
        2399,
    ];

    public const int DARKFLAME_CLEFT_SHADOW_REALM_UI_MAP_ID = 2304;

    protected $fillable = [
        'dungeon_id',
        'index',
        'mdt_sub_level',
        'ui_map_id',
        'map_name',
        'name',
        'default',
        'facade',
        'min_enemy_size',
        'max_enemy_size',
        'enemy_engagement_max_range',
        'enemy_engagement_max_range_patrols',
        'ingame_min_x',
        'ingame_min_y',
        'ingame_max_x',
        'ingame_max_y',
        'percentage_display_zoom',
        'zoom_max',
        'active',
    ];

    public $timestamps = false;

    public $hidden = [
        'dungeon_id',
        'mdt_sub_level',
        'ui_map_id',
        'map_name',
        'enemy_engagement_max_range',
        'enemy_engagement_max_range_patrols',
    ];

    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }

    public function enemies(?MappingVersion $mappingVersion = null): HasMany
    {
        return $this->hasMany(Enemy::class)
            ->where('enemies.mapping_version_id', ($mappingVersion ?? $this->dungeon->getCurrentMappingVersion())->id);
    }

    public function enemypacks(?MappingVersion $mappingVersion = null): HasMany
    {
        return $this->hasMany(EnemyPack::class)
            ->where('enemy_packs.mapping_version_id', ($mappingVersion ?? $this->dungeon->getCurrentMappingVersion())->id);
    }

    public function enemypatrols(?MappingVersion $mappingVersion = null): HasMany
    {
        return $this->hasMany(EnemyPatrol::class)
            ->where('enemy_patrols.mapping_version_id', ($mappingVersion ?? $this->dungeon->getCurrentMappingVersion())->id);
    }

    public function mapIcons(?MappingVersion $mappingVersion = null): HasMany
    {
        return $this->hasMany(MapIcon::class)->whereNull('dungeon_route_id')
            ->where('map_icons.mapping_version_id', ($mappingVersion ?? $this->dungeon->getCurrentMappingVersion())->id);
    }

    public function mountableAreas(?MappingVersion $mappingVersion = null): HasMany
    {
        return $this->hasMany(MountableArea::class)
            ->where('mountable_areas.mapping_version_id', ($mappingVersion ?? $this->dungeon->getCurrentMappingVersion())->id);
    }

    public function dungeonFloorSwitchMarkers(?MappingVersion $mappingVersion = null): HasMany
    {
        return $this->hasMany(DungeonFloorSwitchMarker::class)
            ->where('mapping_version_id', ($mappingVersion ?? $this->dungeon->getCurrentMappingVersion())->id);
    }

    public function enemiesForExport(): HasMany
    {
        return $this->hasMany(Enemy::class)->orderBy('id');
    }

    public function enemyPacksForExport(): HasMany
    {
        return $this->hasMany(EnemyPack::class)->orderBy('id');
    }

    public function enemyPatrolsForExport(): HasMany
    {
        return $this->hasMany(EnemyPatrol::class)->with('mdtPolyline')->orderBy('id');
    }

    public function mapIconsForExport(): HasMany
    {
        return $this->hasMany(MapIcon::class)->whereNotNull('mapping_version_id')->orderBy('id');
    }

    public function mountableAreasForExport(): HasMany
    {
        return $this->hasMany(MountableArea::class)->orderBy('id');
    }

    public function floorUnionsForExport(): HasMany
    {
        return $this->hasMany(FloorUnion::class)->orderBy('id');
    }

    public function floorUnionAreasForExport(): HasMany
    {
        return $this->hasMany(FloorUnionArea::class)->orderBy('id');
    }

    public function dungeonFloorSwitchMarkersForExport(): HasMany
    {
        return $this->hasMany(DungeonFloorSwitchMarker::class)->orderBy('id');
    }

    public function floorcouplings(): HasMany
    {
        return $this->hasMany(FloorCoupling::class, 'floor1_id');
    }

    public function floorUnions(): HasMany
    {
        return $this->hasMany(FloorUnion::class);
    }

    public function floorUnionAreas(): HasMany
    {
        return $this->hasMany(FloorUnionArea::class);
    }

    /**
     * If this floor is in a union to another floor (this floor will not contain enemies and delegates it to this other floor instead)
     */
    public function floorUnion(): HasOne
    {
        return $this->hasOne(FloorUnion::class, 'target_floor_id');
    }

    /**
     * @return Collection<Floor> A list of all connected floors, regardless of direction
     */
    public function connectedFloors(): Collection
    {
        return $this->directConnectedFloors->merge($this->reverseConnectedFloors);
    }

    public function directConnectedFloors(): BelongsToMany
    {
        return $this->belongsToMany(Floor::class, 'floor_couplings', 'floor1_id', 'floor2_id');
    }

    public function reverseConnectedFloors(): BelongsToMany
    {
        return $this->belongsToMany(Floor::class, 'floor_couplings', 'floor2_id', 'floor1_id');
    }

    public function dungeonSpeedrunRequiredNpcs10Man(): HasMany
    {
        return $this->hasMany(DungeonSpeedrunRequiredNpc::class)
            ->where('difficulty', Dungeon::DIFFICULTY_10_MAN);
    }

    public function dungeonSpeedrunRequiredNpcs25Man(): HasMany
    {
        return $this->hasMany(DungeonSpeedrunRequiredNpc::class)
            ->where('difficulty', Dungeon::DIFFICULTY_25_MAN);
    }

    /**
     * Scope a query to only include active floors.
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('floors.active', 1);
    }

    #[Scope]
    protected function indexOrFacade(
        Builder        $builder,
        MappingVersion $mappingVersion,
        int            $floorIndex,
        ?string        $mapFacadeStyle = null,
    ): Builder {
        $useFacade = (($mapFacadeStyle ?? User::getCurrentUserMapFacadeStyle()) === User::MAP_FACADE_STYLE_FACADE) && $mappingVersion->facade_enabled;

        // Facade should be FORCED to use floor index 1
        if ($useFacade && $floorIndex > 1) {
            $floorIndex = 1;
        }

        // Either grab the facade floor, or grab the requested floor _as long as it's not the facade floor_, otherwise return the default floor
        return $builder->where(
            static fn(Builder $builder) => $builder->when(
                $useFacade,
                static fn(Builder $builder) => $builder->where('facade', 1)->orWhere('default', 1),
            )->when(
                !$useFacade,
                static fn(Builder $builder) => $builder->where('facade', 0)
                    ->where(static function (Builder $builder) use ($floorIndex) {
                        // Either try to resolve the actual floor, or revert to the default if not found
                        $builder->where('index', $floorIndex)
                            ->orWhere('default', 1);
                    }),
            ),
        )->when($useFacade, static fn(Builder $builder) => $builder->orderByDesc('facade'))
            ->when(
                !$useFacade,
                static fn(Builder $builder) => $builder
                    ->orderByRaw('(`index` = ?) DESC', [$floorIndex]) // preferred match first
                    ->orderByDesc('default'), // fallback if index not found
            )
            ->limit(1);
    }

    #[Scope]
    protected function defaultOrFacade(Builder $builder, MappingVersion $mappingVersion): Builder
    {
        $useFacade = (User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE) && $mappingVersion->facade_enabled;

        return $builder->where(static function (Builder $builder) {
            $builder->where('facade', 1)
                ->orWhere('default', 1);
        })->orderByDesc($useFacade ? 'facade' : 'default')
            ->limit(1);
    }

    public function findClosestFloorSwitchMarker(
        CoordinatesServiceInterface $coordinatesService,
        LatLng                      $latLng,
        int                         $targetFloorId,
    ): ?DungeonFloorSwitchMarker {
        $result = null;

        /** @var Collection<DungeonFloorSwitchMarker> $dungeonFloorSwitchMarkers */
        $dungeonFloorSwitchMarkers = $this->dungeonFloorSwitchMarkers()
            ->where('target_floor_id', $targetFloorId)->get();

        if ($dungeonFloorSwitchMarkers->count() > 1) {
            // Find the closest floors switch marker with the same target floor
            $distanceToClosestFloorSwitchMarker = 99999999999;
            foreach ($dungeonFloorSwitchMarkers as $dungeonFloorSwitchMarker) {
                $distanceToFloorSwitchMarker = $coordinatesService->distanceBetweenPoints(
                    $latLng->getLng(),
                    $dungeonFloorSwitchMarker->lng,
                    $latLng->getLat(),
                    $dungeonFloorSwitchMarker->lat,
                );

                if ($distanceToClosestFloorSwitchMarker > $distanceToFloorSwitchMarker) {
                    $distanceToClosestFloorSwitchMarker = $distanceToFloorSwitchMarker;
                    $result                             = $dungeonFloorSwitchMarker;
                }
            }
        } else {
            $result = $dungeonFloorSwitchMarkers->first();
        }

        return $result;
    }

    public function getDungeonId(): ?int
    {
        return $this->dungeon_id;
    }

    public function getMapBounds(): MapBounds
    {
        return new MapBounds($this->ingame_min_x, $this->ingame_min_y, $this->ingame_max_x, $this->ingame_max_y);
    }

    /**
     * @param int|null $dungeonId Can be passed in case the uiMapIds are not unique
     */
    #[Deprecated(message: 'Use FloorRepository::findByUiMapId instead')]
    public static function findByUiMapId(int $uiMapId, ?int $dungeonId = null): ?Floor
    {
        return Floor::where('ui_map_id', self::UI_MAP_ID_MAPPING[$uiMapId] ?? $uiMapId)
            ->when($dungeonId !== null, static fn(Builder $builder) => $builder->where('dungeon_id', $dungeonId))
            ->first();
    }

    public static function isUiMapIdOpenWorld(int $uiMapId): bool
    {
        return in_array($uiMapId, self::UI_MAP_ID_OPEN_WORLD);
    }

    public function ensureConnectionToFloor(Floor $targetFloor): bool
    {
        $hasCoupling = false;
        foreach ($this->floorcouplings as $floorCoupling) {
            if ($floorCoupling->floor2_id === $targetFloor->id) {
                $hasCoupling = true;
                break;
            }
        }

        if (!$hasCoupling) {
            FloorCoupling::create([
                'floor1_id' => $this->id,
                'floor2_id' => $targetFloor->id,
            ]);
        }

        return !$hasCoupling;
    }
}
