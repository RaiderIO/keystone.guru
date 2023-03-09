<?php

namespace App\Models;

use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\CloneForNewMappingVersionNoRelations;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $mapping_version_id
 * @property int $floor_id
 * @property int $target_floor_id
 * @property float $lat
 * @property float $lng
 * @property string $direction
 *
 * @property Floor $floor
 * @property Floor $targetfloor
 *
 * @mixin Eloquent
 */
class DungeonFloorSwitchMarker extends CacheModel implements MappingModelInterface, MappingModelCloneableInterface
{
    use CloneForNewMappingVersionNoRelations;

    protected $appends = ['direction'];
    protected $hidden = ['floor', 'targetfloor', 'laravel_through_key'];
    protected $fillable = [
        'id',
        'mapping_version_id',
        'floor_id',
        'target_floor_id',
        'lat',
        'lng',
    ];

    public $timestamps = false;

    /**
     * @return string
     */
    public function getDirectionAttribute(): string
    {
        /** @var FloorCoupling $floorCoupling */
        $floorCoupling = FloorCoupling::where('floor1_id', $this->floor_id)->where('floor2_id', $this->target_floor_id)->first();

        return $floorCoupling === null ? 'unknown' : $floorCoupling->direction;
    }

    /**
     * @return BelongsTo
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * @return BelongsTo
     */
    public function targetfloor(): BelongsTo
    {
        return $this->belongsTo(Floor::class, 'target_floor_id');
    }

    /**
     * @return int|null
     */
    public function getDungeonId(): ?int
    {
        return optional($this->floor)->dungeon_id ?? null;
    }

    /**
     * @return int
     */
    public function getMdtDirection(): int
    {
        $direction = $this->direction;

        switch ($direction) {
            case FloorCoupling::DIRECTION_UP:
                $result = 1;
                break;
            case FloorCoupling::DIRECTION_DOWN:
                $result = -1;
                break;
            case FloorCoupling::DIRECTION_LEFT:
                $result = -2;
                break;
            default:
                $result = 2;
                break;
        }

        return $result;
    }
}
