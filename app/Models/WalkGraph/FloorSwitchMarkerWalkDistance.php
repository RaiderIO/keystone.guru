<?php

namespace App\Models\WalkGraph;

use App\Models\DungeonFloorSwitchMarker;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Database\Factories\WalkGraph\FloorSwitchMarkerWalkDistanceFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The distance between two floor switch markers on the same floor, stored once per pair with
 * dungeon_floor_switch_marker_id < other_dungeon_floor_switch_marker_id. It carries a walk across a floor that is only
 * passed through, from the marker it was entered by to the marker it is left by. A null walk distance means the walk
 * graph cannot answer for the pair: use the straight-line distance, which is then unverified.
 *
 * @property int        $id
 * @property int        $mapping_version_id
 * @property int        $floor_id
 * @property int        $dungeon_floor_switch_marker_id
 * @property int        $other_dungeon_floor_switch_marker_id
 * @property float      $straight_distance
 * @property float|null $walk_distance
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property MappingVersion           $mappingVersion
 * @property Floor                    $floor
 * @property DungeonFloorSwitchMarker $dungeonFloorSwitchMarker
 * @property DungeonFloorSwitchMarker $otherDungeonFloorSwitchMarker
 *
 * @mixin Eloquent
 */
class FloorSwitchMarkerWalkDistance extends Model
{
    /** @use HasFactory<FloorSwitchMarkerWalkDistanceFactory> */
    use HasFactory;

    protected $fillable = [
        'mapping_version_id',
        'floor_id',
        'dungeon_floor_switch_marker_id',
        'other_dungeon_floor_switch_marker_id',
        'straight_distance',
        'walk_distance',
    ];

    public function casts(): array
    {
        return [
            'straight_distance' => 'float',
            'walk_distance'     => 'float',
        ];
    }

    /** @return BelongsTo<MappingVersion, $this> */
    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingVersion::class);
    }

    /** @return BelongsTo<Floor, $this> */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /** @return BelongsTo<DungeonFloorSwitchMarker, $this> */
    public function dungeonFloorSwitchMarker(): BelongsTo
    {
        return $this->belongsTo(DungeonFloorSwitchMarker::class);
    }

    /** @return BelongsTo<DungeonFloorSwitchMarker, $this> */
    public function otherDungeonFloorSwitchMarker(): BelongsTo
    {
        return $this->belongsTo(DungeonFloorSwitchMarker::class, 'other_dungeon_floor_switch_marker_id');
    }
}
