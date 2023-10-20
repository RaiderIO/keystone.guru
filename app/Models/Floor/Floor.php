<?php

namespace App\Models\Floor;

use App\Logic\Utils\MathUtils;
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
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * @property int                                     $id
 * @property int                                     $dungeon_id
 * @property int                                     $index
 * @property int|null                                $mdt_sub_level
 * @property int|null                                $ui_map_id
 * @property string                                  $name
 * @property boolean                                 $default
 * @property int                                     $min_enemy_size
 * @property int                                     $max_enemy_size
 * @property int                                     $enemy_engagement_max_range         When generating dungeon routes, this is the maximum range from engagement of an enemy where we consider enemies in the mapping to match up
 * @property int                                     $enemy_engagement_max_range_patrols The max range after which we're considering patrols
 * @property float                                   $ingame_min_x
 * @property float                                   $ingame_min_y
 * @property float                                   $ingame_max_x
 * @property float                                   $ingame_max_y
 * @property int|null                                $percentage_display_zoom
 * @property boolean                                 $active
 *
 * @property Dungeon                                 $dungeon
 *
 * @property FloorUnion|null                         $floorUnion
 *
 * @property Collection|Enemy[]                      $enemies
 * @property Collection|EnemyPack[]                  $enemypacks
 * @property Collection|EnemyPatrol[]                $enemypatrols
 * @property Collection|MapIcon[]                    $mapicons
 * @property Collection|DungeonFloorSwitchMarker[]   $dungeonfloorswitchmarkers
 * @property Collection|MountableArea[]              $mountableareas
 * @property Collection|FloorUnion[]                 $floorUnions
 * @property Collection|FloorUnionArea[]             $floorUnionAreas
 *
 * @property Collection|Enemy[]                      $enemiesForExport
 * @property Collection|EnemyPack[]                  $enemyPacksForExport
 * @property Collection|EnemyPatrol[]                $enemyPatrolsForExport
 * @property Collection|MapIcon[]                    $mapIconsForExport
 * @property Collection|DungeonFloorSwitchMarker[]   $dungeonFloorSwitchMarkersForExport
 * @property Collection|MountableArea[]              $mountableAreasForExport
 * @property Collection|FloorUnion[]                 $floorUnionsForExport
 * @property Collection|FloorUnionArea[]             $floorUnionAreasForExport
 *
 * @property Collection|FloorCoupling[]              $floorcouplings
 * @property Collection|DungeonSpeedrunRequiredNpc[] $dungeonspeedrunrequirednpcs
 * @property Collection|Floor[]                      $connectedFloors
 * @property Collection|Floor[]                      $directConnectedFloors
 * @property Collection|Floor[]                      $reverseConnectedFloors
 *
 * @method static Builder active()
 *
 * @mixin Eloquent
 */
class Floor extends CacheModel implements MappingModelInterface
{
    use HasFactory;

    /** @var int Y */
    const MAP_MAX_LAT = -256;

    /** @var int X */
    const MAP_MAX_LNG = 384;


    // Can map certain floors to others here, so that we can put enemies that are on their own floor (like some final
    // bosses) and put them on the main floor without introducing a 2nd floor.
    const UI_MAP_ID_MAPPING = [
        // Court of Stars
        762  => 761,
        763  => 761,
        // Brackenhide Hollow
        2106 => 2096,
        // Temple of the Jade Serpent
        430  => 429,
        // Algeth'ar Academy
        2099 => 2097,
        // Ruby Life Pools (Dragon Isles zone)
        1978 => 2095,
        // Nokhud Offensive
        2023 => 2093,
    ];

    protected $fillable = [
        'dungeon_id',
        'index',
        'name',
        'default',
        'ui_map_id',
        'enemy_engagement_max_range',
        'enemy_engagement_max_range_patrols',
        'ingame_min_x',
        'ingame_min_y',
        'ingame_max_x',
        'ingame_max_y',
        'active',
    ];

    public $timestamps = false;

    public $hidden = ['dungeon', 'dungeon_id', 'created_at', 'updated_at'];

