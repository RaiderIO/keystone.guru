<?php

namespace App\Models;

use App\Logic\Utils\MathUtils;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $dungeon_id
 * @property int $index
 * @property int|null $mdt_sub_level
 * @property string $name
 * @property boolean $default
 * @property int $min_enemy_size
 * @property int $max_enemy_size
 * @property int $ingame_min_x
 * @property int $ingame_min_y
 * @property int $ingame_max_x
 * @property int $ingame_max_y
 * @property int|null $percentage_display_zoom
 *
 * @property Dungeon $dungeon
 *
 * @property Collection|Enemy[] $enemies
 * @property Collection|EnemyPack[] $enemypacks
 * @property Collection|EnemyPatrol[] $enemypatrols
 * @property Collection|MapIcon[] $mapicons
 * @property Collection|DungeonFloorSwitchMarker[] $dungeonfloorswitchmarkers
 * @property Collection|MountableArea[] $mountableareas
 *
 * @property Collection|Enemy[] $enemiesForExport
 * @property Collection|EnemyPack[] $enemyPacksForExport
 * @property Collection|EnemyPatrol[] $enemyPatrolsForExport
 * @property Collection|MapIcon[] $mapIconsForExport
 * @property Collection|DungeonFloorSwitchMarker[] $dungeonFloorSwitchMarkersForExport
 * @property Collection|MountableArea[] $mountableAreasForExport
 *
 * @property Collection|DungeonSpeedrunRequiredNpc[] $dungeonspeedrunrequirednpcs
 * @property Collection|Floor[] $connectedFloors
 * @property Collection|Floor[] $directConnectedFloors
 * @property Collection|Floor[] $reverseConnectedFloors
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

    protected $fillable = ['ingame_min_x', 'ingame_min_y', 'ingame_max_x', 'ingame_max_y'];

    public $timestamps = false;

    public $hidden = ['dungeon_id', 'created_at', 'updated_at'];

    /**
     * @return BelongsTo
     */
    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }

    /**
     * @param MappingVersion|null $mappingVersion
     * @return HasMany
     */
    public function enemies(?MappingVersion $mappingVersion = null): HasMany
    {
        return $this->hasMany(Enemy::class)
            ->where('enemies.mapping_version_id', ($mappingVersion ?? $this->dungeon->getCurrentMappingVersion())->id);
    }

    /**
     * @param MappingVersion|null $mappingVersion
     * @return HasMany
     */
    public function enemypacks(?MappingVersion $mappingVersion = null): HasMany
    {
        return $this->hasMany(EnemyPack::class)
            ->where('enemy_packs.mapping_version_id', ($mappingVersion ?? $this->dungeon->getCurrentMappingVersion())->id);
    }

    /**
     * @param MappingVersion|null $mappingVersion
     * @return HasMany
     */
    public function enemypatrols(?MappingVersion $mappingVersion = null): HasMany
    {
        return $this->hasMany(EnemyPatrol::class)
            ->where('enemy_patrols.mapping_version_id', ($mappingVersion ?? $this->dungeon->getCurrentMappingVersion())->id);
    }

    /**
     * @param MappingVersion|null $mappingVersion
     * @return HasMany
     */
    public function mapicons(?MappingVersion $mappingVersion = null): HasMany
    {
        return $this->hasMany(MapIcon::class)->whereNull('dungeon_route_id')
            ->where('map_icons.mapping_version_id', ($mappingVersion ?? $this->dungeon->getCurrentMappingVersion())->id);
    }

    /**
     * @param MappingVersion|null $mappingVersion
     * @return HasMany
     */
    public function mountableareas(?MappingVersion $mappingVersion = null): HasMany
    {
        return $this->hasMany(MountableArea::class)
            ->where('mountable_areas.mapping_version_id', ($mappingVersion ?? $this->dungeon->getCurrentMappingVersion())->id);
    }

    /**
     * @param MappingVersion|null $mappingVersion
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
        return $this->hasMany(MapIcon::class)->where('dungeon_route_id', -1);
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
    public function dungeonspeedrunrequirednpcs(): HasMany
    {
        return $this->hasMany(DungeonSpeedrunRequiredNpc::class);
    }

    /**
     * @param float $lat
     * @param float $lng
     * @param int $targetFloorId
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
                if ($distanceToClosestFloorSwitchMarker < $distanceToFloorSwitchMarker) {
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
     * @return array{x: float, y: float}
     */
    public function calculateIngameLocationForMapLocation(float $lat, float $lng): array
    {
        $ingameMapSizeX = $this->ingame_max_x - $this->ingame_min_x;
        $ingameMapSizeY = $this->ingame_max_y - $this->ingame_min_y;

        $factorLat = ($lat / self::MAP_MAX_LAT);
        $factorLng = ($lng / self::MAP_MAX_LNG);

        return [
            'x' => ($ingameMapSizeX * $factorLng) + $this->ingame_min_x,
            'y' => ($ingameMapSizeY * $factorLat) + $this->ingame_min_y,
        ];
    }

    /**
     * @param float $x
     * @param float $y
     * @return array{x: float, y: float}
     */
    public function calculateMapLocationForIngameLocation(float $x, float $y): array
    {
        $ingameMapSizeX = $this->ingame_max_x - $this->ingame_min_x;
        $ingameMapSizeY = $this->ingame_max_y - $this->ingame_min_y;

        $factorX = (($x - $this->ingame_min_x) / $ingameMapSizeX);
        $factorY = (($y - $this->ingame_min_y) / $ingameMapSizeY);

        return [
            'lat' => (self::MAP_MAX_LAT * $factorY),
            'lng' => (self::MAP_MAX_LNG * $factorX),
        ];
    }

    /**
     * @return int
     */
    public function getDungeonId(): int
    {
        return $this->dungeon_id;
    }
}
