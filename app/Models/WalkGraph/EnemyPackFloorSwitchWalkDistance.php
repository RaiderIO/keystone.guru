<?php

namespace App\Models\WalkGraph;

use App\Models\DungeonFloorSwitchMarker;
use App\Models\EnemyPack;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Database\Factories\WalkGraph\EnemyPackFloorSwitchWalkDistanceFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The distance between a pack and a floor switch marker on the same floor. Combined with the marker's link to the
 * other floor, it gives the walk from a pack to the packs of the next floor through that marker. A null walk distance
 * means the walk graph cannot answer for the pair: use the straight-line distance, which is then unverified.
 *
 * @property int        $id
 * @property int        $mapping_version_id
 * @property int        $floor_id
 * @property int        $enemy_pack_id
 * @property int        $dungeon_floor_switch_marker_id
 * @property float      $straight_distance
 * @property float|null $walk_distance
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property MappingVersion           $mappingVersion
 * @property Floor                    $floor
 * @property EnemyPack                $enemyPack
 * @property DungeonFloorSwitchMarker $dungeonFloorSwitchMarker
 *
 * @mixin Eloquent
 */
class EnemyPackFloorSwitchWalkDistance extends Model
{
    /** @use HasFactory<EnemyPackFloorSwitchWalkDistanceFactory> */
    use HasFactory;

    protected $fillable = [
        'mapping_version_id',
        'floor_id',
        'enemy_pack_id',
        'dungeon_floor_switch_marker_id',
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

    /** @return BelongsTo<EnemyPack, $this> */
    public function enemyPack(): BelongsTo
    {
        return $this->belongsTo(EnemyPack::class);
    }

    /** @return BelongsTo<DungeonFloorSwitchMarker, $this> */
    public function dungeonFloorSwitchMarker(): BelongsTo
    {
        return $this->belongsTo(DungeonFloorSwitchMarker::class);
    }
}