    /**
     * @return BelongsTo
     */
    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }

    /**
     * @param MappingVersion|null $mappingVersion
     *
     * @return HasMany
     */
    public function enemies(?MappingVersion $mappingVersion = null): HasMany
    {
        return $this->hasMany(Enemy::class)
                    ->where('enemies.mapping_version_id', ($mappingVersion ?? $this->dungeon->getCurrentMappingVersion())->id);
    }

    /**
     * @param MappingVersion|null $mappingVersion
     *
     * @return HasMany
     */
    public function enemypacks(?MappingVersion $mappingVersion = null): HasMany
    {
        return $this->hasMany(EnemyPack::class)
                    ->where('enemy_packs.mapping_version_id', ($mappingVersion ?? $this->dungeon->getCurrentMappingVersion())->id);
    }

    /**
     * @param MappingVersion|null $mappingVersion
     *
     * @return HasMany
     */
    public function enemypatrols(?MappingVersion $mappingVersion = null): HasMany
    {
        return $this->hasMany(EnemyPatrol::class)
                    ->where('enemy_patrols.mapping_version_id', ($mappingVersion ?? $this->dungeon->getCurrentMappingVersion())->id);
    }

    /**
     * @param MappingVersion|null $mappingVersion
     *
     * @return HasMany
     */
    public function mapicons(?MappingVersion $mappingVersion = null): HasMany
    {
        return $this->hasMany(MapIcon::class)->whereNull('dungeon_route_id')
                    ->where('map_icons.mapping_version_id', ($mappingVersion ?? $this->dungeon->getCurrentMappingVersion())->id);
    }

    /**
     * @param MappingVersion|null $mappingVersion
     *
     * @return HasMany
     */
    public function mountableareas(?MappingVersion $mappingVersion = null): HasMany
    {
        return $this->hasMany(MountableArea::class)
                    ->where('mountable_areas.mapping_version_id', ($mappingVersion ?? $this->dungeon->getCurrentMappingVersion())->id);
    }

    /**
     * @param MappingVersion|null $mappingVersion
     *
     * @return HasMany
     */
    public function dungeonfloorswitchmarkers(?MappingVersion $mappingVersion = null): HasMany
    {
        return $this->hasMany(DungeonFloorSwitchMarker::class)
                    ->where('mapping_version_id', ($mappingVersion ?? $this->dungeon->getCurrentMappingVersion())->id);
    }

    /**
     * @return HasMany
     */
    public function enemiesForExport(): HasMany
    {
        return $this->hasMany(Enemy::class);
    }

    /**
     * @return HasMany
     */
    public function enemyPacksForExport(): HasMany
    {
        return $this->hasMany(EnemyPack::class);
    }

    /**
     * @return HasMany
     */
    public function enemyPatrolsForExport(): HasMany
    {
        return $this->hasMany(EnemyPatrol::class);
    }

    /**
     * @return HasMany
     */
    public function mapIconsForExport(): HasMany
    {
        return $this->hasMany(MapIcon::class)->where('dungeon_route_id', null);
    }

    /**
     * @return HasMany
     */
    public function mountableAreasForExport(): HasMany
    {
        return $this->hasMany(MountableArea::class);
    }

    /**
     * @return HasMany
     */
    public function floorUnionsForExport(): HasMany
    {
        return $this->hasMany(FloorUnion::class);
    }

    /**
     * @return HasMany
     */
    public function floorUnionAreasForExport(): HasMany
    {
        return $this->hasMany(FloorUnionArea::class);
    }

    /**
     * @return HasMany
     */
    public function dungeonFloorSwitchMarkersForExport(): HasMany
    {
        return $this->hasMany(DungeonFloorSwitchMarker::class);
    }

    /**
     * @return HasMany
     */
    public function floorcouplings(): HasMany
    {
        return $this->hasMany(FloorCoupling::class, 'floor1_id');
    }

    /**
     * @return HasMany
     */
    public function floorUnions(): HasMany
    {
        return $this->hasMany(FloorUnion::class);
    }

    /**
     * @return HasMany
     */
    public function floorUnionAreas(): HasMany
    {
        return $this->hasMany(FloorUnionArea::class);
    }

    /**
     * If this floor is in a union to another floor (this floor will not contain enemies and delegates it to this other floor instead)
     *
     * @return HasOne
     */
    public function floorUnion(): HasOne
    {
        return $this->hasOne(FloorUnion::class, 'target_floor_id');
    }

    /**
     * @return Collection|Floor[] A list of all connected floors, regardless of direction
     */
    public function connectedFloors(): Collection
    {
        return $this->directConnectedFloors->merge($this->reverseConnectedFloors);
    }

    /**
     * @return BelongsToMany
     */
    public function directConnectedFloors(): BelongsToMany
    {
        return $this->belongsToMany(Floor::class, 'floor_couplings', 'floor1_id', 'floor2_id');
    }

    /**
     * @return BelongsToMany
     */
    public function reverseConnectedFloors(): BelongsToMany
    {
        return $this->belongsToMany(Floor::class, 'floor_couplings', 'floor2_id', 'floor1_id');
    }

    /**
     * @return HasMany
     */
    public function dungeonSpeedrunRequiredNpcs10Man(): HasMany
    {
        return $this->hasMany(DungeonSpeedrunRequiredNpc::class)
                    ->where('difficulty', Dungeon::DIFFICULTY_10_MAN);
    }

    /**
     * @return HasMany
     */
    public function dungeonSpeedrunRequiredNpcs25Man(): HasMany
    {
        return $this->hasMany(DungeonSpeedrunRequiredNpc::class)
                    ->where('difficulty', Dungeon::DIFFICULTY_25_MAN);
    }

    /**
     * Scope a query to only include active floors.
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('floors.active', 1);
    }

    /**
     * @param float $lat
     * @param float $lng
     * @param int   $targetFloorId
     *
     * @return DungeonFloorSwitchMarker|null
     */
    public function findClosestFloorSwitchMarker(float $lat, float $lng, int $targetFloorId): ?DungeonFloorSwitchMarker
    {
        $result = null;

        /** @var Collection|DungeonFloorSwitchMarker[] $dungeonFloorSwitchMarkers */
        $dungeonFloorSwitchMarkers = $this->dungeonfloorswitchmarkers()->where('target_floor_id', $targetFloorId)->get();

        if ($dungeonFloorSwitchMarkers->count() > 1) {
            // Find the closest floors switch marker with the same target floor
            $distanceToClosestFloorSwitchMarker = 99999999999;
            foreach ($dungeonFloorSwitchMarkers as $dungeonFloorSwitchMarker) {
                $distanceToFloorSwitchMarker = MathUtils::distanceBetweenPoints($lng, $dungeonFloorSwitchMarker->lng, $lat, $dungeonFloorSwitchMarker->lat);
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

    /**
     * @param float $lat
     * @param float $lng
     *
     * @return array{x: float, y: float}
     */
    public function calculateIngameLocationForMapLocation(float $lat, float $lng): array
    {
        $ingameMapSizeX = $this->ingame_max_x - $this->ingame_min_x;
        $ingameMapSizeY = $this->ingame_max_y - $this->ingame_min_y;

        // Invert the lat/lngs
        $factorLat = ((self::MAP_MAX_LAT - $lat) / self::MAP_MAX_LAT);
        $factorLng = ((self::MAP_MAX_LNG - $lng) / self::MAP_MAX_LNG);

        return [
            'x' => ($ingameMapSizeX * $factorLng) + $this->ingame_min_x,
            'y' => ($ingameMapSizeY * $factorLat) + $this->ingame_min_y,
        ];
    }

    /**
     * @param float $x
     * @param float $y
     *
     * @return array{lat: float, lng: float}
     */
    public function calculateMapLocationForIngameLocation(float $x, float $y): array
    {
        $ingameMapSizeX = $this->ingame_max_x - $this->ingame_min_x;
        $ingameMapSizeY = $this->ingame_max_y - $this->ingame_min_y;

        $factorX = (($this->ingame_min_x - $x) / $ingameMapSizeX);
        $factorY = (($this->ingame_min_y - $y) / $ingameMapSizeY);

        return [
            'lat' => (self::MAP_MAX_LAT * $factorY) + self::MAP_MAX_LAT,
            'lng' => (self::MAP_MAX_LNG * $factorX) + self::MAP_MAX_LNG,
        ];
    }

    /**
     * @return int|null
     */
    public function getDungeonId(): ?int
    {
        return $this->dungeon_id;
    }

    /**
     * @param int      $uiMapId
     * @param int|null $dungeonId Can be passed in case the uiMapIds are not unique
     *
     * @return Floor
     */
    public static function findByUiMapId(int $uiMapId, int $dungeonId = null): Floor
    {
        return Floor
            ::where('ui_map_id', self::UI_MAP_ID_MAPPING[$uiMapId] ?? $uiMapId)
            ->when($dungeonId !== null, function (Builder $builder) use ($dungeonId) {
                return $builder->where('dungeon_id', $dungeonId);
            })
            ->firstOrFail();
    }

    /**
     * @param Floor $targetFloor
     *
     * @return bool
     */
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
