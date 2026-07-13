<?php

namespace App\Models\CombatLog;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Traits\HasLatLng;
use Database\Factories\CombatLog\CombatLogRouteEnemyFailureFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int      $id
 * @property int|null $dungeon_route_id
 * @property int      $dungeon_id
 * @property int      $floor_id
 * @property int      $mapping_version_id
 * @property int|null $npc_id
 * @property float    $lat
 * @property float    $lng
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Dungeon        $dungeon
 * @property Floor          $floor
 * @property MappingVersion $mappingVersion
 * @property Npc|null       $npc
 *
 * @mixin Eloquent
 */
class CombatLogRouteEnemyFailure extends Model
{
    /** @use HasFactory<CombatLogRouteEnemyFailureFactory> */
    use HasFactory, HasLatLng;

    protected $connection = 'combatlog';

    protected $fillable = [
        'dungeon_route_id',
        'dungeon_id',
        'floor_id',
        'mapping_version_id',
        'npc_id',
        'lat',
        'lng',
    ];

    /**
     * @return BelongsTo<Dungeon, $this>
     */
    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }

    /**
     * @return BelongsTo<Floor, $this>
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * @return BelongsTo<MappingVersion, $this>
     */
    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingVersion::class);
    }

    /**
     * @return BelongsTo<Npc, $this>
     */
    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }
}
