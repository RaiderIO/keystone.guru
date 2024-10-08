<?php

namespace App\Models\Floor;

use App\Logic\Structs\LatLng;
use App\Models\CacheModel;
use App\Models\Interfaces\ConvertsVerticesInterface;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Traits\HasVertices;
use App\Models\Traits\SeederModel;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int            $id
 * @property int            $mapping_version_id
 * @property int            $floor_id
 * @property int            $floor_union_id
 * @property string         $vertices_json
 * @property MappingVersion $mappingVersion
 * @property Floor          $floor
 * @property FloorUnion     $floorUnion
 *
 * @mixin Eloquent
 */
class FloorUnionArea extends CacheModel implements ConvertsVerticesInterface, MappingModelCloneableInterface, MappingModelInterface
{
    use HasVertices;
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'mapping_version_id',
        'floor_id',
        'floor_union_id',
        'vertices_json',
    ];

    protected $hidden = ['mappingVersion', 'floor'];

    protected $casts = [
        'mapping_version_id' => 'integer',
        'floor_id'           => 'integer',
        'floor_union_id'     => 'integer',
    ];

    private ?array $cachedVertices = null;

    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingVersion::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function floorUnion(): BelongsTo
    {
        return $this->belongsTo(FloorUnion::class);
    }

    public function containsPoint(CoordinatesServiceInterface $coordinatesService, LatLng $latLng): bool
    {
        if (!is_array($this->cachedVertices)) {
            $this->cachedVertices = json_decode($this->vertices_json, true);
        }

        return $coordinatesService->polygonContainsPoint($latLng, $this->cachedVertices);
    }

    public function getDungeonId(): ?int
    {
        return $this->floor->dungeon_id;
    }

    public function cloneForNewMappingVersion(MappingVersion $mappingVersion, ?MappingModelInterface $newParent = null): Model
    {
        $clone                     = clone $this;
        $clone->exists             = false;
        $clone->id                 = null;
        $clone->mapping_version_id = $mappingVersion->id;
        $clone->floor_union_id     = $newParent?->id ?? 0;
        $clone->save();

        return $clone;
    }
}
