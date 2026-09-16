<?php

namespace App\Models\CombatLog;

use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Traits\HasLatLng;
use Database\Factories\CombatLog\CombatLogRouteEnemyResolutionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An engaged enemy the Auto Route Creator did resolve to a mapped enemy, but only from far away. Recorded for the
 * outliers alone - anything closer than the configured threshold is assumed to be a correct match and is not stored.
 *
 * @property int         $id
 * @property int|null    $dungeon_route_id
 * @property string|null $source             null when this environment recorded the row itself, otherwise the remote host it was imported from (production, staging)
 * @property int         $dungeon_id
 * @property int         $floor_id
 * @property int         $mapping_version_id
 * @property int|null    $npc_id
 * @property int         $enemy_id
 * @property float       $lat                where the engagement happened
 * @property float       $lng
 * @property float       $enemy_lat          where the enemy it resolved to is mapped
 * @property float       $enemy_lng
 * @property float       $distance           ingame yards between the two
 * @property float       $weighted_distance  the distance the matcher judged on, skewed by the enemy's kill priority
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Dungeon        $dungeon
 * @property Floor          $floor
 * @property MappingVersion $mappingVersion
 * @property Npc|null       $npc
 * @property Enemy|null     $enemy
 *
 * @mixin Eloquent
 */
class CombatLogRouteEnemyResolution extends Model
{
    /** @use HasFactory<CombatLogRouteEnemyResolutionFactory> */
    use HasFactory, HasLatLng;

    protected $connection = 'combatlog';

    protected $fillable = [
        'dungeon_route_id',
        'source',
        'dungeon_id',
        'floor_id',
        'mapping_version_id',
        'npc_id',
        'enemy_id',
        'lat',
        'lng',
        'enemy_lat',
        'enemy_lng',
        'distance',
        'weighted_distance',
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

    /**
     * @return BelongsTo<Enemy, $this>
     */
    public function enemy(): BelongsTo
    {
        return $this->belongsTo(Enemy::class);
    }
}
