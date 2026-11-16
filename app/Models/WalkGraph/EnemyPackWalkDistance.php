<?php

namespace App\Models\WalkGraph;

use App\Models\EnemyPack;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Database\Factories\WalkGraph\EnemyPackWalkDistanceFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The distance between two packs on the same floor, stored once per pair with enemy_pack_id < other_enemy_pack_id.
 * A null walk distance means the walk graph cannot answer for the pair (a pack nobody walked near, or a detour
 * beyond the search limit): use the straight-line distance, which is then unverified.
 *
 * @property int        $id
 * @property int        $mapping_version_id
 * @property int        $floor_id
 * @property int        $enemy_pack_id
 * @property int        $other_enemy_pack_id
 * @property float      $straight_distance
 * @property float|null $walk_distance
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property MappingVersion $mappingVersion
 * @property Floor          $floor
 * @property EnemyPack      $enemyPack
 * @property EnemyPack      $otherEnemyPack
 *
 * @mixin Eloquent
 */
class EnemyPackWalkDistance extends Model
{
    /** @use HasFactory<EnemyPackWalkDistanceFactory> */
    use HasFactory;

    protected $fillable = [
        'mapping_version_id',
        'floor_id',
        'enemy_pack_id',
        'other_enemy_pack_id',
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

    /** @return BelongsTo<EnemyPack, $this> */
    public function otherEnemyPack(): BelongsTo
    {
        return $this->belongsTo(EnemyPack::class, 'other_enemy_pack_id');
    }
}
