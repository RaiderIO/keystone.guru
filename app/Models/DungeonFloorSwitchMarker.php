<?php

namespace App\Models;

use App\Models\Floor\Floor;
use App\Models\Floor\FloorCoupling;
use App\Models\Mapping\CloneForNewMappingVersionNoRelations;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Traits\HasLatLng;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int                           $id
 * @property int                           $mapping_version_id
 * @property int                           $floor_id
 * @property int                           $source_floor_id
 * @property int                           $target_floor_id
 * @property int|null                      $linked_dungeon_floor_switch_marker_id
 * @property float                         $lat
 * @property float                         $lng
 * @property string                        $direction
 * @property string                        $floorCouplingDirection
 * @property Floor                         $floor
 * @property Floor|null                    $sourceFloor
 * @property Floor                         $targetFloor
 * @property DungeonFloorSwitchMarker|null $linkedDungeonFloorSwitchMarker
 *
 * @mixin Eloquent
 */
class DungeonFloorSwitchMarker extends CacheModel implements MappingModelCloneableInterface, MappingModelInterface
{
    use CloneForNewMappingVersionNoRelations;
    use HasLatLng;
    use SeederModel;

    protected $appends = ['floorCouplingDirection']; // , 'ingameX', 'ingameY'

    protected $hidden = ['mappingVersion', 'floor', 'targetFloor', 'sourceFloor', 'laravel_through_key'];

    protected $fillable = [
        'id',
        'mapping_version_id',
        'floor_id',
        'source_floor_id',
        'target_floor_id',
        'linked_dungeon_floor_switch_marker_id',
        'direction',
        'lat',
        'lng',
    ];

    protected $casts = [
        'mapping_version_id'                    => 'integer',
        'floor_id'                              => 'integer',
        'source_floor_id'                       => 'integer',
        'target_floor_id'                       => 'integer',
        'linked_dungeon_floor_switch_marker_id' => 'integer',
        'lat'                                   => 'float',
        'lng'                                   => 'float',
    ];

    public $timestamps = false;

    private string $floorCouplingDirection = 'unknown';

    //    /** @var float Future Laravel-me, please find a better solution for this Q.Q */
    //    private float $ingameX = 0;
    //    private float $ingameY = 0;

    public function getFloorCouplingDirectionAttribute(): string
    {
        // Prevent double setting
        if ($this->floorCouplingDirection !== 'unknown') {
            return $this->floorCouplingDirection;
        }

        /** @var FloorCoupling|null $floorCoupling */
        $floorCoupling = FloorCoupling::where('floor1_id', $this->source_floor_id ?? $this->floor_id)
            ->where('floor2_id', $this->target_floor_id)
            ->first();

        return $this->floorCouplingDirection = ($floorCoupling === null ? 'unknown' : $floorCoupling->direction);
    }

    //
    //    /**
    //     * @return float
    //     */
    //    public function getIngameXAttribute(): float
    //    {
    //        return $this->ingameX;
    //    }
    //
    //    /**
    //     * @return float
    //     */
    //    public function getIngameYAttribute(): float
    //    {
    //        return $this->ingameY;
    //    }

    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingVersion::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function sourceFloor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function targetFloor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function linkedDungeonFloorSwitchMarker(): HasOne
    {
        return $this->hasOne(DungeonFloorSwitchMarker::class);
    }

    public function getDungeonId(): ?int
    {
        return $this->floor?->dungeon_id ?? null;
    }

    public function getMdtDirection(): int
    {
        $direction = $this->direction;

        $result = match ($direction) {
            FloorCoupling::DIRECTION_UP => 1,
            FloorCoupling::DIRECTION_DOWN => -1,
            FloorCoupling::DIRECTION_LEFT => -2,
            default => 2,
        };

        return $result;
    }

    //    /**
    //     * @param float $ingameX
    //     * @return DungeonFloorSwitchMarker
    //     */
    //    public function setIngameX(float $ingameX): DungeonFloorSwitchMarker
    //    {
    //        $this->ingameX = $ingameX;
    //
    //        return $this;
    //    }
    //
    //    /**
    //     * @param float $ingameY
    //     * @return DungeonFloorSwitchMarker
    //     */
    //    public function setIngameY(float $ingameY): DungeonFloorSwitchMarker
    //    {
    //        $this->ingameY = $ingameY;
    //
    //        return $this;
    //    }
}
