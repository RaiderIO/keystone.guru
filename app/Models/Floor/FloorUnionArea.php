<?php

namespace App\Models\Floor;

use App\Models\CacheModel;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int            $id
 * @property int            $mapping_version_id
 * @property int            $floor_id
 * @property int            $floor_union_id
 * @property string         $vertices_json
 *
 * @property MappingVersion $mappingVersion
 * @property Floor          $floor
 * @property FloorUnion     $floorUnion
 *
 * @mixin Eloquent
 */
class FloorUnionArea extends CacheModel implements MappingModelInterface
{
    public $timestamps = false;

    protected $fillable = [
        'mapping_version_id',
        'floor_id',
        'floor_union_id',
        'vertices_json',
    ];

    protected $hidden = ['floor'];

    protected $casts = [
        'mapping_version_id' => 'integer',
        'floor_id'           => 'integer',
        'floor_union_id'     => 'integer',
    ];

    /**
     * @return BelongsTo
     */
    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingVersion::class);
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
    public function floorUnion(): BelongsTo
    {
        return $this->belongsTo(FloorUnion::class);
    }

    public function getDungeonId(): ?int
    {
        return $this->floor->dungeon_id;
    }
}
