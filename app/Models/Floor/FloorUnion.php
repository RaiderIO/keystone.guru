<?php

namespace App\Models\Floor;

use App\Models\CacheModel;
use App\Models\Interfaces\EventModelInterface;
use App\Models\Mapping\CloneForNewMappingVersionNoRelations;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Traits\HasLatLng;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int                        $id
 * @property int                        $mapping_version_id
 * @property int                        $floor_id
 * @property int                        $target_floor_id
 * @property float                      $lat
 * @property float                      $lng
 * @property float                      $size
 * @property float                      $rotation
 * @property MappingVersion             $mappingVersion
 * @property Floor                      $floor
 * @property Floor                      $targetFloor
 * @property Collection<FloorUnionArea> $floorUnionAreas
 *
 * @mixin Eloquent
 */
class FloorUnion extends CacheModel implements MappingModelCloneableInterface, MappingModelInterface, EventModelInterface
{
    use CloneForNewMappingVersionNoRelations;
    use HasLatLng;
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'mapping_version_id',
        'floor_id',
        'target_floor_id',
        'lat',
        'lng',
        'size',
        'rotation',
    ];

    protected $with = [
        'floorUnionAreas',
    ];

    protected $hidden = ['mappingVersion', 'floor'];

    protected $casts = [
        'mapping_version_id' => 'integer',
        'floor_id'           => 'integer',
        'target_floor_id'    => 'integer',
        'lat'                => 'float',
        'lng'                => 'float',
        'size'               => 'float',
        'rotation'           => 'float',
    ];

    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingVersion::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function targetFloor(): BelongsTo
    {
        return $this->belongsTo(Floor::class, 'target_floor_id');
    }

    public function floorUnionAreas(): HasMany
    {
        return $this->hasMany(FloorUnionArea::class);
    }

    public function getDungeonId(): ?int
    {
        return $this->floor->dungeon_id;
    }

    public function getEventData(): array
    {
        return [];
    }
}
